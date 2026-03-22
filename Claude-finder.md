

# Custom File Dialog for Qt6 - MacOS Finder Style

## Project Structure

```
CustomFileDialog/
├── CMakeLists.txt
├── src/
│   ├── main.cpp
│   ├── customfiledialog.h
│   ├── customfiledialog.cpp
│   ├── filesystemmodel.h
│   ├── filesystemmodel.cpp
│   ├── filepreviewwidget.h
│   ├── filepreviewwidget.cpp
│   ├── thumbnailprovider.h
│   ├── thumbnailprovider.cpp
│   ├── pathbreadcrumb.h
│   ├── pathbreadcrumb.cpp
│   ├── sidebarwidget.h
│   ├── sidebarwidget.cpp
│   ├── fileitemdelegate.h
│   ├── fileitemdelegate.cpp
│   ├── sortfilterproxymodel.h
│   ├── sortfilterproxymodel.cpp
│   └── dialogresult.h
└── resources/
    └── resources.qrc
```

## CMakeLists.txt

```cmake
cmake_minimum_required(VERSION 3.20)
project(CustomFileDialog VERSION 1.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 20)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_AUTOMOC ON)
set(CMAKE_AUTORCC ON)
set(CMAKE_AUTOUIC ON)

find_package(Qt6 6.10 REQUIRED COMPONENTS
    Core
    Gui
    Widgets
    Concurrent
)

set(SOURCES
    src/main.cpp
    src/customfiledialog.cpp
    src/filesystemmodel.cpp
    src/filepreviewwidget.cpp
    src/thumbnailprovider.cpp
    src/pathbreadcrumb.cpp
    src/sidebarwidget.cpp
    src/fileitemdelegate.cpp
    src/sortfilterproxymodel.cpp
)

set(HEADERS
    src/customfiledialog.h
    src/filesystemmodel.h
    src/filepreviewwidget.h
    src/thumbnailprovider.h
    src/pathbreadcrumb.h
    src/sidebarwidget.h
    src/fileitemdelegate.h
    src/sortfilterproxymodel.h
    src/dialogresult.h
)

qt_add_executable(CustomFileDialog ${SOURCES} ${HEADERS} resources/resources.qrc)

target_link_libraries(CustomFileDialog PRIVATE
    Qt6::Core
    Qt6::Gui
    Qt6::Widgets
    Qt6::Concurrent
)

target_include_directories(CustomFileDialog PRIVATE src)
```

## resources/resources.qrc

```xml
<RCC>
    <qresource prefix="/">
    </qresource>
</RCC>
```

## src/dialogresult.h

```cpp
#pragma once

#include <QString>
#include <QStringList>
#include <QMetaType>

enum class FileDialogMode {
    OpenFile,           // Select a single file
    OpenFiles,          // Select multiple files
    OpenDirectory,      // Select a directory
    SaveFile,           // Specify a file name for saving
    OpenFilesAndDirs    // Select any combination of files and directories
};

enum class FileDialogViewMode {
    List,
    Icons,
    Columns
};

struct FileDialogResult {
    bool accepted = false;
    QString selectedPath;
    QStringList selectedPaths;
    QString selectedFilter;

    bool isEmpty() const {
        return selectedPaths.isEmpty() && selectedPath.isEmpty();
    }
};

Q_DECLARE_METATYPE(FileDialogResult)
```

## src/sortfilterproxymodel.h

```cpp
#pragma once

#include <QSortFilterProxyModel>
#include <QFileInfo>
#include <QCollator>
#include <QStringList>
#include <QDateTime>
#include <QMimeDatabase>

class SortFilterProxyModel : public QSortFilterProxyModel
{
    Q_OBJECT

public:
    enum SortCriteria {
        SortByName = 0,
        SortBySize,
        SortByType,
        SortByDate,
        SortByExtension
    };
    Q_ENUM(SortCriteria)

    explicit SortFilterProxyModel(QObject *parent = nullptr);

    void setNameFilters(const QStringList &filters);
    void setShowHiddenFiles(bool show);
    void setSortCriteria(SortCriteria criteria);
    void setSortFoldersFirst(bool foldersFirst);
    void setFilterCaseSensitivity(Qt::CaseSensitivity sensitivity);

    QStringList nameFilters() const { return m_nameFilters; }
    bool showHiddenFiles() const { return m_showHidden; }
    SortCriteria sortCriteria() const { return m_sortCriteria; }

protected:
    bool filterAcceptsRow(int sourceRow, const QModelIndex &sourceParent) const override;
    bool lessThan(const QModelIndex &left, const QModelIndex &right) const override;

private:
    bool matchesNameFilter(const QString &fileName) const;

    QStringList m_nameFilters;
    bool m_showHidden = false;
    SortCriteria m_sortCriteria = SortByName;
    bool m_foldersFirst = true;
    QCollator m_collator;
    QMimeDatabase m_mimeDb;
    Qt::CaseSensitivity m_caseSensitivity = Qt::CaseInsensitive;
};
```

## src/sortfilterproxymodel.cpp

```cpp
#include "sortfilterproxymodel.h"
#include <QFileSystemModel>
#include <QRegularExpression>

SortFilterProxyModel::SortFilterProxyModel(QObject *parent)
    : QSortFilterProxyModel(parent)
{
    m_collator.setNumericMode(true);
    m_collator.setCaseSensitivity(Qt::CaseInsensitive);
    setDynamicSortFilter(true);
}

void SortFilterProxyModel::setNameFilters(const QStringList &filters)
{
    m_nameFilters = filters;
    invalidateFilter();
}

void SortFilterProxyModel::setShowHiddenFiles(bool show)
{
    m_showHidden = show;
    invalidateFilter();
}

void SortFilterProxyModel::setSortCriteria(SortCriteria criteria)
{
    m_sortCriteria = criteria;
    invalidate();
}

void SortFilterProxyModel::setSortFoldersFirst(bool foldersFirst)
{
    m_foldersFirst = foldersFirst;
    invalidate();
}

void SortFilterProxyModel::setFilterCaseSensitivity(Qt::CaseSensitivity sensitivity)
{
    m_caseSensitivity = sensitivity;
    invalidateFilter();
}

bool SortFilterProxyModel::filterAcceptsRow(int sourceRow,
                                             const QModelIndex &sourceParent) const
{
    auto *fsModel = qobject_cast<QFileSystemModel *>(sourceModel());
    if (!fsModel)
        return true;

    QModelIndex idx = fsModel->index(sourceRow, 0, sourceParent);
    QString fileName = fsModel->fileName(idx);
    QFileInfo fi = fsModel->fileInfo(idx);

    // Filter hidden files
    if (!m_showHidden && fileName.startsWith('.') && fileName != "." && fileName != "..")
        return false;

    // Directories always pass name filters
    if (fi.isDir())
        return true;

    // Apply name filters
    if (!m_nameFilters.isEmpty()) {
        return matchesNameFilter(fileName);
    }

    return true;
}

bool SortFilterProxyModel::matchesNameFilter(const QString &fileName) const
{
    for (const QString &filter : m_nameFilters) {
        // Extract patterns from filter strings like "Images (*.png *.jpg)"
        QString pattern = filter;
        int parenStart = filter.indexOf('(');
        int parenEnd = filter.indexOf(')');
        if (parenStart != -1 && parenEnd != -1) {
            pattern = filter.mid(parenStart + 1, parenEnd - parenStart - 1).trimmed();
        }

        QStringList patterns = pattern.split(' ', Qt::SkipEmptyParts);
        for (const QString &p : patterns) {
            QString regexPattern = QRegularExpression::wildcardToRegularExpression(p.trimmed());
            QRegularExpression regex(regexPattern,
                m_caseSensitivity == Qt::CaseInsensitive
                    ? QRegularExpression::CaseInsensitiveOption
                    : QRegularExpression::NoPatternOption);
            if (regex.match(fileName).hasMatch())
                return true;
        }
    }
    return false;
}

bool SortFilterProxyModel::lessThan(const QModelIndex &left,
                                     const QModelIndex &right) const
{
    auto *fsModel = qobject_cast<QFileSystemModel *>(sourceModel());
    if (!fsModel)
        return QSortFilterProxyModel::lessThan(left, right);

    QFileInfo leftInfo = fsModel->fileInfo(left);
    QFileInfo rightInfo = fsModel->fileInfo(right);

    // Folders first
    if (m_foldersFirst) {
        if (leftInfo.isDir() && !rightInfo.isDir())
            return sortOrder() == Qt::AscendingOrder;
        if (!leftInfo.isDir() && rightInfo.isDir())
            return sortOrder() != Qt::AscendingOrder;
    }

    switch (m_sortCriteria) {
    case SortByName:
        return m_collator.compare(leftInfo.fileName(), rightInfo.fileName()) < 0;

    case SortBySize:
        if (leftInfo.isDir() && rightInfo.isDir())
            return m_collator.compare(leftInfo.fileName(), rightInfo.fileName()) < 0;
        return leftInfo.size() < rightInfo.size();

    case SortByType: {
        QMimeDatabase db;
        QString leftMime = db.mimeTypeForFile(leftInfo).name();
        QString rightMime = db.mimeTypeForFile(rightInfo).name();
        if (leftMime == rightMime)
            return m_collator.compare(leftInfo.fileName(), rightInfo.fileName()) < 0;
        return leftMime < rightMime;
    }

    case SortByDate:
        if (leftInfo.lastModified() == rightInfo.lastModified())
            return m_collator.compare(leftInfo.fileName(), rightInfo.fileName()) < 0;
        return leftInfo.lastModified() < rightInfo.lastModified();

    case SortByExtension: {
        QString leftExt = leftInfo.suffix().toLower();
        QString rightExt = rightInfo.suffix().toLower();
        if (leftExt == rightExt)
            return m_collator.compare(leftInfo.fileName(), rightInfo.fileName()) < 0;
        return leftExt < rightExt;
    }
    }

    return QSortFilterProxyModel::lessThan(left, right);
}
```

## src/thumbnailprovider.h

```cpp
#pragma once

#include <QObject>
#include <QPixmap>
#include <QIcon>
#include <QCache>
#include <QMutex>
#include <QSet>
#include <QSize>
#include <QFileInfo>
#include <QMimeDatabase>

class ThumbnailProvider : public QObject
{
    Q_OBJECT

public:
    explicit ThumbnailProvider(QObject *parent = nullptr);
    ~ThumbnailProvider();

    void requestThumbnail(const QString &filePath, const QSize &size);
    QPixmap getCachedThumbnail(const QString &filePath, const QSize &size) const;
    bool hasCachedThumbnail(const QString &filePath, const QSize &size) const;
    QIcon fileIcon(const QFileInfo &info) const;
    void clearCache();
    void cancelAll();

    static bool isImageFile(const QString &filePath);
    static QStringList supportedImageFormats();

signals:
    void thumbnailReady(const QString &filePath, const QPixmap &thumbnail);

private:
    void generateThumbnail(const QString &filePath, const QSize &size);
    static QString cacheKey(const QString &filePath, const QSize &size);

    mutable QMutex m_mutex;
    QCache<QString, QPixmap> m_cache;
    QSet<QString> m_pendingRequests;
    QMimeDatabase m_mimeDb;
    bool m_cancelled = false;
};
```

## src/thumbnailprovider.cpp

