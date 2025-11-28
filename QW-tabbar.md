Отлично! Ниже представлен обновлённый код, включающий:

- Новое **свойство `activeIndex`** типа `int` (указывает на индекс активной закладки).
- **Обработку кликов по закладкам**: при клике индекс активной закладки меняется.
- Сигнал `activeIndexChanged`, чтобы можно было отслеживать изменения из QML.

---

### 📄 TabBar.h (TabBar.hpp)

```cpp
#ifndef TABBAR_H
#define TABBAR_H

#include <QtQuick/QQuickPaintedItem>
#include <QPainter>
#include <QColor>
#include <QFontMetrics>
#include <QRect>
#include <QVariant>
#include <QList>
#include <QVariantMap>

class TabBar : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(QVariantList tabs READ tabs WRITE setTabs NOTIFY tabsChanged)
    Q_PROPERTY(QColor backgroundColor READ backgroundColor WRITE setBackgroundColor NOTIFY backgroundColorChanged)
    Q_PROPERTY(int activeIndex READ activeIndex WRITE setActiveIndex NOTIFY activeIndexChanged)
    Q_PROPERTY(int maxTabWidth READ maxTabWidth WRITE setMaxTabWidth NOTIFY maxTabWidthChanged)
    Q_PROPERTY(int cornerRadius READ cornerRadius WRITE setCornerRadius NOTIFY cornerRadiusChanged)

public:
    explicit TabBar(QQuickItem *parent = nullptr);

    QVariantList tabs() const;
    void setTabs(const QVariantList &tabs);

    QColor backgroundColor() const;
    void setBackgroundColor(const QColor &color);

    int activeIndex() const;
    void setActiveIndex(int index);

    int maxTabWidth() const;
    void setMaxTabWidth(int width);

    int cornerRadius() const;
    void setCornerRadius(int radius);

    void paint(QPainter *painter) override;

signals:
    void tabsChanged();
    void backgroundColorChanged();
    void activeIndexChanged();
    void maxTabWidthChanged();
    void cornerRadiusChanged();

protected:
    void mousePressEvent(QMouseEvent *event) override;

private:
    QVariantList m_tabs;
    QColor m_backgroundColor = Qt::lightGray;
    int m_activeIndex = -1;
    int m_maxTabWidth = 200;
    int m_cornerRadius = 8;

    int tabAt(const QPointF &pos) const; // Вспомогательная функция для определения индекса закладки по позиции
};

#endif // TABBAR_H
```

---

### 📄 TabBar.cpp

