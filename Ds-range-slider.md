Я создам для вас диапазонный слайдер на C++/Qt6 и QML. Вот полная реализация:

## C++ часть (RangeSlider.h)

```cpp
#ifndef RANGESLIDER_H
#define RANGESLIDER_H

#include <QObject>
#include <QQuickItem>
#include <QQuickPaintedItem>
#include <QPainter>
#include <QTimer>

class RangeSlider : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(double minimum READ minimum WRITE setMinimum NOTIFY minimumChanged)
    Q_PROPERTY(double maximum READ maximum WRITE setMaximum NOTIFY maximumChanged)
    Q_PROPERTY(double from READ from WRITE setFrom NOTIFY fromChanged)
    Q_PROPERTY(double to READ to WRITE setTo NOTIFY toChanged)
    Q_PROPERTY(QColor grooveColor READ grooveColor WRITE setGrooveColor NOTIFY grooveColorChanged)
    Q_PROPERTY(QColor rangeColor READ rangeColor WRITE setRangeColor NOTIFY rangeColorChanged)
    Q_PROPERTY(QColor handleColor READ handleColor WRITE setHandleColor NOTIFY handleColorChanged)
    Q_PROPERTY(bool active READ active WRITE setActive NOTIFY activeChanged)
    Q_PROPERTY(int handleWidth READ handleWidth WRITE setHandleWidth NOTIFY handleWidthChanged)

public:
    explicit RangeSlider(QQuickItem *parent = nullptr);

    void paint(QPainter *painter) override;

    double minimum() const;
    double maximum() const;
    double from() const;
    double to() const;
    QColor grooveColor() const;
    QColor rangeColor() const;
    QColor handleColor() const;
    bool active() const;
    int handleWidth() const;

public slots:
    void setMinimum(double minimum);
    void setMaximum(double maximum);
    void setFrom(double from);
    void setTo(double to);
    void setGrooveColor(QColor grooveColor);
    void setRangeColor(QColor rangeColor);
    void setHandleColor(QColor handleColor);
    void setActive(bool active);
    void setHandleWidth(int handleWidth);

signals:
    void minimumChanged(double minimum);
    void maximumChanged(double maximum);
    void fromChanged(double from);
    void toChanged(double to);
    void grooveColorChanged(QColor grooveColor);
    void rangeColorChanged(QColor rangeColor);
    void handleColorChanged(QColor handleColor);
    void activeChanged(bool active);
    void handleWidthChanged(int handleWidth);
    void rangeChanged(double from, double to);

protected:
    void mousePressEvent(QMouseEvent *event) override;
    void mouseMoveEvent(QMouseEvent *event) override;
    void mouseReleaseEvent(QMouseEvent *event) override;
    void geometryChanged(const QRectF &newGeometry, const QRectF &oldGeometry) override;

private:
    enum Handle { None, LeftHandle, RightHandle };

    double m_minimum;
    double m_maximum;
    double m_from;
    double m_to;
    QColor m_grooveColor;
    QColor m_rangeColor;
    QColor m_handleColor;
    bool m_active;
    int m_handleWidth;

    Handle m_pressedHandle;
    QPointF m_pressPos;
    double m_pressFrom;
    double m_pressTo;

    double valueFromPosition(double position) const;
    double positionFromValue(double value) const;
    void updateRange();
    void clampValues();
};

#endif // RANGESLIDER_H
```

## C++ реализация (RangeSlider.cpp)

