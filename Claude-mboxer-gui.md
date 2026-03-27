

# Email Archive Viewer - Qt6 Widgets Application

Below is the complete source code for a Qt6 Widgets-based email archive viewer that follows the developer guideline. It uses `qlitehtml` for HTML email rendering, supports light/dark theme switching, and features a macOS-inspired QSS style.

## Project Structure

```
email_viewer/
├── CMakeLists.txt
├── src/
│   ├── main.cpp
│   ├── mainwindow.h
│   ├── mainwindow.cpp
│   ├── emailstore.h
│   ├── emailstore.cpp
│   ├── emailmessage.h
│   ├── emailmessage.cpp
│   ├── foldertreemodel.h
│   ├── foldertreemodel.cpp
│   ├── messagelistmodel.h
│   ├── messagelistmodel.cpp
│   ├── messagelistdelegate.h
│   ├── messagelistdelegate.cpp
│   ├── messageviewwidget.h
│   ├── messageviewwidget.cpp
│   ├── mboxparser.h
│   ├── mboxparser.cpp
│   ├── searchwidget.h
│   ├── searchwidget.cpp
│   ├── threadviewwidget.h
│   ├── threadviewwidget.cpp
│   ├── thememanager.h
│   ├── thememanager.cpp
│   └── attachmentwidget.h
│   └── attachmentwidget.cpp
├── resources/
│   ├── resources.qrc
│   ├── styles/
│   │   ├── macos_light.qss
│   │   └── macos_dark.qss
│   └── icons/
│       ├── inbox.svg
│       ├── sent.svg
│       ├── folder.svg
│       ├── label.svg
│       ├── attachment.svg
│       ├── search.svg
│       ├── sun.svg
│       ├── moon.svg
│       ├── calendar.svg
│       └── mail.svg
└── README.md
```

---

## CMakeLists.txt

```cmake
cmake_minimum_required(VERSION 3.22)
project(EmailArchiveViewer VERSION 1.0.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 20)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_AUTOMOC ON)
set(CMAKE_AUTORCC ON)
set(CMAKE_AUTOUIC ON)

# ── Find Qt6 ──────────────────────────────────────────────────────────────────
find_package(Qt6 6.10 REQUIRED COMPONENTS
    Core
    Gui
    Widgets
)

# ── Find qlitehtml ────────────────────────────────────────────────────────────
# qlitehtml provides a Qt6-compatible litehtml wrapper widget.
# It should be installed such that find_package can locate it.
# If built from source alongside, use add_subdirectory instead.
#
# Option A: installed system-wide or via CMAKE_PREFIX_PATH
find_package(qlitehtml QUIET)

# Option B: If qlitehtml is a subdirectory (submodule)
if(NOT qlitehtml_FOUND)
    if(EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/third_party/qlitehtml/CMakeLists.txt")
        add_subdirectory(third_party/qlitehtml)
        set(QLITEHTML_FOUND TRUE)
    else()
        message(STATUS "qlitehtml not found – building with fallback QTextBrowser for HTML rendering.")
        message(STATUS "To enable qlitehtml, either:")
        message(STATUS "  1. Install qlitehtml and set CMAKE_PREFIX_PATH, or")
        message(STATUS "  2. Clone qlitehtml into third_party/qlitehtml/")
        set(QLITEHTML_FOUND FALSE)
    endif()
endif()

# ── Sources ───────────────────────────────────────────────────────────────────
set(SOURCES
    src/main.cpp
    src/mainwindow.h
    src/mainwindow.cpp
    src/emailstore.h
    src/emailstore.cpp
    src/emailmessage.h
    src/emailmessage.cpp
    src/foldertreemodel.h
    src/foldertreemodel.cpp
    src/messagelistmodel.h
    src/messagelistmodel.cpp
    src/messagelistdelegate.h
    src/messagelistdelegate.cpp
    src/messageviewwidget.h
    src/messageviewwidget.cpp
    src/mboxparser.h
    src/mboxparser.cpp
    src/searchwidget.h
    src/searchwidget.cpp
    src/threadviewwidget.h
    src/threadviewwidget.cpp
    src/thememanager.h
    src/thememanager.cpp
    src/attachmentwidget.h
    src/attachmentwidget.cpp
)

set(RESOURCES
    resources/resources.qrc
)

# ── Executable ────────────────────────────────────────────────────────────────
add_executable(${PROJECT_NAME} ${SOURCES} ${RESOURCES})

target_include_directories(${PROJECT_NAME} PRIVATE src)

target_link_libraries(${PROJECT_NAME} PRIVATE
    Qt6::Core
    Qt6::Gui
    Qt6::Widgets
)

if(qlitehtml_FOUND OR QLITEHTML_FOUND)
    target_link_libraries(${PROJECT_NAME} PRIVATE qlitehtml)
    target_compile_definitions(${PROJECT_NAME} PRIVATE HAS_QLITEHTML=1)
else()
    target_compile_definitions(${PROJECT_NAME} PRIVATE HAS_QLITEHTML=0)
endif()

# ── Install ───────────────────────────────────────────────────────────────────
install(TARGETS ${PROJECT_NAME} DESTINATION bin)
```

---

## resources/resources.qrc

```xml
<RCC>
    <qresource prefix="/">
        <file>styles/macos_light.qss</file>
        <file>styles/macos_dark.qss</file>
        <file>icons/inbox.svg</file>
        <file>icons/sent.svg</file>
        <file>icons/folder.svg</file>
        <file>icons/label.svg</file>
        <file>icons/attachment.svg</file>
        <file>icons/search.svg</file>
        <file>icons/sun.svg</file>
        <file>icons/moon.svg</file>
        <file>icons/calendar.svg</file>
        <file>icons/mail.svg</file>
    </qresource>
</RCC>
```

---

## SVG Icon Files

### resources/icons/inbox.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12H16L14 15H10L8 12H2"/><path d="M5.45 5.11L2 12V18A2 2 0 0 0 4 20H20A2 2 0 0 0 22 18V12L18.55 5.11A2 2 0 0 0 16.76 4H7.24A2 2 0 0 0 5.45 5.11Z"/></svg>
```

### resources/icons/sent.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
```

### resources/icons/folder.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
```

### resources/icons/label.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
```

### resources/icons/attachment.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
```

### resources/icons/search.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
```

### resources/icons/sun.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
```

### resources/icons/moon.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
```

### resources/icons/calendar.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
```

### resources/icons/mail.svg
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
```

---

## QSS Stylesheets (macOS-inspired)

### resources/styles/macos_light.qss

```css
/* ═══════════════════════════════════════════════════════════════════════════
   macOS Light Theme — Apple Human Interface Guidelines inspired
   ═══════════════════════════════════════════════════════════════════════════ */

/* ── Global ─────────────────────────────────────────────────────────────── */
QWidget {
    font-family: -apple-system, "SF Pro Text", "Helvetica Neue", "Segoe UI", sans-serif;
    font-size: 13px;
    color: #1d1d1f;
    background-color: #ffffff;
    selection-background-color: #007AFF;
    selection-color: #ffffff;
}

/* ── Main Window ────────────────────────────────────────────────────────── */
QMainWindow {
    background-color: #f5f5f7;
}

QMainWindow::separator {
    width: 1px;
    height: 1px;
    background: #d2d2d7;
}

/* ── Menu Bar ───────────────────────────────────────────────────────────── */
QMenuBar {
    background-color: rgba(255, 255, 255, 0.8);
    border-bottom: 1px solid #d2d2d7;
    padding: 2px 8px;
    font-size: 13px;
}

QMenuBar::item {
    padding: 4px 12px;
    border-radius: 6px;
    background: transparent;
}

QMenuBar::item:selected {
    background-color: rgba(0, 122, 255, 0.1);
}

QMenuBar::item:pressed {
    background-color: #007AFF;
    color: white;
}

QMenu {
    background-color: rgba(255, 255, 255, 0.95);
    border: 1px solid #d2d2d7;
    border-radius: 10px;
    padding: 5px 0px;
}

QMenu::item {
    padding: 6px 30px 6px 20px;
    border-radius: 5px;
    margin: 1px 5px;
}

QMenu::item:selected {
    background-color: #007AFF;
    color: white;
}

QMenu::separator {
    height: 1px;
    background: #d2d2d7;
    margin: 5px 12px;
}

/* ── Tool Bar ───────────────────────────────────────────────────────────── */
QToolBar {
    background-color: #f5f5f7;
    border-bottom: 1px solid #d2d2d7;
    padding: 4px 8px;
    spacing: 4px;
}

QToolButton {
    background: transparent;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 12px;
    color: #007AFF;
}

QToolButton:hover {
    background-color: rgba(0, 122, 255, 0.08);
}

QToolButton:pressed {
    background-color: rgba(0, 122, 255, 0.15);
}

QToolButton:checked {
    background-color: rgba(0, 122, 255, 0.12);
    color: #007AFF;
}

/* ── Splitter ───────────────────────────────────────────────────────────── */
QSplitter::handle {
    background: #e5e5ea;
    width: 1px;
    height: 1px;
}

QSplitter::handle:hover {
    background: #007AFF;
}

/* ── Tree View (Folder Tree / Sidebar) ──────────────────────────────────── */
QTreeView {
    background-color: #f5f5f7;
    border: none;
    outline: none;
    font-size: 13px;
    show-decoration-selected: 1;
}

QTreeView::item {
    padding: 5px 8px;
    border-radius: 7px;
    margin: 1px 6px;
    min-height: 22px;
}

QTreeView::item:selected {
    background-color: rgba(0, 122, 255, 0.12);
    color: #007AFF;
}

QTreeView::item:hover:!selected {
    background-color: rgba(0, 0, 0, 0.04);
}

QTreeView::branch {
    background: transparent;
}

QTreeView::branch:has-children:!has-siblings:closed,
QTreeView::branch:closed:has-children:has-siblings {
    image: none;
    border-image: none;
}

QTreeView::branch:open:has-children:!has-siblings,
QTreeView::branch:open:has-children:has-siblings {
    image: none;
    border-image: none;
}

/* ── List View (Message List) ───────────────────────────────────────────── */
QListView {
    background-color: #ffffff;
    border: none;
    outline: none;
    font-size: 13px;
    show-decoration-selected: 1;
}

QListView::item {
    padding: 10px 12px;
    border-bottom: 1px solid #f2f2f7;
    min-height: 48px;
}

QListView::item:selected {
    background-color: rgba(0, 122, 255, 0.08);
    border-left: 3px solid #007AFF;
}

QListView::item:hover:!selected {
    background-color: rgba(0, 0, 0, 0.02);
}

/* ── Scroll Bars ────────────────────────────────────────────────────────── */
QScrollBar:vertical {
    background: transparent;
    width: 8px;
    margin: 0;
    border: none;
}

QScrollBar::handle:vertical {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
    min-height: 30px;
}

QScrollBar::handle:vertical:hover {
    background: rgba(0, 0, 0, 0.35);
}

QScrollBar::add-line:vertical,
QScrollBar::sub-line:vertical {
    height: 0;
    background: none;
    border: none;
}

QScrollBar::add-page:vertical,
QScrollBar::sub-page:vertical {
    background: none;
}

QScrollBar:horizontal {
    background: transparent;
    height: 8px;
    margin: 0;
    border: none;
}

QScrollBar::handle:horizontal {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
    min-width: 30px;
}

QScrollBar::handle:horizontal:hover {
    background: rgba(0, 0, 0, 0.35);
}

QScrollBar::add-line:horizontal,
QScrollBar::sub-line:horizontal {
    width: 0;
    background: none;
    border: none;
}

QScrollBar::add-page:horizontal,
QScrollBar::sub-page:horizontal {
    background: none;
}

/* ── Line Edit / Search ─────────────────────────────────────────────────── */
QLineEdit {
    background-color: rgba(142, 142, 147, 0.12);
    border: 1px solid transparent;
    border-radius: 8px;
    padding: 6px 12px 6px 30px;
    font-size: 13px;
    color: #1d1d1f;
}

QLineEdit:focus {
    border: 1px solid #007AFF;
    background-color: #ffffff;
}

QLineEdit::placeholder {
    color: #8e8e93;
}

/* ── Labels ─────────────────────────────────────────────────────────────── */
QLabel {
    color: #1d1d1f;
    background: transparent;
}

QLabel#headerLabel {
    font-size: 11px;
    color: #86868b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

QLabel#senderLabel {
    font-size: 14px;
    font-weight: 600;
    color: #1d1d1f;
}

QLabel#subjectLabel {
    font-size: 16px;
    font-weight: 700;
    color: #1d1d1f;
}

QLabel#dateLabel {
    font-size: 12px;
    color: #86868b;
}

QLabel#recipientLabel {
    font-size: 12px;
    color: #636366;
}

/* ── Push Button ────────────────────────────────────────────────────────── */
QPushButton {
    background-color: #007AFF;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
}

QPushButton:hover {
    background-color: #0056CC;
}

QPushButton:pressed {
    background-color: #004099;
}

QPushButton:disabled {
    background-color: #d2d2d7;
    color: #8e8e93;
}

QPushButton#secondaryButton {
    background-color: rgba(142, 142, 147, 0.12);
    color: #007AFF;
}

QPushButton#secondaryButton:hover {
    background-color: rgba(0, 122, 255, 0.1);
}

/* ── Tab Widget ─────────────────────────────────────────────────────────── */
QTabWidget::pane {
    border: none;
    background-color: #ffffff;
}

QTabBar::tab {
    background: transparent;
    padding: 8px 16px;
    margin-right: 2px;
    border-bottom: 2px solid transparent;
    font-size: 13px;
    color: #86868b;
}

QTabBar::tab:selected {
    color: #007AFF;
    border-bottom: 2px solid #007AFF;
}

QTabBar::tab:hover:!selected {
    color: #1d1d1f;
}

/* ── Group Box ──────────────────────────────────────────────────────────── */
QGroupBox {
    background-color: #ffffff;
    border: 1px solid #e5e5ea;
    border-radius: 12px;
    margin-top: 12px;
    padding: 16px;
    font-size: 13px;
    font-weight: 600;
}

QGroupBox::title {
    subcontrol-origin: margin;
    subcontrol-position: top left;
    padding: 0 8px;
    color: #1d1d1f;
}

/* ── Status Bar ─────────────────────────────────────────────────────────── */
QStatusBar {
    background-color: #f5f5f7;
    border-top: 1px solid #d2d2d7;
    font-size: 12px;
    color: #86868b;
    padding: 2px 8px;
}

QStatusBar::item {
    border: none;
}

/* ── Header View (for table/list column headers) ────────────────────────── */
QHeaderView::section {
    background-color: #f5f5f7;
    border: none;
    border-bottom: 1px solid #d2d2d7;
    border-right: 1px solid #e5e5ea;
    padding: 8px 12px;
    font-size: 11px;
    font-weight: 600;
    color: #86868b;
    text-transform: uppercase;
}

/* ── Text Browser (fallback message view) ───────────────────────────────── */
QTextBrowser {
    background-color: #ffffff;
    border: none;
    padding: 16px;
    font-size: 14px;
    line-height: 1.6;
}

/* ── Frame (message header area) ────────────────────────────────────────── */
QFrame#messageHeaderFrame {
    background-color: #ffffff;
    border-bottom: 1px solid #e5e5ea;
    padding: 16px;
}

QFrame#attachmentFrame {
    background-color: #f5f5f7;
    border: 1px solid #e5e5ea;
    border-radius: 10px;
    padding: 10px;
    margin: 8px 16px;
}

/* ── Combo Box ──────────────────────────────────────────────────────────── */
QComboBox {
    background-color: rgba(142, 142, 147, 0.12);
    border: 1px solid transparent;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 13px;
    min-width: 120px;
}

QComboBox:focus {
    border: 1px solid #007AFF;
}

QComboBox::drop-down {
    border: none;
    width: 24px;
}

QComboBox QAbstractItemView {
    background-color: #ffffff;
    border: 1px solid #d2d2d7;
    border-radius: 8px;
    selection-background-color: #007AFF;
    selection-color: white;
    padding: 4px;
}

/* ── Progress Bar ───────────────────────────────────────────────────────── */
QProgressBar {
    background-color: rgba(142, 142, 147, 0.12);
    border: none;
    border-radius: 4px;
    height: 4px;
    text-align: center;
    font-size: 0px;
}

QProgressBar::chunk {
    background-color: #007AFF;
    border-radius: 4px;
}

/* ── Tooltip ────────────────────────────────────────────────────────────── */
QToolTip {
    background-color: rgba(30, 30, 30, 0.95);
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 12px;
}

/* ── Custom badge/pill for labels ───────────────────────────────────────── */
QLabel#labelBadge {
    background-color: rgba(0, 122, 255, 0.1);
    color: #007AFF;
    border-radius: 10px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 600;
}

QLabel#countBadge {
    background-color: #007AFF;
    color: white;
    border-radius: 9px;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 600;
    min-width: 18px;
    max-height: 18px;
}
```

