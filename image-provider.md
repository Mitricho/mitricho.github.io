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




# Full List of System Theme Icons for Qt/QML

The available icon names depend on the [FreeDesktop Icon Theme Specification](https://specifications.freedesktop.org/icon-naming-spec/icon-naming-spec-latest.html) which is followed by most Linux desktop environments (GNOME, KDE, XFCE, etc.) and partially supported on other platforms.

## Standard Icon Categories

### Actions
```
edit-cut, edit-copy, edit-paste, edit-delete, edit-select-all, edit-find, edit-find-replace
edit-redo, edit-undo, edit-clear, edit-clear-all
document-new, document-open, document-save, document-save-as, document-send
document-print, document-print-preview, document-properties
folder-new, view-refresh, process-stop, media-playback-start, media-playback-pause
media-playback-stop, media-record, media-seek-backward, media-seek-forward
media-skip-backward, media-skip-forward, media-eject, media-playlist-repeat
media-playlist-shuffle, zoom-in, zoom-out, zoom-fit-best, zoom-original
view-sort-ascending, view-sort-descending, format-indent-less, format-indent-more
format-justify-left, format-justify-center, format-justify-right, format-justify-fill
format-text-bold, format-text-italic, format-text-underline, format-text-strikethrough
list-add, list-remove, help-about, help-contents, help-faq
```

### Applications
```
accessories-calculator, accessories-text-editor, internet-mail, internet-web-browser
multimedia-volume-control, preferences-desktop, preferences-system, system-file-manager
utilities-terminal
```

### Categories
```
applications-accessories, applications-development, applications-engineering
applications-games, applications-graphics, applications-internet
applications-multimedia, applications-office, applications-other
applications-science, applications-system, applications-utilities
preferences-desktop, preferences-system
```

### Devices
```
audio-card, audio-input-microphone, battery, camera, camera-photo
computer, drive-harddisk, drive-optical, drive-removable-media
input-gaming, input-keyboard, input-mouse, input-tablet
media-flash, media-floppy, media-optical, media-tape
modem, multimedia-player, network-wired, network-wireless
printer, scanner, video-display
```

### Emblems
```
emblem-default, emblem-documents, emblem-downloads, emblem-favorite
emblem-important, emblem-mail, emblem-photos, emblem-readonly
emblem-shared, emblem-symbolic-link, emblem-synchronized, emblem-system
emblem-unreadable
```

### Mime Types
```
application-x-executable, audio-x-generic, font-x-generic, image-x-generic
package-x-generic, text-html, text-x-generic, text-x-script
video-x-generic, x-office-address-book, x-office-calendar
x-office-document, x-office-presentation, x-office-spreadsheet
```

### Places
```
folder, folder-remote, folder-saved-search, folder-documents
folder-download, folder-music, folder-pictures, folder-publicshare
folder-templates, folder-videos, network-server, network-workgroup
start-here, user-bookmarks, user-desktop, user-home, user-trash
```

### Status
```
appointment-missed, appointment-soon, audio-volume-high, audio-volume-low
audio-volume-medium, audio-volume-muted, battery-caution, battery-low
dialog-error, dialog-information, dialog-password, dialog-question
dialog-warning, folder-drag-accept, folder-open, folder-visiting
image-loading, image-missing, mail-attachment, mail-unread
mail-read, mail-replied, mail-signed, mail-signed-verified
media-playlist-repeat, media-playlist-shuffle, network-error
network-idle, network-offline, network-receive, network-transmit
network-transmit-receive, printer-error, printer-printing, security-high
security-medium, security-low, software-update-available
software-update-urgent, task-due, task-past-due, user-available
user-away, user-idle, user-offline, user-trash-full, weather-clear
weather-clear-night, weather-few-clouds, weather-few-clouds-night
weather-overcast, weather-severe-alert, weather-showers
weather-showers-scattered, weather-snow, weather-storm
```

## How to Use in QML

```qml
Image {
    source: "image://systemicons/folder"  // Standard folder icon
    width: 32
    height: 32
}

Image {
    source: "image://systemicons/document-save"  // Save icon
    width: 24
    height: 24
}

Image {
    source: "image://systemicons/dialog-information"  // Info dialog icon
    width: 48
    height: 48
}
```

## Notes:

1. **Availability varies** by platform and installed icon themes
2. **Fallback behavior**: If an icon isn't available, you'll get a transparent image (as implemented in the provider)
3. **Size variants**: Many icons have different versions for 16px, 22px, 24px, 32px, 48px, etc.
4. **Custom themes**: Users may have different icon themes installed which can affect appearance

To check if a specific icon is available in your current theme, you can use:
```cpp
bool available = QIcon::hasThemeIcon("icon-name");
```

Would you like me to provide any additional details about specific icon categories or usage patterns?
