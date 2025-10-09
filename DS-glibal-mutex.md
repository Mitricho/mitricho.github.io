Да, конечно! Вот несколько способов создать глобальный static мьютекс, доступный всем классам программы:

## Способ 1: Глобальная статическая переменная в namespace

**conversionmanager.h**
```cpp
#ifndef CONVERSIONMANAGER_H
#define CONVERSIONMANAGER_H

#include <QMutex>
#include <QObject>

namespace ConversionManager {
    extern QMutex& getGlobalMutex();
    bool isConversionAvailable();
    bool tryStartConversion(); // Попытаться начать конвертацию
    void finishConversion();   // Завершить конвертацию
}

#endif // CONVERSIONMANAGER_H
```

**conversionmanager.cpp**
```cpp
#include "conversionmanager.h"

namespace ConversionManager {
    // Глобальный мьютекс для всех FFmpeg операций
    static QMutex globalFfmpegMutex;
    
    QMutex& getGlobalMutex() {
        return globalFfmpegMutex;
    }
    
    bool isConversionAvailable() {
        return globalFfmpegMutex.tryLock();
    }
    
    bool tryStartConversion() {
        if (globalFfmpegMutex.tryLock()) {
            return true;
        }
        return false;
    }
    
    void finishConversion() {
        // Проверяем, не заблокирован ли уже мьютекс текущим потоком
        if (globalFfmpegMutex.tryLock()) {
            // Если смогли захватить, значит он не был заблокирован - что-то не так
            globalFfmpegMutex.unlock();
            qWarning() << "Conversion finished but mutex was not locked!";
        } else {
            // Мьютекс заблокирован - разблокируем
            globalFfmpegMutex.unlock();
        }
    }
}
```

## Способ 2: Singleton класс с расширенным функционалом

**globalconversioncontroller.h**
```cpp
#ifndef GLOBALCONVERSIONCONTROLLER_H
#define GLOBALCONVERSIONCONTROLLER_H

#include <QMutex>
#include <QObject>
#include <QTimer>

class GlobalConversionController : public QObject
{
    Q_OBJECT

public:
    static GlobalConversionController& instance();
    
    QMutex& getMutex() { return m_globalMutex; }
    
    // Проверка доступности
    bool isConversionAvailable();
    
    // Попытка начать конвертацию
    bool tryAcquireConversion(int timeoutMs = 0);
    
    // Освобождение конвертации
    void releaseConversion();
    
    // Принудительное освобождение (на случай аварийных ситуаций)
    void forceReleaseConversion();
    
    // Получить информацию о состоянии
    QString getStatus() const;

signals:
    void conversionStarted();
    void conversionFinished();
    void conversionAvailableChanged(bool available);

private:
    explicit GlobalConversionController(QObject *parent = nullptr);
    ~GlobalConversionController() = default;
    
    QMutex m_globalMutex;
    QTimer m_safetyTimer;
    bool m_conversionInProgress = false;

private slots:
    void onSafetyTimeout();
};

#endif // GLOBALCONVERSIONCONTROLLER_H
```

**globalconversioncontroller.cpp**
```cpp
#include "globalconversioncontroller.h"
#include <QDebug>

GlobalConversionController::GlobalConversionController(QObject *parent) 
    : QObject(parent)
{
    // Таймер безопасности на случай, если конвертация зависнет
    m_safetyTimer.setSingleShot(true);
    m_safetyTimer.setInterval(300000); // 5 минут максимум
    connect(&m_safetyTimer, &QTimer::timeout, this, &GlobalConversionController::onSafetyTimeout);
}

GlobalConversionController& GlobalConversionController::instance() {
    static GlobalConversionController instance;
    return instance;
}

bool GlobalConversionController::isConversionAvailable() {
    return m_globalMutex.tryLock();
}

bool GlobalConversionController::tryAcquireConversion(int timeoutMs) {
    if (timeoutMs == 0) {
        // Без ожидания
        if (m_globalMutex.tryLock()) {
            m_conversionInProgress = true;
            m_safetyTimer.start();
            emit conversionStarted();
            emit conversionAvailableChanged(false);
            return true;
        }
        return false;
    } else {
        // С ожиданием
        if (m_globalMutex.tryLock(timeoutMs)) {
            m_conversionInProgress = true;
            m_safetyTimer.start();
            emit conversionStarted();
            emit conversionAvailableChanged(false);
            return true;
        }
        return false;
    }
}

void GlobalConversionController::releaseConversion() {
    if (m_conversionInProgress) {
        m_globalMutex.unlock();
        m_conversionInProgress = false;
        m_safetyTimer.stop();
        emit conversionFinished();
        emit conversionAvailableChanged(true);
    }
}

void GlobalConversionController::forceReleaseConversion() {
    if (m_globalMutex.tryLock()) {
        // Если смогли захватить - значит он не был заблокирован
        m_globalMutex.unlock();
    } else {
        // Мьютекс заблокирован - принудительно разблокируем
        m_globalMutex.unlock();
    }
    m_conversionInProgress = false;
    m_safetyTimer.stop();
    emit conversionFinished();
    emit conversionAvailableChanged(true);
    qWarning() << "Conversion was force-released!";
}

QString GlobalConversionController::getStatus() const {
    if (m_globalMutex.tryLock()) {
        const_cast<QMutex&>(m_globalMutex).unlock();
        return "Available";
    } else {
        return "Busy - conversion in progress";
    }
}

void GlobalConversionController::onSafetyTimeout() {
    qCritical() << "Conversion safety timeout! Force releasing mutex.";
    forceReleaseConversion();
}
```

