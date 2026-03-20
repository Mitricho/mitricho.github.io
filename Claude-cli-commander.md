

# Динамический GUI на основе JSON для Ollama

## Структура проекта

```
project/
├── CMakeLists.txt
├── ollama_commands.json
├── main.cpp
├── dynamicguibuilder.h
└── dynamicguibuilder.cpp
```

## JSON файл с описанием команд Ollama

```json
{
  "application": "ollama",
  "description": "Ollama CLI command builder",
  "version": "1.0",
  "elements": [
    {
      "id": "command",
      "type": "combobox",
      "label": "Command:",
      "editable": false,
      "items": [
        {
          "text": "run",
          "description": "Run a model"
        },
        {
          "text": "pull",
          "description": "Pull a model from registry"
        },
        {
          "text": "push",
          "description": "Push a model to registry"
        },
        {
          "text": "list",
          "description": "List available models"
        },
        {
          "text": "ps",
          "description": "List running models"
        },
        {
          "text": "create",
          "description": "Create a model from Modelfile"
        },
        {
          "text": "show",
          "description": "Show model information"
        },
        {
          "text": "cp",
          "description": "Copy a model"
        },
        {
          "text": "rm",
          "description": "Remove a model"
        },
        {
          "text": "serve",
          "description": "Start ollama server"
        },
        {
          "text": "stop",
          "description": "Stop a running model"
        },
        {
          "text": "help",
          "description": "Show help"
        }
      ],
      "default": "run"
    },
    {
      "id": "model",
      "type": "combobox",
      "label": "Model:",
      "editable": true,
      "placeholder": "Enter model name or select...",
      "items": [
        {
          "text": "llama3.1:8b",
          "description": "Meta LLaMA 3.1 8B"
        },
        {
          "text": "llama3.1:70b",
          "description": "Meta LLaMA 3.1 70B"
        },
        {
          "text": "deepseek-r1:7b",
          "description": "DeepSeek R1 7B"
        },
        {
          "text": "deepseek-r1:14b",
          "description": "DeepSeek R1 14B"
        },
        {
          "text": "qwen3:8b",
          "description": "Alibaba Qwen 3 8B"
        },
        {
          "text": "qwen3:14b",
          "description": "Alibaba Qwen 3 14B"
        },
        {
          "text": "gemma3:9b",
          "description": "Google Gemma 3 9B"
        },
        {
          "text": "mistral:7b",
          "description": "Mistral 7B"
        },
        {
          "text": "phi4:14b",
          "description": "Microsoft Phi-4 14B"
        },
        {
          "text": "minimax-m1:40b",
          "description": "MiniMax M1 40B"
        }
      ],
      "default": "llama3.1:8b"
    },
    {
      "id": "options",
      "type": "group",
      "label": "Options:",
      "children": [
        {
          "id": "verbose",
          "type": "checkbox",
          "label": "--verbose",
          "flag": "--verbose",
          "default": false
        },
        {
          "id": "nowordwrap",
          "type": "checkbox",
          "label": "--nowordwrap",
          "flag": "--nowordwrap",
          "default": false
        },
        {
          "id": "insecure",
          "type": "checkbox",
          "label": "--insecure",
          "flag": "--insecure",
          "default": false
        },
        {
          "id": "keepalive",
          "type": "lineedit",
          "label": "--keepalive",
          "flag": "--keepalive",
          "placeholder": "e.g. 5m, 1h",
          "default": ""
        },
        {
          "id": "format",
          "type": "combobox",
          "label": "--format",
          "flag": "--format",
          "editable": false,
          "items": [
            { "text": "", "description": "Default (none)" },
            { "text": "json", "description": "JSON output" }
          ],
          "default": ""
        }
      ]
    },
    {
      "id": "prompt",
      "type": "lineedit",
      "label": "Prompt:",
      "placeholder": "Enter your prompt here...",
      "default": ""
    }
  ],
  "command_template": "{application} {command} {model} {options} {prompt}"
}
```

## Заголовочный файл

