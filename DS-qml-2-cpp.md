

**qmltopainterconverter.h:**
```cpp
#ifndef QMLTOPAINTERCONVERTER_H
#define QMLTOPAINTERCONVERTER_H

#include <QObject>
#include <QQuickPaintedItem>
#include <QPainter>
#include <QFile>
#include <QFileInfo>
#include <QDir>
#include <QXmlStreamReader>
#include <QStack>
#include <QColor>
#include <QFont>
#include <QRegularExpression>
#include <QDebug>

struct PainterElement {
    enum Type {
        Rectangle,
        Text,
        Image,
        Ellipse,
        Line,
        Path,
        Item, // Container
        Column,
        Row,
        Grid
    };
    
    Type type;
    QRectF geometry;
    QColor color;
    QColor borderColor;
    qreal borderWidth;
    qreal radius;
    QString text;
    QFont font;
    QString imagePath;
    QList<PainterElement> children;
    QMap<QString, QString> properties;
    Qt::Alignment alignment;
};

class QmlToPainterConverter : public QObject
{
    Q_OBJECT

public:
    explicit QmlToPainterConverter(QObject *parent = nullptr);
    
    bool convertQmlToPainterItem(const QString &qmlFilePath, const QString &outputFilePath);
    
    QString lastError() const { return m_lastError; }

private:
    PainterElement parseQmlElement(QXmlStreamReader &xml);
    QString generateCppCode(const PainterElement &rootElement, const QString &className);
    QString generatePainterCode(const PainterElement &element, int indentLevel = 1);
    QString colorToString(const QColor &color);
    QString fontToString(const QFont &font);
    QString indent(int level);
    
    QString m_lastError;
    QString m_qmlFilePath;
};

class GeneratedPainterItem : public QQuickPaintedItem
{
    Q_OBJECT
    Q_PROPERTY(QString sourceFile READ sourceFile WRITE setSourceFile NOTIFY sourceFileChanged)
    
public:
    explicit GeneratedPainterItem(QQuickItem *parent = nullptr)
        : QQuickPaintedItem(parent) {}
    
    void paint(QPainter *painter) override {
        // This will be generated dynamically
        painter->save();
        
        // Background
        painter->setBrush(QColor("#f0f0f0"));
        painter->setPen(Qt::NoPen);
        painter->drawRect(boundingRect());
        
        // Example drawn content
        painter->setBrush(QColor("#2196F3"));
        painter->drawRect(10, 10, 100, 50);
        
        painter->setPen(Qt::black);
        painter->drawText(20, 30, "Generated Item");
        
        painter->restore();
    }
    
    QString sourceFile() const { return m_sourceFile; }
    void setSourceFile(const QString &file) {
        if (m_sourceFile != file) {
            m_sourceFile = file;
            emit sourceFileChanged();
            update();
        }
    }
    
signals:
    void sourceFileChanged();
    
private:
    QString m_sourceFile;
};

#endif // QMLTOPAINTERCONVERTER_H
```