### resources/styles/macos_dark.qss

```css
/* ═══════════════════════════════════════════════════════════════════════════
   macOS Dark Theme — Apple Human Interface Guidelines inspired
   ═══════════════════════════════════════════════════════════════════════════ */

/* ── Global ─────────────────────────────────────────────────────────────── */
QWidget {
    font-family: -apple-system, "SF Pro Text", "Helvetica Neue", "Segoe UI", sans-serif;
    font-size: 13px;
    color: #f5f5f7;
    background-color: #1c1c1e;
    selection-background-color: #0A84FF;
    selection-color: #ffffff;
}

/* ── Main Window ────────────────────────────────────────────────────────── */
QMainWindow {
    background-color: #1c1c1e;
}

QMainWindow::separator {
    width: 1px;
    height: 1px;
    background: #38383a;
}

/* ── Menu Bar ───────────────────────────────────────────────────────────── */
QMenuBar {
    background-color: rgba(28, 28, 30, 0.9);
    border-bottom: 1px solid #38383a;
    padding: 2px 8px;
    font-size: 13px;
}

QMenuBar::item {
    padding: 4px 12px;
    border-radius: 6px;
    background: transparent;
}

QMenuBar::item:selected {
    background-color: rgba(10, 132, 255, 0.2);
}

QMenuBar::item:pressed {
    background-color: #0A84FF;
    color: white;
}

QMenu {
    background-color: rgba(44, 44, 46, 0.95);
    border: 1px solid #48484a;
    border-radius: 10px;
    padding: 5px 0px;
}

QMenu::item {
    padding: 6px 30px 6px 20px;
    border-radius: 5px;
    margin: 1px 5px;
    color: #f5f5f7;
}

QMenu::item:selected {
    background-color: #0A84FF;
    color: white;
}

QMenu::separator {
    height: 1px;
    background: #48484a;
    margin: 5px 12px;
}

/* ── Tool Bar ───────────────────────────────────────────────────────────── */
QToolBar {
    background-color: #2c2c2e;
    border-bottom: 1px solid #38383a;
    padding: 4px 8px;
    spacing: 4px;
}

QToolButton {
    background: transparent;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 12px;
    color: #0A84FF;
}

QToolButton:hover {
    background-color: rgba(10, 132, 255, 0.15);
}

QToolButton:pressed {
    background-color: rgba(10, 132, 255, 0.25);
}

QToolButton:checked {
    background-color: rgba(10, 132, 255, 0.2);
    color: #0A84FF;
}

/* ── Splitter ───────────────────────────────────────────────────────────── */
QSplitter::handle {
    background: #38383a;
    width: 1px;
    height: 1px;
}

QSplitter::handle:hover {
    background: #0A84FF;
}

/* ── Tree View (Folder Tree / Sidebar) ──────────────────────────────────── */
QTreeView {
    background-color: #2c2c2e;
    border: none;
    outline: none;
    font-size: 13px;
    show-decoration-selected: 1;
}

QTreeView::item {
    padding: 5px 8px;
    border-radius: 7px;
    margin: 1px 6px;
    min-height: 22px;
    color: #f5f5f7;
}

QTreeView::item:selected {
    background-color: rgba(10, 132, 255, 0.2);
    color: #0A84FF;
}

QTreeView::item:hover:!selected {
    background-color: rgba(255, 255, 255, 0.05);
}

QTreeView::branch {
    background: transparent;
}

/* ── List View (Message List) ───────────────────────────────────────────── */
QListView {
    background-color: #1c1c1e;
    border: none;
    outline: none;
    font-size: 13px;
    show-decoration-selected: 1;
}

QListView::item {
    padding: 10px 12px;
    border-bottom: 1px solid #2c2c2e;
    min-height: 48px;
    color: #f5f5f7;
}

QListView::item:selected {
    background-color: rgba(10, 132, 255, 0.15);
    border-left: 3px solid #0A84FF;
}

QListView::item:hover:!selected {
    background-color: rgba(255, 255, 255, 0.03);
}

/* ── Scroll Bars ────────────────────────────────────────────────────────── */
QScrollBar:vertical {
    background: transparent;
    width: 8px;
    margin: 0;
    border: none;
}

QScrollBar::handle:vertical {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    min-height: 30px;
}

QScrollBar::handle:vertical:hover {
    background: rgba(255, 255, 255, 0.35);
}

QScrollBar::add-line:vertical,
QScrollBar::sub-line:vertical {
    height: 0;
    background: none;
    border: none;
}

QScrollBar::add-page:vertical,
QScrollBar::sub-page:vertical {
    background: none;
}

QScrollBar:horizontal {
    background: transparent;
    height: 8px;
    margin: 0;
    border: none;
}

QScrollBar::handle:horizontal {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    min-width: 30px;
}

QScrollBar::handle:horizontal:hover {
    background: rgba(255, 255, 255, 0.35);
}

QScrollBar::add-line:horizontal,
QScrollBar::sub-line:horizontal {
    width: 0;
    background: none;
    border: none;
}

QScrollBar::add-page:horizontal,
QScrollBar::sub-page:horizontal {
    background: none;
}

/* ── Line Edit / Search ─────────────────────────────────────────────────── */
QLineEdit {
    background-color: rgba(118, 118, 128, 0.24);
    border: 1px solid transparent;
    border-radius: 8px;
    padding: 6px 12px 6px 30px;
    font-size: 13px;
    color: #f5f5f7;
}

QLineEdit:focus {
    border: 1px solid #0A84FF;
    background-color: #2c2c2e;
}

QLineEdit::placeholder {
    color: #636366;
}

/* ── Labels ─────────────────────────────────────────────────────────────── */
QLabel {
    color: #f5f5f7;
    background: transparent;
}

QLabel#headerLabel {
    font-size: 11px;
    color: #98989d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

QLabel#senderLabel {
    font-size: 14px;
    font-weight: 600;
    color: #f5f5f7;
}

QLabel#subjectLabel {
    font-size: 16px;
    font-weight: 700;
    color: #f5f5f7;
}

QLabel#dateLabel {
    font-size: 12px;
    color: #98989d;
}

QLabel#recipientLabel {
    font-size: 12px;
    color: #aeaeb2;
}

/* ── Push Button ────────────────────────────────────────────────────────── */
QPushButton {
    background-color: #0A84FF;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
}

QPushButton:hover {
    background-color: #409CFF;
}

QPushButton:pressed {
    background-color: #0060CC;
}

QPushButton:disabled {
    background-color: #48484a;
    color: #636366;
}

QPushButton#secondaryButton {
    background-color: rgba(118, 118, 128, 0.24);
    color: #0A84FF;
}

QPushButton#secondaryButton:hover {
    background-color: rgba(10, 132, 255, 0.2);
}

/* ── Tab Widget ─────────────────────────────────────────────────────────── */
QTabWidget::pane {
    border: none;
    background-color: #1c1c1e;
}

QTabBar::tab {
    background: transparent;
    padding: 8px 16px;
    margin-right: 2px;
    border-bottom: 2px solid transparent;
    font-size: 13px;
    color: #98989d;
}

QTabBar::tab:selected {
    color: #0A84FF;
    border-bottom: 2px solid #0A84FF;
}

QTabBar::tab:hover:!selected {
    color: #f5f5f7;
}

/* ── Group Box ──────────────────────────────────────────────────────────── */
QGroupBox {
    background-color: #2c2c2e;
    border: 1px solid #38383a;
    border-radius: 12px;
    margin-top: 12px;
    padding: 16px;
    font-size: 13px;
    font-weight: 600;
    color: #f5f5f7;
}

QGroupBox::title {
    subcontrol-origin: margin;
    subcontrol-position: top left;
    padding: 0 8px;
    color: #f5f5f7;
}

/* ── Status Bar ─────────────────────────────────────────────────────────── */
QStatusBar {
    background-color: #2c2c2e;
    border-top: 1px solid #38383a;
    font-size: 12px;
    color: #98989d;
    padding: 2px 8px;
}

QStatusBar::item {
    border: none;
}

/* ── Header View ────────────────────────────────────────────────────────── */
QHeaderView::section {
    background-color: #2c2c2e;
    border: none;
    border-bottom: 1px solid #38383a;
    border-right: 1px solid #3a3a3c;
    padding: 8px 12px;
    font-size: 11px;
    font-weight: 600;
    color: #98989d;
    text-transform: uppercase;
}

/* ── Text Browser ───────────────────────────────────────────────────────── */
QTextBrowser {
    background-color: #1c1c1e;
    border: none;
    padding: 16px;
    font-size: 14px;
    line-height: 1.6;
    color: #f5f5f7;
}

/* ── Frame (message header area) ────────────────────────────────────────── */
QFrame#messageHeaderFrame {
    background-color: #2c2c2e;
    border-bottom: 1px solid #38383a;
    padding: 16px;
}

QFrame#attachmentFrame {
    background-color: #2c2c2e;
    border: 1px solid #38383a;
    border-radius: 10px;
    padding: 10px;
    margin: 8px 16px;
}

/* ── Combo Box ──────────────────────────────────────────────────────────── */
QComboBox {
    background-color: rgba(118, 118, 128, 0.24);
    border: 1px solid transparent;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 13px;
    color: #f5f5f7;
    min-width: 120px;
}

QComboBox:focus {
    border: 1px solid #0A84FF;
}

QComboBox::drop-down {
    border: none;
    width: 24px;
}

QComboBox QAbstractItemView {
    background-color: #2c2c2e;
    border: 1px solid #48484a;
    border-radius: 8px;
    selection-background-color: #0A84FF;
    selection-color: white;
    padding: 4px;
    color: #f5f5f7;
}

/* ── Progress Bar ───────────────────────────────────────────────────────── */
QProgressBar {
    background-color: rgba(118, 118, 128, 0.24);
    border: none;
    border-radius: 4px;
    height: 4px;
    text-align: center;
    font-size: 0px;
}

QProgressBar::chunk {
    background-color: #0A84FF;
    border-radius: 4px;
}

/* ── Tooltip ────────────────────────────────────────────────────────────── */
QToolTip {
    background-color: rgba(60, 60, 60, 0.95);
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 12px;
}

/* ── Custom badge/pill for labels ───────────────────────────────────────── */
QLabel#labelBadge {
    background-color: rgba(10, 132, 255, 0.2);
    color: #0A84FF;
    border-radius: 10px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 600;
}

QLabel#countBadge {
    background-color: #0A84FF;
    color: white;
    border-radius: 9px;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 600;
    min-width: 18px;
    max-height: 18px;
}
```

---

## Source Files

### src/emailmessage.h

```cpp
#pragma once

#include <QString>
#include <QStringList>
#include <QDateTime>
#include <QJsonObject>
#include <QJsonArray>
#include <QList>

struct EmailAddress {
    QString name;
    QString email;

    QString displayString() const {
        if (name.isEmpty()) return email;
        return QStringLiteral("%1 <%2>").arg(name, email);
    }

    static EmailAddress fromJson(const QJsonObject &obj) {
        return { obj.value("name").toString(), obj.value("email").toString() };
    }
};

struct AttachmentInfo {
    QString filename;
    QString mimeType;
    qint64 size = 0;
    QString storagePath;

    static AttachmentInfo fromJson(const QJsonObject &obj) {
        AttachmentInfo a;
        a.filename = obj.value("filename").toString();
        a.mimeType = obj.value("mimeType").toString();
        a.size = obj.value("size").toInteger();
        a.storagePath = obj.value("storagePath").toString();
        return a;
    }

    QString sizeString() const {
        if (size < 1024) return QStringLiteral("%1 B").arg(size);
        if (size < 1024 * 1024) return QStringLiteral("%1 KB").arg(size / 1024.0, 0, 'f', 1);
        return QStringLiteral("%1 MB").arg(size / (1024.0 * 1024.0), 0, 'f', 1);
    }
};

class EmailMessage {
public:
    // Identifiers
    QString messageId;
    QString contentHash;
    QString threadId;

    // Headers
    QString subject;
    EmailAddress from;
    QDateTime date;
    QList<EmailAddress> to;
    QList<EmailAddress> cc;
    QList<EmailAddress> bcc;

    // Threading
    QString inReplyTo;
    QStringList references;

    // Labels
    QStringList gmailLabels;

    // Attachments
    QList<AttachmentInfo> attachments;

    // Body flags
    bool hasTextBody = false;
    bool hasHtmlBody = false;

    // File paths (set when loading)
    QString metaJsonPath;
    QString txtPath;
    QString mboxPath;

    // Load from meta.json
    bool loadFromJson(const QString &filePath);
    bool loadFromJsonObject(const QJsonObject &obj, const QString &basePath = {});

    // Get body content
    QString textBody() const;
    QString htmlBody() const;  // Parsed from .mbox MIME

    bool isValid() const { return !messageId.isEmpty(); }

    // For list display
    QString previewText() const;
};
```

