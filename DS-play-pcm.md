Вот код на C++ с использованием Qt6 для воспроизведения несжатого PCM звука из файла:

## Заголовочный файл (audioplayer.h)

```cpp
#ifndef AUDIOPLAYER_H
#define AUDIOPLAYER_H

#include <QObject>
#include <QAudioOutput>
#include <QAudioDevice>
#include <QMediaDevices>
#include <QFile>
#include <QBuffer>
#include <QTimer>

class AudioPlayer : public QObject
{
    Q_OBJECT

public:
    explicit AudioPlayer(QObject *parent = nullptr);
    ~AudioPlayer();

    bool loadAudioFile(const QString &filename, 
                      QAudioFormat::SampleFormat sampleFormat = QAudioFormat::Int16,
                      int sampleRate = 44100, 
                      int channelCount = 2);
    
    void play();
    void pause();
    void stop();
    void setVolume(float volume); // 0.0 - 1.0

    QAudioFormat getAudioFormat() const { return m_format; }
    qint64 getPosition() const;
    qint64 getTotalSize() const;

signals:
    void playbackFinished();
    void errorOccurred(const QString &error);

private slots:
    void handleStateChanged(QAudio::State state);
    void feedAudioData();

private:
    bool setupAudioOutput();
    void cleanup();

    QAudioOutput *m_audioOutput;
    QIODevice *m_audioDevice;
    QFile m_audioFile;
    QBuffer m_buffer;
    QAudioFormat m_format;
    QTimer *m_feedTimer;
    
    static const int BUFFER_SIZE = 4096; // Размер буфера в байтах
};

#endif // AUDIOPLAYER_H
```

## Файл реализации (audioplayer.cpp)

