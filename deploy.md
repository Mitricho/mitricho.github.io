Для максимально автоматизированного производства дистрибутивов C++/Qt6 приложения (Widgets и QML) под Debian/Ubuntu и Windows 11 идеально подходит связка **GitHub Actions (или GitLab CI) + Docker + кросс-компиляция**. Это обеспечивает воспроизводимость сборок и изолирует процесс от окружения хост-машины.

Основная стратегия:
1.  **Для Linux**: сборка нативной версии в контейнере Ubuntu 24.04, создание `.deb` пакета и, отдельно, в контейнере Ubuntu 20.04 (для совместимости glibc) сборка и упаковка в **AppImage** с помощью `linuxdeployqt`.
2.  **Для Windows**: кросс-компиляция на той же Debian/Ubuntu машине с использованием **MXE (M cross environment)** или инструментария **MinGW-w64**.

Ниже представлено детальное решение с инфраструктурой кода.

### 1. Структура репозитория и подготовка

Организуйте проект единообразно. Убедитесь, что ваш `CMakeLists.txt` корректно настроен для обеих платформ и использует `CMake` (стандарт для Qt6).

```bash
your-qt-app/
├── .github/workflows/          # Для GitHub Actions
│   ├── build.yml
│   └── Dockerfile
├── cmake/                       # Вспомогательные модули CMake (опционально)
├── src/                          # Исходники C++/QML
├── resources/                    # Ресурсы, иконки
├── dist/
│   ├── linux/
│   │   └── your-app.desktop      # .desktop файл для AppImage/Deb
│   └── windows/                  # Иконки .ico, .rc файлы
├── CMakeLists.txt
└── README.md
```

### 2. Базовая инфраструктура: Система сборки (CMake + Conan)

Для управления зависимостями Qt6 и инструментами сборки рекомендуется использовать **Conan** (менеджер пакетов C/C++) в профильных конфигурациях. Это делает пайплайн чище .

**Пример базового CMakeLists.txt:**
```cmake
cmake_minimum_required(VERSION 3.16)
project(MyQtApp VERSION 1.0.0 LANGUAGES CXX)

find_package(Qt6 REQUIRED COMPONENTS Core Widgets Quick)

qt_standard_project_setup()

add_executable(myapp ${PROJECT_SOURCES}) # Ваши исходники

target_link_libraries(myapp PRIVATE Qt6::Core Qt6::Widgets Qt6::Quick)

qt_generate_deploy_app_script(
    TARGET myapp
    OUTPUT_SCRIPT deploy_script
    MACOS_BUNDLE_POST_BUILD
)
install(SCRIPT ${deploy_script})
```

### 3. Автоматизация для Linux: .deb и AppImage

#### 3.1. Базовый образ сборки (Debian 12/Ubuntu 24)
Создайте образ для сборки нативных deb-пакетов и кросс-компиляции под Windows.

**`Dockerfile.build`**:
```dockerfile
FROM ubuntu:24.04 AS builder-base

# Предотвращаем интеративные запросы
ENV DEBIAN_FRONTEND=noninteractive

# Устанавливаем зависимости для сборки Qt6 и системные тулзы
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    cmake \
    ninja-build \
    git \
    python3-pip \
    # Зависимости Qt6 (из Wiki Qt )
    libfontconfig1-dev \
    libfreetype-dev \
    libx11-dev \
    libx11-xcb-dev \
    libxcb-cursor-dev \
    libxcb-glx0-dev \
    libxcb-icccm4-dev \
    libxcb-image0-dev \
    libxcb-keysyms1-dev \
    libxcb-randr0-dev \
    libxcb-render-util0-dev \
    libxcb-shape0-dev \
    libxcb-shm0-dev \
    libxcb-sync-dev \
    libxcb-util-dev \
    libxcb-xfixes0-dev \
    libxcb-xkb-dev \
    libxcb1-dev \
    libxext-dev \
    libxfixes-dev \
    libxi-dev \
    libxkbcommon-dev \
    libxkbcommon-x11-dev \
    libxrender-dev \
    # Для WebEngine, если используется
    libssl-dev \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем aqtinstall для гибкой установки Qt6
RUN pip3 install aqtinstall

# Точка входа - передавать команды сборки
CMD ["/bin/bash"]
```

#### 3.2. Сборка .deb пакета
Процесс: установка Qt6 через `aqt` в контейнере, конфигурация CMake, сборка и упаковка в `.deb` через `cpack`.

#### 3.3. Сборка AppImage (в Ubuntu 20.04)
**Критическое требование:** Для максимальной совместимости AppImage должен собираться в **самом старом возможном дистрибутиве**, так как AppImage включает glibc с системы сборки. Ubuntu 20.04 — идеальный кандидат .

