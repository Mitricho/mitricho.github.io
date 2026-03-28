

# Vectorscope QQuickPaintedItem Component

Here's a professional-grade vectorscope component for Qt 6.10:

## vectorscope.h

```cpp
#ifndef VECTORSCOPE_H
#define VECTORSCOPE_H

#include <QQuickPaintedItem>
#include <QImage>
#include <QColor>
#include <QTimer>
#include <QElapsedTimer>
#include <QMutex>
#include <QFuture>
#include <QFutureWatcher>
#include <QtConcurrent>
#include <QPainter>
#include <QPainterPath>
#include <QFont>
#include <QtMath>
#include <QThread>

/**
 * @brief Professional Vectorscope display component for Qt Quick.
 *
 * Displays a color vectorscope showing the chrominance distribution of an image
 * in the CbCr (or UV) color plane, similar to professional video scopes found
 * in software like DaVinci Resolve, Adobe Premiere Pro (Lumetri), etc.
 *
 * Usage in QML:
 * @code
 * Vectorscope {
 *     width: 400
 *     height: 400
 *     sourceImage: myImageProvider.currentFrame
 *     qualityMode: Vectorscope.Performance  // or Vectorscope.Balanced, Vectorscope.Accuracy
 *     intensity: 1.5
 *     showGraticule: true
 *     showSkinToneLine: true
 *     backgroundColor: "#1a1a1a"
 * }
 * @endcode
 */
class Vectorscope : public QQuickPaintedItem
{
    Q_OBJECT
    QML_ELEMENT

    // --- Properties ---
    Q_PROPERTY(QImage sourceImage READ sourceImage WRITE setSourceImage NOTIFY sourceImageChanged)
    Q_PROPERTY(QualityMode qualityMode READ qualityMode WRITE setQualityMode NOTIFY qualityModeChanged)
    Q_PROPERTY(qreal intensity READ intensity WRITE setIntensity NOTIFY intensityChanged)
    Q_PROPERTY(bool showGraticule READ showGraticule WRITE setShowGraticule NOTIFY showGraticuleChanged)
    Q_PROPERTY(bool showSkinToneLine READ showSkinToneLine WRITE setShowSkinToneLine NOTIFY showSkinToneLineChanged)
    Q_PROPERTY(QColor backgroundColor READ backgroundColor WRITE setBackgroundColor NOTIFY backgroundColorChanged)
    Q_PROPERTY(qreal graticuleOpacity READ graticuleOpacity WRITE setGraticuleOpacity NOTIFY graticuleOpacityChanged)
    Q_PROPERTY(bool colorizePoints READ colorizePoints WRITE setColorizePoints NOTIFY colorizePointsChanged)
    Q_PROPERTY(int targetFps READ targetFps WRITE setTargetFps NOTIFY targetFpsChanged)
    Q_PROPERTY(bool clampSignal READ clampSignal WRITE setClampSignal NOTIFY clampSignalChanged)

public:
    /**
     * @brief Quality/performance trade-off modes.
     */
    enum QualityMode {
        Performance = 0, ///< Subsample heavily, suitable for 60fps+ playback
        Balanced    = 1, ///< Good balance for 30fps playback
        Accuracy    = 2  ///< Process all pixels, best for still analysis
    };
    Q_ENUM(QualityMode)

    explicit Vectorscope(QQuickItem *parent = nullptr);
    ~Vectorscope() override;

    // Property accessors
    QImage sourceImage() const;
    void setSourceImage(const QImage &image);

    QualityMode qualityMode() const;
    void setQualityMode(QualityMode mode);

    qreal intensity() const;
    void setIntensity(qreal intensity);

    bool showGraticule() const;
    void setShowGraticule(bool show);

    bool showSkinToneLine() const;
    void setShowSkinToneLine(bool show);

    QColor backgroundColor() const;
    void setBackgroundColor(const QColor &color);

    qreal graticuleOpacity() const;
    void setGraticuleOpacity(qreal opacity);

    bool colorizePoints() const;
    void setColorizePoints(bool colorize);

    int targetFps() const;
    void setTargetFps(int fps);

    bool clampSignal() const;
    void setClampSignal(bool clamp);

    void paint(QPainter *painter) override;

signals:
    void sourceImageChanged();
    void qualityModeChanged();
    void intensityChanged();
    void showGraticuleChanged();
    void showSkinToneLineChanged();
    void backgroundColorChanged();
    void graticuleOpacityChanged();
    void colorizePointsChanged();
    void targetFpsChanged();
    void clampSignalChanged();

private slots:
    void onComputationFinished();

private:
    // --- Internal types ---

    /**
     * @brief Color target definition for graticule markers (R, G, B, Cy, Mg, Yl).
     */
    struct ColorTarget {
        QString label;
        qreal cb;       // Normalized Cb [-0.5, 0.5]
        qreal cr;       // Normalized Cr [-0.5, 0.5]
        QColor color;
        qreal cb75;     // 75% bar position
        qreal cr75;     // 75% bar position
    };

    /**
     * @brief Accumulated vectorscope data for the scatter buffer.
     * Uses a 2D histogram approach for efficient rendering.
     */
    struct ScopeData {
        std::vector<uint32_t> histogram;  // 2D histogram [y * size + x]
        std::vector<uint32_t> colorAccumR; // Accumulated R for colorized mode
        std::vector<uint32_t> colorAccumG; // Accumulated G
        std::vector<uint32_t> colorAccumB; // Accumulated B
        int size = 0;
        uint32_t maxCount = 0;
        bool valid = false;
    };

    // --- Methods ---
    void initColorTargets();
    void computeScopeAsync(const QImage &image);
    ScopeData computeScopeData(const QImage &image, int scopeSize) const;
    int getSubsampleStep(int imageWidth, int imageHeight) const;

    void renderBackground(QPainter *painter, int cx, int cy, int radius) const;
    void renderScopeImage(QPainter *painter, int cx, int cy, int radius, const ScopeData &data) const;
    void renderGraticule(QPainter *painter, int cx, int cy, int radius) const;
    void renderSkinToneLine(QPainter *painter, int cx, int cy, int radius) const;
    void renderColorTargets(QPainter *painter, int cx, int cy, int radius) const;

    QPointF cbcrToPoint(qreal cb, qreal cr, int cx, int cy, int radius) const;

    // --- Data members ---
    QImage m_sourceImage;
    QualityMode m_qualityMode = Balanced;
    qreal m_intensity = 1.5;
    bool m_showGraticule = true;
    bool m_showSkinToneLine = true;
    QColor m_backgroundColor = QColor(0x1a, 0x1a, 0x1a);
    qreal m_graticuleOpacity = 0.4;
    bool m_colorizePoints = true;
    int m_targetFps = 30;
    bool m_clampSignal = true;

    // Color targets for graticule
    QVector<ColorTarget> m_colorTargets;

    // Async computation
    QFuture<ScopeData> m_computeFuture;
    QFutureWatcher<ScopeData> m_computeWatcher;
    bool m_computationPending = false;
    bool m_newImageWaiting = false;

    // Cached scope data
    ScopeData m_currentScopeData;
    QImage m_cachedScopeImage;   // Pre-rendered scope scatter image
    int m_lastScopeRenderSize = 0;

    // Frame throttling
    QElapsedTimer m_frameTimer;
    qint64 m_lastUpdateTime = 0;

    // Thread safety
    mutable QMutex m_dataMutex;

    // Cached graticule image (re-rendered only on resize)
    mutable QImage m_cachedGraticule;
    mutable int m_cachedGraticuleRadius = 0;
    mutable bool m_cachedGraticuleShowSkin = false;
};

#endif // VECTORSCOPE_H
```