```cpp
// dynamicguibuilder.h
#ifndef DYNAMICGUIBUILDER_H
#define DYNAMICGUIBUILDER_H

#include <QWidget>
#include <QJsonDocument>
#include <QJsonObject>
#include <QJsonArray>
#include <QHBoxLayout>
#include <QVBoxLayout>
#include <QComboBox>
#include <QCheckBox>
#include <QLineEdit>
#include <QLabel>
#include <QPushButton>
#include <QGroupBox>
#include <QMap>
#include <QVariant>
#include <functional>

///
/// @brief Элемент GUI, созданный из JSON-описания.
///        Хранит виджет, его тип, идентификатор и флаг командной строки.
///
struct GuiElement
{
    QString id;
    QString type;
    QString flag;        // CLI-флаг (например "--verbose")
    QWidget *widget = nullptr;

    /// Возвращает текущее значение элемента в виде строки
    QString currentValue() const;

    /// Элемент несёт смысловое значение (не пустое)?
    bool hasValue() const;
};

///
/// @class DynamicGuiBuilder
/// @brief Строит интерфейс из JSON-описания и добавляет его указанному родителю.
///
/// Основной контейнер — горизонтальный QHBoxLayout.
/// Каждый элемент JSON-описания превращается в контрол (QComboBox, QCheckBox,
/// QLineEdit) и помещается слева направо.
///
/// После взаимодействия пользователя метод buildCommandString() возвращает
/// готовую строку команды.
///
class DynamicGuiBuilder : public QObject
{
    Q_OBJECT

public:
    /// Конструирует builder и размещает GUI внутри @p parentWidget.
    /// @param parentWidget — виджет-контейнер, получит QHBoxLayout с контролами.
    /// @param parent       — QObject-родитель для цепочки владения.
    explicit DynamicGuiBuilder(QWidget *parentWidget, QObject *parent = nullptr);
    ~DynamicGuiBuilder() override = default;

    // ── Загрузка ──────────────────────────────────────────────

    /// Загружает JSON из файла на диске и строит GUI.
    /// @return true при успешной загрузке и построении.
    bool loadFromFile(const QString &filePath);

    /// Загружает JSON из готового QJsonDocument.
    bool loadFromJson(const QJsonDocument &doc);

    // ── Результат ─────────────────────────────────────────────

    /// Собирает и возвращает полную строку команды
    /// на основе текущего состояния контролов.
    QString buildCommandString() const;

    /// Возвращает значение конкретного элемента по его id.
    QString elementValue(const QString &id) const;

    /// Возвращает список всех id загруженных элементов.
    QStringList elementIds() const;

signals:
    /// Излучается при изменении любого контрола.
    void commandChanged(const QString &command);

    /// Излучается при ошибке загрузки/парсинга.
    void errorOccurred(const QString &errorMessage);

private:
    // ── Построение элементов ──────────────────────────────────

    void buildGui(const QJsonObject &root);

    /// Рекурсивно создаёт элементы из JSON-массива "elements"
    /// и добавляет их в указанный layout.
    void createElements(const QJsonArray &elements, QBoxLayout *targetLayout);

    /// Фабричные методы для конкретных типов контролов.
    GuiElement createComboBox(const QJsonObject &obj);
    GuiElement createCheckBox(const QJsonObject &obj);
    GuiElement createLineEdit(const QJsonObject &obj);
    GuiElement createGroup(const QJsonObject &obj, QBoxLayout *parentLayout);

    /// Подключает сигналы виджета для отслеживания изменений.
    void connectElement(const GuiElement &elem);

    /// Слот-обработчик: пересобирает строку и шлёт сигнал.
    void onAnyElementChanged();

    // ── Данные ────────────────────────────────────────────────

    QWidget        *m_parentWidget  = nullptr;
    QHBoxLayout    *m_mainLayout    = nullptr;
    QJsonObject     m_root;
    QString         m_application;
    QString         m_commandTemplate;

    /// Все элементы по их id (плоская карта, включая вложенные).
    QMap<QString, GuiElement> m_elements;

    /// Порядок id для воспроизводимой сборки строки.
    QStringList m_elementOrder;
};

#endif // DYNAMICGUIBUILDER_H
```

## Реализация

