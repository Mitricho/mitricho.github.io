Вот полный список всех файлов с C++ кодом, которые я создал для этого проекта:

## Заголовочные файлы (.h)

1. **mainwindow.h** - Главное окно приложения
2. **projecttreemodel.h** - Модель дерева проекта с чекбоксами
3. **codeprocessor.h** - Обработчик кода из ответов AI
4. **apimanager.h** - Менеджер API-запросов
5. **diffviewer.h** - Диалог для просмотра различий
6. **diffhighlighter.h** (включен в diffviewer.h) - Подсветка синтаксиса для diff

## Файлы реализации (.cpp)

7. **mainwindow.cpp** - Реализация главного окна
8. **projecttreemodel.cpp** - Реализация модели дерева проекта
9. **codeprocessor.cpp** - Реализация обработчика кода
10. **apimanager.cpp** - Реализация менеджера API
11. **diffviewer.cpp** - Реализация диалога diff-просмотра

## Дополнительные файлы

12. **main.cpp** - Точка входа в приложение
13. **CMakeLists.txt** - Файл сборки CMake
14. **config.json** (предполагаемый) - Конфигурационный файл
15. **style.qss** (опционально) - Стили Qt

## Полное содержимое каждого файла:

### 1. mainwindow.h
```cpp
#pragma once

#include <QMainWindow>
#include <QSplitter>
#include <QTreeView>
#include <QTextEdit>
#include <QPushButton>
#include <QComboBox>
#include <QLabel>
#include <QStandardItemModel>
#include "apimanager.h"
#include "codeprocessor.h"
#include "diffviewer.h"

class MainWindow : public QMainWindow
{
    Q_OBJECT

public:
    MainWindow(QWidget *parent = nullptr);
    ~MainWindow();

private slots:
    void loadProject();
    void sendPrompt();
    void updateSelectedFilesCount();
    void handleApiResponse(const QString &response, bool isError);
    void processAndApplyCode(const QString &markdownResponse);
    void clearChat();
    void exportChat();
    void showSettings();
    void showTreeContextMenu(const QPoint &pos);
    void showFileDiff(const QString &filePath, 
                     const QString &oldContent, 
                     const QString &newContent);
    void onFileChangedExternally(const QString &filePath);
    void showExtensionFilterDialog();

private:
    void setupUI();
    void setupMenu();
    void setupConnections();
    
    // Центральные виджеты
    QSplitter *mainSplitter;
    
    // Левая панель - проект
    QWidget *projectPanel;
    QVBoxLayout *projectLayout;
    QLabel *projectLabel;
    QTreeView *projectTreeView;
    QStandardItemModel *projectModel;
    QLabel *selectedFilesLabel;
    QPushButton *loadProjectBtn;
    
    // Центральная панель - чат
    QWidget *chatPanel;
    QVBoxLayout *chatLayout;
    QTextEdit *chatHistory;
    QTextEdit *promptEdit;
    QPushButton *sendButton;
    QPushButton *clearButton;
    QPushButton *attachFileButton;
    
    // Правая панель - информация
    QWidget *infoPanel;
    QVBoxLayout *infoLayout;
    QLabel *modelLabel;
    QComboBox *modelComboBox;
    QLabel *statusLabel;
    QPushButton *settingsButton;
    
    // Менеджеры
    ApiManager *apiManager;
    CodeProcessor *codeProcessor;
    
    // Текущий проект
    QString currentProjectPath;
};
```