## vectorscope.cpp

```cpp
#include "vectorscope.h"

#include <QPainter>
#include <QPainterPath>
#include <QtMath>
#include <QSGNode>
#include <algorithm>
#include <cmath>
#include <cstring>

// ============================================================================
// BT.709 RGB to YCbCr conversion constants
// ============================================================================
// Y  =  0.2126*R + 0.7152*G + 0.0722*B
// Cb = -0.1146*R - 0.3854*G + 0.5000*B
// Cr =  0.5000*R - 0.4542*G - 0.0458*B
// (Cb and Cr normalized to [-0.5, 0.5] for 8-bit [0,255] input)

static constexpr double kCbR = -0.1146;
static constexpr double kCbG = -0.3854;
static constexpr double kCbB =  0.5000;
static constexpr double kCrR =  0.5000;
static constexpr double kCrG = -0.4542;
static constexpr double kCrB = -0.0458;

// ============================================================================
// Constructor / Destructor
// ============================================================================

Vectorscope::Vectorscope(QQuickItem *parent)
    : QQuickPaintedItem(parent)
{
    setRenderTarget(QQuickPaintedItem::FramebufferObject);
    setAntialiasing(true);

    initColorTargets();

    connect(&m_computeWatcher, &QFutureWatcher<ScopeData>::finished,
            this, &Vectorscope::onComputationFinished);

    m_frameTimer.start();
}

Vectorscope::~Vectorscope()
{
    if (m_computeFuture.isRunning()) {
        m_computeFuture.waitForFinished();
    }
}

// ============================================================================
// Color target initialization
// ============================================================================

void Vectorscope::initColorTargets()
{
    // BT.709 100% color bar positions in CbCr space
    // For each primary/secondary color at 100% and 75% saturation
    auto computeCbCr = [](int r, int g, int b) -> std::pair<double, double> {
        double rd = r / 255.0;
        double gd = g / 255.0;
        double bd = b / 255.0;
        double cb = kCbR * rd + kCbG * gd + kCbB * bd;
        double cr = kCrR * rd + kCrG * gd + kCrB * bd;
        return {cb, cr};
    };

    struct ColorDef {
        QString label;
        int r100, g100, b100; // 100% color bar
        QColor displayColor;
    };

    QVector<ColorDef> defs = {
        {"R",  255, 0,   0,   QColor(255, 50,  50)},
        {"G",  0,   255, 0,   QColor(50,  200, 50)},
        {"B",  0,   0,   255, QColor(80,  80,  255)},
        {"Cy", 0,   255, 255, QColor(0,   200, 200)},
        {"Mg", 255, 0,   255, QColor(200, 50,  200)},
        {"Yl", 255, 255, 0,   QColor(200, 200, 0)},
    };

    m_colorTargets.clear();
    m_colorTargets.reserve(defs.size());

    for (const auto &def : defs) {
        auto [cb100, cr100] = computeCbCr(def.r100, def.g100, def.b100);

        // 75% bars: scale colors to 75%
        int r75 = static_cast<int>(def.r100 * 0.75);
        int g75 = static_cast<int>(def.g100 * 0.75);
        int b75 = static_cast<int>(def.b100 * 0.75);
        auto [cb75, cr75] = computeCbCr(r75, g75, b75);

        ColorTarget target;
        target.label = def.label;
        target.cb = cb100;
        target.cr = cr100;
        target.color = def.displayColor;
        target.cb75 = cb75;
        target.cr75 = cr75;
        m_colorTargets.append(target);
    }
}

// ============================================================================
// Property implementations
// ============================================================================

QImage Vectorscope::sourceImage() const
{
    return m_sourceImage;
}

void Vectorscope::setSourceImage(const QImage &image)
{
    m_sourceImage = image;
    emit sourceImageChanged();

    // Frame throttling
    qint64 now = m_frameTimer.elapsed();
    qint64 minInterval = (m_targetFps > 0) ? (1000 / m_targetFps) : 0;

    if (now - m_lastUpdateTime < minInterval) {
        m_newImageWaiting = true;
        // Schedule a delayed update
        QTimer::singleShot(static_cast<int>(minInterval - (now - m_lastUpdateTime)),
                           this, [this]() {
            if (m_newImageWaiting && !m_computationPending) {
                m_newImageWaiting = false;
                computeScopeAsync(m_sourceImage);
            }
        });
        return;
    }

    if (!m_computationPending) {
        computeScopeAsync(image);
    } else {
        m_newImageWaiting = true;
    }
}

Vectorscope::QualityMode Vectorscope::qualityMode() const
{
    return m_qualityMode;
}

void Vectorscope::setQualityMode(QualityMode mode)
{
    if (m_qualityMode != mode) {
        m_qualityMode = mode;
        emit qualityModeChanged();
        if (!m_sourceImage.isNull()) {
            computeScopeAsync(m_sourceImage);
        }
    }
}

qreal Vectorscope::intensity() const
{
    return m_intensity;
}

void Vectorscope::setIntensity(qreal intensity)
{
    intensity = qBound(0.1, intensity, 10.0);
    if (!qFuzzyCompare(m_intensity, intensity)) {
        m_intensity = intensity;
        emit intensityChanged();
        m_cachedScopeImage = QImage(); // Invalidate cache
        update();
    }
}

bool Vectorscope::showGraticule() const
{
    return m_showGraticule;
}

void Vectorscope::setShowGraticule(bool show)
{
    if (m_showGraticule != show) {
        m_showGraticule = show;
        emit showGraticuleChanged();
        m_cachedGraticuleRadius = 0; // Invalidate graticule cache
        update();
    }
}

bool Vectorscope::showSkinToneLine() const
{
    return m_showSkinToneLine;
}

void Vectorscope::setShowSkinToneLine(bool show)
{
    if (m_showSkinToneLine != show) {
        m_showSkinToneLine = show;
        emit showSkinToneLineChanged();
        m_cachedGraticuleRadius = 0;
        update();
    }
}

QColor Vectorscope::backgroundColor() const
{
    return m_backgroundColor;
}

void Vectorscope::setBackgroundColor(const QColor &color)
{
    if (m_backgroundColor != color) {
        m_backgroundColor = color;
        emit backgroundColorChanged();
        m_cachedGraticuleRadius = 0;
        update();
    }
}

qreal Vectorscope::graticuleOpacity() const
{
    return m_graticuleOpacity;
}

void Vectorscope::setGraticuleOpacity(qreal opacity)
{
    opacity = qBound(0.0, opacity, 1.0);
    if (!qFuzzyCompare(m_graticuleOpacity, opacity)) {
        m_graticuleOpacity = opacity;
        emit graticuleOpacityChanged();
        m_cachedGraticuleRadius = 0;
        update();
    }
}

bool Vectorscope::colorizePoints() const
{
    return m_colorizePoints;
}

void Vectorscope::setColorizePoints(bool colorize)
{
    if (m_colorizePoints != colorize) {
        m_colorizePoints = colorize;
        emit colorizePointsChanged();
        m_cachedScopeImage = QImage();
        if (!m_sourceImage.isNull()) {
            computeScopeAsync(m_sourceImage);
        }
    }
}

int Vectorscope::targetFps() const
{
    return m_targetFps;
}

void Vectorscope::setTargetFps(int fps)
{
    fps = qBound(1, fps, 120);
    if (m_targetFps != fps) {
        m_targetFps = fps;
        emit targetFpsChanged();
    }
}

bool Vectorscope::clampSignal() const
{
    return m_clampSignal;
}

void Vectorscope::setClampSignal(bool clamp)
{
    if (m_clampSignal != clamp) {
        m_clampSignal = clamp;
        emit clampSignalChanged();
        if (!m_sourceImage.isNull()) {
            computeScopeAsync(m_sourceImage);
        }
    }
}

// ============================================================================
// Subsampling step based on quality mode and image resolution
// ============================================================================

int Vectorscope::getSubsampleStep(int imageWidth, int imageHeight) const
{
    int totalPixels = imageWidth * imageHeight;

    switch (m_qualityMode) {
    case Performance:
        // Target ~50k-100k samples for real-time performance
        if (totalPixels > 2000000) return 8;       // > 2MP
        if (totalPixels > 500000)  return 4;        // > 0.5MP
        return 2;

    case Balanced:
        // Target ~200k-500k samples
        if (totalPixels > 8000000) return 6;        // > 8MP (4K)
        if (totalPixels > 2000000) return 4;        // > 2MP (1080p)
        if (totalPixels > 500000)  return 2;        // > 0.5MP
        return 1;

    case Accuracy:
        // Process all or nearly all pixels
        if (totalPixels > 33000000) return 4;       // > 8K
        if (totalPixels > 8000000)  return 2;       // > 4K
        return 1;
    }
    return 2;
}

// ============================================================================
// Async computation
// ============================================================================

void Vectorscope::computeScopeAsync(const QImage &image)
{
    if (image.isNull()) return;

    m_computationPending = true;
    m_lastUpdateTime = m_frameTimer.elapsed();

    // Determine scope buffer size based on display size
    int displaySize = static_cast<int>(qMin(width(), height()));
    int scopeSize = qBound(128, displaySize, 1024);

    // Make a deep copy for the worker thread
    QImage imageCopy = image.copy();

    m_computeFuture = QtConcurrent::run([this, imageCopy, scopeSize]() {
        return computeScopeData(imageCopy, scopeSize);
    });
    m_computeWatcher.setFuture(m_computeFuture);
}

void Vectorscope::onComputationFinished()
{
    {
        QMutexLocker lock(&m_dataMutex);
        m_currentScopeData = m_computeWatcher.result();
        m_cachedScopeImage = QImage(); // Invalidate rendered cache
    }

    m_computationPending = false;
    update();

    // If a new image arrived while we were computing, process it now
    if (m_newImageWaiting) {
        m_newImageWaiting = false;
        if (!m_sourceImage.isNull()) {
            computeScopeAsync(m_sourceImage);
        }
    }
}

// ============================================================================
// Core computation: build 2D CbCr histogram
// ============================================================================

Vectorscope::ScopeData Vectorscope::computeScopeData(const QImage &image, int scopeSize) const
{
    ScopeData data;
    data.size = scopeSize;
    data.histogram.resize(static_cast<size_t>(scopeSize * scopeSize), 0);
    data.valid = false;

    bool colorize = m_colorizePoints;
    if (colorize) {
        data.colorAccumR.resize(static_cast<size_t>(scopeSize * scopeSize), 0);
        data.colorAccumG.resize(static_cast<size_t>(scopeSize * scopeSize), 0);
        data.colorAccumB.resize(static_cast<size_t>(scopeSize * scopeSize), 0);
    }

    // Convert to a format we can process efficiently
    QImage converted = image;
    if (converted.format() != QImage::Format_RGB32 &&
        converted.format() != QImage::Format_ARGB32 &&
        converted.format() != QImage::Format_ARGB32_Premultiplied) {
        converted = converted.convertToFormat(QImage::Format_ARGB32);
    }

    if (converted.format() == QImage::Format_ARGB32_Premultiplied) {
        converted = converted.convertToFormat(QImage::Format_ARGB32);
    }

    int w = converted.width();
    int h = converted.height();
    int step = getSubsampleStep(w, h);
    bool clamp = m_clampSignal;

    // Precompute LUT for RGB to CbCr mapping
    // Cb = kCbR*R + kCbG*G + kCbB*B  -> range [-0.5, 0.5]
    // Cr = kCrR*R + kCrG*G + kCrB*B  -> range [-0.5, 0.5]
    // Map to scope coords: x = (Cb + 0.5) * (scopeSize-1), y = (0.5 - Cr) * (scopeSize-1)
    // Note: Cr is inverted for display (positive Cr = up in traditional vectorscope,
    // but we use Y-down screen coords, and the standard vectorscope has R at top-right)

    // Actually, standard vectorscope layout:
    //   The vectorscope maps Cb to X axis (positive right) and Cr to Y axis (positive up).
    //   In screen coordinates, Y is inverted, so Cr maps to negative Y direction.

    const double halfSize = (scopeSize - 1) * 0.5;
    // Scale factor: the full range [-0.5, 0.5] maps to the radius of the circle.
    // The circle fills the scopeSize, so radius = halfSize.
    // Point at (cb, cr) maps to screen: x = cx + cb * 2 * halfSize, y = cy - cr * 2 * halfSize
    // Since halfSize = (scopeSize-1)/2, and cx = cy = halfSize:
    // x = halfSize + cb * (scopeSize - 1)
    // y = halfSize - cr * (scopeSize - 1)

    const double scale = static_cast<double>(scopeSize - 1);

    // Build precomputed tables for each channel value [0..255]
    // Cb contribution from R, G, B
    std::array<double, 256> cbFromR, cbFromG, cbFromB;
    std::array<double, 256> crFromR, crFromG, crFromB;
    for (int i = 0; i < 256; ++i) {
        double v = i / 255.0;
        cbFromR[i] = kCbR * v;
        cbFromG[i] = kCbG * v;
        cbFromB[i] = kCbB * v;
        crFromR[i] = kCrR * v;
        crFromG[i] = kCrG * v;
        crFromB[i] = kCrB * v;
    }

    uint32_t maxCount = 0;

    for (int row = 0; row < h; row += step) {
        const QRgb *scanline = reinterpret_cast<const QRgb *>(converted.constScanLine(row));

        for (int col = 0; col < w; col += step) {
            QRgb pixel = scanline[col];
            int r = qRed(pixel);
            int g = qGreen(pixel);
            int b = qBlue(pixel);

            double cb = cbFromR[r] + cbFromG[g] + cbFromB[b]; // [-0.5, 0.5]
            double cr = crFromR[r] + crFromG[g] + crFromB[b]; // [-0.5, 0.5]

            // Map to scope buffer coordinates
            // Cb -> X (positive right), Cr -> Y (positive up, but screen is Y-down)
            int sx = static_cast<int>((cb + 0.5) * scale + 0.5);
            int sy = static_cast<int>((0.5 - cr) * scale + 0.5);

            if (clamp) {
                // Check if within the unit circle
                double dist = cb * cb + cr * cr;
                if (dist > 0.25) continue; // Outside unit circle (radius 0.5)
            }

            // Bounds check
            if (sx < 0 || sx >= scopeSize || sy < 0 || sy >= scopeSize)
                continue;

            size_t idx = static_cast<size_t>(sy * scopeSize + sx);
            uint32_t count = ++data.histogram[idx];
            if (count > maxCount) maxCount = count;

            if (colorize) {
                data.colorAccumR[idx] += static_cast<uint32_t>(r);
                data.colorAccumG[idx] += static_cast<uint32_t>(g);
                data.colorAccumB[idx] += static_cast<uint32_t>(b);
            }
        }
    }

    data.maxCount = maxCount;
    data.valid = (maxCount > 0);
    return data;
}

// ============================================================================
// Coordinate conversion
// ============================================================================

QPointF Vectorscope::cbcrToPoint(qreal cb, qreal cr, int cx, int cy, int radius) const
{
    // cb in [-0.5, 0.5], cr in [-0.5, 0.5]
    // Map to screen coordinates within the circle
    double x = cx + cb * 2.0 * radius;
    double y = cy - cr * 2.0 * radius;  // Y inverted for screen
    return QPointF(x, y);
}

// ============================================================================
// Paint method
// ============================================================================

void Vectorscope::paint(QPainter *painter)
{
    if (!painter) return;

    int w = static_cast<int>(width());
    int h = static_cast<int>(height());
    if (w <= 0 || h <= 0) return;

    painter->setRenderHint(QPainter::Antialiasing);
    painter->setRenderHint(QPainter::SmoothPixmapTransform);

    // Calculate scope geometry
    int margin = 8;
    int diameter = qMin(w, h) - 2 * margin;
    if (diameter <= 0) return;

    int radius = diameter / 2;
    int cx = w / 2;
    int cy = h / 2;

    // 1. Fill background
    painter->fillRect(0, 0, w, h, m_backgroundColor);

    // 2. Render scope circle background with subtle color wheel tint
    renderBackground(painter, cx, cy, radius);

    // 3. Render scope data (the scatter/histogram plot)
    {
        QMutexLocker lock(&m_dataMutex);
        if (m_currentScopeData.valid) {
            renderScopeImage(painter, cx, cy, radius, m_currentScopeData);
        }
    }

    // 4. Render graticule overlay
    if (m_showGraticule) {
        renderGraticule(painter, cx, cy, radius);
    }
}

// ============================================================================
// Render the dark circular background with subtle color tinting
// ============================================================================

void Vectorscope::renderBackground(QPainter *painter, int cx, int cy, int radius) const
{
    // Create a circular clip
    QPainterPath circlePath;
    circlePath.addEllipse(QPointF(cx, cy), radius, radius);
    painter->save();
    painter->setClipPath(circlePath);

    // Dark background
    painter->fillRect(cx - radius, cy - radius, radius * 2, radius * 2,
                      QColor(10, 10, 12));

    // Subtle radial color wheel gradient for professional look
    // We paint a very subtle color-tinted overlay to hint at the color directions
    QImage colorWheel(radius * 2, radius * 2, QImage::Format_ARGB32_Premultiplied);
    colorWheel.fill(Qt::transparent);

    int d = radius * 2;
    for (int y = 0; y < d; ++y) {
        QRgb *line = reinterpret_cast<QRgb *>(colorWheel.scanLine(y));
        for (int x = 0; x < d; ++x) {
            double dx = x - radius;
            double dy = y - radius;
            double dist = std::sqrt(dx * dx + dy * dy);

            if (dist <= radius) {
                // Angle in the CbCr plane
                double angle = std::atan2(-dy, dx); // -dy because Cr is up

                // Convert angle to hue (approximate the color wheel)
                // Cb axis (right) = Blue direction, Cr axis (up) = Red direction
                // This is a simplified mapping
                double hue = std::fmod(angle * 180.0 / M_PI + 360.0, 360.0);

                // Distance from center normalized
                double normDist = dist / radius;

                // Very subtle coloring - only at edges
                int alpha = static_cast<int>(normDist * normDist * 18); // Very subtle

                QColor c = QColor::fromHsvF(hue / 360.0, 0.6, 0.3);
                line[x] = qRgba(c.red(), c.green(), c.blue(), alpha);
            }
        }
    }

    painter->drawImage(cx - radius, cy - radius, colorWheel);
    painter->restore();
}

// ============================================================================
// Render the scope data as a scatter/heat map
// ============================================================================

void Vectorscope::renderScopeImage(QPainter *painter, int cx, int cy, int radius,
                                     const ScopeData &data) const
{
    if (!data.valid || data.size <= 0) return;

    int scopeSize = data.size;
    int diameter = radius * 2;

    // Check if we need to re-render the scope image
    // (We always re-render when data changes; the cache is invalidated in onComputationFinished)
    if (m_cachedScopeImage.isNull() || m_lastScopeRenderSize != diameter) {

        QImage scopeImg(diameter, diameter, QImage::Format_ARGB32_Premultiplied);
        scopeImg.fill(Qt::transparent);

        // Log-scale mapping for the histogram
        double logMax = std::log1p(static_cast<double>(data.maxCount));
        double intensityScale = m_intensity;
        double radiusSq = static_cast<double>(radius) * radius;

        bool colorize = m_colorizePoints && !data.colorAccumR.empty();

        for (int sy = 0; sy < scopeSize; ++sy) {
            for (int sx = 0; sx < scopeSize; ++sx) {
                size_t idx = static_cast<size_t>(sy * scopeSize + sx);
                uint32_t count = data.histogram[idx];
                if (count == 0) continue;

                // Map scope buffer position to display position
                double srcX = (static_cast<double>(sx) / (scopeSize - 1)) * (diameter - 1);
                double srcY = (static_cast<double>(sy) / (scopeSize - 1)) * (diameter - 1);

                // Check within circle
                double dx = srcX - radius;
                double dy = srcY - radius;
                if (dx * dx + dy * dy > radiusSq) continue;

                int px = static_cast<int>(srcX);
                int py = static_cast<int>(srcY);
                if (px < 0 || px >= diameter || py < 0 || py >= diameter) continue;

                // Compute brightness from histogram count (log scale)
                double logVal = std::log1p(static_cast<double>(count));
                double brightness = (logVal / logMax) * intensityScale;
                brightness = qBound(0.0, brightness, 1.0);

                // Apply gamma curve for better visual separation
                brightness = std::pow(brightness, 0.6);

                int alpha = static_cast<int>(brightness * 255.0);

                QRgb color;
                if (colorize && count > 0) {
                    // Average color of all pixels that mapped to this bin
                    int avgR = static_cast<int>(data.colorAccumR[idx] / count);
                    int avgG = static_cast<int>(data.colorAccumG[idx] / count);
                    int avgB = static_cast<int>(data.colorAccumB[idx] / count);

                    // Boost saturation and brightness for visibility
                    double maxC = qMax({avgR, avgG, avgB}) / 255.0;
                    double minC = qMin({avgR, avgG, avgB}) / 255.0;
                    double sat = (maxC > 0) ? (maxC - minC) / maxC : 0;

                    if (sat < 0.05) {
                        // Near-neutral colors render as white
                        color = qRgba(255, 255, 255, alpha);
                    } else {
                        // Boost the color for visibility on dark background
                        double boost = qMin(2.0, 1.0 / maxC);
                        int cr = qBound(0, static_cast<int>(avgR * boost), 255);
                        int cg = qBound(0, static_cast<int>(avgG * boost), 255);
                        int cb = qBound(0, static_cast<int>(avgB * boost), 255);

                        // Blend with white based on brightness for glow effect
                        double whiteBlend = brightness * brightness;
                        cr = static_cast<int>(cr * (1.0 - whiteBlend) + 255 * whiteBlend);
                        cg = static_cast<int>(cg * (1.0 - whiteBlend) + 255 * whiteBlend);
                        cb = static_cast<int>(cb * (1.0 - whiteBlend) + 255 * whiteBlend);

                        color = qRgba(qBound(0, cr, 255),
                                      qBound(0, cg, 255),
                                      qBound(0, cb, 255),
                                      alpha);
                    }
                } else {
                    // Monochrome white/green phosphor look
                    color = qRgba(255, 255, 255, alpha);
                }

                // Write pixel with additive-like blending by using premultiplied alpha
                QRgb *destLine = reinterpret_cast<QRgb *>(scopeImg.scanLine(py));
                QRgb existing = destLine[px];

                // Simple additive blending
                int er = qRed(existing);
                int eg = qGreen(existing);
                int eb = qBlue(existing);
                int ea = qAlpha(existing);

                int nr = qMin(255, er + (qRed(color) * alpha / 255));
                int ng = qMin(255, eg + (qGreen(color) * alpha / 255));
                int nb = qMin(255, eb + (qBlue(color) * alpha / 255));
                int na = qMin(255, ea + alpha);

                destLine[px] = qRgba(nr, ng, nb, na);
            }
        }

        // Apply a slight gaussian-like blur for the "glow" effect (simple 3x3 box blur)
        QImage blurred(diameter, diameter, QImage::Format_ARGB32_Premultiplied);
        blurred.fill(Qt::transparent);

        for (int y = 1; y < diameter - 1; ++y) {
            const QRgb *prevLine = reinterpret_cast<const QRgb *>(scopeImg.constScanLine(y - 1));
            const QRgb *currLine = reinterpret_cast<const QRgb *>(scopeImg.constScanLine(y));
            const QRgb *nextLine = reinterpret_cast<const QRgb *>(scopeImg.constScanLine(y + 1));
            QRgb *destLine = reinterpret_cast<QRgb *>(blurred.scanLine(y));

            for (int x = 1; x < diameter - 1; ++x) {
                // 3x3 kernel with center weight
                int sumR = 0, sumG = 0, sumB = 0, sumA = 0;

                // Center pixel (weight 4)
                sumR += qRed(currLine[x]) * 4;
                sumG += qGreen(currLine[x]) * 4;
                sumB += qBlue(currLine[x]) * 4;
                sumA += qAlpha(currLine[x]) * 4;

                // Adjacent pixels (weight 2)
                sumR += qRed(currLine[x-1]) * 2 + qRed(currLine[x+1]) * 2;
                sumG += qGreen(currLine[x-1]) * 2 + qGreen(currLine[x+1]) * 2;
                sumB += qBlue(currLine[x-1]) * 2 + qBlue(currLine[x+1]) * 2;
                sumA += qAlpha(currLine[x-1]) * 2 + qAlpha(currLine[x+1]) * 2;

                sumR += qRed(prevLine[x]) * 2 + qRed(nextLine[x]) * 2;
                sumG += qGreen(prevLine[x]) * 2 + qGreen(nextLine[x]) * 2;
                sumB += qBlue(prevLine[x]) * 2 + qBlue(nextLine[x]) * 2;
                sumA += qAlpha(prevLine[x]) * 2 + qAlpha(nextLine[x]) * 2;

                // Diagonal pixels (weight 1)
                sumR += qRed(prevLine[x-1]) + qRed(prevLine[x+1]);
                sumG += qGreen(prevLine[x-1]) + qGreen(prevLine[x+1]);
                sumB += qBlue(prevLine[x-1]) + qBlue(prevLine[x+1]);
                sumA += qAlpha(prevLine[x-1]) + qAlpha(prevLine[x+1]);

                sumR += qRed(nextLine[x-1]) + qRed(nextLine[x+1]);
                sumG += qGreen(nextLine[x-1]) + qGreen(nextLine[x+1]);
                sumB += qBlue(nextLine[x-1]) + qBlue(nextLine[x+1]);
                sumA += qAlpha(nextLine[x-1]) + qAlpha(nextLine[x+1]);

                // Total weight = 4 + 4*2 + 4*1 = 16
                destLine[x] = qRgba(sumR / 16, sumG / 16, sumB / 16, sumA / 16);
            }
        }

        // Composite: original sharp + blurred glow
        QImage finalImg(diameter, diameter, QImage::Format_ARGB32_Premultiplied);
        finalImg.fill(Qt::transparent);

        for (int y = 0; y < diameter; ++y) {
            const QRgb *sharpLine = reinterpret_cast<const QRgb *>(scopeImg.constScanLine(y));
            const QRgb *blurLine = reinterpret_cast<const QRgb *>(blurred.constScanLine(y));
            QRgb *destLine = reinterpret_cast<QRgb *>(finalImg.scanLine(y));

            for (int x = 0; x < diameter; ++x) {
                // Additive composite: sharp + 0.5 * blur (for glow)
                int r = qMin(255, qRed(sharpLine[x]) + qRed(blurLine[x]) / 2);
                int g = qMin(255, qGreen(sharpLine[x]) + qGreen(blurLine[x]) / 2);
                int b = qMin(255, qBlue(sharpLine[x]) + qBlue(blurLine[x]) / 2);
                int a = qMin(255, qAlpha(sharpLine[x]) + qAlpha(blurLine[x]) / 2);
                destLine[x] = qRgba(r, g, b, a);
            }
        }

        // Cast away const for caching (this is a render cache, logically const)
        const_cast<Vectorscope*>(this)->m_cachedScopeImage = finalImg;
        const_cast<Vectorscope*>(this)->m_lastScopeRenderSize = diameter;
    }

    // Draw the cached scope image
    painter->save();
    QPainterPath clipPath;
    clipPath.addEllipse(QPointF(cx, cy), radius, radius);
    painter->setClipPath(clipPath);
    painter->drawImage(cx - radius, cy - radius, m_cachedScopeImage);
    painter->restore();
}

// ============================================================================
// Render graticule (grid, color targets, labels)
// ============================================================================

void Vectorscope::renderGraticule(QPainter *painter, int cx, int cy, int radius) const
{
    // Check if we can use cached graticule
    if (!m_cachedGraticule.isNull() &&
        m_cachedGraticuleRadius == radius &&
        m_cachedGraticuleShowSkin == m_showSkinToneLine) {
        // Draw cached graticule centered
        int gratW = m_cachedGraticule.width();
        int gratH = m_cachedGraticule.height();
        painter->drawImage(cx - gratW / 2, cy - gratH / 2, m_cachedGraticule);
        return;
    }

    // Render graticule to cache image
    int diameter = radius * 2 + 40; // Extra space for labels
    QImage gratImg(diameter + 40, diameter + 40, QImage::Format_ARGB32_Premultiplied);
    gratImg.fill(Qt::transparent);

    QPainter gp(&gratImg);
    gp.setRenderHint(QPainter::Antialiasing);

    int gcx = gratImg.width() / 2;
    int gcy = gratImg.height() / 2;

    QColor lineColor(180, 180, 180, static_cast<int>(m_graticuleOpacity * 100));
    QColor circleColor(120, 120, 120, static_cast<int>(m_graticuleOpacity * 80));

    // Outer circle
    QPen circlePen(circleColor, 1.0);
    gp.setPen(circlePen);
    gp.drawEllipse(QPointF(gcx, gcy), radius, radius);

    // Inner circles (25%, 50%, 75%)
    QPen innerCirclePen(circleColor);
    innerCirclePen.setWidthF(0.5);
    innerCirclePen.setStyle(Qt::DotLine);
    gp.setPen(innerCirclePen);
    gp.drawEllipse(QPointF(gcx, gcy), radius * 0.75, radius * 0.75);
    gp.drawEllipse(QPointF(gcx, gcy), radius * 0.50, radius * 0.50);
    gp.drawEllipse(QPointF(gcx, gcy), radius * 0.25, radius * 0.25);

    // Cross-hairs (vertical and horizontal axes through center)
    QPen crossPen(lineColor);
    crossPen.setWidthF(0.5);
    gp.setPen(crossPen);
    gp.drawLine(QPointF(gcx - radius, gcy), QPointF(gcx + radius, gcy));
    gp.drawLine(QPointF(gcx, gcy - radius), QPointF(gcx, gcy + radius));

    // Draw lines connecting color targets (hexagonal pattern)
    QPen hexPen(lineColor);
    hexPen.setWidthF(0.5);
    gp.setPen(hexPen);

    // Connect 75% targets in order: R -> Yl -> G -> Cy -> B -> Mg -> R
    // Order in m_colorTargets: R(0), G(1), B(2), Cy(3), Mg(4), Yl(5)
    int targetOrder[] = {0, 5, 1, 3, 2, 4}; // R, Yl, G, Cy, B, Mg
    for (int i = 0; i < 6; ++i) {
        int curr = targetOrder[i];
        int next = targetOrder[(i + 1) % 6];
        QPointF p1 = cbcrToPoint(m_colorTargets[curr].cb75, m_colorTargets[curr].cr75,
                                  gcx, gcy, radius);
        QPointF p2 = cbcrToPoint(m_colorTargets[next].cb75, m_colorTargets[next].cr75,
                                  gcx, gcy, radius);
        gp.drawLine(p1, p2);
    }

    // Draw lines from center to each 100% target
    QPen radialPen(lineColor);
    radialPen.setWidthF(0.5);
    gp.setPen(radialPen);
    for (const auto &target : m_colorTargets) {
        QPointF p = cbcrToPoint(target.cb, target.cr, gcx, gcy, radius);
        gp.drawLine(QPointF(gcx, gcy), p);
    }

    // Draw I and Q axis labels
    // Q axis is at 33 degrees, I axis at 123 degrees from positive Cb axis
    // Actually, I is at angle 123° from Cb axis (33° from Cr axis)
    // Q is at 33° from Cb axis

    QFont labelFont;
    labelFont.setPixelSize(qMax(10, radius / 18));
    labelFont.setBold(false);
    gp.setFont(labelFont);

    // Q and -I markers on the periphery
    double qAngle = 33.0 * M_PI / 180.0;   // Q axis angle from positive Cb (X) axis
    double iAngle = 123.0 * M_PI / 180.0;  // I axis angle

    QColor axisFontColor(180, 180, 180, static_cast<int>(m_graticuleOpacity * 200));
    gp.setPen(axisFontColor);

    // Q label
    {
        double qx = gcx + std::cos(qAngle) * (radius + 12);
        double qy = gcy - std::sin(qAngle) * (radius + 12);
        gp.drawText(QRectF(qx - 10, qy - 8, 20, 16), Qt::AlignCenter, "Q");
    }

    // -I label (opposite of I axis)
    {
        double ix = gcx + std::cos(iAngle - M_PI) * (radius + 12);
        double iy = gcy - std::sin(iAngle - M_PI) * (radius + 12);
        gp.drawText(QRectF(ix - 10, iy - 8, 20, 16), Qt::AlignCenter, "-i");
    }

    // Skin tone line
    if (m_showSkinToneLine) {
        renderSkinToneLine(&gp, gcx, gcy, radius);
    }

    // Color targets (boxes and labels)
    renderColorTargets(&gp, gcx, gcy, radius);

    gp.end();

    // Cache the graticule
    m_cachedGraticule = gratImg;
    m_cachedGraticuleRadius = radius;
    m_cachedGraticuleShowSkin = m_showSkinToneLine;

    // Draw it
    painter->drawImage(cx - gratImg.width() / 2, cy - gratImg.height() / 2, gratImg);
}

// ============================================================================
// Render skin tone line (the "I" line at ~123° or the traditional skin tone
// vector at approximately 147° in the CbCr plane)
// ============================================================================

void Vectorscope::renderSkinToneLine(QPainter *painter, int cx, int cy, int radius) const
{
    // The skin tone line in vectorscope corresponds to the direction where
    // all human skin tones (regardless of race) fall.
    // In the YCbCr (BT.709) color space, this is approximately at the angle
    // corresponding to Cb ≈ -0.1, Cr ≈ +0.15 (normalized direction).
    // This roughly corresponds to about 123° from the positive Cb axis
    // (measuring counter-clockwise in our coordinate system).
    //
    // More precisely, the skin tone vector is at approximately:
    // Cb/Cr ratio of about -0.65 (i.e., Cb ≈ -0.117, Cr ≈ 0.179 for unit vector)
    // This is close to the "I" axis of the YIQ color space.

    double skinAngle = std::atan2(0.179, -0.117); // Angle in CbCr space

    double x1 = cx;
    double y1 = cy;
    double x2 = cx + std::cos(skinAngle) * radius;
    double y2 = cy - std::sin(skinAngle) * radius; // Y inverted

    QPen skinPen(QColor(180, 140, 80, static_cast<int>(m_graticuleOpacity * 180)));
    skinPen.setWidthF(1.5);
    skinPen.setStyle(Qt::DashLine);
    painter->setPen(skinPen);
    painter->drawLine(QPointF(x1, y1), QPointF(x2, y2));
}

// ============================================================================
// Render color target markers (the small squares at 75% and 100% positions)
// ============================================================================

void Vectorscope::renderColorTargets(QPainter *painter, int cx, int cy, int radius) const
{
    int boxSize75 = qMax(4, radius / 25);
    int boxSize100 = qMax(5, radius / 20);

    QFont labelFont;
    labelFont.setPixelSize(qMax(10, radius / 16));
    labelFont.setBold(true);
    painter->setFont(labelFont);

    for (const auto &target : m_colorTargets) {
        QColor drawColor = target.color;
        drawColor.setAlpha(static_cast<int>(m_graticuleOpacity * 255 * 1.5));

        QPen boxPen(drawColor, 1.5);
        painter->setPen(boxPen);
        painter->setBrush(Qt::NoBrush);

        // 75% box (smaller, inner)
        QPointF p75 = cbcrToPoint(target.cb75, target.cr75, cx, cy, radius);
        painter->drawRect(QRectF(p75.x() - boxSize75 / 2.0, p75.y() - boxSize75 / 2.0,
                                  boxSize75, boxSize75));

        // 100% box (larger, outer)
        QPointF p100 = cbcrToPoint(target.cb, target.cr, cx, cy, radius);
        painter->drawRect(QRectF(p100.x() - boxSize100 / 2.0, p100.y() - boxSize100 / 2.0,
                                  boxSize100, boxSize100));

        // Label near the 100% target
        // Position label slightly outside the box
        double angle = std::atan2(-(p100.y() - cy), p100.x() - cx);
        double labelDist = boxSize100 + labelFont.pixelSize() * 0.8;
        double lx = p100.x() + std::cos(angle) * labelDist;
        double ly = p100.y() - std::sin(angle) * labelDist;

        painter->setPen(drawColor);
        QRectF textRect(lx - 20, ly - 10, 40, 20);
        painter->drawText(textRect, Qt::AlignCenter, target.label);
    }
}

// ============================================================================
// Registration helper
// ============================================================================
```

