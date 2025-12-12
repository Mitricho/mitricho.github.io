Ниже — полный код для создания анимированного splash‑экрана с GIF‑анимацией в **Qt 6** на C++.

### Что делает код
- Показывает полноэкранный splash‑экран с центрированной GIF‑анимацией.
- Анимация воспроизводится циклически, пока splash‑экран активен.
- Splash‑экран закрывается по нажатию клавиши или через заданное время.

### Требования
- Qt 6 (модуль `Widgets` и `Gui`)
- GIF‑файл (например, `loading.gif` в папке ресурсов)

---

### 1. Проектный файл (.pro)

```qmake
QT += core gui widgets

CONFIG += c++17

SOURCES += \
        main.cpp \
        splashscreen.cpp

HEADERS += \
        splashscreen.h

RESOURCES += resources.qrc  # если используете ресурсы
```

### 2. Заголовочный файл (splashscreen.h)

```cpp
#ifndef SPLASHSCREEN_H
#define SPLASHSCREEN_H

#include <QSplashScreen>
#include <QMovie>
#include <QLabel>

class SplashScreen : public QSplashScreen
{
    Q_OBJECT

public:
    explicit SplashScreen(const QString &gifPath, QWidget *parent = nullptr);
    ~SplashScreen();

protected:
    void mousePressEvent(QMouseEvent *event) override;

private:
    QMovie *movie;
    QLabel *label;
};

#endif // SPLASHSCREEN_H
```

### 3. Реализация (splashscreen.cpp)

```cpp
#include "splashscreen.h"

SplashScreen::SplashScreen(const QString &gifPath, QWidget *parent)
    : QSplashScreen(parent)
{
    // Создаём QMovie для GIF
    movie = new QMovie(gifPath);
    if (!movie->isValid()) {
        qWarning() << "Не удалось загрузить GIF:" << gifPath;
        return;
    }

    // Настраиваем QLabel для отображения анимации
    label = new QLabel(this);
    label->setAlignment(Qt::AlignCenter);
    label->setMovie(movie);

    // Размещаем label по центру splash‑экрана
    label->resize(movie->scaledSize());
    label->move((width() - label->width()) / 2,
                (height() - label->height()) / 2);

    // Запускаем анимацию
    movie->start();

    // Устанавливаем полупрозрачный фон (опционально)
    setWindowOpacity(0.95);
}

SplashScreen::~SplashScreen()
{
    movie->stop();
    delete movie;
    delete label;
}

// Закрываем splash при клике
void SplashScreen::mousePressEvent(QMouseEvent *event)
{
    Q_UNUSED(event)
    close();
}
```

### 4. Основной файл (main.cpp)

```cpp
#include <QApplication>
#include <QTimer>
#include "splashscreen.h"

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);

    // Путь к GIF (можно использовать ресурсы: ":images/loading.gif")
    QString gifPath = "loading.gif";  // или ":/loading.gif" для ресурсов

    // Создаём splash‑экран
    SplashScreen splash(gifPath);
    splash.showFullScreen();  // Полноэкранный режим

    // Автоматическое закрытие через 3000 мс (3 секунды)
    QTimer::singleShot(3000, &splash, &SplashScreen::close);

    // Запуск основного окна (здесь — просто ждём закрытия splash)
    return app.exec();
}
```

### 5. Ресурсы (optional, resources.qrc)

Если хотите включить GIF в ресурсы:

```xml
<RCC>
    <qresource prefix="/">
        <file>loading.gif</file>
    </qresource>
</RCC>
```

Тогда в `main.cpp` используйте:
```cpp
QString gifPath = ":/loading.gif";
```

---

### Как собрать и запустить

1. Поместите `loading.gif` в папку проекта (или добавьте в ресурсы).
2. Создайте файлы `.pro`, `splashscreen.h`, `splashscreen.cpp`, `main.cpp`.
3. Соберите проект через `qmake` и `make` (или в Qt Creator).
4. Запустите — появится полноэкранный splash с анимированным GIF.

---

### Примечания

- **Размер GIF**: лучше подбирать под разрешение экрана или масштабировать в коде.
- **Закрытие**: можно добавить кнопку, сигнал от основного окна или таймаут.
- **Стиль**: можно настроить прозрачность, рамку, фон через `setStyleSheet()`.

Если нужно добавить текст (например, версию ПО) — вставьте `QLabel` поверх анимации.
