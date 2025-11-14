Вот реализация класса на основе QQuickPaintedItem для отображения waveform с требуемым функционалом:

**WaveformItem.h**
```cpp
#ifndef WAVEFORMITEM_H
#define WAVEFORMITEM_H

#include <QQuickPaintedItem>
#include <QImage>
#include <QPointF>
#include <QTimer>

class WaveformItem : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(QString imageSource READ imageSource WRITE setImageSource NOTIFY imageSourceChanged)
    Q_PROPERTY(int channelCount READ channelCount WRITE setChannelCount NOTIFY channelCountChanged)
    Q_PROPERTY(int currentChannel READ currentChannel WRITE setCurrentChannel NOTIFY currentChannelChanged)
    Q_PROPERTY(qreal stretchFactor READ stretchFactor WRITE setStretchFactor NOTIFY stretchFactorChanged)
    Q_PROPERTY(qreal stretchPivot READ stretchPivot WRITE setStretchPivot NOTIFY stretchPivotChanged)

public:
    explicit WaveformItem(QQuickItem *parent = nullptr);

    QString imageSource() const;
    void setImageSource(const QString &source);

    int channelCount() const;
    void setChannelCount(int count);

    int currentChannel() const;
    void setCurrentChannel(int channel);

    qreal stretchFactor() const;
    void setStretchFactor(qreal factor);

    qreal stretchPivot() const;
    void setStretchPivot(qreal pivot);

    void paint(QPainter *painter) override;

protected:
    void mousePressEvent(QMouseEvent *event) override;
    void mouseMoveEvent(QMouseEvent *event) override;
    void mouseReleaseEvent(QMouseEvent *event) override;
    void wheelEvent(QWheelEvent *event) override;

private slots:
    void loadImage();

private:
    void updateSourceRect();

    QString m_imageSource;
    QImage m_waveformImage;
    int m_channelCount;
    int m_currentChannel;
    qreal m_stretchFactor;
    qreal m_stretchPivot;
    
    // Прокрутка
    qreal m_scrollOffset;
    QPointF m_lastMousePos;
    bool m_isDragging;
    
    // Область исходного изображения для отрисовки
    QRectF m_sourceRect;
};

#endif // WAVEFORMITEM_H
```

