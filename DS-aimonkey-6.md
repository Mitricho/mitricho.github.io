Создам класс `PromptTemplateDialog` для выбора шаблонов промптов. Вот полная реализация:

## 1. prompttemplatedialog.h

```cpp
#pragma once

#include <QDialog>
#include <QListWidget>
#include <QPushButton>
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QLabel>
#include <QJsonDocument>
#include <QJsonArray>
#include <QJsonObject>
#include <QFile>
#include <QTextBrowser>

class PromptTemplateDialog : public QDialog
{
    Q_OBJECT

public:
    explicit PromptTemplateDialog(QWidget *parent = nullptr);
    ~PromptTemplateDialog();

    bool loadTemplates(const QString &filePath = QString());

signals:
    void promptTemplateChosen(const QString &prompt);

private slots:
    void onTemplateSelected(QListWidgetItem *item);
    void onInsertClicked();
    void onCancelClicked();

private:
    void setupUI();
    void createDefaultTemplates();

    QListWidget *templatesList;
    QTextBrowser *previewBrowser;
    QPushButton *insertButton;
    QPushButton *cancelButton;

    struct TemplateInfo {
        QString name;
        QString description;
        QString prompt;
        QString category;
    };
    QList<TemplateInfo> templates;
    QString currentTemplatePath;
};
```

## 2. prompttemplatedialog.cpp