```cpp
// dynamicguibuilder.cpp
#include "dynamicguibuilder.h"

#include <QFile>
#include <QJsonParseError>
#include <QDebug>
#include <QToolTip>

// ═══════════════════════════════════════════════════════════════
//  GuiElement
// ═══════════════════════════════════════════════════════════════

QString GuiElement::currentValue() const
{
    if (!widget)
        return {};

    if (type == QStringLiteral("combobox")) {
        auto *cb = qobject_cast<QComboBox *>(widget);
        if (!cb) return {};
        // Для editable-комбобокса берём текст из lineEdit,
        // иначе — currentText
        return cb->isEditable() ? cb->currentText().trimmed()
                                : cb->currentText();
    }

    if (type == QStringLiteral("checkbox")) {
        auto *chk = qobject_cast<QCheckBox *>(widget);
        if (!chk) return {};
        return chk->isChecked() ? flag : QString();
    }

    if (type == QStringLiteral("lineedit")) {
        auto *le = qobject_cast<QLineEdit *>(widget);
        if (!le) return {};
        return le->text().trimmed();
    }

    return {};
}

bool GuiElement::hasValue() const
{
    return !currentValue().isEmpty();
}

// ═══════════════════════════════════════════════════════════════
//  DynamicGuiBuilder — public
// ═══════════════════════════════════════════════════════════════

DynamicGuiBuilder::DynamicGuiBuilder(QWidget *parentWidget, QObject *parent)
    : QObject(parent)
    , m_parentWidget(parentWidget)
{
    Q_ASSERT(parentWidget);
}

bool DynamicGuiBuilder::loadFromFile(const QString &filePath)
{
    QFile file(filePath);
    if (!file.open(QIODevice::ReadOnly | QIODevice::Text)) {
        const QString msg = QStringLiteral("Cannot open file: %1").arg(filePath);
        qWarning() << msg;
        emit errorOccurred(msg);
        return false;
    }

    QJsonParseError parseError;
    QJsonDocument doc = QJsonDocument::fromJson(file.readAll(), &parseError);
    file.close();

    if (parseError.error != QJsonParseError::NoError) {
        const QString msg = QStringLiteral("JSON parse error at offset %1: %2")
                                .arg(parseError.offset)
                                .arg(parseError.errorString());
        qWarning() << msg;
        emit errorOccurred(msg);
        return false;
    }

    return loadFromJson(doc);
}

bool DynamicGuiBuilder::loadFromJson(const QJsonDocument &doc)
{
    if (!doc.isObject()) {
        emit errorOccurred(QStringLiteral("JSON root is not an object"));
        return false;
    }

    // Очистим предыдущий GUI, если был
    if (m_mainLayout) {
        QLayoutItem *item;
        while ((item = m_mainLayout->takeAt(0)) != nullptr) {
            if (item->widget())
                item->widget()->deleteLater();
            if (item->layout())
                delete item->layout();
            delete item;
        }
        delete m_mainLayout;
        m_mainLayout = nullptr;
    }
    m_elements.clear();
    m_elementOrder.clear();

    m_root = doc.object();
    buildGui(m_root);
    return true;
}

QString DynamicGuiBuilder::buildCommandString() const
{
    // Если есть шаблон — используем его
    if (!m_commandTemplate.isEmpty()) {
        QString result = m_commandTemplate;

        // Подставляем {application}
        result.replace(QStringLiteral("{application}"), m_application);

        // Собираем options (все checkbox и lineedit с flag)
        QStringList optionParts;
        for (const QString &id : m_elementOrder) {
            const GuiElement &elem = m_elements[id];
            if (elem.flag.isEmpty())
                continue;
            if (elem.type == QStringLiteral("checkbox")) {
                if (elem.hasValue())
                    optionParts << elem.currentValue();
            } else if (elem.type == QStringLiteral("lineedit") && !elem.flag.isEmpty()) {
                QString val = elem.currentValue();
                if (!val.isEmpty())
                    optionParts << elem.flag << val;
            } else if (elem.type == QStringLiteral("combobox") && !elem.flag.isEmpty()) {
                QString val = elem.currentValue();
                if (!val.isEmpty())
                    optionParts << elem.flag << val;
            }
        }
        result.replace(QStringLiteral("{options}"), optionParts.join(QLatin1Char(' ')));

        // Подставляем каждый именованный элемент (кроме options)
        for (const QString &id : m_elementOrder) {
            const QString placeholder = QStringLiteral("{%1}").arg(id);
            if (result.contains(placeholder)) {
                const GuiElement &elem = m_elements[id];
                result.replace(placeholder, elem.currentValue());
            }
        }

        // Чистим лишние пробелы
        return result.simplified();
    }

    // Без шаблона — просто склеиваем всё по порядку
    QStringList parts;
    if (!m_application.isEmpty())
        parts << m_application;

    for (const QString &id : m_elementOrder) {
        const GuiElement &elem = m_elements[id];
        if (!elem.hasValue())
            continue;

        if (elem.type == QStringLiteral("checkbox")) {
            parts << elem.currentValue(); // сам flag
        } else if (!elem.flag.isEmpty()) {
            parts << elem.flag << elem.currentValue();
        } else {
            // prompt — если содержит пробелы, обернуть в кавычки
            QString val = elem.currentValue();
            if (val.contains(QLatin1Char(' ')))
                val = QStringLiteral("\"%1\"").arg(val);
            parts << val;
        }
    }

    return parts.join(QLatin1Char(' ')).simplified();
}

QString DynamicGuiBuilder::elementValue(const QString &id) const
{
    auto it = m_elements.constFind(id);
    if (it == m_elements.constEnd())
        return {};
    return it->currentValue();
}

QStringList DynamicGuiBuilder::elementIds() const
{
    return m_elementOrder;
}

// ═══════════════════════════════════════════════════════════════
//  DynamicGuiBuilder — private
// ═══════════════════════════════════════════════════════════════

void DynamicGuiBuilder::buildGui(const QJsonObject &root)
{
    m_application    = root.value(QStringLiteral("application")).toString();
    m_commandTemplate = root.value(QStringLiteral("command_template")).toString();

    // Создаём главный горизонтальный layout
    m_mainLayout = new QHBoxLayout;
    m_mainLayout->setContentsMargins(4, 4, 4, 4);
    m_mainLayout->setSpacing(8);

    // Если у parentWidget уже есть layout — удалим
    if (m_parentWidget->layout()) {
        delete m_parentWidget->layout();
    }
    m_parentWidget->setLayout(m_mainLayout);

    // Парсим элементы
    QJsonArray elements = root.value(QStringLiteral("elements")).toArray();
    createElements(elements, m_mainLayout);

    // Растягивающий спейсер в конце
    m_mainLayout->addStretch(1);
}

void DynamicGuiBuilder::createElements(const QJsonArray &elements,
                                        QBoxLayout *targetLayout)
{
    for (const QJsonValue &val : elements) {
        if (!val.isObject())
            continue;

        QJsonObject obj = val.toObject();
        QString type = obj.value(QStringLiteral("type")).toString().toLower();
        QString label = obj.value(QStringLiteral("label")).toString();

        GuiElement elem;

        if (type == QStringLiteral("combobox")) {
            elem = createComboBox(obj);
        } else if (type == QStringLiteral("checkbox")) {
            elem = createCheckBox(obj);
        } else if (type == QStringLiteral("lineedit")) {
            elem = createLineEdit(obj);
        } else if (type == QStringLiteral("group")) {
            elem = createGroup(obj, targetLayout);
            // group уже добавлен в layout внутри createGroup
            if (!elem.id.isEmpty()) {
                // Для group не добавляем в m_elements отдельно,
                // дочерние элементы уже добавлены
            }
            continue;
        } else {
            qWarning() << "Unknown element type:" << type;
            continue;
        }

        if (elem.widget) {
            // Добавляем label + widget как пару в вертикальный мини-layout
            if (!label.isEmpty()) {
                QVBoxLayout *vbox = new QVBoxLayout;
                vbox->setSpacing(2);
                vbox->setContentsMargins(0, 0, 0, 0);

                QLabel *lbl = new QLabel(label, m_parentWidget);
                QFont f = lbl->font();
                f.setPointSize(f.pointSize() - 1);
                f.setBold(true);
                lbl->setFont(f);

                vbox->addWidget(lbl);
                vbox->addWidget(elem.widget);
                targetLayout->addLayout(vbox);
            } else {
                targetLayout->addWidget(elem.widget);
            }

            m_elements.insert(elem.id, elem);
            m_elementOrder.append(elem.id);
            connectElement(elem);
        }
    }
}

GuiElement DynamicGuiBuilder::createComboBox(const QJsonObject &obj)
{
    GuiElement elem;
    elem.id   = obj.value(QStringLiteral("id")).toString();
    elem.type = QStringLiteral("combobox");
    elem.flag = obj.value(QStringLiteral("flag")).toString();

    auto *cb = new QComboBox(m_parentWidget);
    cb->setObjectName(elem.id);
    cb->setSizeAdjustPolicy(QComboBox::AdjustToContents);
    cb->setMinimumWidth(120);

    bool editable = obj.value(QStringLiteral("editable")).toBool(false);
    cb->setEditable(editable);

    if (editable) {
        QString ph = obj.value(QStringLiteral("placeholder")).toString();
        if (!ph.isEmpty())
            cb->lineEdit()->setPlaceholderText(ph);
    }

    // Заполняем items
    QJsonArray items = obj.value(QStringLiteral("items")).toArray();
    for (const QJsonValue &itemVal : items) {
        QJsonObject itemObj = itemVal.toObject();
        QString text = itemObj.value(QStringLiteral("text")).toString();
        QString desc = itemObj.value(QStringLiteral("description")).toString();

        cb->addItem(text);
        if (!desc.isEmpty()) {
            int idx = cb->count() - 1;
            cb->setItemData(idx, desc, Qt::ToolTipRole);
        }
    }

    // Default
    QString def = obj.value(QStringLiteral("default")).toString();
    if (!def.isEmpty()) {
        int idx = cb->findText(def);
        if (idx >= 0)
            cb->setCurrentIndex(idx);
        else if (editable)
            cb->setEditText(def);
    }

    elem.widget = cb;
    return elem;
}

GuiElement DynamicGuiBuilder::createCheckBox(const QJsonObject &obj)
{
    GuiElement elem;
    elem.id   = obj.value(QStringLiteral("id")).toString();
    elem.type = QStringLiteral("checkbox");
    elem.flag = obj.value(QStringLiteral("flag")).toString();

    auto *chk = new QCheckBox(obj.value(QStringLiteral("label")).toString(),
                              m_parentWidget);
    chk->setObjectName(elem.id);
    chk->setChecked(obj.value(QStringLiteral("default")).toBool(false));

    elem.widget = chk;
    return elem;
}

GuiElement DynamicGuiBuilder::createLineEdit(const QJsonObject &obj)
{
    GuiElement elem;
    elem.id   = obj.value(QStringLiteral("id")).toString();
    elem.type = QStringLiteral("lineedit");
    elem.flag = obj.value(QStringLiteral("flag")).toString();

    auto *le = new QLineEdit(m_parentWidget);
    le->setObjectName(elem.id);
    le->setMinimumWidth(150);

    QString ph = obj.value(QStringLiteral("placeholder")).toString();
    if (!ph.isEmpty())
        le->setPlaceholderText(ph);

    QString def = obj.value(QStringLiteral("default")).toString();
    if (!def.isEmpty())
        le->setText(def);

    elem.widget = le;
    return elem;
}

GuiElement DynamicGuiBuilder::createGroup(const QJsonObject &obj,
                                           QBoxLayout *parentLayout)
{
    GuiElement elem;
    elem.id   = obj.value(QStringLiteral("id")).toString();
    elem.type = QStringLiteral("group");

    QString label = obj.value(QStringLiteral("label")).toString();

    // Создаём QGroupBox с вложенным горизонтальным layout
    auto *groupBox = new QGroupBox(label, m_parentWidget);
    groupBox->setObjectName(elem.id);

    auto *innerLayout = new QHBoxLayout;
    innerLayout->setSpacing(6);
    innerLayout->setContentsMargins(6, 4, 6, 4);
    groupBox->setLayout(innerLayout);

    // Рекурсивно создаём дочерние элементы внутри группы
    QJsonArray children = obj.value(QStringLiteral("children")).toArray();
    createElements(children, innerLayout);

    parentLayout->addWidget(groupBox);

    elem.widget = groupBox;
    return elem;
}

void DynamicGuiBuilder::connectElement(const GuiElement &elem)
{
    if (!elem.widget)
        return;

    if (elem.type == QStringLiteral("combobox")) {
        auto *cb = qobject_cast<QComboBox *>(elem.widget);
        if (cb) {
            connect(cb, &QComboBox::currentIndexChanged,
                    this, &DynamicGuiBuilder::onAnyElementChanged);
            if (cb->isEditable()) {
                connect(cb->lineEdit(), &QLineEdit::textEdited,
                        this, &DynamicGuiBuilder::onAnyElementChanged);
            }
        }
    } else if (elem.type == QStringLiteral("checkbox")) {
        auto *chk = qobject_cast<QCheckBox *>(elem.widget);
        if (chk) {
            connect(chk, &QCheckBox::toggled,
                    this, &DynamicGuiBuilder::onAnyElementChanged);
        }
    } else if (elem.type == QStringLiteral("lineedit")) {
        auto *le = qobject_cast<QLineEdit *>(elem.widget);
        if (le) {
            connect(le, &QLineEdit::textChanged,
                    this, &DynamicGuiBuilder::onAnyElementChanged);
        }
    }
}

void DynamicGuiBuilder::onAnyElementChanged()
{
    emit commandChanged(buildCommandString());
}
```

