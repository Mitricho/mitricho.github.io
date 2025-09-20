Отлично! Добавляю запрошенные функции. Вот расширенная версия класса:

## Обновленный WaveformGenerator.h

```cpp
#ifndef WAVEFORMGENERATOR_H
#define WAVEFORMGENERATOR_H

#include <QObject>
#include <QImage>
#include <QVector>
#include <QSize>
#include <QColor>
#include <QMap>
#include <memory>

extern "C" {
#include <libavformat/avformat.h>
#include <libavcodec/avcodec.h>
#include <libavutil/avutil.h>
#include <libswresample/swresample.h>
}

class WaveformGenerator : public QObject
{
    Q_OBJECT

public:
    explicit WaveformGenerator(QObject *parent = nullptr);
    ~WaveformGenerator();

    enum WaveformStyle {
        StyleLines,
        StyleBars,
        StyleFilled,
        StyleSpectrogram
    };
    Q_ENUM(WaveformStyle)

    enum ExportFormat {
        FormatPNG,
        FormatSVG,
        FormatJPEG
    };
    Q_ENUM(ExportFormat)

    Q_PROPERTY(QSize imageSize READ imageSize WRITE setImageSize NOTIFY imageSizeChanged)
    Q_PROPERTY(QColor waveformColor READ waveformColor WRITE setWaveformColor NOTIFY waveformColorChanged)
    Q_PROPERTY(QColor backgroundColor READ backgroundColor WRITE setBackgroundColor NOTIFY backgroundColorChanged)
    Q_PROPERTY(QColor clipColor READ clipColor WRITE setClipColor NOTIFY clipColorChanged)
    Q_PROPERTY(WaveformStyle style READ style WRITE setStyle NOTIFY styleChanged)
    Q_PROPERTY(int fftSize READ fftSize WRITE setFftSize NOTIFY fftSizeChanged)
    Q_PROPERTY(bool showClipping READ showClipping WRITE setShowClipping NOTIFY showClippingChanged)
    Q_PROPERTY(double clipThreshold READ clipThreshold WRITE setClipThreshold NOTIFY clipThresholdChanged)

    QSize imageSize() const;
    void setImageSize(const QSize &size);

    QColor waveformColor() const;
    void setWaveformColor(const QColor &color);

    QColor backgroundColor() const;
    void setBackgroundColor(const QColor &color);

    QColor clipColor() const;
    void setClipColor(const QColor &color);

    WaveformStyle style() const;
    void setStyle(WaveformStyle style);

    int fftSize() const;
    void setFftSize(int size);

    bool showClipping() const;
    void setShowClipping(bool show);

    double clipThreshold() const;
    void setClipThreshold(double threshold);

public slots:
    void generateWaveform(const QString &filePath);
    void generateSpectrogram(const QString &filePath);
    void cancelGeneration();
    bool exportWaveform(const QString &filePath, ExportFormat format = FormatPNG, int imageIndex = 0) const;
    bool exportSVG(const QString &filePath, int imageIndex = 0) const;
    QVector<float> getFrequencySpectrum(int channel = 0) const;
    QVector<QPointF> getClippingPoints(int channel = 0) const;

signals:
    void waveformGenerated(const QVector<QImage> &waveformImages);
    void spectrogramGenerated(const QImage &spectrogramImage);
    void generationProgress(double progress);
    void errorOccurred(const QString &errorMessage);
    void imageSizeChanged();
    void waveformColorChanged();
    void backgroundColorChanged();
    void clipColorChanged();
    void styleChanged();
    void fftSizeChanged();
    void showClippingChanged();
    void clipThresholdChanged();

private:
    struct AVFormatContextDeleter {
        void operator()(AVFormatContext* ctx) const {
            if (ctx) avformat_close_input(&ctx);
        }
    };

    struct AVCodecContextDeleter {
        void operator()(AVCodecContext* ctx) const {
            if (ctx) avcodec_free_context(&ctx);
        }
    };

    struct SwrContextDeleter {
        void operator()(SwrContext* ctx) const {
            if (ctx) swr_free(&ctx);
        }
    };

    using AVFormatContextPtr = std::unique_ptr<AVFormatContext, AVFormatContextDeleter>;
    using AVCodecContextPtr = std::unique_ptr<AVCodecContext, AVCodecContextDeleter>;
    using SwrContextPtr = std::unique_ptr<SwrContext, SwrContextDeleter>;

    // Основные методы
    bool initializeFFmpeg(const QString &filePath);
    int findAudioStream();
    bool setupAudioCodec(int audioStreamIndex);
    bool setupResampler();
    bool processAudioFrames();
    
    // Waveform методы
    QVector<QImage> createWaveformImages(const QVector<float> &samples);
    QImage createWaveformImage(const QVector<float> &channelSamples, int channelIndex);
    void drawWaveformLines(QPainter &painter, const QVector<float> &samples, int channelIndex);
    void drawWaveformBars(QPainter &painter, const QVector<float> &samples, int channelIndex);
    void drawWaveformFilled(QPainter &painter, const QVector<float> &samples, int channelIndex);
    
    // Спектрограмма методы
    QImage createSpectrogramImage(const QVector<float> &samples);
    QVector<float> applyFFT(const QVector<float> &input);
    void applyWindowFunction(QVector<float> &samples, int startIdx);
    QVector<QVector<float>> computeSpectrogram(const QVector<float> &samples);
    
    // Вспомогательные методы
    QVector<QPointF> findClippingPoints(const QVector<float> &samples, int channelIndex) const;
    bool isClipping(float sample) const;
    QColor getColorForAmplitude(float amplitude) const;
    QColor getColorForFrequency(float magnitude, float maxMagnitude) const;
    
    // Экспорт
    bool exportToSVG(const QString &filePath, const QImage &image) const;

    AVFormatContextPtr formatContext_;
    AVCodecContextPtr codecContext_;
    SwrContextPtr swrContext_;
    
    QSize imageSize_;
    QColor waveformColor_;
    QColor backgroundColor_;
    QColor clipColor_;
    WaveformStyle style_;
    
    bool cancelRequested_;
    int sampleRate_;
    int channels_;
    AVSampleFormat outputSampleFormat_;
    
    // Новые поля для расширенных функций
    QVector<float> allSamples_;
    QVector<QPointF> clippingPoints_;
    QVector<QVector<float>> frequencySpectra_;
    QVector<QImage> waveformImages_;
    QImage spectrogramImage_;
    
    int fftSize_;
    bool showClipping_;
    double clipThreshold_;
};

#endif // WAVEFORMGENERATOR_H
```

