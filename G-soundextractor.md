```cpp
#include <QObject>
#include <QImage>
#include <QPainter>
#include <QDebug>
#include <QList>
#include <QUrl>
#include <QFileInfo>

extern "C" {
#include <libavformat/avformat.h>
#include <libavcodec/avcodec.h>
#include <libavutil/avutil.h>
#include <libavutil/opt.h>
#include <libswresample/swresample.h>
#include <libavutil/channel_layout.h>
#include <libavutil/samplefmt.h>
}

class AudioWaveformExtractor : public QObject {
    Q_OBJECT

public:
    explicit AudioWaveformExtractor(QObject *parent = nullptr) : QObject(parent) {}

    Q_INVOKABLE QList<QImage> generateWaveform(const QString &filePath, int imageWidth = 1920, int imageHeight = 200, int imagesPerSecond = 1) {
        QList<QImage> waveformImages;

        // Convert QString to const char*
        QByteArray pathBytes = QUrl(filePath).toLocalFile().toUtf8();
        const char *inputFile = pathBytes.constData();

        AVFormatContext *formatCtx = nullptr;
        AVCodecContext *codecCtx = nullptr;
        AVCodec *codec = nullptr;
        SwrContext *swrCtx = nullptr;
        AVPacket *packet = av_packet_alloc();
        AVFrame *frame = av_frame_alloc();

        int audioStreamIndex = -1;
        int ret = 0;

        // Open input file
        if ((ret = avformat_open_input(&formatCtx, inputFile, nullptr, nullptr)) < 0) {
            qDebug() << "Could not open input file:" << filePath;
            goto cleanup;
        }

        // Find stream info
        if ((ret = avformat_find_stream_info(formatCtx, nullptr)) < 0) {
            qDebug() << "Could not find stream information";
            goto cleanup;
        }

        // Find audio stream
        for (unsigned int i = 0; i < formatCtx->nb_streams; i++) {
            if (formatCtx->streams[i]->codecpar->codec_type == AVMEDIA_TYPE_AUDIO) {
                audioStreamIndex = i;
                break;
            }
        }

        if (audioStreamIndex == -1) {
            qDebug() << "No audio stream found";
            goto cleanup;
        }

        // Find decoder
        AVCodecParameters *codecPar = formatCtx->streams[audioStreamIndex]->codecpar;
        codec = avcodec_find_decoder(codecPar->codec_id);
        if (!codec) {
            qDebug() << "Unsupported codec";
            goto cleanup;
        }

        // Allocate codec context
        codecCtx = avcodec_alloc_context3(codec);
        if (!codecCtx) {
            qDebug() << "Could not allocate codec context";
            goto cleanup;
        }

        if ((ret = avcodec_parameters_to_context(codecCtx, codecPar)) < 0) {
            qDebug() << "Could not copy codec parameters";
            goto cleanup;
        }

        if ((ret = avcodec_open2(codecCtx, codec, nullptr)) < 0) {
            qDebug() << "Could not open codec";
            goto cleanup;
        }

        // Initialize resampler to convert to mono, float, 44100 Hz
        swrCtx = swr_alloc();
        if (!swrCtx) {
            qDebug() << "Could not allocate resampler context";
            goto cleanup;
        }

        av_opt_set_chlayout(swrCtx, "in_chlayout", codecCtx->ch_layout.u.mask, 0);
        av_opt_set_int(swrCtx, "in_sample_rate", codecCtx->sample_rate, 0);
        av_opt_set_sample_fmt(swrCtx, "in_sample_fmt", codecCtx->sample_fmt, 0);

        av_opt_set_chlayout(swrCtx, "out_chlayout", AV_CH_LAYOUT_MONO, 0);
        av_opt_set_int(swrCtx, "out_sample_rate", 44100, 0);
        av_opt_set_sample_fmt(swrCtx, "out_sample_fmt", AV_SAMPLE_FMT_FLT, 0);

        if ((ret = swr_init(swrCtx)) < 0) {
            qDebug() << "Could not initialize resampler";
            goto cleanup;
        }

        // Calculate total duration in seconds
        double duration = static_cast<double>(formatCtx->duration) / AV_TIME_BASE;
        int sampleRate = 44100;
        int64_t totalSamples = static_cast<int64_t>(duration * sampleRate);

        // For waveform, we'll collect all resampled samples
        std::vector<float> audioSamples;
        audioSamples.reserve(totalSamples);

        // Decode and resample audio
        while (av_read_frame(formatCtx, packet) >= 0) {
            if (packet->stream_index == audioStreamIndex) {
                if ((ret = avcodec_send_packet(codecCtx, packet)) < 0) {
                    qDebug() << "Error sending packet";
                    break;
                }

                while (ret >= 0) {
                    ret = avcodec_receive_frame(codecCtx, frame);
                    if (ret == AVERROR(EAGAIN) || ret == AVERROR_EOF) {
                        break;
                    } else if (ret < 0) {
                        qDebug() << "Error receiving frame";
                        goto cleanup;
                    }

                    // Resample frame
                    int outSamples = swr_get_out_samples(swrCtx, frame->nb_samples);
                    std::vector<float> resampledBuffer(outSamples);

                    ret = swr_convert(swrCtx, reinterpret_cast<uint8_t**>(&resampledBuffer[0]), outSamples,
                                      const_cast<const uint8_t**>(frame->data), frame->nb_samples);

                    if (ret < 0) {
                        qDebug() << "Error resampling";
                        goto cleanup;
                    }

                    audioSamples.insert(audioSamples.end(), resampledBuffer.begin(), resampledBuffer.begin() + ret);
                }
            }
            av_packet_unref(packet);
        }

        // Flush resampler
        while ((ret = swr_convert(swrCtx, nullptr, 0, nullptr, 0)) > 0) {}

        if (audioSamples.empty()) {
            qDebug() << "No audio samples extracted";
            goto cleanup;
        }

        // Now generate waveform images
        // Calculate how many images we need: one per second if imagesPerSecond > 0, else single image
        int numImages = 1;
        if (imagesPerSecond > 0) {
            numImages = static_cast<int>(duration * imagesPerSecond);
            if (numImages < 1) numImages = 1;
        }

        int samplesPerImage = audioSamples.size() / numImages;
        int binSize = samplesPerImage / imageWidth;
        if (binSize < 1) binSize = 1;

        for (int imgIndex = 0; imgIndex < numImages; ++imgIndex) {
            QImage image(imageWidth, imageHeight, QImage::Format_ARGB32);
            image.fill(Qt::transparent);

            QPainter painter(&image);
            painter.setPen(QColor(0, 255, 0)); // Green waveform
            painter.setRenderHint(QPainter::Antialiasing);

            int startSample = imgIndex * samplesPerImage;
            int endSample = std::min(static_cast<int>(audioSamples.size()), startSample + samplesPerImage);

            float maxAmp = 0.0f;
            for (int i = startSample; i < endSample; ++i) {
                maxAmp = std::max(maxAmp, std::abs(audioSamples[i]));
            }
            if (maxAmp == 0.0f) maxAmp = 1.0f; // Avoid division by zero

            int prevX = 0;
            float prevY = imageHeight / 2.0f;

            for (int x = 0; x < imageWidth; ++x) {
                int binStart = startSample + x * binSize;
                int binEnd = std::min(endSample, binStart + binSize);

                float minVal = 1.0f;
                float maxVal = -1.0f;

                for (int s = binStart; s < binEnd; ++s) {
                    float sample = audioSamples[s] / maxAmp;
                    minVal = std::min(minVal, sample);
                    maxVal = std::max(maxVal, sample);
                }

                float yTop = (imageHeight / 2.0f) * (1.0f - maxVal);
                float yBottom = (imageHeight / 2.0f) * (1.0f - minVal);

                painter.drawLine(prevX, prevY, x, (yTop + yBottom) / 2.0f);

                prevX = x;
                prevY = (yTop + yBottom) / 2.0f;
            }

            waveformImages.append(image);
        }

cleanup:
        if (frame) av_frame_free(&frame);
        if (packet) av_packet_free(&packet);
        if (codecCtx) avcodec_free_context(&codecCtx);
        if (formatCtx) avformat_close_input(&formatCtx);
        if (swrCtx) swr_free(&swrCtx);

        return waveformImages;
    }
};
```

