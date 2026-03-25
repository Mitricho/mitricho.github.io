

# Qt6 Widgets + QML Hybrid Demo Application

## Project Structure

```
hybrid-app/
├── CMakeLists.txt
├── main.cpp
├── mainwindow.h
├── mainwindow.cpp
├── qmlkeyfilter.h
├── qmlkeyfilter.cpp
├── qml/
│   └── main.qml
└── resources.qrc
```

## CMakeLists.txt

```cmake
cmake_minimum_required(VERSION 3.19)
project(HybridWidgetsQml VERSION 1.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_AUTOMOC ON)
set(CMAKE_AUTORCC ON)
set(CMAKE_AUTOUIC ON)

find_package(Qt6 6.10 REQUIRED COMPONENTS
    Core
    Gui
    Widgets
    Quick
    QuickWidgets
    Qml
)

qt_standard_project_setup()

qt_add_executable(HybridWidgetsQml
    main.cpp
    mainwindow.h
    mainwindow.cpp
    qmlkeyfilter.h
    qmlkeyfilter.cpp
    resources.qrc
)

target_link_libraries(HybridWidgetsQml PRIVATE
    Qt6::Core
    Qt6::Gui
    Qt6::Widgets
    Qt6::Quick
    Qt6::QuickWidgets
    Qt6::Qml
)

# Ensure QML files are found at runtime
set_target_properties(HybridWidgetsQml PROPERTIES
    WIN32_EXECUTABLE TRUE
    MACOSX_BUNDLE TRUE
)
```

## resources.qrc

```xml
<RCC>
    <qresource prefix="/">
        <file>qml/main.qml</file>
    </qresource>
</RCC>
```

## qmlkeyfilter.h

```cpp
#ifndef QMLKEYFILTER_H
#define QMLKEYFILTER_H

#include <QObject>
#include <QEvent>
#include <QKeyEvent>
#include <QQuickView>
#include <QCoreApplication>
#include <QQuickItem>
#include <QDebug>

// This event filter intercepts key events from the container widget
// and forwards them into the QQuickView so QML items receive them.
// It also exposes key press info as signals for QML to consume via
// a context property.

class QmlKeyFilter : public QObject
{
    Q_OBJECT
    Q_PROPERTY(QString lastKey READ lastKey NOTIFY lastKeyChanged)
    Q_PROPERTY(int lastKeyCode READ lastKeyCode NOTIFY lastKeyChanged)
    Q_PROPERTY(int modifiers READ modifiers NOTIFY lastKeyChanged)
    Q_PROPERTY(QString modifierString READ modifierString NOTIFY lastKeyChanged)
    Q_PROPERTY(bool isPressed READ isPressed NOTIFY lastKeyChanged)

public:
    explicit QmlKeyFilter(QQuickView *quickView, QObject *parent = nullptr);

    QString lastKey() const { return m_lastKey; }
    int lastKeyCode() const { return m_lastKeyCode; }
    int modifiers() const { return m_modifiers; }
    QString modifierString() const { return m_modifierString; }
    bool isPressed() const { return m_isPressed; }

    bool eventFilter(QObject *watched, QEvent *event) override;

signals:
    void lastKeyChanged();
    void keyPressed(int key, const QString &text, int modifiers);
    void keyReleased(int key, const QString &text, int modifiers);

private:
    void forwardKeyEvent(QKeyEvent *keyEvent);
    QString buildModifierString(Qt::KeyboardModifiers mods);

    QQuickView *m_quickView;
    QString m_lastKey;
    int m_lastKeyCode = 0;
    int m_modifiers = 0;
    QString m_modifierString;
    bool m_isPressed = false;
};

#endif // QMLKEYFILTER_H
```

## qmlkeyfilter.cpp

```cpp
#include "qmlkeyfilter.h"

QmlKeyFilter::QmlKeyFilter(QQuickView *quickView, QObject *parent)
    : QObject(parent)
    , m_quickView(quickView)
{
}

bool QmlKeyFilter::eventFilter(QObject *watched, QEvent *event)
{
    if (event->type() == QEvent::KeyPress || event->type() == QEvent::KeyRelease) {
        QKeyEvent *keyEvent = static_cast<QKeyEvent *>(event);

        // Update properties for QML binding
        m_lastKey = keyEvent->text().isEmpty() ? QKeySequence(keyEvent->key()).toString() : keyEvent->text();
        m_lastKeyCode = keyEvent->key();
        m_modifiers = static_cast<int>(keyEvent->modifiers());
        m_modifierString = buildModifierString(keyEvent->modifiers());
        m_isPressed = (event->type() == QEvent::KeyPress);

        emit lastKeyChanged();

        if (event->type() == QEvent::KeyPress) {
            emit keyPressed(keyEvent->key(), m_lastKey, m_modifiers);
        } else {
            emit keyReleased(keyEvent->key(), m_lastKey, m_modifiers);
        }

        // Forward the key event to QQuickView so QML items get it
        forwardKeyEvent(keyEvent);

        // Return false so widgets chain still processes (menus, shortcuts, etc.)
        return false;
    }

    return QObject::eventFilter(watched, event);
}

void QmlKeyFilter::forwardKeyEvent(QKeyEvent *keyEvent)
{
    if (!m_quickView)
        return;

    // Create a copy of the event and send it to the QQuickView window
    QKeyEvent *clonedEvent = new QKeyEvent(
        keyEvent->type(),
        keyEvent->key(),
        keyEvent->modifiers(),
        keyEvent->nativeScanCode(),
        keyEvent->nativeVirtualKey(),
        keyEvent->nativeModifiers(),
        keyEvent->text(),
        keyEvent->isAutoRepeat(),
        keyEvent->count()
    );

    // Post to the QQuickView so it dispatches to focused QML items
    QCoreApplication::postEvent(m_quickView, clonedEvent);
}

QString QmlKeyFilter::buildModifierString(Qt::KeyboardModifiers mods)
{
    QStringList parts;
    if (mods & Qt::ControlModifier) parts << "Ctrl";
    if (mods & Qt::ShiftModifier) parts << "Shift";
    if (mods & Qt::AltModifier) parts << "Alt";
    if (mods & Qt::MetaModifier) parts << "Meta";
    return parts.join("+");
}
```

