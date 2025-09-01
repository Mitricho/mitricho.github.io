
## Required Dependencies

First, make sure you have these installed:
- Qt6 development packages
- FFmpeg 6.1 development libraries

## Implementation

```cpp
#include <QImage>
#include <QDebug>
#include <QFile>

extern "C" {
#include <libavutil/imgutils.h>
#include <libavutil/pixdesc.h>
#include <libswscale/swscale.h>
}

class FrameToImageConverter {
public:
    static bool convertAndSaveAVFrameToImage(AVFrame* frame, const QString& filePath, const QString& format = "png") {
        if (!frame || !frame->data[0]) {
            qWarning() << "Invalid AVFrame provided";
            return false;
        }

        // Convert AVFrame to QImage
        QImage image = convertAVFrameToQImage(frame);
        if (image.isNull()) {
            qWarning() << "Failed to convert AVFrame to QImage";
            return false;
        }

        // Save the image to disk
        return saveQImageToFile(image, filePath, format);
    }

private:
    static QImage convertAVFrameToQImage(AVFrame* frame) {
        // Check if the frame is valid
        if (!frame || !frame->data[0]) {
            return QImage();
        }

        // Determine the pixel format and create appropriate QImage format
        AVPixelFormat pixelFormat = static_cast<AVPixelFormat>(frame->format);
        QImage::Format qtFormat = getQImageFormat(pixelFormat);
        
        if (qtFormat == QImage::Format_Invalid) {
            // Convert to a supported format using swscale
            return convertUsingSwsContext(frame);
        }

        // Create QImage directly from AVFrame data
        return createQImageFromFrame(frame, qtFormat);
    }

    static QImage::Format getQImageFormat(AVPixelFormat avFormat) {
        switch (avFormat) {
            case AV_PIX_FMT_RGB24:
                return QImage::Format_RGB888;
            case AV_PIX_FMT_RGBA:
                return QImage::Format_RGBA8888;
            case AV_PIX_FMT_ARGB:
                return QImage::Format_ARGB32;
            case AV_PIX_FMT_BGRA:
                return QImage::Format_ARGB32; // Qt will handle byte order
            case AV_PIX_FMT_GRAY8:
                return QImage::Format_Grayscale8;
            case AV_PIX_FMT_RGB32:
                return QImage::Format_RGB32;
            default:
                return QImage::Format_Invalid;
        }
    }

    static QImage convertUsingSwsContext(AVFrame* frame) {
        SwsContext* swsCtx = nullptr;
        AVFrame* rgbFrame = nullptr;
        QImage result;

        try {
            // Create conversion context
            swsCtx = sws_getContext(
                frame->width, frame->height,
                static_cast<AVPixelFormat>(frame->format),
                frame->width, frame->height,
                AV_PIX_FMT_RGB24, // Convert to RGB24 which Qt supports
                SWS_BILINEAR,
                nullptr, nullptr, nullptr
            );

            if (!swsCtx) {
                throw std::runtime_error("Failed to create SwsContext");
            }

            // Allocate RGB frame
            rgbFrame = av_frame_alloc();
            if (!rgbFrame) {
                throw std::runtime_error("Failed to allocate RGB frame");
            }

            rgbFrame->format = AV_PIX_FMT_RGB24;
            rgbFrame->width = frame->width;
            rgbFrame->height = frame->height;

            int ret = av_frame_get_buffer(rgbFrame, 0);
            if (ret < 0) {
                throw std::runtime_error("Failed to allocate RGB frame buffer");
            }

            // Convert the frame
            ret = sws_scale(swsCtx, 
                           frame->data, frame->linesize, 
                           0, frame->height,
                           rgbFrame->data, rgbFrame->linesize);
            
            if (ret <= 0) {
                throw std::runtime_error("Failed to convert frame using swscale");
            }

            // Create QImage from converted frame
            result = QImage(rgbFrame->data[0], 
                           rgbFrame->width, 
                           rgbFrame->height, 
                           rgbFrame->linesize[0],
                           QImage::Format_RGB888);

            // Make a copy since the QImage will reference the frame data
            result = result.copy();

        } catch (const std::exception& e) {
            qWarning() << "Conversion error:" << e.what();
            result = QImage();
        }

        // Cleanup
        if (rgbFrame) {
            av_frame_free(&rgbFrame);
        }
        if (swsCtx) {
            sws_freeContext(swsCtx);
        }

        return result;
    }

    static QImage createQImageFromFrame(AVFrame* frame, QImage::Format format) {
        // For formats that don't need conversion, create QImage directly
        // Note: This assumes the frame data is in a compatible format
        QImage image(frame->data[0], 
                    frame->width, 
                    frame->height, 
                    frame->linesize[0],
                    format);

        // Make a copy since the QImage will reference the frame data
        return image.copy();
    }

    static bool saveQImageToFile(const QImage& image, const QString& filePath, const QString& format) {
        if (image.isNull()) {
            qWarning() << "Cannot save null image";
            return false;
        }

        // Ensure the directory exists
        QFileInfo fileInfo(filePath);
        QDir().mkpath(fileInfo.absolutePath());

        // Save the image
        bool success = image.save(filePath, format.toUpper().toUtf8().constData());
        
        if (!success) {
            qWarning() << "Failed to save image to:" << filePath;
        } else {
            qDebug() << "Image successfully saved to:" << filePath;
        }

        return success;
    }
};
```

