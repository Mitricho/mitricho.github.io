# Fast File System Enumeration with Qt6

Here's a high-performance implementation for enumerating files and folders using Qt6, returning each item as a `QVariantMap`:

## Header File (FileEnumerator.h)

```cpp
#pragma once

#include <QObject>
#include <QVariantMap>
#include <QDir>
#include <QFuture>
#include <QThreadPool>
#include <QtConcurrent>

class FileEnumerator : public QObject
{
    Q_OBJECT
public:
    explicit FileEnumerator(QObject *parent = nullptr);
    
    Q_INVOKABLE void startEnumeration(const QString &directoryPath);
    Q_INVOKABLE void cancel();

signals:
    void itemFound(const QVariantMap &item);
    void enumerationFinished();
    void enumerationCancelled();
    void errorOccurred(const QString &error);

private:
    static QVariantMap fileInfoToMap(const QFileInfo &info);
    static void enumerateDirectory(const QString &path, FileEnumerator *enumerator, std::atomic<bool> &cancelled);

    QFuture<void> m_future;
    std::atomic<bool> m_cancelled{false};
};
```

## Implementation File (FileEnumerator.cpp)

```cpp
#include "FileEnumerator.h"
#include <QDateTime>
#include <QMimeDatabase>

FileEnumerator::FileEnumerator(QObject *parent) 
    : QObject(parent)
{
}

void FileEnumerator::startEnumeration(const QString &directoryPath)
{
    m_cancelled = false;
    
    QDir dir(directoryPath);
    if (!dir.exists()) {
        emit errorOccurred("Directory does not exist");
        return;
    }

    // Use QtConcurrent for parallel processing in a separate thread
    m_future = QtConcurrent::run([this, directoryPath]() {
        enumerateDirectory(directoryPath, this, m_cancelled);
        
        if (m_cancelled) {
            QMetaObject::invokeMethod(this, "enumerationCancelled");
        } else {
            QMetaObject::invokeMethod(this, "enumerationFinished");
        }
    });
}

void FileEnumerator::cancel()
{
    m_cancelled = true;
    m_future.waitForFinished();
}

QVariantMap FileEnumerator::fileInfoToMap(const QFileInfo &info)
{
    static QMimeDatabase mimeDb; // Reuse for performance
    
    QVariantMap item;
    item["name"] = info.fileName();
    item["path"] = info.filePath();
    item["absolutePath"] = info.absoluteFilePath();
    item["isDir"] = info.isDir();
    item["isFile"] = info.isFile();
    item["isSymLink"] = info.isSymLink();
    item["size"] = info.size();
    item["created"] = info.birthTime().toString(Qt::ISODate);
    item["modified"] = info.lastModified().toString(Qt::ISODate);
    item["accessed"] = info.lastRead().toString(Qt::ISODate);
    item["hidden"] = info.isHidden();
    
    if (info.isFile()) {
        item["mimeType"] = mimeDb.mimeTypeForFile(info).name();
        item["suffix"] = info.suffix();
    }
    
    item["permissions"] = static_cast<int>(info.permissions());
    item["owner"] = info.owner();
    item["group"] = info.group();
    
    return item;
}

void FileEnumerator::enumerateDirectory(const QString &path, FileEnumerator *enumerator, std::atomic<bool> &cancelled)
{
    QDir dir(path);
    
    // Optimize directory listing
    dir.setFilter(QDir::AllEntries | QDir::NoDotAndDotDot | QDir::System | QDir::Hidden);
    dir.setSorting(QDir::DirsFirst | QDir::Name | QDir::IgnoreCase);
    
    // Get all files and directories in one go
    const QFileInfoList entries = dir.entryInfoList();
    
    for (const QFileInfo &info : entries) {
        if (cancelled) {
            return;
        }
        
        QVariantMap item = fileInfoToMap(info);
        QMetaObject::invokeMethod(enumerator, "itemFound", Qt::QueuedConnection, Q_ARG(QVariantMap, item));
        
        // Recursively enumerate subdirectories
        if (info.isDir() && !info.isSymLink()) {
            enumerateDirectory(info.absoluteFilePath(), enumerator, cancelled);
        }
    }
}
```

## How to Use

### From C++:

```cpp
FileEnumerator enumerator;
QObject::connect(&enumerator, &FileEnumerator::itemFound, [](const QVariantMap &item) {
    qDebug() << "Found:" << item["name"].toString();
});
QObject::connect(&enumerator, &FileEnumerator::enumerationFinished, []() {
    qDebug() << "Enumeration finished";
});

enumerator.startEnumeration("/path/to/directory");
```

### From QML:

```qml
import QtQuick 2.15
import QtQuick.Controls 2.15

ApplicationWindow {
    visible: true
    width: 800
    height: 600

    FileEnumerator {
        id: fileEnumerator
        onItemFound: {
            console.log("Found:", item.name, "Path:", item.path, "Size:", item.size);
            listModel.append(item);
        }
        onEnumerationFinished: console.log("Finished enumerating")
        onErrorOccurred: console.error("Error:", error)
    }

    ListView {
        anchors.fill: parent
        model: ListModel { id: listModel }
        delegate: Text { 
            text: model.name + (model.isDir ? " (DIR)" : "") 
        }
    }

    Component.onCompleted: fileEnumerator.startEnumeration(Qt.resolvedUrl("."))
}
```

## Performance Optimizations:

1. **Single directory scan**: Uses `entryInfoList()` instead of multiple `entryList()` calls
2. **Parallel processing**: Uses `QtConcurrent` to run in background thread
3. **Batched processing**: Processes all files in a directory at once
4. **MIME type caching**: Reuses `QMimeDatabase` instance
5. **Cancellation support**: Allows stopping long-running enumerations
6. **Minimal allocations**: Creates QVariantMap only when needed
7. **Queued signals**: Uses `Qt::QueuedConnection` to avoid blocking

## Features:

- Recursive directory traversal
- Complete file information (permissions, dates, ownership)
- MIME type detection
- Symlink detection
- Hidden file support
- Cancellable operation
- Thread-safe implementation
- QML-friendly interface

Would you like me to add any specific features or modify the implementation in any way?
