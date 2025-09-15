# Улучшенный диапазонный слайдер для Qt6/QML

Спасибо за оценку! Я рад, что код вам понравился. Давайте создадим еще более продвинутую версию диапазонного слайдера с дополнительными функциями и улучшенным пользовательским интерфейсом.

## Улучшенная реализация C++ (AdvancedRangeSlider.h)

```cpp
#ifndef ADVANCEDRANGESLIDER_H
#define ADVANCEDRANGESLIDER_H

#include <QObject>
#include <QQuickItem>
#include <QQuickPaintedItem>
#include <QPainter>
#include <QTimer>
#include <QTime>

class AdvancedRangeSlider : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(double minimum READ minimum WRITE setMinimum NOTIFY minimumChanged)
    Q_PROPERTY(double maximum READ maximum WRITE setMaximum NOTIFY maximumChanged)
    Q_PROPERTY(double from READ from WRITE setFrom NOTIFY fromChanged)
    Q_PROPERTY(double to READ to WRITE setTo NOTIFY toChanged)
    Q_PROPERTY(QColor grooveColor READ grooveColor WRITE setGrooveColor NOTIFY grooveColorChanged)
    Q_PROPERTY(QColor rangeColor READ rangeColor WRITE setRangeColor NOTIFY rangeColorChanged)
    Q_PROPERTY(QColor handleColor READ handleColor WRITE setHandleColor NOTIFY handleColorChanged)
    Q_PROPERTY(QColor textColor READ textColor WRITE setTextColor NOTIFY textColorChanged)
    Q_PROPERTY(bool active READ active WRITE setActive NOTIFY activeChanged)
    Q_PROPERTY(int handleWidth READ handleWidth WRITE setHandleWidth NOTIFY handleWidthChanged)
    Q_PROPERTY(bool showValues READ showValues WRITE setShowValues NOTIFY showValuesChanged)
    Q_PROPERTY(bool snapToWholeNumbers READ snapToWholeNumbers WRITE setSnapToWholeNumbers NOTIFY snapToWholeNumbersChanged)
    Q_PROPERTY(QString timeFormat READ timeFormat WRITE setTimeFormat NOTIFY timeFormatChanged)
    Q_PROPERTY(bool showTime READ showTime WRITE setShowTime NOTIFY showTimeChanged)

public:
    explicit AdvancedRangeSlider(QQuickItem *parent = nullptr);

    void paint(QPainter *painter) override;

    double minimum() const;
    double maximum() const;
    double from() const;
    double to() const;
    QColor grooveColor() const;
    QColor rangeColor() const;
    QColor handleColor() const;
    QColor textColor() const;
    bool active() const;
    int handleWidth() const;
    bool showValues() const;
    bool snapToWholeNumbers() const;
    QString timeFormat() const;
    bool showTime() const;

public slots:
    void setMinimum(double minimum);
    void setMaximum(double maximum);
    void setFrom(double from);
    void setTo(double to);
    void setGrooveColor(QColor grooveColor);
    void setRangeColor(QColor rangeColor);
    void setHandleColor(QColor handleColor);
    void setTextColor(QColor textColor);
    void setActive(bool active);
    void setHandleWidth(int handleWidth);
    void setShowValues(bool show);
    void setSnapToWholeNumbers(bool snap);
    void setTimeFormat(QString format);
    void setShowTime(bool show);
    void resetRange();

    // Методы для работы со временем
    Q_INVOKABLE QString formatTime(double value) const;
    Q_INVOKABLE void setRange(double from, double to);

signals:
    void minimumChanged(double minimum);
    void maximumChanged(double maximum);
    void fromChanged(double from);
    void toChanged(double to);
    void grooveColorChanged(QColor grooveColor);
    void rangeColorChanged(QColor rangeColor);
    void handleColorChanged(QColor handleColor);
    void textColorChanged(QColor textColor);
    void activeChanged(bool active);
    void handleWidthChanged(int handleWidth);
    void showValuesChanged(bool show);
    void snapToWholeNumbersChanged(bool snap);
    void timeFormatChanged(QString format);
    void showTimeChanged(bool show);
    void rangeChanged(double from, double to);
    void userInteractionStarted();
    void userInteractionFinished();

protected:
    void mousePressEvent(QMouseEvent *event) override;
    void mouseMoveEvent(QMouseEvent *event) override;
    void mouseReleaseEvent(QMouseEvent *event) override;
    void geometryChanged(const QRectF &newGeometry, const QRectF &oldGeometry) override;
    void hoverMoveEvent(QHoverEvent *event) override;

private:
    enum Handle { None, LeftHandle, RightHandle, WholeRange };

    double m_minimum;
    double m_maximum;
    double m_from;
    double m_to;
    QColor m_grooveColor;
    QColor m_rangeColor;
    QColor m_handleColor;
    QColor m_textColor;
    bool m_active;
    int m_handleWidth;
    bool m_showValues;
    bool m_snapToWholeNumbers;
    QString m_timeFormat;
    bool m_showTime;

    Handle m_pressedHandle;
    QPointF m_pressPos;
    double m_pressFrom;
    double m_pressTo;
    double m_hoverPos;

    double valueFromPosition(double position) const;
    double positionFromValue(double value) const;
    void updateRange();
    void clampValues();
    double snapValue(double value) const;
    void drawHandle(QPainter *painter, double position, bool isHovered);
    void drawValueTooltip(QPainter *painter, double value, double position);
};

#endif // ADVANCEDRANGESLIDER_H
```