### src/emailmessage.cpp

```cpp
#include "emailmessage.h"

#include <QFile>
#include <QJsonDocument>
#include <QJsonArray>
#include <QFileInfo>
#include <QDir>
#include <QTextStream>
#include <QRegularExpression>

bool EmailMessage::loadFromJson(const QString &filePath)
{
    QFile file(filePath);
    if (!file.open(QIODevice::ReadOnly | QIODevice::Text))
        return false;

    QJsonParseError error;
    QJsonDocument doc = QJsonDocument::fromJson(file.readAll(), &error);
    if (error.error != QJsonParseError::NoError)
        return false;

    metaJsonPath = filePath;

    // Derive sibling file paths
    QString baseName = filePath;
    baseName.chop(10); // Remove ".meta.json"

    txtPath = baseName + QStringLiteral(".txt");
    mboxPath = baseName + QStringLiteral(".mbox");

    return loadFromJsonObject(doc.object(), QFileInfo(filePath).absolutePath());
}

bool EmailMessage::loadFromJsonObject(const QJsonObject &obj, const QString &basePath)
{
    messageId = obj.value("messageId").toString();
    contentHash = obj.value("contentHash").toString();
    threadId = obj.value("threadId").toString();
    subject = obj.value("subject").toString();

    // From
    from = EmailAddress::fromJson(obj.value("from").toObject());

    // Date
    date = QDateTime::fromString(obj.value("date").toString(), Qt::ISODate);

    // Recipients
    to.clear();
    for (const auto &v : obj.value("to").toArray())
        to.append(EmailAddress::fromJson(v.toObject()));

    cc.clear();
    for (const auto &v : obj.value("cc").toArray())
        cc.append(EmailAddress::fromJson(v.toObject()));

    bcc.clear();
    for (const auto &v : obj.value("bcc").toArray())
        bcc.append(EmailAddress::fromJson(v.toObject()));

    // Threading
    inReplyTo = obj.value("inReplyTo").toString();
    references.clear();
    for (const auto &v : obj.value("references").toArray())
        references.append(v.toString());

    // Labels
    gmailLabels.clear();
    for (const auto &v : obj.value("gmailLabels").toArray())
        gmailLabels.append(v.toString());

    // Attachments
    attachments.clear();
    for (const auto &v : obj.value("attachments").toArray())
        attachments.append(AttachmentInfo::fromJson(v.toObject()));

    // Body flags
    hasTextBody = obj.value("hasTextBody").toBool();
    hasHtmlBody = obj.value("hasHtmlBody").toBool();

    return !messageId.isEmpty();
}

QString EmailMessage::textBody() const
{
    if (txtPath.isEmpty()) return {};

    QFile file(txtPath);
    if (!file.open(QIODevice::ReadOnly | QIODevice::Text))
        return {};

    return QString::fromUtf8(file.readAll());
}

QString EmailMessage::htmlBody() const
{
    // Parse MIME from .mbox to extract HTML body
    if (mboxPath.isEmpty()) return {};

    QFile file(mboxPath);
    if (!file.open(QIODevice::ReadOnly))
        return {};

    QByteArray data = file.readAll();
    QString content = QString::fromUtf8(data);

    // Simple MIME parser: find text/html part
    // Look for Content-Type: text/html boundary
    static QRegularExpression boundaryRe(
        R"(boundary="?([^"\s;]+)"?)",
        QRegularExpression::CaseInsensitiveOption
    );

    auto boundaryMatch = boundaryRe.match(content);
    if (!boundaryMatch.hasMatch()) {
        // No boundary - check if the whole thing is HTML
        if (content.contains(QStringLiteral("Content-Type: text/html"), Qt::CaseInsensitive) ||
            content.contains(QStringLiteral("<html"), Qt::CaseInsensitive)) {

            // Try to extract body after headers
            int bodyStart = content.indexOf(QStringLiteral("\n\n"));
            if (bodyStart >= 0) {
                return content.mid(bodyStart + 2);
            }
        }
        return {};
    }

    QString boundary = boundaryMatch.captured(1);
    QStringList parts = content.split(QStringLiteral("--") + boundary);

    for (const QString &part : parts) {
        if (part.contains(QStringLiteral("Content-Type: text/html"), Qt::CaseInsensitive)) {
            // Find the body (after double newline)
            int bodyStart = part.indexOf(QStringLiteral("\n\n"));
            if (bodyStart < 0) bodyStart = part.indexOf(QStringLiteral("\r\n\r\n"));
            if (bodyStart < 0) continue;

            QString body = part.mid(bodyStart + 2).trimmed();

            // Handle Content-Transfer-Encoding
            if (part.contains(QStringLiteral("Content-Transfer-Encoding: base64"), Qt::CaseInsensitive)) {
                // Remove any trailing boundary markers
                int endIdx = body.indexOf(QStringLiteral("--"));
                if (endIdx > 0) body = body.left(endIdx);
                body.remove(QRegularExpression(QStringLiteral("\\s")));
                QByteArray decoded = QByteArray::fromBase64(body.toLatin1());
                return QString::fromUtf8(decoded);
            }

            if (part.contains(QStringLiteral("Content-Transfer-Encoding: quoted-printable"), Qt::CaseInsensitive)) {
                // Decode quoted-printable
                body.replace(QStringLiteral("=\r\n"), QString());
                body.replace(QStringLiteral("=\n"), QString());

                static QRegularExpression qpRe(QStringLiteral("=([0-9A-Fa-f]{2})"));
                auto it = qpRe.globalMatch(body);
                QString decoded;
                int lastEnd = 0;
                while (it.hasNext()) {
                    auto m = it.next();
                    decoded += body.mid(lastEnd, m.capturedStart() - lastEnd);
                    bool ok;
                    char c = static_cast<char>(m.captured(1).toInt(&ok, 16));
                    decoded += QChar::fromLatin1(c);
                    lastEnd = m.capturedEnd();
                }
                decoded += body.mid(lastEnd);
                return decoded;
            }

            // Plain 7bit/8bit
            int endIdx = body.indexOf(QStringLiteral("\n--"));
            if (endIdx > 0) body = body.left(endIdx);
            return body;
        }
    }

    return {};
}

QString EmailMessage::previewText() const
{
    QString text = textBody();
    if (text.isEmpty()) return {};

    // Skip headers in .txt file (find first blank line)
    int bodyStart = text.indexOf(QStringLiteral("\n\n"));
    if (bodyStart >= 0)
        text = text.mid(bodyStart + 2);

    // Clean and truncate
    text.replace(QChar('\n'), QChar(' '));
    text = text.simplified();
    if (text.length() > 150)
        text = text.left(150) + QStringLiteral("…");

    return text;
}
```

### src/emailstore.h

```cpp
#pragma once

#include <QObject>
#include <QString>
#include <QMap>
#include <QList>
#include <QJsonObject>
#include <QJsonArray>
#include <QHash>
#include <memory>

#include "emailmessage.h"

struct ThreadInfo {
    QString threadId;
    QString subject;
    int messageCount = 0;
    QStringList messageIds;
    QStringList messagePaths; // paths to .meta.json files
};

struct LabelInfo {
    QString name;
    int messageCount = 0;
};

struct SenderInfo {
    QString email;
    QString name;
    int messageCount = 0;
};

struct IndexEntry {
    QString messageId;
    QString path;         // relative path to .meta.json
    QString date;
    QString subject;
    QString from;
    QString threadId;
};

class EmailStore : public QObject {
    Q_OBJECT

public:
    explicit EmailStore(QObject *parent = nullptr);

    bool loadArchive(const QString &archivePath);
    QString archivePath() const { return m_archivePath; }

    // Label navigation
    QList<LabelInfo> labels() const { return m_labels; }
    QList<IndexEntry> messagesByLabel(const QString &label);

    // Date navigation
    QStringList availableMonths() const;
    QList<IndexEntry> messagesByMonth(const QString &yearMonth); // "YYYY-MM"

    // Sender navigation
    QList<SenderInfo> senders() const { return m_senders; }
    QList<IndexEntry> messagesBySender(const QString &email);

    // Thread support
    ThreadInfo threadInfo(const QString &threadId) const;
    QList<std::shared_ptr<EmailMessage>> threadMessages(const QString &threadId);

    // Search
    QList<IndexEntry> searchBySubjectWord(const QString &word);
    QList<IndexEntry> searchFullText(const QString &query);

    // Message loading
    std::shared_ptr<EmailMessage> loadMessage(const QString &metaJsonPath);
    std::shared_ptr<EmailMessage> loadMessageById(const QString &messageId);

    // Statistics
    int totalMessages() const;
    int totalThreads() const;

signals:
    void loadingStarted();
    void loadingProgress(int current, int total);
    void loadingFinished(int messageCount);
    void errorOccurred(const QString &error);

private:
    void loadLabelIndex();
    void loadSenderIndex();
    void loadDateIndices();
    void loadThreadIndex();
    void loadWordIndices();
    void scanForMetaFiles();

    QString m_archivePath;
    QList<LabelInfo> m_labels;
    QList<SenderInfo> m_senders;
    QMap<QString, QList<IndexEntry>> m_labelIndex;     // label -> entries
    QMap<QString, QList<IndexEntry>> m_dateIndex;      // YYYY-MM -> entries
    QMap<QString, QList<IndexEntry>> m_senderIndex;    // email -> entries
    QMap<QString, ThreadInfo> m_threads;               // threadId -> ThreadInfo
    QMap<QString, QList<IndexEntry>> m_wordIndex;      // word -> entries
    QHash<QString, QString> m_messageIdToPath;         // messageId -> meta.json path

    // Cache
    QHash<QString, std::shared_ptr<EmailMessage>> m_messageCache;
    int m_maxCacheSize = 500;
};
```

### src/emailstore.cpp