```cpp
#include "audioplayer.h"
#include <QDebug>
#include <QAudioDevice>

AudioPlayer::AudioPlayer(QObject *parent)
    : QObject(parent)
    , m_audioOutput(nullptr)
    , m_audioDevice(nullptr)
    , m_feedTimer(new QTimer(this))
{
    m_feedTimer->setSingleShot(false);
    connect(m_feedTimer, &QTimer::timeout, this, &AudioPlayer::feedAudioData);
}

AudioPlayer::~AudioPlayer()
{
    cleanup();
}

bool AudioPlayer::loadAudioFile(const QString &filename, 
                               QAudioFormat::SampleFormat sampleFormat,
                               int sampleRate, 
                               int channelCount)
{
    cleanup();

    // Настройка формата аудио
    m_format.setSampleRate(sampleRate);
    m_format.setChannelCount(channelCount);
    m_format.setSampleFormat(sampleFormat);

    // Открываем файл
    m_audioFile.setFileName(filename);
    if (!m_audioFile.open(QIODevice::ReadOnly)) {
        emit errorOccurred("Не удалось открыть файл: " + filename);
        return false;
    }

    // Настраиваем аудио выход
    if (!setupAudioOutput()) {
        m_audioFile.close();
        return false;
    }

    return true;
}

bool AudioPlayer::setupAudioOutput()
{
    // Получаем доступное аудио устройство
    QAudioDevice device = QMediaDevices::defaultAudioOutput();
    if (device.isNull()) {
        emit errorOccurred("Аудио устройство не найдено");
        return false;
    }

    // Проверяем поддержку формата
    if (!device.isFormatSupported(m_format)) {
        emit errorOccurred("Формат не поддерживается устройством");
        return false;
    }

    // Создаем аудио выход
    m_audioOutput = new QAudioOutput(device, m_format, this);
    connect(m_audioOutput, &QAudioOutput::stateChanged, 
            this, &AudioPlayer::handleStateChanged);

    return true;
}

void AudioPlayer::play()
{
    if (!m_audioOutput || !m_audioFile.isOpen()) {
        emit errorOccurred("Аудио не загружено");
        return;
    }

    if (m_audioOutput->state() == QAudio::SuspendedState) {
        m_audioOutput->resume();
    } else {
        // Начинаем воспроизведение с начала файла
        if (m_audioOutput->state() == QAudio::ActiveState) {
            stop();
        }

        m_audioDevice = m_audioOutput->start();
        if (!m_audioDevice) {
            emit errorOccurred("Не удалось начать воспроизведение");
            return;
        }

        // Запускаем таймер для подачи данных
        m_feedTimer->start(10); // Обновление каждые 10 мс
    }
}

void AudioPlayer::pause()
{
    if (m_audioOutput) {
        m_audioOutput->suspend();
        m_feedTimer->stop();
    }
}

void AudioPlayer::stop()
{
    if (m_audioOutput) {
        m_audioOutput->stop();
        m_feedTimer->stop();
        
        // Перематываем файл в начало
        if (m_audioFile.isOpen()) {
            m_audioFile.seek(0);
        }
    }
}

void AudioPlayer::setVolume(float volume)
{
    if (m_audioOutput) {
        m_audioOutput->setVolume(qBound(0.0f, volume, 1.0f));
    }
}

qint64 AudioPlayer::getPosition() const
{
    return m_audioFile.isOpen() ? m_audioFile.pos() : 0;
}

qint64 AudioPlayer::getTotalSize() const
{
    return m_audioFile.isOpen() ? m_audioFile.size() : 0;
}

void AudioPlayer::handleStateChanged(QAudio::State state)
{
    switch (state) {
    case QAudio::IdleState:
        // Воспроизведение завершено
        m_feedTimer->stop();
        emit playbackFinished();
        break;
    case QAudio::StoppedState:
        m_feedTimer->stop();
        if (m_audioOutput->error() != QAudio::NoError) {
            emit errorOccurred("Ошибка аудио: " + 
                             QString::number(m_audioOutput->error()));
        }
        break;
    default:
        break;
    }
}

void AudioPlayer::feedAudioData()
{
    if (!m_audioDevice || !m_audioFile.isOpen()) {
        return;
    }

    // Проверяем, сколько данных может принять устройство
    qint64 bytesFree = m_audioOutput->bytesFree();
    if (bytesFree <= 0) {
        return;
    }

    // Читаем данные из файла
    QByteArray data = m_audioFile.read(qMin(bytesFree, (qint64)BUFFER_SIZE));
    
    if (data.size() > 0) {
        m_audioDevice->write(data);
    } else {
        // Конец файла
        m_feedTimer->stop();
    }
}

void AudioPlayer::cleanup()
{
    if (m_audioOutput) {
        m_audioOutput->stop();
        m_audioOutput->deleteLater();
        m_audioOutput = nullptr;
    }
    
    if (m_audioFile.isOpen()) {
        m_audioFile.close();
    }
    
    m_feedTimer->stop();
}
```

## Пример использования (main.cpp)