```cpp
#include "TabBar.h"
#include <QMouseEvent>

TabBar::TabBar(QQuickItem *parent)
    : QQuickPaintedItem(parent)
{
    setAcceptedMouseButtons(Qt::LeftButton);
}

QVariantList TabBar::tabs() const
{
    return m_tabs;
}

void TabBar::setTabs(const QVariantList &tabs)
{
    if (m_tabs != tabs) {
        m_tabs = tabs;
        emit tabsChanged();
        update();
    }
}

QColor TabBar::backgroundColor() const
{
    return m_backgroundColor;
}

void TabBar::setBackgroundColor(const QColor &color)
{
    if (m_backgroundColor != color) {
        m_backgroundColor = color;
        emit backgroundColorChanged();
        update();
    }
}

int TabBar::activeIndex() const
{
    return m_activeIndex;
}

void TabBar::setActiveIndex(int index)
{
    if (m_activeIndex != index && index >= -1 && index < m_tabs.size()) {
        m_activeIndex = index;
        emit activeIndexChanged();
        update();
    }
}

int TabBar::maxTabWidth() const
{
    return m_maxTabWidth;
}

void TabBar::setMaxTabWidth(int width)
{
    if (m_maxTabWidth != width) {
        m_maxTabWidth = width;
        emit maxTabWidthChanged();
        update();
    }
}

int TabBar::cornerRadius() const
{
    return m_cornerRadius;
}

void TabBar::setCornerRadius(int radius)
{
    if (m_cornerRadius != radius) {
        m_cornerRadius = radius;
        emit cornerRadiusChanged();
        update();
    }
}

void TabBar::paint(QPainter *painter)
{
    painter->setRenderHint(QPainter::Antialiasing);

    QColor tabColor = m_backgroundColor.lighter(120); // на 20% светлее

    int x = 0;
    int tabHeight = height() - 4; // оставляем место для синей линии внизу
    int tabY = 0;

    for (int i = 0; i < m_tabs.size(); ++i) {
        QVariantMap tab = m_tabs[i].toMap();
        QString text = tab["text"].toString();

        QFontMetrics fm = painter->fontMetrics();
        int textWidth = fm.horizontalAdvance(text) + 20; // отступы по 10 слева и справа
        int tabWidth = qMin(textWidth, m_maxTabWidth);

        QRect tabRect(x, tabY, tabWidth, tabHeight);

        // Рисуем закладку
        painter->setBrush(tabColor);
        painter->setPen(Qt::NoPen);
        painter->drawRoundedRect(tabRect, m_cornerRadius, m_cornerRadius);

        // Рисуем текст
        painter->setPen(Qt::black);
        painter->drawText(tabRect, Qt::AlignCenter, text);

        // Если это активная закладка, рисуем синюю линию под ней и по бокам
        if (i == m_activeIndex) {
            painter->setPen(QPen(Qt::blue, 2));
            int lineY = tabRect.bottom();
            painter->drawLine(0, lineY, width(), lineY); // полная линия
        }

        x += tabWidth + 5; // отступ между закладками
    }

    // Рисуем нижнюю линию (если нет активной)
    if (m_activeIndex < 0 || m_activeIndex >= m_tabs.size()) {
        painter->setPen(QPen(Qt::blue, 2));
        painter->drawLine(0, height() - 2, width(), height() - 2);
    }
}

void TabBar::mousePressEvent(QMouseEvent *event)
{
    int clickedIndex = tabAt(event->pos());
    if (clickedIndex != -1) {
        setActiveIndex(clickedIndex);
    }
    QQuickPaintedItem::mousePressEvent(event);
}

int TabBar::tabAt(const QPointF &pos) const
{
    int x = 0;
    int tabHeight = height() - 4;
    int tabY = 0;

    for (int i = 0; i < m_tabs.size(); ++i) {
        QVariantMap tab = m_tabs[i].toMap();
        QString text = tab["text"].toString();

        QFontMetrics fm = QFontMetrics(font());
        int textWidth = fm.horizontalAdvance(text) + 20;
        int tabWidth = qMin(textWidth, m_maxTabWidth);

        QRect tabRect(x, tabY, tabWidth, tabHeight);

        if (tabRect.contains(pos.toPoint())) {
            return i;
        }

        x += tabWidth + 5;
    }

    return -1;
}
```

---

### 📄 main.cpp

```cpp
#include <QGuiApplication>
#include <QQmlApplicationEngine>
#include <QQmlContext>
#include "TabBar.h"

int main(int argc, char *argv[])
{
    QCoreApplication::setAttribute(Qt::AA_EnableHighDpiScaling);

    QGuiApplication app(argc, argv);

    qmlRegisterType<TabBar>("MyModule", 1, 0, "TabBar");

    QQmlApplicationEngine engine;
    engine.load(QUrl(QStringLiteral("qrc:/main.qml")));

    if (engine.rootObjects().isEmpty()) {
        return -1;
    }

    return app.exec();
}
```

---

### 📄 main.qml

```qml
import QtQuick
import QtQuick.Controls
import MyModule 1.0

ApplicationWindow {
    width: 800
    height: 100
    visible: true

    TabBar {
        anchors.fill: parent
        backgroundColor: "gray"
        activeIndex: 1
        maxTabWidth: 150
        cornerRadius: 10
        tabs: [
            {"text": "Label one"},
            {"text": "Second Tab"},
            {"text": "Tab 3"},
            {"text": "Very Long Tab Name"}
        ]

        // Отслеживание изменения активной закладки
        onActiveIndexChanged: console.log("Active tab changed to:", activeIndex)
    }
}
```

---

### 🛠️ CMakeLists.txt (если используете CMake)

```cmake
cmake_minimum_required(VERSION 3.16)
project(MyApp LANGUAGES CXX)

find_package(Qt6 REQUIRED COMPONENTS Core Quick)

qt_standard_project_setup()

qt_add_executable(myapp
    main.cpp
    TabBar.cpp
    TabBar.h
)

qt_add_qml_module(myapp
    URI MyApp
    VERSION 1.0
    QML_FILES main.qml
)

target_link_libraries(myapp Qt6::Core Qt6::Quick)
```

---

### ✅ Что теперь работает:

- Клик по закладке меняет `activeIndex`.
- `activeIndex` можно задавать из QML и изменять через клики.
- При смене активной закладки — обновляется отображение (синяя линия под активной).
- Из QML можно слушать `onActiveIndexChanged`.

Если нужно, могу добавить анимацию, поддержку прокрутки, иконки и т.д.