```cpp
#include "thumbnailprovider.h"
#include <QtConcurrent>
#include <QImageReader>
#include <QFileIconProvider>
#include <QPainter>

ThumbnailProvider::ThumbnailProvider(QObject *parent)
    : QObject(parent)
    , m_cache(200) // Cache up to 200 thumbnails
{
}

ThumbnailProvider::~ThumbnailProvider()
{
    cancelAll();
}

void ThumbnailProvider::cancelAll()
{
    QMutexLocker lock(&m_mutex);
    m_cancelled = true;
    m_pendingRequests.clear();
}

void ThumbnailProvider::clearCache()
{
    QMutexLocker lock(&m_mutex);
    m_cache.clear();
}

QString ThumbnailProvider::cacheKey(const QString &filePath, const QSize &size)
{
    return filePath + QStringLiteral("_%1x%2").arg(size.width()).arg(size.height());
}

bool ThumbnailProvider::isImageFile(const QString &filePath)
{
    QMimeDatabase db;
    QMimeType mime = db.mimeTypeForFile(filePath, QMimeDatabase::MatchExtension);
    return mime.name().startsWith("image/");
}

QStringList ThumbnailProvider::supportedImageFormats()
{
    QStringList formats;
    const auto supported = QImageReader::supportedImageFormats();
    for (const QByteArray &fmt : supported) {
        formats << QStringLiteral("*.") + QString::fromLatin1(fmt);
    }
    return formats;
}

QPixmap ThumbnailProvider::getCachedThumbnail(const QString &filePath, const QSize &size) const
{
    QMutexLocker lock(&m_mutex);
    QString key = cacheKey(filePath, size);
    if (auto *pix = m_cache.object(key))
        return *pix;
    return QPixmap();
}

bool ThumbnailProvider::hasCachedThumbnail(const QString &filePath, const QSize &size) const
{
    QMutexLocker lock(&m_mutex);
    return m_cache.contains(cacheKey(filePath, size));
}

QIcon ThumbnailProvider::fileIcon(const QFileInfo &info) const
{
    QFileIconProvider provider;
    return provider.icon(info);
}

void ThumbnailProvider::requestThumbnail(const QString &filePath, const QSize &size)
{
    {
        QMutexLocker lock(&m_mutex);
        m_cancelled = false;
        QString key = cacheKey(filePath, size);

        if (m_cache.contains(key)) {
            emit thumbnailReady(filePath, *m_cache.object(key));
            return;
        }

        if (m_pendingRequests.contains(key))
            return;

        m_pendingRequests.insert(key);
    }

    QtConcurrent::run([this, filePath, size]() {
        generateThumbnail(filePath, size);
    });
}

void ThumbnailProvider::generateThumbnail(const QString &filePath, const QSize &size)
{
    {
        QMutexLocker lock(&m_mutex);
        if (m_cancelled)
            return;
    }

    QPixmap thumbnail;

    if (isImageFile(filePath)) {
        QImageReader reader(filePath);
        reader.setAutoTransform(true);

        QSize originalSize = reader.size();
        if (originalSize.isValid()) {
            QSize scaledSize = originalSize.scaled(size, Qt::KeepAspectRatio);
            reader.setScaledSize(scaledSize);
        }

        QImage image = reader.read();
        if (!image.isNull()) {
            if (image.size().width() > size.width() || image.size().height() > size.height()) {
                image = image.scaled(size, Qt::KeepAspectRatio, Qt::SmoothTransformation);
            }

            // Add a subtle shadow/border like macOS
            QPixmap result(image.size() + QSize(4, 4));
            result.fill(Qt::transparent);
            QPainter painter(&result);
            painter.setRenderHint(QPainter::Antialiasing);

            // Shadow
            painter.setPen(Qt::NoPen);
            painter.setBrush(QColor(0, 0, 0, 30));
            painter.drawRoundedRect(QRect(QPoint(2, 2), image.size()), 3, 3);

            // Image
            painter.drawImage(QPoint(0, 0), image);

            // Border
            painter.setPen(QColor(0, 0, 0, 40));
            painter.setBrush(Qt::NoBrush);
            painter.drawRoundedRect(QRect(QPoint(0, 0), image.size()), 2, 2);

            painter.end();
            thumbnail = result;
        }
    }

    if (thumbnail.isNull()) {
        QFileIconProvider iconProvider;
        QFileInfo fi(filePath);
        QIcon icon = iconProvider.icon(fi);
        thumbnail = icon.pixmap(size);
    }

    {
        QMutexLocker lock(&m_mutex);
        if (m_cancelled)
            return;
        QString key = cacheKey(filePath, size);
        m_cache.insert(key, new QPixmap(thumbnail));
        m_pendingRequests.remove(key);
    }

    QMetaObject::invokeMethod(this, [this, filePath, thumbnail]() {
        emit thumbnailReady(filePath, thumbnail);
    }, Qt::QueuedConnection);
}
```

## src/fileitemdelegate.h

```cpp
#pragma once

#include <QStyledItemDelegate>
#include <QFileSystemModel>
#include "thumbnailprovider.h"

class FileItemDelegate : public QStyledItemDelegate
{
    Q_OBJECT

public:
    enum ViewStyle {
        ListView,
        IconView
    };

    explicit FileItemDelegate(ThumbnailProvider *thumbProvider,
                               QObject *parent = nullptr);

    void setViewStyle(ViewStyle style);
    ViewStyle viewStyle() const { return m_viewStyle; }
    void setIconSize(const QSize &size);

    void paint(QPainter *painter, const QStyleOptionViewItem &option,
               const QModelIndex &index) const override;
    QSize sizeHint(const QStyleOptionViewItem &option,
                   const QModelIndex &index) const override;

private:
    void paintListItem(QPainter *painter, const QStyleOptionViewItem &option,
                       const QModelIndex &index) const;
    void paintIconItem(QPainter *painter, const QStyleOptionViewItem &option,
                       const QModelIndex &index) const;

    QString formatFileSize(qint64 bytes) const;
    QString formatDate(const QDateTime &dt) const;

    ThumbnailProvider *m_thumbProvider;
    ViewStyle m_viewStyle = ListView;
    QSize m_iconSize{48, 48};
    QMimeDatabase m_mimeDb;
};
```

## src/fileitemdelegate.cpp

```cpp
#include "fileitemdelegate.h"
#include "sortfilterproxymodel.h"
#include <QPainter>
#include <QApplication>
#include <QFileInfo>
#include <QFileIconProvider>
#include <QDateTime>

FileItemDelegate::FileItemDelegate(ThumbnailProvider *thumbProvider,
                                     QObject *parent)
    : QStyledItemDelegate(parent)
    , m_thumbProvider(thumbProvider)
{
}

void FileItemDelegate::setViewStyle(ViewStyle style)
{
    m_viewStyle = style;
}

void FileItemDelegate::setIconSize(const QSize &size)
{
    m_iconSize = size;
}

QSize FileItemDelegate::sizeHint(const QStyleOptionViewItem &option,
                                  const QModelIndex &index) const
{
    Q_UNUSED(option)
    Q_UNUSED(index)

    if (m_viewStyle == IconView) {
        return QSize(m_iconSize.width() + 40, m_iconSize.height() + 50);
    }
    return QSize(-1, qMax(36, m_iconSize.height() + 8));
}

void FileItemDelegate::paint(QPainter *painter, const QStyleOptionViewItem &option,
                              const QModelIndex &index) const
{
    painter->save();
    painter->setRenderHint(QPainter::Antialiasing);

    if (m_viewStyle == IconView)
        paintIconItem(painter, option, index);
    else
        paintListItem(painter, option, index);

    painter->restore();
}

void FileItemDelegate::paintListItem(QPainter *painter,
                                      const QStyleOptionViewItem &option,
                                      const QModelIndex &index) const
{
    // Get the source model data through proxy
    const auto *proxyModel = qobject_cast<const SortFilterProxyModel *>(index.model());
    const QFileSystemModel *fsModel = nullptr;
    QModelIndex sourceIndex = index;

    if (proxyModel) {
        fsModel = qobject_cast<const QFileSystemModel *>(proxyModel->sourceModel());
        sourceIndex = proxyModel->mapToSource(index);
    }

    if (!fsModel) {
        QStyledItemDelegate::paint(painter, option, index);
        return;
    }

    // Only paint custom in column 0
    if (index.column() != 0) {
        QStyledItemDelegate::paint(painter, option, index);
        return;
    }

    QFileInfo fileInfo = fsModel->fileInfo(sourceIndex);
    QString fileName = fileInfo.fileName();

    // Background - macOS style selection
    QRect fullRect = option.rect;

    if (option.state & QStyle::State_Selected) {
        QColor selColor(0, 99, 225, 200); // macOS blue
        painter->setPen(Qt::NoPen);
        painter->setBrush(selColor);
        painter->drawRoundedRect(fullRect.adjusted(2, 1, -2, -1), 6, 6);
    } else if (option.state & QStyle::State_MouseOver) {
        QColor hoverColor(0, 0, 0, 15);
        painter->setPen(Qt::NoPen);
        painter->setBrush(hoverColor);
        painter->drawRoundedRect(fullRect.adjusted(2, 1, -2, -1), 6, 6);
    }

    // Icon
    int iconPad = 6;
    int iconH = fullRect.height() - 2 * iconPad;
    QSize thumbSize(iconH, iconH);
    QRect iconRect(fullRect.left() + 8, fullRect.top() + iconPad, iconH, iconH);

    QPixmap thumb;
    if (m_thumbProvider && ThumbnailProvider::isImageFile(fileInfo.absoluteFilePath())) {
        thumb = m_thumbProvider->getCachedThumbnail(fileInfo.absoluteFilePath(), thumbSize);
        if (thumb.isNull()) {
            m_thumbProvider->requestThumbnail(fileInfo.absoluteFilePath(), thumbSize);
        }
    }

    if (!thumb.isNull()) {
        QRect drawRect = thumb.rect();
        drawRect.moveCenter(iconRect.center());
        painter->drawPixmap(drawRect, thumb);
    } else {
        QFileIconProvider iconProvider;
        QIcon icon = iconProvider.icon(fileInfo);
        icon.paint(painter, iconRect, Qt::AlignCenter);
    }

    // Text
    bool selected = option.state & QStyle::State_Selected;
    QColor textColor = selected ? Qt::white : option.palette.color(QPalette::Text);
    QColor detailColor = selected ? QColor(255, 255, 255, 180) : QColor(128, 128, 128);

    int textLeft = iconRect.right() + 10;
    int textWidth = fullRect.right() - textLeft - 10;

    // File name
    QFont nameFont = option.font;
    nameFont.setPointSize(nameFont.pointSize());
    painter->setFont(nameFont);
    painter->setPen(textColor);

    QRect nameRect(textLeft, fullRect.top() + 4, textWidth * 0.5, fullRect.height() / 2);
    QString elidedName = painter->fontMetrics().elidedText(fileName, Qt::ElideMiddle, nameRect.width());
    painter->drawText(nameRect, Qt::AlignLeft | Qt::AlignVCenter, elidedName);

    // Details line: size and date
    QFont detailFont = option.font;
    detailFont.setPointSize(detailFont.pointSize() - 1);
    painter->setFont(detailFont);
    painter->setPen(detailColor);

    QString details;
    if (fileInfo.isDir()) {
        details = QStringLiteral("Folder");
    } else {
        details = formatFileSize(fileInfo.size());
    }

    QRect detailRect(textLeft, fullRect.top() + fullRect.height() / 2, textWidth * 0.5, fullRect.height() / 2 - 4);
    painter->drawText(detailRect, Qt::AlignLeft | Qt::AlignVCenter, details);

    // Date on the right side
    QString dateStr = formatDate(fileInfo.lastModified());
    QRect dateRect(fullRect.right() - 180, fullRect.top(), 170, fullRect.height());
    painter->setPen(detailColor);
    painter->setFont(detailFont);
    painter->drawText(dateRect, Qt::AlignRight | Qt::AlignVCenter, dateStr);

    // Size on the right-center
    if (!fileInfo.isDir()) {
        QString sizeStr = formatFileSize(fileInfo.size());
        QRect sizeRect(fullRect.right() - 350, fullRect.top(), 150, fullRect.height());
        painter->drawText(sizeRect, Qt::AlignRight | Qt::AlignVCenter, sizeStr);
    }
}

void FileItemDelegate::paintIconItem(QPainter *painter,
                                      const QStyleOptionViewItem &option,
                                      const QModelIndex &index) const
{
    const auto *proxyModel = qobject_cast<const SortFilterProxyModel *>(index.model());
    const QFileSystemModel *fsModel = nullptr;
    QModelIndex sourceIndex = index;

    if (proxyModel) {
        fsModel = qobject_cast<const QFileSystemModel *>(proxyModel->sourceModel());
        sourceIndex = proxyModel->mapToSource(index);
    }

    if (!fsModel) {
        QStyledItemDelegate::paint(painter, option, index);
        return;
    }

    QFileInfo fileInfo = fsModel->fileInfo(sourceIndex);
    QString fileName = fileInfo.fileName();
    QRect fullRect = option.rect;

    // Selection background
    if (option.state & QStyle::State_Selected) {
        // Only highlight the text area in icon view (macOS style)
    } else if (option.state & QStyle::State_MouseOver) {
        QColor hoverColor(0, 0, 0, 10);
        painter->setPen(Qt::NoPen);
        painter->setBrush(hoverColor);
        painter->drawRoundedRect(fullRect.adjusted(4, 4, -4, -4), 8, 8);
    }

    // Icon area
    QRect iconRect(fullRect.left() + (fullRect.width() - m_iconSize.width()) / 2,
                   fullRect.top() + 8,
                   m_iconSize.width(), m_iconSize.height());

    QPixmap thumb;
    if (m_thumbProvider && ThumbnailProvider::isImageFile(fileInfo.absoluteFilePath())) {
        thumb = m_thumbProvider->getCachedThumbnail(fileInfo.absoluteFilePath(), m_iconSize);
        if (thumb.isNull()) {
            m_thumbProvider->requestThumbnail(fileInfo.absoluteFilePath(), m_iconSize);
        }
    }

    if (!thumb.isNull()) {
        QRect drawRect = thumb.rect();
        drawRect.moveCenter(iconRect.center());
        painter->drawPixmap(drawRect, thumb);
    } else {
        QFileIconProvider iconProvider;
        QIcon icon = iconProvider.icon(fileInfo);
        icon.paint(painter, iconRect, Qt::AlignCenter);
    }

    // Text below icon
    bool selected = option.state & QStyle::State_Selected;
    QRect textRect(fullRect.left() + 4, iconRect.bottom() + 4,
                   fullRect.width() - 8, fullRect.height() - iconRect.height() - 16);

    QFont textFont = option.font;
    textFont.setPointSize(textFont.pointSize() - 1);
    painter->setFont(textFont);

    QString elidedName = painter->fontMetrics().elidedText(fileName, Qt::ElideMiddle, textRect.width());

    if (selected) {
        // Draw selection pill behind text
        QRect textBounds = painter->fontMetrics().boundingRect(textRect,
                          Qt::AlignHCenter | Qt::AlignTop | Qt::TextWordWrap, elidedName);
        textBounds.adjust(-4, -2, 4, 2);
        textBounds.moveCenter(QPoint(textRect.center().x(), textBounds.center().y()));
        painter->setPen(Qt::NoPen);
        painter->setBrush(QColor(0, 99, 225));
        painter->drawRoundedRect(textBounds, 4, 4);
        painter->setPen(Qt::white);
    } else {
        painter->setPen(option.palette.color(QPalette::Text));
    }

    painter->drawText(textRect, Qt::AlignHCenter | Qt::AlignTop | Qt::TextWordWrap, elidedName);
}

QString FileItemDelegate::formatFileSize(qint64 bytes) const
{
    if (bytes < 0)
        return QString();

    const char *units[] = {"B", "KB", "MB", "GB", "TB"};
    int unitIndex = 0;
    double size = static_cast<double>(bytes);

    while (size >= 1024.0 && unitIndex < 4) {
        size /= 1024.0;
        unitIndex++;
    }

    if (unitIndex == 0)
        return QStringLiteral("%1 B").arg(bytes);

    return QStringLiteral("%1 %2").arg(size, 0, 'f', 1).arg(units[unitIndex]);
}

QString FileItemDelegate::formatDate(const QDateTime &dt) const
{
    if (!dt.isValid())
        return QString();

    QDateTime now = QDateTime::currentDateTime();
    qint64 daysAgo = dt.daysTo(now);

    if (daysAgo == 0)
        return QStringLiteral("Today, ") + dt.toString("h:mm AP");
    if (daysAgo == 1)
        return QStringLiteral("Yesterday, ") + dt.toString("h:mm AP");
    if (daysAgo < 7)
        return dt.toString("dddd, h:mm AP");

    return dt.toString("MMM d, yyyy, h:mm AP");
}
```

