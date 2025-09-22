Here's the QQuickPaintedItem-based implementation for use in QML:

```cpp
#include <QQuickPaintedItem>
#include <QPainter>
#include <cmath>

class TimelineRuler : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(int totalFrames READ totalFrames WRITE setTotalFrames NOTIFY totalFramesChanged)
    Q_PROPERTY(double pixelsPerFrame READ pixelsPerFrame WRITE setPixelsPerFrame NOTIFY pixelsPerFrameChanged)
    Q_PROPERTY(QColor backgroundColor READ backgroundColor WRITE setBackgroundColor NOTIFY backgroundColorChanged)
    Q_PROPERTY(QColor tickColor READ tickColor WRITE setTickColor NOTIFY tickColorChanged)
    Q_PROPERTY(QColor textColor READ textColor WRITE setTextColor NOTIFY textColorChanged)

public:
    TimelineRuler(QQuickItem *parent = nullptr) 
        : QQuickPaintedItem(parent), 
          m_totalFrames(1000), 
          m_pixelsPerFrame(1.0),
          m_backgroundColor(QColor(240, 240, 240)),
          m_tickColor(Qt::black),
          m_textColor(Qt::black)
    {
        setAntialiasing(true);
    }

    int totalFrames() const { return m_totalFrames; }
    double pixelsPerFrame() const { return m_pixelsPerFrame; }
    QColor backgroundColor() const { return m_backgroundColor; }
    QColor tickColor() const { return m_tickColor; }
    QColor textColor() const { return m_textColor; }

    void setTotalFrames(int totalFrames) {
        if (m_totalFrames != totalFrames) {
            m_totalFrames = totalFrames;
            update();
            emit totalFramesChanged();
        }
    }

    void setPixelsPerFrame(double pixelsPerFrame) {
        if (!qFuzzyCompare(m_pixelsPerFrame, pixelsPerFrame)) {
            m_pixelsPerFrame = pixelsPerFrame;
            update();
            emit pixelsPerFrameChanged();
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
        if (m_totalFrames <= 0 || m_pixelsPerFrame <= 0) {
            return;
        }
        
        // Draw background
        painter->fillRect(boundingRect(), m_backgroundColor);
        
        // Draw ruler border
        painter->setPen(Qt::gray);
        painter->drawLine(0, height() - 1, width(), height() - 1);
        
        // Calculate which ticks to draw
        drawTicks(painter);
    }

signals:
    void totalFramesChanged();
    void pixelsPerFrameChanged();
    void backgroundColorChanged();
    void tickColorChanged();
    void textColorChanged();

private:
    void drawTicks(QPainter *painter) {
        const double widgetWidth = width();
        const int minTickSpacing = 50;  // Minimum pixels for good readability
        
        // Calculate optimal tick spacing
        double frameSpacing = calculateOptimalTickSpacing(widgetWidth);
        double tickSpacingPixels = frameSpacing * m_pixelsPerFrame;
        
        // Ensure minimum spacing for readability
        if (tickSpacingPixels < minTickSpacing) {
            frameSpacing = std::ceil(minTickSpacing / m_pixelsPerFrame);
            tickSpacingPixels = frameSpacing * m_pixelsPerFrame;
        }
        
        if (frameSpacing <= 0) return;
        
        // Draw ticks
        painter->setPen(m_tickColor);
        QFont font = painter->font();
        font.setPointSize(8);
        painter->setFont(font);
        
        const int tickHeight = 12;
        const int majorTickHeight = 18;
        
        for (int frame = 0; frame <= m_totalFrames; frame += static_cast<int>(frameSpacing)) {
            double x = frame * m_pixelsPerFrame;
            
            if (x > widgetWidth) break;
            
            // Determine if this is a major tick (for round numbers)
            bool isMajorTick = (frame % (static_cast<int>(frameSpacing) * 5) == 0) || 
                              (frame == 0) || (frame == m_totalFrames);
            
            int currentTickHeight = isMajorTick ? majorTickHeight : tickHeight;
            
            // Draw tick mark
            painter->drawLine(x, height() - currentTickHeight, x, height());
            
            // Draw frame number for major ticks
            if (isMajorTick) {
                painter->setPen(m_textColor);
                QString frameText = QString::number(frame);
                QRect textRect = painter->fontMetrics().boundingRect(frameText);
                
                // Center text above tick
                double textX = x - textRect.width() / 2.0;
                double textY = height() - majorTickHeight - 5;
                
                // Ensure text doesn't go off the left edge
                textX = std::max(0.0, textX);
                
                // Ensure text doesn't go off the right edge
                if (textX + textRect.width() > widgetWidth) {
                    textX = widgetWidth - textRect.width();
                }
                
                painter->drawText(textX, textY, frameText);
                painter->setPen(m_tickColor);
            }
        }
        
        // Always draw the last frame tick
        double lastFrameX = m_totalFrames * m_pixelsPerFrame;
        if (lastFrameX <= widgetWidth) {
            painter->drawLine(lastFrameX, height() - majorTickHeight, lastFrameX, height());
            
            painter->setPen(m_textColor);
            QString lastFrameText = QString::number(m_totalFrames);
            QRect textRect = painter->fontMetrics().boundingRect(lastFrameText);
            double textX = lastFrameX - textRect.width() / 2.0;
            double textY = height() - majorTickHeight - 5;
            
            // Adjust if text goes off the right edge
            if (textX + textRect.width() > widgetWidth) {
                textX = widgetWidth - textRect.width();
            }
            
            painter->drawText(textX, textY, lastFrameText);
        }
    }
    
    double calculateOptimalTickSpacing(double widgetWidth) const {
        // Estimate how many ticks we can fit
        const int maxTicks = widgetWidth / 80; // Allow ~80 pixels per tick with text
        
        if (maxTicks <= 0) return 1;
        
        double framesPerTick = static_cast<double>(m_totalFrames) / maxTicks;
        
        // Round to a nice number (1, 2, 5, 10, 20, 50, 100, etc.)
        return roundToNiceNumber(framesPerTick);
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
    QColor m_backgroundColor;
    QColor m_tickColor;
    QColor m_textColor;
};
```

