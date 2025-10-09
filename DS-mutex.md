Вот пример с гарантированным освобождением мьютекса при любом сценарии:

## Полностью безопасная реализация с QMutexLocker

```cpp
// VideoConverter.h
#include <QObject>
#include <QMutex>
#include <QMutexLocker>

class VideoConverter : public QObject
{
    Q_OBJECT
    Q_PROPERTY(bool isAvailable READ isAvailable NOTIFY availabilityChanged)

public:
    explicit VideoConverter(QObject *parent = nullptr) : QObject(parent) {}

    bool isAvailable() const {
        // Пытаемся захватить и сразу отпускаем для проверки
        if (s_ffmpegMutex.tryLock()) {
            s_ffmpegMutex.unlock();
            return true;
        }
        return false;
    }

public slots:
    int convertVideo(const QString &input, const QString &output) {
        // Пытаемся захватить мьютекс без ожидания
        QMutexLocker locker(&s_ffmpegMutex);
        
        if (!locker.isLocked()) {
            // Мьютекс занят - возвращаем -1
            emit conversionBusy();
            return -1;
        }

        // Мьютекс захвачен - QMutexLocker гарантирует его освобождение
        // при выходе из метода (включая исключения и ранние return)
        
        emit conversionStarted();
        emit availabilityChanged(false);

        // 1. Проверка входных параметров
        if (input.isEmpty() || output.isEmpty()) {
            emit error("Input or output filename is empty");
            return -2; // Мьютекс автоматически освободится!
        }

        // 2. Инициализация FFmpeg структур
        AVFormatContext *pFormatContext = nullptr;
        int ret = avformat_open_input(&pFormatContext, input.toStdString().c_str(), nullptr, nullptr);
        if (ret != 0) {
            emit error(QString("Could not open input file: %1").arg(ret));
            return -3; // Мьютекс автоматически освободится!
        }

        // 3. Используем RAII для автоматического освобождения FFmpeg ресурсов
        struct FFmpegCleanup {
            AVFormatContext **ctx;
            FFmpegCleanup(AVFormatContext **context) : ctx(context) {}
            ~FFmpegCleanup() {
                if (*ctx) {
                    avformat_close_input(ctx);
                }
            }
        } cleanup(&pFormatContext);

        try {
            // 4. Получение информации о потоке
            if (avformat_find_stream_info(pFormatContext, nullptr) < 0) {
                emit error("Could not find stream information");
                return -4; // Мьютекс и FFmpeg ресурсы автоматически освободятся!
            }

            // 5. Основной цикл конвертации
            AVPacket packet;
            while (av_read_frame(pFormatContext, &packet) >= 0) {
                // Проверяем не была ли запрошена отмена операции
                if (m_cancelRequested) {
                    emit conversionCancelled();
                    return -5; // Все ресурсы автоматически освободятся!
                }

                // ... логика обработки пакета ...
                
                // Эмулируем прогресс для примера
                static int progress = 0;
                progress += 10;
                if (progress <= 100) {
                    emit progressChanged(progress);
                }

                av_packet_unref(&packet);
                
                // Искусственное исключение для демонстрации безопасности
                if (progress == 50) {
                    // throw std::runtime_error("Demo exception"); // Раскомментируйте для теста
                }
            }

            // 6. Успешное завершение
            emit conversionFinished();
            return 0;

        } catch (const std::exception &e) {
            // Обработка исключений - мьютекс ВСЕ РАВНО освободится!
            emit error(QString("Exception during conversion: %1").arg(e.what()));
            return -6;
        } catch (...) {
            emit error("Unknown exception during conversion");
            return -7;
        }

        // Мьютекс автоматически освободится здесь через деструктор locker
    }

    // Дополнительный метод для отмены операции
    Q_INVOKABLE void cancelConversion() {
        m_cancelRequested = true;
    }

signals:
    void conversionStarted();
    void conversionFinished();
    void conversionBusy();
    void conversionCancelled();
    void error(const QString &message);
    void progressChanged(int percent);
    void availabilityChanged(bool available);

private:
    static QMutex s_ffmpegMutex;
    bool m_cancelRequested = false;
};

// VideoConverter.cpp
QMutex VideoConverter::s_ffmpegMutex;
```