```cpp
#include "prompttemplatedialog.h"
#include <QMessageBox>
#include <QDir>
#include <QApplication>
#include <QStyle>
#include <QFontMetrics>

PromptTemplateDialog::PromptTemplateDialog(QWidget *parent)
    : QDialog(parent)
{
    setupUI();
    
    // Пробуем загрузить шаблоны
    QString defaultPath = QDir(QApplication::applicationDirPath()).filePath("templates.json");
    if (!loadTemplates(defaultPath)) {
        // Если файл не найден, создаем стандартные шаблоны
        createDefaultTemplates();
    }
    
    setWindowTitle("Выбор шаблона промпта");
    resize(800, 600);
}

PromptTemplateDialog::~PromptTemplateDialog()
{
}

void PromptTemplateDialog::setupUI()
{
    QVBoxLayout *mainLayout = new QVBoxLayout(this);
    mainLayout->setContentsMargins(10, 10, 10, 10);
    mainLayout->setSpacing(10);
    
    // Заголовок
    QLabel *titleLabel = new QLabel("Выберите шаблон промпта");
    titleLabel->setStyleSheet(R"(
        QLabel {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            padding: 5px;
        }
    )");
    mainLayout->addWidget(titleLabel);
    
    // Разделитель
    QFrame *separator = new QFrame;
    separator->setFrameShape(QFrame::HLine);
    separator->setFrameShadow(QFrame::Sunken);
    separator->setStyleSheet("color: #bdc3c7;");
    mainLayout->addWidget(separator);
    
    // Основной контейнер
    QHBoxLayout *contentLayout = new QHBoxLayout;
    contentLayout->setSpacing(15);
    
    // Левая панель: список шаблонов
    QWidget *listPanel = new QWidget;
    QVBoxLayout *listLayout = new QVBoxLayout(listPanel);
    listLayout->setContentsMargins(0, 0, 0, 0);
    
    QLabel *listLabel = new QLabel("Доступные шаблоны:");
    listLabel->setStyleSheet("font-weight: bold; color: #34495e;");
    listLayout->addWidget(listLabel);
    
    templatesList = new QListWidget;
    templatesList->setSelectionMode(QAbstractItemView::SingleSelection);
    templatesList->setAlternatingRowColors(true);
    templatesList->setStyleSheet(R"(
        QListWidget {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 5px;
        }
        QListWidget::item {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        QListWidget::item:selected {
            background-color: #3498db;
            color: white;
            border-radius: 3px;
        }
        QListWidget::item:hover:!selected {
            background-color: #e9ecef;
            border-radius: 3px;
        }
    )");
    listLayout->addWidget(templatesList, 1);
    
    contentLayout->addWidget(listPanel, 1);
    
    // Правая панель: предпросмотр
    QWidget *previewPanel = new QWidget;
    QVBoxLayout *previewLayout = new QVBoxLayout(previewPanel);
    previewLayout->setContentsMargins(0, 0, 0, 0);
    
    QLabel *previewLabel = new QLabel("Предпросмотр промпта:");
    previewLabel->setStyleSheet("font-weight: bold; color: #34495e;");
    previewLayout->addWidget(previewLabel);
    
    previewBrowser = new QTextBrowser;
    previewBrowser->setReadOnly(true);
    previewBrowser->setStyleSheet(R"(
        QTextBrowser {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            font-family: 'Consolas', monospace;
            font-size: 11px;
        }
    )");
    previewLayout->addWidget(previewBrowser, 1);
    
    contentLayout->addWidget(previewPanel, 2);
    
    mainLayout->addLayout(contentLayout, 1);
    
    // Панель кнопок
    QHBoxLayout *buttonLayout = new QHBoxLayout;
    buttonLayout->setSpacing(10);
    
    buttonLayout->addStretch();
    
    insertButton = new QPushButton("Вставить промпт");
    insertButton->setIcon(QIcon::fromTheme("edit-paste"));
    insertButton->setEnabled(false);
    insertButton->setStyleSheet(R"(
        QPushButton {
            background-color: #2ecc71;
            color: white;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 4px;
            min-width: 120px;
        }
        QPushButton:hover {
            background-color: #27ae60;
        }
        QPushButton:pressed {
            background-color: #229954;
        }
        QPushButton:disabled {
            background-color: #bdc3c7;
            color: #7f8c8d;
        }
    )");
    buttonLayout->addWidget(insertButton);
    
    cancelButton = new QPushButton("Отмена");
    cancelButton->setIcon(QIcon::fromTheme("dialog-cancel"));
    cancelButton->setStyleSheet(R"(
        QPushButton {
            background-color: #e74c3c;
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            min-width: 100px;
        }
        QPushButton:hover {
            background-color: #c0392b;
        }
        QPushButton:pressed {
            background-color: #a93226;
        }
    )");
    buttonLayout->addWidget(cancelButton);
    
    mainLayout->addLayout(buttonLayout);
    
    // Соединения
    connect(templatesList, &QListWidget::itemClicked,
            this, &PromptTemplateDialog::onTemplateSelected);
    connect(templatesList, &QListWidget::itemDoubleClicked,
            this, &PromptTemplateDialog::onInsertClicked);
    connect(insertButton, &QPushButton::clicked,
            this, &PromptTemplateDialog::onInsertClicked);
    connect(cancelButton, &QPushButton::clicked,
            this, &CancelClicked);
}

bool PromptTemplateDialog::loadTemplates(const QString &filePath)
{
    if (filePath.isEmpty()) {
        // Пробуем найти файл в стандартных местах
        QStringList possiblePaths = {
            QDir(QApplication::applicationDirPath()).filePath("templates.json"),
            QDir(QApplication::applicationDirPath()).filePath("config/templates.json"),
            QDir::home().filePath(".aidevassistant/templates.json"),
            ":/templates/templates.json"
        };
        
        for (const QString &path : possiblePaths) {
            if (QFile::exists(path)) {
                currentTemplatePath = path;
                break;
            }
        }
    } else {
        currentTemplatePath = filePath;
    }
    
    if (currentTemplatePath.isEmpty() || !QFile::exists(currentTemplatePath)) {
        return false;
    }
    
    QFile file(currentTemplatePath);
    if (!file.open(QIODevice::ReadOnly | QIODevice::Text)) {
        qWarning() << "Не удалось открыть файл шаблонов:" << file.errorString();
        return false;
    }
    
    QByteArray jsonData = file.readAll();
    file.close();
    
    QJsonDocument jsonDoc = QJsonDocument::fromJson(jsonData);
    if (jsonDoc.isNull() || !jsonDoc.isObject()) {
        qWarning() << "Неверный формат JSON файла шаблонов";
        return false;
    }
    
    QJsonObject root = jsonDoc.object();
    if (!root.contains("templates") || !root["templates"].isArray()) {
        qWarning() << "Отсутствует массив templates в JSON";
        return false;
    }
    
    QJsonArray templatesArray = root["templates"].toArray();
    templates.clear();
    
    for (const QJsonValue &value : templatesArray) {
        if (value.isObject()) {
            QJsonObject obj = value.toObject();
            TemplateInfo info;
            
            info.name = obj.value("name").toString();
            info.description = obj.value("description").toString();
            info.prompt = obj.value("prompt").toString();
            info.category = obj.value("category").toString("Общие");
            
            if (!info.name.isEmpty() && !info.prompt.isEmpty()) {
                templates.append(info);
            }
        }
    }
    
    // Заполняем список
    templatesList->clear();
    for (const TemplateInfo &info : templates) {
        QListWidgetItem *item = new QListWidgetItem;
        
        // Форматируем текст элемента
        QString itemText = QString("<b>%1</b>").arg(info.name);
        if (!info.category.isEmpty()) {
            itemText += QString(" <span style='color: #7f8c8d; font-size: 10px;'>(%2)</span>").arg(info.category);
        }
        
        if (!info.description.isEmpty()) {
            // Обрезаем описание если слишком длинное
            QString shortDesc = info.description;
            if (shortDesc.length() > 100) {
                shortDesc = shortDesc.left(100) + "...";
            }
            itemText += QString("<br><span style='color: #34495e; font-size: 11px;'>%1</span>").arg(shortDesc);
        }
        
        item->setText(itemText);
        item->setData(Qt::UserRole, templates.indexOf(info));
        item->setToolTip(info.description);
        
        // Устанавливаем иконку в зависимости от категории
        QString iconName;
        if (info.category == "Рефакторинг") {
            iconName = "edit-redo";
        } else if (info.category == "Отладка") {
            iconName = "tools-report-bug";
        } else if (info.category == "Документация") {
            iconName = "document-edit";
        } else if (info.category == "Оптимизация") {
            iconName = "speedometer";
        } else {
            iconName = "text-plain";
        }
        
        item->setIcon(QIcon::fromTheme(iconName));
        templatesList->addItem(item);
    }
    
    return !templates.isEmpty();
}

void PromptTemplateDialog::createDefaultTemplates()
{
    templates.clear();
    
    // Шаблон 1: Рефакторинг кода
    TemplateInfo refactorTemplate;
    refactorTemplate.name = "Рефакторинг кода";
    refactorTemplate.category = "Рефакторинг";
    refactorTemplate.description = "Попросить AI улучшить структуру и читаемость кода, следуя лучшим практикам";
    refactorTemplate.prompt = R"(Пожалуйста, выполни рефакторинг следующего кода:

1. Улучши именование переменных и функций
2. Удали дублирование кода
3. Упрости сложные условия
4. Разбей большие функции на меньшие
5. Добавь комментарии для сложных участков
6. Следуй принципам SOLID и clean code

После рефакторинга предоставь:
- Объяснение внесенных изменений
- Оценку улучшения читаемости
- Рекомендации по дальнейшему улучшению

Код для рефакторинга:
{ВСТАВЬ_КОД_ЗДЕСЬ})";
    templates.append(refactorTemplate);
    
    // Шаблон 2: Поиск и исправление ошибок
    TemplateInfo debugTemplate;
    debugTemplate.name = "Отладка и исправление ошибок";
    debugTemplate.category = "Отладка";
    debugTemplate.description = "Найти и исправить ошибки в коде, включая логические ошибки и проблемы с производительностью";
    debugTemplate.prompt = R"(Проанализируй следующий код на наличие ошибок и проблем:

1. Синтаксические ошибки
2. Логические ошибки
3. Потенциальные утечки памяти
4. Проблемы с производительностью
5. Потенциальные race conditions
6. Небезопасные операции

Для каждой найденной проблемы предоставь:
- Описание проблемы
- Уровень серьезности (критический, серьезный, средний, низкий)
- Конкретное место в коде
- Исправленную версию кода
- Объяснение почему это было ошибкой

Если ошибок нет, подтверди что код корректный и дай рекомендации по улучшению.

Код для анализа:
{ВСТАВЬ_КОД_ЗДЕСЬ}

Дополнительная информация об ошибке (если есть):
{ОПИСАНИЕ_ОШИБКИ})";
    templates.append(debugTemplate);
    
    // Шаблон 3: Генерация документации
    TemplateInfo docsTemplate;
    docsTemplate.name = "Генерация документации";
    docsTemplate.category = "Документация";
    docsTemplate.description = "Создать документацию для кода: комментарии, README, API документацию";
    docsTemplate.prompt = R"(Создай подробную документацию для следующего кода:

1. **Для каждого публичного класса/функции:**
   - Краткое описание назначения
   - Параметры (тип, назначение, ограничения)
   - Возвращаемое значение
   - Исключения которые могут быть выброшены
   - Пример использования

2. **Общая документация:**
   - Архитектурное описание системы
   - Диаграмма зависимостей (в текстовом виде)
   - Требования к окружению
   - Инструкции по сборке и запуску
   - Примеры конфигурации

3. **Комментарии в коде:**
   - Добавь/улучши docstrings для всех публичных методов
   - Добавь inline комментарии для сложной логики
   - Укажи допущения и ограничения

Формат документации:
- Классы и функции: в формате JSDoc/Doxygen
- README: в формате Markdown
- API документация: в формате OpenAPI/Swagger если применимо

Код для документирования:
{ВСТАВЬ_КОД_ЗДЕСЬ}

Контекст проекта:
{КОНТЕКСТ_ПРОЕКТА})";
    templates.append(docsTemplate);
    
    // Заполняем список
    templatesList->clear();
    for (const TemplateInfo &info : templates) {
        QListWidgetItem *item = new QListWidgetItem;
        
        QString itemText = QString("<b>%1</b>").arg(info.name);
        if (!info.category.isEmpty()) {
            itemText += QString(" <span style='color: #7f8c8d; font-size: 10px;'>(%2)</span>").arg(info.category);
        }
        
        if (!info.description.isEmpty()) {
            QString shortDesc = info.description;
            if (shortDesc.length() > 100) {
                shortDesc = shortDesc.left(100) + "...";
            }
            itemText += QString("<br><span style='color: #34495e; font-size: 11px;'>%1</span>").arg(shortDesc);
        }
        
        item->setText(itemText);
        item->setData(Qt::UserRole, templates.indexOf(info));
        item->setToolTip(info.description);
        
        // Устанавливаем иконку
        QString iconName;
        if (info.category == "Рефакторинг") {
            iconName = "edit-redo";
        } else if (info.category == "Отладка") {
            iconName = "tools-report-bug";
        } else if (info.category == "Документация") {
            iconName = "document-edit";
        } else {
            iconName = "text-plain";
        }
        
        item->setIcon(QIcon::fromTheme(iconName));
        templatesList->addItem(item);
    }
}

void PromptTemplateDialog::onTemplateSelected(QListWidgetItem *item)
{
    if (!item) {
        previewBrowser->clear();
        insertButton->setEnabled(false);
        return;
    }
    
    int index = item->data(Qt::UserRole).toInt();
    if (index >= 0 && index < templates.size()) {
        const TemplateInfo &info = templates[index];
        
        // Форматируем предпросмотр
        QString previewHtml = QString(R"(
            <div style="font-family: 'Segoe UI', Arial, sans-serif;">
                <h3 style="color: #2c3e50; margin-bottom: 10px;">%1</h3>
                <div style="color: #7f8c8d; font-size: 12px; margin-bottom: 15px;">
                    <strong>Категория:</strong> %2<br>
                    <strong>Описание:</strong> %3
                </div>
                <div style="background-color: #f8f9fa; border-left: 4px solid #3498db; 
                            padding: 15px; border-radius: 4px; font-family: 'Consolas', monospace; 
                            font-size: 11px; line-height: 1.4; white-space: pre-wrap;">
                    %4
                </div>
                <div style="margin-top: 15px; color: #95a5a6; font-size: 11px; padding: 10px; 
                            background-color: #ecf0f1; border-radius: 4px;">
                    <strong>Примечание:</strong> Замените заполнители в фигурных скобках ({...}) 
                    на ваш конкретный код или информацию.
                </div>
            </div>
        )")
        .arg(info.name.toHtmlEscaped())
        .arg(info.category.toHtmlEscaped())
        .arg(info.description.toHtmlEscaped())
        .arg(info.prompt.toHtmlEscaped().replace("\n", "<br>"));
        
        previewBrowser->setHtml(previewHtml);
        insertButton->setEnabled(true);
    }
}

void PromptTemplateDialog::onInsertClicked()
{
    QListWidgetItem *currentItem = templatesList->currentItem();
    if (!currentItem) {
        QMessageBox::warning(this, "Шаблон не выбран", 
                            "Пожалуйста, выберите шаблон промпта из списка.");
        return;
    }
    
    int index = currentItem->data(Qt::UserRole).toInt();
    if (index >= 0 && index < templates.size()) {
        const TemplateInfo &info = templates[index];
        emit promptTemplateChosen(info.prompt);
        accept();
    }
}

void PromptTemplateDialog::onCancelClicked()
{
    reject();
}
```