## src/pathbreadcrumb.h

```cpp
#pragma once

#include <QWidget>
#include <QHBoxLayout>
#include <QPushButton>
#include <QScrollArea>

class PathBreadcrumb : public QWidget
{
    Q_OBJECT

public:
    explicit PathBreadcrumb(QWidget *parent = nullptr);

    void setPath(const QString &path);
    QString currentPath() const { return m_currentPath; }

signals:
    void pathClicked(const QString &path);

private:
    void rebuildCrumbs();

    QScrollArea *m_scrollArea;
    QWidget *m_crumbContainer;
    QHBoxLayout *m_crumbLayout;
    QString m_currentPath;
};
```

## src/pathbreadcrumb.cpp

```cpp
#include "pathbreadcrumb.h"
#include <QDir>
#include <QLabel>
#include <QToolButton>
#include <QScrollBar>
#include <QTimer>

PathBreadcrumb::PathBreadcrumb(QWidget *parent)
    : QWidget(parent)
{
    auto *mainLayout = new QHBoxLayout(this);
    mainLayout->setContentsMargins(4, 0, 4, 0);
    mainLayout->setSpacing(0);

    m_scrollArea = new QScrollArea(this);
    m_scrollArea->setWidgetResizable(true);
    m_scrollArea->setHorizontalScrollBarPolicy(Qt::ScrollBarAlwaysOff);
    m_scrollArea->setVerticalScrollBarPolicy(Qt::ScrollBarAlwaysOff);
    m_scrollArea->setFrameShape(QFrame::NoFrame);
    m_scrollArea->setFixedHeight(30);
    m_scrollArea->setStyleSheet(
        "QScrollArea { background: transparent; }"
    );

    m_crumbContainer = new QWidget;
    m_crumbLayout = new QHBoxLayout(m_crumbContainer);
    m_crumbLayout->setContentsMargins(0, 0, 0, 0);
    m_crumbLayout->setSpacing(0);
    m_crumbLayout->addStretch();

    m_scrollArea->setWidget(m_crumbContainer);
    mainLayout->addWidget(m_scrollArea);

    setFixedHeight(32);
    setStyleSheet(
        "PathBreadcrumb {"
        "  background-color: rgba(0, 0, 0, 0.03);"
        "  border-radius: 6px;"
        "}"
    );
}

void PathBreadcrumb::setPath(const QString &path)
{
    m_currentPath = QDir::cleanPath(path);
    rebuildCrumbs();
}

void PathBreadcrumb::rebuildCrumbs()
{
    // Clear existing crumbs
    QLayoutItem *item;
    while ((item = m_crumbLayout->takeAt(0)) != nullptr) {
        delete item->widget();
        delete item;
    }

    QStringList parts = m_currentPath.split('/', Qt::SkipEmptyParts);

    // Root button
    auto *rootBtn = new QToolButton;
    rootBtn->setText(QStringLiteral(" / "));
    rootBtn->setStyleSheet(
        "QToolButton {"
        "  border: none;"
        "  padding: 2px 4px;"
        "  color: #0066CC;"
        "  font-size: 12px;"
        "  background: transparent;"
        "}"
        "QToolButton:hover {"
        "  background-color: rgba(0, 0, 0, 0.06);"
        "  border-radius: 4px;"
        "}"
    );
    connect(rootBtn, &QToolButton::clicked, this, [this]() {
        emit pathClicked("/");
    });
    m_crumbLayout->addWidget(rootBtn);

    QString accumulated = "/";
    for (int i = 0; i < parts.size(); ++i) {
        // Separator
        auto *sep = new QLabel(QStringLiteral("›"));
        sep->setStyleSheet("QLabel { color: #999; padding: 0 2px; font-size: 14px; }");
        m_crumbLayout->addWidget(sep);

        accumulated += parts[i] + "/";
        QString fullPath = QDir::cleanPath(accumulated);

        auto *btn = new QToolButton;
        btn->setText(parts[i]);
        btn->setToolTip(fullPath);

        bool isLast = (i == parts.size() - 1);
        QString style;
        if (isLast) {
            style = QStringLiteral(
                "QToolButton {"
                "  border: none;"
                "  padding: 2px 6px;"
                "  font-weight: bold;"
                "  font-size: 12px;"
                "  color: #333;"
                "  background: transparent;"
                "}"
            );
        } else {
            style = QStringLiteral(
                "QToolButton {"
                "  border: none;"
                "  padding: 2px 6px;"
                "  color: #0066CC;"
                "  font-size: 12px;"
                "  background: transparent;"
                "}"
                "QToolButton:hover {"
                "  background-color: rgba(0, 0, 0, 0.06);"
                "  border-radius: 4px;"
                "}"
            );
        }
        btn->setStyleSheet(style);

        connect(btn, &QToolButton::clicked, this, [this, fullPath]() {
            emit pathClicked(fullPath);
        });
        m_crumbLayout->addWidget(btn);
    }

    m_crumbLayout->addStretch();

    // Scroll to end
    QTimer::singleShot(0, this, [this]() {
        m_scrollArea->horizontalScrollBar()->setValue(
            m_scrollArea->horizontalScrollBar()->maximum());
    });
}
```

## src/sidebarwidget.h

```cpp
#pragma once

#include <QWidget>
#include <QTreeWidget>
#include <QVBoxLayout>

class SidebarWidget : public QWidget
{
    Q_OBJECT

public:
    explicit SidebarWidget(QWidget *parent = nullptr);

    void setCurrentPath(const QString &path);

signals:
    void locationClicked(const QString &path);

private:
    void buildSidebar();
    void addSection(const QString &title, const QList<QPair<QString, QString>> &items);

    QTreeWidget *m_tree;
};
```

## src/sidebarwidget.cpp

```cpp
#include "sidebarwidget.h"
#include <QStandardPaths>
#include <QDir>
#include <QStorageInfo>
#include <QFileIconProvider>
#include <QHeaderView>

SidebarWidget::SidebarWidget(QWidget *parent)
    : QWidget(parent)
{
    auto *layout = new QVBoxLayout(this);
    layout->setContentsMargins(0, 0, 0, 0);

    m_tree = new QTreeWidget(this);
    m_tree->setHeaderHidden(true);
    m_tree->setIndentation(16);
    m_tree->setRootIsDecorated(false);
    m_tree->setAnimated(true);
    m_tree->setExpandsOnDoubleClick(false);
    m_tree->setIconSize(QSize(18, 18));

    m_tree->setStyleSheet(
        "QTreeWidget {"
        "  background-color: #F5F5F7;"
        "  border: none;"
        "  font-size: 12px;"
        "}"
        "QTreeWidget::item {"
        "  padding: 4px 8px;"
        "  border-radius: 6px;"
        "  margin: 1px 6px;"
        "}"
        "QTreeWidget::item:selected {"
        "  background-color: rgba(0, 0, 0, 0.08);"
        "  color: #333;"
        "}"
        "QTreeWidget::item:hover {"
        "  background-color: rgba(0, 0, 0, 0.04);"
        "}"
        "QTreeWidget::branch {"
        "  background: transparent;"
        "}"
    );

    layout->addWidget(m_tree);
    buildSidebar();

    connect(m_tree, &QTreeWidget::itemClicked, this, [this](QTreeWidgetItem *item, int) {
        QString path = item->data(0, Qt::UserRole).toString();
        if (!path.isEmpty()) {
            emit locationClicked(path);
        }
    });

    setMinimumWidth(160);
    setMaximumWidth(250);
}

void SidebarWidget::buildSidebar()
{
    m_tree->clear();
    QFileIconProvider iconProvider;

    // Favorites section
    QList<QPair<QString, QString>> favorites;
    favorites << qMakePair(QStringLiteral("Desktop"),
                           QStandardPaths::writableLocation(QStandardPaths::DesktopLocation));
    favorites << qMakePair(QStringLiteral("Documents"),
                           QStandardPaths::writableLocation(QStandardPaths::DocumentsLocation));
    favorites << qMakePair(QStringLiteral("Downloads"),
                           QStandardPaths::writableLocation(QStandardPaths::DownloadLocation));
    favorites << qMakePair(QStringLiteral("Pictures"),
                           QStandardPaths::writableLocation(QStandardPaths::PicturesLocation));
    favorites << qMakePair(QStringLiteral("Music"),
                           QStandardPaths::writableLocation(QStandardPaths::MusicLocation));
    favorites << qMakePair(QStringLiteral("Videos"),
                           QStandardPaths::writableLocation(QStandardPaths::MoviesLocation));
    favorites << qMakePair(QStringLiteral("Home"),
                           QDir::homePath());

    addSection(QStringLiteral("Favorites"), favorites);

    // Locations (volumes)
    QList<QPair<QString, QString>> locations;
    const auto volumes = QStorageInfo::mountedVolumes();
    for (const QStorageInfo &vol : volumes) {
        if (vol.isValid() && vol.isReady()) {
            QString name = vol.displayName();
            if (name.isEmpty())
                name = vol.rootPath();
            // Filter to meaningful mounts
            QString root = vol.rootPath();
            if (root == "/" || root.startsWith("/media") || root.startsWith("/mnt")
                || root.startsWith("/run/media")) {
                locations << qMakePair(name, root);
            }
        }
    }
    if (!locations.isEmpty())
        addSection(QStringLiteral("Locations"), locations);
}

void SidebarWidget::addSection(const QString &title,
                                const QList<QPair<QString, QString>> &items)
{
    auto *sectionItem = new QTreeWidgetItem(m_tree);
    sectionItem->setText(0, title);
    sectionItem->setFlags(Qt::ItemIsEnabled);
    QFont sectionFont = sectionItem->font(0);
    sectionFont.setBold(true);
    sectionFont.setPointSize(sectionFont.pointSize() - 1);
    sectionItem->setFont(0, sectionFont);
    sectionItem->setForeground(0, QColor(128, 128, 128));
    sectionItem->setExpanded(true);

    QFileIconProvider iconProvider;

    for (const auto &pair : items) {
        QDir dir(pair.second);
        if (!dir.exists())
            continue;

        auto *child = new QTreeWidgetItem(sectionItem);
        child->setText(0, pair.first);
        child->setData(0, Qt::UserRole, pair.second);
        child->setIcon(0, iconProvider.icon(QFileInfo(pair.second)));
    }
}

void SidebarWidget::setCurrentPath(const QString &path)
{
    // Highlight matching sidebar item
    for (int i = 0; i < m_tree->topLevelItemCount(); ++i) {
        QTreeWidgetItem *section = m_tree->topLevelItem(i);
        for (int j = 0; j < section->childCount(); ++j) {
            QTreeWidgetItem *child = section->child(j);
            if (child->data(0, Qt::UserRole).toString() == path) {
                m_tree->setCurrentItem(child);
                return;
            }
        }
    }
    m_tree->clearSelection();
}
```

## src/filepreviewwidget.h

```cpp
#pragma once

#include <QWidget>
#include <QLabel>
#include <QVBoxLayout>
#include <QFileInfo>
#include "thumbnailprovider.h"

class FilePreviewWidget : public QWidget
{
    Q_OBJECT

public:
    explicit FilePreviewWidget(ThumbnailProvider *thumbProvider,
                                QWidget *parent = nullptr);

    void setFile(const QString &filePath);
    void clear();

private slots:
    void onThumbnailReady(const QString &filePath, const QPixmap &thumbnail);

private:
    void updateFileInfo(const QFileInfo &info);
    QString formatSize(qint64 bytes) const;

    ThumbnailProvider *m_thumbProvider;
    QLabel *m_previewLabel;
    QLabel *m_fileNameLabel;
    QLabel *m_fileInfoLabel;
    QLabel *m_fileSizeLabel;
    QLabel *m_fileDateLabel;
    QLabel *m_fileTypeLabel;
    QLabel *m_fileDimensionsLabel;
    QString m_currentFile;
};
```

## src/filepreviewwidget.cpp

