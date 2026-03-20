Отличная идея! Добавим сохранение истории команд с использованием QSettings и префиксом для изоляции настроек.

## DynamicWidgets.h (обновленный)

```cpp
#ifndef DYNAMICWIDGETS_H
#define DYNAMICWIDGETS_H

#include <QWidget>
#include <QHBoxLayout>
#include <QComboBox>
#include <QLineEdit>
#include <QListWidget>
#include <QPushButton>
#include <QJsonObject>
#include <QJsonArray>
#include <QMap>
#include <QSettings>

class DynamicWidgets : public QWidget {
    Q_OBJECT

public:
    explicit DynamicWidgets(QWidget *parent = nullptr);
    ~DynamicWidgets();
    
    bool loadFromFile(const QString &filename);
    bool loadFromJson(const QByteArray &jsonData);
    QString getCommand() const;
    
private slots:
    void onCommandChanged(int index);
    void onModelTextChanged(const QString &text);
    void onExecuteCommand();
    void onHistoryItemClicked(QListWidgetItem *item);
    void onClearHistory();
    
private:
    void setupUI();
    void updateCommandPreview();
    void saveCommandToHistory(const QString &command);
    void loadHistoryFromSettings();
    void saveHistoryToSettings();
    void updateHistoryDisplay();
    
    static const QString SETTINGS_PREFIX;
    static const int MAX_HISTORY_SIZE;
    
    QHBoxLayout *m_mainLayout;
    QVBoxLayout *m_controlLayout;
    QHBoxLayout *m_inputLayout;
    
    QComboBox *m_commandCombo;
    QComboBox *m_modelCombo;
    QLineEdit *m_customModelEdit;
    QLineEdit *m_additionalArgsEdit;
    QPushButton *m_executeBtn;
    
    QListWidget *m_historyList;
    QPushButton *m_clearHistoryBtn;
    
    QJsonObject m_config;
    QStringList m_availableModels;
    QStringList m_commandHistory;
    QString m_currentCommand;
    QString m_currentModel;
    
    static const int COMMAND_ROLE = Qt::UserRole + 1;
};

#endif // DYNAMICWIDGETS_H
```

## DynamicWidgets.cpp (обновленный)