## 3. templates.json (пример файла шаблонов)

```json
{
    "version": "1.0",
    "last_updated": "2024-01-15",
    "description": "Шаблоны промптов для AI Dev Assistant",
    "templates": [
        {
            "name": "Рефакторинг кода",
            "category": "Рефакторинг",
            "description": "Попросить AI улучшить структуру и читаемость кода, следуя лучшим практикам",
            "prompt": "Пожалуйста, выполни рефакторинг следующего кода:\n\n1. Улучши именование переменных и функций\n2. Удали дублирование кода\n3. Упрости сложные условия\n4. Разбей большие функции на меньшие\n5. Добавь комментарии для сложных участков\n6. Следуй принципам SOLID и clean code\n\nПосле рефакторинга предоставь:\n- Объяснение внесенных изменений\n- Оценку улучшения читаемости\n- Рекомендации по дальнейшему улучшению\n\nКод для рефакторинга:\n{ВСТАВЬ_КОД_ЗДЕСЬ}"
        },
        {
            "name": "Отладка и исправление ошибок",
            "category": "Отладка",
            "description": "Найти и исправить ошибки в коде, включая логические ошибки и проблемы с производительностью",
            "prompt": "Проанализируй следующий код на наличие ошибок и проблем:\n\n1. Синтаксические ошибки\n2. Логические ошибки\n3. Потенциальные утечки памяти\n4. Проблемы с производительностью\n5. Потенциальные race conditions\n6. Небезопасные операции\n\nДля каждой найденной проблемы предоставь:\n- Описание проблемы\n- Уровень серьезности (критический, серьезный, средний, низкий)\n- Конкретное место в коде\n- Исправленную версию кода\n- Объяснение почему это было ошибкой\n\nЕсли ошибок нет, подтверди что код корректный и дай рекомендации по улучшению.\n\nКод для анализа:\n{ВСТАВЬ_КОД_ЗДЕСЬ}\n\nДополнительная информация об ошибке (если есть):\n{ОПИСАНИЕ_ОШИБКИ}"
        },
        {
            "name": "Генерация документации",
            "category": "Документация",
            "description": "Создать документацию для кода: комментарии, README, API документацию",
            "prompt": "Создай подробную документацию для следующего кода:\n\n1. **Для каждого публичного класса/функции:**\n   - Краткое описание назначения\n   - Параметры (тип, назначение, ограничения)\n   - Возвращаемое значение\n   - Исключения которые могут быть выброшены\n   - Пример использования\n\n2. **Общая документация:**\n   - Архитектурное описание системы\n   - Диаграмма зависимостей (в текстовом виде)\n   - Требования к окружению\n   - Инструкции по сборке и запуску\n   - Примеры конфигурации\n\n3. **Комментарии в коде:**\n   - Добавь/улучши docstrings для всех публичных методов\n   - Добавь inline комментарии для сложной логики\n   - Укажи допущения и ограничения\n\nФормат документации:\n- Классы и функции: в формате JSDoc/Doxygen\n- README: в формате Markdown\n- API документация: в формате OpenAPI/Swagger если применимо\n\nКод для документирования:\n{ВСТАВЬ_КОД_ЗДЕСЬ}\n\nКонтекст проекта:\n{КОНТЕКСТ_ПРОЕКТА}"
        }
    ]
}
```