**`Dockerfile.appimage`**:
```dockerfile
FROM ubuntu:20.04 AS appimage-builder

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y software-properties-common wget && \
    # Для Qt6 нужен более свежий gcc, чем в стоковом 20.04
    add-apt-repository ppa:ubuntu-toolchain-r/test -y && \
    apt-get update && apt-get install -y \
    build-essential \
    cmake \
    g++-11 \
    wget \
    libgl1-mesa-dev \
    fuse \
    libfuse2 \
    file \
    && update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-11 110 \
    && update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-11 110

# Устанавливаем Qt6 через aqt (требует Python)
RUN apt-get install -y python3-pip && pip3 install aqtinstall
RUN aqt install-qt linux desktop 6.6.0 gcc_64 -O /opt/Qt

# Устанавливаем linuxdeployqt
RUN wget -c https://github.com/probonopd/linuxdeployqt/releases/download/continuous/linuxdeployqt-continuous-x86_64.AppImage && \
    chmod a+x linuxdeployqt-continuous-x86_64.AppImage && \
    mv linuxdeployqt-continuous-x86_64.AppImage /usr/local/bin/linuxdeployqt

WORKDIR /workspace
```
**Логика сборки:** внутри этого контейнера происходит стандартная сборка, после чего `linuxdeployqt` копирует все зависимости в `AppDir` и финализирует AppImage .

### 4. Автоматизация для Windows 11: Кросс-компиляция

Самый эффективный метод без использования виртуальной машины Windows — кросс-компиляция с Linux на Windows с помощью **MXE** .

#### 4.1. Подготовка среды (MXE)
Добавьте в ваш базовый Docker-образ установку MXE.

**Дополнение к `Dockerfile.build`**:
```dockerfile
# Кросс-компилятор для Windows
RUN apt-get update && apt-get install -y automake autoconf libtool make pkg-config

# Клонируем MXE и собираем Qt6 статически (для простоты распространения .exe)
RUN git clone https://github.com/mxe/mxe.git /opt/mxe && \
    cd /opt/mxe && \
    make MXE_TARGETS='x86_64-w64-mingw32.static' qt6-qtbase qt6-qtdeclarative -j$(nproc)
ENV PATH=/opt/mxe/usr/bin:$PATH
```

#### 4.2. Сборка .exe
Используйте тулчейн файл от MXE. CMake автоматически подхватит кросс-компилятор, если правильно указать параметры.

### 5. CI/CD (GitHub Actions) — полная автоматизация

Ниже пример пайплайна, который запускается при пуше в main. Он использует подход Docker-изоляции, описанный в статье dev.to .

**`.github/workflows/build.yml`**:
```yaml
name: Build Distributions

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  build-deb:
    runs-on: ubuntu-24.04 # Используем свежий раннер
    container:
      image: ubuntu:24.04 # Работаем внутри чистого Ubuntu 24
    steps:
      - uses: actions/checkout@v4
      - name: Install deps (Qt6 from apt for speed)
        run: |
          apt-get update && apt-get install -y qt6-base-dev qt6-declarative-dev cmake ninja-build build-essential
      - name: Configure and Build
        run: |
          cmake -B build -DCMAKE_BUILD_TYPE=Release -GNinja
          cmake --build build
      - name: Package .deb
        run: |
          cd build
          cpack -G DEB
      - name: Upload .deb
        uses: actions/upload-artifact@v4
        with:
          name: deb-package
          path: build/*.deb

  build-appimage:
    runs-on: ubuntu-22.04 # Нам нужен Linux для запуска Docker
    steps:
      - uses: actions/checkout@v4
      - name: Build AppImage inside Ubuntu 20.04 Docker
        run: |
          # Собираем образ Ubuntu 20.04 с Qt6
          docker build -t appimage-builder -f Dockerfile.appimage .
          # Запускаем сборку внутри контейнера
          docker run --rm -v ${{ github.workspace }}:/workspace appimage-builder \
            /bin/bash -c "
              cd /workspace && \
              mkdir -p build-appimage && cd build-appimage && \
              /opt/Qt/6.6.0/gcc_64/bin/qmake ../your-app.pro && \
              make -j$(nproc) && \
              linuxdeployqt ./your-app -appimage
            "
      - name: Upload AppImage
        uses: actions/upload-artifact@v4
        with:
          name: appimage
          path: '*.AppImage'

  build-windows:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@v4
      - name: Cross-compile for Windows (MXE)
        run: |
          # Здесь используем предустановленный MXE (можно через Docker)
          docker run --rm -v ${{ github.workspace }}:/src mxe/mxe:static-qt6 \
            /bin/bash -c "
              cd /src && \
              x86_64-w64-mingw32.static-cmake -B build-windows -DCMAKE_BUILD_TYPE=Release && \
              cmake --build build-windows
            "
      - name: Upload .exe
        uses: actions/upload-artifact@v4
        with:
          name: windows-exe
          path: build-windows/*.exe
```

### 6. Итоговая схема

1.  **Хост-система**: Debian 12 (или Ubuntu 24).
2.  **Инструменты**: Docker, Git, make.
3.  **Билд-агент (CI)**: 
    *   Для `.deb`: собирается в `ubuntu:24` с Qt из репозитория (быстро) .
    *   Для `AppImage`: собирается в `ubuntu:20` со скомпилированным Qt6 от `aqt`, упаковывается `linuxdeployqt` .
    *   Для `.exe`: собирается в контейнере с `MXE` и `mingw-w64` .
4.  **Результат**: Автоматически создаются три артефакта, доступных для скачивания.

Это решение обеспечивает **максимальную автоматизацию**, использует **только Linux-инфраструктуру** для всех сборок и гарантирует совместимость AppImage со старыми системами за счет использования Ubuntu 20.04.