## main.cpp (Registration Example)

```cpp
#include <QGuiApplication>
#include <QQmlApplicationEngine>
#include "vectorscope.h"

int main(int argc, char *argv[])
{
    QGuiApplication app(argc, argv);

    // Vectorscope is auto-registered via QML_ELEMENT macro
    // Make sure your CMakeLists.txt includes it in qt_add_qml_module

    QQmlApplicationEngine engine;

    const QUrl url(QStringLiteral("qrc:/main.qml"));
    QObject::connect(&engine, &QQmlApplicationEngine::objectCreationFailed,
                     &app, []() { QCoreApplication::exit(-1); },
                     Qt::QueuedConnection);
    engine.load(url);

    return app.exec();
}
```

## CMakeLists.txt

```cmake
cmake_minimum_required(VERSION 3.16)

project(VectorscopeDemo VERSION 1.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(Qt6 6.10 REQUIRED COMPONENTS Quick Concurrent)

qt_standard_project_setup()

qt_add_executable(VectorscopeDemo
    main.cpp
)

qt_add_qml_module(VectorscopeDemo
    URI VectorscopeModule
    VERSION 1.0
    QML_FILES
        main.qml
    SOURCES
        vectorscope.h
        vectorscope.cpp
)

target_link_libraries(VectorscopeDemo PRIVATE
    Qt6::Quick
    Qt6::Concurrent
)
```

