Here's the optimized version with performance enhancements for very large timelines:

```cpp
#include <QQuickPaintedItem>
#include <QPainter>
#include <QCache>
#include <cmath>
#include <algorithm>

class TimelineRuler : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(int totalFrames READ totalFrames WRITE setTotalFrames NOTIFY totalFramesChanged)
    Q_PROPERTY(double pixelsPerFrame READ pixelsPerFrame WRITE setPixelsPerFrame NOTIFY pixelsPerFrameChanged)
    Q_PROPERTY(QColor backgroundColor READ backgroundColor WRITE setBackgroundColor NOTIFY backgroundColorChanged)
    Q_PROPERTY(QColor tickColor READ tickColor WRITE setTickColor NOTIFY tickColorChanged)
    Q_PROPERTY(QColor textColor READ textColor WRITE setTextColor NOTIFY textColorChanged)
    Q_PROPERTY(double visibleStart READ visibleStart WRITE setVisibleStart NOTIFY visibleStartChanged)
    Q_PROPERTY(double visibleEnd READ visibleEnd WRITE setVisibleEnd NOTIFY visibleEndChanged)

public:
    TimelineRuler(QQuickItem *parent = nullptr) 
        : QQuickPaintedItem(parent), 
          m_totalFrames(1000), 
          m_pixelsPerFrame(1.0),
          m_visibleStart(0.0),
          m_visibleEnd(1.0),
          m_backgroundColor(QColor(240, 240, 240)),
          m_tickColor(Qt::black),
          m_textColor(Qt::black),
          m_textCache(1000) // Cache up to 1000 text measurements
    {
        setAntialiasing(true);
        setPerformanceHint(FastFBOResizing, true);
        setPerformanceHint(QQuickPaintedItem::PerformanceHint::FastPainting, true);
    }

    int totalFrames() const { return m_totalFrames; }
    double pixelsPerFrame() const { return m_pixelsPerFrame; }
    double visibleStart() const { return m_visibleStart; }
    double visibleEnd() const { return m_visibleEnd; }
    QColor backgroundColor() const { return m_backgroundColor; }
    QColor tickColor() const { return m_tickColor; }
    QColor textColor() const { return m_textColor; }

    void setTotalFrames(int totalFrames) {
        if (m_totalFrames != totalFrames) {
            m_totalFrames = totalFrames;
            m_cachedTickSpacing = -1; // Invalidate cache
            update();
            emit totalFramesChanged();
        }
    }

    void setPixelsPerFrame(double pixelsPerFrame) {
        if (!qFuzzyCompare(m_pixelsPerFrame, pixelsPerFrame)) {
            m_pixelsPerFrame = pixelsPerFrame;
            m_cachedTickSpacing = -1; // Invalidate cache
            update();
            emit pixelsPerFrameChanged();
        }
    }

    void setVisibleStart(double start) {
        if (!qFuzzyCompare(m_visibleStart, start)) {
            m_visibleStart = start;
            update();
            emit visibleStartChanged();
        }
    }

    void setVisibleEnd(double end) {
        if (!qFuzzyCompare(m_visibleEnd, end)) {
            m_visibleEnd = end;
            update();
            emit visibleEndChanged();
        }
    }

    void setBackgroundColor(const QColor &color) {
        if (m_backgroundColor != color) {
            m_backgroundColor = color;
            update();
            emit backgroundColorChanged();
        }
    }

    void setTickColor(const QColor &color) {
        if (m_tickColor != color) {
            m_tickColor = color;
            update();
            emit tickColorChanged();
        }
    }

    void setTextColor(const QColor &color) {
        if (m_textColor != color) {
            m_textColor = color;
            update();
            emit textColorChanged();
        }
    }

    void paint(QPainter *painter) override {
        if (m_totalFrames <= 0 || m_pixelsPerFrame <= 0 || width() <= 0) {
            return;
        }
        
        // Draw background
        painter->fillRect(boundingRect(), m_backgroundColor);
        
        // Draw ruler border
        painter->setPen(Qt::gray);
        painter->drawLine(0, height() - 1, width(), height() - 1);
        
        // Calculate visible range
        double visibleWidth = width();
        double startFrame = m_visibleStart * m_totalFrames;
        double endFrame = m_visibleEnd * m_totalFrames;
        
        // Draw only visible ticks
        drawVisibleTicks(painter, startFrame, endFrame, visibleWidth);
    }

signals:
    void totalFramesChanged();
    void pixelsPerFrameChanged();
    void visibleStartChanged();
    void visibleEndChanged();
    void backgroundColorChanged();
    void tickColorChanged();
    void textColorChanged();

private:
    struct TextMetrics {
        QRect rect;
        int width;
        int height;
    };

    void drawVisibleTicks(QPainter *painter, double startFrame, double endFrame, double visibleWidth) {
        // Calculate optimal tick spacing (with caching)
        double frameSpacing = calculateOptimalTickSpacing(visibleWidth);
        if (frameSpacing <= 0) return;
        
        const int tickHeight = 12;
        const int majorTickHeight = 18;
        
        // Pre-calculate font metrics
        QFont font = painter->font();
        font.setPointSize(8);
        QFontMetrics fm(font);
        painter->setFont(font);
        painter->setPen(m_tickColor);
        
        // Find the first visible tick
        int firstTick = static_cast<int>(std::floor(startFrame / frameSpacing)) * static_cast<int>(frameSpacing);
        firstTick = std::max(0, firstTick);
        
        // Draw only visible ticks
        for (int frame = firstTick; frame <= endFrame; frame += static_cast<int>(frameSpacing)) {
            if (frame > m_totalFrames) break;
            
            double x = (frame - startFrame) * m_pixelsPerFrame;
            
            // Skip if outside visible area (with small margin)
            if (x < -50 || x > visibleWidth + 50) continue;
            
            // Determine if this is a major tick
            bool isMajorTick = (frame % (static_cast<int>(frameSpacing) * 5) == 0) || 
                              (frame == 0) || (frame == m_totalFrames);
            
            int currentTickHeight = isMajorTick ? majorTickHeight : tickHeight;
            
            // Draw tick mark
            painter->drawLine(x, height() - currentTickHeight, x, height());
            
            // Draw frame number for major ticks
            if (isMajorTick) {
                drawTickLabel(painter, frame, x, majorTickHeight, visibleWidth, fm);
            }
        }
        
        // Always draw the last frame tick if visible
        if (endFrame >= m_totalFrames) {
            double lastFrameX = (m_totalFrames - startFrame) * m_pixelsPerFrame;
            if (lastFrameX >= -50 && lastFrameX <= visibleWidth + 50) {
                painter->drawLine(lastFrameX, height() - majorTickHeight, lastFrameX, height());
                drawTickLabel(painter, m_totalFrames, lastFrameX, majorTickHeight, visibleWidth, fm);
            }
        }
    }
    
    void drawTickLabel(QPainter *painter, int frame, double x, int tickHeight, double visibleWidth, const QFontMetrics &fm) {
        QString frameText = QString::number(frame);
        
        // Get text metrics from cache or calculate
        TextMetrics metrics = getTextMetrics(frameText, fm);
        
        // Center text above tick
        double textX = x - metrics.width / 2.0;
        double textY = height() - tickHeight - 5;
        
        // Clamp text position to visible area
        textX = std::max(0.0, std::min(textX, visibleWidth - metrics.width));
        
        // Only draw if text is fully or partially visible
        if (textX + metrics.width > 0 && textX < visibleWidth) {
            painter->setPen(m_textColor);
            painter->drawText(QRectF(textX, textY, metrics.width, metrics.height), 
                             Qt::AlignCenter, frameText);
            painter->setPen(m_tickColor);
        }
    }
    
    TextMetrics getTextMetrics(const QString &text, const QFontMetrics &fm) {
        // Check cache first
        if (m_textCache.contains(text)) {
            return *m_textCache.object(text);
        }
        
        // Calculate and cache
        TextMetrics metrics;
        metrics.rect = fm.boundingRect(text);
        metrics.width = metrics.rect.width();
        metrics.height = metrics.rect.height();
        
        m_textCache.insert(text, new TextMetrics(metrics));
        return metrics;
    }
    
    double calculateOptimalTickSpacing(double widgetWidth) {
        // Use cached value if available and valid
        if (m_cachedTickSpacing > 0 && qFuzzyCompare(m_cachedWidgetWidth, widgetWidth)) {
            return m_cachedTickSpacing;
        }
        
        // Estimate how many ticks we can fit
        const int maxTicks = widgetWidth / 80;
        
        if (maxTicks <= 0) return 1;
        
        double framesPerTick = static_cast<double>(m_totalFrames) / maxTicks;
        
        // Round to a nice number
        double spacing = roundToNiceNumber(framesPerTick);
        
        // Cache the result
        m_cachedTickSpacing = spacing;
        m_cachedWidgetWidth = widgetWidth;
        
        return spacing;
    }
    
    double roundToNiceNumber(double value) const {
        if (value <= 0) return 1;
        
        double exponent = std::floor(std::log10(value));
        double fraction = value / std::pow(10.0, exponent);
        
        double niceFraction;
        if (fraction <= 1.5) {
            niceFraction = 1.0;
        } else if (fraction <= 3.0) {
            niceFraction = 2.0;
        } else if (fraction <= 7.0) {
            niceFraction = 5.0;
        } else {
            niceFraction = 10.0;
        }
        
        return niceFraction * std::pow(10.0, exponent);
    }

private:
    int m_totalFrames;
    double m_pixelsPerFrame;
    double m_visibleStart;
    double m_visibleEnd;
    QColor m_backgroundColor;
    QColor m_tickColor;
    QColor m_textColor;
    
    // Performance optimization members
    double m_cachedTickSpacing = -1;
    double m_cachedWidgetWidth = -1;
    QCache<QString, TextMetrics> m_textCache;
};
```