```cpp
#include "emailstore.h"

#include <QDir>
#include <QDirIterator>
#include <QFile>
#include <QJsonDocument>
#include <QJsonArray>
#include <QJsonObject>
#include <QDebug>

EmailStore::EmailStore(QObject *parent)
    : QObject(parent)
{
}

bool EmailStore::loadArchive(const QString &archivePath)
{
    QDir dir(archivePath);
    if (!dir.exists()) {
        emit errorOccurred(tr("Archive directory does not exist: %1").arg(archivePath));
        return false;
    }

    m_archivePath = archivePath;

    emit loadingStarted();

    // Load indices (lazy/fast approach)
    loadLabelIndex();
    loadSenderIndex();
    loadDateIndices();
    loadThreadIndex();

    // Build messageId -> path mapping from indices
    // We can do this from any loaded index, or scan meta files
    int total = 0;
    for (const auto &entries : m_labelIndex)
        total += entries.size();

    if (total == 0) {
        // Fallback: scan filesystem for .meta.json
        scanForMetaFiles();
        total = m_messageIdToPath.size();
    }

    emit loadingFinished(total);
    return true;
}

void EmailStore::loadLabelIndex()
{
    QString labelListPath = m_archivePath + QStringLiteral("/indices/by_label/_label_list.json");
    QFile file(labelListPath);
    if (!file.open(QIODevice::ReadOnly)) {
        qDebug() << "No label list found at" << labelListPath;
        return;
    }

    QJsonDocument doc = QJsonDocument::fromJson(file.readAll());
    QJsonArray arr = doc.array();
    // Could also be an object with label->count mapping
    if (doc.isArray()) {
        for (const auto &v : arr) {
            QJsonObject obj = v.toObject();
            LabelInfo li;
            li.name = obj.value("label").toString();
            if (li.name.isEmpty()) li.name = obj.value("name").toString();
            li.messageCount = obj.value("count").toInt();
            if (li.messageCount == 0) li.messageCount = obj.value("messageCount").toInt();
            m_labels.append(li);
        }
    } else if (doc.isObject()) {
        QJsonObject obj = doc.object();
        for (auto it = obj.begin(); it != obj.end(); ++it) {
            LabelInfo li;
            li.name = it.key();
            li.messageCount = it.value().toInt();
            m_labels.append(li);
        }
    }
}

void EmailStore::loadSenderIndex()
{
    QString senderListPath = m_archivePath + QStringLiteral("/indices/by_sender/_sender_list.json");
    QFile file(senderListPath);
    if (!file.open(QIODevice::ReadOnly)) return;

    QJsonDocument doc = QJsonDocument::fromJson(file.readAll());
    if (doc.isArray()) {
        for (const auto &v : doc.array()) {
            QJsonObject obj = v.toObject();
            SenderInfo si;
            si.email = obj.value("email").toString();
            si.name = obj.value("name").toString();
            si.messageCount = obj.value("count").toInt();
            if (si.messageCount == 0) si.messageCount = obj.value("messageCount").toInt();
            m_senders.append(si);
        }
    } else if (doc.isObject()) {
        QJsonObject obj = doc.object();
        for (auto it = obj.begin(); it != obj.end(); ++it) {
            SenderInfo si;
            si.email = it.key();
            if (it.value().isObject()) {
                QJsonObject sobj = it.value().toObject();
                si.name = sobj.value("name").toString();
                si.messageCount = sobj.value("count").toInt();
            } else {
                si.messageCount = it.value().toInt();
            }
            m_senders.append(si);
        }
    }
}

void EmailStore::loadDateIndices()
{
    QDir dateDir(m_archivePath + QStringLiteral("/indices/by_date"));
    if (!dateDir.exists()) return;

    QStringList jsonFiles = dateDir.entryList({QStringLiteral("*.json")}, QDir::Files, QDir::Name);
    for (const QString &fileName : jsonFiles) {
        QString yearMonth = fileName;
        yearMonth.chop(5); // Remove ".json"

        // Don't load the full content now — lazy load
        // Just register that this month exists
        m_dateIndex[yearMonth] = {};
    }
}

void EmailStore::loadThreadIndex()
{
    QString threadPath = m_archivePath + QStringLiteral("/thread_index.json");
    QFile file(threadPath);
    if (!file.open(QIODevice::ReadOnly)) return;

    QJsonDocument doc = QJsonDocument::fromJson(file.readAll());

    auto processThread = [this](const QJsonObject &obj) {
        ThreadInfo ti;
        ti.threadId = obj.value("threadId").toString();
        ti.subject = obj.value("subject").toString();
        ti.messageCount = obj.value("messageCount").toInt();

        QJsonArray ids = obj.value("messageIds").toArray();
        for (const auto &v : ids)
            ti.messageIds.append(v.toString());

        QJsonArray paths = obj.value("messagePaths").toArray();
        for (const auto &v : paths) {
            QString p = v.toString();
            ti.messagePaths.append(p);
        }

        // Build messageId to path mapping
        for (int i = 0; i < ti.messageIds.size() && i < ti.messagePaths.size(); ++i) {
            QString fullPath = ti.messagePaths[i];
            if (!fullPath.startsWith('/'))
                fullPath = m_archivePath + '/' + fullPath;
            m_messageIdToPath[ti.messageIds[i]] = fullPath;
        }

        m_threads[ti.threadId] = ti;
    };

    if (doc.isArray()) {
        for (const auto &v : doc.array()) {
            processThread(v.toObject());
        }
    } else if (doc.isObject()) {
        QJsonObject obj = doc.object();
        for (auto it = obj.begin(); it != obj.end(); ++it) {
            QJsonObject threadObj = it.value().toObject();
            if (!threadObj.contains("threadId"))
                threadObj["threadId"] = it.key();
            processThread(threadObj);
        }
    }
}

void EmailStore::scanForMetaFiles()
{
    QDirIterator it(m_archivePath, {QStringLiteral("*.meta.json")},
                    QDir::Files, QDirIterator::Subdirectories);

    int count = 0;
    while (it.hasNext()) {
        QString path = it.next();

        // Quick parse to get messageId
        QFile file(path);
        if (!file.open(QIODevice::ReadOnly)) continue;

        // Read only what we need (first ~1KB should have messageId)
        QByteArray data = file.read(2048);
        // Simple extraction without full JSON parse
        int idx = data.indexOf("\"messageId\"");
        if (idx >= 0) {
            int colon = data.indexOf(':', idx);
            int quote1 = data.indexOf('"', colon + 1);
            int quote2 = data.indexOf('"', quote1 + 1);
            if (quote1 >= 0 && quote2 > quote1) {
                QString msgId = QString::fromUtf8(data.mid(quote1 + 1, quote2 - quote1 - 1));
                m_messageIdToPath[msgId] = path;
            }
        }

        count++;
        if (count % 1000 == 0)
            emit loadingProgress(count, 0);
    }
}

QList<IndexEntry> EmailStore::messagesByLabel(const QString &label)
{
    // Check if already loaded
    if (m_labelIndex.contains(label) && !m_labelIndex[label].isEmpty())
        return m_labelIndex[label];

    // Load from index file
    QString safeName = label.toLower();
    safeName.replace(' ', '_');
    QString path = m_archivePath + QStringLiteral("/indices/by_label/") + safeName + QStringLiteral(".json");

    QFile file(path);
    if (!file.open(QIODevice::ReadOnly)) {
        // Try exact name
        path = m_archivePath + QStringLiteral("/indices/by_label/") + label + QStringLiteral(".json");
        file.setFileName(path);
        if (!file.open(QIODevice::ReadOnly))
            return {};
    }

    QJsonDocument doc = QJsonDocument::fromJson(file.readAll());
    QList<IndexEntry> entries;

    for (const auto &v : doc.array()) {
        QJsonObject obj = v.toObject();
        IndexEntry e;
        e.messageId = obj.value("messageId").toString();
        e.path = obj.value("path").toString();
        e.date = obj.value("date").toString();
        e.subject = obj.value("subject").toString();
        e.from = obj.value("from").toString();
        e.threadId = obj.value("threadId").toString();

        if (!e.path.startsWith('/'))
            e.path = m_archivePath + '/' + e.path;

        // Update messageId -> path mapping
        if (!e.messageId.isEmpty() && !e.path.isEmpty())
            m_messageIdToPath[e.messageId] = e.path;

        entries.append(e);
    }

    m_labelIndex[label] = entries;
    return entries;
}

QStringList EmailStore::availableMonths() const
{
    return m_dateIndex.keys();
}

QList<IndexEntry> EmailStore::messagesByMonth(const QString &yearMonth)
{
    if (m_dateIndex.contains(yearMonth) && !m_dateIndex[yearMonth].isEmpty())
        return m_dateIndex[yearMonth];

    QString path = m_archivePath + QStringLiteral("/indices/by_date/") + yearMonth + QStringLiteral(".json");
    QFile file(path);
    if (!file.open(QIODevice::ReadOnly)) return {};

    QJsonDocument doc = QJsonDocument::fromJson(file.readAll());
    QList<IndexEntry> entries;

    for (const auto &v : doc.array()) {
        QJsonObject obj = v.toObject();
        IndexEntry e;
        e.messageId = obj.value("messageId").toString();
        e.path = obj.value("path").toString();
        e.date = obj.value("date").toString();
        e.subject = obj.value("subject").toString();
        e.from = obj.value("from").toString();
        e.threadId = obj.value("threadId").toString();

        if (!e.path.startsWith('/'))
            e.path = m_archivePath + '/' + e.path;

        m_messageIdToPath[e.messageId] = e.path;
        entries.append(e);
    }

    m_dateIndex[yearMonth] = entries;
    return entries;
}

QList<IndexEntry> EmailStore::messagesBySender(const QString &email)
{
    if (m_senderIndex.contains(email) && !m_senderIndex[email].isEmpty())
        return m_senderIndex[email];

    QString path = m_archivePath + QStringLiteral("/indices/by_sender/") + email + QStringLiteral(".json");
    QFile file(path);
    if (!file.open(QIODevice::ReadOnly)) return {};

    QJsonDocument doc = QJsonDocument::fromJson(file.readAll());
    QList<IndexEntry> entries;

    for (const auto &v : doc.array()) {
        QJsonObject obj = v.toObject();
        IndexEntry e;
        e.messageId = obj.value("messageId").toString();
        e.path = obj.value("path").toString();
        e.date = obj.value("date").toString();
        e.subject = obj.value("subject").toString();
        e.threadId = obj.value("threadId").toString();

        if (!e.path.startsWith('/'))
            e.path = m_archivePath + '/' + e.path;

        m_messageIdToPath[e.messageId] = e.path;
        entries.append(e);
    }

    m_senderIndex[email] = entries;
    return entries;
}

ThreadInfo EmailStore::threadInfo(const QString &threadId) const
{
    return m_threads.value(threadId);
}

QList<std::shared_ptr<EmailMessage>> EmailStore::threadMessages(const QString &threadId)
{
    QList<std::shared_ptr<EmailMessage>> messages;

    auto it = m_threads.constFind(threadId);
    if (it == m_threads.constEnd()) return messages;

    const ThreadInfo &ti = it.value();

    // Try loading from messagePaths first
    if (!ti.messagePaths.isEmpty()) {
        for (const QString &path : ti.messagePaths) {
            QString fullPath = path;
            if (!fullPath.startsWith('/'))
                fullPath = m_archivePath + '/' + fullPath;
            auto msg = loadMessage(fullPath);
            if (msg) messages.append(msg);
        }
    } else {
        // Fall back to messageIds
        for (const QString &msgId : ti.messageIds) {
            auto msg = loadMessageById(msgId);
            if (msg) messages.append(msg);
        }
    }

    // Sort by date
    std::sort(messages.begin(), messages.end(),
              [](const auto &a, const auto &b) { return a->date < b->date; });

    return messages;
}

QList<IndexEntry> EmailStore::searchBySubjectWord(const QString &word)
{
    QString lower = word.toLower();
    if (lower.isEmpty()) return {};

    QChar firstChar = lower[0];
    QString bucketFile = m_archivePath + QStringLiteral("/indices/by_word/")
                         + firstChar + QStringLiteral(".json");

    QFile file(bucketFile);
    if (!file.open(QIODevice::ReadOnly)) return {};

    QJsonDocument doc = QJsonDocument::fromJson(file.readAll());
    QJsonObject obj = doc.object();

    QList<IndexEntry> entries;
    QJsonArray arr = obj.value(lower).toArray();
    for (const auto &v : arr) {
        QJsonObject eobj = v.toObject();
        IndexEntry e;
        e.messageId = eobj.value("messageId").toString();
        e.path = eobj.value("path").toString();
        if (!e.path.startsWith('/'))
            e.path = m_archivePath + '/' + e.path;
        entries.append(e);
    }

    return entries;
}

QList<IndexEntry> EmailStore::searchFullText(const QString &query)
{
    QList<IndexEntry> results;
    if (query.isEmpty()) return results;

    // Simple in-memory search over .txt files (for smaller archives)
    // For large archives, this should use an external index
    QDirIterator it(m_archivePath, {QStringLiteral("*.txt")},
                    QDir::Files, QDirIterator::Subdirectories);

    int count = 0;
    while (it.hasNext() && count < 1000) {
        QString filePath = it.next();
        QFile file(filePath);
        if (!file.open(QIODevice::ReadOnly | QIODevice::Text)) continue;

        QString content = QString::fromUtf8(file.readAll());
        if (content.contains(query, Qt::CaseInsensitive)) {
            IndexEntry e;
            // Derive meta.json path
            QString metaPath = filePath;
            metaPath.chop(4); // Remove ".txt"
            metaPath += QStringLiteral(".meta.json");

            e.path = metaPath;
            results.append(e);
            count++;
        }
    }

    return results;
}

std::shared_ptr<EmailMessage> EmailStore::loadMessage(const QString &metaJsonPath)
{
    // Check cache
    auto it = m_messageCache.find(metaJsonPath);
    if (it != m_messageCache.end())
        return it.value();

    auto msg = std::make_shared<EmailMessage>();
    if (!msg->loadFromJson(metaJsonPath))
        return nullptr;

    // Cache management
    if (m_messageCache.size() >= m_maxCacheSize) {
        // Simple eviction: remove oldest quarter
        auto keys = m_messageCache.keys();
        for (int i = 0; i < keys.size() / 4; ++i)
            m_messageCache.remove(keys[i]);
    }

    m_messageCache[metaJsonPath] = msg;
    return msg;
}

std::shared_ptr<EmailMessage> EmailStore::loadMessageById(const QString &messageId)
{
    auto it = m_messageIdToPath.constFind(messageId);
    if (it == m_messageIdToPath.constEnd()) return nullptr;
    return loadMessage(it.value());
}

int EmailStore::totalMessages() const
{
    return m_messageIdToPath.size();
}

int EmailStore::totalThreads() const
{
    return m_threads.size();
}
```

### src/foldertreemodel.h

```cpp
#pragma once

#include <QStandardItemModel>
#include <QIcon>
#include "emailstore.h"

class FolderTreeModel : public QStandardItemModel {
    Q_OBJECT

public:
    enum ItemRole {
        FolderTypeRole = Qt::UserRole + 1,
        FolderDataRole,
        MessageCountRole
    };

    enum FolderType {
        Root,
        LabelsRoot,
        Label,
        DatesRoot,
        DateYear,
        DateMonth,
        SendersRoot,
        Sender
    };

    explicit FolderTreeModel(QObject *parent = nullptr);

    void buildFromStore(EmailStore *store);

private:
    QStandardItem *createItem(const QString &text, FolderType type,
                              const QString &data = {}, int count = -1);
    QIcon iconForType(FolderType type) const;
};
```

### src/foldertreemodel.cpp

```cpp
#include "foldertreemodel.h"
#include <QIcon>

FolderTreeModel::FolderTreeModel(QObject *parent)
    : QStandardItemModel(parent)
{
}

void FolderTreeModel::buildFromStore(EmailStore *store)
{
    clear();
    if (!store) return;

    QStandardItem *root = invisibleRootItem();

    // ── Labels section ──────────────────────────────────────────────────
    auto *labelsRoot = createItem(tr("Labels"), LabelsRoot);
    labelsRoot->setEditable(false);
    root->appendRow(labelsRoot);

    // Add well-known labels first
    QStringList priorityLabels = { "Inbox", "Sent", "Drafts", "Important", "Starred", "Spam", "Trash" };
    QSet<QString> addedLabels;

    const auto &labels = store->labels();
    for (const QString &pLabel : priorityLabels) {
        for (const auto &li : labels) {
            if (li.name.compare(pLabel, Qt::CaseInsensitive) == 0) {
                auto *item = createItem(li.name, Label, li.name, li.messageCount);
                labelsRoot->appendRow(item);
                addedLabels.insert(li.name);
                break;
            }
        }
    }

    // Add remaining labels
    for (const auto &li : labels) {
        if (!addedLabels.contains(li.name)) {
            auto *item = createItem(li.name, Label, li.name, li.messageCount);
            labelsRoot->appendRow(item);
        }
    }

    // ── Dates section ───────────────────────────────────────────────────
    auto *datesRoot = createItem(tr("Dates"), DatesRoot);
    datesRoot->setEditable(false);
    root->appendRow(datesRoot);

    QStringList months = store->availableMonths();
    // Group by year
    QMap<QString, QStringList> yearMonths;
    for (const QString &ym : months) {
        QString year = ym.left(4);
        yearMonths[year].append(ym);
    }

    // Reverse order (newest first)
    QList<QString> years = yearMonths.keys();
    std::sort(years.begin(), years.end(), std::greater<>());

    static const QStringList monthNames = {
        QString(), "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    };

    for (const QString &year : years) {
        auto *yearItem = createItem(year, DateYear, year);
        datesRoot->appendRow(yearItem);

        QStringList yms = yearMonths[year];
        std::sort(yms.begin(), yms.end(), std::greater<>());

        for (const QString &ym : yms) {
            int monthNum = ym.mid(5, 2).toInt();
            QString monthName = (monthNum > 0 && monthNum < monthNames.size())
                                    ? monthNames[monthNum]
                                    : ym.mid(5);
            auto *monthItem = createItem(monthName, DateMonth, ym);
            yearItem->appendRow(monthItem);
        }
    }

    // ── Senders section ─────────────────────────────────────────────────
    auto *sendersRoot = createItem(tr("Senders"), SendersRoot);
    sendersRoot->setEditable(false);
    root->appendRow(sendersRoot);

    auto senders = store->senders();
    // Sort by message count descending
    std::sort(senders.begin(), senders.end(),
              [](const SenderInfo &a, const SenderInfo &b) {
                  return a.messageCount > b.messageCount;
              });

    // Show top senders (limit to avoid huge tree)
    int senderLimit = qMin(static_cast<int>(senders.size()), 100);
    for (int i = 0; i < senderLimit; ++i) {
        const auto &si = senders[i];
        QString display = si.name.isEmpty() ? si.email : si.name;
        auto *item = createItem(display, Sender, si.email, si.messageCount);
        item->setToolTip(si.email);
        sendersRoot->appendRow(item);
    }
}

QStandardItem *FolderTreeModel::createItem(const QString &text, FolderType type,
                                           const QString &data, int count)
{
    QString displayText = text;
    if (count > 0)
        displayText = QStringLiteral("%1 (%2)").arg(text).arg(count);

    auto *item = new QStandardItem(iconForType(type), displayText);
    item->setData(static_cast<int>(type), FolderTypeRole);
    item->setData(data, FolderDataRole);
    item->setData(count, MessageCountRole);
    item->setEditable(false);
    return item;
}

QIcon FolderTreeModel::iconForType(FolderType type) const
{
    switch (type) {
    case LabelsRoot:
    case Label:
        return QIcon(QStringLiteral(":/icons/label.svg"));
    case DatesRoot:
    case DateYear:
    case DateMonth:
        return QIcon(QStringLiteral(":/icons/calendar.svg"));
    case SendersRoot:
    case Sender:
        return QIcon(QStringLiteral(":/icons/mail.svg"));
    default:
        return QIcon(QStringLiteral(":/icons/folder.svg"));
    }
}
```