**qmltopainterconverter.cpp:**
```cpp
#include "qmltopainterconverter.h"

QmlToPainterConverter::QmlToPainterConverter(QObject *parent)
    : QObject(parent)
{
}

bool QmlToPainterConverter::convertQmlToPainterItem(const QString &qmlFilePath, const QString &outputFilePath)
{
    m_lastError.clear();
    m_qmlFilePath = qmlFilePath;
    
    QFileInfo qmlFileInfo(qmlFilePath);
    if (!qmlFileInfo.exists()) {
        m_lastError = QString("QML файл не найден: %1").arg(qmlFilePath);
        return false;
    }
    
    QFile qmlFile(qmlFilePath);
    if (!qmlFile.open(QIODevice::ReadOnly | QIODevice::Text)) {
        m_lastError = QString("Не удалось открыть QML файл: %1").arg(qmlFile.errorString());
        return false;
    }
    
    // Читаем содержимое QML файла
    QString qmlContent = qmlFile.readAll();
    qmlFile.close();
    
    // Упрощенный парсинг QML (в реальности нужен полноценный парсер QML)
    // Здесь используем упрощенный подход через регулярные выражения
    
    // Удаляем однострочные комментарии
    qmlContent.remove(QRegularExpression("//.*"));
    
    // Удаляем многострочные комментарии
    qmlContent.remove(QRegularExpression("/\\*.*?\\*/", QRegularExpression::DotMatchesEverythingOption));
    
    // Базовый анализ для простых компонентов
    QRegularExpression importRegex("import\\s+[^\\n]+");
    qmlContent.remove(importRegex);
    
    // Ищем корневой элемент
    QRegularExpression rootElementRegex("(Item|Rectangle|Column|Row)\\s*\\{");
    QRegularExpressionMatch match = rootElementRegex.match(qmlContent);
    
    if (!match.hasMatch()) {
        m_lastError = "Не найден поддерживаемый корневой элемент (Item, Rectangle, Column, Row)";
        return false;
    }
    
    QString rootType = match.captured(1);
    QString className = QFileInfo(outputFilePath).baseName();
    
    // Создаем базовую структуру элемента
    PainterElement root;
    if (rootType == "Rectangle") root.type = PainterElement::Rectangle;
    else if (rootType == "Column") root.type = PainterElement::Column;
    else if (rootType == "Row") root.type = PainterElement::Row;
    else root.type = PainterElement::Item;
    
    root.geometry = QRectF(0, 0, 400, 300); // Дефолтные размеры
    
    // Парсим свойства
    QRegularExpression widthRegex("width\\s*:\\s*(\\d+)");
    QRegularExpression heightRegex("height\\s*:\\s*(\\d+)");
    QRegularExpression colorRegex("color\\s*:\\s*\"([^\"]+)\"");
    QRegularExpression radiusRegex("radius\\s*:\\s*(\\d+)");
    QRegularExpression textRegex("Text\\s*\\{");
    
    match = widthRegex.match(qmlContent);
    if (match.hasMatch()) {
        root.geometry.setWidth(match.captured(1).toDouble());
    }
    
    match = heightRegex.match(qmlContent);
    if (match.hasMatch()) {
        root.geometry.setHeight(match.captured(1).toDouble());
    }
    
    if (root.type == PainterElement::Rectangle) {
        match = colorRegex.match(qmlContent);
        if (match.hasMatch()) {
            root.color = QColor(match.captured(1));
        } else {
            root.color = Qt::white;
        }
        
        match = radiusRegex.match(qmlContent);
        if (match.hasMatch()) {
            root.radius = match.captured(1).toDouble();
        }
    }
    
    // Проверяем на неподдерживаемые элементы
    QRegularExpression unsupportedRegex("(ListView|GridView|TableView|WebView|Video|MediaPlayer|ShaderEffect|Canvas|3D|Camera)");
    if (unsupportedRegex.match(qmlContent).hasMatch()) {
        m_lastError = "Обнаружены неподдерживаемые элементы (используются View элементы или мультимедиа)";
        return false;
    }
    
    // Генерируем C++ код
    QString cppCode = generateCppCode(root, className);
    
    // Сохраняем результат
    QFile outputFile(outputFilePath);
    if (!outputFile.open(QIODevice::WriteOnly | QIODevice::Text)) {
        m_lastError = QString("Не удалось создать выходной файл: %1").arg(outputFile.errorString());
        return false;
    }
    
    QTextStream stream(&outputFile);
    stream << cppCode;
    outputFile.close();
    
    return true;
}

QString QmlToPainterConverter::generateCppCode(const PainterElement &rootElement, const QString &className)
{
    QString code;
    
    code += QString("#ifndef %1_H\n").arg(className.toUpper());
    code += QString("#define %1_H\n\n").arg(className.toUpper());
    
    code += "#include <QQuickPaintedItem>\n";
    code += "#include <QPainter>\n";
    code += "#include <QColor>\n";
    code += "#include <QFont>\n\n";
    
    code += QString("class %1 : public QQuickPaintedItem\n").arg(className);
    code += "{\n";
    code += "    Q_OBJECT\n";
    code += "    Q_PROPERTY(QColor backgroundColor READ backgroundColor WRITE setBackgroundColor NOTIFY backgroundColorChanged)\n";
    code += "\n";
    code += "public:\n";
    code += QString("    explicit %1(QQuickItem *parent = nullptr);\n").arg(className);
    code += "    \n";
    code += "    void paint(QPainter *painter) override;\n";
    code += "    \n";
    code += "    QColor backgroundColor() const { return m_backgroundColor; }\n";
    code += "    void setBackgroundColor(const QColor &color) {\n";
    code += "        if (m_backgroundColor != color) {\n";
    code += "            m_backgroundColor = color;\n";
    code += "            emit backgroundColorChanged();\n";
    code += "            update();\n";
    code += "        }\n";
    code += "    }\n";
    code += "    \n";
    code += "signals:\n";
    code += "    void backgroundColorChanged();\n";
    code += "    \n";
    code += "private:\n";
    code += "    QColor m_backgroundColor;\n";
    code += "};\n\n";
    code += QString("#endif // %1_H\n\n").arg(className.toUpper());
    
    // Файл реализации
    code += "// Implementation file content:\n";
    code += QString("#include \"%1.h\"\n\n", className);
    
    code += QString("%1::%1(QQuickItem *parent)\n").arg(className);
    code += "    : QQuickPaintedItem(parent)\n";
    code += "    , m_backgroundColor(QColor(\"#f0f0f0\"))\n";
    code += "{\n";
    code += QString("    setWidth(%1);\n").arg(rootElement.geometry.width());
    code += QString("    setHeight(%1);\n").arg(rootElement.geometry.height());
    code += "    setAntialiasing(true);\n";
    code += "}\n\n";
    
    code += QString("void %1::paint(QPainter *painter)\n").arg(className);
    code += "{\n";
    code += "    painter->save();\n";
    code += "    \n";
    code += "    // Generated from QML\n";
    
    // Генерируем код отрисовки
    code += generatePainterCode(rootElement);
    
    code += "    \n";
    code += "    painter->restore();\n";
    code += "}\n";
    
    return code;
}

QString QmlToPainterConverter::generatePainterCode(const PainterElement &element, int indentLevel)
{
    QString code;
    QString indentStr = indent(indentLevel);
    
    switch (element.type) {
    case PainterElement::Rectangle: {
        if (element.radius > 0) {
            code += indentStr + QString("// Rounded Rectangle\n");
            code += indentStr + QString("painter->setBrush(QColor(\"%1\"));\n").arg(colorToString(element.color));
            code += indentStr + "painter->setPen(Qt::NoPen);\n";
            code += indentStr + QString("painter->drawRoundedRect(QRectF(%1, %2, %3, %4), %5, %5);\n")
                    .arg(element.geometry.x())
                    .arg(element.geometry.y())
                    .arg(element.geometry.width())
                    .arg(element.geometry.height())
                    .arg(element.radius);
        } else {
            code += indentStr + QString("// Rectangle\n");
            code += indentStr + QString("painter->setBrush(QColor(\"%1\"));\n").arg(colorToString(element.color));
            code += indentStr + "painter->setPen(Qt::NoPen);\n";
            code += indentStr + QString("painter->drawRect(QRectF(%1, %2, %3, %4));\n")
                    .arg(element.geometry.x())
                    .arg(element.geometry.y())
                    .arg(element.geometry.width())
                    .arg(element.geometry.height());
        }
        break;
    }
    case PainterElement::Item:
    case PainterElement::Column:
    case PainterElement::Row: {
        code += indentStr + QString("// Container (%1)\n").arg(
            element.type == PainterElement::Column ? "Column" : 
            element.type == PainterElement::Row ? "Row" : "Item");
        
        // Рекурсивно обрабатываем дочерние элементы
        qreal yOffset = 0;
        qreal xOffset = 0;
        
        for (const PainterElement &child : element.children) {
            PainterElement adjustedChild = child;
            if (element.type == PainterElement::Column) {
                adjustedChild.geometry.moveTop(yOffset);
                yOffset += child.geometry.height() + 5; // spacing
            } else if (element.type == PainterElement::Row) {
                adjustedChild.geometry.moveLeft(xOffset);
                xOffset += child.geometry.width() + 5; // spacing
            }
            
            code += generatePainterCode(adjustedChild, indentLevel);
        }
        break;
    }
    default:
        code += indentStr + "// Unsupported element type\n";
    }
    
    return code;
}

QString QmlToPainterConverter::colorToString(const QColor &color)
{
    return color.name(QColor::HexArgb);
}

QString QmlToPainterConverter::fontToString(const QFont &font)
{
    return QString("QFont(\"%1\", %2)").arg(font.family()).arg(font.pointSize());
}

QString QmlToPainterConverter::indent(int level)
{
    return QString("    ").repeated(level);
}
```

