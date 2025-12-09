Вот реализация класса на C++ для Qt 6.9 на основе QtQuickPaintedItem:

**bookmarkitem.h:**
```cpp
#ifndef BOOKMARKITEM_H
#define BOOKMARKITEM_H

#include <QQuickPaintedItem>
#include <QPainter>
#include <QFontMetrics>
#include <QColor>

class BookmarkItem : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(QStringList bookmarks READ bookmarks WRITE setBookmarks NOTIFY bookmarksChanged)
    Q_PROPERTY(int currentIndex READ currentIndex WRITE setCurrentIndex NOTIFY currentIndexChanged)
    Q_PROPERTY(QColor bookmarkColor READ bookmarkColor WRITE setBookmarkColor NOTIFY bookmarkColorChanged)
    Q_PROPERTY(QColor textColor READ textColor WRITE setTextColor NOTIFY textColorChanged)
    Q_PROPERTY(double cornerRadius READ cornerRadius WRITE setCornerRadius NOTIFY cornerRadiusChanged)
    Q_PROPERTY(double padding READ padding WRITE setPadding NOTIFY paddingChanged)
    Q_PROPERTY(double maxTextWidth READ maxTextWidth WRITE setMaxTextWidth NOTIFY maxTextWidthChanged)
    Q_PROPERTY(QFont font READ font WRITE setFont NOTIFY fontChanged)

public:
    explicit BookmarkItem(QQuickItem *parent = nullptr);

    void paint(QPainter *painter) override;

    QStringList bookmarks() const;
    void setBookmarks(const QStringList &bookmarks);

    int currentIndex() const;
    void setCurrentIndex(int index);

    QColor bookmarkColor() const;
    void setBookmarkColor(const QColor &color);

    QColor textColor() const;
    void setTextColor(const QColor &color);

    double cornerRadius() const;
    void setCornerRadius(double radius);

    double padding() const;
    void setPadding(double padding);

    double maxTextWidth() const;
    void setMaxTextWidth(double width);

    QFont font() const;
    void setFont(const QFont &font);

protected:
    void mousePressEvent(QMouseEvent *event) override;
    void hoverMoveEvent(QHoverEvent *event) override;

signals:
    void bookmarksChanged();
    void currentIndexChanged();
    void bookmarkColorChanged();
    void textColorChanged();
    void cornerRadiusChanged();
    void paddingChanged();
    void maxTextWidthChanged();
    void fontChanged();
    void bookmarkClicked(int index);

private:
    void updateBookmarkRects();
    QColor calculateBackgroundColor() const;
    QColor calculateHoverColor(const QColor &baseColor) const;
    QString elideText(const QString &text, double maxWidth) const;

    QStringList m_bookmarks;
    int m_currentIndex = -1;
    int m_hoveredIndex = -1;
    QColor m_bookmarkColor = QColor("#3498db");
    QColor m_textColor = Qt::white;
    double m_cornerRadius = 6.0;
    double m_padding = 8.0;
    double m_maxTextWidth = 200.0;
    QFont m_font;
    QList<QRectF> m_bookmarkRects;
    QFontMetrics m_fontMetrics;
};

#endif // BOOKMARKITEM_H
```

