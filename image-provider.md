# System Icon Image Provider for Qt6 (QML)

Here's a complete implementation of a `QQuickImageProvider` that retrieves system theme icons using `QIcon::fromTheme()` without any widget or style dependencies:

```cpp
#include <QQuickImageProvider>
#include <QIcon>
#include <QPixmap>
#include <QDebug>

class SystemIconProvider : public QQuickImageProvider
{
public:
    SystemIconProvider()
        : QQuickImageProvider(QQuickImageProvider::Pixmap)
    {
    }

    QPixmap requestPixmap(const QString &id, QSize *size, const QSize &requestedSize) override
    {
        // Parse the requested size if specified in the ID (format: "iconName?width-height")
        QString iconName = id;
        QSize iconSize(32, 32); // default size
        
        int questionMarkPos = id.indexOf('?');
        if (questionMarkPos != -1) {
            iconName = id.left(questionMarkPos);
            QString sizePart = id.mid(questionMarkPos + 1);
            
            int dashPos = sizePart.indexOf('-');
            if (dashPos != -1) {
                bool widthOk, heightOk;
                int width = sizePart.left(dashPos).toInt(&widthOk);
                int height = sizePart.mid(dashPos + 1).toInt(&heightOk);
                
                if (widthOk && heightOk && width > 0 && height > 0) {
                    iconSize = QSize(width, height);
                }
            }
        }
        
        // If no size in ID, use requestedSize if valid
        if (requestedSize.isValid()) {
            iconSize = requestedSize;
        }
        
        // Get the theme icon
        QPixmap pixmap;
        if (QIcon::hasThemeIcon(iconName)) {
            QIcon icon = QIcon::fromTheme(iconName);
            pixmap = icon.pixmap(iconSize);
        } else {
            qWarning() << "Theme icon not found:" << iconName;
            // Return a transparent pixmap of requested size
            pixmap = QPixmap(iconSize);
            pixmap.fill(Qt::transparent);
        }
        
        if (size) {
            *size = pixmap.size();
        }
        
        return pixmap;
    }
};
```

## How to use in QML:

1. First register the provider in your C++ code (e.g., in main.cpp):

```cpp
#include <QQmlApplicationEngine>
#include "SystemIconProvider.h"

int main(int argc, char *argv[])
{
    QGuiApplication app(argc, argv);
    
    QQmlApplicationEngine engine;
    engine.addImageProvider(QLatin1String("systemicons"), new SystemIconProvider());
    
    // ... rest of your application setup
    return app.exec();
}
```

2. Then use it in QML:

```qml
Image {
    source: "image://systemicons/folder"  // Default size (32x32)
    width: 32
    height: 32
}

Image {
    source: "image://systemicons/document-save?48-48"  // Explicit size
    width: 48
    height: 48
}

Image {
    source: "image://systemicons/edit-cut"  // Will use implicit size from Image element
    width: 24
    height: 24
}
```

## Features:

1. Uses Qt6's theme icon system (`QIcon::fromTheme()`)
2. No widget or style dependencies
3. Supports both explicit sizing via URL and implicit sizing via Image element
4. Falls back gracefully when icons aren't available
5. Clean QML interface

Common icon names you can use include: "folder", "document-open", "document-save", "edit-cut", "edit-copy", "edit-paste", "media-playback-start", "media-playback-pause", etc. (depending on the icon theme installed on the system).