**Key Performance Optimizations:**

1. **Visible Range Rendering**: Only draws ticks that are actually visible using `visibleStart` and `visibleEnd` properties

2. **Tick Spacing Caching**: Caches the calculated tick spacing to avoid recalculating on every paint

3. **Text Metrics Caching**: Caches text measurements to avoid expensive `fontMetrics().boundingRect()` calls

4. **Early Exit Conditions**: Skips drawing ticks that are outside the visible area (+50px margin)

5. **QQuickPaintedItem Performance Hints**: Uses `FastFBOResizing` and `FastPainting` hints

6. **Efficient Loop**: Starts from the first visible tick instead of iterating from frame 0

7. **Pre-calculated Metrics**: Pre-calculates font metrics outside the drawing loop

**Enhanced QML Usage:**
```qml
TimelineRuler {
    id: timelineRuler
    width: 800
    height: 30
    totalFrames: 1000000 // 1 million frames!
    pixelsPerFrame: 0.1
    visibleStart: timelineScrollBar.position
    visibleEnd: timelineScrollBar.position + timelineScrollBar.size
    
    Connections {
        target: timelineView
        onViewportChanged: {
            timelineRuler.visibleStart = newStart
            timelineRuler.visibleEnd = newEnd
        }
    }
}
```

**Additional Optimization Tips:**

1. **For extremely large timelines (1M+ frames):**
```cpp
// In calculateOptimalTickSpacing():
const int maxTicks = widgetWidth / 120; // More spacing for huge timelines

// Add logarithmic scaling for massive frame counts:
if (m_totalFrames > 1000000) {
    framesPerTick *= 10; // Automatically increase spacing
}
```

2. **Consider adding LOD (Level of Detail) rendering:**
```cpp
// Add to class:
Q_PROPERTY(int lodLevel READ lodLevel WRITE setLodLevel NOTIFY lodLevelChanged)

// In drawVisibleTicks():
if (m_lodLevel > 1) {
    // Skip minor ticks at high zoom levels
    if (!isMajorTick) continue;
}
```

3. **For animated timelines, add a frame rate limiter:**
```qml
TimelineRuler {
    // Limit repaints to 30 FPS during animation
    Timer {
        running: animation.running
        interval: 33
        onTriggered: parent.update()
    }
}
```

This optimized version can easily handle timelines with millions of frames while maintaining smooth performance, even during scrolling and zooming operations.