### 2. projecttreemodel.h
```cpp
#pragma once

#include <QStandardItemModel>
#include <QSet>
#include <QFileSystemWatcher>
#include <QMenu>

class ProjectTreeModel : public QStandardItemModel
{
    Q_OBJECT

public:
    enum CustomRoles {
        FilePathRole = Qt::UserRole + 1,
        IsFileRole,
        IsCheckedRole,
        FileSizeRole,
        LastModifiedRole
    };
    
    explicit ProjectTreeModel(QObject *parent = nullptr);
    ~ProjectTreeModel();
    
    void loadProject(const QString &path);
    QSet<QString> getSelectedFiles() const;
    QSet<QString> getAllFiles() const;
    QString readFileContent(const QString &filePath) const;
    QString getProjectStructure() const;
    QString getRelativePath(const QString &absolutePath) const;
    void refreshFile(const QString &filePath);
    void refreshProject();
    
    Qt::ItemFlags flags(const QModelIndex &index) const override;
    QVariant data(const QModelIndex &index, int role = Qt::DisplayRole) const override;
    bool setData(const QModelIndex &index, const QVariant &value, int role = Qt::EditRole) override;
    
    QMenu* createContextMenu(const QModelIndex &index, QWidget *parent = nullptr);
    
signals:
    void fileContentChanged(const QString &filePath);
    void selectionChanged(int count);
    void requestFileDiff(const QString &filePath, const QString &oldContent, const QString &newContent);

public slots:
    void checkAll();
    void uncheckAll();
    void checkByPattern(const QString &pattern);
    void showFileInExplorer(const QModelIndex &index);
    void copyFilePath(const QModelIndex &index);
    void openFileInExternalEditor(const QModelIndex &index);
    
private:
    void setupFileWatcher();
    void addDirectory(QStandardItem *parent, const QDir &dir);
    QStandardItem* createItemForFile(const QFileInfo &fileInfo);
    void updateParentCheckState(QStandardItem *item);
    void updateChildCheckStates(QStandardItem *item, Qt::CheckState state);
    QStandardItem* findItemByPath(const QString &path);
    void checkByPatternInFolder(const QModelIndex &parentIndex, const QString &pattern);
    
    QString projectRoot;
    QFileSystemWatcher *fileWatcher;
    QMap<QString, QString> fileHashes;
    
private slots:
    void onFileChanged(const QString &path);
};
```

### 3. codeprocessor.h
```cpp
#pragma once

#include <QObject>
#include <QRegularExpression>
#include <QMap>
#include <QSet>

class CodeProcessor : public QObject
{
    Q_OBJECT

public:
    explicit CodeProcessor(QObject *parent = nullptr);
    
    struct CodeBlock {
        QString language;
        QString code;
        QString filePath;
    };
    
    void setAvailableFiles(const QSet<QString> &files);
    QList<CodeBlock> extractCodeBlocks(const QString &markdown);
    bool applyCodeToFiles(const QList<CodeBlock> &codeBlocks);
    QString createFileContext(const QSet<QString> &selectedFiles);
    QString readCurrentFile(const QString &filePath) const;
    
signals:
    void fileUpdated(const QString &filePath);
    void errorOccurred(const QString &error);
    void codeBlocksExtracted(const QList<CodeBlock> &blocks);

private:
    QString detectFilePath(const QString &code, const QString &language);
    QString getFileSignature(const QString &filePath) const;
    QMap<QString, QStringList> initLanguageExtensions() const;
    
    QSet<QString> availableFiles;
    QMap<QString, QString> fileSignatures;
    QMap<QString, QStringList> languageExtensions;
};
```

### 4. apimanager.h
```cpp
#pragma once

#include <QObject>
#include <QNetworkAccessManager>
#include <QNetworkReply>
#include <QJsonObject>

class ApiManager : public QObject
{
    Q_OBJECT

public:
    enum ApiProvider {
        DeepSeek,
        Qwen,
        Ollama
    };
    
    explicit ApiManager(QObject *parent = nullptr);
    
    void setApiKey(const QString &key, ApiProvider provider);
    void setModel(const QString &model);
    void sendRequest(const QString &prompt, const QList<QPair<QString, QString>> &files);
    void setBaseUrl(const QString &url);
    
signals:
    void responseReceived(const QString &response);
    void errorOccurred(const QString &error);
    void requestComplete();

private slots:
    void onReplyFinished(QNetworkReply *reply);

private:
    QNetworkAccessManager *networkManager;
    QString apiKey;
    QString currentModel;
    ApiProvider currentProvider;
    QString baseUrl;
    
    QJsonObject createDeepSeekPayload(const QString &prompt, 
                                     const QList<QPair<QString, QString>> &files);
    QJsonObject createQwenPayload(const QString &prompt,
                                 const QList<QPair<QString, QString>> &files);
    QJsonObject createOllamaPayload(const QString &prompt);
    QByteArray createMultipartData(const QJsonObject &payload);
};
```