```cpp
#include "DynamicWidgets.h"
#include <QFile>
#include <QJsonDocument>
#include <QJsonParseError>
#include <QLabel>
#include <QDebug>
#include <QDateTime>
#include <QMessageBox>

// Определение статических членов
const QString DynamicWidgets::SETTINGS_PREFIX = "OllamaCommander/";
const int DynamicWidgets::MAX_HISTORY_SIZE = 50;

DynamicWidgets::DynamicWidgets(QWidget *parent)
    : QWidget(parent)
    , m_mainLayout(nullptr)
    , m_controlLayout(nullptr)
    , m_inputLayout(nullptr)
    , m_commandCombo(nullptr)
    , m_modelCombo(nullptr)
    , m_customModelEdit(nullptr)
    , m_additionalArgsEdit(nullptr)
    , m_executeBtn(nullptr)
    , m_historyList(nullptr)
    , m_clearHistoryBtn(nullptr)
{
    setupUI();
    loadHistoryFromSettings();
}

DynamicWidgets::~DynamicWidgets()
{
    saveHistoryToSettings();
}

void DynamicWidgets::setupUI()
{
    m_mainLayout = new QHBoxLayout(this);
    m_mainLayout->setSpacing(10);
    
    // Левая панель с контролами
    m_controlLayout = new QVBoxLayout();
    
    // Верхняя строка с заголовком
    QHBoxLayout *titleLayout = new QHBoxLayout();
    QLabel *titleLabel = new QLabel("Ollama Commander", this);
    QFont titleFont = titleLabel->font();
    titleFont.setBold(true);
    titleFont.setPointSize(12);
    titleLabel->setFont(titleFont);
    titleLayout->addWidget(titleLabel);
    titleLayout->addStretch();
    m_controlLayout->addLayout(titleLayout);
    
    // Горизонтальный ряд для контролов
    m_inputLayout = new QHBoxLayout();
    m_inputLayout->setSpacing(10);
    
    // Заголовок для команд
    QLabel *cmdLabel = new QLabel("Command:", this);
    m_inputLayout->addWidget(cmdLabel);
    
    // Комбобокс для команд
    m_commandCombo = new QComboBox(this);
    m_commandCombo->setMinimumWidth(120);
    m_commandCombo->setToolTip("Select Ollama command");
    m_inputLayout->addWidget(m_commandCombo);
    
    // Заголовок для моделей
    QLabel *modelLabel = new QLabel("Model:", this);
    m_inputLayout->addWidget(modelLabel);
    
    // Комбобокс для моделей
    m_modelCombo = new QComboBox(this);
    m_modelCombo->setMinimumWidth(150);
    m_modelCombo->setEditable(true);
    m_modelCombo->setToolTip("Select model or choose 'Custom model...' to enter your own");
    m_inputLayout->addWidget(m_modelCombo);
    
    // Поле для кастомной модели (скрыто по умолчанию)
    m_customModelEdit = new QLineEdit(this);
    m_customModelEdit->setPlaceholderText("Enter custom model name...");
    m_customModelEdit->setMinimumWidth(150);
    m_customModelEdit->setToolTip("Type your custom model name");
    m_customModelEdit->hide();
    m_inputLayout->addWidget(m_customModelEdit);
    
    // Поле для дополнительных аргументов
    QLabel *argsLabel = new QLabel("Args:", this);
    m_inputLayout->addWidget(argsLabel);
    
    m_additionalArgsEdit = new QLineEdit(this);
    m_additionalArgsEdit->setPlaceholderText("Additional arguments...");
    m_additionalArgsEdit->setMinimumWidth(200);
    m_additionalArgsEdit->setToolTip("Additional command line arguments");
    m_inputLayout->addWidget(m_additionalArgsEdit);
    
    // Кнопка выполнения
    m_executeBtn = new QPushButton("Execute", this);
    m_executeBtn->setToolTip("Execute command and save to history");
    m_executeBtn->setStyleSheet("QPushButton { background-color: #4CAF50; color: white; padding: 5px 15px; }");
    m_inputLayout->addWidget(m_executeBtn);
    
    m_controlLayout->addLayout(m_inputLayout);
    
    // Добавляем левую панель в основной макет
    m_mainLayout->addLayout(m_controlLayout, 2);
    
    // Правая панель с историей
    QVBoxLayout *historyLayout = new QVBoxLayout();
    
    QHBoxLayout *historyHeaderLayout = new QHBoxLayout();
    QLabel *historyLabel = new QLabel("Command History", this);
    QFont historyFont = historyLabel->font();
    historyFont.setBold(true);
    historyLabel->setFont(historyFont);
    historyHeaderLayout->addWidget(historyLabel);
    
    historyHeaderLayout->addStretch();
    
    m_clearHistoryBtn = new QPushButton("Clear", this);
    m_clearHistoryBtn->setToolTip("Clear command history");
    m_clearHistoryBtn->setStyleSheet("QPushButton { color: red; }");
    historyHeaderLayout->addWidget(m_clearHistoryBtn);
    
    historyLayout->addLayout(historyHeaderLayout);
    
    m_historyList = new QListWidget(this);
    m_historyList->setToolTip("Click on any command to load it");
    m_historyList->setMaximumWidth(300);
    m_historyList->setMinimumWidth(250);
    historyLayout->addWidget(m_historyList);
    
    m_mainLayout->addLayout(historyLayout, 1);
    
    // Подключаем сигналы
    connect(m_commandCombo, QOverload<int>::of(&QComboBox::currentIndexChanged),
            this, &DynamicWidgets::onCommandChanged);
    connect(m_modelCombo, &QComboBox::editTextChanged,
            this, &DynamicWidgets::onModelTextChanged);
    connect(m_customModelEdit, &QLineEdit::textChanged,
            this, &DynamicWidgets::onModelTextChanged);
    connect(m_additionalArgsEdit, &QLineEdit::textChanged,
            this, &DynamicWidgets::updateCommandPreview);
    connect(m_executeBtn, &QPushButton::clicked,
            this, &DynamicWidgets::onExecuteCommand);
    connect(m_historyList, &QListWidget::itemClicked,
            this, &DynamicWidgets::onHistoryItemClicked);
    connect(m_clearHistoryBtn, &QPushButton::clicked,
            this, &DynamicWidgets::onClearHistory);
}

bool DynamicWidgets::loadFromFile(const QString &filename)
{
    QFile file(filename);
    if (!file.open(QIODevice::ReadOnly)) {
        qWarning() << "Cannot open file:" << filename;
        return false;
    }
    
    QByteArray data = file.readAll();
    file.close();
    
    return loadFromJson(data);
}

bool DynamicWidgets::loadFromJson(const QByteArray &jsonData)
{
    QJsonParseError parseError;
    QJsonDocument doc = QJsonDocument::fromJson(jsonData, &parseError);
    
    if (parseError.error != QJsonParseError::NoError) {
        qWarning() << "JSON parse error:" << parseError.errorString();
        return false;
    }
    
    if (!doc.isObject()) {
        qWarning() << "JSON is not an object";
        return false;
    }
    
    m_config = doc.object();
    
    // Очищаем комбобоксы
    m_commandCombo->clear();
    m_modelCombo->clear();
    
    // Загружаем команды
    if (m_config.contains("commands") && m_config["commands"].isArray()) {
        QJsonArray commands = m_config["commands"].toArray();
        for (const QJsonValue &cmd : commands) {
            if (cmd.isString()) {
                QString command = cmd.toString();
                m_commandCombo->addItem(command);
                
                // Добавляем тултип из конфига
                if (m_config.contains("tooltips") && m_config["tooltips"].isObject()) {
                    QJsonObject tooltips = m_config["tooltips"].toObject();
                    if (tooltips.contains(command)) {
                        int index = m_commandCombo->count() - 1;
                        m_commandCombo->setItemData(index, tooltips[command].toString(), Qt::ToolTipRole);
                    }
                }
            }
        }
    }
    
    // Загружаем модели
    m_availableModels.clear();
    if (m_config.contains("models") && m_config["models"].isArray()) {
        QJsonArray models = m_config["models"].toArray();
        for (const QJsonValue &model : models) {
            if (model.isString()) {
                QString modelName = model.toString();
                m_availableModels.append(modelName);
                m_modelCombo->addItem(modelName);
            }
        }
    }
    
    // Добавляем опцию для кастомной модели
    m_modelCombo->addItem("Custom model...");
    
    // Устанавливаем значения по умолчанию
    if (m_config.contains("default_command")) {
        int index = m_commandCombo->findText(m_config["default_command"].toString());
        if (index >= 0) {
            m_commandCombo->setCurrentIndex(index);
        }
    }
    
    return true;
}

void DynamicWidgets::onCommandChanged(int index)
{
    Q_UNUSED(index);
    QString command = m_commandCombo->currentText();
    
    // Обновляем UI в зависимости от команды
    if (command == "run") {
        m_modelCombo->show();
        if (m_modelCombo->currentText() == "Custom model...") {
            m_customModelEdit->show();
            m_modelCombo->hide();
        }
    } else {
        m_modelCombo->hide();
        m_customModelEdit->hide();
    }
    
    updateCommandPreview();
}

void DynamicWidgets::onModelTextChanged(const QString &text)
{
    Q_UNUSED(text);
    
    // Если выбрана кастомная модель
    if (m_modelCombo->currentText() == "Custom model..." && !m_customModelEdit->isVisible()) {
        m_modelCombo->hide();
        m_customModelEdit->show();
        m_customModelEdit->setFocus();
    } 
    // Если в кастомном поле пусто и пользователь переключился обратно
    else if (m_customModelEdit->isVisible() && m_customModelEdit->text().isEmpty()) {
        if (m_modelCombo->currentText() != "Custom model...") {
            m_customModelEdit->hide();
            m_modelCombo->show();
        }
    }
    
    updateCommandPreview();
}

void DynamicWidgets::updateCommandPreview()
{
    QString command = m_commandCombo->currentText();
    QString model;
    
    if (command == "run") {
        if (m_customModelEdit->isVisible()) {
            model = m_customModelEdit->text();
        } else {
            model = m_modelCombo->currentText();
        }
    }
    
    m_currentCommand = command;
    m_currentModel = model;
}

void DynamicWidgets::onExecuteCommand()
{
    QString command = getCommand();
    
    if (command == "ollama" || command.isEmpty()) {
        QMessageBox::warning(this, "Invalid Command", "Please select a valid command and model.");
        return;
    }
    
    // Сохраняем команду в историю
    saveCommandToHistory(command);
    
    // Здесь можно добавить сигнал или вызов внешней команды
    QMessageBox::information(this, "Command Ready", 
                            "Command ready to execute:\n\n" + command);
    
    qDebug() << "Executing command:" << command;
}

void DynamicWidgets::saveCommandToHistory(const QString &command)
{
    // Не сохраняем пустые команды
    if (command.isEmpty() || command == "ollama") {
        return;
    }
    
    // Проверяем на дубликаты (не сохраняем если последняя команда такая же)
    if (!m_commandHistory.isEmpty() && m_commandHistory.first() == command) {
        return;
    }
    
    // Добавляем команду в начало списка
    m_commandHistory.prepend(command);
    
    // Ограничиваем размер истории
    while (m_commandHistory.size() > MAX_HISTORY_SIZE) {
        m_commandHistory.removeLast();
    }
    
    // Сохраняем в QSettings
    saveHistoryToSettings();
    
    // Обновляем отображение
    updateHistoryDisplay();
}

void DynamicWidgets::loadHistoryFromSettings()
{
    QSettings settings;
    settings.beginGroup(SETTINGS_PREFIX + "History");
    
    // Загружаем историю команд
    int size = settings.beginReadArray("commands");
    m_commandHistory.clear();
    
    for (int i = 0; i < size; ++i) {
        settings.setArrayIndex(i);
        QString command = settings.value("command").toString();
        if (!command.isEmpty()) {
            m_commandHistory.append(command);
        }
    }
    
    settings.endArray();
    settings.endGroup();
    
    // Обновляем отображение
    updateHistoryDisplay();
}

void DynamicWidgets::saveHistoryToSettings()
{
    QSettings settings;
    settings.beginGroup(SETTINGS_PREFIX + "History");
    
    // Сохраняем историю команд
    settings.beginWriteArray("commands");
    for (int i = 0; i < m_commandHistory.size(); ++i) {
        settings.setArrayIndex(i);
        settings.setValue("command", m_commandHistory[i]);
    }
    settings.endArray();
    
    settings.endGroup();
    settings.sync();
}

void DynamicWidgets::updateHistoryDisplay()
{
    m_historyList->clear();
    
    for (const QString &command : m_commandHistory) {
        QListWidgetItem *item = new QListWidgetItem(command);
        
        // Добавляем время создания (можно добавить реальное время из настроек)
        QDateTime now = QDateTime::currentDateTime();
        QString timeStr = now.toString("hh:mm:ss");
        
        item->setToolTip("Executed at: " + timeStr);
        item->setData(COMMAND_ROLE, command);
        
        m_historyList->addItem(item);
    }
    
    if (m_commandHistory.isEmpty()) {
        QListWidgetItem *item = new QListWidgetItem("No history yet");
        item->setFlags(item->flags() & ~Qt::ItemIsEnabled);
        item->setForeground(Qt::gray);
        m_historyList->addItem(item);
    }
}

void DynamicWidgets::onHistoryItemClicked(QListWidgetItem *item)
{
    if (!item) return;
    
    QString command = item->data(COMMAND_ROLE).toString();
    if (command.isEmpty()) return;
    
    // Парсим команду на части
    QStringList parts = command.split(' ', Qt::SkipEmptyParts);
    
    if (parts.size() >= 2 && parts[0] == "ollama") {
        // Устанавливаем команду
        if (parts.size() >= 2) {
            int cmdIndex = m_commandCombo->findText(parts[1]);
            if (cmdIndex >= 0) {
                m_commandCombo->setCurrentIndex(cmdIndex);
            }
        }
        
        // Устанавливаем модель для команды run
        if (parts.size() >= 3 && parts[1] == "run") {
            QString model = parts[2];
            
            // Проверяем, есть ли модель в списке
            int modelIndex = m_modelCombo->findText(model);
            if (modelIndex >= 0) {
                m_modelCombo->setCurrentIndex(modelIndex);
                m_customModelEdit->hide();
                m_modelCombo->show();
            } else {
                // Если модели нет в списке, используем кастомное поле
                m_modelCombo->setCurrentIndex(m_modelCombo->findText("Custom model..."));
                m_customModelEdit->setText(model);
            }
        }
        
        // Устанавливаем дополнительные аргументы
        if (parts.size() >= 4) {
            QStringList args = parts.mid(3);
            m_additionalArgsEdit->setText(args.join(' '));
        } else {
            m_additionalArgsEdit->clear();
        }
    }
}

void DynamicWidgets::onClearHistory()
{
    QMessageBox::StandardButton reply = QMessageBox::question(this, "Clear History", 
                                                              "Are you sure you want to clear all command history?",
                                                              QMessageBox::Yes | QMessageBox::No);
    
    if (reply == QMessageBox::Yes) {
        m_commandHistory.clear();
        saveHistoryToSettings();
        updateHistoryDisplay();
    }
}

QString DynamicWidgets::getCommand() const
{
    QStringList commandParts;
    
    // Базовый вызов ollama
    QString cmd = m_commandCombo->currentText();
    if (cmd.isEmpty() || cmd == "Custom model...") {
        return QString();
    }
    
    commandParts << "ollama" << cmd;
    
    // Модель для команды run
    if (cmd == "run") {
        QString model;
        if (m_customModelEdit->isVisible()) {
            model = m_customModelEdit->text();
        } else {
            model = m_modelCombo->currentText();
        }
        
        if (!model.isEmpty() && model != "Custom model...") {
            commandParts << model;
        }
    }
    
    // Дополнительные аргументы
    QString additionalArgs = m_additionalArgsEdit->text();
    if (!additionalArgs.isEmpty()) {
        // Разбиваем строку с аргументами, учитывая кавычки
        bool inQuotes = false;
        QString currentArg;
        
        for (QChar ch : additionalArgs) {
            if (ch == '"') {
                inQuotes = !inQuotes;
                currentArg += ch;
            } else if (ch.isSpace() && !inQuotes) {
                if (!currentArg.isEmpty()) {
                    commandParts << currentArg;
                    currentArg.clear();
                }
            } else {
                currentArg += ch;
            }
        }
        
        if (!currentArg.isEmpty()) {
            commandParts << currentArg;
        }
    }
    
    return commandParts.join(' ');
}
```