## Usage Example

```cpp
#include <QCoreApplication>
#include "FrameToImageConverter.h"

extern "C" {
#include <libavformat/avformat.h>
#include <libavcodec/avcodec.h>
}

int main(int argc, char *argv[]) {
    QCoreApplication app(argc, argv);
    
    // Example usage (you'll need to get an actual AVFrame from your video processing)
    AVFrame* frame = nullptr; // This should come from your video decoding
    
    // Convert and save the frame
    bool success = FrameToImageConverter::convertAndSaveAVFrameToImage(
        frame, 
        "/path/to/save/image.png", 
        "png"
    );
    
    if (success) {
        qDebug() << "Frame saved successfully!";
    } else {
        qDebug() << "Failed to save frame!";
    }
    
    return 0;
}
```

## CMake Configuration

Here's a sample CMakeLists.txt:

```cmake
cmake_minimum_required(VERSION 3.16)
project(FrameToImageConverter)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(Qt6 REQUIRED COMPONENTS Core)
find_package(PkgConfig REQUIRED)

pkg_check_modules(AVCODEC REQUIRED libavcodec)
pkg_check_modules(AVUTIL REQUIRED libavutil)
pkg_check_modules(SWSCALE REQUIRED libswscale)

add_library(FrameToImageConverter STATIC FrameToImageConverter.cpp)
target_link_libraries(FrameToImageConverter
    Qt6::Core
    ${AVCODEC_LIBRARIES}
    ${AVUTIL_LIBRARIES}
    ${SWSCALE_LIBRARIES}
)

target_include_directories(FrameToImageConverter PRIVATE
    ${AVCODEC_INCLUDE_DIRS}
    ${AVUTIL_INCLUDE_DIRS}
    ${SWSCALE_INCLUDE_DIRS}
)
```

## Key Features

1. **Modern FFmpeg 6.1 API**: Uses non-deprecated functions
2. **Automatic format conversion**: Handles various pixel formats using swscale
3. **Error handling**: Comprehensive error checking and reporting
4. **Memory management**: Proper cleanup of FFmpeg resources
5. **Flexible output**: Supports multiple image formats via Qt's save capabilities

## Notes

- The implementation handles both direct format conversion (when possible) and uses swscale for unsupported formats
- Always makes a copy of the image data to avoid issues with frame buffer lifetime
- Includes proper error handling and resource cleanup
- Supports common image formats like PNG, JPEG, BMP through Qt's save functionality

Make sure to link against the required FFmpeg libraries (`avcodec`, `avutil`, `swscale`) when building your project.