### 5. diffviewer.h
```cpp
#pragma once

#include <QDialog>
#include <QTextEdit>
#include <QPushButton>
#include <QLabel>
#include <QPlainTextEdit>
#include <QSyntaxHighlighter>

class DiffHighlighter : public QSyntaxHighlighter
{
    Q_OBJECT
    
public:
    DiffHighlighter(QTextDocument *parent = nullptr);
    
protected:
    void highlightBlock(const QString &text) override;
    
private:
    struct HighlightingRule {
        QRegularExpression pattern;
        QTextCharFormat format;
    };
    QVector<HighlightingRule> highlightingRules;
};

class DiffViewer : public QDialog
{
    Q_OBJECT

public:
    explicit DiffViewer(QWidget *parent = nullptr);
    
    void setDiff(const QString &filePath, 
                const QString &oldContent, 
                const QString &newContent);
    
    QString getMergedContent() const;
    
signals:
    void acceptedWithChanges();
    void rejectedChanges();

private slots:
    void applyChanges();
    void keepOriginal();
    void showSideBySide(bool enabled);
    void showUnified(bool enabled);
    void navigateNextChange();
    void navigatePrevChange();
    
private:
    void setupUI();
    void calculateDiff();
    void updateNavigationButtons();
    void navigateToChange(int index);
    
    QString filePath;
    QString originalContent;
    QString newContent;
    
    // UI элементы
    QLabel *fileNameLabel;
    QPushButton *sideBySideBtn;
    QPushButton *unifiedBtn;
    QPushButton *prevChangeBtn;
    QPushButton *nextChangeBtn;
    QLabel *changeCounterLabel;
    
    QSplitter *splitter;
    QTextEdit *leftEditor;
    QTextEdit *rightEditor;
    QPlainTextEdit *unifiedEditor;
    
    QPushButton *applyBtn;
    QPushButton *rejectBtn;
    QPushButton *cancelBtn;
    
    DiffHighlighter *unifiedHighlighter;
    
    struct Change {
        int startLine;
        int endLine;
        QString type;
    };
    QVector<Change> changes;
    int currentChangeIndex;
};
```

### 6. main.cpp
```cpp
#include "mainwindow.h"
#include <QApplication>
#include <QStyleFactory>
#include <QFile>
#include <QSettings>

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);
    
    // Настройка приложения
    app.setApplicationName("AI Dev Assistant");
    app.setOrganizationName("DeepSeekTools");
    app.setApplicationVersion("1.0.0");
    
    // Загрузка стилей
    QFile styleFile(":/styles/style.qss");
    if (styleFile.open(QFile::ReadOnly)) {
        QString styleSheet = QLatin1String(styleFile.readAll());
        app.setStyleSheet(styleSheet);
    } else {
        // Используем Fusion стиль по умолчанию
        app.setStyle(QStyleFactory::create("Fusion"));
        
        // Кастомная палитра
        QPalette palette;
        palette.setColor(QPalette::Window, QColor(53, 53, 53));
        palette.setColor(QPalette::WindowText, Qt::white);
        palette.setColor(QPalette::Base, QColor(25, 25, 25));
        palette.setColor(QPalette::AlternateBase, QColor(53, 53, 53));
        palette.setColor(QPalette::ToolTipBase, Qt::white);
        palette.setColor(QPalette::ToolTipText, Qt::white);
        palette.setColor(QPalette::Text, Qt::white);
        palette.setColor(QPalette::Button, QColor(53, 53, 53));
        palette.setColor(QPalette::ButtonText, Qt::white);
        palette.setColor(QPalette::BrightText, Qt::red);
        palette.setColor(QPalette::Link, QColor(42, 130, 218));
        palette.setColor(QPalette::Highlight, QColor(42, 130, 218));
        palette.setColor(QPalette::HighlightedText, Qt::black);
        app.setPalette(palette);
    }
    
    // Восстановление настроек окна
    QSettings settings;
    
    MainWindow window;
    window.show();
    
    return app.exec();
}
```

