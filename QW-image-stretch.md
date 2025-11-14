Вот пример реализации класса `WaveformItem`, унаследованного от `QQuickPaintedItem`, который:

- загружает изображение с несколькими горизонтальными полосами (каналами),
- рисует указанный канал (по индексу),
- поддерживает горизонтальную прокрутку мышью (drag),
- поддерживает масштабирование по X относительно произвольной точки.

---

## ✅ **Заголовочный файл (`waveformitem.h`)**
```cpp
#ifndef WAVEFORMITEM_H
#define WAVEFORMITEM_H

#include <QQuickPaintedItem>
#include <QImage>
#include <QMouseEvent>
#include <QRectF>

class WaveformItem : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(int channelCount READ channelCount WRITE setChannelCount NOTIFY channelCountChanged)
    Q_PROPERTY(int currentChannel READ currentChannel WRITE setCurrentChannel NOTIFY currentChannelChanged)
    Q_PROPERTY(QString imagePath READ imagePath WRITE setImagePath NOTIFY imagePathChanged)
    Q_PROPERTY(qreal scaleX READ scaleX WRITE setScaleX NOTIFY scaleXChanged)
    Q_PROPERTY(qreal offsetX READ offsetX WRITE setOffsetX NOTIFY offsetxChanged)

public:
    explicit WaveformItem(QQuickItem *parent = nullptr);

    void paint(QPainter *painter) override;

    int channelCount() const { return m_channelCount; }
    void setChannelCount(int count);

    int currentChannel() const { return m_currentChannel; }
    void setCurrentChannel(int channel);

    QString imagePath() const { return m_imagePath; }
    void setImagePath(const QString &path);

    qreal scaleX() const { return m_scaleX; }
    void setScaleX(qreal scale);

    qreal offsetX() const { return m_offsetX; }
    void setOffsetX(qreal offset);

    // Для масштабирования относительно произвольной точки:
    Q_INVOKABLE void zoomAt(qreal factor, qreal anchorX);

signals:
    void channelCountChanged();
    void currentChannelChanged();
    void imagePathChanged();
    void scaleXChanged();
    void offsetxChanged();

protected:
    void mousePressEvent(QMouseEvent *event) override;
    void mouseMoveEvent(QMouseEvent *event) override;
    void mouseReleaseEvent(QMouseEvent *event) override;
    void wheelEvent(QWheelEvent *event) override;

private:
    int m_channelCount = 1;
    int m_currentChannel = 0;
    QString m_imagePath;
    QImage m_image;
    qreal m_scaleX = 1.0;
    qreal m_offsetX = 0.0;

    // Для drag
    bool m_dragging = false;
    QPoint m_lastMousePos;
};

#endif // WAVEFORMITEM_H
```

---