To integrate this class with QML, you need to register it in your main.cpp like this:

```cpp
#include <QGuiApplication>
#include <QQmlApplicationEngine>
#include "audiowaveformextractor.h"  // Include the header for the class

int main(int argc, char *argv[]) {
    QGuiApplication app(argc, argv);

    qmlRegisterType<AudioWaveformExtractor>("com.example", 1, 0, "AudioWaveformExtractor");

    QQmlApplicationEngine engine;
    engine.load(QUrl(QStringLiteral("qrc:/main.qml")));

    return app.exec();
}
```

In QML, you can use it like:

```qml
import QtQuick
import com.example 1.0

Item {
    AudioWaveformExtractor {
        id: extractor
    }

    function loadWaveform(filePath) {
        var images = extractor.generateWaveform(filePath, 800, 100, 1);  // Example parameters
        // Now display the images, e.g., in a ListView or Image components
        // For simplicity, assume single image:
        if (images.length > 0) {
            myImage.source = "image://provider/" + images[0];  // Or save to file and load
        }
    }
}
```

Note: For displaying QImage in QML, you might need a custom QQuickImageProvider or save the images to temporary files. This class handles extraction and rendering as QImage list. Adjust parameters as needed. Ensure FFmpeg libraries are linked in your project (e.g., via pkg-config or CMake).