## mainwindow.h

```cpp
#ifndef MAINWINDOW_H
#define MAINWINDOW_H

#include <QMainWindow>
#include <QQuickView>
#include <QLabel>
#include "qmlkeyfilter.h"

class MainWindow : public QMainWindow
{
    Q_OBJECT

public:
    explicit MainWindow(QWidget *parent = nullptr);
    ~MainWindow() override;

protected:
    void closeEvent(QCloseEvent *event) override;

private:
    void createMenus();
    void createStatusBar();
    void setupQmlView();
    void updateStatusBar(const QString &msg);

    QQuickView *m_quickView = nullptr;
    QWidget *m_container = nullptr;
    QmlKeyFilter *m_keyFilter = nullptr;
    QLabel *m_statusLabel = nullptr;
};

#endif // MAINWINDOW_H
```

## mainwindow.cpp

```cpp
#include "mainwindow.h"

#include <QMenuBar>
#include <QStatusBar>
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QMessageBox>
#include <QApplication>
#include <QQmlContext>
#include <QQmlEngine>
#include <QTimer>
#include <QLabel>
#include <QPushButton>
#include <QGroupBox>
#include <QSplitter>
#include <QTextEdit>
#include <QDockWidget>

MainWindow::MainWindow(QWidget *parent)
    : QMainWindow(parent)
{
    setWindowTitle("Qt6 Widgets + QML Hybrid Demo — Keyboard Forwarding");
    resize(1100, 750);

    createMenus();
    createStatusBar();
    setupQmlView();
}

MainWindow::~MainWindow() = default;

void MainWindow::createMenus()
{
    // --- File Menu ---
    QMenu *fileMenu = menuBar()->addMenu("&File");

    QAction *newAct = fileMenu->addAction("&New", this, [this]() {
        updateStatusBar("File > New triggered");
    });
    newAct->setShortcut(QKeySequence::New);

    QAction *openAct = fileMenu->addAction("&Open...", this, [this]() {
        updateStatusBar("File > Open triggered");
    });
    openAct->setShortcut(QKeySequence::Open);

    QAction *saveAct = fileMenu->addAction("&Save", this, [this]() {
        updateStatusBar("File > Save triggered");
    });
    saveAct->setShortcut(QKeySequence::Save);

    fileMenu->addSeparator();

    fileMenu->addAction("E&xit", this, &QWidget::close, QKeySequence::Quit);

    // --- Edit Menu ---
    QMenu *editMenu = menuBar()->addMenu("&Edit");
    editMenu->addAction("&Undo", this, [this]() {
        updateStatusBar("Edit > Undo triggered");
    }, QKeySequence::Undo);
    editMenu->addAction("&Redo", this, [this]() {
        updateStatusBar("Edit > Redo triggered");
    }, QKeySequence::Redo);
    editMenu->addSeparator();
    editMenu->addAction("Cu&t", this, [this]() {
        updateStatusBar("Edit > Cut triggered");
    }, QKeySequence::Cut);
    editMenu->addAction("&Copy", this, [this]() {
        updateStatusBar("Edit > Copy triggered");
    }, QKeySequence::Copy);
    editMenu->addAction("&Paste", this, [this]() {
        updateStatusBar("Edit > Paste triggered");
    }, QKeySequence::Paste);

    // --- View Menu ---
    QMenu *viewMenu = menuBar()->addMenu("&View");
    QAction *focusQmlAct = viewMenu->addAction("&Focus QML View", this, [this]() {
        if (m_container) {
            m_container->setFocus();
            // Also set focus to the QQuickView root item
            if (m_quickView && m_quickView->rootObject()) {
                m_quickView->rootObject()->forceActiveFocus();
            }
            updateStatusBar("Focus set to QML view");
        }
    });
    focusQmlAct->setShortcut(QKeySequence("F5"));

    // --- Help Menu ---
    QMenu *helpMenu = menuBar()->addMenu("&Help");
    helpMenu->addAction("&About", this, [this]() {
        QMessageBox::about(this, "About",
            "Qt6 Widgets + QML Hybrid Demo\n\n"
            "Demonstrates embedding QQuickView inside a QMainWindow\n"
            "with proper keyboard event forwarding to QML.\n\n"
            "Press F5 to focus the QML view.\n"
            "Tab/Arrow keys navigate QML controls.\n"
            "All key presses are logged in the QML display.");
    });
    helpMenu->addAction("About &Qt", qApp, &QApplication::aboutQt);
}

void MainWindow::createStatusBar()
{
    m_statusLabel = new QLabel("Ready — Press F5 to focus QML view");
    statusBar()->addWidget(m_statusLabel, 1);

    QLabel *hintLabel = new QLabel("  F5: Focus QML | Alt: Menu  ");
    hintLabel->setStyleSheet("color: gray;");
    statusBar()->addPermanentWidget(hintLabel);
}

void MainWindow::setupQmlView()
{
    // Create the QQuickView
    m_quickView = new QQuickView();
    m_quickView->setResizeMode(QQuickView::SizeRootObjectToView);
    m_quickView->setColor(QColor(30, 30, 30));

    // Create the key filter and register as context property BEFORE loading QML
    m_keyFilter = new QmlKeyFilter(m_quickView, this);
    m_quickView->rootContext()->setContextProperty("keyFilter", m_keyFilter);

    // Load QML
    m_quickView->setSource(QUrl("qrc:/qml/main.qml"));

    // Check for errors
    if (m_quickView->status() == QQuickView::Error) {
        qWarning() << "QML loading errors:";
        for (const auto &error : m_quickView->errors()) {
            qWarning() << "  " << error.toString();
        }
    }

    // Create the container widget from the QQuickView
    m_container = QWidget::createWindowContainer(m_quickView, this);
    m_container->setMinimumSize(400, 300);
    m_container->setFocusPolicy(Qt::StrongFocus);

    // Install the key event filter on the container widget
    m_container->installEventFilter(m_keyFilter);

    // Also install on this main window to catch keys when container doesn't have focus
    // but we only forward when the QML view should receive them
    // The container itself handles focus properly

    // --- Build a layout with a widgets sidebar and the QML view ---
    QSplitter *splitter = new QSplitter(Qt::Horizontal, this);

    // Left panel: some widget controls to demonstrate coexistence
    QWidget *leftPanel = new QWidget();
    QVBoxLayout *leftLayout = new QVBoxLayout(leftPanel);
    leftLayout->setContentsMargins(8, 8, 8, 8);

    QGroupBox *widgetsGroup = new QGroupBox("Widget Controls");
    QVBoxLayout *wgLayout = new QVBoxLayout(widgetsGroup);

    QLabel *infoLabel = new QLabel(
        "This side uses traditional\n"
        "Qt Widgets. The right side\n"
        "embeds a QQuickView.\n\n"
        "Click the QML area or press\n"
        "F5 to focus it, then use\n"
        "keyboard to interact.");
    infoLabel->setWordWrap(true);
    wgLayout->addWidget(infoLabel);

    QPushButton *focusBtn = new QPushButton("Focus QML View (F5)");
    connect(focusBtn, &QPushButton::clicked, this, [this]() {
        m_container->setFocus();
        if (m_quickView && m_quickView->rootObject()) {
            m_quickView->rootObject()->forceActiveFocus();
        }
        updateStatusBar("Focus set to QML view via button");
    });
    wgLayout->addWidget(focusBtn);

    QPushButton *resetBtn = new QPushButton("Reset QML State");
    connect(resetBtn, &QPushButton::clicked, this, [this]() {
        // Reload QML
        if (m_quickView && m_quickView->rootObject()) {
            QMetaObject::invokeMethod(m_quickView->rootObject(), "resetState");
        }
        updateStatusBar("QML state reset");
    });
    wgLayout->addWidget(resetBtn);

    QTextEdit *widgetTextEdit = new QTextEdit();
    widgetTextEdit->setPlaceholderText("Widget QTextEdit — type here to verify widgets still work independently");
    widgetTextEdit->setMaximumHeight(120);
    wgLayout->addWidget(widgetTextEdit);

    wgLayout->addStretch();
    leftLayout->addWidget(widgetsGroup);
    leftPanel->setMaximumWidth(280);
    leftPanel->setMinimumWidth(200);

    splitter->addWidget(leftPanel);
    splitter->addWidget(m_container);
    splitter->setStretchFactor(0, 0);
    splitter->setStretchFactor(1, 1);
    splitter->setSizes({250, 750});

    setCentralWidget(splitter);

    // Connect key filter signals to status bar
    connect(m_keyFilter, &QmlKeyFilter::keyPressed, this,
        [this](int key, const QString &text, int modifiers) {
            QString modStr = m_keyFilter->modifierString();
            QString display = modStr.isEmpty() ? text : modStr + "+" + text;
            updateStatusBar(QString("Key pressed: %1 (0x%2)")
                .arg(display)
                .arg(key, 0, 16));
        });
}

void MainWindow::updateStatusBar(const QString &msg)
{
    m_statusLabel->setText(msg);
}

void MainWindow::closeEvent(QCloseEvent *event)
{
    QMainWindow::closeEvent(event);
}
```

