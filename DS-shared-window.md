Для организации общего кода между проектами QMake и CMake я рекомендую следующую структуру:

## 1. Структура директорий

```
project_root/
├── shared_lib/              # Общая библиотека с QWindow
│   ├── include/
│   │   └── shared_window.h
│   ├── src/
│   │   └── shared_window.cpp
│   ├── CMakeLists.txt      # Для CMake сборки
│   └── shared_lib.pro      # Для QMake сборки
├── project_qmake/
│   ├── project_qmake.pro
│   └── main.cpp
└── project_cmake/
    ├── CMakeLists.txt
    └── main.cpp
```

## 2. Код общей библиотеки

**shared_window.h:**
```cpp
#pragma once

#include <QWindow>
#include <QQuickRenderControl>

class SharedWindow : public QWindow
{
    Q_OBJECT

public:
    explicit SharedWindow(QWindow *parent = nullptr);
    ~SharedWindow();

    void init();
    void render();

protected:
    void exposeEvent(QExposeEvent *event) override;
    void resizeEvent(QResizeEvent *event) override;

private:
    QScopedPointer<QQuickRenderControl> m_renderControl;
    // другие общие методы и поля
};
```

**shared_window.cpp:**
```cpp
#include "shared_window.h"

SharedWindow::SharedWindow(QWindow *parent) : QWindow(parent)
{
    // Инициализация
}

SharedWindow::~SharedWindow() = default;

void SharedWindow::init()
{
    // Общая логика инициализации
}

void SharedWindow::render()
{
    // Общая логика рендеринга
}

// ... остальные реализации
```

## 3. Конфигурационные файлы

**shared_lib.pro (QMake):**
```qmake
QT += core gui quick

TEMPLATE = lib
CONFIG += staticlib

TARGET = shared_window

HEADERS += include/shared_window.h
SOURCES += src/shared_window.cpp

INCLUDEPATH += include

# Общие определения
DEFINES += QT_DEPRECATED_WARNINGS
```

**CMakeLists.txt (для общей библиотеки):**
```cmake
cmake_minimum_required(VERSION 3.16)
project(SharedWindow)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(Qt6 REQUIRED COMPONENTS Core Gui Quick)

set(SOURCES
    src/shared_window.cpp
)

set(HEADERS
    include/shared_window.h
)

add_library(shared_window STATIC ${SOURCES} ${HEADERS})

target_include_directories(shared_window PUBLIC
    ${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_link_libraries(shared_window PUBLIC
    Qt6::Core
    Qt6::Gui
    Qt6::Quick
)

target_compile_definitions(shared_window PRIVATE
    QT_DEPRECATED_WARNINGS
)
```

## 4. Настройка проектов-потребителей

**project_qmake.pro:**
```qmake
QT += core gui quick

TARGET = project_qmake
TEMPLATE = app

SOURCES += main.cpp

# Подключение общей библиотеки
INCLUDEPATH += ../shared_lib/include
LIBS += -L../shared_lib -lshared_window

PRE_TARGETDEPS += ../shared_lib/libshared_window.a

# Сборка общей библиотеки перед проектом
shared_lib.target = ../shared_lib/libshared_window.a
shared_lib.commands = cd ../shared_lib && qmake && $(MAKE)
QMAKE_EXTRA_TARGETS += shared_lib
PRE_TARGETDEPS += $$shared_lib.target
```

**CMakeLists.txt (для CMake проекта):**
```cmake
cmake_minimum_required(VERSION 3.16)
project(CmakeProject)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(Qt6 REQUIRED COMPONENTS Core Gui Quick)

# Добавление общей библиотеки как подпроекта
add_subdirectory(../shared_lib shared_lib)

add_executable(${PROJECT_NAME} main.cpp)

target_link_libraries(${PROJECT_NAME} PRIVATE
    shared_window
    Qt6::Core
    Qt6::Gui
    Qt6::Quick
)
```

## 5. Альтернативный подход: Git Submodule

Если проекты находятся в разных репозиториях:

```bash
# В каждом проекте
git submodule add https://github.com/yourorg/shared-window-lib.git shared_lib
git submodule update --init --recursive
```

## 6. Рекомендации

1. **Используйте статическую библиотеку** - проще в распространении
2. **Минимизируйте зависимости** - включайте только Qt Core, Gui, Quick
3. **Версионирование** - используйте semantic versioning для библиотеки
4. **Автоматизация сборки** - CI/CD для проверки совместимости
5. **Документация** - опишите зависимости и требования к окружению

Этот подход позволит легко поддерживать общий код между разными системами сборки и обеспечит гибкость в разработке.