## main.qml (Demo/Test)

```qml
import QtQuick
import QtQuick.Controls
import QtQuick.Layouts
import QtQuick.Dialogs
import VectorscopeModule

ApplicationWindow {
    id: root
    width: 900
    height: 700
    visible: true
    title: "Vectorscope Demo"
    color: "#2a2a2a"

    property var loadedImage: null

    RowLayout {
        anchors.fill: parent
        anchors.margins: 10
        spacing: 10

        // Left panel: controls
        ColumnLayout {
            Layout.preferredWidth: 220
            Layout.fillHeight: true
            spacing: 8

            Label {
                text: "Vectorscope Controls"
                font.bold: true
                font.pixelSize: 16
                color: "white"
            }

            Button {
                text: "Load Image..."
                Layout.fillWidth: true
                onClicked: fileDialog.open()
            }

            // Quality mode
            Label { text: "Quality Mode:"; color: "#ccc" }
            ComboBox {
                id: qualityCombo
                Layout.fillWidth: true
                model: ["Performance", "Balanced", "Accuracy"]
                currentIndex: 1
                onCurrentIndexChanged: vectorscope.qualityMode = currentIndex
            }

            // Intensity
            Label { text: "Intensity: " + intensitySlider.value.toFixed(1); color: "#ccc" }
            Slider {
                id: intensitySlider
                Layout.fillWidth: true
                from: 0.1
                to: 5.0
                value: 1.5
                stepSize: 0.1
                onValueChanged: vectorscope.intensity = value
            }

            // Graticule opacity
            Label { text: "Graticule Opacity: " + (gratOpacitySlider.value * 100).toFixed(0) + "%"; color: "#ccc" }
            Slider {
                id: gratOpacitySlider
                Layout.fillWidth: true
                from: 0.0
                to: 1.0
                value: 0.4
                stepSize: 0.05
                onValueChanged: vectorscope.graticuleOpacity = value
            }

            // Toggles
            CheckBox {
                id: graticuleCheck
                text: "Show Graticule"
                checked: true
                onCheckedChanged: vectorscope.showGraticule = checked
                contentItem: Text { text: parent.text; color: "#ccc"; leftPadding: parent.indicator.width + 8 }
            }

            CheckBox {
                id: skinToneCheck
                text: "Skin Tone Line"
                checked: true
                onCheckedChanged: vectorscope.showSkinToneLine = checked
                contentItem: Text { text: parent.text; color: "#ccc"; leftPadding: parent.indicator.width + 8 }
            }

            CheckBox {
                id: colorizeCheck
                text: "Colorize Points"
                checked: true
                onCheckedChanged: vectorscope.colorizePoints = checked
                contentItem: Text { text: parent.text; color: "#ccc"; leftPadding: parent.indicator.width + 8 }
            }

            CheckBox {
                id: clampCheck
                text: "Clamp Signal"
                checked: true
                onCheckedChanged: vectorscope.clampSignal = checked
                contentItem: Text { text: parent.text; color: "#ccc"; leftPadding: parent.indicator.width + 8 }
            }

            // Target FPS
            Label { text: "Target FPS: " + fpsSlider.value; color: "#ccc" }
            Slider {
                id: fpsSlider
                Layout.fillWidth: true
                from: 5
                to: 60
                value: 30
                stepSize: 5
                onValueChanged: vectorscope.targetFps = value
            }

            Item { Layout.fillHeight: true }

            // Source image preview
            Label { text: "Source Image:"; color: "#ccc" }
            Image {
                id: previewImage
                Layout.fillWidth: true
                Layout.preferredHeight: 150
                fillMode: Image.PreserveAspectFit
                source: ""
            }
        }

        // Right panel: vectorscope
        Rectangle {
            Layout.fillWidth: true
            Layout.fillHeight: true
            color: "#1a1a1a"
            radius: 4

            Vectorscope {
                id: vectorscope
                anchors.fill: parent
                anchors.margins: 5
                qualityMode: Vectorscope.Balanced
                intensity: 1.5
                showGraticule: true
                showSkinToneLine: true
                colorizePoints: true
                clampSignal: true
                graticuleOpacity: 0.4
                backgroundColor: "#1a1a1a"
            }
        }
    }

    FileDialog {
        id: fileDialog
        title: "Select Image"
        nameFilters: ["Image files (*.png *.jpg *.jpeg *.bmp *.tiff *.tif)"]
        onAccepted: {
            var path = selectedFile.toString()
            previewImage.source = path

            // Load image and send to vectorscope
            // Remove the "file://" prefix for QImage loading
            var localPath = path.replace("file://", "")
            imageLoader.source = path
        }
    }

    // Helper to load image data
    Image {
        id: imageLoader
        visible: false
        onStatusChanged: {
            if (status === Image.Ready) {
                // Use grabToImage to get QImage-compatible data
                imageLoader.grabToImage(function(result) {
                    vectorscope.sourceImage = result.image
                })
            }
        }
    }
}
```