```cpp
#include "RangeSlider.h"
#include <QMouseEvent>
#include <QDebug>

RangeSlider::RangeSlider(QQuickItem *parent)
    : QQuickPaintedItem(parent),
      m_minimum(0.0),
      m_maximum(100.0),
      m_from(20.0),
      m_to(80.0),
      m_grooveColor(Qt::gray),
      m_rangeColor(Qt::blue),
      m_handleColor(Qt::white),
      m_active(true),
      m_handleWidth(12),
      m_pressedHandle(None)
{
    setAcceptedMouseButtons(Qt::LeftButton);
    setAntialiasing(true);
}

void RangeSlider::paint(QPainter *painter)
{
    if (!m_active || width() <= 0 || height() <= 0)
        return;

    painter->setRenderHint(QPainter::Antialiasing);

    // Рисуем желобок (groove)
    QRectF grooveRect(0, height() / 2 - 2, width(), 4);
    painter->setPen(Qt::NoPen);
    painter->setBrush(m_grooveColor);
    painter->drawRoundedRect(grooveRect, 2, 2);

    // Рисуем выделенный диапазон
    double fromPos = positionFromValue(m_from);
    double toPos = positionFromValue(m_to);
    QRectF rangeRect(fromPos, height() / 2 - 2, toPos - fromPos, 4);
    painter->setBrush(m_rangeColor);
    painter->drawRoundedRect(rangeRect, 2, 2);

    // Рисуем ручки
    painter->setBrush(m_handleColor);
    
    // Левая ручка
    QRectF leftHandleRect(fromPos - m_handleWidth / 2, height() / 2 - 10, 
                         m_handleWidth, 20);
    painter->drawRoundedRect(leftHandleRect, 4, 4);
    
    // Правая ручка
    QRectF rightHandleRect(toPos - m_handleWidth / 2, height() / 2 - 10, 
                          m_handleWidth, 20);
    painter->drawRoundedRect(rightHandleRect, 4, 4);
}

double RangeSlider::minimum() const { return m_minimum; }
double RangeSlider::maximum() const { return m_maximum; }
double RangeSlider::from() const { return m_from; }
double RangeSlider::to() const { return m_to; }
QColor RangeSlider::grooveColor() const { return m_grooveColor; }
QColor RangeSlider::rangeColor() const { return m_rangeColor; }
QColor RangeSlider::handleColor() const { return m_handleColor; }
bool RangeSlider::active() const { return m_active; }
int RangeSlider::handleWidth() const { return m_handleWidth; }

void RangeSlider::setMinimum(double minimum)
{
    if (qFuzzyCompare(m_minimum, minimum))
        return;
    
    m_minimum = minimum;
    clampValues();
    update();
    emit minimumChanged(m_minimum);
}

void RangeSlider::setMaximum(double maximum)
{
    if (qFuzzyCompare(m_maximum, maximum))
        return;
    
    m_maximum = maximum;
    clampValues();
    update();
    emit maximumChanged(m_maximum);
}

void RangeSlider::setFrom(double from)
{
    if (qFuzzyCompare(m_from, from))
        return;
    
    m_from = from;
    clampValues();
    update();
    emit fromChanged(m_from);
    emit rangeChanged(m_from, m_to);
}

void RangeSlider::setTo(double to)
{
    if (qFuzzyCompare(m_to, to))
        return;
    
    m_to = to;
    clampValues();
    update();
    emit toChanged(m_to);
    emit rangeChanged(m_from, m_to);
}

void RangeSlider::setGrooveColor(QColor grooveColor)
{
    if (m_grooveColor == grooveColor)
        return;
    
    m_grooveColor = grooveColor;
    update();
    emit grooveColorChanged(m_grooveColor);
}

void RangeSlider::setRangeColor(QColor rangeColor)
{
    if (m_rangeColor == rangeColor)
        return;
    
    m_rangeColor = rangeColor;
    update();
    emit rangeColorChanged(m_rangeColor);
}

void RangeSlider::setHandleColor(QColor handleColor)
{
    if (m_handleColor == handleColor)
        return;
    
    m_handleColor = handleColor;
    update();
    emit handleColorChanged(m_handleColor);
}

void RangeSlider::setActive(bool active)
{
    if (m_active == active)
        return;
    
    m_active = active;
    update();
    emit activeChanged(m_active);
}

void RangeSlider::setHandleWidth(int handleWidth)
{
    if (m_handleWidth == handleWidth)
        return;
    
    m_handleWidth = handleWidth;
    update();
    emit handleWidthChanged(m_handleWidth);
}

void RangeSlider::mousePressEvent(QMouseEvent *event)
{
    if (!m_active)
        return;

    m_pressPos = event->position();
    m_pressFrom = m_from;
    m_pressTo = m_to;

    double fromPos = positionFromValue(m_from);
    double toPos = positionFromValue(m_to);

    // Определяем, какую ручку нажали
    if (qAbs(m_pressPos.x() - fromPos) <= m_handleWidth) {
        m_pressedHandle = LeftHandle;
    } else if (qAbs(m_pressPos.x() - toPos) <= m_handleWidth) {
        m_pressedHandle = RightHandle;
    } else if (m_pressPos.x() > fromPos && m_pressPos.x() < toPos) {
        // Нажали на диапазон - можно реализовать перемещение всего диапазона
        m_pressedHandle = None;
    } else {
        m_pressedHandle = None;
    }
}

void RangeSlider::mouseMoveEvent(QMouseEvent *event)
{
    if (!m_active || m_pressedHandle == None)
        return;

    double delta = valueFromPosition(event->position().x()) - 
                   valueFromPosition(m_pressPos.x());

    if (m_pressedHandle == LeftHandle) {
        setFrom(m_pressFrom + delta);
    } else if (m_pressedHandle == RightHandle) {
        setTo(m_pressTo + delta);
    }
}

void RangeSlider::mouseReleaseEvent(QMouseEvent *event)
{
    Q_UNUSED(event)
    m_pressedHandle = None;
}

void RangeSlider::geometryChanged(const QRectF &newGeometry, const QRectF &oldGeometry)
{
    QQuickPaintedItem::geometryChanged(newGeometry, oldGeometry);
    update();
}

double RangeSlider::valueFromPosition(double position) const
{
    if (width() <= 0)
        return m_minimum;
    
    double normalized = qBound(0.0, position / width(), 1.0);
    return m_minimum + normalized * (m_maximum - m_minimum);
}

double RangeSlider::positionFromValue(double value) const
{
    if (m_maximum <= m_minimum)
        return 0;
    
    double normalized = (value - m_minimum) / (m_maximum - m_minimum);
    return normalized * width();
}

void RangeSlider::updateRange()
{
    clampValues();
    update();
    emit rangeChanged(m_from, m_to);
}

void RangeSlider::clampValues()
{
    // Обеспечиваем корректные значения
    m_from = qBound(m_minimum, m_from, m_to);
    m_to = qBound(m_from, m_to, m_maximum);
}
```

