Вот дополненный код с примерами вызова C++ методов из JavaScript:

**main.cpp:**
```cpp
#include <QCoreApplication>
#include <QFile>
#include <QTextStream>
#include <QJSEngine>
#include <QDebug>
#include <QCommandLineParser>
#include <QCommandLineOption>
#include <QObject>
#include <QTimer>
#include <QDateTime>

// ========== 1. ПРОСТОЙ КЛАСС С МЕТОДАМИ ==========
class Calculator : public QObject
{
    Q_OBJECT
public:
    Q_INVOKABLE int add(int a, int b) {
        qDebug() << "Calculator::add called with" << a << "and" << b;
        return a + b;
    }
    
    Q_INVOKABLE double multiply(double a, double b) {
        return a * b;
    }
    
    Q_INVOKABLE QString greet(const QString &name) {
        return QString("Hello, %1!").arg(name);
    }
    
    Q_INVOKABLE QVariantList processArray(const QVariantList &array) {
        QVariantList result;
        for (const QVariant &item : array) {
            result.append(item.toInt() * 2);
        }
        return result;
    }
};

// ========== 2. КЛАСС ДЛЯ РАБОТЫ С ФАЙЛАМИ ==========
class FileManager : public QObject
{
    Q_OBJECT
public:
    Q_INVOKABLE QString readFile(const QString &filePath) {
        QFile file(filePath);
        if (!file.open(QIODevice::ReadOnly | QIODevice::Text)) {
            return QString("Error: Cannot open file %1").arg(filePath);
        }
        QTextStream stream(&file);
        return stream.readAll();
    }
    
    Q_INVOKABLE bool writeFile(const QString &filePath, const QString &content) {
        QFile file(filePath);
        if (!file.open(QIODevice::WriteOnly | QIODevice::Text)) {
            return false;
        }
        QTextStream stream(&file);
        stream << content;
        return true;
    }
    
    Q_INVOKABLE qint64 getFileSize(const QString &filePath) {
        QFileInfo fileInfo(filePath);
        return fileInfo.size();
    }
};

// ========== 3. КЛАСС С СОСТОЯНИЕМ ==========
class Counter : public QObject
{
    Q_OBJECT
    Q_PROPERTY(int value READ value NOTIFY valueChanged)
    
public:
    Counter() : m_value(0) {}
    
    Q_INVOKABLE int increment() {
        m_value++;
        emit valueChanged(m_value);
        return m_value;
    }
    
    Q_INVOKABLE int decrement() {
        m_value--;
        emit valueChanged(m_value);
        return m_value;
    }
    
    Q_INVOKABLE void reset() {
        m_value = 0;
        emit valueChanged(m_value);
    }
    
    int value() const { return m_value; }
    
signals:
    void valueChanged(int newValue);
    
private:
    int m_value;
};

// ========== 4. СИНГЛТОН ДЛЯ СИСТЕМНОЙ ИНФОРМАЦИИ ==========
class SystemInfo : public QObject
{
    Q_OBJECT
public:
    Q_INVOKABLE QString currentDateTime() {
        return QDateTime::currentDateTime().toString("yyyy-MM-dd hh:mm:ss");
    }
    
    Q_INVOKABLE qint64 timestamp() {
        return QDateTime::currentMSecsSinceEpoch();
    }
    
    Q_INVOKABLE QString platform() {
        return QSysInfo::prettyProductName();
    }
};

int main(int argc, char *argv[])
{
    QCoreApplication app(argc, argv);
    QCoreApplication::setApplicationName("Qt JavaScript Runner with C++ Bindings");
    QCoreApplication::setApplicationVersion("1.0");

    QCommandLineParser parser;
    parser.setApplicationDescription("JavaScript runner with C++ class bindings");
    parser.addHelpOption();
    parser.addVersionOption();
    parser.addPositionalArgument("file", "JavaScript file to execute");
    
    QCommandLineOption evalOption("e", "Evaluate JavaScript code directly", "code");
    parser.addOption(evalOption);
    
    parser.process(app);
    
    // Создаем движок JavaScript
    QJSEngine engine;
    
    // Регистрируем функции для вывода
    QJSValue printFunc = engine.evaluate(
        "(function() {"
        "    var output = '';"
        "    for (var i = 0; i < arguments.length; i++) {"
        "        output += arguments[i];"
        "        if (i < arguments.length - 1) output += ' ';"
        "    }"
        "    console.log(output);"
        "})"
    );
    engine.globalObject().setProperty("print", printFunc);
    
    QJSValue consoleObj = engine.newObject();
    consoleObj.setProperty("log", printFunc);
    engine.globalObject().setProperty("console", consoleObj);
    
    // ========== РЕГИСТРАЦИЯ C++ КЛАССОВ ==========
    
    // 1. Calculator - создаем экземпляр
    Calculator calculator;
    QJSValue calcObj = engine.newQObject(&calculator);
    engine.globalObject().setProperty("Calculator", calcObj);
    
    // 2. FileManager - создаем экземпляр
    FileManager fileManager;
    QJSValue fileManagerObj = engine.newQObject(&fileManager);
    engine.globalObject().setProperty("FileManager", fileManagerObj);
    
    // 3. Counter - создаем экземпляр (будет доступен как глобальный объект)
    Counter counter;
    QJSValue counterObj = engine.newQObject(&counter);
    engine.globalObject().setProperty("counter", counterObj);
    
    // 4. SystemInfo - регистрируем как синглтон
    SystemInfo systemInfo;
    QJSValue systemInfoObj = engine.newQObject(&systemInfo);
    engine.globalObject().setProperty("System", systemInfoObj);
    
    // 5. Регистрируем вспомогательные функции
    QJSValue waitFunc = engine.evaluate(
        "(function(ms) {"
        "    var start = Date.now();"
        "    while (Date.now() - start < ms) {}"
        "})"
    );
    engine.globalObject().setProperty("wait", waitFunc);
    
    bool executed = false;
    
    // Пример кода для демонстрации, если не передан файл
    QString demoCode = R"(
print("=== DEMONSTRATION OF C++ METHODS CALL FROM JavaScript ===");

// 1. Использование Calculator
print("\n1. Calculator methods:");
var sum = Calculator.add(5, 3);
print("Calculator.add(5, 3) =", sum);

var product = Calculator.multiply(2.5, 4.0);
print("Calculator.multiply(2.5, 4.0) =", product);

var greeting = Calculator.greet("World");
print("Calculator.greet('World') =", greeting);

// Работа с массивами
var inputArray = [1, 2, 3, 4, 5];
var resultArray = Calculator.processArray(inputArray);
print("Calculator.processArray([1,2,3,4,5]) =", JSON.stringify(resultArray));

// 2. Использование FileManager
print("\n2. FileManager methods (demo):");
var fileSize = FileManager.getFileSize("main.cpp");
print("FileManager.getFileSize('main.cpp') =", fileSize, "bytes");

// 3. Использование Counter с состоянием
print("\n3. Counter with state:");
print("Initial counter value:", counter.value);
counter.increment();
print("After increment:", counter.value);
counter.increment();
print("After another increment:", counter.value);
counter.decrement();
print("After decrement:", counter.value);
counter.reset();
print("After reset:", counter.value);

// 4. Использование SystemInfo
print("\n4. System information:");
print("Current datetime:", System.currentDateTime());
print("Timestamp:", System.timestamp());
print("Platform:", System.platform());

// 5. Комбинированный пример
print("\n5. Combined example:");
var calculated = Calculator.add(
    Calculator.multiply(2, 3),
    Calculator.multiply(4, 5)
);
print("Complex calculation result:", calculated);

print("\n=== DEMONSTRATION COMPLETE ===");
)";
    
    if (parser.isSet(evalOption)) {
        QString code = parser.value(evalOption);
        QJSValue result = engine.evaluate(code);
        
        if (result.isError()) {
            qCritical() << "Error:" << result.toString();
            return 1;
        }
        executed = true;
    }
    
    const QStringList args = parser.positionalArguments();
    if (!args.isEmpty() && !executed) {
        QString fileName = args.first();
        QFile file(fileName);
        
        if (!file.open(QIODevice::ReadOnly | QIODevice::Text)) {
            qCritical() << "Cannot open file:" << fileName;
            return 1;
        }
        
        QTextStream stream(&file);
        stream.setEncoding(QStringConverter::Utf8);
        QString code = stream.readAll();
        file.close();
        
        QJSValue result = engine.evaluate(code, fileName);
        
        if (result.isError()) {
            qCritical() << "Error at line" << result.property("lineNumber").toInt()
                       << ":" << result.toString();
            return 1;
        }
        executed = true;
    }
    
    if (!executed) {
        // Запускаем демонстрационный код
        qDebug() << "No script specified, running demonstration...";
        QJSValue result = engine.evaluate(demoCode);
        
        if (result.isError()) {
            qCritical() << "Error:" << result.toString();
            return 1;
        }
    }
    
    return 0;
}

#include "main.moc"
```