## Обновленный WaveformGenerator.cpp

```cpp
#include "WaveformGenerator.h"
#include <QPainter>
#include <QDebug>
#include <QFile>
#include <QSvgGenerator>
#include <cmath>
#include <complex>
#include <valarray>

extern "C" {
#include <libavutil/opt.h>
#include <libavutil/channel_layout.h>
}

const double PI = 3.14159265358979323846;

WaveformGenerator::WaveformGenerator(QObject *parent)
    : QObject(parent)
    , imageSize_(800, 200)
    , waveformColor_(Qt::blue)
    , backgroundColor_(Qt::white)
    , clipColor_(Qt::red)
    , style_(StyleLines)
    , cancelRequested_(false)
    , sampleRate_(44100)
    , channels_(2)
    , outputSampleFormat_(AV_SAMPLE_FMT_FLT)
    , fftSize_(1024)
    , showClipping_(true)
    , clipThreshold_(0.99)
{
}

WaveformGenerator::~WaveformGenerator()
{
    cancelGeneration();
}

// ... существующие getters/setters ...

int WaveformGenerator::fftSize() const { return fftSize_; }
void WaveformGenerator::setFftSize(int size)
{
    if (fftSize_ != size && size >= 32 && size <= 16384) {
        fftSize_ = size;
        emit fftSizeChanged();
    }
}

bool WaveformGenerator::showClipping() const { return showClipping_; }
void WaveformGenerator::setShowClipping(bool show)
{
    if (showClipping_ != show) {
        showClipping_ = show;
        emit showClippingChanged();
    }
}

double WaveformGenerator::clipThreshold() const { return clipThreshold_; }
void WaveformGenerator::setClipThreshold(double threshold)
{
    if (clipThreshold_ != threshold && threshold >= 0.5 && threshold <= 1.0) {
        clipThreshold_ = threshold;
        emit clipThresholdChanged();
    }
}

void WaveformGenerator::generateWaveform(const QString &filePath)
{
    cancelRequested_ = false;
    allSamples_.clear();
    clippingPoints_.clear();
    frequencySpectra_.clear();
    
    if (!initializeFFmpeg(filePath)) {
        emit errorOccurred("Failed to initialize FFmpeg");
        return;
    }

    int audioStreamIndex = findAudioStream();
    if (audioStreamIndex == -1) {
        emit errorOccurred("No audio stream found");
        return;
    }

    if (!setupAudioCodec(audioStreamIndex)) {
        emit errorOccurred("Failed to setup audio codec");
        return;
    }

    if (!setupResampler()) {
        emit errorOccurred("Failed to setup resampler");
        return;
    }

    if (!processAudioFrames()) {
        emit errorOccurred("Failed to process audio frames");
        return;
    }

    if (cancelRequested_) {
        return;
    }

    waveformImages_ = createWaveformImages(allSamples_);
    emit waveformGenerated(waveformImages_);
}

void WaveformGenerator::generateSpectrogram(const QString &filePath)
{
    generateWaveform(filePath);
    if (cancelRequested_ || allSamples_.isEmpty()) return;

    spectrogramImage_ = createSpectrogramImage(allSamples_);
    emit spectrogramGenerated(spectrogramImage_);
}

bool WaveformGenerator::exportWaveform(const QString &filePath, ExportFormat format, int imageIndex) const
{
    if (imageIndex < 0 || imageIndex >= waveformImages_.size()) {
        return false;
    }

    switch (format) {
    case FormatPNG:
        return waveformImages_[imageIndex].save(filePath, "PNG");
    case FormatSVG:
        return exportToSVG(filePath, waveformImages_[imageIndex]);
    case FormatJPEG:
        return waveformImages_[imageIndex].save(filePath, "JPEG");
    default:
        return false;
    }
}

bool WaveformGenerator::exportSVG(const QString &filePath, int imageIndex) const
{
    return exportWaveform(filePath, FormatSVG, imageIndex);
}

QVector<float> WaveformGenerator::getFrequencySpectrum(int channel) const
{
    if (channel < 0 || channel >= frequencySpectra_.size()) {
        return QVector<float>();
    }
    return frequencySpectra_[channel];
}

QVector<QPointF> WaveformGenerator::getClippingPoints(int channel) const
{
    QVector<QPointF> points;
    for (const auto &point : clippingPoints_) {
        if (static_cast<int>(point.y()) == channel) {
            points.append(QPointF(point.x(), point.y()));
        }
    }
    return points;
}

// ... существующие методы initializeFFmpeg, findAudioStream, setupAudioCodec, setupResampler ...

bool WaveformGenerator::processAudioFrames()
{
    AVPacket *packet = av_packet_alloc();
    AVFrame *frame = av_frame_alloc();
    AVFrame *resampledFrame = av_frame_alloc();

    if (!packet || !frame || !resampledFrame) {
        av_packet_free(&packet);
        av_frame_free(&frame);
        av_frame_free(&resampledFrame);
        return false;
    }

    const int64_t duration = formatContext_->duration;
    int64_t processedBytes = 0;

    while (av_read_frame(formatContext_.get(), packet) >= 0 && !cancelRequested_) {
        if (packet->stream_index != codecContext_->stream_index) {
            av_packet_unref(packet);
            continue;
        }

        if (avcodec_send_packet(codecContext_.get(), packet) < 0) {
            av_packet_unref(packet);
            continue;
        }

        while (avcodec_receive_frame(codecContext_.get(), frame) >= 0 && !cancelRequested_) {
            if (swr_convert_frame(swrContext_.get(), resampledFrame, frame) < 0) {
                continue;
            }

            const float *samples = reinterpret_cast<const float*>(resampledFrame->data[0]);
            const int sampleCount = resampledFrame->nb_samples * channels_;
            
            for (int i = 0; i < sampleCount; i++) {
                allSamples_.append(samples[i]);
                
                // Detect clipping
                if (showClipping_ && isClipping(samples[i])) {
                    int channel = i % channels_;
                    int sampleIndex = allSamples_.size() - 1;
                    clippingPoints_.append(QPointF(sampleIndex, channel));
                }
            }

            processedBytes += packet->size;
            if (duration > 0) {
                double progress = static_cast<double>(processedBytes) / duration;
                emit generationProgress(qMin(progress, 1.0));
            }

            av_frame_unref(resampledFrame);
        }

        av_packet_unref(packet);
    }

    av_packet_free(&packet);
    av_frame_free(&frame);
    av_frame_free(&resampledFrame);

    return !cancelRequested_;
}

QVector<QImage> WaveformGenerator::createWaveformImages(const QVector<float> &samples)
{
    QVector<QImage> images;
    
    if (samples.isEmpty()) {
        return images;
    }

    const int totalSamples = samples.size();
    const int samplesPerChannel = totalSamples / channels_;
    
    for (int channel = 0; channel < channels_; channel++) {
        QVector<float> channelSamples;
        channelSamples.reserve(samplesPerChannel);
        
        for (int i = channel; i < totalSamples; i += channels_) {
            channelSamples.append(samples[i]);
        }
        
        images.append(createWaveformImage(channelSamples, channel));
    }

    return images;
}

QImage WaveformGenerator::createWaveformImage(const QVector<float> &samples, int channelIndex)
{
    QImage image(imageSize_, QImage::Format_ARGB32);
    image.fill(backgroundColor_);
    
    QPainter painter(&image);
    painter.setRenderHint(QPainter::Antialiasing);

    switch (style_) {
    case StyleLines:
        drawWaveformLines(painter, samples, channelIndex);
        break;
    case StyleBars:
        drawWaveformBars(painter, samples, channelIndex);
        break;
    case StyleFilled:
        drawWaveformFilled(painter, samples, channelIndex);
        break;
    case StyleSpectrogram:
        // Спектрограмма обрабатывается отдельно
        break;
    }

    // Рисуем точки клиппинга
    if (showClipping_) {
        painter.setPen(QPen(clipColor_, 3));
        painter.setBrush(clipColor_);
        
        const float samplesPerPixel = static_cast<float>(samples.size()) / imageSize_.width();
        const int centerY = imageSize_.height() / 2;
        
        for (const auto &point : clippingPoints_) {
            if (static_cast<int>(point.y()) == channelIndex) {
                int x = static_cast<int>(point.x() / samplesPerPixel);
                painter.drawEllipse(x, centerY, 4, 4);
            }
        }
    }

    painter.end();
    return image;
}

void WaveformGenerator::drawWaveformLines(QPainter &painter, const QVector<float> &samples, int channelIndex)
{
    if (samples.isEmpty()) return;

    const int width = imageSize_.width();
    const int height = imageSize_.height();
    const int centerY = height / 2;
    const float samplesPerPixel = static_cast<float>(samples.size()) / width;

    QPainterPath path;
    bool firstPoint = true;

    for (int x = 0; x < width; x++) {
        const int startSample = static_cast<int>(x * samplesPerPixel);
        const int endSample = static_cast<int>((x + 1) * samplesPerPixel);
        
        if (endSample >= samples.size()) break;

        float maxAmplitude = 0.0f;
        for (int i = startSample; i < endSample; i++) {
            maxAmplitude = qMax(maxAmplitude, std::abs(samples[i]));
        }

        const int y = centerY - static_cast<int>(maxAmplitude * centerY);

        if (firstPoint) {
            path.moveTo(x, y);
            firstPoint = false;
        } else {
            path.lineTo(x, y);
        }
    }

    painter.setPen(QPen(waveformColor_, 1.5));
    painter.drawPath(path);
}

void WaveformGenerator::drawWaveformBars(QPainter &painter, const QVector<float> &samples, int channelIndex)
{
    if (samples.isEmpty()) return;

    const int width = imageSize_.width();
    const int height = imageSize_.height();
    const int centerY = height / 2;
    const float samplesPerPixel = static_cast<float>(samples.size()) / width;

    painter.setPen(Qt::NoPen);
    painter.setBrush(waveformColor_);

    for (int x = 0; x < width; x++) {
        const int startSample = static_cast<int>(x * samplesPerPixel);
        const int endSample = static_cast<int>((x + 1) * samplesPerPixel);
        
        if (endSample >= samples.size()) break;

        float maxAmplitude = 0.0f;
        for (int i = startSample; i < endSample; i++) {
            maxAmplitude = qMax(maxAmplitude, std::abs(samples[i]));
        }

        const int barHeight = static_cast<int>(maxAmplitude * height);
        const int y = centerY - barHeight / 2;

        painter.drawRect(x, y, 1, barHeight);
    }
}

void WaveformGenerator::drawWaveformFilled(QPainter &painter, const QVector<float> &samples, int channelIndex)
{
    if (samples.isEmpty()) return;

    const int width = imageSize_.width();
    const int height = imageSize_.height();
    const int centerY = height / 2;
    const float samplesPerPixel = static_cast<float>(samples.size()) / width;

    QPainterPath path;
    path.moveTo(0, centerY);

    for (int x = 0; x < width; x++) {
        const int startSample = static_cast<int>(x * samplesPerPixel);
        const int endSample = static_cast<int>((x + 1) * samplesPerPixel);
        
        if (endSample >= samples.size()) break;

        float maxAmplitude = 0.0f;
        for (int i = startSample; i < endSample; i++) {
            maxAmplitude = qMax(maxAmplitude, samples[i]);
        }

        const int y = centerY - static_cast<int>(maxAmplitude * centerY);
        path.lineTo(x, y);
    }

    path.lineTo(width, centerY);
    path.closeSubpath();

    painter.setPen(Qt::NoPen);
    painter.setBrush(waveformColor_);
    painter.drawPath(path);
}

QImage WaveformGenerator::createSpectrogramImage(const QVector<float> &samples)
{
    if (samples.isEmpty()) {
        return QImage();
    }

    QVector<QVector<float>> spectrogramData = computeSpectrogram(samples);
    if (spectrogramData.isEmpty()) {
        return QImage();
    }

    const int width = spectrogramData.size();
    const int height = fftSize_ / 2; // Используем только реальные частоты

    QImage image(width, height, QImage::Format_ARGB32);
    image.fill(backgroundColor_);

    // Находим максимальную величину для нормализации
    float maxMagnitude = 0.0f;
    for (const auto &spectrum : spectrogramData) {
        for (float magnitude : spectrum) {
            maxMagnitude = qMax(maxMagnitude, magnitude);
        }
    }

    // Рисуем спектрограмму
    for (int x = 0; x < width; x++) {
        for (int y = 0; y < height; y++) {
            if (y < spectrogramData[x].size()) {
                float magnitude = spectrogramData[x][y];
                QColor color = getColorForFrequency(magnitude, maxMagnitude);
                image.setPixelColor(x, height - y - 1, color); // Переворачиваем по Y
            }
        }
    }

    return image;
}

QVector<QVector<float>> WaveformGenerator::computeSpectrogram(const QVector<float> &samples)
{
    QVector<QVector<float>> spectrogram;
    const int hopSize = fftSize_ / 4; // 75% overlap
    const int totalFrames = (samples.size() - fftSize_) / hopSize + 1;

    for (int i = 0; i < totalFrames; i++) {
        const int startIdx = i * hopSize;
        if (startIdx + fftSize_ > samples.size()) break;

        // Извлекаем фрейм и применяем оконную функцию
        QVector<float> frame(fftSize_);
        for (int j = 0; j < fftSize_; j++) {
            frame[j] = samples[startIdx + j];
        }
        applyWindowFunction(frame, 0);

        // Применяем FFT
        QVector<float> spectrum = applyFFT(frame);
        spectrogram.append(spectrum);
    }

    return spectrogram;
}

QVector<float> WaveformGenerator::applyFFT(const QVector<float> &input)
{
    const int n = input.size();
    std::valarray<std::complex<float>> data(n);

    // Копируем данные
    for (int i = 0; i < n; i++) {
        data[i] = std::complex<float>(input[i], 0.0f);
    }

    // Cooley-Tukey FFT
    for (int i = 1, j = 0; i < n; i++) {
        int bit = n >> 1;
        for (; j >= bit; bit >>= 1) {
            j -= bit;
        }
        j += bit;
        if (i < j) {
            std::swap(data[i], data[j]);
        }
    }

    for (int len = 2; len <= n; len <<= 1) {
        float angle = -2 * PI / len;
        std::complex<float> wlen(std::cos(angle), std::sin(angle));
        
        for (int i = 0; i < n; i += len) {
            std::complex<float> w(1);
            for (int j = 0; j < len/2; j++) {
                std::complex<float> u = data[i + j];
                std::complex<float> v = data[i + j + len/2] * w;
                data[i + j] = u + v;
                data[i + j + len/2] = u - v;
                w *= wlen;
            }
        }
    }

    // Вычисляем magnitudes (только первую половину)
    QVector<float> spectrum(n / 2);
    for (int i = 0; i < n / 2; i++) {
        spectrum[i] = std::abs(data[i]) / n;
    }

    return spectrum;
}

void WaveformGenerator::applyWindowFunction(QVector<float> &samples, int startIdx)
{
    // Hann window
    for (int i = 0; i < samples.size(); i++) {
        float window = 0.5f * (1 - std::cos(2 * PI * i / (samples.size() - 1)));
        samples[i] *= window;
    }
}

QVector<QPointF> WaveformGenerator::findClippingPoints(const QVector<float> &samples, int channelIndex) const
{
    QVector<QPointF> points;
    for (int i = 0; i < samples.size(); i++) {
        if (isClipping(samples[i])) {
            points.append(QPointF(i, channelIndex));
        }
    }
    return points;
}

bool WaveformGenerator::isClipping(float sample) const
{
    return std::abs(sample) > clipThreshold_;
}

QColor WaveformGenerator::getColorForAmplitude(float amplitude) const
{
    // Градиент от синего к красному для амплитуды
    int red = static_cast<int>(amplitude * 255);
    int blue = static_cast<int>((1 - amplitude) * 255);
    return QColor(red, 0, blue);
}

QColor WaveformGenerator::getColorForFrequency(float magnitude, float maxMagnitude) const
{
    if (maxMagnitude == 0) return Qt::black;

    float normalized = magnitude / maxMagnitude;
    normalized = qBound(0.0f, normalized, 1.0f);

    // Цветовая карта для спектрограммы (от синего к красному)
    if (normalized < 0.25) {
        return QColor(0, 0, static_cast<int>(normalized * 4 * 255));
    } else if (normalized < 0.5) {
        return QColor(0, static<int>((normalized - 0.25) * 4 * 255), 255);
    } else if (normalized < 0.75) {
        return QColor(static<int>((normalized - 0.5) * 4 * 255), 255, static<int>((0.75 - normalized) * 4 * 255));
    } else {
        return QColor(255, static<int>((1 - normalized) * 4 * 255), 0);
    }
}

bool WaveformGenerator::exportToSVG(const QString &filePath, const QImage &image) const
{
    QSvgGenerator generator;
    generator.setFileName(filePath);
    generator.setSize(image.size());
    generator.setViewBox(QRect(0, 0, image.width(), image.height()));
    generator.setTitle("Waveform");
    generator.setDescription("Waveform generated by FFmpeg and Qt");

    QPainter painter;
    if (!painter.begin(&generator)) {
        return false;
    }

    painter.drawImage(0, 0, image);
    painter.end();

    return true;
}
```