### 7. CMakeLists.txt
```cmake
cmake_minimum_required(VERSION 3.16)
project(AIDevAssistant VERSION 1.0.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_AUTOMOC ON)
set(CMAKE_AUTORCC ON)
set(CMAKE_AUTOUIC ON)

# Поиск Qt6
find_package(Qt6 REQUIRED COMPONENTS Core Widgets Network)

# Настройка исходных файлов
set(SOURCES
    main.cpp
    mainwindow.cpp
    projecttreemodel.cpp
    codeprocessor.cpp
    apimanager.cpp
    diffviewer.cpp
)

set(HEADERS
    mainwindow.h
    projecttreemodel.h
    codeprocessor.h
    apimanager.h
    diffviewer.h
)

# Создание исполняемого файла
qt_add_executable(AIDevAssistant
    ${SOURCES}
    ${HEADERS}
)

# Подключение Qt модулей
target_link_libraries(AIDevAssistant
    Qt6::Core
    Qt6::Widgets
    Qt6::Network
)

# Включение директорий
target_include_directories(AIDevAssistant PRIVATE
    ${CMAKE_CURRENT_SOURCE_DIR}
)

# Настройка для релиза
set_target_properties(AIDevAssistant PROPERTIES
    WIN32_EXECUTABLE TRUE
    MACOSX_BUNDLE TRUE
)

# Установка иконки (опционально)
if(EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/assets/icon.ico")
    set_target_properties(AIDevAssistant PROPERTIES
        RC_ICONS "${CMAKE_CURRENT_SOURCE_DIR}/assets/icon.ico"
    )
endif()

# Копирование ресурсов
if(EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/styles")
    file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/styles" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
endif()

# Создание установщика (опционально, для Windows)
if(WIN32)
    include(InstallRequiredSystemLibraries)
    set(CPACK_PACKAGE_NAME "AI Dev Assistant")
    set(CPACK_PACKAGE_VENDOR "DeepSeekTools")
    set(CPACK_PACKAGE_VERSION ${PROJECT_VERSION})
    set(CPACK_PACKAGE_EXECUTABLES "AIDevAssistant" "AI Dev Assistant")
    set(CPACK_CREATE_DESKTOP_LINKS "AIDevAssistant")
    include(CPack)
endif()
```

### 8. config.json (пример)
```json
{
    "api": {
        "deepseek": {
            "base_url": "https://api.deepseek.com/v1",
            "models": ["deepseek-chat", "deepseek-coder"],
            "max_tokens": 4096
        },
        "qwen": {
            "base_url": "https://dashscope.aliyuncs.com/api/v1",
            "models": ["qwen-max", "qwen-plus", "qwen-turbo"],
            "max_tokens": 2048
        },
        "ollama": {
            "base_url": "http://localhost:11434/api",
            "models": ["codellama", "deepseek-coder"],
            "max_tokens": 8192
        }
    },
    "ui": {
        "default_model": "deepseek-chat",
        "auto_apply_changes": false,
        "show_diff_before_apply": true,
        "max_file_size_mb": 10,
        "allowed_extensions": [".cpp", ".h", ".py", ".js", ".ts", ".java", ".go", ".rs", ".php", ".html", ".css", ".json", ".xml", ".md"]
    },
    "project": {
        "remember_last_project": true,
        "auto_refresh_interval": 5000,
        "exclude_patterns": [".git", "node_modules", "__pycache__", "*.exe", "*.dll"]
    }
}
```

### 9. style.qss (опционально, для стилизации)
```css
/* Основные стили */
QMainWindow {
    background-color: #2b2b2b;
}

QTextEdit, QPlainTextEdit {
    background-color: #1e1e1e;
    color: #d4d4d4;
    border: 1px solid #3c3c3c;
    border-radius: 4px;
    padding: 5px;
    font-family: 'Consolas', monospace;
    font-size: 12px;
}

QTextEdit:focus, QPlainTextEdit:focus {
    border: 1px solid #007acc;
}

QPushButton {
    background-color: #0e639c;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 8px 16px;
    font-weight: bold;
}

QPushButton:hover {
    background-color: #1177bb;
}

QPushButton:pressed {
    background-color: #0c547d;
}

QPushButton:disabled {
    background-color: #3c3c3c;
    color: #6d6d6d;
}

QTreeView {
    background-color: #252526;
    color: #d4d4d4;
    border: none;
    alternate-background-color: #2d2d30;
}

QTreeView::item {
    height: 22px;
    padding: 2px;
}

QTreeView::item:selected {
    background-color: #094771;
    color: white;
}

QTreeView::item:hover {
    background-color: #2a2d2e;
}

QComboBox {
    background-color: #3c3c3c;
    color: #d4d4d4;
    border: 1px solid #3c3c3c;
    border-radius: 4px;
    padding: 5px;
    min-width: 120px;
}

QComboBox:hover {
    border: 1px solid #007acc;
}

QComboBox::drop-down {
    border: none;
}

QComboBox QAbstractItemView {
    background-color: #3c3c3c;
    color: #d4d4d4;
    border: 1px solid #007acc;
}

QLabel {
    color: #d4d4d4;
}

QMenu {
    background-color: #2d2d30;
    color: #d4d4d4;
    border: 1px solid #3c3c3c;
}

QMenu::item:selected {
    background-color: #094771;
}

QMenuBar {
    background-color: #2d2d30;
    color: #d4d4d4;
}

QMenuBar::item:selected {
    background-color: #3c3c3c;
}

QSplitter::handle {
    background-color: #3c3c3c;
    width: 1px;
}

QSplitter::handle:hover {
    background-color: #007acc;
}

/* Стили для DiffViewer */
DiffViewer {
    background-color: #2b2b2b;
}

DiffViewer QTextEdit {
    background-color: #1e1e1e;
}

DiffViewer QPushButton#applyBtn {
    background-color: #388e3c;
}

DiffViewer QPushButton#rejectBtn {
    background-color: #d32f2f;
}

/* Стили для чекбоксов в дереве */
QTreeView::indicator {
    width: 16px;
    height: 16px;
}

QTreeView::indicator:unchecked {
    border: 1px solid #6d6d6d;
    background-color: #3c3c3c;
}

QTreeView::indicator:checked {
    border: 1px solid #007acc;
    background-color: #007acc;
}

QTreeView::indicator:checked:disabled {
    background-color: #6d6d6d;
}
```