```cpp
#include "filepreviewwidget.h"
#include <QFileIconProvider>
#include <QMimeDatabase>
#include <QImageReader>
#include <QScrollArea>

FilePreviewWidget::FilePreviewWidget(ThumbnailProvider *thumbProvider,
                                       QWidget *parent)
    : QWidget(parent)
    , m_thumbProvider(thumbProvider)
{
    auto *mainLayout = new QVBoxLayout(this);
    mainLayout->setContentsMargins(12, 12, 12, 12);
    mainLayout->setSpacing(8);

    // Preview image
    m_previewLabel = new QLabel;
    m_previewLabel->setFixedSize(200, 200);
    m_previewLabel->setAlignment(Qt::AlignCenter);
    m_previewLabel->setStyleSheet(
        "QLabel {"
        "  background-color: rgba(0, 0, 0, 0.02);"
        "  border-radius: 8px;"
        "  border: 1px solid rgba(0, 0, 0, 0.06);"
        "}"
    );
    mainLayout->addWidget(m_previewLabel, 0, Qt::AlignHCenter);

    // File name
    m_fileNameLabel = new QLabel;
    m_fileNameLabel->setAlignment(Qt::AlignCenter);
    m_fileNameLabel->setWordWrap(true);
    m_fileNameLabel->setStyleSheet(
        "QLabel { font-weight: bold; font-size: 13px; color: #333; }"
    );
    mainLayout->addWidget(m_fileNameLabel);

    // Separator
    auto *sep = new QWidget;
    sep->setFixedHeight(1);
    sep->setStyleSheet("background-color: rgba(0, 0, 0, 0.1);");
    mainLayout->addWidget(sep);

    // Info labels
    auto addInfoRow = [&](const QString &, QLabel *&valueLabel) {
        auto *row = new QWidget;
        auto *rowLayout = new QVBoxLayout(row);
        rowLayout->setContentsMargins(0, 2, 0, 2);
        rowLayout->setSpacing(0);

        valueLabel = new QLabel;
        valueLabel->setStyleSheet("QLabel { font-size: 11px; color: #666; }");
        valueLabel->setWordWrap(true);
        rowLayout->addWidget(valueLabel);
        mainLayout->addWidget(row);
    };

    addInfoRow("Type", m_fileTypeLabel);
    addInfoRow("Size", m_fileSizeLabel);
    addInfoRow("Modified", m_fileDateLabel);
    addInfoRow("Dimensions", m_fileDimensionsLabel);
    addInfoRow("Info", m_fileInfoLabel);

    mainLayout->addStretch();

    setFixedWidth(230);
    setStyleSheet(
        "FilePreviewWidget {"
        "  background-color: #FAFAFA;"
        "  border-left: 1px solid #E0E0E0;"
        "}"
    );

    if (m_thumbProvider) {
        connect(m_thumbProvider, &ThumbnailProvider::thumbnailReady,
                this, &FilePreviewWidget::onThumbnailReady);
    }

    clear();
}

void FilePreviewWidget::setFile(const QString &filePath)
{
    m_currentFile = filePath;
    QFileInfo info(filePath);

    if (!info.exists()) {
        clear();
        return;
    }

    m_fileNameLabel->setText(info.fileName());
    updateFileInfo(info);

    // Request thumbnail
    QSize previewSize(180, 180);
    if (m_thumbProvider && ThumbnailProvider::isImageFile(filePath)) {
        QPixmap cached = m_thumbProvider->getCachedThumbnail(filePath, previewSize);
        if (!cached.isNull()) {
            m_previewLabel->setPixmap(cached.scaled(180, 180, Qt::KeepAspectRatio,
                                                     Qt::SmoothTransformation));
        } else {
            // Show generic icon while loading
            QFileIconProvider iconProvider;
            m_previewLabel->setPixmap(iconProvider.icon(info).pixmap(64, 64));
            m_thumbProvider->requestThumbnail(filePath, previewSize);
        }

        // Dimensions
        QImageReader reader(filePath);
        QSize imgSize = reader.size();
        if (imgSize.isValid()) {
            m_fileDimensionsLabel->setText(
                QStringLiteral("Dimensions: %1 × %2").arg(imgSize.width()).arg(imgSize.height()));
            m_fileDimensionsLabel->show();
        } else {
            m_fileDimensionsLabel->hide();
        }
    } else {
        QFileIconProvider iconProvider;
        m_previewLabel->setPixmap(iconProvider.icon(info).pixmap(64, 64));
        m_fileDimensionsLabel->hide();
    }
}

void FilePreviewWidget::clear()
{
    m_currentFile.clear();
    m_previewLabel->clear();
    m_previewLabel->setText("No Selection");
    m_fileNameLabel->clear();
    m_fileTypeLabel->clear();
    m_fileSizeLabel->clear();
    m_fileDateLabel->clear();
    m_fileDimensionsLabel->clear();
    m_fileInfoLabel->clear();
}

void FilePreviewWidget::onThumbnailReady(const QString &filePath, const QPixmap &thumbnail)
{
    if (filePath == m_currentFile && !thumbnail.isNull()) {
        m_previewLabel->setPixmap(thumbnail.scaled(180, 180, Qt::KeepAspectRatio,
                                                    Qt::SmoothTransformation));
    }
}

void FilePreviewWidget::updateFileInfo(const QFileInfo &info)
{
    // Type
    QMimeDatabase mimeDb;
    QMimeType mime = mimeDb.mimeTypeForFile(info);
    m_fileTypeLabel->setText(QStringLiteral("Type: %1").arg(mime.comment()));

    // Size
    if (info.isDir()) {
        m_fileSizeLabel->setText(QStringLiteral("Size: —"));
    } else {
        m_fileSizeLabel->setText(QStringLiteral("Size: %1").arg(formatSize(info.size())));
    }

    // Date
    m_fileDateLabel->setText(
        QStringLiteral("Modified: %1").arg(info.lastModified().toString("MMM d, yyyy h:mm AP")));

    // Permissions
    QString perms;
    perms += info.isReadable() ? "R" : "-";
    perms += info.isWritable() ? "W" : "-";
    perms += info.isExecutable() ? "X" : "-";
    m_fileInfoLabel->setText(QStringLiteral("Permissions: %1\nOwner: %2")
                             .arg(perms, info.owner()));
}

QString FilePreviewWidget::formatSize(qint64 bytes) const
{
    if (bytes < 1024)
        return QStringLiteral("%1 bytes").arg(bytes);
    if (bytes < 1024 * 1024)
        return QStringLiteral("%1 KB").arg(bytes / 1024.0, 0, 'f', 1);
    if (bytes < 1024LL * 1024 * 1024)
        return QStringLiteral("%1 MB").arg(bytes / (1024.0 * 1024.0), 0, 'f', 1);
    return QStringLiteral("%1 GB").arg(bytes / (1024.0 * 1024.0 * 1024.0), 0, 'f', 2);
}
```

## src/filesystemmodel.h

```cpp
#pragma once

#include <QFileSystemModel>

// Extended QFileSystemModel with additional computed data columns
class FileSystemModelEx : public QFileSystemModel
{
    Q_OBJECT

public:
    enum ExtraColumns {
        ColumnName = 0,
        ColumnSize = 1,
        ColumnType = 2,
        ColumnDate = 3
    };

    explicit FileSystemModelEx(QObject *parent = nullptr);

    QVariant data(const QModelIndex &index, int role = Qt::DisplayRole) const override;
    int columnCount(const QModelIndex &parent = QModelIndex()) const override;
    QVariant headerData(int section, Qt::Orientation orientation,
                        int role = Qt::DisplayRole) const override;
};
```

## src/filesystemmodel.cpp

```cpp
#include "filesystemmodel.h"
#include <QFileInfo>
#include <QMimeDatabase>
#include <QDateTime>

FileSystemModelEx::FileSystemModelEx(QObject *parent)
    : QFileSystemModel(parent)
{
    setReadOnly(true);
    setOption(QFileSystemModel::DontWatchForChanges, false);
}

int FileSystemModelEx::columnCount(const QModelIndex &parent) const
{
    Q_UNUSED(parent)
    return 4; // Name, Size, Type, Date
}

QVariant FileSystemModelEx::headerData(int section, Qt::Orientation orientation,
                                        int role) const
{
    if (orientation == Qt::Horizontal && role == Qt::DisplayRole) {
        switch (section) {
        case ColumnName: return QStringLiteral("Name");
        case ColumnSize: return QStringLiteral("Size");
        case ColumnType: return QStringLiteral("Kind");
        case ColumnDate: return QStringLiteral("Date Modified");
        }
    }
    return QFileSystemModel::headerData(section, orientation, role);
}

QVariant FileSystemModelEx::data(const QModelIndex &index, int role) const
{
    if (!index.isValid())
        return QVariant();

    if (role == Qt::DisplayRole) {
        QFileInfo info = fileInfo(index);

        switch (index.column()) {
        case ColumnName:
            return info.fileName();

        case ColumnSize:
            if (info.isDir())
                return QStringLiteral("--");
            {
                qint64 sz = info.size();
                if (sz < 1024)
                    return QStringLiteral("%1 B").arg(sz);
                if (sz < 1024 * 1024)
                    return QStringLiteral("%1 KB").arg(sz / 1024.0, 0, 'f', 1);
                if (sz < 1024LL * 1024 * 1024)
                    return QStringLiteral("%1 MB").arg(sz / (1024.0 * 1024.0), 0, 'f', 1);
                return QStringLiteral("%1 GB").arg(sz / (1024.0 * 1024.0 * 1024.0), 0, 'f', 2);
            }

        case ColumnType: {
            QMimeDatabase mimeDb;
            QMimeType mime = mimeDb.mimeTypeForFile(info);
            return mime.comment();
        }

        case ColumnDate:
            return info.lastModified().toString("MMM d, yyyy, h:mm AP");
        }
    }

    if (role == Qt::TextAlignmentRole) {
        if (index.column() == ColumnSize)
            return QVariant(Qt::AlignRight | Qt::AlignVCenter);
    }

    // For column 0, delegate to base
    if (index.column() == 0)
        return QFileSystemModel::data(index, role);

    return QVariant();
}
```

## src/customfiledialog.h

```cpp
#pragma once

#include <QDialog>
#include <QTreeView>
#include <QListView>
#include <QLineEdit>
#include <QComboBox>
#include <QPushButton>
#include <QToolButton>
#include <QSplitter>
#include <QStackedWidget>
#include <QLabel>
#include <QTimer>
#include <QShortcut>

#include "dialogresult.h"
#include "filesystemmodel.h"
#include "sortfilterproxymodel.h"
#include "thumbnailprovider.h"
#include "fileitemdelegate.h"
#include "pathbreadcrumb.h"
#include "sidebarwidget.h"
#include "filepreviewwidget.h"

class CustomFileDialog : public QDialog
{
    Q_OBJECT

public:
    explicit CustomFileDialog(QWidget *parent = nullptr);
    ~CustomFileDialog();

    // Configuration
    void setMode(FileDialogMode mode);
    FileDialogMode mode() const { return m_mode; }

    void setDirectory(const QString &path);
    QString directory() const;

    void setNameFilters(const QStringList &filters);
    QStringList nameFilters() const;

    void setDefaultSuffix(const QString &suffix);
    QString defaultSuffix() const { return m_defaultSuffix; }

    void setWindowTitle(const QString &title);

    void setAcceptButtonText(const QString &text);
    void setRejectButtonText(const QString &text);

    void setSaveFileName(const QString &name);

    // Results
    QString selectedFile() const;
    QStringList selectedFiles() const;
    FileDialogResult result() const;

    // Show non-blocking
    void openDialog();

    // Static helpers (non-blocking pattern using callbacks)
    static void getOpenFileName(QWidget *parent,
                                const QString &caption,
                                const QString &dir,
                                const QString &filter,
                                std::function<void(const QString &)> callback);

    static void getOpenFileNames(QWidget *parent,
                                 const QString &caption,
                                 const QString &dir,
                                 const QString &filter,
                                 std::function<void(const QStringList &)> callback);

    static void getExistingDirectory(QWidget *parent,
                                     const QString &caption,
                                     const QString &dir,
                                     std::function<void(const QString &)> callback);

    static void getSaveFileName(QWidget *parent,
                                const QString &caption,
                                const QString &dir,
                                const QString &filter,
                                std::function<void(const QString &)> callback);

signals:
    void fileSelected(const QString &path);
    void filesSelected(const QStringList &paths);
    void directorySelected(const QString &path);
    void dialogFinished(const FileDialogResult &result);
    void currentDirectoryChanged(const QString &path);
    void filterChanged(const QString &filter);

protected:
    void keyPressEvent(QKeyEvent *event) override;
    void showEvent(QShowEvent *event) override;

private slots:
    void onItemActivated(const QModelIndex &index);
    void onSelectionChanged();
    void onBreadcrumbClicked(const QString &path);
    void onSidebarClicked(const QString &path);
    void onSortCriteriaChanged(int index);
    void onFilterChanged(int index);
    void onSearchTextChanged(const QString &text);
    void onAccept();
    void onReject();
    void navigateUp();
    void navigateBack();
    void navigateForward();
    void toggleHiddenFiles();
    void togglePreview();
    void setViewMode(FileDialogViewMode viewMode);
    void onThumbnailReady(const QString &filePath, const QPixmap &pixmap);

private:
    void setupUI();
    void setupToolbar();
    void setupCentralArea();
    void setupBottomBar();
    void setupConnections();
    void setupShortcuts();
    void applyMacOSStyle();

    void navigateTo(const QString &path);
    void updateNavigationButtons();
    QStringList getSelectedPaths() const;

    // Mode
    FileDialogMode m_mode = FileDialogMode::OpenFile;
    FileDialogViewMode m_viewMode = FileDialogViewMode::List;
    QString m_defaultSuffix;

    // Models
    FileSystemModelEx *m_fsModel;
    SortFilterProxyModel *m_proxyModel;
    ThumbnailProvider *m_thumbProvider;

    // Navigation
    QStringList m_history;
    int m_historyIndex = -1;
    bool m_navigating = false;

    // UI Components
    QSplitter *m_mainSplitter;
    SidebarWidget *m_sidebar;
    QStackedWidget *m_viewStack;
    QTreeView *m_treeView;
    QListView *m_iconView;
    FilePreviewWidget *m_preview;
    PathBreadcrumb *m_breadcrumb;

    // Toolbar
    QToolButton *m_backBtn;
    QToolButton *m_forwardBtn;
    QToolButton *m_upBtn;
    QToolButton *m_listViewBtn;
    QToolButton *m_iconViewBtn;
    QToolButton *m_previewBtn;
    QLineEdit *m_searchEdit;
    QComboBox *m_sortCombo;

    // Bottom bar
    QLineEdit *m_fileNameEdit;
    QComboBox *m_filterCombo;
    QPushButton *m_acceptBtn;
    QPushButton *m_cancelBtn;
    QLabel *m_fileNameLabel;
    QToolButton *m_hiddenBtn;
    QToolButton *m_newFolderBtn;

    // Delegates
    FileItemDelegate *m_listDelegate;
    FileItemDelegate *m_iconDelegate;

    // Result
    FileDialogResult m_result;

    // Search debounce
    QTimer *m_searchTimer;
};
```