## Пример использования в QML:

```qml
import QtQuick 2.15
import QtQuick.Controls 2.15

Item {
    width: 800
    height: 600

    WaveformGenerator {
        id: waveformGenerator
        imageSize: Qt.size(800, 200)
        waveformColor: "blue"
        backgroundColor: "white"
        clipColor: "red"
        style: WaveformGenerator.StyleLines
        fftSize: 1024
        showClipping: true
        clipThreshold: 0.99

        onWaveformGenerated: {
            waveformRepeater.model = images
        }
        onSpectrogramGenerated: {
            spectrogramImage.source = spectrogramImage
        }
    }

    TabBar {
        id: tabBar
        width: parent.width
        
        TabButton { text: "Waveform" }
        TabButton { text: "Spectrogram" }
    }

    StackLayout {
        anchors.top: tabBar.bottom
        anchors.bottom: parent.bottom
        width: parent.width
        currentIndex: tabBar.currentIndex

        // Вкладка Waveform
        Column {
            Repeater {
                id: waveformRepeater
                delegate: Image {
                    source: modelData
                    width: parent.width
                    height: 200
                    fillMode: Image.PreserveAspectFit
                }
            }
        }

        // Вкладка Spectrogram
        Image {
            id: spectrogramImage
            width: parent.width
            height: 400
            fillMode: Image.PreserveAspectFit
        }
    }

    Button {
        anchors.bottom: parent.bottom
        anchors.right: parent.right
        text: "Export SVG"
        onClicked: {
            waveformGenerator.exportSVG("waveform.svg")
        }
    }
}
```

Теперь класс поддерживает:

1. **Экспорт в SVG** через QSvgGenerator
2. **Подсветку клиппинга** с настраиваемым порогом
3. **Спектрограмму** с реализацией FFT
4. **Цветовые градиенты** для визуализации
5. **Гибкие настройки** через свойства QML

Реализация FFT использует алгоритм Кули-Тьюки, а спектрограмма строится с перекрытием фреймов для плавности.