### src/messagelistmodel.h

```cpp
#pragma once

#include <QAbstractListModel>
#include <QList>
#include "emailstore.h"

class MessageListModel : public QAbstractListModel {
    Q_OBJECT

public:
    enum Roles {
        SubjectRole = Qt::DisplayRole,
        SenderRole = Qt::UserRole + 1,
        DateRole,
        PreviewRole,
        ThreadIdRole,
        MessageIdRole,
        MetaPathRole,
        HasAttachmentRole,
        IsThreadHeadRole,
        ThreadDepthRole
    };

    explicit MessageListModel(QObject *parent = nullptr);

    int rowCount(const QModelIndex &parent = {}) const override;
    QVariant data(const QModelIndex &index, int role = Qt::DisplayRole) const override;

    void setEntries(const QList<IndexEntry> &entries, EmailStore *store);
    void clear();

    IndexEntry entryAt(int row) const;

private:
    struct DisplayItem {
        IndexEntry entry;
        bool isThreadHead = false;
        int threadDepth = 0;
        QString senderName;
        QString previewText;
    };

    QList<DisplayItem> m_items;
    void groupByThread(const QList<IndexEntry> &entries);
};
```

### src/messagelistmodel.cpp

```cpp
#include "messagelistmodel.h"
#include <QDateTime>
#include <algorithm>

MessageListModel::MessageListModel(QObject *parent)
    : QAbstractListModel(parent)
{
}

int MessageListModel::rowCount(const QModelIndex &parent) const
{
    if (parent.isValid()) return 0;
    return static_cast<int>(m_items.size());
}

QVariant MessageListModel::data(const QModelIndex &index, int role) const
{
    if (!index.isValid() || index.row() < 0 || index.row() >= m_items.size())
        return {};

    const auto &item = m_items[index.row()];

    switch (role) {
    case SubjectRole:
        return item.entry.subject;
    case SenderRole:
        return item.senderName.isEmpty() ? item.entry.from : item.senderName;
    case DateRole:
        return item.entry.date;
    case PreviewRole:
        return item.previewText;
    case ThreadIdRole:
        return item.entry.threadId;
    case MessageIdRole:
        return item.entry.messageId;
    case MetaPathRole:
        return item.entry.path;
    case IsThreadHeadRole:
        return item.isThreadHead;
    case ThreadDepthRole:
        return item.threadDepth;
    default:
        return {};
    }
}

void MessageListModel::setEntries(const QList<IndexEntry> &entries, EmailStore *store)
{
    beginResetModel();
    m_items.clear();

    // Sort by date descending
    QList<IndexEntry> sorted = entries;
    std::sort(sorted.begin(), sorted.end(),
              [](const IndexEntry &a, const IndexEntry &b) {
                  return a.date > b.date;
              });

    // Group by thread
    QMap<QString, QList<int>> threadGroups; // threadId -> indices in sorted
    for (int i = 0; i < sorted.size(); ++i) {
        if (!sorted[i].threadId.isEmpty())
            threadGroups[sorted[i].threadId].append(i);
    }

    QSet<int> added;

    for (int i = 0; i < sorted.size(); ++i) {
        if (added.contains(i)) continue;

        const auto &entry = sorted[i];

        if (!entry.threadId.isEmpty() && threadGroups[entry.threadId].size() > 1) {
            // Thread head
            DisplayItem head;
            head.entry = entry;
            head.isThreadHead = true;
            head.threadDepth = 0;
            head.senderName = entry.from;
            m_items.append(head);
            added.insert(i);

            // Thread children (remaining messages in this thread)
            auto &indices = threadGroups[entry.threadId];
            for (int idx : indices) {
                if (idx == i) continue;
                added.insert(idx);

                DisplayItem child;
                child.entry = sorted[idx];
                child.isThreadHead = false;
                child.threadDepth = 1;
                child.senderName = sorted[idx].from;
                m_items.append(child);
            }
        } else {
            // Standalone message
            DisplayItem item;
            item.entry = entry;
            item.isThreadHead = false;
            item.threadDepth = 0;
            item.senderName = entry.from;
            m_items.append(item);
            added.insert(i);
        }
    }

    endResetModel();
}

void MessageListModel::clear()
{
    beginResetModel();
    m_items.clear();
    endResetModel();
}

IndexEntry MessageListModel::entryAt(int row) const
{
    if (row < 0 || row >= m_items.size()) return {};
    return m_items[row].entry;
}
```

### src/messagelistdelegate.h

```cpp
#pragma once

#include <QStyledItemDelegate>

class MessageListDelegate : public QStyledItemDelegate {
    Q_OBJECT

public:
    explicit MessageListDelegate(QObject *parent = nullptr);

    void paint(QPainter *painter, const QStyleOptionViewItem &option,
               const QModelIndex &index) const override;
    QSize sizeHint(const QStyleOptionViewItem &option,
                   const QModelIndex &index) const override;

    void setDarkMode(bool dark) { m_darkMode = dark; }

private:
    bool m_darkMode = false;
};
```

### src/messagelistdelegate.cpp

```cpp
#include "messagelistdelegate.h"
#include "messagelistmodel.h"

#include <QPainter>
#include <QApplication>
#include <QDateTime>

MessageListDelegate::MessageListDelegate(QObject *parent)
    : QStyledItemDelegate(parent)
{
}

void MessageListDelegate::paint(QPainter *painter, const QStyleOptionViewItem &option,
                                const QModelIndex &index) const
{
    painter->save();
    painter->setRenderHint(QPainter::Antialiasing);

    QRect rect = option.rect;
    bool selected = option.state & QStyle::State_Selected;
    bool hovered = option.state & QStyle::State_MouseOver;
    int threadDepth = index.data(MessageListModel::ThreadDepthRole).toInt();
    bool isThreadHead = index.data(MessageListModel::IsThreadHeadRole).toBool();

    // Background
    if (selected) {
        QColor bg = m_darkMode ? QColor(10, 132, 255, 38) : QColor(0, 122, 255, 20);
        painter->fillRect(rect, bg);

        // Left accent bar
        QColor accent = m_darkMode ? QColor(10, 132, 255) : QColor(0, 122, 255);
        painter->fillRect(QRect(rect.left(), rect.top(), 3, rect.height()), accent);
    } else if (hovered) {
        QColor bg = m_darkMode ? QColor(255, 255, 255, 8) : QColor(0, 0, 0, 5);
        painter->fillRect(rect, bg);
    }

    // Bottom border
    QColor borderColor = m_darkMode ? QColor(44, 44, 46) : QColor(242, 242, 247);
    painter->setPen(borderColor);
    painter->drawLine(rect.bottomLeft(), rect.bottomRight());

    // Indentation for thread children
    int leftPadding = 14 + threadDepth * 24;

    // Thread indicator
    if (isThreadHead) {
        QColor threadColor = m_darkMode ? QColor(10, 132, 255) : QColor(0, 122, 255);
        painter->setPen(Qt::NoPen);
        painter->setBrush(threadColor);
        painter->drawEllipse(QPoint(rect.left() + leftPadding - 12, rect.top() + 20), 3, 3);
    } else if (threadDepth > 0) {
        QColor lineColor = m_darkMode ? QColor(72, 72, 74) : QColor(210, 210, 215);
        painter->setPen(QPen(lineColor, 1));
        painter->drawLine(rect.left() + leftPadding - 12, rect.top(),
                          rect.left() + leftPadding - 12, rect.bottom());
    }

    // Sender
    QString sender = index.data(MessageListModel::SenderRole).toString();
    QFont senderFont = option.font;
    senderFont.setWeight(QFont::DemiBold);
    senderFont.setPointSize(senderFont.pointSize());
    painter->setFont(senderFont);
    QColor senderColor = m_darkMode ? QColor(245, 245, 247) : QColor(29, 29, 31);
    painter->setPen(senderColor);

    QRect senderRect(rect.left() + leftPadding, rect.top() + 8,
                     rect.width() - leftPadding - 80, 18);
    painter->drawText(senderRect, Qt::AlignLeft | Qt::AlignVCenter,
                      painter->fontMetrics().elidedText(sender, Qt::ElideRight, senderRect.width()));

    // Date
    QString dateStr = index.data(MessageListModel::DateRole).toString();
    QDateTime dt = QDateTime::fromString(dateStr, Qt::ISODate);
    QString displayDate;
    if (dt.isValid()) {
        QDate today = QDate::currentDate();
        if (dt.date() == today)
            displayDate = dt.toString(QStringLiteral("h:mm AP"));
        else if (dt.date().year() == today.year())
            displayDate = dt.toString(QStringLiteral("MMM d"));
        else
            displayDate = dt.toString(QStringLiteral("MMM d, yyyy"));
    } else {
        displayDate = dateStr.left(10);
    }

    QFont dateFont = option.font;
    dateFont.setPointSize(dateFont.pointSize() - 1);
    painter->setFont(dateFont);
    QColor dateColor = m_darkMode ? QColor(152, 152, 157) : QColor(134, 134, 139);
    painter->setPen(dateColor);

    QRect dateRect(rect.right() - 80, rect.top() + 8, 70, 18);
    painter->drawText(dateRect, Qt::AlignRight | Qt::AlignVCenter, displayDate);

    // Subject
    QString subject = index.data(MessageListModel::SubjectRole).toString();
    QFont subjectFont = option.font;
    subjectFont.setPointSize(subjectFont.pointSize());
    painter->setFont(subjectFont);
    QColor subjectColor = m_darkMode ? QColor(229, 229, 234) : QColor(60, 60, 67);
    painter->setPen(subjectColor);

    QRect subjectRect(rect.left() + leftPadding, rect.top() + 28,
                      rect.width() - leftPadding - 14, 18);
    painter->drawText(subjectRect, Qt::AlignLeft | Qt::AlignVCenter,
                      painter->fontMetrics().elidedText(subject, Qt::ElideRight, subjectRect.width()));

    // Preview
    QString preview = index.data(MessageListModel::PreviewRole).toString();
    if (!preview.isEmpty()) {
        QFont previewFont = option.font;
        previewFont.setPointSize(previewFont.pointSize() - 1);
        painter->setFont(previewFont);
        QColor previewColor = m_darkMode ? QColor(142, 142, 147) : QColor(142, 142, 147);
        painter->setPen(previewColor);

        QRect previewRect(rect.left() + leftPadding, rect.top() + 46,
                          rect.width() - leftPadding - 14, 16);
        painter->drawText(previewRect, Qt::AlignLeft | Qt::AlignVCenter,
                          painter->fontMetrics().elidedText(preview, Qt::ElideRight, previewRect.width()));
    }

    painter->restore();
}

QSize MessageListDelegate::sizeHint(const QStyleOptionViewItem &option,
                                    const QModelIndex &index) const
{
    Q_UNUSED(option);
    QString preview = index.data(MessageListModel::PreviewRole).toString();
    return QSize(300, preview.isEmpty() ? 56 : 70);
}
```

### src/mboxparser.h

```cpp
#pragma once

#include <QString>
#include <QByteArray>
#include <QMap>

struct MimePart {
    QMap<QString, QString> headers;
    QString contentType;
    QString transferEncoding;
    QString charset;
    QByteArray body;
    QList<MimePart> parts; // Nested multipart
};

class MboxParser {
public:
    explicit MboxParser(const QString &mboxFilePath);

    bool parse();

    QString htmlBody() const { return m_htmlBody; }
    QString textBody() const { return m_textBody; }
    QList<MimePart> attachments() const { return m_attachments; }

    QMap<QString, QByteArray> inlineImages() const { return m_inlineImages; }

private:
    void parseMimeStructure(const QByteArray &data);
    MimePart parsePart(const QByteArray &data);
    QList<MimePart> parseMultipart(const QByteArray &data, const QString &boundary);
    QByteArray decodeBody(const QByteArray &data, const QString &encoding);
    QString decodeText(const QByteArray &data, const QString &charset);
    QString extractBoundary(const QString &contentType);

    QString m_filePath;
    QString m_htmlBody;
    QString m_textBody;
    QList<MimePart> m_attachments;
    QMap<QString, QByteArray> m_inlineImages; // content-id -> decoded data
};
```

### src/mboxparser.cpp