## src/customfiledialog.cpp

```cpp
#include "customfiledialog.h"

#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QKeyEvent>
#include <QShowEvent>
#include <QApplication>
#include <QScreen>
#include <QDir>
#include <QFileInfo>
#include <QMessageBox>
#include <QScrollBar>
#include <QMenu>
#include <QAction>
#include <QInputDialog>

CustomFileDialog::CustomFileDialog(QWidget *parent)
    : QDialog(parent)
{
    m_fsModel = new FileSystemModelEx(this);
    m_proxyModel = new SortFilterProxyModel(this);
    m_thumbProvider = new ThumbnailProvider(this);
    m_searchTimer = new QTimer(this);
    m_searchTimer->setSingleShot(true);
    m_searchTimer->setInterval(300);

    m_proxyModel->setSourceModel(m_fsModel);
    m_proxyModel->setSortCriteria(SortFilterProxyModel::SortByName);

    m_fsModel->setRootPath(QDir::homePath());
    m_fsModel->setFilter(QDir::AllEntries | QDir::NoDotAndDotDot | QDir::AllDirs);

    setupUI();
    setupConnections();
    setupShortcuts();
    applyMacOSStyle();

    navigateTo(QDir::homePath());

    resize(960, 600);

    QDialog::setWindowTitle(QStringLiteral("Open"));
}

CustomFileDialog::~CustomFileDialog()
{
    m_thumbProvider->cancelAll();
}

void CustomFileDialog::setupUI()
{
    auto *mainLayout = new QVBoxLayout(this);
    mainLayout->setContentsMargins(0, 0, 0, 0);
    mainLayout->setSpacing(0);

    setupToolbar();

    // Breadcrumb
    m_breadcrumb = new PathBreadcrumb(this);
    mainLayout->addWidget(m_breadcrumb);

    setupCentralArea();
    mainLayout->addWidget(m_mainSplitter, 1);

    setupBottomBar();
}

void CustomFileDialog::setupToolbar()
{
    auto *toolbar = new QWidget(this);
    toolbar->setFixedHeight(42);
    toolbar->setStyleSheet(
        "QWidget {"
        "  background-color: #F0F0F2;"
        "  border-bottom: 1px solid #D5D5D7;"
        "}"
    );

    auto *tbLayout = new QHBoxLayout(toolbar);
    tbLayout->setContentsMargins(8, 4, 8, 4);
    tbLayout->setSpacing(4);

    auto makeToolBtn = [](const QString &text, const QString &tooltip) {
        auto *btn = new QToolButton;
        btn->setText(text);
        btn->setToolTip(tooltip);
        btn->setFixedSize(28, 28);
        btn->setStyleSheet(
            "QToolButton {"
            "  border: none;"
            "  border-radius: 6px;"
            "  background: transparent;"
            "  font-size: 16px;"
            "  color: #555;"
            "}"
            "QToolButton:hover {"
            "  background-color: rgba(0, 0, 0, 0.08);"
            "}"
            "QToolButton:pressed {"
            "  background-color: rgba(0, 0, 0, 0.12);"
            "}"
            "QToolButton:disabled {"
            "  color: #CCC;"
            "}"
        );
        return btn;
    };

    m_backBtn = makeToolBtn(QStringLiteral("◀"), QStringLiteral("Back"));
    m_forwardBtn = makeToolBtn(QStringLiteral("▶"), QStringLiteral("Forward"));
    m_upBtn = makeToolBtn(QStringLiteral("▲"), QStringLiteral("Go Up"));
    m_backBtn->setEnabled(false);
    m_forwardBtn->setEnabled(false);

    tbLayout->addWidget(m_backBtn);
    tbLayout->addWidget(m_forwardBtn);
    tbLayout->addWidget(m_upBtn);
    tbLayout->addSpacing(8);

    // View mode buttons
    m_listViewBtn = makeToolBtn(QStringLiteral("≡"), QStringLiteral("List View"));
    m_iconViewBtn = makeToolBtn(QStringLiteral("⊞"), QStringLiteral("Icon View"));
    m_listViewBtn->setCheckable(true);
    m_iconViewBtn->setCheckable(true);
    m_listViewBtn->setChecked(true);

    tbLayout->addWidget(m_listViewBtn);
    tbLayout->addWidget(m_iconViewBtn);
    tbLayout->addSpacing(4);

    m_previewBtn = makeToolBtn(QStringLiteral("◫"), QStringLiteral("Toggle Preview"));
    m_previewBtn->setCheckable(true);
    m_previewBtn->setChecked(true);
    tbLayout->addWidget(m_previewBtn);

    tbLayout->addStretch();

    // Sort combo
    m_sortCombo = new QComboBox;
    m_sortCombo->addItem(QStringLiteral("Name"), SortFilterProxyModel::SortByName);
    m_sortCombo->addItem(QStringLiteral("Date Modified"), SortFilterProxyModel::SortByDate);
    m_sortCombo->addItem(QStringLiteral("Size"), SortFilterProxyModel::SortBySize);
    m_sortCombo->addItem(QStringLiteral("Kind"), SortFilterProxyModel::SortByType);
    m_sortCombo->addItem(QStringLiteral("Extension"), SortFilterProxyModel::SortByExtension);
    m_sortCombo->setFixedWidth(130);
    m_sortCombo->setStyleSheet(
        "QComboBox {"
        "  border: 1px solid #D0D0D2;"
        "  border-radius: 6px;"
        "  padding: 2px 8px;"
        "  background-color: white;"
        "  font-size: 12px;"
        "  min-height: 22px;"
        "}"
        "QComboBox:hover {"
        "  border-color: #B0B0B2;"
        "}"
        "QComboBox::drop-down {"
        "  border: none;"
        "  width: 20px;"
        "}"
        "QComboBox::down-arrow {"
        "  image: none;"
        "  border: none;"
        "}"
    );
    tbLayout->addWidget(new QLabel("Sort:"));
    tbLayout->addWidget(m_sortCombo);
    tbLayout->addSpacing(8);

    // Search bar
    m_searchEdit = new QLineEdit;
    m_searchEdit->setPlaceholderText(QStringLiteral("Search"));
    m_searchEdit->setClearButtonEnabled(true);
    m_searchEdit->setFixedWidth(180);
    m_searchEdit->setStyleSheet(
        "QLineEdit {"
        "  border: 1px solid #D0D0D2;"
        "  border-radius: 6px;"
        "  padding: 3px 8px;"
        "  background-color: white;"
        "  font-size: 12px;"
        "}"
        "QLineEdit:focus {"
        "  border-color: #0066CC;"
        "}"
    );
    tbLayout->addWidget(m_searchEdit);

    static_cast<QVBoxLayout *>(layout())->addWidget(toolbar);
}

void CustomFileDialog::setupCentralArea()
{
    m_mainSplitter = new QSplitter(Qt::Horizontal, this);
    m_mainSplitter->setChildrenCollapsible(false);

    // Sidebar
    m_sidebar = new SidebarWidget;
    m_mainSplitter->addWidget(m_sidebar);

    // File view area
    auto *viewContainer = new QWidget;
    auto *viewLayout = new QVBoxLayout(viewContainer);
    viewLayout->setContentsMargins(0, 0, 0, 0);
    viewLayout->setSpacing(0);

    m_viewStack = new QStackedWidget;

    // Tree view (list mode)
    m_treeView = new QTreeView;
    m_treeView->setModel(m_proxyModel);
    m_treeView->setRootIsDecorated(false);
    m_treeView->setAlternatingRowColors(false);
    m_treeView->setSortingEnabled(true);
    m_treeView->setSelectionMode(QAbstractItemView::SingleSelection);
    m_treeView->setSelectionBehavior(QAbstractItemView::SelectRows);
    m_treeView->setDragEnabled(false);
    m_treeView->setAnimated(true);
    m_treeView->setIndentation(0);
    m_treeView->setUniformRowHeights(false);
    m_treeView->setItemsExpandable(false);

    // Header setup
    auto *header = m_treeView->header();
    header->setStretchLastSection(true);
    header->setSectionResizeMode(0, QHeaderView::Stretch);
    header->setSectionResizeMode(1, QHeaderView::Fixed);
    header->setSectionResizeMode(2, QHeaderView::Fixed);
    header->setSectionResizeMode(3, QHeaderView::Fixed);
    header->resizeSection(1, 90);
    header->resizeSection(2, 150);
    header->resizeSection(3, 180);
    header->setDefaultAlignment(Qt::AlignLeft | Qt::AlignVCenter);

    m_treeView->setStyleSheet(
        "QTreeView {"
        "  border: none;"
        "  background-color: white;"
        "  font-size: 13px;"
        "  outline: none;"
        "}"
        "QTreeView::item {"
        "  padding: 4px 0;"
        "  border: none;"
        "}"
        "QTreeView::item:selected {"
        "  background: transparent;"
        "}"
        "QTreeView::item:hover {"
        "  background: transparent;"
        "}"
        "QHeaderView::section {"
        "  background-color: #FAFAFA;"
        "  border: none;"
        "  border-bottom: 1px solid #E5E5E7;"
        "  border-right: 1px solid #EEE;"
        "  padding: 6px 10px;"
        "  font-size: 11px;"
        "  font-weight: 600;"
        "  color: #888;"
        "}"
    );

    m_listDelegate = new FileItemDelegate(m_thumbProvider, this);
    m_listDelegate->setViewStyle(FileItemDelegate::ListView);
    m_listDelegate->setIconSize(QSize(32, 32));
    m_treeView->setItemDelegateForColumn(0, m_listDelegate);

    m_viewStack->addWidget(m_treeView);

    // Icon view
    m_iconView = new QListView;
    m_iconView->setModel(m_proxyModel);
    m_iconView->setViewMode(QListView::IconMode);
    m_iconView->setResizeMode(QListView::Adjust);
    m_iconView->setWrapping(true);
    m_iconView->setSpacing(8);
    m_iconView->setUniformItemSizes(true);
    m_iconView->setSelectionMode(QAbstractItemView::SingleSelection);
    m_iconView->setMovement(QListView::Static);
    m_iconView->setGridSize(QSize(110, 120));
    m_iconView->setIconSize(QSize(64, 64));

    m_iconView->setStyleSheet(
        "QListView {"
        "  border: none;"
        "  background-color: white;"
        "  outline: none;"
        "}"
        "QListView::item {"
        "  border: none;"
        "}"
        "QListView::item:selected {"
        "  background: transparent;"
        "}"
    );

    m_iconDelegate = new FileItemDelegate(m_thumbProvider, this);
    m_iconDelegate->setViewStyle(FileItemDelegate::IconView);
    m_iconDelegate->setIconSize(QSize(64, 64));
    m_iconView->setItemDelegate(m_iconDelegate);

    m_viewStack->addWidget(m_iconView);

    viewLayout->addWidget(m_viewStack);
    m_mainSplitter->addWidget(viewContainer);

    // Preview panel
    m_preview = new FilePreviewWidget(m_thumbProvider);
    m_mainSplitter->addWidget(m_preview);

    m_mainSplitter->setStretchFactor(0, 0);
    m_mainSplitter->setStretchFactor(1, 1);
    m_mainSplitter->setStretchFactor(2, 0);
    m_mainSplitter->setSizes({200, 530, 230});

    m_mainSplitter->setStyleSheet(
        "QSplitter::handle {"
        "  background-color: #E0E0E2;"
        "  width: 1px;"
        "}"
    );
}

void CustomFileDialog::setupBottomBar()
{
    auto *bottomBar = new QWidget(this);
    bottomBar->setFixedHeight(70);
    bottomBar->setStyleSheet(
        "QWidget {"
        "  background-color: #F0F0F2;"
        "  border-top: 1px solid #D5D5D7;"
        "}"
    );

    auto *bottomLayout = new QVBoxLayout(bottomBar);
    bottomLayout->setContentsMargins(12, 8, 12, 8);
    bottomLayout->setSpacing(6);

    // File name row
    auto *nameRow = new QHBoxLayout;
    nameRow->setSpacing(8);

    m_fileNameLabel = new QLabel(QStringLiteral("File name:"));
    m_fileNameLabel->setStyleSheet("QLabel { font-size: 12px; color: #555; border: none; }");
    nameRow->addWidget(m_fileNameLabel);

    m_fileNameEdit = new QLineEdit;
    m_fileNameEdit->setStyleSheet(
        "QLineEdit {"
        "  border: 1px solid #D0D0D2;"
        "  border-radius: 6px;"
        "  padding: 4px 8px;"
        "  background-color: white;"
        "  font-size: 13px;"
        "}"
        "QLineEdit:focus {"
        "  border-color: #0066CC;"
        "}"
    );
    nameRow->addWidget(m_fileNameEdit, 1);

    m_hiddenBtn = new QToolButton;
    m_hiddenBtn->setText(QStringLiteral("⚙"));
    m_hiddenBtn->setToolTip(QStringLiteral("Toggle Hidden Files"));
    m_hiddenBtn->setCheckable(true);
    m_hiddenBtn->setFixedSize(26, 26);
    m_hiddenBtn->setStyleSheet(
        "QToolButton {"
        "  border: 1px solid #D0D0D2;"
        "  border-radius: 6px;"
        "  background: white;"
        "  font-size: 14px;"
        "}"
        "QToolButton:checked {"
        "  background: #E0E0E2;"
        "}"
        "QToolButton:hover {"
        "  background: #F0F0F2;"
        "}"
    );
    nameRow->addWidget(m_hiddenBtn);

    m_newFolderBtn = new QToolButton;
    m_newFolderBtn->setText(QStringLiteral("📁+"));
    m_newFolderBtn->setToolTip(QStringLiteral("New Folder"));
    m_newFolderBtn->setFixedSize(36, 26);
    m_newFolderBtn->setStyleSheet(
        "QToolButton {"
        "  border: 1px solid #D0D0D2;"
        "  border-radius: 6px;"
        "  background: white;"
        "  font-size: 12px;"
        "}"
        "QToolButton:hover {"
        "  background: #F0F0F2;"
        "}"
    );
    nameRow->addWidget(m_newFolderBtn);

    bottomLayout->addLayout(nameRow);

    // Button row
    auto *btnRow = new QHBoxLayout;
    btnRow->setSpacing(8);

    m_filterCombo = new QComboBox;
    m_filterCombo->setMinimumWidth(200);
    m_filterCombo->setStyleSheet(
        "QComboBox {"
        "  border: 1px solid #D0D0D2;"
        "  border-radius: 6px;"
        "  padding: 3px 8px;"
        "  background-color: white;"
        "  font-size: 12px;"
        "  min-height: 24px;"
        "}"
        "QComboBox::drop-down {"
        "  border: none;"
        "  width: 20px;"
        "}"
    );
    btnRow->addWidget(m_filterCombo);

    btnRow->addStretch();

    m_cancelBtn = new QPushButton(QStringLiteral("Cancel"));
    m_cancelBtn->setFixedSize(90, 30);
    m_cancelBtn->setStyleSheet(
        "QPushButton {"
        "  border: 1px solid #D0D0D2;"
        "  border-radius: 6px;"
        "  background-color: white;"
        "  font-size: 13px;"
        "  color: #333;"
        "}"
        "QPushButton:hover {"
        "  background-color: #F5F5F5;"
        "}"
        "QPushButton:pressed {"
        "  background-color: #E8E8E8;"
        "}"
    );
    btnRow->addWidget(m_cancelBtn);

    m_acceptBtn = new QPushButton(QStringLiteral("Open"));
    m_acceptBtn->setFixedSize(90, 30);
    m_acceptBtn->setDefault(true);
    m_acceptBtn->setStyleSheet(
        "QPushButton {"
        "  border: none;"
        "  border-radius: 6px;"
        "  background-color: #0066CC;"
        "  font-size: 13px;"
        "  color: white;"
        "  font-weight: bold;"
        "}"
        "QPushButton:hover {"
        "  background-color: #0055AA;"
        "}"
        "QPushButton:pressed {"
        "  background-color: #004488;"
        "}"
        "QPushButton:disabled {"
        "  background-color: #99C2E8;"
        "}"
    );
    btnRow->addWidget(m_acceptBtn);

    bottomLayout->addLayout(btnRow);

    static_cast<QVBoxLayout *>(layout())->addWidget(bottomBar);
}

void CustomFileDialog::setupConnections()
{
    // Tree view
    connect(m_treeView, &QTreeView::activated, this, &CustomFileDialog::onItemActivated);
    connect(m_treeView->selectionModel(), &QItemSelectionModel::selectionChanged,
            this, &CustomFileDialog::onSelectionChanged);

    // Icon view
    connect(m_iconView, &QListView::activated, this, &CustomFileDialog::onItemActivated);
    connect(m_iconView->selectionModel(), &QItemSelectionModel::selectionChanged,
            this, &CustomFileDialog::onSelectionChanged);

    // Navigation
    connect(m_backBtn, &QToolButton::clicked, this, &CustomFileDialog::navigateBack);
    connect(m_forwardBtn, &QToolButton::clicked, this, &CustomFileDialog::navigateForward);
    connect(m_upBtn, &QToolButton::clicked, this, &CustomFileDialog::navigateUp);

    // Breadcrumb
    connect(m_breadcrumb, &PathBreadcrumb::pathClicked,
            this, &CustomFileDialog::onBreadcrumbClicked);

    // Sidebar
    connect(m_sidebar, &SidebarWidget::locationClicked,
            this, &CustomFileDialog::onSidebarClicked);

    // View mode
    connect(m_listViewBtn, &QToolButton::clicked, this, [this]() {
        setViewMode(FileDialogViewMode::List);
    });
    connect(m_iconViewBtn, &QToolButton::clicked, this, [this]() {
        setViewMode(FileDialogViewMode::Icons);
    });

    // Preview
    connect(m_previewBtn, &QToolButton::toggled, this, &CustomFileDialog::togglePreview);

    // Sort
    connect(m_sortCombo, QOverload<int>::of(&QComboBox::currentIndexChanged),
            this, &CustomFileDialog::onSortCriteriaChanged);

    // Filter
    connect(m_filterCombo, QOverload<int>::of(&QComboBox::currentIndexChanged),
            this, &CustomFileDialog::onFilterChanged);

    // Search
    connect(m_searchEdit, &QLineEdit::textChanged, this, [this](const QString &text) {
        m_searchTimer->start();
        Q_UNUSED(text)
    });
    connect(m_searchTimer, &QTimer::timeout, this, [this]() {
        onSearchTextChanged(m_searchEdit->text());
    });

    // Buttons
    connect(m_acceptBtn, &QPushButton::clicked, this, &CustomFileDialog::onAccept);
    connect(m_cancelBtn, &QPushButton::clicked, this, &CustomFileDialog::onReject);
    connect(m_hiddenBtn, &QToolButton::toggled, this, &CustomFileDialog::toggleHiddenFiles);

    // New folder
    connect(m_newFolderBtn, &QToolButton::clicked, this, [this]() {
        bool ok;
        QString name = QInputDialog::getText(this, QStringLiteral("New Folder"),
                                              QStringLiteral("Folder name:"),
                                              QLineEdit::Normal,
                                              QStringLiteral("New Folder"), &ok);
        if (ok && !name.isEmpty()) {
            QDir dir(directory());
            if (dir.mkdir(name)) {
                // Navigate into it
                navigateTo(dir.absoluteFilePath(name));
            } else {
                QMessageBox::warning(this, QStringLiteral("Error"),
                                     QStringLiteral("Could not create folder."));
            }
        }
    });

    // Thumbnails
    connect(m_thumbProvider, &ThumbnailProvider::thumbnailReady,
            this, &CustomFileDialog::onThumbnailReady);

    // File name edit (for save mode)
    connect(m_fileNameEdit, &QLineEdit::returnPressed, this, &CustomFileDialog::onAccept);
}

void CustomFileDialog::setupShortcuts()
{
    auto *escShortcut = new QShortcut(QKeySequence(Qt::Key_Escape), this);
    connect(escShortcut, &QShortcut::activated, this, &CustomFileDialog::onReject);

    auto *backShortcut = new QShortcut(QKeySequence(Qt::ALT | Qt::Key_Left), this);
    connect(backShortcut, &QShortcut::activated, this, &CustomFileDialog::navigateBack);

    auto *fwdShortcut = new QShortcut(QKeySequence(Qt::ALT | Qt::Key_Right), this);
    connect(fwdShortcut, &QShortcut::activated, this, &CustomFileDialog::navigateForward);

    auto *upShortcut = new QShortcut(QKeySequence(Qt::ALT | Qt::Key_Up), this);
    connect(upShortcut, &QShortcut::activated, this, &CustomFileDialog::navigateUp);

    auto *hiddenShortcut = new QShortcut(QKeySequence(Qt::CTRL | Qt::SHIFT | Qt::Key_H), this);
    connect(hiddenShortcut, &QShortcut::activated, this, [this]() {
        m_hiddenBtn->toggle();
    });

    auto *searchShortcut = new QShortcut(QKeySequence(Qt::CTRL | Qt::Key_F), this);
    connect(searchShortcut, &QShortcut::activated, this, [this]() {
        m_searchEdit->setFocus();
        m_searchEdit->selectAll();
    });
}

void CustomFileDialog::applyMacOSStyle()
{
    setStyleSheet(
        "CustomFileDialog {"
        "  background-color: #FFFFFF;"
        "}"
    );
}

void CustomFileDialog::setMode(FileDialogMode mode)
{
    m_mode = mode;

    switch (mode) {
    case FileDialogMode::OpenFile:
        m_acceptBtn->setText(QStringLiteral("Open"));
        m_fileNameLabel->setText(QStringLiteral("File name:"));
        m_fileNameEdit->setReadOnly(true);
        m_fileNameEdit->setPlaceholderText(QStringLiteral("Select a file"));
        m_treeView->setSelectionMode(QAbstractItemView::SingleSelection);
        m_iconView->setSelectionMode(QAbstractItemView::SingleSelection);
        m_newFolderBtn->hide();
        break;

    case FileDialogMode::OpenFiles:
        m_acceptBtn->setText(QStringLiteral("Open"));
        m_fileNameLabel->setText(QStringLiteral("File names:"));
        m_fileNameEdit->setReadOnly(true);
        m_fileNameEdit->setPlaceholderText(QStringLiteral("Select files"));
        m_treeView->setSelectionMode(QAbstractItemView::ExtendedSelection);
        m_iconView->setSelectionMode(QAbstractItemView::ExtendedSelection);
        m_newFolderBtn->hide();
        break;

    case FileDialogMode::OpenDirectory:
        m_acceptBtn->setText(QStringLiteral("Choose"));
        m_fileNameLabel->setText(QStringLiteral("Folder:"));
        m_fileNameEdit->setReadOnly(true);
        m_fileNameEdit->setPlaceholderText(QStringLiteral("Select a folder"));
        m_treeView->setSelectionMode(QAbstractItemView::SingleSelection);
        m_iconView->setSelectionMode(QAbstractItemView::SingleSelection);
        m_newFolderBtn->show();
        break;

    case FileDialogMode::SaveFile:
        m_acceptBtn->setText(QStringLiteral("Save"));
        m_fileNameLabel->setText(QStringLiteral("Save as:"));
        m_fileNameEdit->setReadOnly(false);
        m_fileNameEdit->setPlaceholderText(QStringLiteral("Enter file name"));
        m_treeView->setSelectionMode(QAbstractItemView::SingleSelection);
        m_iconView->setSelectionMode(QAbstractItemView::SingleSelection);
        m_newFolderBtn->show();
        break;

    case FileDialogMode::OpenFilesAndDirs:
        m_acceptBtn->setText(QStringLiteral("Open"));
        m_fileNameLabel->setText(QStringLiteral("Selection:"));
        m_fileNameEdit->setReadOnly(true);
        m_fileNameEdit->setPlaceholderText(QStringLiteral("Select files and folders"));
        m_treeView->setSelectionMode(QAbstractItemView::ExtendedSelection);
        m_iconView->setSelectionMode(QAbstractItemView::ExtendedSelection);
        m_newFolderBtn->show();
        break;
    }
}

void CustomFileDialog::setDirectory(const QString &path)
{
    navigateTo(path);
}

QString CustomFileDialog::directory() const
{
    if (m_historyIndex >= 0 && m_historyIndex < m_history.size())
        return m_history[m_historyIndex];
    return QDir::homePath();
}

void CustomFileDialog::setNameFilters(const QStringList &filters)
{
    m_filterCombo->clear();
    for (const QString &f : filters) {
        m_filterCombo->addItem(f);
    }
    if (!filters.isEmpty()) {
        onFilterChanged(0);
    }
}

QStringList CustomFileDialog::nameFilters() const
{
    QStringList filters;
    for (int i = 0; i < m_filterCombo->count(); ++i) {
        filters << m_filterCombo->itemText(i);
    }
    return filters;
}

void CustomFileDialog::setDefaultSuffix(const QString &suffix)
{
    m_defaultSuffix = suffix;
}

void CustomFileDialog::setWindowTitle(const QString &title)
{
    QDialog::setWindowTitle(title);
}

void CustomFileDialog::setAcceptButtonText(const QString &text)
{
    m_acceptBtn->setText(text);
}

void CustomFileDialog::setRejectButtonText(const QString &text)
{
    m_cancelBtn->setText(text);
}

void CustomFileDialog::setSaveFileName(const QString &name)
{
    m_fileNameEdit->setText(name);
}

QString CustomFileDialog::selectedFile() const
{
    return m_result.selectedPath;
}

QStringList CustomFileDialog::selectedFiles() const
{
    return m_result.selectedPaths;
}

FileDialogResult CustomFileDialog::result() const
{
    return m_result;
}

void CustomFileDialog::openDialog()
{
    show();
    raise();
    activateWindow();
}

void CustomFileDialog::navigateTo(const QString &path)
{
    QFileInfo fi(path);
    if (!fi.exists() || !fi.isDir())
        return;

    QString cleanPath = QDir::cleanPath(path);

    m_fsModel->setRootPath(cleanPath);
    QModelIndex sourceIndex = m_fsModel->index(cleanPath);
    QModelIndex proxyIndex = m_proxyModel->mapFromSource(sourceIndex);

    m_treeView->setRootIndex(proxyIndex);
    m_iconView->setRootIndex(proxyIndex);

    // Update history
    if (!m_navigating) {
        // Remove forward history
        if (m_historyIndex < m_history.size() - 1)
            m_history = m_history.mid(0, m_historyIndex + 1);
        m_history.append(cleanPath);
        m_historyIndex = m_history.size() - 1;
    }

    m_breadcrumb->setPath(cleanPath);
    m_sidebar->setCurrentPath(cleanPath);
    updateNavigationButtons();

    emit currentDirectoryChanged(cleanPath);

    // If in directory mode, update the file name
    if (m_mode == FileDialogMode::OpenDirectory) {
        m_fileNameEdit->setText(QDir(cleanPath).dirName());
    }
}

void CustomFileDialog::navigateUp()
{
    QDir dir(directory());
    if (dir.cdUp()) {
        navigateTo(dir.absolutePath());
    }
}

void CustomFileDialog::navigateBack()
{
    if (m_historyIndex > 0) {
        m_navigating = true;
        m_historyIndex--;
        navigateTo(m_history[m_historyIndex]);
        m_navigating = false;
        updateNavigationButtons();
    }
}

void CustomFileDialog::navigateForward()
{
    if (m_historyIndex < m_history.size() - 1) {
        m_navigating = true;
        m_historyIndex++;
        navigateTo(m_history[m_historyIndex]);
        m_navigating = false;
        updateNavigationButtons();
    }
}

void CustomFileDialog::updateNavigationButtons()
{
    m_backBtn->setEnabled(m_historyIndex > 0);
    m_forwardBtn->setEnabled(m_historyIndex < m_history.size() - 1);

    QDir dir(directory());
    m_upBtn->setEnabled(dir.cdUp());
}

void CustomFileDialog::onItemActivated(const QModelIndex &index)
{
    QModelIndex sourceIndex = m_proxyModel->mapToSource(index);
    QFileInfo fi = m_fsModel->fileInfo(sourceIndex);

    if (fi.isDir()) {
        navigateTo(fi.absoluteFilePath());
    } else {
        // Double-click on file = accept
        if (m_mode != FileDialogMode::OpenDirectory) {
            m_fileNameEdit->setText(fi.fileName());
            onAccept();
        }
    }
}

void CustomFileDialog::onSelectionChanged()
{
    QStringList paths = getSelectedPaths();

    if (paths.isEmpty()) {
        m_preview->clear();
        if (m_mode != FileDialogMode::SaveFile)
            m_fileNameEdit->clear();
        return;
    }

    // Update preview with first selected file
    QFileInfo fi(paths.first());
    m_preview->setFile(paths.first());

    // Update file name edit
    if (m_mode == FileDialogMode::SaveFile) {
        if (fi.isFile()) {
            m_fileNameEdit->setText(fi.fileName());
        }
    } else if (m_mode == FileDialogMode::OpenDirectory) {
        if (fi.isDir()) {
            m_fileNameEdit->setText(fi.fileName());
        }
    } else if (paths.size() == 1) {
        m_fileNameEdit->setText(fi.fileName());
    } else {
        QStringList names;
        for (const QString &p : paths)
            names << QFileInfo(p).fileName();
        m_fileNameEdit->setText(names.join(QStringLiteral(", ")));
    }
}

QStringList CustomFileDialog::getSelectedPaths() const
{
    QStringList paths;

    QAbstractItemView *currentView = (m_viewMode == FileDialogViewMode::List)
        ? static_cast<QAbstractItemView *>(m_treeView)
        : static_cast<QAbstractItemView *>(m_iconView);

    QModelIndexList selected = currentView->selectionModel()->selectedIndexes();

    QSet<int> seenRows;
    for (const QModelIndex &idx : selected) {
        if (seenRows.contains(idx.row()))
            continue;
        seenRows.insert(idx.row());

        QModelIndex sourceIdx = m_proxyModel->mapToSource(idx);
        QFileInfo fi = m_fsModel->fileInfo(sourceIdx);
        paths << fi.absoluteFilePath();
    }

    return paths;
}

void CustomFileDialog::onBreadcrumbClicked(const QString &path)
{
    navigateTo(path);
}

void CustomFileDialog::onSidebarClicked(const QString &path)
{
    navigateTo(path);
}

void CustomFileDialog::onSortCriteriaChanged(int index)
{
    auto criteria = static_cast<SortFilterProxyModel::SortCriteria>(
        m_sortCombo->itemData(index).toInt());
    m_proxyModel->setSortCriteria(criteria);
    m_proxyModel->sort(0, Qt::AscendingOrder);
}

void CustomFileDialog::onFilterChanged(int index)
{
    Q_UNUSED(index)
    QString filter = m_filterCombo->currentText();
    if (filter.isEmpty()) {
        m_proxyModel->setNameFilters({});
    } else {
        m_proxyModel->setNameFilters({filter});
    }
    emit filterChanged(filter);
}

void CustomFileDialog::onSearchTextChanged(const QString &text)
{
    if (text.isEmpty()) {
        m_proxyModel->setFilterFixedString(QString());
    } else {
        m_proxyModel->setFilterFixedString(text);
        m_proxyModel->setFilterKeyColumn(0);
        m_proxyModel->setFilterCaseSensitivity(Qt::CaseInsensitive);
    }
}

void CustomFileDialog::onAccept()
{
    m_result = FileDialogResult{};
    m_result.accepted = true;
    m_result.selectedFilter = m_filterCombo->currentText();

    QString currentDir = directory();

    switch (m_mode) {
    case FileDialogMode::OpenFile: {
        QStringList paths = getSelectedPaths();
        if (paths.isEmpty() || !QFileInfo(paths.first()).isFile()) {
            // Check if something typed in file name edit
            QString typed = m_fileNameEdit->text().trimmed();
            if (!typed.isEmpty()) {
                QString fullPath = QDir(currentDir).absoluteFilePath(typed);
                if (QFileInfo::exists(fullPath)) {
                    m_result.selectedPath = fullPath;
                    m_result.selectedPaths = {fullPath};
                } else {
                    return; // Don't accept if file doesn't exist
                }
            } else {
                return;
            }
        } else {
            m_result.selectedPath = paths.first();
            m_result.selectedPaths = {paths.first()};
        }
        emit fileSelected(m_result.selectedPath);
        break;
    }

    case FileDialogMode::OpenFiles: {
        QStringList paths = getSelectedPaths();
        // Filter to files only
        QStringList filePaths;
        for (const QString &p : paths) {
            if (QFileInfo(p).isFile())
                filePaths << p;
        }
        if (filePaths.isEmpty())
            return;
        m_result.selectedPaths = filePaths;
        m_result.selectedPath = filePaths.first();
        emit filesSelected(m_result.selectedPaths);
        break;
    }

    case FileDialogMode::OpenDirectory: {
        QStringList paths = getSelectedPaths();
        QString dirPath;
        if (!paths.isEmpty() && QFileInfo(paths.first()).isDir()) {
            dirPath = paths.first();
        } else {
            dirPath = currentDir;
        }
        m_result.selectedPath = dirPath;
        m_result.selectedPaths = {dirPath};
        emit directorySelected(m_result.selectedPath);
        break;
    }

    case FileDialogMode::SaveFile: {
        QString fileName = m_fileNameEdit->text().trimmed();
        if (fileName.isEmpty())
            return;

        // Apply default suffix
        if (!m_defaultSuffix.isEmpty() && !fileName.contains('.')) {
            fileName += '.' + m_defaultSuffix;
        }

        QString fullPath = QDir(currentDir).absoluteFilePath(fileName);

        // Check if exists
        if (QFileInfo::exists(fullPath)) {
            auto btn = QMessageBox::question(this, QStringLiteral("File exists"),
                QStringLiteral("The file \"%1\" already exists. Do you want to replace it?")
                    .arg(fileName),
                QMessageBox::Yes | QMessageBox::No);
            if (btn != QMessageBox::Yes)
                return;
        }

        m_result.selectedPath = fullPath;
        m_result.selectedPaths = {fullPath};
        emit fileSelected(m_result.selectedPath);
        break;
    }

    case FileDialogMode::OpenFilesAndDirs: {
        QStringList paths = getSelectedPaths();
        if (paths.isEmpty())
            return;
        m_result.selectedPaths = paths;
        m_result.selectedPath = paths.first();
        emit filesSelected(m_result.selectedPaths);
        break;
    }
    }

    emit dialogFinished(m_result);
    accept();
}

void CustomFileDialog::onReject()
{
    m_result = FileDialogResult{};
    m_result.accepted = false;
    emit dialogFinished(m_result);
    reject();
}

void CustomFileDialog::toggleHiddenFiles()
{
    bool show = m_hiddenBtn->isChecked();
    m_proxyModel->setShowHiddenFiles(show);

    QDir::Filters filters = QDir::AllEntries | QDir::NoDotAndDotDot | QDir::AllDirs;
    if (show)
        filters |= QDir::Hidden;
    m_fsModel->setFilter(filters);
}

void CustomFileDialog::togglePreview()
{
    m_preview->setVisible(m_previewBtn->isChecked());
}

void CustomFileDialog::setViewMode(FileDialogViewMode viewMode)
{
    m_viewMode = viewMode;

    m_listViewBtn->setChecked(viewMode == FileDialogViewMode::List);
    m_iconViewBtn->setChecked(viewMode == FileDialogViewMode::Icons);

    if (viewMode == FileDialogViewMode::List) {
        m_viewStack->setCurrentWidget(m_treeView);
        // Sync root index
        m_treeView->setRootIndex(m_iconView->rootIndex());
    } else {
        m_viewStack->setCurrentWidget(m_iconView);
        m_iconView->setRootIndex(m_treeView->rootIndex());
    }
}

void CustomFileDialog::onThumbnailReady(const QString &filePath, const QPixmap &pixmap)
{
    Q_UNUSED(filePath)
    Q_UNUSED(pixmap)

    // Force repaint of visible items
    if (m_viewMode == FileDialogViewMode::List) {
        m_treeView->viewport()->update();
    } else {
        m_iconView->viewport()->update();
    }
}

void CustomFileDialog::keyPressEvent(QKeyEvent *event)
{
    if (event->key() == Qt::Key_Return || event->key() == Qt::Key_Enter) {
        // If file name edit has focus in save mode, accept
        if (m_mode == FileDialogMode::SaveFile && m_fileNameEdit->hasFocus()) {
            onAccept();
            return;
        }

        // Otherwise, check if a directory is selected to navigate into it
        QStringList paths = getSelectedPaths();
        if (paths.size() == 1 && QFileInfo(paths.first()).isDir()) {
            if (m_mode != FileDialogMode::OpenDirectory) {
                navigateTo(paths.first());
                return;
            }
        }
    }

    if (event->key() == Qt::Key_Backspace) {
        navigateUp();
        return;
    }

    QDialog::keyPressEvent(event);
}

void CustomFileDialog::showEvent(QShowEvent *event)
{
    QDialog::showEvent(event);

    // Center on screen
    if (auto *screen = QApplication::primaryScreen()) {
        QRect screenGeometry = screen->availableGeometry();
        move(screenGeometry.center() - rect().center());
    }
}

// Static convenience methods

void CustomFileDialog::getOpenFileName(QWidget *parent,
                                        const QString &caption,
                                        const QString &dir,
                                        const QString &filter,
                                        std::function<void(const QString &)> callback)
{
    auto *dlg = new CustomFileDialog(parent);
    dlg->setMode(FileDialogMode::OpenFile);
    dlg->setWindowTitle(caption.isEmpty() ? QStringLiteral("Open File") : caption);
    if (!dir.isEmpty())
        dlg->setDirectory(dir);
    if (!filter.isEmpty())
        dlg->setNameFilters(filter.split(";;"));
    dlg->setAttribute(Qt::WA_DeleteOnClose);

    QObject::connect(dlg, &CustomFileDialog::fileSelected, callback);
    QObject::connect(dlg, &QDialog::rejected, [callback]() {
        callback(QString());
    });

    dlg->openDialog();
}

void CustomFileDialog::getOpenFileNames(QWidget *parent,
                                         const QString &caption,
                                         const QString &dir,
                                         const QString &filter,
                                         std::function<void(const QStringList &)> callback)
{
    auto *dlg = new CustomFileDialog(parent);
    dlg->setMode(FileDialogMode::OpenFiles);
    dlg->setWindowTitle(caption.isEmpty() ? QStringLiteral("Open Files") : caption);
    if (!dir.isEmpty())
        dlg->setDirectory(dir);
    if (!filter.isEmpty())
        dlg->setNameFilters(filter.split(";;"));
    dlg->setAttribute(Qt::WA_DeleteOnClose);

    QObject::connect(dlg, &CustomFileDialog::filesSelected, callback);
    QObject::connect(dlg, &QDialog::rejected, [callback]() {
        callback(QStringList());
    });

    dlg->openDialog();
}

void CustomFileDialog::getExistingDirectory(QWidget *parent,
                                             const QString &caption,
                                             const QString &dir,
                                             std::function<void(const QString &)> callback)
{
    auto *dlg = new CustomFileDialog(parent);
    dlg->setMode(FileDialogMode::OpenDirectory);
    dlg->setWindowTitle(caption.isEmpty() ? QStringLiteral("Choose Directory") : caption);
    if (!dir.isEmpty())
        dlg->setDirectory(dir);
    dlg->setAttribute(Qt::WA_DeleteOnClose);

    QObject::connect(dlg, &CustomFileDialog::directorySelected, callback);
    QObject::connect(dlg, &QDialog::rejected, [callback]() {
        callback(QString());
    });

    dlg->openDialog();
}

void CustomFileDialog::getSaveFileName(QWidget *parent,
                                        const QString &caption,
                                        const QString &dir,
                                        const QString &filter,
                                        std::function<void(const QString &)> callback)
{
    auto *dlg = new CustomFileDialog(parent);
    dlg->setMode(FileDialogMode::SaveFile);
    dlg->setWindowTitle(caption.isEmpty() ? QStringLiteral("Save File") : caption);
    if (!dir.isEmpty()) {
        QFileInfo fi(dir);
        if (fi.isDir()) {
            dlg->setDirectory(dir);
        } else {
            dlg->setDirectory(fi.absolutePath());
            dlg->setSaveFileName(fi.fileName());
        }
    }
    if (!filter.isEmpty())
        dlg->setNameFilters(filter.split(";;"));
    dlg->setAttribute(Qt::WA_DeleteOnClose);

    QObject::connect(dlg, &CustomFileDialog::fileSelected, callback);
    QObject::connect(dlg, &QDialog::rejected, [callback]() {
        callback(QString());
    });

    dlg->openDialog();
}
```

