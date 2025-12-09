Отличные идеи! Давайте добавим все запрошенные функции. Вот обновленная реализация:

**bookmarkitem.h:**
```cpp
#ifndef BOOKMARKITEM_H
#define BOOKMARKITEM_H

#include <QQuickPaintedItem>
#include <QPainter>
#include <QFontMetrics>
#include <QColor>
#include <QMimeData>
#include <QDrag>

class BookmarkItem : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(QStringList bookmarks READ bookmarks WRITE setBookmarks NOTIFY bookmarksChanged)
    Q_PROPERTY(int currentIndex READ currentIndex WRITE setCurrentIndex NOTIFY currentIndexChanged)
    Q_PROPERTY(QColor bookmarkColor READ bookmarkColor WRITE setBookmarkColor NOTIFY bookmarkColorChanged)
    Q_PROPERTY(QColor textColor READ textColor WRITE setTextColor NOTIFY textColorChanged)
    Q_PROPERTY(QColor borderColor READ borderColor WRITE setBorderColor NOTIFY borderColorChanged)
    Q_PROPERTY(double cornerRadius READ cornerRadius WRITE setCornerRadius NOTIFY cornerRadiusChanged)
    Q_PROPERTY(double padding READ padding WRITE setPadding NOTIFY paddingChanged)
    Q_PROPERTY(double maxTextWidth READ maxTextWidth WRITE setMaxTextWidth NOTIFY maxTextWidthChanged)
    Q_PROPERTY(QFont font READ font WRITE setFont NOTIFY fontChanged)
    Q_PROPERTY(double borderWidth READ borderWidth WRITE setBorderWidth NOTIFY borderWidthChanged)
    Q_PROPERTY(double lineHeight READ lineHeight WRITE setLineHeight NOTIFY lineHeightChanged)

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

    QColor borderColor() const;
    void setBorderColor(const QColor &color);

    double cornerRadius() const;
    void setCornerRadius(double radius);

    double padding() const;
    void setPadding(double padding);

    double maxTextWidth() const;
    void setMaxTextWidth(double width);

    QFont font() const;
    void setFont(const QFont &font);

    double borderWidth() const;
    void setBorderWidth(double width);

    double lineHeight() const;
    void setLineHeight(double height);

signals:
    void bookmarksChanged();
    void currentIndexChanged();
    void bookmarkColorChanged();
    void textColorChanged();
    void borderColorChanged();
    void cornerRadiusChanged();
    void paddingChanged();
    void maxTextWidthChanged();
    void fontChanged();
    void borderWidthChanged();
    void lineHeightChanged();
    void bookmarkClicked(int index);
    void bookmarksReordered(const QStringList &newOrder);

protected:
    void mousePressEvent(QMouseEvent *event) override;
    void mouseMoveEvent(QMouseEvent *event) override;
    void mouseReleaseEvent(QMouseEvent *event) override;
    void hoverMoveEvent(QHoverEvent *event) override;

private:
    void updateBookmarkRects();
    QColor calculateBackgroundColor() const;
    QColor calculateHoverColor(const QColor &baseColor) const;
    QString elideText(const QString &text, double maxWidth) const;
    void drawActiveBookmarkBorder(QPainter *painter, const QRectF &rect);
    void drawBottomLine(QPainter *painter);
    int findBookmarkAtPosition(const QPointF &pos);
    void startDrag(const QPointF &pos, int index);
    void dropBookmark(int sourceIndex, int targetIndex);

    QStringList m_bookmarks;
    int m_currentIndex = -1;
    int m_hoveredIndex = -1;
    int m_dragIndex = -1;
    QPointF m_dragStartPos;
    bool m_isDragging = false;
    
    QColor m_bookmarkColor = QColor("#3498db");
    QColor m_textColor = Qt::white;
    QColor m_borderColor = QColor("#2980b9");
    double m_cornerRadius = 6.0;
    double m_padding = 8.0;
    double m_maxTextWidth = 200.0;
    double m_borderWidth = 2.0;
    double m_lineHeight = 2.0;
    
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
#include <QApplication>
#include <QDebug>

BookmarkItem::BookmarkItem(QQuickItem *parent)
    : QQuickPaintedItem(parent)
    , m_font("Arial", 11)
    , m_fontMetrics(m_font)
{
    setAcceptHoverEvents(true);
    setAntialiasing(true);
    setAcceptedMouseButtons(Qt::LeftButton);
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

    // Сначала рисуем все неактивные закладки (чтобы активная была сверху)
    for (int i = 0; i < m_bookmarks.size(); ++i) {
        if (i == m_currentIndex)
            continue;

        const QRectF &rect = m_bookmarkRects.value(i);
        if (!rect.isValid())
            continue;

        // Определяем цвет закладки
        QColor bookmarkColor = m_bookmarkColor;
        
        // Если наведена мышь и это не перетаскиваемая закладка
        if (i == m_hoveredIndex && !m_isDragging) {
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

    // Рисуем нижнюю линию (под всеми неактивными закладками)
    drawBottomLine(painter);

    // Теперь рисуем активную закладку (она будет поверх линии)
    if (m_currentIndex >= 0 && m_currentIndex < m_bookmarkRects.size()) {
        const QRectF &rect = m_bookmarkRects.value(m_currentIndex);
        if (rect.isValid()) {
            // Определяем цвет активной закладки
            QColor bookmarkColor = m_bookmarkColor.lighter(110);
            
            // Создаем путь с закругленными углами
            QPainterPath path;
            QRectF visibleRect = rect;
            visibleRect.setBottom(visibleRect.bottom() - m_cornerRadius);
            
            path.addRoundedRect(visibleRect, m_cornerRadius, m_cornerRadius);

            // Рисуем закладку
            painter->fillPath(path, bookmarkColor);

            // Рисуем обводку активной закладки
            drawActiveBookmarkBorder(painter, visibleRect);

            // Подготавливаем и рисуем текст
            QString text = m_bookmarks[m_currentIndex];
            QString elidedText = elideText(text, m_maxTextWidth);
            
            QRectF textRect = visibleRect;
            painter->setPen(m_textColor);
            painter->drawText(textRect, Qt::AlignCenter, elidedText);
        }
    }

    // Если идет перетаскивание, рисуем подсказку о месте вставки
    if (m_isDragging && m_dragIndex >= 0 && m_dragIndex <= m_bookmarks.size()) {
        painter->setPen(QPen(m_borderColor, m_borderWidth));
        painter->setBrush(Qt::NoBrush);
        
        // Рисуем линию-индикатор места вставки
        double insertX;
        if (m_dragIndex == 0) {
            insertX = 0;
        } else if (m_dragIndex == m_bookmarks.size()) {
            insertX = width();
        } else {
            insertX = m_bookmarkRects.value(m_dragIndex).left();
        }
        
        painter->drawLine(QLineF(insertX, 0, insertX, height() - m_cornerRadius));
    }
}

double BookmarkItem::borderWidth() const
{
    return m_borderWidth;
}

void BookmarkItem::setBorderWidth(double width)
{
    if (!qFuzzyCompare(m_borderWidth, width)) {
        m_borderWidth = width;
        update();
        emit borderWidthChanged();
    }
}

double BookmarkItem::lineHeight() const
{
    return m_lineHeight;
}

void BookmarkItem::setLineHeight(double height)
{
    if (!qFuzzyCompare(m_lineHeight, height)) {
        m_lineHeight = height;
        update();
        emit lineHeightChanged();
    }
}

QColor BookmarkItem::borderColor() const
{
    return m_borderColor;
}

void BookmarkItem::setBorderColor(const QColor &color)
{
    if (m_borderColor != color) {
        m_borderColor = color;
        update();
        emit borderColorChanged();
    }
}

void BookmarkItem::drawActiveBookmarkBorder(QPainter *painter, const QRectF &rect)
{
    painter->save();
    
    // Настраиваем перо для обводки
    QPen pen(m_borderColor, m_borderWidth);
    pen.setJoinStyle(Qt::MiterJoin);
    painter->setPen(pen);
    painter->setBrush(Qt::NoBrush);
    
    // Создаем путь с закругленными углами и рисуем обводку
    QPainterPath path;
    path.addRoundedRect(rect, m_cornerRadius, m_cornerRadius);
    painter->drawPath(path);
    
    painter->restore();
}

void BookmarkItem::drawBottomLine(QPainter *painter)
{
    painter->save();
    
    QPen pen(m_borderColor, m_lineHeight);
    painter->setPen(pen);
    
    // Позиция линии - у нижнего края видимой части
    double lineY = height() - m_cornerRadius - m_lineHeight / 2;
    
    // Если есть активная закладка, рисуем линию с разрывом
    if (m_currentIndex >= 0 && m_currentIndex < m_bookmarkRects.size()) {
        const QRectF &activeRect = m_bookmarkRects.value(m_currentIndex);
        if (activeRect.isValid()) {
            // Левая часть линии (до активной закладки)
            if (activeRect.left() > 0) {
                painter->drawLine(QLineF(0, lineY, activeRect.left(), lineY));
            }
            
            // Правая часть линии (после активной закладки)
            if (activeRect.right() < width()) {
                painter->drawLine(QLineF(activeRect.right(), lineY, width(), lineY));
            }
        } else {
            // Если активная закладка невалидна, рисуем сплошную линию
            painter->drawLine(QLineF(0, lineY, width(), lineY));
        }
    } else {
        // Нет активной закладки - рисуем сплошную линию
        painter->drawLine(QLineF(0, lineY, width(), lineY));
    }
    
    painter->restore();
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
    m_dragStartPos = pos;
    m_dragIndex = -1;
    
    for (int i = 0; i < m_bookmarkRects.size(); ++i) {
        const QRectF &rect = m_bookmarkRects[i];
        QRectF clickRect = rect;
        clickRect.setBottom(clickRect.bottom() - m_cornerRadius);
        
        if (clickRect.contains(pos)) {
            m_dragIndex = i;
            setCurrentIndex(i);
            emit bookmarkClicked(i);
            break;
        }
    }
    
    QQuickPaintedItem::mousePressEvent(event);
}

void BookmarkItem::mouseMoveEvent(QMouseEvent *event)
{
    if (!(event->buttons() & Qt::LeftButton))
        return;
    
    if (m_dragIndex < 0 || m_dragIndex >= m_bookmarks.size())
        return;
    
    QPointF pos = event->position();
    QPointF dragDistance = pos - m_dragStartPos;
    
    // Запускаем перетаскивание, если переместили мышь достаточно далеко
    if (!m_isDragging && dragDistance.manhattanLength() > QApplication::startDragDistance()) {
        startDrag(pos, m_dragIndex);
    }
    
    if (m_isDragging) {
        // Находим новую позицию для вставки
        int newIndex = findBookmarkAtPosition(pos);
        if (newIndex != m_dragIndex && newIndex != -1) {
            m_dragIndex = newIndex;
            update();
        }
    }
    
    QQuickPaintedItem::mouseMoveEvent(event);
}

void BookmarkItem::mouseReleaseEvent(QMouseEvent *event)
{
    if (m_isDragging && m_dragIndex >= 0) {
        int sourceIndex = m_dragIndex;
        int targetIndex = findBookmarkAtPosition(event->position());
        
        if (targetIndex != -1 && targetIndex != sourceIndex) {
            dropBookmark(sourceIndex, targetIndex);
        }
        
        m_isDragging = false;
        m_dragIndex = -1;
        update();
    }
    
    QQuickPaintedItem::mouseReleaseEvent(event);
}

void BookmarkItem::hoverMoveEvent(QHoverEvent *event)
{
    if (m_isDragging) {
        // При перетаскивании обновляем позицию курсора
        QPointF pos = event->position();
        int newIndex = findBookmarkAtPosition(pos);
        if (newIndex != m_dragIndex && newIndex != -1) {
            m_dragIndex = newIndex;
            update();
        }
    } else {
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
    
    for (const QString &text : m_bookmarks) {
        QString elidedText = elideText(text, m_maxTextWidth);
        double textWidth = m_fontMetrics.horizontalAdvance(elidedText);
        double bookmarkWidth = textWidth + 2 * m_padding;
        
        // Создаем прямоугольник для закладки
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

int BookmarkItem::findBookmarkAtPosition(const QPointF &pos)
{
    if (m_bookmarkRects.isEmpty())
        return -1;
    
    // Если курсор перед первой закладкой
    if (pos.x() <= m_bookmarkRects.first().left())
        return 0;
    
    // Если курсор после последней закладки
    if (pos.x() >= m_bookmarkRects.last().right())
        return m_bookmarks.size();
    
    // Ищем, между какими закладками находится курсор
    for (int i = 0; i < m_bookmarkRects.size(); ++i) {
        const QRectF &rect = m_bookmarkRects[i];
        if (pos.x() >= rect.left() && pos.x() <= rect.right()) {
            // Определяем, в левой или правой половине закладки
            double centerX = rect.left() + rect.width() / 2;
            return (pos.x() < centerX) ? i : i + 1;
        }
    }
    
    return -1;
}

void BookmarkItem::startDrag(const QPointF &pos, int index)
{
    m_isDragging = true;
    
    // Можно реализовать настоящий drag&drop с QDrag,
    // но для простоты будем использовать визуальную индикацию
    update();
}

void BookmarkItem::dropBookmark(int sourceIndex, int targetIndex)
{
    if (sourceIndex < 0 || sourceIndex >= m_bookmarks.size() ||
        targetIndex < 0 || targetIndex > m_bookmarks.size())
        return;
    
    // Рассчитываем итоговый индекс после перемещения
    int finalTargetIndex = targetIndex;
    if (sourceIndex < targetIndex) {
        finalTargetIndex--;
    }
    
    if (sourceIndex == finalTargetIndex)
        return;
    
    // Перемещаем закладку
    QStringList newBookmarks = m_bookmarks;
    QString movedBookmark = newBookmarks.takeAt(sourceIndex);
    newBookmarks.insert(finalTargetIndex, movedBookmark);
    
    // Обновляем текущий индекс
    int newCurrentIndex = m_currentIndex;
    if (m_currentIndex == sourceIndex) {
        newCurrentIndex = finalTargetIndex;
    } else if (sourceIndex < m_currentIndex && m_currentIndex <= finalTargetIndex) {
        newCurrentIndex--;
    } else if (finalTargetIndex <= m_currentIndex && m_currentIndex < sourceIndex) {
        newCurrentIndex++;
    }
    
    // Устанавливаем новые данные
    setBookmarks(newBookmarks);
    setCurrentIndex(newCurrentIndex);
    
    emit bookmarksReordered(newBookmarks);
}
```

