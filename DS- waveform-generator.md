Вот реализация класса для извлечения звука и генерации waveform с использованием FFmpeg 6.1 API и Qt6:

## WaveformGenerator.h

```cpp
#ifndef WAVEFORMGENERATOR_H
#define WAVEFORMGENERATOR_H

#include <QObject>
#include <QImage>
#include <QVector>
#include <QSize>
#include <memory>

extern "C" {
#include <libavformat/avformat.h>
#include <libavcodec/avcodec.h>
#include <libavutil/avutil.h>
#include <libswresample/swresample.h>
}

class WaveformGenerator : public QObject
{
    Q_OBJECT

public:
    explicit WaveformGenerator(QObject *parent = nullptr);
    ~WaveformGenerator();

    enum WaveformStyle {
        StyleLines,
        StyleBars,
        StyleFilled
    };
    Q_ENUM(WaveformStyle)

    Q_PROPERTY(QSize imageSize READ imageSize WRITE setImageSize NOTIFY imageSizeChanged)
    Q_PROPERTY(QColor waveformColor READ waveformColor WRITE setWaveformColor NOTIFY waveformColorChanged)
    Q_PROPERTY(QColor backgroundColor READ backgroundColor WRITE setBackgroundColor NOTIFY backgroundColorChanged)
    Q_PROPERTY(WaveformStyle style READ style WRITE setStyle NOTIFY styleChanged)

    QSize imageSize() const;
    void setImageSize(const QSize &size);

    QColor waveformColor() const;
    void setWaveformColor(const QColor &color);

    QColor backgroundColor() const;
    void setBackgroundColor(const QColor &color);

    WaveformStyle style() const;
    void setStyle(WaveformStyle style);

public slots:
    void generateWaveform(const QString &filePath);
    void cancelGeneration();

signals:
    void waveformGenerated(const QVector<QImage> &waveformImages);
    void generationProgress(double progress);
    void errorOccurred(const QString &errorMessage);
    void imageSizeChanged();
    void waveformColorChanged();
    void backgroundColorChanged();
    void styleChanged();

private:
    struct AVFormatContextDeleter {
        void operator()(AVFormatContext* ctx) const {
            if (ctx) avformat_close_input(&ctx);
        }
    };

    struct AVCodecContextDeleter {
        void operator()(AVCodecContext* ctx) const {
            if (ctx) avcodec_free_context(&ctx);
        }
    };

    struct SwrContextDeleter {
        void operator()(SwrContext* ctx) const {
            if (ctx) swr_free(&ctx);
        }
    };

    using AVFormatContextPtr = std::unique_ptr<AVFormatContext, AVFormatContextDeleter>;
    using AVCodecContextPtr = std::unique_ptr<AVCodecContext, AVCodecContextDeleter>;
    using SwrContextPtr = std::unique_ptr<SwrContext, SwrContextDeleter>;

    bool initializeFFmpeg(const QString &filePath);
    int findAudioStream();
    bool setupAudioCodec(int audioStreamIndex);
    bool setupResampler();
    bool processAudioFrames();
    QVector<QImage> createWaveformImages(const QVector<float> &samples);
    QImage createWaveformImage(const QVector<float> &channelSamples, int channelIndex);
    void drawWaveformLines(QPainter &painter, const QVector<float> &samples);
    void drawWaveformBars(QPainter &painter, const QVector<float> &samples);
    void drawWaveformFilled(QPainter &painter, const QVector<float> &samples);

    AVFormatContextPtr formatContext_;
    AVCodecContextPtr codecContext_;
    SwrContextPtr swrContext_;
    
    QSize imageSize_;
    QColor waveformColor_;
    QColor backgroundColor_;
    WaveformStyle style_;
    
    bool cancelRequested_;
    int sampleRate_;
    int channels_;
    AVSampleFormat outputSampleFormat_;
};

#endif // WAVEFORMGENERATOR_H
```

## WaveformGenerator.cpp