## src/main.cpp

```cpp
#include <QApplication>
#include <QPushButton>
#include <QVBoxLayout>
#include <QLabel>
#include <QWidget>
#include <QDebug>
#include "customfiledialog.h"

class DemoWindow : public QWidget
{
    Q_OBJECT

public:
    DemoWindow(QWidget *parent = nullptr) : QWidget(parent)
    {
        setWindowTitle("Custom File Dialog Demo");
        resize(500, 400);

        auto *layout = new QVBoxLayout(this);
        layout->setSpacing(12);
        layout->setContentsMargins(20, 20, 20, 20);

        auto *title = new QLabel("Custom File Dialog - macOS Finder Style");
        title->setStyleSheet("font-size: 18px; font-weight: bold; color: #333;");
        title->setAlignment(Qt::AlignCenter);
        layout->addWidget(title);

        layout->addSpacing(10);

        m_resultLabel = new QLabel("No selection yet");
        m_resultLabel->setWordWrap(true);
        m_resultLabel->setStyleSheet(
            "QLabel {"
            "  background-color: #F5F5F7;"
            "  border: 1px solid #E0E0E0;"
            "  border-radius: 8px;"
            "  padding: 12px;"
            "  font-size: 12px;"
            "  color: #555;"
            "}"
        );
        m_resultLabel->setMinimumHeight(80);
        layout->addWidget(m_resultLabel);

        layout->addSpacing(10);

        auto makeButton = [&](const QString &text) {
            auto *btn = new QPushButton(text);
            btn->setFixedHeight(36);
            btn->setStyleSheet(
                "QPushButton {"
                "  border: 1px solid #D0D0D2;"
                "  border-radius: 8px;"
                "  background-color: white;"
                "  font-size: 13px;"
                "  padding: 0 16px;"
                "}"
                "QPushButton:hover {"
                "  background-color: #F0F0F2;"
                "}"
                "QPushButton:pressed {"
                "  background-color: #E5E5E7;"
                "}"
            );
            layout->addWidget(btn);
            return btn;
        };

        // Open Single File
        auto *openFileBtn = makeButton("Open Single File");
        connect(openFileBtn, &QPushButton::clicked, this, [this]() {
            CustomFileDialog::getOpenFileName(this,
                "Open File",
                QDir::homePath(),
                "All Files (*);;Images (*.png *.jpg *.jpeg *.gif *.bmp *.svg *.webp);;Text Files (*.txt *.md *.csv)",
                [this](const QString &path) {
                    if (path.isEmpty())
                        m_resultLabel->setText("Cancelled");
                    else
                        m_resultLabel->setText("Selected file:\n" + path);
                });
        });

        // Open Multiple Files
        auto *openFilesBtn = makeButton("Open Multiple Files");
        connect(openFilesBtn, &QPushButton::clicked, this, [this]() {
            CustomFileDialog::getOpenFileNames(this,
                "Open Files",
                QDir::homePath(),
                "All Files (*);;Images (*.png *.jpg *.jpeg *.gif *.bmp);;Documents (*.pdf *.doc *.docx *.odt)",
                [this](const QStringList &paths) {
                    if (paths.isEmpty())
                        m_resultLabel->setText("Cancelled");
                    else
                        m_resultLabel->setText(
                            QString("Selected %1 file(s):\n").arg(paths.size()) + paths.join("\n"));
                });
        });

        // Open Directory
        auto *openDirBtn = makeButton("Open Directory");
        connect(openDirBtn, &QPushButton::clicked, this, [this]() {
            CustomFileDialog::getExistingDirectory(this,
                "Choose Directory",
                QDir::homePath(),
                [this](const QString &path) {
                    if (path.isEmpty())
                        m_resultLabel->setText("Cancelled");
                    else
                        m_resultLabel->setText("Selected directory:\n" + path);
                });
        });

        // Save File
        auto *saveFileBtn = makeButton("Save File");
        connect(saveFileBtn, &QPushButton::clicked, this, [this]() {
            CustomFileDialog::getSaveFileName(this,
                "Save File",
                QDir::homePath() + "/untitled.txt",
                "Text Files (*.txt);;All Files (*)",
                [this](const QString &path) {
                    if (path.isEmpty())
                        m_resultLabel->setText("Cancelled");
                    else
                        m_resultLabel->setText("Save path:\n" + path);
                });
        });

        // Advanced: Non-blocking with signal/slot
        auto *advancedBtn = makeButton("Advanced (Signal/Slot Pattern)");
        advancedBtn->setStyleSheet(
            advancedBtn->styleSheet() +
            "QPushButton { background-color: #0066CC; color: white; border: none; font-weight: bold; }"
            "QPushButton:hover { background-color: #0055AA; }"
        );
        connect(advancedBtn, &QPushButton::clicked, this, [this]() {
            auto *dlg = new CustomFileDialog(this);
            dlg->setMode(FileDialogMode::OpenFilesAndDirs);
            dlg->setWindowTitle("Advanced Selection");
            dlg->setDirectory(QDir::homePath());
            dlg->setNameFilters({
                "All Files (*)",
                "Images (*.png *.jpg *.jpeg *.gif *.bmp *.svg *.webp *.tiff)",
                "Videos (*.mp4 *.avi *.mkv *.mov *.webm)",
                "Audio (*.mp3 *.wav *.flac *.ogg *.aac)",
                "Documents (*.pdf *.doc *.docx *.odt *.txt)",
                "Archives (*.zip *.tar *.gz *.7z *.rar)"
            });
            dlg->setAttribute(Qt::WA_DeleteOnClose);

            // Connect signals
            connect(dlg, &CustomFileDialog::dialogFinished, this,
                    [this](const FileDialogResult &result) {
                if (result.accepted) {
                    m_resultLabel->setText(
                        QString("Accepted! Selected %1 item(s):\n")
                            .arg(result.selectedPaths.size())
                        + result.selectedPaths.join("\n")
                        + "\n\nFilter: " + result.selectedFilter);
                } else {
                    m_resultLabel->setText("Dialog cancelled");
                }
            });

            connect(dlg, &CustomFileDialog::currentDirectoryChanged, this,
                    [](const QString &dir) {
                qDebug() << "Directory changed to:" << dir;
            });

            dlg->openDialog();
        });

        layout->addStretch();
    }

private:
    QLabel *m_resultLabel;
};

#include "main.moc"

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);
    app.setApplicationName("CustomFileDialog");

    // Set a clean, modern style
    app.setStyle("Fusion");

    // Light color palette
    QPalette palette;
    palette.setColor(QPalette::Window, QColor(255, 255, 255));
    palette.setColor(QPalette::WindowText, QColor(51, 51, 51));
    palette.setColor(QPalette::Base, QColor(255, 255, 255));
    palette.setColor(QPalette::AlternateBase, QColor(245, 245, 247));
    palette.setColor(QPalette::ToolTipBase, QColor(255, 255, 255));
    palette.setColor(QPalette::ToolTipText, QColor(51, 51, 51));
    palette.setColor(QPalette::Text, QColor(51, 51, 51));
    palette.setColor(QPalette::Button, QColor(240, 240, 242));
    palette.setColor(QPalette::ButtonText, QColor(51, 51, 51));
    palette.setColor(QPalette::Highlight, QColor(0, 102, 204));
    palette.setColor(QPalette::HighlightedText, Qt::white);
    app.setPalette(palette);

    DemoWindow window;
    window.show();

    return app.exec();
}
```