```cpp
#include "mboxparser.h"

#include <QFile>
#include <QRegularExpression>
#include <QDebug>
#include <QStringDecoder>

MboxParser::MboxParser(const QString &mboxFilePath)
    : m_filePath(mboxFilePath)
{
}

bool MboxParser::parse()
{
    QFile file(m_filePath);
    if (!file.open(QIODevice::ReadOnly))
        return false;

    QByteArray data = file.readAll();
    file.close();

    // Remove mbox "From " line if present
    if (data.startsWith("From ")) {
        int lineEnd = data.indexOf('\n');
        if (lineEnd >= 0)
            data = data.mid(lineEnd + 1);
    }

    parseMimeStructure(data);
    return true;
}

void MboxParser::parseMimeStructure(const QByteArray &data)
{
    MimePart rootPart = parsePart(data);

    // Process the root part
    std::function<void(const MimePart &)> processPart = [&](const MimePart &part) {
        QString ct = part.contentType.toLower();

        if (ct.startsWith("multipart/")) {
            for (const auto &sub : part.parts)
                processPart(sub);
        } else if (ct.startsWith("text/html")) {
            if (m_htmlBody.isEmpty())
                m_htmlBody = decodeText(decodeBody(part.body, part.transferEncoding), part.charset);
        } else if (ct.startsWith("text/plain")) {
            if (m_textBody.isEmpty())
                m_textBody = decodeText(decodeBody(part.body, part.transferEncoding), part.charset);
        } else {
            // Check if it's an inline image
            QString contentId = part.headers.value("content-id");
            QString disposition = part.headers.value("content-disposition").toLower();

            if (!contentId.isEmpty() || disposition.startsWith("inline")) {
                // Store as inline image
                contentId.remove('<');
                contentId.remove('>');
                if (!contentId.isEmpty())
                    m_inlineImages[contentId] = decodeBody(part.body, part.transferEncoding);
            }

            if (disposition.startsWith("attachment") || !part.headers.value("content-disposition").isEmpty()) {
                m_attachments.append(part);
            }
        }
    };

    processPart(rootPart);
}

MimePart MboxParser::parsePart(const QByteArray &data)
{
    MimePart part;

    // Separate headers and body
    int headerEnd = data.indexOf("\r\n\r\n");
    if (headerEnd < 0) headerEnd = data.indexOf("\n\n");
    if (headerEnd < 0) {
        part.body = data;
        part.contentType = "text/plain";
        return part;
    }

    int bodyStart = headerEnd + (data.mid(headerEnd, 4) == "\r\n\r\n" ? 4 : 2);

    QByteArray headerData = data.left(headerEnd);
    part.body = data.mid(bodyStart);

    // Parse headers (handle folded lines)
    QByteArray unfoldedHeaders = headerData;
    unfoldedHeaders.replace("\r\n ", " ");
    unfoldedHeaders.replace("\r\n\t", " ");
    unfoldedHeaders.replace("\n ", " ");
    unfoldedHeaders.replace("\n\t", " ");

    QList<QByteArray> headerLines = unfoldedHeaders.split('\n');
    for (const QByteArray &line : headerLines) {
        int colon = line.indexOf(':');
        if (colon > 0) {
            QString key = QString::fromLatin1(line.left(colon)).trimmed().toLower();
            QString value = QString::fromUtf8(line.mid(colon + 1)).trimmed();
            part.headers[key] = value;
        }
    }

    part.contentType = part.headers.value("content-type", "text/plain");
    part.transferEncoding = part.headers.value("content-transfer-encoding", "7bit").toLower();

    // Extract charset
    static QRegularExpression charsetRe(R"(charset="?([^";\s]+)"?)", QRegularExpression::CaseInsensitiveOption);
    auto charsetMatch = charsetRe.match(part.contentType);
    part.charset = charsetMatch.hasMatch() ? charsetMatch.captured(1) : "utf-8";

    // Check for multipart
    if (part.contentType.toLower().startsWith("multipart/")) {
        QString boundary = extractBoundary(part.contentType);
        if (!boundary.isEmpty()) {
            part.parts = parseMultipart(part.body, boundary);
        }
    }

    return part;
}

QList<MimePart> MboxParser::parseMultipart(const QByteArray &data, const QString &boundary)
{
    QList<MimePart> parts;
    QByteArray delim = ("--" + boundary).toUtf8();
    QByteArray endDelim = delim + "--";

    QList<QByteArray> segments = data.split('\n');
    QByteArray currentPart;
    bool inPart = false;

    for (const QByteArray &line : segments) {
        QByteArray trimmed = line.trimmed();

        if (trimmed == delim || trimmed == (delim + "\r")) {
            if (inPart && !currentPart.isEmpty()) {
                parts.append(parsePart(currentPart));
            }
            currentPart.clear();
            inPart = true;
            continue;
        }

        if (trimmed.startsWith(endDelim)) {
            if (inPart && !currentPart.isEmpty()) {
                parts.append(parsePart(currentPart));
            }
            break;
        }

        if (inPart) {
            currentPart += line + '\n';
        }
    }

    return parts;
}

QByteArray MboxParser::decodeBody(const QByteArray &data, const QString &encoding)
{
    if (encoding == "base64") {
        QByteArray cleaned = data;
        cleaned.replace("\r", "");
        cleaned.replace("\n", "");
        cleaned.replace(" ", "");
        return QByteArray::fromBase64(cleaned);
    }

    if (encoding == "quoted-printable") {
        QByteArray result;
        QList<QByteArray> lines = data.split('\n');
        for (QByteArray line : lines) {
            if (line.endsWith('\r'))
                line.chop(1);

            if (line.endsWith('=')) {
                line.chop(1);
                // Soft line break, don't add newline
            } else {
                line += '\n';
            }

            // Decode =XX sequences
            QByteArray decoded;
            for (int i = 0; i < line.size(); ++i) {
                if (line[i] == '=' && i + 2 < line.size()) {
                    bool ok;
                    int value = QByteArray(line.mid(i + 1, 2)).toInt(&ok, 16);
                    if (ok) {
                        decoded += static_cast<char>(value);
                        i += 2;
                        continue;
                    }
                }
                decoded += line[i];
            }
            result += decoded;
        }
        return result;
    }

    return data;
}

QString MboxParser::decodeText(const QByteArray &data, const QString &charset)
{
    if (charset.isEmpty() || charset.compare("utf-8", Qt::CaseInsensitive) == 0 ||
        charset.compare("utf8", Qt::CaseInsensitive) == 0) {
        return QString::fromUtf8(data);
    }

    auto decoder = QStringDecoder(charset.toLatin1().constData());
    if (decoder.isValid()) {
        return decoder(data);
    }

    // Fallback to UTF-8
    return QString::fromUtf8(data);
}

QString MboxParser::extractBoundary(const QString &contentType)
{
    static QRegularExpression boundaryRe(R"(boundary="?([^";\s]+)"?)", QRegularExpression::CaseInsensitiveOption);
    auto match = boundaryRe.match(contentType);
    return match.hasMatch() ? match.captured(1) : QString();
}
```

### src/attachmentwidget.h

```cpp
#pragma once

#include <QWidget>
#include <QHBoxLayout>
#include <QLabel>
#include <QPushButton>
#include <QList>
#include "emailmessage.h"

class AttachmentWidget : public QWidget {
    Q_OBJECT

public:
    explicit AttachmentWidget(QWidget *parent = nullptr);

    void setAttachments(const QList<AttachmentInfo> &attachments);
    void clear();

signals:
    void attachmentClicked(const AttachmentInfo &info);

private:
    QHBoxLayout *m_layout;
    QLabel *m_titleLabel;
    QList<QPushButton *> m_buttons;
};
```

### src/attachmentwidget.cpp

```cpp
#include "attachmentwidget.h"
#include <QIcon>

AttachmentWidget::AttachmentWidget(QWidget *parent)
    : QWidget(parent)
{
    m_layout = new QHBoxLayout(this);
    m_layout->setContentsMargins(12, 8, 12, 8);
    m_layout->setSpacing(8);

    m_titleLabel = new QLabel(QStringLiteral("📎 Attachments:"), this);
    m_titleLabel->setObjectName(QStringLiteral("headerLabel"));
    m_layout->addWidget(m_titleLabel);

    m_layout->addStretch();

    setObjectName(QStringLiteral("attachmentFrame"));
    hide();
}

void AttachmentWidget::setAttachments(const QList<AttachmentInfo> &attachments)
{
    clear();

    if (attachments.isEmpty()) {
        hide();
        return;
    }

    for (const auto &att : attachments) {
        auto *btn = new QPushButton(
            QStringLiteral("%1 (%2)").arg(att.filename, att.sizeString()), this);
        btn->setObjectName(QStringLiteral("secondaryButton"));
        btn->setIcon(QIcon(QStringLiteral(":/icons/attachment.svg")));
        btn->setCursor(Qt::PointingHandCursor);
        btn->setToolTip(att.mimeType);

        connect(btn, &QPushButton::clicked, this, [this, att]() {
            emit attachmentClicked(att);
        });

        m_layout->insertWidget(m_layout->count() - 1, btn); // Before the stretch
        m_buttons.append(btn);
    }

    show();
}

void AttachmentWidget::clear()
{
    for (auto *btn : m_buttons) {
        m_layout->removeWidget(btn);
        delete btn;
    }
    m_buttons.clear();
    hide();
}
```

### src/messageviewwidget.h

```cpp
#pragma once

#include <QWidget>
#include <QVBoxLayout>
#include <QLabel>
#include <QFrame>
#include <QStackedWidget>
#include <QTextBrowser>
#include <memory>

#include "emailmessage.h"
#include "attachmentwidget.h"

#if HAS_QLITEHTML
#include <qlitehtml/qlitehtmlwidget.h>
#endif

class MessageViewWidget : public QWidget {
    Q_OBJECT

public:
    explicit MessageViewWidget(QWidget *parent = nullptr);

    void showMessage(std::shared_ptr<EmailMessage> message);
    void clear();

    void setDarkMode(bool dark);

signals:
    void attachmentRequested(const AttachmentInfo &info);

private:
    void setupUi();
    void displayHeaders(const EmailMessage &msg);
    void displayBody(const EmailMessage &msg);

    // Header section
    QFrame *m_headerFrame;
    QLabel *m_subjectLabel;
    QLabel *m_senderLabel;
    QLabel *m_recipientLabel;
    QLabel *m_dateLabel;
    QLabel *m_labelsContainer;

    // Body
    QStackedWidget *m_bodyStack;
    QTextBrowser *m_textBrowser;
#if HAS_QLITEHTML
    QLiteHtmlWidget *m_htmlView;
#endif

    // Attachments
    AttachmentWidget *m_attachmentWidget;

    // State
    bool m_darkMode = false;
    std::shared_ptr<EmailMessage> m_currentMessage;

    QVBoxLayout *m_mainLayout;
};
```

### src/messageviewwidget.cpp

```cpp
#include "messageviewwidget.h"
#include "mboxparser.h"

#include <QScrollArea>
#include <QDateTime>

MessageViewWidget::MessageViewWidget(QWidget *parent)
    : QWidget(parent)
{
    setupUi();
}

void MessageViewWidget::setupUi()
{
    m_mainLayout = new QVBoxLayout(this);
    m_mainLayout->setContentsMargins(0, 0, 0, 0);
    m_mainLayout->setSpacing(0);

    // ── Header Frame ────────────────────────────────────────────────────
    m_headerFrame = new QFrame(this);
    m_headerFrame->setObjectName(QStringLiteral("messageHeaderFrame"));
    m_headerFrame->setFrameShape(QFrame::NoFrame);

    auto *headerLayout = new QVBoxLayout(m_headerFrame);
    headerLayout->setContentsMargins(20, 16, 20, 16);
    headerLayout->setSpacing(4);

    m_subjectLabel = new QLabel(this);
    m_subjectLabel->setObjectName(QStringLiteral("subjectLabel"));
    m_subjectLabel->setWordWrap(true);
    headerLayout->addWidget(m_subjectLabel);

    m_senderLabel = new QLabel(this);
    m_senderLabel->setObjectName(QStringLiteral("senderLabel"));
    headerLayout->addWidget(m_senderLabel);

    m_recipientLabel = new QLabel(this);
    m_recipientLabel->setObjectName(QStringLiteral("recipientLabel"));
    m_recipientLabel->setWordWrap(true);
    headerLayout->addWidget(m_recipientLabel);

    m_dateLabel = new QLabel(this);
    m_dateLabel->setObjectName(QStringLiteral("dateLabel"));
    headerLayout->addWidget(m_dateLabel);

    m_labelsContainer = new QLabel(this);
    m_labelsContainer->setObjectName(QStringLiteral("labelBadge"));
    m_labelsContainer->setWordWrap(true);
    headerLayout->addWidget(m_labelsContainer);

    m_mainLayout->addWidget(m_headerFrame);

    // ── Attachment bar ──────────────────────────────────────────────────
    m_attachmentWidget = new AttachmentWidget(this);
    connect(m_attachmentWidget, &AttachmentWidget::attachmentClicked,
            this, &MessageViewWidget::attachmentRequested);
    m_mainLayout->addWidget(m_attachmentWidget);

    // ── Body Stack ──────────────────────────────────────────────────────
    m_bodyStack = new QStackedWidget(this);

    // Plain text fallback
    m_textBrowser = new QTextBrowser(this);
    m_textBrowser->setOpenExternalLinks(true);
    m_textBrowser->setReadOnly(true);
    m_bodyStack->addWidget(m_textBrowser);

#if HAS_QLITEHTML
    // litehtml HTML renderer
    m_htmlView = new QLiteHtmlWidget(this);
    m_bodyStack->addWidget(m_htmlView);
#endif

    m_mainLayout->addWidget(m_bodyStack, 1);

    clear();
}

void MessageViewWidget::showMessage(std::shared_ptr<EmailMessage> message)
{
    if (!message) {
        clear();
        return;
    }

    m_currentMessage = message;
    displayHeaders(*message);
    displayBody(*message);

    m_attachmentWidget->setAttachments(message->attachments);

    m_headerFrame->show();
}

void MessageViewWidget::clear()
{
    m_subjectLabel->clear();
    m_senderLabel->clear();
    m_recipientLabel->clear();
    m_dateLabel->clear();
    m_labelsContainer->clear();
    m_labelsContainer->hide();
    m_textBrowser->clear();
    m_attachmentWidget->clear();
    m_headerFrame->hide();
    m_currentMessage.reset();

    // Show placeholder
    m_textBrowser->setHtml(QStringLiteral(
        "<div style='text-align:center; color:#8e8e93; padding-top:100px; font-size:16px;'>"
        "Select a message to read</div>"
    ));
    m_bodyStack->setCurrentWidget(m_textBrowser);
}

void MessageViewWidget::displayHeaders(const EmailMessage &msg)
{
    m_subjectLabel->setText(msg.subject);
    m_senderLabel->setText(msg.from.displayString());

    // Recipients
    QStringList toList;
    for (const auto &addr : msg.to)
        toList << addr.displayString();

    QString recipientText = QStringLiteral("To: ") + toList.join(QStringLiteral(", "));

    if (!msg.cc.isEmpty()) {
        QStringList ccList;
        for (const auto &addr : msg.cc)
            ccList << addr.displayString();
        recipientText += QStringLiteral("\nCc: ") + ccList.join(QStringLiteral(", "));
    }

    m_recipientLabel->setText(recipientText);

    // Date
    if (msg.date.isValid()) {
        m_dateLabel->setText(msg.date.toString(QStringLiteral("dddd, MMMM d, yyyy 'at' h:mm AP")));
    } else {
        m_dateLabel->clear();
    }

    // Labels
    if (!msg.gmailLabels.isEmpty()) {
        m_labelsContainer->setText(msg.gmailLabels.join(QStringLiteral("  •  ")));
        m_labelsContainer->show();
    } else {
        m_labelsContainer->hide();
    }
}

void MessageViewWidget::displayBody(const EmailMessage &msg)
{
    // Try HTML body first
    if (msg.hasHtmlBody) {
        MboxParser parser(msg.mboxPath);
        if (parser.parse() && !parser.htmlBody().isEmpty()) {
            QString html = parser.htmlBody();

            // Optionally inject dark-mode CSS
            if (m_darkMode) {
                QString darkCss = QStringLiteral(
                    "<style>"
                    "body { background-color: #1c1c1e !important; color: #f5f5f7 !important; }"
                    "a { color: #0A84FF !important; }"
                    "</style>"
                );
                int headEnd = html.indexOf("</head>", 0, Qt::CaseInsensitive);
                if (headEnd >= 0) {
                    html.insert(headEnd, darkCss);
                } else {
                    html = darkCss + html;
                }
            }

#if HAS_QLITEHTML
            m_htmlView->setHtml(html);
            m_bodyStack->setCurrentWidget(m_htmlView);
            return;
#else
            m_textBrowser->setHtml(html);
            m_bodyStack->setCurrentWidget(m_textBrowser);
            return;
#endif
        }
    }

    // Fall back to plain text
    QString text = msg.textBody();
    if (!text.isEmpty()) {
        // Convert plain text to HTML with basic formatting
        QString html = text.toHtmlEscaped();
        html.replace('\n', QStringLiteral("<br>"));
        html.replace(QStringLiteral("  "), QStringLiteral("&nbsp;&nbsp;"));

        // Linkify URLs
        static QRegularExpression urlRe(QStringLiteral(R"((https?://\S+))"));
        html.replace(urlRe, QStringLiteral("<a href=\"\\1\">\\1</a>"));

        QString styled = QStringLiteral(
            "<div style='font-family: -apple-system, sans-serif; font-size: 14px; "
            "line-height: 1.6; padding: 16px; color: %1;'>%2</div>"
        ).arg(m_darkMode ? "#f5f5f7" : "#1d1d1f", html);

        m_textBrowser->setHtml(styled);
        m_bodyStack->setCurrentWidget(m_textBrowser);
    } else {
        m_textBrowser->setHtml(QStringLiteral(
            "<div style='text-align:center; color:#8e8e93; padding-top:50px;'>"
            "No message body available</div>"
        ));
        m_bodyStack->setCurrentWidget(m_textBrowser);
    }
}

void MessageViewWidget::setDarkMode(bool dark)
{
    m_darkMode = dark;

    // Re-display current message if any
    if (m_currentMessage) {
        displayBody(*m_currentMessage);
    }
}
```