**Corresponding QML usage:**

```qml
import QtQuick 2.15
import YourModule 1.0

TimelineRuler {
    id: timelineRuler
    width: 800
    height: 30
    totalFrames: 5000
    pixelsPerFrame: 0.5
    backgroundColor: "#f0f0f0"
    tickColor: "black"
    textColor: "black"

    // Connect to your timeline's zoom/scroll changes
    Connections {
        target: yourTimelineComponent
        onZoomChanged: timelineRuler.pixelsPerFrame = newZoomLevel
        onScrollChanged: timelineRuler.x = -scrollPosition
    }
}
```

**Additional files needed:**

1. **main.cpp** (register the type):
```cpp
#include <QGuiApplication>
#include <QQmlApplicationEngine>
#include <QQmlContext>
#include "timelineruler.h"

int main(int argc, char *argv[])
{
    QGuiApplication app(argc, argv);
    
    QQmlApplicationEngine engine;
    
    // Register the TimelineRuler class
    qmlRegisterType<TimelineRuler>("com.example.timeline", 1, 0, "TimelineRuler");
    
    engine.load(QUrl(QStringLiteral("qrc:/main.qml")));
    
    return app.exec();
}
```

2. **CMakeLists.txt** or **.pro** file (ensure proper compilation):
```cmake
# For CMake
qt_add_executable(YourApp
    main.cpp
    timelineruler.cpp
)

target_link_libraries(YourApp PRIVATE Qt6::Quick Qt6::Gui)
```

**Key changes from QWidget to QQuickPaintedItem:**

1. **Inheritance**: Changed from `QWidget` to `QQuickPaintedItem`
2. **Painting**: Override `paint()` instead of `paintEvent()`
3. **Properties**: Added Q_PROPERTY declarations for QML access
4. **Signals**: Added signal declarations for property changes
5. **Coordinate system**: Uses floating-point coordinates for better precision
6. **Antialiasing**: Enabled by default with `setAntialiasing(true)`

**Features:**
- Fully customizable from QML
- Efficient rendering even with thousands of frames
- Adaptive tick spacing
- Customizable colors
- Proper signal/slot system for integration with other QML components

The ruler will now work seamlessly in QML and can be styled and controlled through properties and signals.