**bookmarkitem.cpp:**
```cpp
#include "bookmarkitem.h"
#include <QMouseEvent>
#include <QHoverEvent>
#include <QDebug>

BookmarkItem::BookmarkItem(QQuickItem *parent)
    : QQuickPaintedItem(parent)
    , m_font("Arial", 11)
    , m_fontMetrics(m_font)
{
    setAcceptHoverEvents(true);
    setAntialiasing(true);
}

void BookmarkItem::paint(QPainter *painter)
{
    if (m_bookmarks.isEmpty() || width() <= 0 || height() <= 0)
        return;

    // Устанавливаем качество отрисовки
    painter->setRenderHint(QPainter::Antialiasing);
    painter->setFont(m_font);

    // Рассчитываем фоновый цвет (на 30% темнее цвета закладок)
    QColor backgroundColor = calculateBackgroundColor();
    
    // Рисуем фон
    painter->fillRect(boundingRect(), backgroundColor);

    // Обновляем прямоугольники закладок
    updateBookmarkRects();

    // Рисуем все закладки
    for (int i = 0; i < m_bookmarks.size(); ++i) {
        const QRectF &rect = m_bookmarkRects.value(i);
        if (!rect.isValid())
            continue;

        // Определяем цвет закладки
        QColor bookmarkColor = m_bookmarkColor;
        
        // Если это текущая закладка
        if (i == m_currentIndex) {
            bookmarkColor = bookmarkColor.lighter(110);
        }
        // Если наведена мышь
        else if (i == m_hoveredIndex) {
            bookmarkColor = calculateHoverColor(bookmarkColor);
        }

        // Создаем путь с закругленными углами (нижние скругления будут скрыты)
        QPainterPath path;
        QRectF visibleRect = rect;
        visibleRect.setBottom(visibleRect.bottom() - m_cornerRadius); // Скрываем нижние скругления
        
        path.addRoundedRect(visibleRect, m_cornerRadius, m_cornerRadius);

        // Рисуем закладку
        painter->fillPath(path, bookmarkColor);

        // Подготавливаем текст
        QString text = m_bookmarks[i];
        QString elidedText = elideText(text, m_maxTextWidth);
        
        // Рассчитываем область для текста (только видимая часть)
        QRectF textRect = visibleRect;
        
        // Рисуем текст
        painter->setPen(m_textColor);
        painter->drawText(textRect, Qt::AlignCenter, elidedText);
    }
}

QStringList BookmarkItem::bookmarks() const
{
    return m_bookmarks;
}

void BookmarkItem::setBookmarks(const QStringList &bookmarks)
{
    if (m_bookmarks != bookmarks) {
        m_bookmarks = bookmarks;
        updateBookmarkRects();
        update();
        emit bookmarksChanged();
    }
}

int BookmarkItem::currentIndex() const
{
    return m_currentIndex;
}

void BookmarkItem::setCurrentIndex(int index)
{
    if (m_currentIndex != index && index >= -1 && index < m_bookmarks.size()) {
        m_currentIndex = index;
        update();
        emit currentIndexChanged();
    }
}

QColor BookmarkItem::bookmarkColor() const
{
    return m_bookmarkColor;
}

void BookmarkItem::setBookmarkColor(const QColor &color)
{
    if (m_bookmarkColor != color) {
        m_bookmarkColor = color;
        update();
        emit bookmarkColorChanged();
    }
}

QColor BookmarkItem::textColor() const
{
    return m_textColor;
}

void BookmarkItem::setTextColor(const QColor &color)
{
    if (m_textColor != color) {
        m_textColor = color;
        update();
        emit textColorChanged();
    }
}

double BookmarkItem::cornerRadius() const
{
    return m_cornerRadius;
}

void BookmarkItem::setCornerRadius(double radius)
{
    if (!qFuzzyCompare(m_cornerRadius, radius)) {
        m_cornerRadius = radius;
        updateBookmarkRects();
        update();
        emit cornerRadiusChanged();
    }
}

double BookmarkItem::padding() const
{
    return m_padding;
}

void BookmarkItem::setPadding(double padding)
{
    if (!qFuzzyCompare(m_padding, padding)) {
        m_padding = padding;
        updateBookmarkRects();
        update();
        emit paddingChanged();
    }
}

double BookmarkItem::maxTextWidth() const
{
    return m_maxTextWidth;
}

void BookmarkItem::setMaxTextWidth(double width)
{
    if (!qFuzzyCompare(m_maxTextWidth, width)) {
        m_maxTextWidth = width;
        update();
        emit maxTextWidthChanged();
    }
}

QFont BookmarkItem::font() const
{
    return m_font;
}

void BookmarkItem::setFont(const QFont &font)
{
    if (m_font != font) {
        m_font = font;
        m_fontMetrics = QFontMetrics(m_font);
        updateBookmarkRects();
        update();
        emit fontChanged();
    }
}

void BookmarkItem::mousePressEvent(QMouseEvent *event)
{
    QPointF pos = event->position();
    
    for (int i = 0; i < m_bookmarkRects.size(); ++i) {
        const QRectF &rect = m_bookmarkRects[i];
        QRectF clickRect = rect;
        clickRect.setBottom(clickRect.bottom() - m_cornerRadius);
        
        if (clickRect.contains(pos)) {
            setCurrentIndex(i);
            emit bookmarkClicked(i);
            break;
        }
    }
    
    QQuickPaintedItem::mousePressEvent(event);
}

void BookmarkItem::hoverMoveEvent(QHoverEvent *event)
{
    QPointF pos = event->position();
    int oldHovered = m_hoveredIndex;
    m_hoveredIndex = -1;
    
    for (int i = 0; i < m_bookmarkRects.size(); ++i) {
        const QRectF &rect = m_bookmarkRects[i];
        QRectF hoverRect = rect;
        hoverRect.setBottom(hoverRect.bottom() - m_cornerRadius);
        
        if (hoverRect.contains(pos)) {
            m_hoveredIndex = i;
            break;
        }
    }
    
    if (oldHovered != m_hoveredIndex) {
        update();
    }
    
    QQuickPaintedItem::hoverMoveEvent(event);
}

void BookmarkItem::updateBookmarkRects()
{
    m_bookmarkRects.clear();
    
    if (m_bookmarks.isEmpty() || width() <= 0)
        return;
    
    double x = 0;
    double itemHeight = height();
    double visibleHeight = itemHeight - m_cornerRadius; // Высота видимой части
    
    for (const QString &text : m_bookmarks) {
        QString elidedText = elideText(text, m_maxTextWidth);
        double textWidth = m_fontMetrics.horizontalAdvance(elidedText);
        double bookmarkWidth = textWidth + 2 * m_padding;
        
        // Создаем прямоугольник для закладки (нижняя часть выходит за границы)
        QRectF rect(x, 0, bookmarkWidth, itemHeight);
        m_bookmarkRects.append(rect);
        
        x += bookmarkWidth;
    }
}

QColor BookmarkItem::calculateBackgroundColor() const
{
    // Фон на 30% темнее цвета закладок
    return m_bookmarkColor.darker(130);
}

QColor BookmarkItem::calculateHoverColor(const QColor &baseColor) const
{
    // Закладка становится светлее на 20% при наведении
    return baseColor.lighter(120);
}

QString BookmarkItem::elideText(const QString &text, double maxWidth) const
{
    if (text.isEmpty())
        return text;
    
    double availableWidth = maxWidth - 2 * m_padding;
    if (availableWidth <= 0)
        return "...";
    
    return m_fontMetrics.elidedText(text, Qt::ElideRight, static_cast<int>(availableWidth));
}
```