## main.cpp

```cpp
#include <QApplication>
#include <QQuickWindow>
#include <QSurfaceFormat>
#include "mainwindow.h"

int main(int argc, char *argv[])
{
    // Set the graphics API before creating QApplication
    QQuickWindow::setGraphicsApi(QSGRendererInterface::OpenGL);

    QApplication app(argc, argv);
    app.setApplicationName("HybridWidgetsQml");
    app.setOrganizationName("Demo");
    app.setApplicationVersion("1.0");

    // Set a nice default surface format
    QSurfaceFormat format;
    format.setDepthBufferSize(24);
    format.setStencilBufferSize(8);
    format.setSamples(4);
    QSurfaceFormat::setDefaultFormat(format);

    MainWindow window;
    window.show();

    return app.exec();
}
```

## qml/main.qml

```qml
import QtQuick
import QtQuick.Controls
import QtQuick.Layouts

Rectangle {
    id: root
    color: "#1e1e2e"
    focus: true

    // Track all pressed keys for the key visualizer
    property var pressedKeys: ({})
    property string lastKeyText: "None"
    property int lastKeyCode: 0
    property int keyPressCount: 0
    property var keyLog: []
    property int selectedButtonIndex: -1

    // Reset function callable from C++
    function resetState() {
        pressedKeys = {};
        lastKeyText = "None";
        lastKeyCode = 0;
        keyPressCount = 0;
        keyLog = [];
        selectedButtonIndex = -1;
        logModel.clear();
        textInput1.text = "";
        textInput2.text = "";
        slider.value = 50;
        progressBar.value = 0;
        spinBox.value = 0;
        comboBox.currentIndex = 0;
    }

    function addToLog(eventType, keyName, keyCode) {
        var timestamp = new Date().toLocaleTimeString(Qt.locale(), "HH:mm:ss.zzz");
        var entry = timestamp + " | " + eventType + " | " + keyName + " (0x" + keyCode.toString(16).toUpperCase() + ")";
        logModel.insert(0, { "text": entry, "eventType": eventType });
        if (logModel.count > 100) {
            logModel.remove(logModel.count - 1);
        }
    }

    // Key handling directly on the root
    Keys.onPressed: function(event) {
        keyPressCount++;
        var keyName = event.text || "Special(0x" + event.key.toString(16) + ")";
        lastKeyText = keyName;
        lastKeyCode = event.key;

        var newPressed = Object.assign({}, pressedKeys);
        newPressed[event.key] = keyName;
        pressedKeys = newPressed;

        addToLog("PRESS", keyName, event.key);

        // Slider control with arrow keys when slider is focused
        // Progress bar increment with +/- keys
        if (event.key === Qt.Key_Plus || event.key === Qt.Key_Equal) {
            progressBar.value = Math.min(1.0, progressBar.value + 0.05);
        } else if (event.key === Qt.Key_Minus) {
            progressBar.value = Math.max(0.0, progressBar.value - 0.05);
        }
    }

    Keys.onReleased: function(event) {
        var keyName = event.text || "Special(0x" + event.key.toString(16) + ")";

        var newPressed = Object.assign({}, pressedKeys);
        delete newPressed[event.key];
        pressedKeys = newPressed;

        addToLog("RELEASE", keyName, event.key);
    }

    // Also connect to the C++ key filter signals for redundant confirmation
    Connections {
        target: keyFilter
        function onKeyPressed(key, text, modifiers) {
            // The C++ filter also detected the key — we can use this
            // as a backup confirmation
        }
    }

    ListModel {
        id: logModel
    }

    // Mouse click to grab focus
    MouseArea {
        anchors.fill: parent
        onClicked: root.forceActiveFocus()
        // Don't consume, let children handle too
        propagateComposedEvents: true
    }

    ScrollView {
        anchors.fill: parent
        anchors.margins: 12
        contentWidth: availableWidth

        ColumnLayout {
            width: parent.width
            spacing: 12

            // ===== HEADER =====
            Rectangle {
                Layout.fillWidth: true
                Layout.preferredHeight: 60
                radius: 8
                color: root.activeFocus || root.focus ? "#45475a" : "#585b70"

                RowLayout {
                    anchors.fill: parent
                    anchors.margins: 12

                    Text {
                        text: "🎹 QML Keyboard Demo"
                        font.pixelSize: 22
                        font.bold: true
                        color: "#cdd6f4"
                    }

                    Item { Layout.fillWidth: true }

                    Rectangle {
                        width: focusIndicatorText.implicitWidth + 20
                        height: 28
                        radius: 14
                        color: root.activeFocus ? "#a6e3a1" : "#f38ba8"

                        Text {
                            id: focusIndicatorText
                            anchors.centerIn: parent
                            text: root.activeFocus ? "✓ QML HAS FOCUS" : "✗ QML NO FOCUS"
                            font.pixelSize: 12
                            font.bold: true
                            color: "#1e1e2e"
                        }
                    }
                }
            }

            // ===== KEY STATS =====
            Rectangle {
                Layout.fillWidth: true
                Layout.preferredHeight: statsLayout.implicitHeight + 24
                radius: 8
                color: "#313244"

                GridLayout {
                    id: statsLayout
                    anchors.fill: parent
                    anchors.margins: 12
                    columns: 4
                    columnSpacing: 16
                    rowSpacing: 8

                    Text { text: "Last Key:"; color: "#a6adc8"; font.pixelSize: 13 }
                    Text {
                        text: root.lastKeyText
                        color: "#f9e2af"
                        font.pixelSize: 15
                        font.bold: true
                    }

                    Text { text: "Key Code:"; color: "#a6adc8"; font.pixelSize: 13 }
                    Text {
                        text: "0x" + root.lastKeyCode.toString(16).toUpperCase()
                        color: "#89b4fa"
                        font.pixelSize: 15
                        font.family: "monospace"
                    }

                    Text { text: "Total Presses:"; color: "#a6adc8"; font.pixelSize: 13 }
                    Text {
                        text: root.keyPressCount
                        color: "#a6e3a1"
                        font.pixelSize: 15
                        font.bold: true
                    }

                    Text { text: "Currently Held:"; color: "#a6adc8"; font.pixelSize: 13 }
                    Text {
                        text: {
                            var keys = Object.keys(root.pressedKeys);
                            return keys.length > 0 ? Object.values(root.pressedKeys).join(", ") : "None";
                        }
                        color: "#f5c2e7"
                        font.pixelSize: 15
                    }

                    // From C++ keyFilter context property
                    Text { text: "C++ Filter Key:"; color: "#a6adc8"; font.pixelSize: 13 }
                    Text {
                        text: keyFilter.lastKey || "N/A"
                        color: "#fab387"
                        font.pixelSize: 15
                    }

                    Text { text: "Modifiers:"; color: "#a6adc8"; font.pixelSize: 13 }
                    Text {
                        text: keyFilter.modifierString || "None"
                        color: "#94e2d5"
                        font.pixelSize: 15
                    }
                }
            }

            // ===== INTERACTIVE QML CONTROLS =====
            Rectangle {
                Layout.fillWidth: true
                Layout.preferredHeight: controlsColumn.implicitHeight + 24
                radius: 8
                color: "#313244"

                ColumnLayout {
                    id: controlsColumn
                    anchors.fill: parent
                    anchors.margins: 12
                    spacing: 10

                    Text {
                        text: "Interactive QML Controls (Tab to navigate, Enter/Space to activate)"
                        color: "#cdd6f4"
                        font.pixelSize: 15
                        font.bold: true
                    }

                    // Row of buttons navigable by Tab
                    RowLayout {
                        spacing: 8
                        Layout.fillWidth: true

                        Repeater {
                            model: ["Button A", "Button B", "Button C", "Button D"]

                            Button {
                                text: modelData
                                focusPolicy: Qt.StrongFocus

                                background: Rectangle {
                                    radius: 6
                                    color: parent.activeFocus ? "#89b4fa"
                                         : parent.hovered ? "#585b70"
                                         : "#45475a"
                                    border.color: parent.activeFocus ? "#cdd6f4" : "transparent"
                                    border.width: 2
                                }

                                contentItem: Text {
                                    text: parent.text
                                    color: parent.activeFocus ? "#1e1e2e" : "#cdd6f4"
                                    font.pixelSize: 13
                                    font.bold: parent.activeFocus
                                    horizontalAlignment: Text.AlignHCenter
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onClicked: {
                                    root.addToLog("BUTTON", text + " clicked", 0);
                                }

                                Keys.onReturnPressed: {
                                    clicked();
                                }
                                Keys.onEnterPressed: {
                                    clicked();
                                }
                            }
                        }
                    }

                    // Text inputs
                    RowLayout {
                        spacing: 12
                        Layout.fillWidth: true

                        ColumnLayout {
                            Layout.fillWidth: true
                            spacing: 4
                            Text {
                                text: "Text Input 1 (Tab here, type to test):"
                                color: "#a6adc8"
                                font.pixelSize: 12
                            }
                            TextField {
                                id: textInput1
                                Layout.fillWidth: true
                                placeholderText: "Type here..."
                                color: "#cdd6f4"
                                font.pixelSize: 14
                                focusPolicy: Qt.StrongFocus
                                
                                background: Rectangle {
                                    radius: 6
                                    color: "#45475a"
                                    border.color: textInput1.activeFocus ? "#89b4fa" : "#585b70"
                                    border.width: textInput1.activeFocus ? 2 : 1
                                }

                                onTextChanged: {
                                    if (activeFocus && text.length > 0) {
                                        root.addToLog("INPUT1", "text: \"" + text + "\"", 0);
                                    }
                                }
                            }
                        }

                        ColumnLayout {
                            Layout.fillWidth: true
                            spacing: 4
                            Text {
                                text: "Text Input 2:"
                                color: "#a6adc8"
                                font.pixelSize: 12
                            }
                            TextField {
                                id: textInput2
                                Layout.fillWidth: true
                                placeholderText: "And here..."
                                color: "#cdd6f4"
                                font.pixelSize: 14
                                focusPolicy: Qt.StrongFocus
                                
                                background: Rectangle {
                                    radius: 6
                                    color: "#45475a"
                                    border.color: textInput2.activeFocus ? "#89b4fa" : "#585b70"
                                    border.width: textInput2.activeFocus ? 2 : 1
                                }

                                onTextChanged: {
                                    if (activeFocus && text.length > 0) {
                                        root.addToLog("INPUT2", "text: \"" + text + "\"", 0);
                                    }
                                }
                            }
                        }
                    }

                    // Slider, SpinBox, ComboBox row
                    RowLayout {
                        spacing: 16
                        Layout.fillWidth: true

                        ColumnLayout {
                            spacing: 4
                            Layout.fillWidth: true

                            Text {
                                text: "Slider (Arrow keys):"
                                color: "#a6adc8"
                                font.pixelSize: 12
                            }
                            Slider {
                                id: slider
                                Layout.fillWidth: true
                                from: 0
                                to: 100
                                value: 50
                                stepSize: 1
                                focusPolicy: Qt.StrongFocus

                                onValueChanged: {
                                    if (activeFocus)
                                        root.addToLog("SLIDER", "value: " + Math.round(value), 0);
                                }

                                background: Rectangle {
                                    x: slider.leftPadding
                                    y: slider.topPadding + slider.availableHeight / 2 - height / 2
                                    width: slider.availableWidth
                                    height: 6
                                    radius: 3
                                    color: "#45475a"

                                    Rectangle {
                                        width: slider.visualPosition * parent.width
                                        height: parent.height
                                        radius: 3
                                        color: slider.activeFocus ? "#89b4fa" : "#585b70"
                                    }
                                }

                                handle: Rectangle {
                                    x: slider.leftPadding + slider.visualPosition * (slider.availableWidth - width)
                                    y: slider.topPadding + slider.availableHeight / 2 - height / 2
                                    width: 20
                                    height: 20
                                    radius: 10
                                    color: slider.activeFocus ? "#89b4fa" : "#cdd6f4"
                                    border.color: slider.activeFocus ? "#cdd6f4" : "transparent"
                                    border.width: 2
                                }
                            }
                            Text {
                                text: "Value: " + Math.round(slider.value)
                                color: "#cdd6f4"
                                font.pixelSize: 12
                            }
                        }

                        ColumnLayout {
                            spacing: 4

                            Text {
                                text: "SpinBox (Up/Down):"
                                color: "#a6adc8"
                                font.pixelSize: 12
                            }
                            SpinBox {
                                id: spinBox
                                from: 0
                                to: 100
                                value: 0
                                editable: true
                                focusPolicy: Qt.StrongFocus

                                onValueChanged: {
                                    if (activeFocus)
                                        root.addToLog("SPINBOX", "value: " + value, 0);
                                }
                            }
                        }

                        ColumnLayout {
                            spacing: 4

                            Text {
                                text: "ComboBox:"
                                color: "#a6adc8"
                                font.pixelSize: 12
                            }
                            ComboBox {
                                id: comboBox
                                model: ["Option Alpha", "Option Beta", "Option Gamma", "Option Delta"]
                                focusPolicy: Qt.StrongFocus

                                onCurrentIndexChanged: {
                                    if (activeFocus)
                                        root.addToLog("COMBO", "selected: " + currentText, 0);
                                }
                            }
                        }
                    }

                    // Progress bar (controlled by +/- keys)
                    ColumnLayout {
                        spacing: 4
                        Layout.fillWidth: true

                        Text {
                            text: "Progress Bar (Press +/- keys to change):"
                            color: "#a6adc8"
                            font.pixelSize: 12
                        }
                        ProgressBar {
                            id: progressBar
                            Layout.fillWidth: true
                            from: 0.0
                            to: 1.0
                            value: 0.0

                            background: Rectangle {
                                radius: 4
                                color: "#45475a"
                                implicitHeight: 16
                            }

                            contentItem: Item {
                                implicitHeight: 16
                                Rectangle {
                                    width: progressBar.visualPosition * parent.width
                                    height: parent.height
                                    radius: 4
                                    color: "#a6e3a1"
                                }
                            }
                        }
                        Text {
                            text: Math.round(progressBar.value * 100) + "%"
                            color: "#cdd6f4"
                            font.pixelSize: 12
                        }
                    }

                    // Checkbox and RadioButton row
                    RowLayout {
                        spacing: 20
                        Layout.fillWidth: true

                        ColumnLayout {
                            spacing: 6
                            Text {
                                text: "Checkboxes (Space to toggle):"
                                color: "#a6adc8"
                                font.pixelSize: 12
                            }
                            CheckBox {
                                id: check1
                                text: "Option 1"
                                focusPolicy: Qt.StrongFocus

                                contentItem: Text {
                                    text: check1.text
                                    color: "#cdd6f4"
                                    font.pixelSize: 13
                                    leftPadding: check1.indicator.width + 6
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onToggled: root.addToLog("CHECK", "Option 1: " + (checked ? "ON" : "OFF"), 0)
                            }
                            CheckBox {
                                id: check2
                                text: "Option 2"
                                focusPolicy: Qt.StrongFocus

                                contentItem: Text {
                                    text: check2.text
                                    color: "#cdd6f4"
                                    font.pixelSize: 13
                                    leftPadding: check2.indicator.width + 6
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onToggled: root.addToLog("CHECK", "Option 2: " + (checked ? "ON" : "OFF"), 0)
                            }
                            CheckBox {
                                id: check3
                                text: "Option 3"
                                focusPolicy: Qt.StrongFocus

                                contentItem: Text {
                                    text: check3.text
                                    color: "#cdd6f4"
                                    font.pixelSize: 13
                                    leftPadding: check3.indicator.width + 6
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onToggled: root.addToLog("CHECK", "Option 3: " + (checked ? "ON" : "OFF"), 0)
                            }
                        }

                        ColumnLayout {
                            spacing: 6
                            Text {
                                text: "RadioButtons (Arrows to select):"
                                color: "#a6adc8"
                                font.pixelSize: 12
                            }
                            ButtonGroup { id: radioGroup }
                            RadioButton {
                                id: radio1
                                text: "Choice A"
                                ButtonGroup.group: radioGroup
                                checked: true
                                focusPolicy: Qt.StrongFocus

                                contentItem: Text {
                                    text: radio1.text
                                    color: "#cdd6f4"
                                    font.pixelSize: 13
                                    leftPadding: radio1.indicator.width + 6
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onToggled: if (checked) root.addToLog("RADIO", "Choice A selected", 0)
                            }
                            RadioButton {
                                id: radio2
                                text: "Choice B"
                                ButtonGroup.group: radioGroup
                                focusPolicy: Qt.StrongFocus

                                contentItem: Text {
                                    text: radio2.text
                                    color: "#cdd6f4"
                                    font.pixelSize: 13
                                    leftPadding: radio2.indicator.width + 6
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onToggled: if (checked) root.addToLog("RADIO", "Choice B selected", 0)
                            }
                            RadioButton {
                                id: radio3
                                text: "Choice C"
                                ButtonGroup.group: radioGroup
                                focusPolicy: Qt.StrongFocus

                                contentItem: Text {
                                    text: radio3.text
                                    color: "#cdd6f4"
                                    font.pixelSize: 13
                                    leftPadding: radio3.indicator.width + 6
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onToggled: if (checked) root.addToLog("RADIO", "Choice C selected", 0)
                            }
                        }

                        // Switch controls
                        ColumnLayout {
                            spacing: 6
                            Text {
                                text: "Switches:"
                                color: "#a6adc8"
                                font.pixelSize: 12
                            }
                            Switch {
                                id: switch1
                                text: "Feature 1"
                                focusPolicy: Qt.StrongFocus

                                contentItem: Text {
                                    text: switch1.text
                                    color: "#cdd6f4"
                                    font.pixelSize: 13
                                    leftPadding: switch1.indicator.width + 6
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onToggled: root.addToLog("SWITCH", "Feature 1: " + (checked ? "ON" : "OFF"), 0)
                            }
                            Switch {
                                id: switch2
                                text: "Feature 2"
                                focusPolicy: Qt.StrongFocus

                                contentItem: Text {
                                    text: switch2.text
                                    color: "#cdd6f4"
                                    font.pixelSize: 13
                                    leftPadding: switch2.indicator.width + 6
                                    verticalAlignment: Text.AlignVCenter
                                }

                                onToggled: root.addToLog("SWITCH", "Feature 2: " + (checked ? "ON" : "OFF"), 0)
                            }
                        }
                    }
                }
            }

            // ===== VIRTUAL KEYBOARD VISUALIZER =====
            Rectangle {
                Layout.fillWidth: true
                Layout.preferredHeight: keyboardGrid.implicitHeight + 60
                radius: 8
                color: "#313244"

                ColumnLayout {
                    anchors.fill: parent
                    anchors.margins: 12
                    spacing: 8

                    Text {
                        text: "Virtual Keyboard Visualizer (shows currently pressed keys)"
                        color: "#cdd6f4"
                        font.pixelSize: 15
                        font.bold: true
                    }

                    // Simplified keyboard layout
                    ColumnLayout {
                        id: keyboardGrid
                        spacing: 4
                        Layout.alignment: Qt.AlignHCenter

                        // Number row
                        Row {
                            spacing: 3
                            Layout.alignment: Qt.AlignHCenter
                            Repeater {
                                model: ["Esc", "1", "2", "3", "4", "5", "6", "7", "8", "9", "0", "-", "=", "Bksp"]
                                Rectangle {
                                    width: modelData === "Bksp" || modelData === "Esc" ? 52 : 36
                                    height: 36
                                    radius: 4
                                    color: {
                                        var keyCode = -1;
                                        switch(modelData) {
                                            case "Esc": keyCode = Qt.Key_Escape; break;
                                            case "1": keyCode = Qt.Key_1; break;
                                            case "2": keyCode = Qt.Key_2; break;
                                            case "3": keyCode = Qt.Key_3; break;
                                            case "4": keyCode = Qt.Key_4; break;
                                            case "5": keyCode = Qt.Key_5; break;
                                            case "6": keyCode = Qt.Key_6; break;
                                            case "7": keyCode = Qt.Key_7; break;
                                            case "8": keyCode = Qt.Key_8; break;
                                            case "9": keyCode = Qt.Key_9; break;
                                            case "0": keyCode = Qt.Key_0; break;
                                            case "-": keyCode = Qt.Key_Minus; break;
                                            case "=": keyCode = Qt.Key_Equal; break;
                                            case "Bksp": keyCode = Qt.Key_Backspace; break;
                                        }
                                        return (keyCode in root.pressedKeys) ? "#89b4fa" : "#45475a";
                                    }
                                    Text {
                                        anchors.centerIn: parent
                                        text: modelData
                                        color: parent.color === "#89b4fa" ? "#1e1e2e" : "#cdd6f4"
                                        font.pixelSize: 11
                                    }
                                }
                            }
                        }

                        // QWERTY row
                        Row {
                            spacing: 3
                            Layout.alignment: Qt.AlignHCenter
                            Repeater {
                                model: ["Tab", "Q", "W", "E", "R", "T", "Y", "U", "I", "O", "P", "[", "]", "\\"]
                                Rectangle {
                                    width: modelData === "Tab" ? 52 : 36
                                    height: 36
                                    radius: 4
                                    color: {
                                        var keyCode = -1;
                                        switch(modelData) {
                                            case "Tab": keyCode = Qt.Key_Tab; break;
                                            case "Q": keyCode = Qt.Key_Q; break;
                                            case "W": keyCode = Qt.Key_W; break;
                                            case "E": keyCode = Qt.Key_E; break;
                                            case "R": keyCode = Qt.Key_R; break;
                                            case "T": keyCode = Qt.Key_T; break;
                                            case "Y": keyCode = Qt.Key_Y; break;
                                            case "U": keyCode = Qt.Key_U; break;
                                            case "I": keyCode = Qt.Key_I; break;
                                            case "O": keyCode = Qt.Key_O; break;
                                            case "P": keyCode = Qt.Key_P; break;
                                            case "[": keyCode = Qt.Key_BracketLeft; break;
                                            case "]": keyCode = Qt.Key_BracketRight; break;
                                            case "\\": keyCode = Qt.Key_Backslash; break;
                                        }
                                        return (keyCode in root.pressedKeys) ? "#a6e3a1" : "#45475a";
                                    }
                                    Text {
                                        anchors.centerIn: parent
                                        text: modelData
                                        color: parent.color === "#a6e3a1" ? "#1e1e2e" : "#cdd6f4"
                                        font.pixelSize: 11
                                    }
                                }
                            }
                        }

                        // Home row
                        Row {
                            spacing: 3
                            Layout.alignment: Qt.AlignHCenter
                            Repeater {
                                model: ["Caps", "A", "S", "D", "F", "G", "H", "J", "K", "L", ";", "'", "Enter"]
                                Rectangle {
                                    width: modelData === "Caps" || modelData === "Enter" ? 62 : 36
                                    height: 36
                                    radius: 4
                                    color: {
                                        var keyCode = -1;
                                        switch(modelData) {
                                            case "Caps": keyCode = Qt.Key_CapsLock; break;
                                            case "A": keyCode = Qt.Key_A; break;
                                            case "S": keyCode = Qt.Key_S; break;
                                            case "D": keyCode = Qt.Key_D; break;
                                            case "F": keyCode = Qt.Key_F; break;
                                            case "G": keyCode = Qt.Key_G; break;
                                            case "H": keyCode = Qt.Key_H; break;
                                            case "J": keyCode = Qt.Key_J; break;
                                            case "K": keyCode = Qt.Key_K; break;
                                            case "L": keyCode = Qt.Key_L; break;
                                            case ";": keyCode = Qt.Key_Semicolon; break;
                                            case "'": keyCode = Qt.Key_Apostrophe; break;
                                            case "Enter": keyCode = Qt.Key_Return; break;
                                        }
                                        return (keyCode in root.pressedKeys) ? "#f9e2af" : "#45475a";
                                    }
                                    Text {
                                        anchors.centerIn: parent
                                        text: modelData
                                        color: parent.color === "#f9e2af" ? "#1e1e2e" : "#cdd6f4"
                                        font.pixelSize: 11
                                    }
                                }
                            }
                        }

                        // Bottom row
                        Row {
                            spacing: 3
                            Layout.alignment: Qt.AlignHCenter
                            Repeater {
                                model: ["Shift", "Z", "X", "C", "V", "B", "N", "M", ",", ".", "/", "Shift"]
                                Rectangle {
                                    width: modelData === "Shift" ? 75 : 36
                                    height: 36
                                    radius: 4
                                    color: {
                                        var keyCode = -1;
                                        switch(modelData) {
                                            case "Shift": keyCode = Qt.Key_Shift; break;
                                            case "Z": keyCode = Qt.Key_Z; break;
                                            case "X": keyCode = Qt.Key_X; break;
                                            case "C": keyCode = Qt.Key_C; break;
                                            case "V": keyCode = Qt.Key_V; break;
                                            case "B": keyCode = Qt.Key_B; break;
                                            case "N": keyCode = Qt.Key_N; break;
                                            case "M": keyCode = Qt.Key_M; break;
                                            case ",": keyCode = Qt.Key_Comma; break;
                                            case ".": keyCode = Qt.Key_Period; break;
                                            case "/": keyCode = Qt.Key_Slash; break;
                                        }
                                        return (keyCode in root.pressedKeys) ? "#f5c2e7" : "#45475a";
                                    }
                                    Text {
                                        anchors.centerIn: parent
                                        text: modelData
                                        color: parent.color === "#f5c2e7" ? "#1e1e2e" : "#cdd6f4"
                                        font.pixelSize: 11
                                    }
                                }
                            }
                        }

                        // Spacebar row
                        Row {
                            spacing: 3
                            Layout.alignment: Qt.AlignHCenter
                            Repeater {
                                model: ["Ctrl", "Alt", "Space", "Alt", "Ctrl"]
                                Rectangle {
                                    width: modelData === "Space" ? 250 : 52
                                    height: 36
                                    radius: 4
                                    color: {
                                        var keyCode = -1;
                                        switch(modelData) {
                                            case "Ctrl": keyCode = Qt.Key_Control; break;
                                            case "Alt": keyCode = Qt.Key_Alt; break;
                                            case "Space": keyCode = Qt.Key_Space; break;
                                        }
                                        return (keyCode in root.pressedKeys) ? "#fab387" : "#45475a";
                                    }
                                    Text {
                                        anchors.centerIn: parent
                                        text: modelData
                                        color: parent.color === "#fab387" ? "#1e1e2e" : "#cdd6f4"
                                        font.pixelSize: 11
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // ===== KEY EVENT LOG =====
            Rectangle {
                Layout.fillWidth: true
                Layout.preferredHeight: 200
                radius: 8
                color: "#313244"

                ColumnLayout {
                    anchors.fill: parent
                    anchors.margins: 12
                    spacing: 8

                    RowLayout {
                        Text {
                            text: "Key Event Log"
                            color: "#cdd6f4"
                            font.pixelSize: 15
                            font.bold: true
                        }
                        Item { Layout.fillWidth: true }
                        Button {
                            text: "Clear Log"
                            focusPolicy: Qt.StrongFocus
                            onClicked: {
                                logModel.clear();
                                root.keyPressCount = 0;
                            }

                            background: Rectangle {
                                radius: 4
                                color: parent.activeFocus ? "#f38ba8" : "#45475a"
                            }
                            contentItem: Text {
                                text: parent.text
                                color: "#cdd6f4"
                                font.pixelSize: 12
                                horizontalAlignment: Text.AlignHCenter
                            }
                        }
                    }

                    ListView {
                        id: logListView
                        Layout.fillWidth: true
                        Layout.fillHeight: true
                        clip: true
                        model: logModel

                        delegate: Rectangle {
                            width: logListView.width
                            height: 22
                            color: index % 2 === 0 ? "transparent" : "#3b3d52"
                            radius: 2

                            Text {
                                anchors.fill: parent
                                anchors.leftMargin: 8
                                text: model.text
                                color: {
                                    if (model.eventType === "PRESS") return "#a6e3a1";
                                    if (model.eventType === "RELEASE") return "#f38ba8";
                                    if (model.eventType === "BUTTON") return "#89b4fa";
                                    if (model.eventType === "SLIDER") return "#f9e2af";
                                    if (model.eventType === "COMBO") return "#f5c2e7";
                                    return "#cdd6f4";
                                }
                                font.family: "monospace"
                                font.pixelSize: 11
                                verticalAlignment: Text.AlignVCenter
                                elide: Text.ElideRight
                            }
                        }

                        ScrollBar.vertical: ScrollBar {
                            active: true
                        }
                    }
                }
            }

            // ===== INSTRUCTIONS =====
            Rectangle {
                Layout.fillWidth: true
                Layout.preferredHeight: instructionsText.implicitHeight + 24
                radius: 8
                color: "#313244"

                Text {
                    id: instructionsText
                    anchors.fill: parent
                    anchors.margins: 12
                    wrapMode: Text.WordWrap
                    textFormat: Text.RichText
                    color: "#a6adc8"
                    font.pixelSize: 12
                    text: "
<b style='color: #cdd6f4;'>Keyboard Navigation Guide:</b><br>
• <b>Tab / Shift+Tab</b> — Navigate between QML controls<br>
• <b>Arrow Keys</b> — Adjust slider, navigate radio buttons, scroll combo box<br>
• <b>Space / Enter</b> — Activate focused button, toggle checkbox/switch<br>
• <b>+/-</b> — Increase/decrease progress bar<br>
• <b>F5</b> — Focus QML view from widgets<br>
• <b>Alt</b> — Access widget menu bar<br>
• <b>Type in TextFields</b> — Verify text input works<br>
• <b>Any key</b> — Lights up on virtual keyboard, logged below
                    "
                }
            }

            // Bottom spacer
            Item {
                Layout.preferredHeight: 12
            }
        }
    }
}
```