**WaveformItem.cpp**
```cpp
#include "WaveformItem.h"
#include <QPainter>
#include <QMouseEvent>
#include <QWheelEvent>
#include <QFileInfo>

WaveformItem::WaveformItem(QQuickItem *parent)
    : QQuickPaintedItem(parent)
    , m_channelCount(1)
    , m_currentChannel(0)
    , m_stretchFactor(1.0)
    , m_stretchPivot(0.0)
    , m_scrollOffset(0.0)
    , m_isDragging(false)
{
    setAcceptedMouseButtons(Qt::LeftButton);
    setAcceptHoverEvents(true);
}

QString WaveformItem::imageSource() const
{
    return m_imageSource;
}

void WaveformItem::setImageSource(const QString &source)
{
    if (m_imageSource == source)
        return;

    m_imageSource = source;
    
    // Загружаем изображение асинхронно
    QTimer::singleShot(0, this, &WaveformItem::loadImage);
    
    emit imageSourceChanged();
}

int WaveformItem::channelCount() const
{
    return m_channelCount;
}

void WaveformItem::setChannelCount(int count)
{
    if (m_channelCount == count || count <= 0)
        return;

    m_channelCount = count;
    update();
    emit channelCountChanged();
}

int WaveformItem::currentChannel() const
{
    return m_currentChannel;
}

void WaveformItem::setCurrentChannel(int channel)
{
    if (m_currentChannel == channel || channel < 0 || channel >= m_channelCount)
        return;

    m_currentChannel = channel;
    update();
    emit currentChannelChanged();
}

qreal WaveformItem::stretchFactor() const
{
    return m_stretchFactor;
}

void WaveformItem::setStretchFactor(qreal factor)
{
    if (qFuzzyCompare(m_stretchFactor, factor) || factor <= 0)
        return;

    m_stretchFactor = factor;
    updateSourceRect();
    update();
    emit stretchFactorChanged();
}

qreal WaveformItem::stretchPivot() const
{
    return m_stretchPivot;
}

void WaveformItem::setStretchPivot(qreal pivot)
{
    if (qFuzzyCompare(m_stretchPivot, pivot))
        return;

    m_stretchPivot = pivot;
    updateSourceRect();
    update();
    emit stretchPivotChanged();
}

void WaveformItem::paint(QPainter *painter)
{
    if (m_waveformImage.isNull() || m_channelCount <= 0)
        return;

    // Вычисляем высоту одной полосы канала
    int channelHeight = m_waveformImage.height() / m_channelCount;
    if (channelHeight <= 0)
        return;

    // Определяем область исходного изображения для текущего канала
    QRect sourceChannelRect(0, m_currentChannel * channelHeight, 
                           m_waveformImage.width(), channelHeight);

    // Рисуем растянутую и смещенную область
    painter->setRenderHint(QPainter::SmoothPixmapTransform);
    painter->drawImage(boundingRect(), m_waveformImage, m_sourceRect.intersected(sourceChannelRect));
}

void WaveformItem::mousePressEvent(QMouseEvent *event)
{
    if (event->button() == Qt::LeftButton) {
        m_isDragging = true;
        m_lastMousePos = event->position();
        setCursor(Qt::ClosedHandCursor);
    }
}

void WaveformItem::mouseMoveEvent(QMouseEvent *event)
{
    if (m_isDragging) {
        QPointF delta = event->position() - m_lastMousePos;
        m_lastMousePos = event->position();
        
        // Обновляем смещение с учетом растяжения
        m_scrollOffset -= delta.x() / m_stretchFactor;
        
        // Ограничиваем смещение в пределах изображения
        qreal maxOffset = m_waveformImage.width() - width() / m_stretchFactor;
        m_scrollOffset = qBound(0.0, m_scrollOffset, qMax(0.0, maxOffset));
        
        updateSourceRect();
        update();
    }
}

void WaveformItem::mouseReleaseEvent(QMouseEvent *event)
{
    if (event->button() == Qt::LeftButton) {
        m_isDragging = false;
        setCursor(Qt::ArrowCursor);
    }
}

void WaveformItem::wheelEvent(QWheelEvent *event)
{
    // Прокрутка колесиком для тонкой настройки
    QPoint numPixels = event->pixelDelta();
    QPoint numDegrees = event->angleDelta() / 8;
    
    qreal delta = 0.0;
    if (!numPixels.isNull()) {
        delta = numPixels.x();
    } else if (!numDegrees.isNull()) {
        delta = numDegrees.x() / 15.0; // Обычно 1 градус = 1 пиксель
    }
    
    if (delta != 0.0) {
        m_scrollOffset -= delta / m_stretchFactor;
        qreal maxOffset = m_waveformImage.width() - width() / m_stretchFactor;
        m_scrollOffset = qBound(0.0, m_scrollOffset, qMax(0.0, maxOffset));
        
        updateSourceRect();
        update();
    }
    
    event->accept();
}

void WaveformItem::loadImage()
{
    if (m_imageSource.isEmpty()) {
        m_waveformImage = QImage();
        update();
        return;
    }

    // Загружаем изображение (поддерживаются как ресурсы, так и файлы)
    if (m_imageSource.startsWith("qrc:/")) {
        m_waveformImage.load(m_imageSource.mid(3)); // Убираем "qrc"
    } else if (QFileInfo::exists(m_imageSource)) {
        m_waveformImage.load(m_imageSource);
    }

    if (!m_waveformImage.isNull()) {
        m_scrollOffset = 0.0;
        updateSourceRect();
    }
    
    update();
}

void WaveformItem::updateSourceRect()
{
    if (m_waveformImage.isNull()) {
        m_sourceRect = QRectF();
        return;
    }

    // Вычисляем видимую область с учетом растяжения и смещения
    qreal visibleWidth = width() / m_stretchFactor;
    
    // Вычисляем смещение относительно точки растяжения
    qreal pivotOffset = m_stretchPivot * m_waveformImage.width();
    qreal scrollWithPivot = m_scrollOffset + pivotOffset * (1.0 - 1.0 / m_stretchFactor);
    
    m_sourceRect = QRectF(scrollWithPivot, 0, visibleWidth, m_waveformImage.height());
}
```

**Пример использования в QML:**
```qml
import QtQuick 2.15
import QtQuick.Controls 2.15

Item {
    width: 800
    height: 200

    WaveformItem {
        id: waveform
        anchors.fill: parent
        imageSource: "path/to/waveform.png"
        channelCount: 2
        currentChannel: channelComboBox.currentIndex
        stretchFactor: stretchSlider.value
        stretchPivot: pivotSlider.value
    }

    Column {
        Row {
            ComboBox {
                id: channelComboBox
                model: ["Channel 1", "Channel 2"]
            }
            
            Slider {
                id: stretchSlider
                from: 0.1
                to: 5.0
                value: 1.0
            }
            
            Text { text: "Stretch: " + stretchSlider.value.toFixed(2) }
        }
        
        Row {
            Slider {
                id: pivotSlider
                from: 0.0
                to: 1.0
                value: 0.0
            }
            
            Text { text: "Pivot: " + pivotSlider.value.toFixed(2) }
        }
    }
}
```

**Основные особенности реализации:**

1. **Загрузка waveform изображения** - поддерживает загрузку из файлов и ресурсов Qt
2. **Выбор канала** - отображает только один указанный канал из многодорожечного waveform
3. **Горизонтальная прокрутка** - перетаскивание мышью для скроллинга
4. **Растяжение с произвольной точкой** - растягивает изображение относительно указанной точки (0.0 - начало, 1.0 - конец)
5. **Плавное отображение** - использует сглаживание при растяжении
6. **Ограничение прокрутки** - предотвращает выход за границы изображения

Класс полностью готов к использованию в Qt6 приложениях с QML интерфейсом.