## 4. Обновление CMakeLists.txt

Добавьте новый файл в CMakeLists.txt:

```cmake
# В секцию SOURCES добавьте:
set(SOURCES
    main.cpp
    mainwindow.cpp
    projecttreemodel.cpp
    codeprocessor.cpp
    apimanager.cpp
    diffviewer.cpp
    prompttemplatedialog.cpp  # Добавить эту строку
)

# В секцию HEADERS добавьте:
set(HEADERS
    mainwindow.h
    projecttreemodel.h
    codeprocessor.h
    apimanager.h
    diffviewer.h
    prompttemplatedialog.h  # Добавить эту строку
)
```

## 5. Обновление mainwindow.cpp для использования диалога

Добавьте в `mainwindow.cpp`:

```cpp
// В начале файла добавьте include
#include "prompttemplatedialog.h"

// В setupMenu() добавьте действие для открытия диалога шаблонов:
void MainWindow::setupMenu()
{
    // ... существующий код ...
    
    // В меню Инструменты или Правка добавьте:
    QMenu *toolsMenu = menuBar()->addMenu("Инструменты");
    
    QAction *templatesAction = new QAction("Шаблоны промптов...", this);
    templatesAction->setShortcut(QKeySequence("Ctrl+T"));
    connect(templatesAction, &QAction::triggered, this, [this]() {
        PromptTemplateDialog dialog(this);
        connect(&dialog, &PromptTemplateDialog::promptTemplateChosen,
                this, [this](const QString &prompt) {
                    // Вставляем промпт в поле ввода
                    promptEdit->setPlainText(prompt);
                    promptEdit->setFocus();
                });
        dialog.exec();
    });
    toolsMenu->addAction(templatesAction);
    
    // ... остальной код ...
}

// Также можно добавить кнопку на панель инструментов (по желанию)
```

## Особенности реализации:

1. **Модальный диалог** на основе `QDialog`
2. **Загрузка из JSON** с fallback на встроенные шаблоны
3. **Визуальное представление**:
   - Список шаблонов с иконками и описанием
   - Предпросмотр промпта с форматированием
   - Подсветка категорий
4. **Три примера шаблонов**:
   - Рефакторинг кода
   - Отладка и исправление ошибок
   - Генерация документации
5. **Сигнал `promptTemplateChosen`** с текстом промпта
6. **Интуитивный интерфейс**:
   - Двойной клик для быстрой вставки
   - Кнопка "Вставить промпт"
   - Предварительный просмотр
   - Подсказки по использованию

## Использование:

```cpp
// Пример использования в вашем коде:
PromptTemplateDialog dialog(this);
connect(&dialog, &PromptTemplateDialog::promptTemplateChosen,
        this, [this](const QString &prompt) {
            // Вставить промпт в нужное место
            promptEdit->setPlainText(prompt);
            // Или сразу отправить
            // sendPrompt();
        });

if (dialog.exec() == QDialog::Accepted) {
    // Диалог закрыт с выбором шаблона
}
```

Диалог автоматически загрузит шаблоны из файла `templates.json` в директории приложения или создаст стандартные шаблоны если файл не найден.



Отличная идея! Вот универсальные шаблоны промптов, которые работают с любым языком программирования:

## 1. Обновленный prompttemplatedialog.cpp (только функция createDefaultTemplates)