### src/threadviewwidget.h

```cpp
#pragma once

#include <QWidget>
#include <QVBoxLayout>
#include <QScrollArea>
#include <QList>
#include <memory>

#include "emailmessage.h"
#include "emailstore.h"

class ThreadViewWidget : public QWidget {
    Q_OBJECT

public:
    explicit ThreadViewWidget(QWidget *parent = nullptr);

    void showThread(const QString &threadId, EmailStore *store);
    void clear();
    void setDarkMode(bool dark);

signals:
    void messageSelected(std::shared_ptr<EmailMessage> message);

private:
    QWidget *createMessageCard(std::shared_ptr<EmailMessage> msg, bool expanded);

    QScrollArea *m_scrollArea;
    QVBoxLayout *m_contentLayout;
    QWidget *m_contentWidget;
    bool m_darkMode = false;
};
```

### src/threadviewwidget.cpp

```cpp
#include "threadviewwidget.h"

#include <QLabel>
#include <QPushButton>
#include <QFrame>

ThreadViewWidget::ThreadViewWidget(QWidget *parent)
    : QWidget(parent)
{
    auto *layout = new QVBoxLayout(this);
    layout->setContentsMargins(0, 0, 0, 0);

    m_scrollArea = new QScrollArea(this);
    m_scrollArea->setWidgetResizable(true);
    m_scrollArea->setFrameShape(QFrame::NoFrame);

    m_contentWidget = new QWidget();
    m_contentLayout = new QVBoxLayout(m_contentWidget);
    m_contentLayout->setContentsMargins(0, 0, 0, 0);
    m_contentLayout->setSpacing(1);
    m_contentLayout->addStretch();

    m_scrollArea->setWidget(m_contentWidget);
    layout->addWidget(m_scrollArea);
}

void ThreadViewWidget::showThread(const QString &threadId, EmailStore *store)
{
    clear();

    if (!store || threadId.isEmpty()) return;

    auto messages = store->threadMessages(threadId);
    if (messages.isEmpty()) return;

    for (int i = 0; i < messages.size(); ++i) {
        bool expanded = (i == messages.size() - 1); // Expand last message
        auto *card = createMessageCard(messages[i], expanded);
        m_contentLayout->insertWidget(m_contentLayout->count() - 1, card); // Before stretch
    }
}

void ThreadViewWidget::clear()
{
    // Remove all widgets except the stretch
    while (m_contentLayout->count() > 1) {
        auto *item = m_contentLayout->takeAt(0);
        if (item->widget())
            delete item->widget();
        delete item;
    }
}

QWidget *ThreadViewWidget::createMessageCard(std::shared_ptr<EmailMessage> msg, bool expanded)
{
    auto *card = new QFrame();
    card->setObjectName(QStringLiteral("messageHeaderFrame"));
    card->setFrameShape(QFrame::NoFrame);
    card->setCursor(Qt::PointingHandCursor);

    auto *layout = new QVBoxLayout(card);
    layout->setContentsMargins(16, 12, 16, 12);
    layout->setSpacing(4);

    // Header row
    auto *headerLayout = new QHBoxLayout();
    auto *senderLabel = new QLabel(msg->from.displayString());
    senderLabel->setObjectName(QStringLiteral("senderLabel"));
    headerLayout->addWidget(senderLabel);

    headerLayout->addStretch();

    auto *dateLabel = new QLabel(msg->date.toString(QStringLiteral("MMM d, h:mm AP")));
    dateLabel->setObjectName(QStringLiteral("dateLabel"));
    headerLayout->addWidget(dateLabel);

    layout->addLayout(headerLayout);

    if (expanded) {
        // Show body preview
        QString text = msg->textBody();
        if (!text.isEmpty()) {
            // Skip headers in .txt
            int bodyStart = text.indexOf(QStringLiteral("\n\n"));
            if (bodyStart >= 0) text = text.mid(bodyStart + 2);

            if (text.length() > 500) text = text.left(500) + QStringLiteral("…");

            auto *bodyLabel = new QLabel(text);
            bodyLabel->setWordWrap(true);
            bodyLabel->setTextFormat(Qt::PlainText);
            layout->addWidget(bodyLabel);
        }
    } else {
        // Collapsed: show subject snippet
        auto *subjectLabel = new QLabel(msg->subject);
        subjectLabel->setObjectName(QStringLiteral("recipientLabel"));
        layout->addWidget(subjectLabel);
    }

    // Make the card clickable
    card->installEventFilter(this);

    // Store message pointer
    card->setProperty("emailMessage", QVariant::fromValue(
        reinterpret_cast<quintptr>(new std::shared_ptr<EmailMessage>(msg))));

    // Connect click
    connect(card, &QObject::destroyed, [msg]() {
        // prevent dangling
    });

    return card;
}

void ThreadViewWidget::setDarkMode(bool dark)
{
    m_darkMode = dark;
}
```

### src/searchwidget.h

```cpp
#pragma once

#include <QWidget>
#include <QLineEdit>
#include <QComboBox>
#include <QHBoxLayout>

class SearchWidget : public QWidget {
    Q_OBJECT

public:
    explicit SearchWidget(QWidget *parent = nullptr);

    enum SearchType {
        BySubject,
        ByFullText,
        BySender
    };

    QString searchText() const;
    SearchType searchType() const;

signals:
    void searchRequested(const QString &text, SearchWidget::SearchType type);

private:
    QLineEdit *m_searchEdit;
    QComboBox *m_typeCombo;
};
```

### src/searchwidget.cpp

```cpp
#include "searchwidget.h"
#include <QIcon>
#include <QLabel>
#include <QTimer>

SearchWidget::SearchWidget(QWidget *parent)
    : QWidget(parent)
{
    auto *layout = new QHBoxLayout(this);
    layout->setContentsMargins(8, 4, 8, 4);
    layout->setSpacing(8);

    // Search icon
    auto *iconLabel = new QLabel(this);
    iconLabel->setPixmap(QIcon(QStringLiteral(":/icons/search.svg")).pixmap(16, 16));
    layout->addWidget(iconLabel);

    // Search input
    m_searchEdit = new QLineEdit(this);
    m_searchEdit->setPlaceholderText(tr("Search emails..."));
    m_searchEdit->setClearButtonEnabled(true);
    layout->addWidget(m_searchEdit, 1);

    // Search type combo
    m_typeCombo = new QComboBox(this);
    m_typeCombo->addItem(tr("Subject"), BySubject);
    m_typeCombo->addItem(tr("Full Text"), ByFullText);
    m_typeCombo->addItem(tr("Sender"), BySender);
    layout->addWidget(m_typeCombo);

    // Debounced search
    auto *debounceTimer = new QTimer(this);
    debounceTimer->setSingleShot(true);
    debounceTimer->setInterval(300);

    connect(m_searchEdit, &QLineEdit::textChanged, debounceTimer, [debounceTimer]() {
        debounceTimer->start();
    });

    connect(debounceTimer, &QTimer::timeout, this, [this]() {
        QString text = m_searchEdit->text().trimmed();
        if (text.length() >= 2) {
            emit searchRequested(text, searchType());
        }
    });

    connect(m_searchEdit, &QLineEdit::returnPressed, this, [this]() {
        QString text = m_searchEdit->text().trimmed();
        if (!text.isEmpty()) {
            emit searchRequested(text, searchType());
        }
    });
}

QString SearchWidget::searchText() const
{
    return m_searchEdit->text().trimmed();
}

SearchWidget::SearchType SearchWidget::searchType() const
{
    return static_cast<SearchType>(m_typeCombo->currentData().toInt());
}
```

### src/thememanager.h

```cpp
#pragma once

#include <QObject>
#include <QString>

class QApplication;

class ThemeManager : public QObject {
    Q_OBJECT
    Q_PROPERTY(bool darkMode READ isDarkMode WRITE setDarkMode NOTIFY themeChanged)

public:
    explicit ThemeManager(QObject *parent = nullptr);

    bool isDarkMode() const { return m_darkMode; }
    void setDarkMode(bool dark);
    void toggleTheme();

    void applyTheme();

    static QString lightStyleSheet();
    static QString darkStyleSheet();

signals:
    void themeChanged(bool darkMode);

private:
    bool m_darkMode = false;
};
```

### src/thememanager.cpp

```cpp
#include "thememanager.h"

#include <QApplication>
#include <QFile>
#include <QTextStream>

ThemeManager::ThemeManager(QObject *parent)
    : QObject(parent)
{
}

void ThemeManager::setDarkMode(bool dark)
{
    if (m_darkMode == dark) return;
    m_darkMode = dark;
    applyTheme();
    emit themeChanged(m_darkMode);
}

void ThemeManager::toggleTheme()
{
    setDarkMode(!m_darkMode);
}

void ThemeManager::applyTheme()
{
    QString stylesheet = m_darkMode ? darkStyleSheet() : lightStyleSheet();

    if (auto *app = qobject_cast<QApplication *>(QCoreApplication::instance())) {
        app->setStyleSheet(stylesheet);
    }
}

QString ThemeManager::lightStyleSheet()
{
    QFile file(QStringLiteral(":/styles/macos_light.qss"));
    if (file.open(QIODevice::ReadOnly | QIODevice::Text)) {
        return QString::fromUtf8(file.readAll());
    }
    return {};
}

QString ThemeManager::darkStyleSheet()
{
    QFile file(QStringLiteral(":/styles/macos_dark.qss"));
    if (file.open(QIODevice::ReadOnly | QIODevice::Text)) {
        return QString::fromUtf8(file.readAll());
    }
    return {};
}
```

### src/mainwindow.h

```cpp
#pragma once

#include <QMainWindow>
#include <QSplitter>
#include <QTreeView>
#include <QListView>
#include <QToolBar>
#include <QStatusBar>
#include <QAction>
#include <QLabel>
#include <QProgressBar>

#include "emailstore.h"
#include "foldertreemodel.h"
#include "messagelistmodel.h"
#include "messagelistdelegate.h"
#include "messageviewwidget.h"
#include "searchwidget.h"
#include "thememanager.h"

class MainWindow : public QMainWindow {
    Q_OBJECT

public:
    explicit MainWindow(QWidget *parent = nullptr);
    ~MainWindow();

private slots:
    void openArchive();
    void onFolderSelected(const QModelIndex &index);
    void onMessageSelected(const QModelIndex &index);
    void onSearchRequested(const QString &text, SearchWidget::SearchType type);
    void onThemeToggled();
    void onLoadingStarted();
    void onLoadingProgress(int current, int total);
    void onLoadingFinished(int messageCount);

private:
    void setupUi();
    void setupMenuBar();
    void setupToolBar();
    void setupStatusBar();
    void createActions();
    void loadArchive(const QString &path);
    void updateStatusBar();

    // Layout
    QSplitter *m_mainSplitter;
    QSplitter *m_rightSplitter;

    // Panels
    QTreeView *m_folderTree;
    QListView *m_messageList;
    MessageViewWidget *m_messageView;

    // Search
    SearchWidget *m_searchWidget;

    // Models
    FolderTreeModel *m_folderModel;
    MessageListModel *m_messageListModel;
    MessageListDelegate *m_messageDelegate;

    // Store
    EmailStore *m_store;

    // Theme
    ThemeManager *m_themeManager;

    // Actions
    QAction *m_openAction;
    QAction *m_themeAction;

    // Status bar
    QLabel *m_statusLabel;
    QProgressBar *m_progressBar;

    // Toolbar
    QToolBar *m_toolBar;
};
```

### src/mainwindow.cpp