## Key Architecture & Design Decisions

### Non-Blocking Usage Pattern

```
┌─────────────────────────────────────────────────────┐
│              Usage Patterns                         │
├─────────────────────────────────────────────────────┤
│                                                     │
│  1. Static Methods + Callbacks (Lambda):            │
│     CustomFileDialog::getOpenFileName(              │
│         parent, caption, dir, filter,               │
│         [](const QString &path) { ... }             │
│     );                                              │
│                                                     │
│  2. Signal/Slot Pattern:                            │
│     auto *dlg = new CustomFileDialog(parent);       │
│     connect(dlg, &dialogFinished, this, &slot);     │
│     dlg->openDialog();  // non-blocking show()      │
│                                                     │
│  3. Direct Result Access (after accept):            │
│     FileDialogResult r = dlg->result();             │
│     QString path = r.selectedPath;                  │
│     QStringList paths = r.selectedPaths;            │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Threading Architecture

```
┌──────────────────┐     ┌─────────────────────┐
│    GUI Thread     │     │  Thread Pool         │
│                   │     │  (QtConcurrent)      │
│  ┌─────────────┐ │     │                      │
│  │ TreeView /   │ │     │  ┌────────────────┐ │
│  │ IconView     │◄├─────┤──│ Thumbnail Gen  │ │
│  └─────────────┘ │     │  └────────────────┘ │
│                   │     │                      │
│  ┌─────────────┐ │     │  Image loading       │
│  │ Preview      │◄├─────┤  and scaling runs   │
│  │ Widget       │ │     │  in worker threads   │
│  └─────────────┘ │     │                      │
│                   │     │  Results delivered   │
│  Signals ────────►├─────┤► via QueuedConnection│
│                   │     │                      │
└──────────────────┘     └─────────────────────┘
```

### Features Summary

| Feature | Implementation |
|---|---|
| **Single file selection** | `FileDialogMode::OpenFile` |
| **Multiple file selection** | `FileDialogMode::OpenFiles` with `ExtendedSelection` |
| **Directory selection** | `FileDialogMode::OpenDirectory` |
| **Save file naming** | `FileDialogMode::SaveFile` with editable filename |
| **Mixed selection** | `FileDialogMode::OpenFilesAndDirs` |
| **Non-blocking** | `openDialog()` + signals, static methods with callbacks |
| **Sorting** | Name, Size, Type, Date, Extension via `SortFilterProxyModel` |
| **Thumbnails** | `QtConcurrent::run` with `QCache`, all Qt-supported image formats |
| **Breadcrumb nav** | Clickable path segments like Finder |
| **Sidebar** | Favorites + Volumes via `QStandardPaths` / `QStorageInfo` |
| **Preview panel** | Image preview, file metadata, toggleable |
| **Hidden files** | Toggle via button or `Ctrl+Shift+H` |
| **Search** | Debounced filename filter |
| **Name filters** | Wildcard patterns, combo selector |
| **Keyboard nav** | Backspace=up, Alt+arrows=history, Ctrl+F=search, Esc=cancel |
| **macOS styling** | Rounded corners, blue accent, Fusion palette, proper spacing |