**Пример использования в QML (main.qml):**
```qml
import QtQuick 2.15
import QtQuick.Window 2.15
import com.example 1.0

Window {
    width: 800
    height: 200
    visible: true
    title: "Bookmark Example"

    BookmarkItem {
        id: bookmarkItem
        anchors.fill: parent
        anchors.margins: 20
        
        bookmarks: ["Home", "Documents", "Downloads", "Applications", "Settings", "Help"]
        currentIndex: 0
        bookmarkColor: "#3498db"
        textColor: "white"
        cornerRadius: 10
        padding: 12
        maxTextWidth: 150
        font.pixelSize: 12
        
        onBookmarkClicked: function(index) {
            console.log("Bookmark clicked:", index, bookmarks[index])
        }
    }
}
```

**CMakeLists.txt (фрагмент для подключения):**
```cmake
# ... существующий код ...

# Создаем библиотеку
qt_add_library(BookmarkLibrary STATIC
    bookmarkitem.cpp
    bookmarkitem.h
)

# Указываем, что библиотека использует Qt Quick
target_link_libraries(BookmarkLibrary
    Qt${QT_VERSION_MAJOR}::Core
    Qt${QT_VERSION_MAJOR}::Quick
    Qt${QT_VERSION_MAJOR}::Gui
)

# Подключаем к основному приложению
target_link_libraries(${PROJECT_NAME} PRIVATE BookmarkLibrary)

# Регистрируем QML тип
qt_add_qml_module(${PROJECT_NAME}
    URI com.example
    VERSION 1.0
    QML_FILES main.qml
    DEPENDENCIES QtQuick
)
```

**Важные особенности реализации:**

1. **Скрытие нижних скруглений**: Достигается путем рисования закладки на высоту `height() - cornerRadius`, а затем расширения прямоугольника вниз на `cornerRadius`.

2. **Расчет ширины**: Ширина каждой закладки рассчитывается как `ширина_текста + 2 * padding`.

3. **Обрезка текста**: Используется `QFontMetrics::elidedText()` для добавления троеточия при превышении максимальной ширины.

4. **Интерактивность**: Реализованы обработчики событий мыши и наведения.

5. **Цветовые эффекты**:
   - Фон на 30% темнее (используется `QColor::darker(130)`)
   - Закладка светлеет на 20% при наведении (`QColor::lighter(120)`)
   - Текущая закладка слегка подсвечивается

6. **Свойства, доступные из QML**:
   - `bookmarks` - список текстов закладок
   - `currentIndex` - индекс текущей закладки
   - `bookmarkColor` - цвет закладок
   - `textColor` - цвет текста (по умолчанию белый)
   - `cornerRadius` - скругление углов
   - `padding` - отступ внутри закладки (по умолчанию 8)
   - `maxTextWidth` - максимальная ширина текста
   - `font` - шрифт текста

Класс полностью готов к использованию в Qt 6.9 и может быть интегрирован в ваш проект через CMake или qmake.