```cpp
#include "mainwindow.h"

#include <QMenuBar>
#include <QFileDialog>
#include <QMessageBox>
#include <QSettings>
#include <QApplication>
#include <QVBoxLayout>
#include <QHeaderView>
#include <QDesktopServices>
#include <QUrl>

MainWindow::MainWindow(QWidget *parent)
    : QMainWindow(parent)
    , m_store(new EmailStore(this))
    , m_themeManager(new ThemeManager(this))
{
    setupUi();
    setupMenuBar();
    setupToolBar();
    setupStatusBar();
    createActions();

    // Apply default theme (light)
    m_themeManager->applyTheme();

    // Restore last opened archive
    QSettings settings;
    QString lastArchive = settings.value(QStringLiteral("lastArchivePath")).toString();
    if (!lastArchive.isEmpty() && QDir(lastArchive).exists()) {
        loadArchive(lastArchive);
    }

    // Restore window geometry
    restoreGeometry(settings.value(QStringLiteral("windowGeometry")).toByteArray());
    restoreState(settings.value(QStringLiteral("windowState")).toByteArray());

    setWindowTitle(QStringLiteral("Email Archive Viewer"));
    resize(1400, 900);
}

MainWindow::~MainWindow()
{
    QSettings settings;
    settings.setValue(QStringLiteral("windowGeometry"), saveGeometry());
    settings.setValue(QStringLiteral("windowState"), saveState());
    settings.setValue(QStringLiteral("lastArchivePath"), m_store->archivePath());
}

void MainWindow::setupUi()
{
    // Central widget with search bar + main content
    auto *centralWidget = new QWidget(this);
    auto *centralLayout = new QVBoxLayout(centralWidget);
    centralLayout->setContentsMargins(0, 0, 0, 0);
    centralLayout->setSpacing(0);

    // Search widget
    m_searchWidget = new SearchWidget(this);
    centralLayout->addWidget(m_searchWidget);

    // Main 3-pane splitter
    m_mainSplitter = new QSplitter(Qt::Horizontal, this);

    // Left: Folder tree
    m_folderTree = new QTreeView(this);
    m_folderModel = new FolderTreeModel(this);
    m_folderTree->setModel(m_folderModel);
    m_folderTree->setHeaderHidden(true);
    m_folderTree->setAnimated(true);
    m_folderTree->setIndentation(16);
    m_folderTree->setMinimumWidth(200);
    m_folderTree->setMaximumWidth(350);

    // Middle: Message list
    m_messageList = new QListView(this);
    m_messageListModel = new MessageListModel(this);
    m_messageDelegate = new MessageListDelegate(this);
    m_messageList->setModel(m_messageListModel);
    m_messageList->setItemDelegate(m_messageDelegate);
    m_messageList->setSelectionMode(QAbstractItemView::SingleSelection);
    m_messageList->setMinimumWidth(280);
    m_messageList->setMaximumWidth(500);
    m_messageList->setVerticalScrollMode(QAbstractItemView::ScrollPerPixel);
    m_messageList->setMouseTracking(true);

    // Right: Message view (in a sub-splitter for thread view)
    m_messageView = new MessageViewWidget(this);

    m_mainSplitter->addWidget(m_folderTree);
    m_mainSplitter->addWidget(m_messageList);
    m_mainSplitter->addWidget(m_messageView);

    m_mainSplitter->setStretchFactor(0, 0);
    m_mainSplitter->setStretchFactor(1, 0);
    m_mainSplitter->setStretchFactor(2, 1);
    m_mainSplitter->setSizes({240, 350, 800});

    centralLayout->addWidget(m_mainSplitter, 1);

    setCentralWidget(centralWidget);

    // Connections
    connect(m_folderTree, &QTreeView::clicked,
            this, &MainWindow::onFolderSelected);

    connect(m_messageList, &QListView::clicked,
            this, &MainWindow::onMessageSelected);

    connect(m_searchWidget, &SearchWidget::searchRequested,
            this, &MainWindow::onSearchRequested);

    connect(m_store, &EmailStore::loadingStarted,
            this, &MainWindow::onLoadingStarted);
    connect(m_store, &EmailStore::loadingProgress,
            this, &MainWindow::onLoadingProgress);
    connect(m_store, &EmailStore::loadingFinished,
            this, &MainWindow::onLoadingFinished);
    connect(m_store, &EmailStore::errorOccurred,
            this, [this](const QString &error) {
                QMessageBox::warning(this, tr("Error"), error);
            });

    connect(m_messageView, &MessageViewWidget::attachmentRequested,
            this, [this](const AttachmentInfo &info) {
                // Try to open attachment location
                if (!info.storagePath.isEmpty()) {
                    QString fullPath = m_store->archivePath() + '/' + info.storagePath;
                    QDesktopServices::openUrl(QUrl::fromLocalFile(fullPath));
                } else {
                    QMessageBox::information(this, tr("Attachment"),
                                             tr("Attachment '%1' is stored within the .mbox file.\n"
                                                "Size: %2")
                                                 .arg(info.filename, info.sizeString()));
                }
            });
}

void MainWindow::setupMenuBar()
{
    auto *fileMenu = menuBar()->addMenu(tr("&File"));

    m_openAction = fileMenu->addAction(tr("&Open Archive..."), this, &MainWindow::openArchive);
    m_openAction->setShortcut(QKeySequence::Open);

    fileMenu->addSeparator();

    fileMenu->addAction(tr("&Quit"), qApp, &QApplication::quit, QKeySequence::Quit);

    auto *viewMenu = menuBar()->addMenu(tr("&View"));

    m_themeAction = viewMenu->addAction(tr("Toggle Dark Mode"), this, &MainWindow::onThemeToggled);
    m_themeAction->setShortcut(QKeySequence(Qt::CTRL | Qt::SHIFT | Qt::Key_T));
    m_themeAction->setCheckable(true);

    auto *helpMenu = menuBar()->addMenu(tr("&Help"));
    helpMenu->addAction(tr("About"), this, [this]() {
        QMessageBox::about(this, tr("Email Archive Viewer"),
                           tr("Email Archive Viewer v1.0\n\n"
                              "A Qt6 application for browsing email archives\n"
                              "exported from Gmail/Google Takeout.\n\n"
                              "Built with Qt %1").arg(qVersion()));
    });
}

void MainWindow::setupToolBar()
{
    m_toolBar = addToolBar(tr("Main"));
    m_toolBar->setMovable(false);
    m_toolBar->setIconSize(QSize(20, 20));
    m_toolBar->setToolButtonStyle(Qt::ToolButtonTextBesideIcon);
}

void MainWindow::setupStatusBar()
{
    m_statusLabel = new QLabel(tr("Ready"), this);
    statusBar()->addWidget(m_statusLabel, 1);

    m_progressBar = new QProgressBar(this);
    m_progressBar->setMaximumWidth(200);
    m_progressBar->setMaximumHeight(16);
    m_progressBar->hide();
    statusBar()->addPermanentWidget(m_progressBar);
}

void MainWindow::createActions()
{
    // Add toolbar buttons
    auto *openBtn = m_toolBar->addAction(
        QIcon(QStringLiteral(":/icons/folder.svg")),
        tr("Open Archive"),
        this, &MainWindow::openArchive
    );
    Q_UNUSED(openBtn);

    m_toolBar->addSeparator();

    auto *themeBtn = m_toolBar->addAction(
        QIcon(QStringLiteral(":/icons/moon.svg")),
        tr("Dark Mode"),
        this, &MainWindow::onThemeToggled
    );
    themeBtn->setCheckable(true);

    connect(m_themeManager, &ThemeManager::themeChanged, themeBtn,
            [themeBtn](bool dark) {
                themeBtn->setChecked(dark);
                themeBtn->setText(dark ? tr("Light Mode") : tr("Dark Mode"));
                themeBtn->setIcon(QIcon(dark
                    ? QStringLiteral(":/icons/sun.svg")
                    : QStringLiteral(":/icons/moon.svg")));
            });
}

void MainWindow::openArchive()
{
    QString dir = QFileDialog::getExistingDirectory(
        this, tr("Open Email Archive Directory"),
        QDir::homePath(),
        QFileDialog::ShowDirsOnly | QFileDialog::DontResolveSymlinks
    );

    if (!dir.isEmpty()) {
        loadArchive(dir);
    }
}

void MainWindow::loadArchive(const QString &path)
{
    m_messageListModel->clear();
    m_messageView->clear();

    if (m_store->loadArchive(path)) {
        m_folderModel->buildFromStore(m_store);
        m_folderTree->expandAll();

        setWindowTitle(QStringLiteral("Email Archive Viewer — %1").arg(
            QDir(path).dirName()));
    }
}

void MainWindow::onFolderSelected(const QModelIndex &index)
{
    if (!index.isValid()) return;

    int type = index.data(FolderTreeModel::FolderTypeRole).toInt();
    QString data = index.data(FolderTreeModel::FolderDataRole).toString();

    QList<IndexEntry> entries;

    switch (static_cast<FolderTreeModel::FolderType>(type)) {
    case FolderTreeModel::Label:
        entries = m_store->messagesByLabel(data);
        break;
    case FolderTreeModel::DateMonth:
        entries = m_store->messagesByMonth(data);
        break;
    case FolderTreeModel::Sender:
        entries = m_store->messagesBySender(data);
        break;
    default:
        return; // Root items, don't load
    }

    m_messageListModel->setEntries(entries, m_store);
    m_messageView->clear();

    updateStatusBar();
    m_statusLabel->setText(tr("%1 messages").arg(entries.size()));
}

void MainWindow::onMessageSelected(const QModelIndex &index)
{
    if (!index.isValid()) return;

    QString metaPath = index.data(MessageListModel::MetaPathRole).toString();
    if (metaPath.isEmpty()) return;

    auto msg = m_store->loadMessage(metaPath);
    if (msg) {
        m_messageView->showMessage(msg);
    }
}

void MainWindow::onSearchRequested(const QString &text, SearchWidget::SearchType type)
{
    QList<IndexEntry> results;

    switch (type) {
    case SearchWidget::BySubject:
        results = m_store->searchBySubjectWord(text);
        break;
    case SearchWidget::ByFullText:
        results = m_store->searchFullText(text);
        break;
    case SearchWidget::BySender:
        results = m_store->messagesBySender(text);
        break;
    }

    // For results that only have paths, load metadata to populate entries
    for (auto &entry : results) {
        if (entry.subject.isEmpty() && !entry.path.isEmpty()) {
            auto msg = m_store->loadMessage(entry.path);
            if (msg) {
                entry.messageId = msg->messageId;
                entry.subject = msg->subject;
                entry.from = msg->from.displayString();
                entry.date = msg->date.toString(Qt::ISODate);
                entry.threadId = msg->threadId;
            }
        }
    }

    m_messageListModel->setEntries(results, m_store);
    m_messageView->clear();

    m_statusLabel->setText(tr("Search: %1 results for '%2'").arg(results.size()).arg(text));
}

void MainWindow::onThemeToggled()
{
    m_themeManager->toggleTheme();
    bool dark = m_themeManager->isDarkMode();

    m_messageDelegate->setDarkMode(dark);
    m_messageView->setDarkMode(dark);

    // Refresh message list painting
    m_messageList->viewport()->update();
}

void MainWindow::onLoadingStarted()
{
    m_statusLabel->setText(tr("Loading archive..."));
    m_progressBar->setRange(0, 0); // Indeterminate
    m_progressBar->show();
}

void MainWindow::onLoadingProgress(int current, int total)
{
    if (total > 0) {
        m_progressBar->setRange(0, total);
        m_progressBar->setValue(current);
    }
    m_statusLabel->setText(tr("Loading... %1 messages").arg(current));
}

void MainWindow::onLoadingFinished(int messageCount)
{
    m_progressBar->hide();
    m_statusLabel->setText(tr("Loaded %1 messages, %2 threads")
                               .arg(messageCount)
                               .arg(m_store->totalThreads()));
}

void MainWindow::updateStatusBar()
{
    m_statusLabel->setText(tr("%1 messages, %2 threads")
                               .arg(m_store->totalMessages())
                               .arg(m_store->totalThreads()));
}
```

### src/main.cpp

```cpp
#include <QApplication>
#include <QCommandLineParser>
#include <QDir>

#include "mainwindow.h"
#include "thememanager.h"

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);

    // Application metadata
    QCoreApplication::setOrganizationName(QStringLiteral("EmailArchiveViewer"));
    QCoreApplication::setApplicationName(QStringLiteral("Email Archive Viewer"));
    QCoreApplication::setApplicationVersion(QStringLiteral("1.0.0"));

    // High-DPI support is automatic in Qt 6.10

    // Command line options
    QCommandLineParser parser;
    parser.setApplicationDescription(QStringLiteral("Email Archive Viewer — browse exported email archives"));
    parser.addHelpOption();
    parser.addVersionOption();

    parser.addPositionalArgument(QStringLiteral("archive"),
                                QStringLiteral("Path to email_archive directory"));

    QCommandLineOption darkOpt(QStringLiteral("dark"), QStringLiteral("Start in dark mode"));
    parser.addOption(darkOpt);

    parser.process(app);

    // Create and show main window
    MainWindow window;

    // Apply theme from command line
    if (parser.isSet(darkOpt)) {
        // Will be applied after window is constructed
        auto *tm = window.findChild<ThemeManager *>();
        if (tm) tm->setDarkMode(true);
    }

    // Open archive from command line
    QStringList args = parser.positionalArguments();
    if (!args.isEmpty()) {
        QString archivePath = args.first();
        if (QDir(archivePath).exists()) {
            // Use QMetaObject::invokeMethod to defer loading until event loop starts
            QMetaObject::invokeMethod(&window, [&window, archivePath]() {
                auto *store = window.findChild<EmailStore *>();
                if (store) {
                    store->loadArchive(archivePath);
                }
            }, Qt::QueuedConnection);
        }
    }

    window.show();

    return app.exec();
}
```

---

## Build & Run Instructions

### Prerequisites

1. **Qt 6.10+** installed (with Widgets module)
2. **CMake 3.22+**
3. **qlitehtml** (optional but recommended):
   - Clone from its repository into `third_party/qlitehtml/`
   - Or install system-wide and ensure it's findable by CMake

### Building

```bash
mkdir build && cd build
cmake .. -DCMAKE_PREFIX_PATH=/path/to/qt6
cmake --build . --parallel
```

### Running

```bash
# Open GUI and select archive directory manually
./EmailArchiveViewer

# Open specific archive directory
./EmailArchiveViewer /path/to/email_archive

# Start in dark mode
./EmailArchiveViewer --dark /path/to/email_archive
```

### Without qlitehtml

If `qlitehtml` is not available, the application falls back to Qt's built-in `QTextBrowser` for HTML rendering. This provides basic HTML display but without full CSS support. The build will emit a status message indicating the fallback.

---

## Design Notes

### Architecture Decisions

1. **Lazy loading**: Index files are loaded on demand when a folder is selected, not all at startup. The folder tree is built from `_label_list.json` and date index file names only.

2. **Three-pane layout**: Following the macOS Mail pattern — sidebar (folder tree), message list, and message preview — connected via `QSplitter` for user-resizable panels.

3. **Thread grouping**: Messages in the list are grouped by `threadId`. Thread head messages show a blue dot indicator, and child messages are indented with a connecting line.

4. **Message caching**: Up to 500 parsed `EmailMessage` objects are kept in an LRU-style cache to avoid re-reading `.meta.json` files on repeated views.

5. **MIME parsing**: A lightweight MIME parser handles `multipart/*` structures, `base64`, and `quoted-printable` transfer encodings to extract HTML bodies from `.mbox` files.

6. **Theme system**: QSS stylesheets are loaded from Qt resources. The `ThemeManager` applies them application-wide and notifies components that need additional adjustments (like the custom delegate and HTML body dark-mode CSS injection).

7. **Custom delegate**: The message list uses a custom `QStyledItemDelegate` for a modern, Mail-like appearance with sender, subject, date, and preview text in each row, rather than the default single-line list item.