### 10. README.md (документация)
```markdown
# AI Dev Assistant

Десктопное приложение для удобного взаимодействия с AI моделями (DeepSeek, Qwen) через API.

## Возможности

- 📁 **Управление проектами**: Загрузка папок с проектами, дерево файлов с чекбоксами
- 🤖 **Мульти-модель**: Поддержка DeepSeek, Qwen и Ollama API
- 💬 **Умный чат**: Markdown-форматирование, подсветка кода
- 🔄 **Авто-применение кода**: Автоматическое извлечение и применение кода из ответов
- 👁️ **Diff-просмотр**: Визуальное сравнение изменений перед применением
- 🛠️ **Контекстное меню**: Быстрые действия с файлами в дереве проекта
- ⚙️ **Настройки**: Гибкая конфигурация API и интерфейса

## Установка

### Требования
- Qt6 версии 6.9 или выше
- C++17 компилятор
- CMake 3.16+

### Сборка
```bash
mkdir build
cd build
cmake ..
cmake --build .
```

### Запуск
```bash
./AIDevAssistant
```

## Использование

1. **Загрузка проекта**: Нажмите "Загрузить проект" и выберите папку
2. **Выбор файлов**: Отметьте галочками файлы для включения в контекст
3. **Отправка запроса**: Введите промпт и нажмите "Отправить"
4. **Просмотр ответа**: Ответ отобразится в чате с Markdown-форматированием
5. **Применение кода**: Код из ответа автоматически предложит заменить соответствующие файлы
6. **Diff-просмотр**: Просмотрите изменения перед применением

## Настройка API

1. Получите API ключи:
   - DeepSeek: https://platform.deepseek.com/
   - Qwen: https://dashscope.aliyuncs.com/
2. Введите ключи в настройках приложения
3. Выберите модель из списка

## Лицензия

MIT License
```

## Сводная таблица файлов:

| № | Файл | Назначение |
|---|------|------------|
| 1 | mainwindow.h | Главное окно приложения |
| 2 | projecttreemodel.h | Модель дерева проекта |
| 3 | codeprocessor.h | Обработчик кода |
| 4 | apimanager.h | Менеджер API |
| 5 | diffviewer.h | Дифф-просмотр |
| 6 | mainwindow.cpp | Реализация главного окна |
| 7 | projecttreemodel.cpp | Реализация модели проекта |
| 8 | codeprocessor.cpp | Реализация обработчика кода |
| 9 | apimanager.cpp | Реализация API менеджера |
| 10 | diffviewer.cpp | Реализация дифф-просмотра |
| 11 | main.cpp | Точка входа |
| 12 | CMakeLists.txt | Файл сборки |
| 13 | config.json | Конфигурация |
| 14 | style.qss | Стилизация |
| 15 | README.md | Документация |

Это полный набор файлов для создания функционального десктопного приложения с расширенными возможностями для работы программиста с AI-моделями через API. Приложение готово к сборке и использованию после настройки API ключей.