**Пример файла `test.js` для тестирования:**
```javascript
// test.js - пример использования C++ методов из JavaScript

print("=== JavaScript File Execution ===");

// 1. Асинхронное взаимодействие (симуляция)
function delayedCalculation() {
    print("\nPerforming delayed calculation...");
    wait(100); // имитация задержки
    
    var a = 10;
    var b = 20;
    var result = Calculator.add(a, b);
    print(`Calculator.add(${a}, ${b}) = ${result}`);
    
    return result;
}

// 2. Работа с файлами
print("\n--- File Operations ---");
try {
    var content = FileManager.readFile("test.js");
    print("File size:", content.length, "characters");
    print("First 100 chars:", content.substring(0, 100) + "...");
} catch(e) {
    print("File read error:", e);
}

// 3. Состояние counter
print("\n--- Counter State Manipulation ---");
print("Starting value:", counter.value);

// Цикл с использованием C++ методов
for (var i = 0; i < 3; i++) {
    counter.increment();
    print(`After increment ${i + 1}:`, counter.value);
}

// 4. Системная информация
print("\n--- System Information ---");
print("Script started at:", System.currentDateTime());

// 5. Обработка массивов через C++
print("\n--- Array Processing ---");
var testArray = [10, 20, 30, 40, 50];
print("Original array:", JSON.stringify(testArray));

var processed = Calculator.processArray(testArray);
print("Processed array (x2):", JSON.stringify(processed));

// 6. Сложные вычисления
print("\n--- Complex Calculations ---");
var complexResult = Calculator.multiply(
    Calculator.add(5, 3),
    Calculator.add(2, 4)
);
print("(5 + 3) * (2 + 4) =", complexResult);

// 7. Использование в функциях JavaScript
function calculateCircleArea(radius) {
    var squared = Calculator.multiply(radius, radius);
    return Calculator.multiply(Math.PI, squared);
}

var radius = 5;
print(`\nArea of circle with radius ${radius}:`, calculateCircleArea(radius));

// 8. Проверка типов возвращаемых значений
print("\n--- Return Type Checking ---");
var intResult = Calculator.add(1, 2);
var doubleResult = Calculator.multiply(1.5, 2.5);
var stringResult = Calculator.greet("Developer");

print("add returns:", typeof intResult, "value:", intResult);
print("multiply returns:", typeof doubleResult, "value:", doubleResult);
print("greet returns:", typeof stringResult, "value:", stringResult);

print("\n=== Script Execution Complete ===");

// Возвращаем результат в C++
"Script executed successfully. Final counter value: " + counter.value;
```

**Ключевые моменты реализации:**

1. **Q_INVOKABLE** - макрос для объявления методов, доступных из JavaScript
2. **newQObject()** - создание JavaScript обертки для C++ объекта
3. **Типы возвращаемых значений:**
   - `int`, `double` - преобразуются в JavaScript числа
   - `QString` - преобразуется в JavaScript строку
   - `QVariantList` - преобразуется в JavaScript массив
   - `bool` - преобразуется в JavaScript boolean

4. **Q_PROPERTY** - для свойств с уведомлениями (как в Counter)

5. **Сигналы и слоты** - также могут быть доступны в JavaScript

**Компиляция и запуск:**
```bash
# Создаем test.js с примером кода выше
nano test.js

# Компилируем
qmake && make

# Запускаем демонстрацию
./jsrunner

# Запускаем тестовый скрипт
./jsrunner test.js

# Или выполняем код напрямую
./jsrunner -e "print('2 + 2 =', Calculator.add(2, 2));"
```

Этот пример показывает полную интеграцию между C++ и JavaScript, позволяя вызывать методы C++ классов, работать с возвращаемыми значениями и даже передавать сложные структуры данных.