**Обновленный пример использования в QML (main.qml):**
```qml
import QtQuick 2.15
import QtQuick.Window 2.15
import com.example 1.0

Window {
    width: 800
    height: 200
    visible: true
    title: "Bookmark Example with Drag&Drop"

    BookmarkItem {
        id: bookmarkItem
        anchors.fill: parent
        anchors.margins: 20
        
        bookmarks: ["Home", "Documents", "Downloads", "Applications", "Settings", "Help"]
        currentIndex: 0
        bookmarkColor: "#3498db"
        textColor: "white"
        borderColor: "#2980b9"  // Синий цвет по умолчанию
        cornerRadius: 10
        padding: 12
        maxTextWidth: 150
        borderWidth: 2.0
        lineHeight: 2.0
        font.pixelSize: 12
        
        onBookmarkClicked: function(index) {
            console.log("Bookmark clicked:", index, bookmarks[index])
        }
        
        onBookmarksReordered: function(newOrder) {
            console.log("Bookmarks reordered:", newOrder)
        }
    }
}
```

**Новые возможности:**

1. **Перетаскивание закладок (Drag&Drop)**:
   - Нажмите и удерживайте закладку для начала перетаскивания
   - Перемещайте курсор для выбора новой позиции
   - Отпустите кнопку мыши для вставки закладки
   - Визуальная индикация места вставки

2. **Обводка активной закладки**:
   - Свойство `borderColor` (по умолчанию синий `#2980b9`)
   - Свойство `borderWidth` (по умолчанию 2.0)
   - Обводка рисуется только вокруг активной закладки

3. **Нижняя линия**:
   - Свойство `lineHeight` для настройки толщины линии
   - Линия рисуется тем же цветом и толщиной, что и обводка
   - Прерывается под активной закладкой, создавая визуальную связь
   - Находится ниже всех неактивных закладок, но под активной

**Визуальный эффект**:
Активная закладка теперь "выступает" над линией, а линия как бы "обходит" ее снизу, что создает интересный трехмерный эффект и четко выделяет текущую позицию.

Класс готов к использованию в Qt 6.9 с поддержкой всех запрошенных функций! 🎨