## main.cpp — демонстрация

```cpp
// main.cpp
#include <QApplication>
#include <QMainWindow>
#include <QVBoxLayout>
#include <QLabel>
#include <QPushButton>
#include <QClipboard>
#include <QMessageBox>
#include <QStatusBar>
#include <QFont>

#include "dynamicguibuilder.h"

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);
    app.setApplicationName(QStringLiteral("Ollama Command Builder"));

    // ── Главное окно ──────────────────────────────────────────
    QMainWindow mainWindow;
    mainWindow.setWindowTitle(QStringLiteral("Ollama Dynamic GUI Builder"));
    mainWindow.setMinimumSize(1000, 220);

    auto *centralWidget = new QWidget;
    auto *mainVBox = new QVBoxLayout(centralWidget);
    mainVBox->setSpacing(12);
    mainVBox->setContentsMargins(12, 12, 12, 12);

    // ── Контейнер для динамического GUI ───────────────────────
    auto *guiContainer = new QWidget;
    guiContainer->setStyleSheet(
        QStringLiteral("QGroupBox { font-weight: bold; }"
                        "QComboBox { min-height: 24px; }"
                        "QLineEdit { min-height: 24px; }"));

    mainVBox->addWidget(guiContainer);

    // ── Метка с результатом ───────────────────────────────────
    auto *resultLabel = new QLabel;
    resultLabel->setWordWrap(true);
    resultLabel->setTextInteractionFlags(Qt::TextSelectableByMouse);
    resultLabel->setStyleSheet(
        QStringLiteral("QLabel {"
                        "  background-color: #1e1e2e;"
                        "  color: #a6e3a1;"
                        "  border: 1px solid #585b70;"
                        "  border-radius: 6px;"
                        "  padding: 10px;"
                        "  font-family: 'Consolas', 'Courier New', monospace;"
                        "  font-size: 14px;"
                        "}"));
    resultLabel->setMinimumHeight(50);

    auto *resultTitle = new QLabel(QStringLiteral("Generated command:"));
    QFont titleFont = resultTitle->font();
    titleFont.setBold(true);
    resultTitle->setFont(titleFont);

    mainVBox->addWidget(resultTitle);
    mainVBox->addWidget(resultLabel);

    // ── Кнопки ────────────────────────────────────────────────
    auto *btnLayout = new QHBoxLayout;

    auto *copyBtn = new QPushButton(QStringLiteral("📋 Copy to Clipboard"));
    copyBtn->setMinimumHeight(32);

    auto *runBtn = new QPushButton(QStringLiteral("▶ Show Command"));
    runBtn->setMinimumHeight(32);

    btnLayout->addStretch();
    btnLayout->addWidget(copyBtn);
    btnLayout->addWidget(runBtn);
    mainVBox->addLayout(btnLayout);

    mainVBox->addStretch();

    mainWindow.setCentralWidget(centralWidget);
    mainWindow.statusBar()->showMessage(QStringLiteral("Ready"));

    // ── Создаём builder и загружаем JSON ──────────────────────
    auto *builder = new DynamicGuiBuilder(guiContainer, &mainWindow);

    // Обновляем метку при каждом изменении
    QObject::connect(builder, &DynamicGuiBuilder::commandChanged,
                     resultLabel, [resultLabel](const QString &cmd) {
                         resultLabel->setText(QStringLiteral("$ ") + cmd);
                     });

    QObject::connect(builder, &DynamicGuiBuilder::errorOccurred,
                     &mainWindow, [&mainWindow](const QString &err) {
                         QMessageBox::warning(&mainWindow,
                                              QStringLiteral("Error"), err);
                     });

    // Кнопка копирования
    QObject::connect(copyBtn, &QPushButton::clicked,
                     &mainWindow, [builder, &mainWindow]() {
                         QString cmd = builder->buildCommandString();
                         QApplication::clipboard()->setText(cmd);
                         mainWindow.statusBar()->showMessage(
                             QStringLiteral("Command copied to clipboard!"), 3000);
                     });

    // Кнопка показа
    QObject::connect(runBtn, &QPushButton::clicked,
                     &mainWindow, [builder, &mainWindow]() {
                         QString cmd = builder->buildCommandString();
                         QMessageBox::information(&mainWindow,
                                                  QStringLiteral("Full Command"),
                                                  cmd);
                     });

    // Загрузка JSON
    if (!builder->loadFromFile(QStringLiteral("ollama_commands.json"))) {
        qCritical() << "Failed to load ollama_commands.json!";
        // Покажем ошибку, но не завершим приложение
    }

    // Обновить начальное значение
    resultLabel->setText(QStringLiteral("$ ") + builder->buildCommandString());

    mainWindow.show();
    return app.exec();
}
```