```cpp
#include "WaveformGenerator.h"
#include <QPainter>
#include <QDebug>
#include <cmath>

extern "C" {
#include <libavutil/opt.h>
#include <libavutil/channel_layout.h>
}

WaveformGenerator::WaveformGenerator(QObject *parent)
    : QObject(parent)
    , imageSize_(800, 200)
    , waveformColor_(Qt::blue)
    , backgroundColor_(Qt::white)
    , style_(StyleLines)
    , cancelRequested_(false)
    , sampleRate_(44100)
    , channels_(2)
    , outputSampleFormat_(AV_SAMPLE_FMT_FLT)
{
}

WaveformGenerator::~WaveformGenerator()
{
    cancelGeneration();
}

QSize WaveformGenerator::imageSize() const { return imageSize_; }
void WaveformGenerator::setImageSize(const QSize &size)
{
    if (imageSize_ != size) {
        imageSize_ = size;
        emit imageSizeChanged();
    }
}

QColor WaveformGenerator::waveformColor() const { return waveformColor_; }
void WaveformGenerator::setWaveformColor(const QColor &color)
{
    if (waveformColor_ != color) {
        waveformColor_ = color;
        emit waveformColorChanged();
    }
}

QColor WaveformGenerator::backgroundColor() const { return backgroundColor_; }
void WaveformGenerator::setBackgroundColor(const QColor &color)
{
    if (backgroundColor_ != color) {
        backgroundColor_ = color;
        emit backgroundColorChanged();
    }
}

WaveformGenerator::WaveformStyle WaveformGenerator::style() const { return style_; }
void WaveformGenerator::setStyle(WaveformStyle style)
{
    if (style_ != style) {
        style_ = style;
        emit styleChanged();
    }
}

void WaveformGenerator::generateWaveform(const QString &filePath)
{
    cancelRequested_ = false;
    
    if (!initializeFFmpeg(filePath)) {
        emit errorOccurred("Failed to initialize FFmpeg");
        return;
    }

    int audioStreamIndex = findAudioStream();
    if (audioStreamIndex == -1) {
        emit errorOccurred("No audio stream found");
        return;
    }

    if (!setupAudioCodec(audioStreamIndex)) {
        emit errorOccurred("Failed to setup audio codec");
        return;
    }

    if (!setupResampler()) {
        emit errorOccurred("Failed to setup resampler");
        return;
    }

    QVector<float> allSamples;
    if (!processAudioFrames(allSamples)) {
        emit errorOccurred("Failed to process audio frames");
        return;
    }

    if (cancelRequested_) {
        return;
    }

    QVector<QImage> waveformImages = createWaveformImages(allSamples);
    emit waveformGenerated(waveformImages);
}

void WaveformGenerator::cancelGeneration()
{
    cancelRequested_ = true;
}

bool WaveformGenerator::initializeFFmpeg(const QString &filePath)
{
    AVFormatContext *formatContext = nullptr;
    if (avformat_open_input(&formatContext, filePath.toUtf8().constData(), nullptr, nullptr) != 0) {
        return false;
    }
    formatContext_.reset(formatContext);

    if (avformat_find_stream_info(formatContext_.get(), nullptr) < 0) {
        return false;
    }

    return true;
}

int WaveformGenerator::findAudioStream()
{
    for (unsigned int i = 0; i < formatContext_->nb_streams; i++) {
        if (formatContext_->streams[i]->codecpar->codec_type == AVMEDIA_TYPE_AUDIO) {
            return i;
        }
    }
    return -1;
}

bool WaveformGenerator::setupAudioCodec(int audioStreamIndex)
{
    AVStream *audioStream = formatContext_->streams[audioStreamIndex];
    const AVCodec *codec = avcodec_find_decoder(audioStream->codecpar->codec_id);
    if (!codec) {
        return false;
    }

    AVCodecContext *codecContext = avcodec_alloc_context3(codec);
    if (!codecContext) {
        return false;
    }

    if (avcodec_parameters_to_context(codecContext, audioStream->codecpar) < 0) {
        return false;
    }

    if (avcodec_open2(codecContext, codec, nullptr) < 0) {
        return false;
    }

    codecContext_.reset(codecContext);
    return true;
}

bool WaveformGenerator::setupResampler()
{
    SwrContext *swr = swr_alloc();
    if (!swr) {
        return false;
    }

    av_opt_set_int(swr, "in_channel_layout", codecContext_->channel_layout, 0);
    av_opt_set_int(swr, "out_channel_layout", AV_CH_LAYOUT_STEREO, 0);
    av_opt_set_int(swr, "in_sample_rate", codecContext_->sample_rate, 0);
    av_opt_set_int(swr, "out_sample_rate", sampleRate_, 0);
    av_opt_set_sample_fmt(swr, "in_sample_fmt", codecContext_->sample_fmt, 0);
    av_opt_set_sample_fmt(swr, "out_sample_fmt", outputSampleFormat_, 0);

    if (swr_init(swr) < 0) {
        swr_free(&swr);
        return false;
    }

    swrContext_.reset(swr);
    channels_ = 2; // Stereo output
    return true;
}

bool WaveformGenerator::processAudioFrames(QVector<float> &allSamples)
{
    AVPacket *packet = av_packet_alloc();
    AVFrame *frame = av_frame_alloc();
    AVFrame *resampledFrame = av_frame_alloc();

    if (!packet || !frame || !resampledFrame) {
        av_packet_free(&packet);
        av_frame_free(&frame);
        av_frame_free(&resampledFrame);
        return false;
    }

    const int64_t duration = formatContext_->duration;
    int64_t processedBytes = 0;

    while (av_read_frame(formatContext_.get(), packet) >= 0 && !cancelRequested_) {
        if (packet->stream_index != codecContext_->stream_index) {
            av_packet_unref(packet);
            continue;
        }

        if (avcodec_send_packet(codecContext_.get(), packet) < 0) {
            av_packet_unref(packet);
            continue;
        }

        while (avcodec_receive_frame(codecContext_.get(), frame) >= 0 && !cancelRequested_) {
            // Resample frame
            if (swr_convert_frame(swrContext_.get(), resampledFrame, frame) < 0) {
                continue;
            }

            // Process samples
            const float *samples = reinterpret_cast<const float*>(resampledFrame->data[0]);
            const int sampleCount = resampledFrame->nb_samples * channels_;
            
            for (int i = 0; i < sampleCount; i++) {
                allSamples.append(samples[i]);
            }

            // Emit progress
            processedBytes += packet->size;
            if (duration > 0) {
                double progress = static_cast<double>(processedBytes) / duration;
                emit generationProgress(qMin(progress, 1.0));
            }

            av_frame_unref(resampledFrame);
        }

        av_packet_unref(packet);
    }

    av_packet_free(&packet);
    av_frame_free(&frame);
    av_frame_free(&resampledFrame);

    return !cancelRequested_;
}

QVector<QImage> WaveformGenerator::createWaveformImages(const QVector<float> &samples)
{
    QVector<QImage> images;
    
    if (samples.isEmpty()) {
        return images;
    }

    const int totalSamples = samples.size();
    const int samplesPerChannel = totalSamples / channels_;
    
    for (int channel = 0; channel < channels_; channel++) {
        QVector<float> channelSamples;
        channelSamples.reserve(samplesPerChannel);
        
        for (int i = channel; i < totalSamples; i += channels_) {
            channelSamples.append(samples[i]);
        }
        
        images.append(createWaveformImage(channelSamples, channel));
    }

    return images;
}

QImage WaveformGenerator::createWaveformImage(const QVector<float> &samples, int channelIndex)
{
    QImage image(imageSize_, QImage::Format_ARGB32);
    image.fill(backgroundColor_);
    
    QPainter painter(&image);
    painter.setRenderHint(QPainter::Antialiasing);
    painter.setPen(waveformColor_);
    painter.setBrush(waveformColor_);

    switch (style_) {
    case StyleLines:
        drawWaveformLines(painter, samples);
        break;
    case StyleBars:
        drawWaveformBars(painter, samples);
        break;
    case StyleFilled:
        drawWaveformFilled(painter, samples);
        break;
    }

    painter.end();
    return image;
}

void WaveformGenerator::drawWaveformLines(QPainter &painter, const QVector<float> &samples)
{
    if (samples.isEmpty()) return;

    const int width = imageSize_.width();
    const int height = imageSize_.height();
    const int centerY = height / 2;
    const float samplesPerPixel = static_cast<float>(samples.size()) / width;

    QPainterPath path;
    bool firstPoint = true;

    for (int x = 0; x < width; x++) {
        const int startSample = static_cast<int>(x * samplesPerPixel);
        const int endSample = static_cast<int>((x + 1) * samplesPerPixel);
        
        if (endSample >= samples.size()) break;

        // Find max amplitude in this pixel column
        float maxAmplitude = 0.0f;
        for (int i = startSample; i < endSample; i++) {
            maxAmplitude = qMax(maxAmplitude, std::abs(samples[i]));
        }

        const int y = centerY - static_cast<int>(maxAmplitude * centerY);

        if (firstPoint) {
            path.moveTo(x, y);
            firstPoint = false;
        } else {
            path.lineTo(x, y);
        }
    }

    painter.drawPath(path);
}

void WaveformGenerator::drawWaveformBars(QPainter &painter, const QVector<float> &samples)
{
    if (samples.isEmpty()) return;

    const int width = imageSize_.width();
    const int height = imageSize_.height();
    const int centerY = height / 2;
    const float samplesPerPixel = static_cast<float>(samples.size()) / width;

    for (int x = 0; x < width; x++) {
        const int startSample = static_cast<int>(x * samplesPerPixel);
        const int endSample = static_cast<int>((x + 1) * samplesPerPixel);
        
        if (endSample >= samples.size()) break;

        // Find max amplitude in this pixel column
        float maxAmplitude = 0.0f;
        for (int i = startSample; i < endSample; i++) {
            maxAmplitude = qMax(maxAmplitude, std::abs(samples[i]));
        }

        const int barHeight = static_cast<int>(maxAmplitude * height);
        const int y = centerY - barHeight / 2;

        painter.drawRect(x, y, 1, barHeight);
    }
}

void WaveformGenerator::drawWaveformFilled(QPainter &painter, const QVector<float> &samples)
{
    if (samples.isEmpty()) return;

    const int width = imageSize_.width();
    const int height = imageSize_.height();
    const int centerY = height / 2;
    const float samplesPerPixel = static_cast<float>(samples.size()) / width;

    QPainterPath path;
    path.moveTo(0, centerY);

    for (int x = 0; x < width; x++) {
        const int startSample = static_cast<int>(x * samplesPerPixel);
        const int endSample = static_cast<int>((x + 1) * samplesPerPixel);
        
        if (endSample >= samples.size()) break;

        float maxAmplitude = 0.0f;
        for (int i = startSample; i < endSample; i++) {
            maxAmplitude = qMax(maxAmplitude, samples[i]);
        }

        const int y = centerY - static_cast<int>(maxAmplitude * centerY);
        path.lineTo(x, y);
    }

    path.lineTo(width, centerY);
    path.closeSubpath();

    painter.drawPath(path);
}
```