## QML часть (RangeSlider.qml)

```qml
import QtQuick 2.15
import QtQuick.Controls 2.15

Item {
    id: root
    width: 300
    height: 40

    property alias minimum: rangeSlider.minimum
    property alias maximum: rangeSlider.maximum
    property alias from: rangeSlider.from
    property alias to: rangeSlider.to
    property alias grooveColor: rangeSlider.grooveColor
    property alias rangeColor: rangeSlider.rangeColor
    property alias handleColor: rangeSlider.handleColor
    property alias active: rangeSlider.active
    property alias handleWidth: rangeSlider.handleWidth

    signal rangeChanged(double from, double to)

    RangeSlider {
        id: rangeSlider
        anchors.fill: parent
        onRangeChanged: root.rangeChanged(from, to)
    }

    // Дополнительные элементы интерфейса (опционально)
    Row {
        anchors.top: parent.bottom
        anchors.topMargin: 5
        spacing: 10

        Text {
            text: "From: " + rangeSlider.from.toFixed(0)
            color: "white"
            font.pixelSize: 12
        }

        Text {
            text: "To: " + rangeSlider.to.toFixed(0)
            color: "white"
            font.pixelSize: 12
        }

        Text {
            text: "Range: " + (rangeSlider.to - rangeSlider.from).toFixed(0)
            color: "white"
            font.pixelSize: 12
        }
    }
}
```

## Пример использования (main.qml)

```qml
import QtQuick 2.15
import QtQuick.Window 2.15
import QtQuick.Controls 2.15

Window {
    width: 800
    height: 600
    visible: true
    title: "Range Slider Example"

    Rectangle {
        anchors.fill: parent
        color: "#2c3e50"

        Column {
            anchors.centerIn: parent
            spacing: 20

            RangeSlider {
                id: videoRangeSlider
                width: 600
                minimum: 0
                maximum: 1000
                from: 200
                to: 800
                grooveColor: "#34495e"
                rangeColor: "#3498db"
                handleColor: "#ecf0f1"
                
                onRangeChanged: {
                    console.log("Video frames selected:", from, "to", to)
                }
            }

            // Элементы управления
            Row {
                spacing: 10
                anchors.horizontalCenter: parent.horizontalCenter

                Button {
                    text: "Set Min/Max"
                    onClicked: {
                        videoRangeSlider.minimum = 0
                        videoRangeSlider.maximum = 5000
                    }
                }

                Button {
                    text: "Reset Range"
                    onClicked: {
                        videoRangeSlider.from = 1000
                        videoRangeSlider.to = 4000
                    }
                }
            }

            // Информация о выбранном диапазоне
            Text {
                anchors.horizontalCenter: parent.horizontalCenter
                text: `Selected frames: ${videoRangeSlider.from.toFixed(0)} - ${videoRangeSlider.to.toFixed(0)} (${(videoRangeSlider.to - videoRangeSlider.from).toFixed(0)} frames)`
                color: "white"
                font.pixelSize: 16
            }
        }
    }
}
```

## Регистрация типа в main.cpp

```cpp
#include <QGuiApplication>
#include <QQmlApplicationEngine>
#include <QQmlContext>
#include "RangeSlider.h"

int main(int argc, char *argv[])
{
    QGuiApplication app(argc, argv);

    qmlRegisterType<RangeSlider>("CustomControls", 1, 0, "RangeSlider");

    QQmlApplicationEngine engine;
    const QUrl url(u"qrc:/main.qml"_qs);
    
    QObject::connect(&engine, &QQmlApplicationEngine::objectCreated,
                     &app, [url](QObject *obj, const QUrl &objUrl) {
        if (!obj && url == objUrl)
            QCoreApplication::exit(-1);
    }, Qt::QueuedConnection);
    
    engine.load(url);

    return app.exec();
}
```

## CMakeLists.txt (фрагмент)

```cmake
qt6_add_qml_module(app
    URI RangeSliderExample
    VERSION 1.0
    QML_FILES
        main.qml
        RangeSlider.qml
    SOURCES
        main.cpp
        RangeSlider.cpp
    HEADERS
        RangeSlider.h
)
```

Этот диапазонный слайдер предоставляет:

1. **Полный функционал на C++** - обработка событий мыши, вычисления позиций, валидация значений
2. **Гибкие настройки** - цвета, размеры, активное/неактивное состояние
3. **Сигналы изменений** - уведомления об изменении диапазона
4. **Валидацию значений** - автоматическое ограничение значений в допустимых пределах
5. **Антиалиасинг** - сглаженное отображение элементов

Вы можете легко настроить внешний вид через QML свойства и использовать слайдер для выделения диапазонов кадров в видеообработке.