```cpp
void PromptTemplateDialog::createDefaultTemplates()
{
    templates.clear();
    
    // Шаблон 1: Универсальный рефакторинг
    TemplateInfo refactorTemplate;
    refactorTemplate.name = "Универсальный рефакторинг кода";
    refactorTemplate.category = "Оптимизация";
    refactorTemplate.description = "Улучшение структуры, читаемости и поддерживаемости кода без изменения функциональности";
    refactorTemplate.prompt = R"(Проведи комплексный рефакторинг предоставленного кода. Сосредоточься на следующих аспектах:

📌 **Читаемость и ясность:**
- Улучши именование переменных, функций, классов и методов
- Упрости сложные логические выражения и условия
- Разбей длинные функции на более мелкие, специализированные
- Удали мертвый код и неиспользуемые зависимости

📌 **Архитектурные улучшения:**
- Выяви и устрани дублирование кода (принцип DRY)
- Улучши организацию модулей и их ответственность (принцип SRP)
- Оптимизируй зависимости между компонентами
- Примени паттерны проектирования где это уместно

📌 **Качество кода:**
- Повысь тестируемость кода
- Улучши обработку ошибок и исключений
- Оптимизируй использование ресурсов (память, процессор)
- Устрани потенциальные точки отказа

📌 **Производительность:**
- Определи узкие места производительности
- Предложи оптимизации алгоритмов
- Улучши использование структур данных
- Оптимизируй сложность операций (Big-O)

**Ожидаемый результат:**
1. Полный рефакторированный код
2. Подробное объяснение всех внесенных изменений
3. Оценка улучшения читаемости (по шкале 1-10)
4. Список примененных принципов и паттернов
5. Рекомендации для дальнейших улучшений

**Исходный код для рефакторинга:**
```{language}
{code}
```)";
    templates.append(refactorTemplate);
    
    // Шаблон 2: Комплексная отладка
    TemplateInfo debugTemplate;
    debugTemplate.name = "Комплексный анализ и отладка";
    debugTemplate.category = "Диагностика";
    debugTemplate.description = "Поиск и исправление ошибок, анализ потенциальных проблем и уязвимостей";
    debugTemplate.prompt = R"(Проведи глубокий анализ предоставленного кода на наличие проблем. Исследуй следующие аспекты:

🔍 **Синтаксические и семантические ошибки:**
- Проверь корректность синтаксиса
- Определи несоответствия типов данных
- Выяви неинициализированные переменные
- Проверь корректность вызовов функций/методов

🔍 **Логические ошибки:**
- Проанализируй бизнес-логику на корректность
- Проверь условия циклов и условных операторов
- Выяви ошибочные предположения о данных
- Определи проблемы с обработкой граничных случаев

🔍 **Безопасность и надежность:**
- Проверь на уязвимости (инъекции, переполнения буфера)
- Проанализируй обработку пользовательского ввода
- Проверь корректность работы с памятью
- Выяви потенциальные race conditions (для многопоточного кода)

🔍 **Производительность и оптимизация:**
- Определи неоптимальные алгоритмы
- Выяви избыточные вычисления
- Проверь эффективность использования структур данных
- Проанализируй сложность операций

🔍 **Качество и поддерживаемость:**
- Оцени тестируемость кода
- Проверь качество обработки ошибок
- Выяви жесткие зависимости
- Оцени модульность кода

**Ожидаемый результат для каждой найденной проблемы:**
1. Точное описание проблемы
2. Уровень критичности (критический/высокий/средний/низкий)
3. Конкретное место в коде (строки)
4. Возможные последствия проблемы
5. Исправленная версия кода
6. Рекомендации по предотвращению подобных проблем в будущем

**Если проблем не найдено:**
- Подтверди корректность кода
- Предложи рекомендации по улучшению качества
- Оцени общую надежность кода

**Код для анализа:**
```{language}
{code}
```

**Дополнительная информация (если есть):**
{описание_проблемы_или_контекст})";
    templates.append(debugTemplate);
    
    // Шаблон 3: Генерация комплексной документации
    TemplateInfo docsTemplate;
    docsTemplate.name = "Генерация комплексной документации";
    docsTemplate.category = "Документирование";
    docsTemplate.description = "Создание полной документации: от inline комментариев до архитектурного описания";
    docsTemplate.prompt = R"(Создай исчерпывающую документацию для предоставленного кода. Охвати все уровни:

📄 **Inline документация (комментарии в коде):**
- Добавь/обнови комментарии к публичным интерфейсам
- Документируй сложные алгоритмы и бизнес-логику
- Добавь пояснения к неочевидным решениям
- Укажи допущения и ограничения

📄 **API документация (публичные интерфейсы):**
- Для каждой публичной функции/метода:
  * Назначение и краткое описание
  * Параметры (назначение, ожидаемые типы, ограничения)
  * Возвращаемое значение
  * Исключения/ошибки
  * Примеры использования
  * Ограничения и предостережения

📄 **Архитектурная документация:**
- Общее описание системы/модуля
- Диаграмма компонентов (в текстовом формате)
- Взаимодействие между модулями
- Потоки данных и управления
- Зависимости от внешних систем

📄 **Документация для разработчиков:**
- Требования к окружению
- Инструкции по сборке и запуску
- Конфигурационные параметры
- Требования к тестированию
- Руководство по внесению изменений

📄 **Операционная документация:**
- Мониторинг и метрики
- Процедуры восстановления при сбоях
- Производительность и масштабирование
- Резервное копирование и восстановление

**Форматы документации:**
1. Комментарии в коде: в стиле документации для данного языка
2. README файл: Markdown формат
3. API документация: структурированный текст или OpenAPI если применимо
4. Архитектурное описание: текст с диаграммами в ASCII/PlantUML формате

**Код для документирования:**
```{language}
{code}
```

**Контекст проекта (если известен):**
{контекст_проекта})";
    templates.append(docsTemplate);
    
    // Шаблон 4: Оптимизация производительности
    TemplateInfo perfTemplate;
    perfTemplate.name = "Оптимизация производительности";
    perfTemplate.category = "Производительность";
    perfTemplate.description = "Анализ и оптимизация кода для улучшения скорости работы и эффективности";
    perfTemplate.prompt = R"(Проведи всесторонний анализ производительности предоставленного кода и предложи оптимизации:

⚡ **Анализ сложности алгоритмов:**
- Определи временную и пространственную сложность (Big-O)
- Выяви алгоритмы с неоптимальной сложностью
- Предложи альтернативные алгоритмы с лучшей сложностью
- Проанализируй вложенные циклы и рекурсии

⚡ **Оптимизация структур данных:**
- Оцени эффективность используемых структур данных
- Предложи более подходящие структуры данных
- Проверь использование кэширования и мемоизации
- Оптимизируй доступ и модификацию данных

⚡ **Профилирование ресурсов:**
- Проанализируй использование памяти
- Выяви утечки памяти или избыточные аллокации
- Проверь эффективность использования процессора
- Определи блокирующие операции

⚡ **Параллелизм и асинхронность (если применимо):**
- Определи возможности для распараллеливания
- Проверь корректность синхронизации
- Выяви блокировки и race conditions
- Предложи улучшения для многопоточного кода

⚡ **Микроптимизации:**
- Выяви избыточные вычисления
- Оптимизируй частые операции
- Проверь эффективность использования библиотек
- Устрани ненужные преобразования типов

**Ожидаемый результат:**
1. Детальный анализ производительности с метриками
2. Список найденных узких мест производительности
3. Оптимизированная версия кода
4. Оценка ожидаемого ускорения (в процентах или разах)
5. Рекомендации по дальнейшим оптимизациям
6. Trade-offs оптимизаций (что улучшилось, что ухудшилось)

**Код для оптимизации:**
```{language}
{code}
```

**Целевые метрики (если известны):**
{целевые_метрики_производительности})";
    templates.append(perfTemplate);
    
    // Шаблон 5: Анализ архитектуры
    TemplateInfo archTemplate;
    archTemplate.name = "Анализ и улучшение архитектуры";
    archTemplate.category = "Архитектура";
    archTemplate.description = "Оценка и улучшение архитектурных решений, проектирование масштабируемых систем";
    archTemplate.prompt = R"(Проведи углубленный анализ архитектуры предоставленного кода и предложи улучшения:

🏗️ **Принципы проектирования:**
- Оцени соответствие принципам SOLID
- Проверь применение принципа DRY (Don't Repeat Yourself)
- Проанализируй сцепление и связность модулей
- Оцени модульность и переиспользуемость

🏗️ **Паттерны проектирования:**
- Определи используемые паттерны проектирования
- Предложи применение уместных паттернов
- Выяви антипаттерны и предложи альтернативы
- Оцени соответствие доменной модели

🏗️ **Масштабируемость и расширяемость:**
- Проанализируй возможности горизонтального масштабирования
- Проверь легковесность добавления новых функций
- Оцени сложность модификации существующего функционала
- Проанализируй управление состоянием системы

🏗️ **Тестируемость:**
- Оцени покрытие тестами (на основе структуры кода)
- Проверь изолируемость модулей для тестирования
- Выяви зависимости, затрудняющие тестирование
- Предложи улучшения для повышения тестируемости

🏗️ **Интеграция и взаимодействие:**
- Проанализируй интерфейсы между модулями
- Проверь согласованность API
- Оцени обработку ошибок на границах модулей
- Проанализируй интеграцию с внешними системами

**Ожидаемый результат:**
1. Архитектурная оценка кода (по шкале 1-10)
2. Диаграмма текущей архитектуры (в текстовом формате)
3. Список архитектурных проблем и их критичность
4. Предложенная улучшенная архитектура
5. План миграции к новой архитектуре
6. Оценка трудозатрат на реархитектурирование

**Код для анализа:**
```{language}
{code}
```

**Контекст системы (если известен):**
{контекст_системы_и_требования})";
    templates.append(archTemplate);
    
    // Шаблон 6: Генерация тестов
    TemplateInfo testsTemplate;
    testsTemplate.name = "Генерация тестового покрытия";
    testsTemplate.category = "Тестирование";
    testsTemplate.description = "Создание unit, integration и функциональных тестов для обеспечения качества кода";
    testsTemplate.prompt = R"(Создай комплексное тестовое покрытие для предоставленного кода:

🧪 **Unit тесты:**
- Для каждой публичной функции/метода создай тесты
- Покрой нормальные сценарии использования
- Добавь тесты для граничных случаев
- Включи тесты для обработки ошибок и исключений
- Используй моки и стабы для изоляции тестируемого кода

🧪 **Интеграционные тесты:**
- Протестируй взаимодействие между модулями
- Добавь тесты для интеграции с внешними системами
- Проверь корректность работы цепочек вызовов
- Протестируй обработку данных между границами модулей

🧪 **Функциональные тесты:**
- Создай тесты для ключевых пользовательских сценариев
- Протестируй бизнес-логику от начала до конца
- Добавь тесты для различных состояний системы
- Включи тесты для конфигурационных вариантов

🧪 **Тесты производительности:**
- Добавь benchmark тесты для критичных по производительности операций
- Протестируй масштабируемость при увеличении нагрузки
- Измерь потребление ресурсов в различных сценариях

🧪 **Тесты безопасности:**
- Протестируй обработку невалидного ввода
- Добавь тесты для проверки авторизации и аутентификации
- Протестируй устойчивость к атакам (если применимо)

**Требования к тестам:**
1. **Читаемость:** понятные имена тестов, структурированный код
2. **Полнота:** покрытие всех значимых сценариев
3. **Изоляция:** минимальные зависимости между тестами
4. **Надежность:** стабильные, воспроизводимые тесты
5. **Поддерживаемость:** легко добавлять новые тесты

**Формат тестов:**
- Используй стандартный фреймворк для данного языка
- Включи setup и teardown логику где необходимо
- Добавь понятные сообщения об ошибках
- Включи комментарии о назначении тестов

**Ожидаемый результат:**
1. Полный набор тестов сгруппированных по типам
2. Оценка покрытия кода (в процентах)
3. Инструкции по запуску тестов
4. Рекомендации по поддержке тестов
5. Список сценариев, требующих ручного тестирования

**Код для тестирования:**
```{language}
{code}
```

**Контекст тестирования:**
{требования_к_тестированию_и_ограничения})";
    templates.append(testsTemplate);
    
    // Заполняем список
    templatesList->clear();
    for (const TemplateInfo &info : templates) {
        QListWidgetItem *item = new QListWidgetItem;
        
        QString itemText = QString("<b>%1</b>").arg(info.name);
        if (!info.category.isEmpty()) {
            itemText += QString(" <span style='color: #7f8c8d; font-size: 10px;'>(%2)</span>").arg(info.category);
        }
        
        if (!info.description.isEmpty()) {
            QString shortDesc = info.description;
            if (shortDesc.length() > 100) {
                shortDesc = shortDesc.left(100) + "...";
            }
            itemText += QString("<br><span style='color: #34495e; font-size: 11px;'>%1</span>").arg(shortDesc);
        }
        
        item->setText(itemText);
        item->setData(Qt::UserRole, templates.indexOf(info));
        item->setToolTip(info.description);
        
        // Устанавливаем иконку в зависимости от категории
        QString iconName;
        if (info.category == "Оптимизация") {
            iconName = "edit-redo";
        } else if (info.category == "Диагностика") {
            iconName = "tools-report-bug";
        } else if (info.category == "Документирование") {
            iconName = "document-edit";
        } else if (info.category == "Производительность") {
            iconName = "speedometer";
        } else if (info.category == "Архитектура") {
            iconName = "project-development";
        } else if (info.category == "Тестирование") {
            iconName = "checkmark";
        } else {
            iconName = "text-plain";
        }
        
        item->setIcon(QIcon::fromTheme(iconName));
        templatesList->addItem(item);
    }
}
```