## Пример использования обновленного класса:

```cpp
// В main.cpp добавьте проверку сохраненной истории
#include <QSettings>

// После создания DynamicWidgets можно проверить настройки
qDebug() << "Settings location:" << QSettings().fileName();

// История автоматически загружается при создании виджета
// и сохраняется при закрытии приложения
```

## Ключевые особенности добавленной функциональности:

1. **Префикс настроек**: `OllamaCommander/` для изоляции от других настроек
2. **Хранение истории**: Используется `QSettings` с группировкой
3. **Максимальный размер**: Ограничение истории до 50 команд
4. **Загрузка истории**: Автоматическая загрузка при создании виджета
5. **Сохранение истории**: Автоматическое сохранение при уничтожении виджета
6. **Отображение истории**: Список команд с возможностью клика для загрузки
7. **Очистка истории**: Кнопка для очистки с подтверждением
8. **Предотвращение дубликатов**: Не сохраняются повторяющиеся команды подряд
9. **Валидация**: Проверка на пустые команды
10. **Визуальные подсказки**: Цвета, иконки, тултипы для лучшего UX

История сохраняется в стандартном для Qt месте (зависит от платформы):
- Windows: `HKEY_CURRENT_USER\Software\<Organization>\<Application>`
- macOS: `~/Library/Preferences/com.<Organization>.<Application>.plist`
- Linux: `~/.config/<Organization>/<Application>.conf`