## CMakeLists.txt

```cmake
cmake_minimum_required(VERSION 3.19)
project(OllamaDynamicGui VERSION 1.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_AUTOMOC ON)
set(CMAKE_AUTORCC ON)
set(CMAKE_AUTOUIC ON)

find_package(Qt6 6.5 REQUIRED COMPONENTS Core Widgets)

add_executable(${PROJECT_NAME}
    main.cpp
    dynamicguibuilder.h
    dynamicguibuilder.cpp
)

target_link_libraries(${PROJECT_NAME} PRIVATE
    Qt6::Core
    Qt6::Widgets
)

# Копируем JSON рядом с бинарником
configure_file(
    ${CMAKE_CURRENT_SOURCE_DIR}/ollama_commands.json
    ${CMAKE_CURRENT_BINARY_DIR}/ollama_commands.json
    COPYONLY
)
```

## Как это работает — схема

```
┌─────────────────────────────────────────────────────────────────────┐
│  QWidget (guiContainer) ← QHBoxLayout                              │
│                                                                     │
│  ┌─────────┐ ┌──────────────┐ ┌─────────────────────┐ ┌──────────┐│
│  │Command: │ │Model:        │ │ Options:   [group]   │ │Prompt:   ││
│  │┌───────┐│ │┌────────────┐│ │┌───┐┌───┐┌───┐┌────┐│ │┌────────┐││
│  ││ run ▼ ││ ││llama3.1 ▼ ││ ││☐v ││☐n ││☐i ││keep││ ││ text   │││
│  │└───────┘│ │└────────────┘│ │└───┘└───┘└───┘└────┘│ │└────────┘││
│  └─────────┘ │ или свой ↑   │ └─────────────────────┘ └──────────┘│
│              └──────────────┘                                       │
└─────────────────────────────────────────────────────────────────────┘
                         │
                         ▼
    ┌─────────────────────────────────────────────────────────┐
    │  $ ollama run llama3.1:8b --verbose "Hello world"       │
    └─────────────────────────────────────────────────────────┘
```

## Ключевые особенности

| Особенность | Реализация |
|---|---|
| **Editable ComboBox** | Модели — `"editable": true`, пользователь может вписать свою модель |
| **Template-based** | Строка команды формируется по шаблону `{application} {command} {model} {options} {prompt}` |
| **Рекурсивные группы** | `"type": "group"` создаёт QGroupBox с вложенным layout |
| **Live update** | Каждое изменение контрола сразу обновляет результат через сигнал `commandChanged` |
| **Tooltips** | У каждого пункта ComboBox есть description, который показывается как tooltip |
| **Расширяемость** | Достаточно добавить новый тип в JSON и фабричный метод `create*()` |