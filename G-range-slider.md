```cpp
// rangeselector.h
#ifndef RANGESELECTOR_H
#define RANGESELECTOR_H

#include <QQuickItem>
#include <QObject>

class RangeSelector : public QQuickItem
{
    Q_OBJECT
    Q_PROPERTY(double minimum READ minimum WRITE setMinimum NOTIFY minimumChanged)
    Q_PROPERTY(double maximum READ maximum WRITE setMaximum NOTIFY maximumChanged)
    Q_PROPERTY(double start READ start WRITE setStart NOTIFY startChanged)
    Q_PROPERTY(double end READ end WRITE setEnd NOTIFY endChanged)
    Q_PROPERTY(bool startDragging READ startDragging NOTIFY startDraggingChanged)
    Q_PROPERTY(bool endDragging READ endDragging NOTIFY endDraggingChanged)

public:
    explicit RangeSelector(QQuickItem *parent = nullptr);

    double minimum() const { return m_minimum; }
    void setMinimum(double min);

    double maximum() const { return m_maximum; }
    void setMaximum(double max);

    double start() const { return m_start; }
    void setStart(double start);

    double end() const { return m_end; }
    void setEnd(double end);

    bool startDragging() const { return m_startDragging; }
    bool endDragging() const { return m_endDragging; }

signals:
    void minimumChanged();
    void maximumChanged();
    void startChanged();
    void endChanged();
    void startDraggingChanged();
    void endDraggingChanged();

private:
    double m_minimum = 0.0;
    double m_maximum = 100.0;
    double m_start = 0.0;
    double m_end = 100.0;
    bool m_startDragging = false;
    bool m_endDragging = false;
};

#endif // RANGESELECTOR_H
```

```cpp
// rangeselector.cpp
#include "rangeselector.h"
#include <QDebug>

RangeSelector::RangeSelector(QQuickItem *parent)
    : QQuickItem(parent)
{
    setFlag(ItemHasContents, true); // Enables painting if needed, but we'll keep it transparent for QML overlay
}

void RangeSelector::setMinimum(double min)
{
    if (qFuzzyCompare(m_minimum, min)) return;
    m_minimum = min;
    // Clamp start and end to new minimum
    if (m_start < min) {
        m_start = min;
        emit startChanged();
    }
    if (m_end < min) {
        m_end = min;
        emit endChanged();
    }
    emit minimumChanged();
}

void RangeSelector::setMaximum(double max)
{
    if (qFuzzyCompare(m_maximum, max)) return;
    m_maximum = max;
    // Clamp start and end to new maximum
    if (m_start > max) {
        m_start = max;
        emit startChanged();
    }
    if (m_end > max) {
        m_end = max;
        emit endChanged();
    }
    emit maximumChanged();
}

void RangeSelector::setStart(double start)
{
    if (qFuzzyCompare(m_start, start)) return;
    // Clamp to [minimum, end]
    double clamped = qMax(m_minimum, qMin(m_end, start));
    if (qFuzzyCompare(m_start, clamped)) return;
    m_start = clamped;
    emit startChanged();
    if (m_startDragging) {
        emit startDraggingChanged(); // To trigger QML visual feedback if needed
    }
}

void RangeSelector::setEnd(double end)
{
    if (qFuzzyCompare(m_end, end)) return;
    // Clamp to [start, maximum]
    double clamped = qMax(m_start, qMin(m_maximum, end));
    if (qFuzzyCompare(m_end, clamped)) return;
    m_end = clamped;
    emit endChanged();
    if (m_endDragging) {
        emit endDraggingChanged(); // To trigger QML visual feedback if needed
    }
}
```

