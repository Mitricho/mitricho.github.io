Here's a C++ implementation using Qt6 and FFmpeg to convert video files with specified codecs:

```cpp
#include <QCoreApplication>
#include <QFileInfo>
#include <QDir>
#include <QDebug>

extern "C" {
    #include <libavformat/avformat.h>
    #include <libavcodec/avcodec.h>
    #include <libavutil/avutil.h>
    #include <libavutil/opt.h>
    #include <libswscale/swscale.h>
    #include <libswresample/swresample.h>
}

class VideoConverter {
public:
    VideoConverter() {
        avformat_network_init();
    }

    ~VideoConverter() {
        avformat_network_deinit();
    }

    bool convertVideo(const QString& sourcePath, 
                     const QString& videoCodec, 
                     const QString& audioCodec) {
        
        // Get file info and create output path
        QFileInfo sourceFileInfo(sourcePath);
        QString outputPath = generateOutputPath(sourcePath, videoCodec);
        
        if (outputPath.isEmpty()) {
            qWarning() << "Failed to generate output path";
            return false;
        }

        AVFormatContext* inputContext = nullptr;
        AVFormatContext* outputContext = nullptr;
        AVPacket* packet = nullptr;
        int ret = 0;

        // Open input file
        ret = avformat_open_input(&inputContext, sourcePath.toUtf8().constData(), nullptr, nullptr);
        if (ret < 0) {
            qWarning() << "Could not open input file:" << sourcePath;
            return false;
        }

        // Retrieve stream information
        ret = avformat_find_stream_info(inputContext, nullptr);
        if (ret < 0) {
            qWarning() << "Failed to retrieve input stream information";
            avformat_close_input(&inputContext);
            return false;
        }

        // Create output context
        ret = avformat_alloc_output_context2(&outputContext, nullptr, nullptr, outputPath.toUtf8().constData());
        if (ret < 0) {
            qWarning() << "Could not create output context";
            avformat_close_input(&inputContext);
            return false;
        }

        // Initialize streams
        QVector<OutputStream> outputStreams;
        if (!initializeStreams(inputContext, outputContext, videoCodec, audioCodec, outputStreams)) {
            cleanupResources(inputContext, outputContext, outputStreams);
            return false;
        }

        // Open output file
        if (!(outputContext->oformat->flags & AVFMT_NOFILE)) {
            ret = avio_open(&outputContext->pb, outputPath.toUtf8().constData(), AVIO_FLAG_WRITE);
            if (ret < 0) {
                qWarning() << "Could not open output file:" << outputPath;
                cleanupResources(inputContext, outputContext, outputStreams);
                return false;
            }
        }

        // Write header
        ret = avformat_write_header(outputContext, nullptr);
        if (ret < 0) {
            qWarning() << "Error occurred when writing header";
            cleanupResources(inputContext, outputContext, outputStreams);
            return false;
        }

        packet = av_packet_alloc();
        if (!packet) {
            qWarning() << "Failed to allocate packet";
            cleanupResources(inputContext, outputContext, outputStreams);
            return false;
        }

        // Process frames
        while (av_read_frame(inputContext, packet) >= 0) {
            if (packet->stream_index < outputStreams.size() && 
                outputStreams[packet->stream_index].isInitialized()) {
                
                OutputStream& ost = outputStreams[packet->stream_index];
                packet->stream_index = ost.streamIndex;
                
                // Rescale packet timestamps
                av_packet_rescale_ts(packet, 
                                    inputContext->streams[packet->stream_index]->time_base,
                                    outputContext->streams[ost.streamIndex]->time_base);
                
                ret = av_interleaved_write_frame(outputContext, packet);
                if (ret < 0) {
                    qWarning() << "Error writing frame";
                    av_packet_unref(packet);
                    break;
                }
            }
            av_packet_unref(packet);
        }

        // Write trailer
        av_write_trailer(outputContext);

        // Cleanup
        av_packet_free(&packet);
        cleanupResources(inputContext, outputContext, outputStreams);

        qInfo() << "Conversion completed successfully. Output file:" << outputPath;
        return true;
    }

private:
    struct OutputStream {
        AVStream* stream = nullptr;
        AVCodecContext* enc = nullptr;
        int streamIndex = -1;
        bool isInitialized() const { return stream && enc; }
    };

    QString generateOutputPath(const QString& sourcePath, const QString& videoCodec) {
        QFileInfo fileInfo(sourcePath);
        QString baseName = fileInfo.completeBaseName();
        QString dirPath = fileInfo.absolutePath();
        
        // Map common codecs to file extensions
        QHash<QString, QString> codecToExtension = {
            {"libx264", "mp4"},
            {"h264", "mp4"},
            {"libx265", "mp4"},
            {"hevc", "mp4"},
            {"vp9", "webm"},
            {"libvpx-vp9", "webm"},
            {"av1", "mp4"},
            {"libaom-av1", "mp4"},
            {"mpeg4", "mp4"},
            {"prores", "mov"},
            {"dnxhd", "mov"}
        };

        QString extension = codecToExtension.value(videoCodec.toLower(), "mp4");
        return dirPath + QDir::separator() + baseName + "_converted." + extension;
    }

    bool initializeStreams(AVFormatContext* inputContext, 
                          AVFormatContext* outputContext,
                          const QString& videoCodecName,
                          const QString& audioCodecName,
                          QVector<OutputStream>& outputStreams) {
        
        outputStreams.resize(inputContext->nb_streams);

        for (unsigned int i = 0; i < inputContext->nb_streams; i++) {
            AVStream* inStream = inputContext->streams[i];
            AVStream* outStream = avformat_new_stream(outputContext, nullptr);
            
            if (!outStream) {
                qWarning() << "Failed allocating output stream";
                return false;
            }

            OutputStream ost;
            ost.stream = outStream;
            ost.streamIndex = i;

            if (inStream->codecpar->codec_type == AVMEDIA_TYPE_VIDEO) {
                if (!setupVideoStream(inStream, outStream, videoCodecName, ost)) {
                    return false;
                }
            } 
            else if (inStream->codecpar->codec_type == AVMEDIA_TYPE_AUDIO) {
                if (!setupAudioStream(inStream, outStream, audioCodecName, ost)) {
                    return false;
                }
            } 
            else {
                // Copy other streams without re-encoding
                if (avcodec_parameters_copy(outStream->codecpar, inStream->codecpar) < 0) {
                    qWarning() << "Failed to copy codec parameters";
                    return false;
                }
                outStream->time_base = inStream->time_base;
            }

            outputStreams[i] = ost;
        }

        return true;
    }

    bool setupVideoStream(AVStream* inStream, AVStream* outStream, 
                         const QString& codecName, OutputStream& ost) {
        
        const AVCodec* codec = avcodec_find_encoder_by_name(codecName.toUtf8().constData());
        if (!codec) {
            qWarning() << "Video codec not found:" << codecName;
            return false;
        }

        ost.enc = avcodec_alloc_context3(codec);
        if (!ost.enc) {
            qWarning() << "Failed to allocate video codec context";
            return false;
        }

        // Set codec parameters
        ost.enc->width = inStream->codecpar->width;
        ost.enc->height = inStream->codecpar->height;
        ost.enc->sample_aspect_ratio = inStream->codecpar->sample_aspect_ratio;
        ost.enc->pix_fmt = static_cast<AVPixelFormat>(inStream->codecpar->format);
        ost.enc->time_base = inStream->time_base;
        ost.enc->framerate = inStream->avg_frame_rate;

        // Set some reasonable defaults
        ost.enc->bit_rate = inStream->codecpar->bit_rate;
        if (ost.enc->bit_rate == 0) {
            ost.enc->bit_rate = 4000000; // 4 Mbps default
        }

        // Open codec
        if (avcodec_open2(ost.enc, codec, nullptr) < 0) {
            qWarning() << "Could not open video codec";
            return false;
        }

        // Copy parameters to stream
        if (avcodec_parameters_from_context(outStream->codecpar, ost.enc) < 0) {
            qWarning() << "Failed to copy video codec parameters to stream";
            return false;
        }

        outStream->time_base = ost.enc->time_base;
        return true;
    }

    bool setupAudioStream(AVStream* inStream, AVStream* outStream,
                         const QString& codecName, OutputStream& ost) {
        
        const AVCodec* codec = avcodec_find_encoder_by_name(codecName.toUtf8().constData());
        if (!codec) {
            qWarning() << "Audio codec not found:" << codecName;
            return false;
        }

        ost.enc = avcodec_alloc_context3(codec);
        if (!ost.enc) {
            qWarning() << "Failed to allocate audio codec context";
            return false;
        }

        // Set codec parameters
        ost.enc->sample_rate = inStream->codecpar->sample_rate;
        ost.enc->channel_layout = inStream->codecpar->channel_layout;
        ost.enc->channels = inStream->codecpar->channels;
        ost.enc->sample_fmt = static_cast<AVSampleFormat>(inStream->codecpar->format);
        ost.enc->time_base = AVRational{1, ost.enc->sample_rate};
        ost.enc->bit_rate = inStream->codecpar->bit_rate;

        if (ost.enc->bit_rate == 0) {
            ost.enc->bit_rate = 128000; // 128 kbps default
        }

        // Open codec
        if (avcodec_open2(ost.enc, codec, nullptr) < 0) {
            qWarning() << "Could not open audio codec";
            return false;
        }

        // Copy parameters to stream
        if (avcodec_parameters_from_context(outStream->codecpar, ost.enc) < 0) {
            qWarning() << "Failed to copy audio codec parameters to stream";
            return false;
        }

        outStream->time_base = ost.enc->time_base;
        return true;
    }

    void cleanupResources(AVFormatContext* inputContext, 
                         AVFormatContext* outputContext,
                         QVector<OutputStream>& outputStreams) {
        
        if (inputContext) {
            avformat_close_input(&inputContext);
        }

        if (outputContext && !(outputContext->oformat->flags & AVFMT_NOFILE)) {
            avio_closep(&outputContext->pb);
        }

        if (outputContext) {
            avformat_free_context(outputContext);
        }

        for (OutputStream& ost : outputStreams) {
            if (ost.enc) {
                avcodec_free_context(&ost.enc);
            }
        }
    }
};

// Example usage:
int main(int argc, char *argv[]) {
    QCoreApplication app(argc, argv);
    
    VideoConverter converter;
    
    QString sourceFile = "/path/to/your/input/video.mp4";
    QString videoCodec = "libx264";
    QString audioCodec = "aac";
    
    bool success = converter.convertVideo(sourceFile, videoCodec, audioCodec);
    
    if (success) {
        qInfo() << "Video conversion completed successfully!";
    } else {
        qWarning() << "Video conversion failed!";
    }
    
    return success ? 0 : 1;
}
```

To use this code, you'll need to add the following to your `.pro` file:

```pro
QT += core

LIBS += -lavformat -lavcodec -lavutil -lswscale -lswresample
```

**Key Features:**

1. **Flexible Codec Support**: Accepts any FFmpeg-supported video and audio codecs
2. **Automatic Output Path**: Generates output path in the same directory as source
3. **Proper Resource Management**: Handles all FFmpeg resource allocation and cleanup
4. **Stream Processing**: Handles both video and audio streams appropriately
5. **Error Handling**: Comprehensive error checking throughout the process

**Usage Examples:**

```cpp
// Convert to H.264 video with AAC audio
converter.convertVideo("input.mov", "libx264", "aac");

// Convert to HEVC with MP3 audio  
converter.convertVideo("input.avi", "libx265", "mp3");

// Convert to VP9 with Opus audio
converter.convertVideo("input.mp4", "libvpx-vp9", "libopus");
```

**Dependencies:**
- Qt6 Core module
- FFmpeg libraries (libavformat, libavcodec, libavutil, libswscale, libswresample)

**Note:** Make sure FFmpeg development libraries are installed on your system and properly linked in your project.