## Build and Run Instructions

```bash
# Create build directory
mkdir build && cd build

# Configure with CMake (adjust Qt path as needed)
cmake .. -DCMAKE_PREFIX_PATH=/path/to/Qt/6.10.0/gcc_64

# Build
cmake --build . --parallel

# Run
./HybridWidgetsQml
```

## Key Architecture Decisions & How Keyboard Forwarding Works

```
┌──────────────────────────────────────────────────────────────┐
│  QMainWindow (MainWindow)                                     │
│  ┌─────────────┐  ┌──────────────────────────────────────┐   │
│  │  Menu Bar    │  │  Title Bar                           │   │
│  ├─────────────┤  └──────────────────────────────────────┘   │
│  │             │                                              │
│  │  Widget     │  ┌──────────────────────────────────────┐   │
│  │  Sidebar    │  │  QWidget::createWindowContainer       │   │
│  │             │  │  ┌──────────────────────────────────┐ │   │
│  │  - Buttons  │  │  │  QQuickView                      │ │   │
│  │  - TextEdit │  │  │  ┌──────────────────────────────┐│ │   │
│  │             │  │  │  │  QML Root Rectangle          ││ │   │
│  │             │  │  │  │  - Key visualizer             ││ │   │
│  │             │  │  │  │  - Buttons, TextFields       ││ │   │
│  │             │  │  │  │  - Slider, ComboBox          ││ │   │
│  │             │  │  │  │  - Checkboxes, Switches      ││ │   │
│  │             │  │  │  │  - Event Log                  ││ │   │
│  │             │  │  │  └──────────────────────────────┘│ │   │
│  │             │  │  └──────────────────────────────────┘ │   │
│  │             │  └──────────────────────────────────────┘   │
│  └─────────────┘                                              │
│  ┌────────────────────────────────────────────────────────┐   │
│  │  Status Bar                                             │   │
│  └────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────┘

Event Flow:
                                                    
  Physical Key Press                                
       │                                            
       ▼                                            
  QApplication event loop                           
       │                                            
       ▼                                            
  Container Widget receives QKeyEvent               
       │                                            
       ▼                                            
  QmlKeyFilter::eventFilter() ◄── installed on container
       │                                            
       ├──► Updates Q_PROPERTY values               
       │    (lastKey, modifiers, etc.)               
       │         │                                   
       │         ▼                                   
       │    QML bindings update automatically        
       │    (key stats display, etc.)                
       │                                            
       ├──► Emits keyPressed/keyReleased signals     
       │         │                                   
       │         ▼                                   
       │    Status bar updates in MainWindow         
       │                                            
       ├──► Clones QKeyEvent                         
       │    Posts to QQuickView via                   
       │    QCoreApplication::postEvent()            
       │         │                                   
       │         ▼                                   
       │    QQuickView dispatches to                  
       │    focused QML Item                          
       │         │                                   
       │         ▼                                   
       │    QML Keys.onPressed/onReleased fire       
       │    QML controls react (TextField types,      
       │    Slider moves, Buttons activate, etc.)     
       │                                            
       └──► Returns false (widgets chain continues)  
            Menu shortcuts still work (Ctrl+S, etc.) 
```

The critical technique is the **three-pronged approach**:

1. **`QWidget::createWindowContainer()`** wraps the `QQuickView` as a widget, giving it a place in the widget focus chain
2. **`QmlKeyFilter` event filter** on the container intercepts key events *before* they vanish into the container's internal handling, clones them, and `postEvent()`s them to the `QQuickView` window — this ensures QML's native `Keys` attached property and all `focusPolicy: Qt.StrongFocus` controls receive keyboard input
3. **Context property `keyFilter`** exposes key state as bindable properties and signals, giving QML a secondary channel to observe *all* keys (even ones consumed by the focus chain before reaching the root item)