```qml
// RangeSlider.qml
import QtQuick 2.15
import QtQuick.Controls 2.15

Item {
    id: root

    // Expose C++ properties to QML
    property alias minimum: selector.minimum
    property alias maximum: selector.maximum
    property alias start: selector.start
    property alias end: selector.end
    property alias startDragging: selector.startDragging
    property alias endDragging: selector.endDragging

    // Default values
    property int handleSize: 20
    property color trackColor: "#cccccc"
    property color selectedColor: "#4CAF50"
    property color handleColor: "#2196F3"

    implicitWidth: 300
    implicitHeight: 40

    // Embed the C++ backend
    RangeSelector {
        id: selector
        anchors.fill: parent
        minimum: 0
        maximum: 100
        start: 20
        end: 80
    }

    // Track background
    Rectangle {
        id: track
        anchors.verticalCenter: parent.verticalCenter
        width: parent.width - handleSize * 2
        height: 4
        anchors.horizontalCenter: parent.horizontalCenter
        radius: height / 2
        color: trackColor
    }

    // Selected range fill
    Rectangle {
        id: selection
        anchors.verticalCenter: parent.verticalCenter
        height: 4
        radius: height / 2
        color: selectedColor
        x: leftHandle.x + leftHandle.width / 2
        width: rightHandle.x - leftHandle.x
        visible: selector.start < selector.end
    }

    // Left handle (for start)
    Rectangle {
        id: leftHandle
        width: handleSize
        height: handleSize
        radius: handleSize / 2
        color: parent.startDragging ? Qt.lighter(handleColor, 1.2) : handleColor
        anchors.verticalCenter: parent.verticalCenter
        x: ((selector.start - selector.minimum) / (selector.maximum - selector.minimum)) * (parent.width - handleSize * 2) + handleSize / 2

        MouseArea {
            id: leftMouseArea
            anchors.fill: parent
            drag.target: parent
            drag.axis: Drag.XAxis
            drag.minimumX: 0
            drag.maximumX: parent.parent.width - handleSize
            drag.threshold: 0 // Immediate drag

            onPressed: {
                selector.startDragging = true
                parent.color = Qt.darker(parent.color, 1.1)
            }
            onReleased: {
                selector.startDragging = false
                parent.color = handleColor
            }
            onPositionChanged: {
                if (drag.active) {
                    var normalizedX = (mouse.x + parent.x - handleSize / 2) / (root.width - handleSize * 2)
                    var newStart = selector.minimum + normalizedX * (selector.maximum - selector.minimum)
                    selector.start = newStart
                }
            }
        }
    }

    // Right handle (for end)
    Rectangle {
        id: rightHandle
        width: handleSize
        height: handleSize
        radius: handleSize / 2
        color: parent.endDragging ? Qt.lighter(handleColor, 1.2) : handleColor
        anchors.verticalCenter: parent.verticalCenter
        x: ((selector.end - selector.minimum) / (selector.maximum - selector.minimum)) * (root.width - handleSize * 2) + handleSize / 2

        MouseArea {
            id: rightMouseArea
            anchors.fill: parent
            drag.target: parent
            drag.axis: Drag.XAxis
            drag.minimumX: 0
            drag.maximumX: parent.parent.width - handleSize
            drag.threshold: 0

            onPressed: {
                selector.endDragging = true
                parent.color = Qt.darker(parent.color, 1.1)
            }
            onReleased: {
                selector.endDragging = false
                parent.color = handleColor
            }
            onPositionChanged: {
                if (drag.active) {
                    var normalizedX = (mouse.x + parent.x - handleSize / 2) / (root.width - handleSize * 2)
                    var newEnd = selector.minimum + normalizedX * (selector.maximum - selector.minimum)
                    selector.end = newEnd
                }
            }
        }
    }

    // Connections to update handles when values change externally
    Connections {
        target: selector
        function onStartChanged() { leftHandle.x = ((selector.start - selector.minimum) / (selector.maximum - selector.minimum)) * (root.width - handleSize * 2) + handleSize / 2; }
        function onEndChanged() { rightHandle.x = ((selector.end - selector.minimum) / (selector.maximum - selector.minimum)) * (root.width - handleSize * 2) + handleSize / 2; }
        function onStartDraggingChanged() { leftHandle.color = selector.startDragging ? Qt.lighter(handleColor, 1.2) : handleColor; }
        function onEndDraggingChanged() { rightHandle.color = selector.endDragging ? Qt.lighter(handleColor, 1.2) : handleColor; }
    }
}
```

### Инструкции по использованию

1. **Сборка C++ части**: 
   - Создайте проект Qt Quick Application в Qt Creator (Qt 6).
   - Добавьте файлы `rangeselector.h` и `rangeselector.cpp` в проект.
   - В `main.cpp` зарегистрируйте класс для QML:
     ```cpp
     #include <QGuiApplication>
     #include <QQmlApplicationEngine>
     #include "rangeselector.h"

     int main(int argc, char *argv[])
     {
         QGuiApplication app(argc, argv);
         qmlRegisterType<RangeSelector>("CustomControls", 1, 0, "RangeSelector");

         QQmlApplicationEngine engine;
         engine.load(QUrl(QStringLiteral("qrc:/main.qml")));
         if (engine.rootObjects().isEmpty())
             return -1;

         return app.exec();
     }
     ```
   - Убедитесь, что `QT += quick` в `.pro` файле.

2. **Использование в QML** (например, в `main.qml`):
   ```qml
   import QtQuick
   import QtQuick.Window
   import CustomControls 1.0  // Если вы используете namespace

   Window {
       width: 400
       height: 200
       visible: true

       RangeSlider {
           id: slider
           anchors.centerIn: parent
           minimum: 0
           maximum: 1000  // Например, общее количество кадров в видео
           start: 100     // Начало диапазона
           end: 500       // Конец диапазона

           onStartChanged: console.log("Start changed to:", start)
           onEndChanged: console.log("End changed to:", end)
       }
   }
   ```

### Описание функционала
- **C++ логика**: Обработка свойств с автоматическим clamping (начало ≥ минимум, конец ≤ максимум, начало ≤ конец). Сигналы для уведомлений об изменениях. Свойства `startDragging` и `endDragging` для визуальной обратной связи во время перетаскивания.
- **QML отрисовка**: Простая визуализация с дорожкой, выделенным диапазоном и двумя ручками. Перетаскивание ручек обновляет значения через C++ сеттеры. Поддержка тач/мыши.
- **Расширение**: Для видео-приложения подключите `start` и `end` к модели видео (например, через QMediaPlayer или FFmpeg). Добавьте анимации или стили в QML по необходимости.

Этот контрол готов к использованию и соответствует требованиям: вся валидация и свойства в C++, отрисовка и взаимодействие в QML.