## Улучшенная реализация C++ (AdvancedRangeSlider.cpp)

```cpp
#include "AdvancedRangeSlider.h"
#include <QMouseEvent>
#include <QHoverEvent>
#include <QDebug>
#include <cmath>

AdvancedRangeSlider::AdvancedRangeSlider(QQuickItem *parent)
    : QQuickPaintedItem(parent),
      m_minimum(0.0),
      m_maximum(100.0),
      m_from(20.0),
      m_to(80.0),
      m_grooveColor(Qt::gray),
      m_rangeColor(Qt::blue),
      m_handleColor(Qt::white),
      m_textColor(Qt::black),
      m_active(true),
      m_handleWidth(12),
      m_showValues(true),
      m_snapToWholeNumbers(false),
      m_timeFormat("hh:mm:ss"),
      m_showTime(false),
      m_pressedHandle(None),
      m_hoverPos(-1)
{
    setAcceptedMouseButtons(Qt::LeftButton);
    setAcceptHoverEvents(true);
    setAntialiasing(true);
}

void AdvancedRangeSlider::paint(QPainter *painter)
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
    bool leftHovered = (m_pressedHandle == LeftHandle) || 
                      (m_hoverPos >= fromPos - m_handleWidth/2 && m_hoverPos <= fromPos + m_handleWidth/2);
    bool rightHovered = (m_pressedHandle == RightHandle) || 
                       (m_hoverPos >= toPos - m_handleWidth/2 && m_hoverPos <= toPos + m_handleWidth/2);
    
    drawHandle(painter, fromPos, leftHovered);
    drawHandle(painter, toPos, rightHovered);

    // Отображаем значения, если включено
    if (m_showValues) {
        drawValueTooltip(painter, m_from, fromPos);
        drawValueTooltip(painter, m_to, toPos);
    }

    // Отображаем значение под курсором
    if (m_hoverPos >= 0 && m_hoverPos <= width()) {
        double hoverValue = valueFromPosition(m_hoverPos);
        drawValueTooltip(painter, hoverValue, m_hoverPos);
    }
}

void AdvancedRangeSlider::drawHandle(QPainter *painter, double position, bool isHovered)
{
    painter->setPen(isHovered ? QPen(m_handleColor.lighter(150), 2) : Qt::NoPen);
    painter->setBrush(isHovered ? m_handleColor.lighter(120) : m_handleColor);
    
    QRectF handleRect(position - m_handleWidth / 2, height() / 2 - 10, 
                     m_handleWidth, 20);
    painter->drawRoundedRect(handleRect, 4, 4);
}

void AdvancedRangeSlider::drawValueTooltip(QPainter *painter, double value, double position)
{
    QString text;
    if (m_showTime) {
        text = formatTime(value);
    } else {
        text = QString::number(snapValue(value), 'f', m_snapToWholeNumbers ? 0 : 1);
    }
    
    QFont font = painter->font();
    font.setPointSize(8);
    painter->setFont(font);
    
    QFontMetrics metrics(font);
    int textWidth = metrics.horizontalAdvance(text);
    int textHeight = metrics.height();
    
    QRectF textRect(position - textWidth/2, height() / 2 - 25, textWidth, textHeight);
    
    // Фон под текстом
    painter->setPen(Qt::NoPen);
    painter->setBrush(QColor(0, 0, 0, 150));
    painter->drawRoundedRect(textRect.adjusted(-2, -2, 2, 2), 3, 3);
    
    // Текст
    painter->setPen(m_textColor);
    painter->drawText(textRect, Qt::AlignCenter, text);
}

// ... остальные геттеры и сеттеры аналогично предыдущей реализации ...

void AdvancedRangeSlider::setSnapToWholeNumbers(bool snap)
{
    if (m_snapToWholeNumbers == snap)
        return;
    
    m_snapToWholeNumbers = snap;
    
    // Применяем snapping к текущим значениям
    setFrom(snapValue(m_from));
    setTo(snapValue(m_to));
    
    update();
    emit snapToWholeNumbersChanged(m_snapToWholeNumbers);
}

void AdvancedRangeSlider::setTimeFormat(QString format)
{
    if (m_timeFormat == format)
        return;
    
    m_timeFormat = format;
    update();
    emit timeFormatChanged(m_timeFormat);
}

void AdvancedRangeSlider::setShowTime(bool show)
{
    if (m_showTime == show)
        return;
    
    m_showTime = show;
    update();
    emit showTimeChanged(m_showTime);
}

void AdvancedRangeSlider::resetRange()
{
    setFrom(m_minimum);
    setTo(m_maximum);
}

QString AdvancedRangeSlider::formatTime(double value) const
{
    int totalSeconds = static_cast<int>(value);
    int hours = totalSeconds / 3600;
    int minutes = (totalSeconds % 3600) / 60;
    int seconds = totalSeconds % 60;
    int milliseconds = static_cast<int>((value - totalSeconds) * 1000);
    
    if (m_timeFormat == "hh:mm:ss") {
        return QString("%1:%2:%3")
            .arg(hours, 2, 10, QLatin1Char('0'))
            .arg(minutes, 2, 10, QLatin1Char('0'))
            .arg(seconds, 2, 10, QLatin1Char('0'));
    } else if (m_timeFormat == "hh:mm:ss.zzz") {
        return QString("%1:%2:%3.%4")
            .arg(hours, 2, 10, QLatin1Char('0'))
            .arg(minutes, 2, 10, QLatin1Char('0'))
            .arg(seconds, 2, 10, QLatin1Char('0'))
            .arg(milliseconds, 3, 10, QLatin1Char('0'));
    } else if (m_timeFormat == "mm:ss") {
        return QString("%1:%2")
            .arg(hours * 60 + minutes, 2, 10, QLatin1Char('0'))
            .arg(seconds, 2, 10, QLatin1Char('0'));
    }
    
    return QString::number(value, 'f', 2);
}

void AdvancedRangeSlider::setRange(double from, double to)
{
    setFrom(from);
    setTo(to);
}

void AdvancedRangeSlider::mousePressEvent(QMouseEvent *event)
{
    if (!m_active)
        return;

    m_pressPos = event->position();
    m_pressFrom = m_from;
    m_pressTo = m_to;

    double fromPos = positionFromValue(m_from);
    double toPos = positionFromValue(m_to);

    // Определяем, какую часть слайдера нажали
    if (qAbs(m_pressPos.x() - fromPos) <= m_handleWidth) {
        m_pressedHandle = LeftHandle;
    } else if (qAbs(m_pressPos.x() - toPos) <= m_handleWidth) {
        m_pressedHandle = RightHandle;
    } else if (m_pressPos.x() > fromPos && m_pressPos.x() < toPos) {
        m_pressedHandle = WholeRange;
    } else {
        m_pressedHandle = None;
        
        // Если кликнули вне диапазона, устанавливаем новый диапазон
        double clickValue = valueFromPosition(m_pressPos.x());
        if (m_pressPos.x() < fromPos) {
            setFrom(clickValue);
        } else {
            setTo(clickValue);
        }
        return;
    }
    
    emit userInteractionStarted();
}

void AdvancedRangeSlider::mouseMoveEvent(QMouseEvent *event)
{
    if (!m_active || m_pressedHandle == None)
        return;

    double delta = valueFromPosition(event->position().x()) - 
                   valueFromPosition(m_pressPos.x());

    if (m_pressedHandle == LeftHandle) {
        setFrom(snapValue(m_pressFrom + delta));
    } else if (m_pressedHandle == RightHandle) {
        setTo(snapValue(m_pressTo + delta));
    } else if (m_pressedHandle == WholeRange) {
        setFrom(snapValue(m_pressFrom + delta));
        setTo(snapValue(m_pressTo + delta));
    }
}

void AdvancedRangeSlider::mouseReleaseEvent(QMouseEvent *event)
{
    Q_UNUSED(event)
    if (m_pressedHandle != None) {
        emit userInteractionFinished();
    }
    m_pressedHandle = None;
}

void AdvancedRangeSlider::hoverMoveEvent(QHoverEvent *event)
{
    m_hoverPos = event->position().x();
    update();
}

double AdvancedRangeSlider::snapValue(double value) const
{
    if (!m_snapToWholeNumbers)
        return value;
    
    return std::round(value);
}

// ... остальные методы аналогично предыдущей реализации ...
```