## 2. Обновленный templates.json

```json
{
    "version": "1.0",
    "last_updated": "2024-01-15",
    "description": "Универсальные шаблоны промптов для любого языка программирования",
    "templates": [
        {
            "name": "Универсальный рефакторинг кода",
            "category": "Оптимизация",
            "description": "Улучшение структуры, читаемости и поддерживаемости кода без изменения функциональности",
            "prompt": "Проведи комплексный рефакторинг предоставленного кода. Сосредоточься на следующих аспектах:\n\n📌 **Читаемость и ясность:**\n- Улучши именование переменных, функций, классов и методов\n- Упрости сложные логические выражения и условия\n- Разбей длинные функции на более мелкие, специализированные\n- Удали мертвый код и неиспользуемые зависимости\n\n📌 **Архитектурные улучшения:**\n- Выяви и устрани дублирование кода (принцип DRY)\n- Улучши организацию модулей и их ответственность (принцип SRP)\n- Оптимизируй зависимости между компонентами\n- Примени паттерны проектирования где это уместно\n\n📌 **Качество кода:**\n- Повысь тестируемость кода\n- Улучши обработку ошибок и исключений\n- Оптимизируй использование ресурсов (память, процессор)\n- Устрани потенциальные точки отказа\n\n📌 **Производительность:**\n- Определи узкие места производительности\n- Предложи оптимизации алгоритмов\n- Улучши использование структур данных\n- Оптимизируй сложность операций (Big-O)\n\n**Ожидаемый результат:**\n1. Полный рефакторированный код\n2. Подробное объяснение всех внесенных изменений\n3. Оценка улучшения читаемости (по шкале 1-10)\n4. Список примененных принципов и паттернов\n5. Рекомендации для дальнейших улучшений\n\n**Исходный код для рефакторинга:**\n```{language}\n{code}\n```"
        },
        {
            "name": "Комплексный анализ и отладка",
            "category": "Диагностика",
            "description": "Поиск и исправление ошибок, анализ потенциальных проблем и уязвимостей",
            "prompt": "Проведи глубокий анализ предоставленного кода на наличие проблем. Исследуй следующие аспекты:\n\n🔍 **Синтаксические и семантические ошибки:**\n- Проверь корректность синтаксиса\n- Определи несоответствия типов данных\n- Выяви неинициализированные переменные\n- Проверь корректность вызовов функций/методов\n\n🔍 **Логические ошибки:**\n- Проанализируй бизнес-логику на корректность\n- Проверь условия циклов и условных операторов\n- Выяви ошибочные предположения о данных\n- Определи проблемы с обработкой граничных случаев\n\n🔍 **Безопасность и надежность:**\n- Проверь на уязвимости (инъекции, переполнения буфера)\n- Проанализируй обработку пользовательского ввода\n- Проверь корректность работы с памятью\n- Выяви потенциальные race conditions (для многопоточного кода)\n\n🔍 **Производительность и оптимизация:**\n- Определи неоптимальные алгоритмы\n- Выяви избыточные вычисления\n- Проверь эффективность использования структур данных\n- Проанализируй сложность операций\n\n🔍 **Качество и поддерживаемость:**\n- Оцени тестируемость кода\n- Проверь качество обработки ошибок\n- Выяви жесткие зависимости\n- Оцени модульность кода\n\n**Ожидаемый результат для каждой найденной проблемы:**\n1. Точное описание проблемы\n2. Уровень критичности (критический/высокий/средний/низкий)\n3. Конкретное место в коде (строки)\n4. Возможные последствия проблемы\n5. Исправленная версия кода\n6. Рекомендации по предотвращению подобных проблем в будущем\n\n**Если проблем не найдено:**\n- Подтверди корректность кода\n- Предложи рекомендации по улучшению качества\n- Оцени общую надежность кода\n\n**Код для анализа:**\n```{language}\n{code}\n```\n\n**Дополнительная информация (если есть):**\n{описание_проблемы_или_контекст}"
        },
        {
            "name": "Генерация комплексной документации",
            "category": "Документирование",
            "description": "Создание полной документации: от inline комментариев до архитектурного описания",
            "prompt": "Создай исчерпывающую документацию для предоставленного кода. Охвати все уровни:\n\n📄 **Inline документация (комментарии в коде):**\n- Добавь/обнови комментарии к публичным интерфейсам\n- Документируй сложные алгоритмы и бизнес-логику\n- Добавь пояснения к неочевидным решениям\n- Укажи допущения и ограничения\n\n📄 **API документация (публичные интерфейсы):**\n- Для каждой публичной функции/метода:\n  * Назначение и краткое описание\n  * Параметры (назначение, ожидаемые типы, ограничения)\n  * Возвращаемое значение\n  * Исключения/ошибки\n  * Примеры использования\n  * Ограничения и предостережения\n\n📄 **Архитектурная документация:**\n- Общее описание системы/модуля\n- Диаграмма компонентов (в текстовом формате)\n- Взаимодействие между модулями\n- Потоки данных и управления\n- Зависимости от внешних систем\n\n📄 **Документация для разработчиков:**\n- Требования к окружению\n- Инструкции по сборке и запуску\n- Конфигурационные параметры\n- Требования к тестированию\n- Руководство по внесению изменений\n\n📄 **Операционная документация:**\n- Мониторинг и метрики\n- Процедуры восстановления при сбоях\n- Производительность и масштабирование\n- Резервное копирование и восстановление\n\n**Форматы документации:**\n1. Комментарии в коде: в стиле документации для данного языка\n2. README файл: Markdown формат\n3. API документация: структурированный текст или OpenAPI если применимо\n4. Архитектурное описание: текст с диаграммами в ASCII/PlantUML формате\n\n**Код для документирования:**\n```{language}\n{code}\n```\n\n**Контекст проекта (если известен):**\n{контекст_проекта}"
        },
        {
            "name": "Оптимизация производительности",
            "category": "Производительность",
            "description": "Анализ и оптимизация кода для улучшения скорости работы и эффективности",
            "prompt": "Проведи всесторонний анализ производительности предоставленного кода и предложи оптимизации:\n\n⚡ **Анализ сложности алгоритмов:**\n- Определи временную и пространственную сложность (Big-O)\n- Выяви алгоритмы с неоптимальной сложностью\n- Предложи альтернативные алгоритмы с лучшей сложностью\n- Проанализируй вложенные циклы и рекурсии\n\n⚡ **Оптимизация структур данных:**\n- Оцени эффективность используемых структур данных\n- Предложи более подходящие структуры данных\n- Проверь использование кэширования и мемоизации\n- Оптимизируй доступ и модификацию данных\n\n⚡ **Профилирование ресурсов:**\n- Проанализируй использование памяти\n- Выяви утечки памяти или избыточные аллокации\n- Проверь эффективность использования процессора\n- Определи блокирующие операции\n\n⚡ **Параллелизм и асинхронность (если применимо):**\n- Определи возможности для распараллеливания\n- Проверь корректность синхронизации\n- Выяви блокировки и race conditions\n- Предложи улучшения для многопоточного кода\n\n⚡ **Микроптимизации:**\n- Выяви избыточные вычисления\n- Оптимизируй частые операции\n- Проверь эффективность использования библиотек\n- Устрани ненужные преобразования типов\n\n**Ожидаемый результат:**\n1. Детальный анализ производительности с метриками\n2. Список найденных узких мест производительности\n3. Оптимизированная версия кода\n4. Оценка ожидаемого ускорения (в процентах или разах)\n5. Рекомендации по дальнейшим оптимизациям\n6. Trade-offs оптимизаций (что улучшилось, что ухудшилось)\n\n**Код для оптимизации:**\n```{language}\n{code}\n```\n\n**Целевые метрики (если известны):**\n{целевые_метрики_производительности}"
        },
        {
            "name": "Анализ и улучшение архитектуры",
            "category": "Архитектура",
            "description": "Оценка и улучшение архитектурных решений, проектирование масштабируемых систем",
            "prompt": "Проведи углубленный анализ архитектуры предоставленного кода и предложи улучшения:\n\n🏗️ **Принципы проектирования:**\n- Оцени соответствие принципам SOLID\n- Проверь применение принципа DRY (Don't Repeat Yourself)\n- Проанализируй сцепление и связность модулей\n- Оцени модульность и переиспользуемость\n\n🏗️ **Паттерны проектирования:**\n- Определи используемые паттерны проектирования\n- Предложи применение уместных паттернов\n- Выяви антипаттерны и предложи альтернативы\n- Оцени соответствие доменной модели\n\n🏗️ **Масштабируемость и расширяемость:**\n- Проанализируй возможности горизонтального масштабирования\n- Проверь легковесность добавления новых функций\n- Оцени сложность модификации существующего функционала\n- Проанализируй управление состоянием системы\n\n🏗️ **Тестируемость:**\n- Оцени покрытие тестами (на основе структуры кода)\n- Проверь изолируемость модулей для тестирования\n- Выяви зависимости, затрудняющие тестирование\n- Предложи улучшения для повышения тестируемости\n\n🏗️ **Интеграция и взаимодействие:**\n- Проанализируй интерфейсы между модулями\n- Проверь согласованность API\n- Оцени обработку ошибок на границах модулей\n- Проанализируй интеграцию с внешними системами\n\n**Ожидаемый результат:**\n1. Архитектурная оценка кода (по шкале 1-10)\n2. Диаграмма текущей архитектуры (в текстовом формате)\n3. Список архитектурных проблем и их критичность\n4. Предложенная улучшенная архитектура\n5. План миграции к новой архитектуре\n6. Оценка трудозатрат на реархитектурирование\n\n**Код для анализа:**\n```{language}\n{code}\n```\n\n**Контекст системы (если известен):**\n{контекст_системы_и_требования}"
        },
        {
            "name": "Генерация тестового покрытия",
            "category": "Тестирование",
            "description": "Создание unit, integration и функциональных тестов для обеспечения качества кода",
            "prompt": "Создай комплексное тестовое покрытие для предоставленного кода:\n\n🧪 **Unit тесты:**\n- Для каждой публичной функции/метода создай тесты\n- Покрой нормальные сценарии использования\n- Добавь тесты для граничных случаев\n- Включи тесты для обработки ошибок и исключений\n- Используй моки и стабы для изоляции тестируемого кода\n\n🧪 **Интеграционные тесты:**\n- Протестируй взаимодействие между модулями\n- Добавь тесты для интеграции с внешними системами\n- Проверь корректность работы цепочек вызовов\n- Протестируй обработку данных между границами модулей\n\n🧪 **Функциональные тесты:**\n- Создай тесты для ключевых пользовательских сценариев\n- Протестируй бизнес-логику от начала до конца\n- Добавь тесты для различных состояний системы\n- Включи тесты для конфигурационных вариантов\n\n🧪 **Тесты производительности:**\n- Добавь benchmark тесты для критичных по производительности операций\n- Протестируй масштабируемость при увеличении нагрузки\n- Измерь потребление ресурсов в различных сценариях\n\n🧪 **Тесты безопасности:**\n- Протестируй обработку невалидного ввода\n- Добавь тесты для проверки авторизации и аутентификации\n- Протестируй устойчивость к атакам (если применимо)\n\n**Требования к тестам:**\n1. **Читаемость:** понятные имена тестов, структурированный код\n2. **Полнота:** покрытие всех значимых сценариев\n3. **Изоляция:** минимальные зависимости между тестами\n4. **Надежность:** стабильные, воспроизводимые тесты\n5. **Поддерживаемость:** легко добавлять новые тесты\n\n**Формат тестов:**\n- Используй стандартный фреймворк для данного языка\n- Включи setup и teardown логику где необходимо\n- Добавь понятные сообщения об ошибках\n- Включи комментарии о назначении тестов\n\n**Ожидаемый результат:**\n1. Полный набор тестов сгруппированных по типам\n2. Оценка покрытия кода (в процентах)\n3. Инструкции по запуску тестов\n4. Рекомендации по поддержке тестов\n5. Список сценариев, требующих ручного тестирования\n\n**Код для тестирования:**\n```{language}\n{code}\n```\n\n**Контекст тестирования:**\n{требования_к_тестированию_и_ограничения}"
        }
    ]
}
```