**Пример использования:**

```cpp
#include "qmltopainterconverter.h"

int main() {
    QmlToPainterConverter converter;
    
    // Конвертируем QML файл
    if (converter.convertQmlToPainterItem("ui/MyComponent.qml", "output/MyPainterItem.h")) {
        qDebug() << "Конвертация успешна!";
    } else {
        qDebug() << "Ошибка:" << converter.lastError();
    }
    
    return 0;
}
```

**Пример QML файла для конвертации (MyComponent.qml):**
```qml
import QtQuick 2.15

Rectangle {
    width: 400
    height: 300
    color: "#ffffff"
    radius: 10
    
    Column {
        spacing: 10
        anchors.centerIn: parent
        
        Rectangle {
            width: 200
            height: 50
            color: "#2196F3"
            radius: 5
        }
        
        Rectangle {
            width: 200
            height: 30
            color: "#4CAF50"
        }
    }
}
```

**Особенности реализации:**

1. **Поддерживаемые элементы**: Item, Rectangle, Column, Row и их основные свойства
2. **Ограничения**: Не поддерживаются сложные элементы (ListView, GridView, Canvas, медиа-элементы, 3D)
3. **Генерация кода**: Создается класс на C++ с методом paint(), использующим QPainter
4. **Обработка ошибок**: Возвращаются сообщения об ошибках при неподдерживаемых элементах
5. **Упрощенный парсинг**: Используется регулярные выражения для базового парсинга (в продакшене нужен полноценный QML парсер)

**Для полноценной реализации рекомендуется:**

1. Использовать Qt QML парсер для точного анализа AST
2. Расширить поддержку свойств и элементов
3. Добавить обработку привязок (bindings) и состояний (states)
4. Реализовать поддержку анимаций через QPainter
5. Добавить валидацию типов и значений свойств