## Улучшенный QML компонент (AdvancedRangeSlider.qml)

```qml
import QtQuick 2.15
import QtQuick.Controls 2.15

Item {
    id: root
    width: 400
    height: 60

    property alias minimum: rangeSlider.minimum
    property alias maximum: rangeSlider.maximum
    property alias from: rangeSlider.from
    property alias to: rangeSlider.to
    property alias grooveColor: rangeSlider.grooveColor
    property alias rangeColor: rangeSlider.rangeColor
    property alias handleColor: rangeSlider.handleColor
    property alias textColor: rangeSlider.textColor
    property alias active: rangeSlider.active
    property alias handleWidth: rangeSlider.handleWidth
    property alias showValues: rangeSlider.showValues
    property alias snapToWholeNumbers: rangeSlider.snapToWholeNumbers
    property alias timeFormat: rangeSlider.timeFormat
    property alias showTime: rangeSlider.showTime

    signal rangeChanged(double from, double to)
    signal userInteractionStarted()
    signal userInteractionFinished()

    AdvancedRangeSlider {
        id: rangeSlider
        anchors.fill: parent
        
        onRangeChanged: {
            root.rangeChanged(from, to)
        }
        onUserInteractionStarted: root.userInteractionStarted()
        onUserInteractionFinished: root.userInteractionFinished()
    }

    // Панель управления
    Row {
        id: controlPanel
        anchors.top: parent.bottom
        anchors.topMargin: 10
        anchors.horizontalCenter: parent.horizontalCenter
        spacing: 10

        Button {
            text: "Весь диапазон"
            onClicked: rangeSlider.resetRange()
        }

        CheckBox {
            id: snapCheckbox
            text: "Целые числа"
            checked: rangeSlider.snapToWholeNumbers
            onCheckedChanged: rangeSlider.snapToWholeNumbers = checked
        }

        CheckBox {
            id: timeCheckbox
            text: "Формат времени"
            checked: rangeSlider.showTime
            onCheckedChanged: rangeSlider.showTime = checked
        }

        CheckBox {
            id: valuesCheckbox
            text: "Показывать значения"
            checked: rangeSlider.showValues
            onCheckedChanged: rangeSlider.showValues = checked
        }
    }

    // Информационная панель
    Rectangle {
        anchors.top: controlPanel.bottom
        anchors.topMargin: 10
        anchors.horizontalCenter: parent.horizontalCenter
        width: childrenRect.width + 20
        height: childrenRect.height + 10
        color: "#40000000"
        radius: 5

        Row {
            anchors.centerIn: parent
            spacing: 15

            Text {
                text: "Начало: " + (rangeSlider.showTime ? 
                    rangeSlider.formatTime(rangeSlider.from) : 
                    rangeSlider.from.toFixed(rangeSlider.snapToWholeNumbers ? 0 : 1))
                color: "white"
                font.pixelSize: 12
            }

            Text {
                text: "Конец: " + (rangeSlider.showTime ? 
                    rangeSlider.formatTime(rangeSlider.to) : 
                    rangeSlider.to.toFixed(rangeSlider.snapToWholeNumbers ? 0 : 1))
                color: "white"
                font.pixelSize: 12
            }

            Text {
                text: "Длительность: " + (rangeSlider.showTime ? 
                    rangeSlider.formatTime(rangeSlider.to - rangeSlider.from) : 
                    (rangeSlider.to - rangeSlider.from).toFixed(rangeSlider.snapToWholeNumbers ? 0 : 1))
                color: "white"
                font.pixelSize: 12
                font.bold: true
            }
        }
    }
}
```