## ✅ **Исходный файл (`waveformitem.cpp`)**
```cpp
#include "waveformitem.h"
#include <QPainter>
#include <QMouseEvent>
#include <QWheelEvent>
#include <QFile>

WaveformItem::WaveformItem(QQuickItem *parent)
    : QQuickPaintedItem(parent)
{
    setAcceptedMouseButtons(Qt::LeftButton | Qt::RightButton);
    setFlag(ItemAcceptsInputMethod, true);
}

void WaveformItem::setChannelCount(int count)
{
    if (m_channelCount == count) return;
    m_channelCount = qMax(1, count);
    update();
    emit channelCountChanged();
}

void WaveformItem::setCurrentChannel(int channel)
{
    if (m_currentChannel == channel) return;
    m_currentChannel = qBound(0, channel, m_channelCount - 1);
    update();
    emit currentChannelChanged();
}

void WaveformItem::setImagePath(const QString &path)
{
    if (m_imagePath == path) return;

    m_imagePath = path;
    if (QFile::exists(path)) {
        m_image = QImage(path);
        if (m_image.isNull()) {
            qWarning() << "Failed to load image:" << path;
        }
    } else {
        m_image = QImage();
        qWarning() << "Image file does not exist:" << path;
    }

    update();
    emit imagePathChanged();
}

void WaveformItem::setScaleX(qreal scale)
{
    if (qFuzzyCompare(m_scaleX, scale)) return;
    m_scaleX = qMax(0.01, scale);
    update();
    emit scaleXChanged();
}

void WaveformItem::setOffsetX(qreal offset)
{
    if (qFuzzyCompare(m_offsetX, offset)) return;
    m_offsetX = offset;
    update();
    emit offsetxChanged();
}

void WaveformItem::zoomAt(qreal factor, qreal anchorX)
{
    // Сохраняем координату точки anchorX в исходной системе координат изображения до zoom
    qreal oldImageX = (anchorX - m_offsetX) / m_scaleX;

    // Меняем масштаб
    setScaleX(m_scaleX * factor);

    // Пересчитываем offsetX так, чтобы точка anchorX осталась на месте
    qreal newImageX = oldImageX;
    qreal newOffsetX = anchorX - newImageX * m_scaleX;

    setOffsetX(newOffsetX);
}

void WaveformItem::paint(QPainter *painter)
{
    if (m_image.isNull() || m_channelCount <= 0 || width() <= 0 || height() <= 0)
        return;

    int channels = m_channelCount;
    int channelHeight = m_image.height() / channels;

    // Извлекаем полосу нужного канала
    QRect srcRect(0, m_currentChannel * channelHeight, m_image.width(), channelHeight);
    QImage channelImage = m_image.copy(srcRect);

    // Масштабируем и смещаем
    QRectF destRect;
    destRect.setWidth(channelImage.width() * m_scaleX);
    destRect.setHeight(height());
    destRect.moveTopLeft(QPointF(m_offsetX, 0));

    // Сжимаем/растягиваем по вертикали на высоту item'а
    painter->drawImage(destRect, channelImage);
}

// --- Mouse Drag ---
void WaveformItem::mousePressEvent(QMouseEvent *event)
{
    if (event->button() == Qt::LeftButton) {
        m_dragging = true;
        m_lastMousePos = event->pos();
        setCursor(Qt::ClosedHandCursor);
    }
    event->accept();
}

void WaveformItem::mouseMoveEvent(QMouseEvent *event)
{
    if (m_dragging) {
        QPoint delta = event->pos() - m_lastMousePos;
        setOffsetX(m_offsetX + delta.x());
        m_lastMousePos = event->pos();
    }
    event->accept();
}

void WaveformItem::mouseReleaseEvent(QMouseEvent *event)
{
    if (event->button() == Qt::LeftButton) {
        m_dragging = false;
        setCursor(Qt::ArrowCursor);
    }
    event->accept();
}

// --- Zoom by wheel ---
void WaveformItem::wheelEvent(QWheelEvent *event)
{
    if (event->modifiers() & Qt::ControlModifier) {
        // Zoom относительно позиции курсора
        qreal factor = 1.0 + (event->angleDelta().y() > 0 ? 0.1 : -0.1);
        zoomAt(factor, event->position().x());
        event->accept();
    } else {
        // Прокрутка по X
        setOffsetX(m_offsetX - event->angleDelta().y() * 0.5);
        event->accept();
    }
}
```

---

## ✅ **Регистрация в `main.cpp`**
```cpp
#include <QGuiApplication>
#include <QQmlApplicationEngine>
#include <QtQml>
#include "waveformitem.h"

int main(int argc, char *argv[])
{
    QGuiApplication app(argc, argv);

    qmlRegisterType<WaveformItem>("WaveformModule", 1, 0, "WaveformItem");

    QQmlApplicationEngine engine;
    engine.load(QUrl(QStringLiteral("qrc:/main.qml")));
    if (engine.rootObjects().isEmpty())
        return -1;

    return app.exec();
}
```

---

## ✅ **Пример использования в QML (`main.qml`)**
```qml
import QtQuick 2.15
import QtQuick.Controls 2.15
import WaveformModule 1.0

ApplicationWindow {
    visible: true
    width: 800
    height: 200
    title: "Waveform Viewer"

    WaveformItem {
        id: waveform
        anchors.fill: parent
        channelCount: 2
        currentChannel: 0
        imagePath: "qrc:/waveform.png"  // должен быть высотой, кратной channelCount
    }

    MouseArea {
        anchors.fill: parent
        acceptedButtons: Qt.RightButton
        onClicked: {
            waveform.currentChannel = (waveform.currentChannel + 1) % waveform.channelCount
        }
    }
}
```

---

## 🔍 Примечания:

- **Изображение waveform**: каждая полоса — один канал. Например, при 2 каналах и изображении высотой 200px — каждая полоса по 100px.
- **offsetX** — смещение изображения по X (в пикселях в экранных координатах).
- **scaleX** — масштаб по X (1.0 = исходный размер).
- **zoomAt(factor, anchorX)** — ключевой метод для zoom относительно точки. Можно вызывать из QML при колесике/кнопках.
- Прокрутка колесиком без `Ctrl` — горизонтальный скролл; с `Ctrl` — зум относительно курсора.

---

Если нужно — могу добавить поддержку:
- вертикального масштаба (амплитуды),
- подсветки участков (например, выделенного фрагмента),
- кэширования каналов для ускорения,
- загрузки по частям (для больших изображений).

Готов доработать под ваши нужды 👍