```cpp
#include <QApplication>
#include <QWidget>
#include <QVBoxLayout>
#include <QPushButton>
#include <QSlider>
#include <QLabel>
#include <QFileDialog>
#include <QMessageBox>
#include "audioplayer.h"

class MainWindow : public QWidget
{
    Q_OBJECT

public:
    MainWindow(QWidget *parent = nullptr) : QWidget(parent)
    {
        setupUI();
        setupConnections();
        
        m_player = new AudioPlayer(this);
        connect(m_player, &AudioPlayer::playbackFinished, 
                this, &MainWindow::onPlaybackFinished);
        connect(m_player, &AudioPlayer::errorOccurred, 
                this, &MainWindow::onErrorOccurred);
    }

private slots:
    void loadAudio()
    {
        QString filename = QFileDialog::getOpenFileName(this, 
            "Открыть PCM файл", "", "RAW Audio (*.raw *.pcm);;Все файлы (*)");
        
        if (!filename.isEmpty()) {
            // Укажите правильные параметры вашего PCM файла
            if (m_player->loadAudioFile(filename, 
                                       QAudioFormat::Int16, // Формат сэмпла
                                       44100,              // Частота дискретизации
                                       2)) {               // Количество каналов
                m_statusLabel->setText("Файл загружен: " + QFileInfo(filename).fileName());
            }
        }
    }

    void playAudio()
    {
        m_player->play();
        m_statusLabel->setText("Воспроизведение...");
    }

    void pauseAudio()
    {
        m_player->pause();
        m_statusLabel->setText("Пауза");
    }

    void stopAudio()
    {
        m_player->stop();
        m_statusLabel->setText("Остановлено");
    }

    void setVolume(int volume)
    {
        m_player->setVolume(volume / 100.0f);
    }

    void onPlaybackFinished()
    {
        m_statusLabel->setText("Воспроизведение завершено");
    }

    void onErrorOccurred(const QString &error)
    {
        QMessageBox::critical(this, "Ошибка", error);
        m_statusLabel->setText("Ошибка: " + error);
    }

private:
    void setupUI()
    {
        QVBoxLayout *layout = new QVBoxLayout(this);

        // Кнопки управления
        QHBoxLayout *buttonLayout = new QHBoxLayout();
        m_loadButton = new QPushButton("Загрузить файл");
        m_playButton = new QPushButton("Воспроизвести");
        m_pauseButton = new QPushButton("Пауза");
        m_stopButton = new QPushButton("Стоп");
        
        buttonLayout->addWidget(m_loadButton);
        buttonLayout->addWidget(m_playButton);
        buttonLayout->addWidget(m_pauseButton);
        buttonLayout->addWidget(m_stopButton);

        // Регулятор громкости
        QHBoxLayout *volumeLayout = new QHBoxLayout();
        volumeLayout->addWidget(new QLabel("Громкость:"));
        m_volumeSlider = new QSlider(Qt::Horizontal);
        m_volumeSlider->setRange(0, 100);
        m_volumeSlider->setValue(50);
        volumeLayout->addWidget(m_volumeSlider);

        // Статус
        m_statusLabel = new QLabel("Загрузите PCM файл для воспроизведения");

        layout->addLayout(buttonLayout);
        layout->addLayout(volumeLayout);
        layout->addWidget(m_statusLabel);

        setWindowTitle("PCM Audio Player");
        resize(400, 150);
    }

    void setupConnections()
    {
        connect(m_loadButton, &QPushButton::clicked, this, &MainWindow::loadAudio);
        connect(m_playButton, &QPushButton::clicked, this, &MainWindow::playAudio);
        connect(m_pauseButton, &QPushButton::clicked, this, &MainWindow::pauseAudio);
        connect(m_stopButton, &QPushButton::clicked, this, &MainWindow::stopAudio);
        connect(m_volumeSlider, &QSlider::valueChanged, this, &MainWindow::setVolume);
    }

    AudioPlayer *m_player;
    QPushButton *m_loadButton;
    QPushButton *m_playButton;
    QPushButton *m_pauseButton;
    QPushButton *m_stopButton;
    QSlider *m_volumeSlider;
    QLabel *m_statusLabel;
};

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);
    
    MainWindow window;
    window.show();
    
    return app.exec();
}

#include "main.moc"
```

## Файл проекта (CMakeLists.txt)

```cmake
cmake_minimum_required(VERSION 3.16)
project(AudioPlayer)

find_package(Qt6 REQUIRED COMPONENTS Core Widgets Multimedia)

qt_standard_project_setup()

qt_add_executable(AudioPlayer
    main.cpp
    audioplayer.cpp
    audioplayer.h
)

target_link_libraries(AudioPlayer Qt6::Core Qt6::Widgets Qt6::Multimedia)
```

## Ключевые особенности:

1. **Гибкая настройка формата** - можно указать частоту дискретизации, количество каналов и формат сэмплов
2. **Потоковая передача** - данные читаются из файла по мере необходимости
3. **Управление воспроизведением** - play, pause, stop
4. **Контроль громкости**
5. **Обработка ошибок**
6. **Сигналы состояния** - уведомления о завершении воспроизведения и ошибках

## Важные замечания:

- Укажите правильные параметры PCM в методе `loadAudioFile()` (формат сэмплов, частота, каналы)
- Файл должен содержать "сырые" PCM данные без заголовков
- Размер буфера можно настроить через константу `BUFFER_SIZE`

Этот код предоставляет полнофункциональный плеер для воспроизведения несжатого PCM аудио из файлов.