## Пример использования (main.qml)

```qml
import QtQuick 2.15
import QtQuick.Window 2.15
import QtQuick.Controls 2.15
import CustomControls 1.0

Window {
    width: 800
    height: 600
    visible: true
    title: "Advanced Range Slider Example"

    Rectangle {
        anchors.fill: parent
        color: "#2c3e50"
        gradient: Gradient {
            GradientStop { position: 0.0; color: "#2c3e50" }
            GradientStop { position: 1.0; color: "#3498db" }
        }

        Column {
            anchors.centerIn: parent
            spacing: 30

            Text {
                anchors.horizontalCenter: parent.horizontalCenter
                text: "Выбор диапазона кадров видео"
                color: "white"
                font.pixelSize: 24
                font.bold: true
            }

            AdvancedRangeSlider {
                id: videoRangeSlider
                width: 600
                minimum: 0
                maximum: 3600 // 1 час в секундах
                from: 600    // 10 минут
                to: 1800     // 30 минут
                grooveColor: "#34495e"
                rangeColor: "#e74c3c"
                handleColor: "#ecf0f1"
                textColor: "white"
                showTime: true
                timeFormat: "hh:mm:ss"
                
                onRangeChanged: {
                    console.log("Selected range:", from, "to", to, 
                                "duration:", (to - from))
                }
                
                onUserInteractionStarted: {
                    console.log("User started interaction")
                }
                
                onUserInteractionFinished: {
                    console.log("User finished interaction")
                }
            }

            // Кнопки действий
            Row {
                spacing: 15
                anchors.horizontalCenter: parent.horizontalCenter

                Button {
                    text: "Удалить диапазон"
                    palette.button: "#e74c3c"
                    palette.buttonText: "white"
                    onClicked: {
                        console.log("Deleting frames from", videoRangeSlider.from, 
                                    "to", videoRangeSlider.to)
                    }
                }

                Button {
                    text: "Экспортировать диапазон"
                    palette.button: "#2ecc71"
                    palette.buttonText: "white"
                    onClicked: {
                        console.log("Exporting frames from", videoRangeSlider.from, 
                                    "to", videoRangeSlider.to)
                    }
                }

                Button {
                    text: "Применить эффект"
                    palette.button: "#9b59b6"
                    palette.buttonText: "white"
                    onClicked: {
                        console.log("Applying effect to frames from", videoRangeSlider.from, 
                                    "to", videoRangeSlider.to)
                    }
                }
            }
        }
    }
}
```

## Основные улучшения:

1. **Поддержка времени** - отображение значений в формате времени
2. **Привязка к целым числам** - опция для работы с кадрами
3. **Подсказки значений** - показ значений при наведении
4. **Перетаскивание всего диапазона** - перемещение диапазона целиком
5. **Улучшенная визуальная обратная связь** - подсветка элементов при наведении
6. **Панель управления** - дополнительные элементы управления в QML
7. **Сигналы взаимодействия** - уведомления о начале/конце взаимодействия
8. **Гибкое форматирование** - поддержка разных форматов отображения

Этот улучшенный слайдер идеально подходит для работы с видео, позволяя точно выбирать диапазоны кадров или временные интервалы с интуитивно понятным интерфейсом.
