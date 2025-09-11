```

#include <QString>
#include <QFileInfo>
#include <QDir>
#include <QCryptographicHash>
#include <QException>
#include <QDebug>
#include <QImage>
#include <QMediaPlayer>
#include <QVideoSink>
#include <QStandardPaths>
#include <QCoreApplication>
#include <QEventLoop>

// Custom exception class for thumbnail generation errors
class ThumbnailException : public QException {
public:
    ThumbnailException(const QString& message) : m_message(message) {}
    QString message() const { return m_message; }
    void raise() const override { throw *this; }
    QException* clone() const override { return new ThumbnailException(*this); }
private:
    QString m_message;
};

QString getThumbnailPath(const QString& filePath) {
    try {
        // Validate input file
        QFileInfo fileInfo(filePath);
        if (!fileInfo.exists()) {
            throw ThumbnailException("File does not exist: " + filePath);
        }
        if (!fileInfo.isFile()) {
            throw ThumbnailException("Path is not a file: " + filePath);
        }

        // Compute MD5 hash of the file URI for thumbnail naming
        QString uri = QUrl::fromLocalFile(fileInfo.absoluteFilePath()).toString();
        QByteArray uriBytes = uri.toUtf8();
        QByteArray hash = QCryptographicHash::hash(uriBytes, QCryptographicHash::Md5);
        QString thumbnailName = QString(hash.toHex()) + ".png";

        // Use Qt's cache directory for thumbnails
        QString thumbnailDir = QStandardPaths::writableLocation(QStandardPaths::CacheLocation) + "/thumbnails/normal/";
        QDir dir(thumbnailDir);
        if (!dir.exists()) {
            if (!dir.mkpath(thumbnailDir)) {
                throw ThumbnailException("Failed to create thumbnail directory: " + thumbnailDir);
            }
        }

        // Check for existing thumbnail
        QString thumbnailPath = thumbnailDir + thumbnailName;
        if (QFileInfo::exists(thumbnailPath)) {
            qDebug() << "Found existing thumbnail at:" << thumbnailPath;
            return thumbnailPath;
        }

        // Determine file type using Qt's MIME database
        QMimeDatabase mimeDb;
        QMimeType mimeType = mimeDb.mimeTypeForFile(filePath);
        if (!mimeType.isValid()) {
            throw ThumbnailException("Failed to determine MIME type for file: " + filePath);
        }

        bool isImage = mimeType.name().startsWith("image/");
        bool isVideo = mimeType.name().startsWith("video/");

        if (!isImage && !isVideo) {
            throw ThumbnailException("Unsupported file type: " + mimeType.name());
        }

        QImage thumbnail;
        if (isImage) {
            // Load and scale image using QImage
            thumbnail = QImage(filePath);
            if (thumbnail.isNull()) {
                throw ThumbnailException("Failed to load image: " + filePath);
            }
            thumbnail = thumbnail.scaled(128, 128, Qt::KeepAspectRatio, Qt::SmoothTransformation);
        } else if (isVideo) {
            // Use QMediaPlayer and QVideoSink to extract a frame
            QMediaPlayer player;
            QVideoSink videoSink;
            player.setVideoOutput(&videoSink);
            player.setSource(QUrl::fromLocalFile(filePath));

            QEventLoop loop;
            bool frameReceived = false;
            QObject::connect(&videoSink, &QVideoSink::videoFrameChanged, [&](const QVideoFrame& frame) {
                if (frame.isValid()) {
                    thumbnail = frame.toImage();
                    frameReceived = true;
                    loop.quit();
                }
            });

            // Start playback to capture a frame
            player.play();

            // Wait for a frame (timeout after 5 seconds)
            QTimer timer;
            timer.setSingleShot(true);
            QObject::connect(&timer, &QTimer::timeout, &loop, &QEventLoop::quit);
            timer.start(5000);
            loop.exec();

            if (!frameReceived || thumbnail.isNull()) {
                throw ThumbnailException("Failed to extract video frame from: " + filePath);
            }

            // Scale thumbnail
            thumbnail = thumbnail.scaled(128, 128, Qt::KeepAspectRatio, Qt::SmoothTransformation);
        }

        // Save thumbnail
        if (!thumbnail.save(thumbnailPath, "PNG")) {
            throw ThumbnailException("Failed to save thumbnail to: " + thumbnailPath);
        }

        qDebug() << "Generated thumbnail at:" << thumbnailPath;
        return thumbnailPath;

    } catch (const ThumbnailException& e) {
        qWarning() << "Thumbnail generation error:" << e.message();
        return QString();
    } catch (...) {
        qWarning() << "Unexpected error during thumbnail generation for file:" << filePath;
        return QString();
    }
}