## Key Design Decisions & Architecture

### Performance Optimizations:

1. **2D Histogram approach**: Instead of drawing individual points, pixels are binned into a 2D CbCr histogram buffer. This is O(n) for n input pixels and allows efficient rendering regardless of image size.

2. **Subsampling with quality modes**:
   - **Performance**: Processes ~50-100k samples (subsample by 4-8x) — suitable for 60fps real-time playback
   - **Balanced**: Processes ~200-500k samples (subsample by 2-4x) — good for 30fps
   - **Accuracy**: Processes all pixels — best for still image analysis

3. **Async computation**: The histogram computation runs on a background thread via `QtConcurrent::run()`, keeping the UI thread responsive. A future-watcher pattern ensures proper completion handling.

4. **Frame throttling**: `targetFps` property prevents excessive recomputation. If frames arrive faster than the target rate, intermediate frames are dropped.

5. **Cached rendering**: The graticule overlay is rendered to a cached QImage and only regenerated on resize or property changes. The scope scatter image is similarly cached.

6. **LUT-based conversion**: Pre-computed lookup tables for RGB→CbCr conversion avoid per-pixel floating-point multiplications.

### Visual Features:

- **Color-tinted background** with subtle radial color wheel
- **Colorized scatter points** showing the actual color of source pixels at each CbCr position
- **Log-scale brightness** with gamma correction for better dynamic range visualization
- **Glow effect** via box-blur compositing (like professional scopes)
- **75% and 100% color bar targets** with connecting hexagonal pattern
- **Skin tone reference line** (I-axis direction)
- **I/Q axis labels**
- **Clamp signal** option to restrict display to the valid gamut circle