## Способ 3: Использование в различных классах

**videoencoder.h**
```cpp
class VideoEncoder : public QObject
{
    Q_OBJECT
public:
    bool encodeVideo(const QString &input, const QString &output) {
        // Используем глобальный контроллер
        auto& controller = GlobalConversionController::instance();
        
        if (!controller.tryAcquireConversion()) {
            qDebug() << "VideoEncoder: System is busy, cannot start encoding";
            return false;
        }
        
        QMutexLocker locker(&controller.getMutex()); // Для автоматического освобождения
        
        try {
            // Логика кодирования с FFmpeg
            // ...
            
            controller.releaseConversion();
            return true;
        } catch (...) {
            controller.forceReleaseConversion();
            throw;
        }
    }
};
```

**audioprocessor.h**
```cpp
class AudioProcessor : public QObject
{
    Q_OBJECT
public:
    void processAudio() {
        // Проверка доступности перед началом операции
        if (!GlobalConversionController::instance().isConversionAvailable()) {
            emit error("System is busy with video conversion");
            return;
        }
        
        // Используем namespace подход
        if (!ConversionManager::tryStartConversion()) {
            emit error("Cannot start audio processing - system busy");
            return;
        }
        
        // Автоматическое освобождение через QMutexLocker
        QMutexLocker locker(&ConversionManager::getGlobalMutex());
        
        // Логика обработки аудио с FFmpeg
        // ...
        
        // Мьютекс автоматически освободится
    }
};
```

**mainconverter.h** (ваш основной класс)
```cpp
class VideoConverter : public QObject
{
    Q_OBJECT
public slots:
    int convertVideo(const QString &input, const QString &output) {
        auto& controller = GlobalConversionController::instance();
        
        if (!controller.tryAcquireConversion()) {
            emit conversionBusy();
            return -1;
        }
        
        // Используем QMutexLocker для гарантированного освобождения
        QMutexLocker locker(&controller.getMutex());
        
        try {
            // Ваша логика конвертации...
            AVFormatContext *pFormatContext = avformat_alloc_context();
            // ...
            
            // При успешном завершении
            controller.releaseConversion();
            emit conversionFinished();
            return 0;
            
        } catch (...) {
            controller.forceReleaseConversion();
            throw;
        }
    }
};
```

## Использование в QML через Context Property

**main.cpp**
```cpp
#include "globalconversioncontroller.h"

int main(int argc, char *argv[]) {
    QGuiApplication app(argc, argv);
    
    // Регистрируем синглтон для доступа из QML
    qmlRegisterSingletonType<GlobalConversionController>("App", 1, 0, "ConversionController",
        [](QQmlEngine *engine, QJSEngine *scriptEngine) -> QObject* {
            Q_UNUSED(engine)
            Q_UNUSED(scriptEngine)
            return &GlobalConversionController::instance();
        });
    
    // ...
}
```

**main.qml**
```qml
import App 1.0

Item {
    ConversionController {
        id: conversionController
        onConversionAvailableChanged: {
            console.log("Conversion available:", available)
            convertButton.enabled = available
        }
    }
    
    Button {
        id: convertButton
        text: "Convert Video"
        onClicked: {
            if (conversionController.isConversionAvailable()) {
                videoConverter.convertVideo("input.mp4", "output.avi")
            } else {
                console.log("System is busy, please wait...")
            }
        }
    }
    
    Text {
        text: "Status: " + conversionController.getStatus()
    }
}
```

## Преимущества этого подхода:

1. **Единая точка контроля** - все классы используют один мьютекс
2. **Безопасность** - автоматическое управление блокировками
3. **Расширяемость** - легко добавить новые классы, использующие FFmpeg
4. **Мониторинг** - можно отслеживать состояние системы
5. **QML интеграция** - полный доступ из QML
6. **Защита от зависаний** - таймер безопасности

Выберите подход, который лучше подходит для вашей архитектуры!