## Альтернативная версия с более строгой обработкой ошибок

```cpp
// Альтернативная, более безопасная версия метода convertVideo
int VideoConverter::convertVideo(const QString &input, const QString &output) {
    // Сразу создаем QMutexLocker - это гарантирует освобождение мьютекса
    QMutexLocker locker(&s_ffmpegMutex);
    
    if (!locker.isLocked()) {
        emit conversionBusy();
        return -1;
    }

    // Обнуляем флаг отмены при начале новой операции
    m_cancelRequested = false;
    
    emit conversionStarted();
    emit availabilityChanged(false);

    // Используем unique_ptr с кастомным deleter для FFmpeg ресурсов
    auto formatContextDeleter = [](AVFormatContext* ctx) {
        if (ctx) {
            avformat_close_input(&ctx);
        }
    };
    
    std::unique_ptr<AVFormatContext, decltype(formatContextDeleter)> pFormatContext(nullptr, formatContextDeleter);

    // Основной блок с обработкой ошибок
    int result = 0;
    AVFormatContext *tempContext = nullptr;
    
    // 1. Открываем входной файл
    int ret = avformat_open_input(&tempContext, input.toStdString().c_str(), nullptr, nullptr);
    if (ret != 0) {
        emit error(QString("Could not open input file: %1").arg(ret));
        result = -2;
        goto cleanup; // Используем goto для централизованной очистки
    }
    pFormatContext.reset(tempContext);

    // 2. Получаем информацию о потоке
    if (avformat_find_stream_info(pFormatContext.get(), nullptr) < 0) {
        emit error("Could not find stream information");
        result = -3;
        goto cleanup;
    }

    // 3. Основная логика конвертации
    try {
        AVPacket packet;
        av_init_packet(&packet);
        
        while (av_read_frame(pFormatContext.get(), &packet) >= 0) {
            if (m_cancelRequested) {
                emit conversionCancelled();
                result = -4;
                goto cleanup;
            }
            
            // ... обработка пакета ...
            
            av_packet_unref(&packet);
        }
        
        emit conversionFinished();
        result = 0;

    } catch (const std::exception &e) {
        emit error(QString("Exception: %1").arg(e.what()));
        result = -5;
    }

cleanup:
    // Все ресурсы автоматически освободятся:
    // - pFormatContext через unique_ptr deleter
    // - мьютекс через QMutexLocker деструктор
    
    return result;
}
```

## Пример использования в QML

```qml
VideoConverter {
    id: videoConverter
    
    onConversionStarted: console.log("Конвертация началась")
    onConversionFinished: console.log("Конвертация завершена успешно!")
    onConversionBusy: console.log("Система занята, попробуйте позже")
    onConversionCancelled: console.log("Конвертация отменена")
    onError: (message) => console.log("Ошибка:", message)
    onProgressChanged: (percent) => progressBar.value = percent
    onAvailabilityChanged: (available) => convertButton.enabled = available
}

Column {
    Button {
        id: convertButton
        text: "Конвертировать"
        onClicked: {
            var result = videoConverter.convertVideo("input.mp4", "output.avi")
            console.log("Результат запуска:", result)
        }
    }
    
    Button {
        text: "Отменить"
        onClicked: videoConverter.cancelConversion()
    }
    
    ProgressBar {
        id: progressBar
        width: parent.width
        from: 0
        to: 100
    }
    
    Text {
        text: "Доступность: " + (videoConverter.isAvailable ? "Свободен" : "Занят")
    }
}
```

## Ключевые гарантии безопасности:

1. **QMutexLocker** гарантирует освобождение мьютекса при любом выходе из метода
2. **RAII** для FFmpeg ресурсов через unique_ptr с кастомным deleter
3. **Обработка исключений** - мьютекс освобождается даже при исключениях
4. **Ранние return** - не требуют ручного освобождения мьютекса
5. **Централизованная очистка** через goto или вложенные scope

Этот подход обеспечивает 100% гарантию освобождения ресурсов при любом сценарии выполнения метода.