## Пример использования в QML:

```qml
import QtQuick 2.15
import QtQuick.Controls 2.15

Item {
    width: 800
    height: 400

    WaveformGenerator {
        id: waveformGenerator
        imageSize: Qt.size(800, 200)
        waveformColor: "blue"
        backgroundColor: "white"
        style: WaveformGenerator.StyleLines

        onWaveformGenerated: {
            waveformRepeater.model = images
        }
        onGenerationProgress: {
            progressBar.value = progress
        }
        onErrorOccurred: {
            console.error("Error:", errorMessage)
        }
    }

    Column {
        anchors.fill: parent

        Repeater {
            id: waveformRepeater
            delegate: Image {
                source: modelData
                width: parent.width
                height: 200
                fillMode: Image.PreserveAspectFit
            }
        }
    }

    ProgressBar {
        id: progressBar
        anchors.bottom: parent.bottom
        width: parent.width
        from: 0
        to: 1
    }

    Button {
        anchors.bottom: parent.bottom
        anchors.right: parent.right
        text: "Generate"
        onClicked: {
            waveformGenerator.generateWaveform("file:///path/to/audio.mp3")
        }
    }
}
```

## CMakeLists.txt (фрагмент):

```cmake
find_package(Qt6 REQUIRED COMPONENTS Core Gui Quick)
find_package(FFmpeg REQUIRED COMPONENTS avformat avcodec avutil swresample)

add_library(WaveformGenerator STATIC
    WaveformGenerator.cpp
    WaveformGenerator.h
)

target_link_libraries(WaveformGenerator
    Qt6::Core
    Qt6::Gui
    Qt6::Quick
    ${FFMPEG_LIBRARIES}
)

target_include_directories(WaveformGenerator
    PRIVATE ${FFMPEG_INCLUDE_DIRS}
)
```

Этот класс:

1. Использует FFmpeg 6.1 API для декодирования аудио
2. Ресемплирует аудио в формат с плавающей точкой
3. Генерирует waveform в виде QImage для каждого канала
4. Поддерживает различные стили отрисовки
5. Предоставляет сигналы прогресса и ошибок
6. Может быть использован в QML через свойства и сигналы