## Ключевые особенности промптов:

### 🌟 **Языковая независимость:**
- Используются общие понятия (функции, методы, классы, модули)
- Фокус на принципах, а не синтаксисе
- Универсальные метрики качества

### 🎯 **Структурированный подход:**
- Четкое разделение на категории и подкатегории
- Эмодзи для визуального разделения
- Маркированные списки для читаемости

### 📋 **Универсальные заполнители:**
- `{language}` - для указания языка программирования
- `{code}` - для вставки кода
- `{context}` - для дополнительного контекста

### 🎨 **Категории шаблонов:**
1. **Оптимизация** - рефакторинг и улучшение кода
2. **Диагностика** - поиск и исправление ошибок  
3. **Документирование** - создание документации
4. **Производительность** - анализ и оптимизация скорости
5. **Архитектура** - проектирование и анализ структуры
6. **Тестирование** - создание тестового покрытия

### 💡 **Преимущества такого подхода:**

1. **Гибкость:** работают с любым языком (C++, Python, Java, JavaScript, Go, Rust и т.д.)
2. **Комплексность:** охватывают полный цикл разработки
3. **Структурированность:** четкие ожидания от AI
4. **Практичность:** решают реальные задачи разработчиков
5. **Расширяемость:** легко добавлять новые шаблоны

Теперь пользователь может выбрать любой из этих шаблонов, и они будут работать с кодом на любом языке программирования! 🚀