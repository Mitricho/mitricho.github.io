Отличные дополнения! Давайте реализуем контекстное меню и дифф-просмотр. Вот расширенная реализация:

## 1. Расширенная модель дерева с контекстным меню

### ProjectTreeModel с поддержкой чекбоксов и контекстного меню:

```cpp
// projecttreemodel.h (расширенная версия)
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
    void refreshProject();
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
    
    QString projectRoot;
    QFileSystemWatcher *fileWatcher;
    QMap<QString, QString> fileHashes; // Для отслеживания изменений
    
private slots:
    void onFileChanged(const QString &path);
};
```

### Реализация контекстного меню:

```cpp
// projecttreemodel.cpp (частичная реализация)
#include "projecttreemodel.h"
#include <QDir>
#include <QFile>
#include <QTextStream>
#include <QCryptographicHash>
#include <QDesktopServices>
#include <QApplication>
#include <QClipboard>
#include <QFileDialog>
#include <QInputDialog>
#include <QMessageBox>
#include <QProcess>

QMenu* ProjectTreeModel::createContextMenu(const QModelIndex &index, QWidget *parent)
{
    QMenu *menu = new QMenu(parent);
    
    if (index.isValid()) {
        QString filePath = data(index, FilePathRole).toString();
        bool isFile = data(index, IsFileRole).toBool();
        
        // Общие действия
        if (isFile) {
            menu->addAction("Открыть во внешнем редакторе", [this, index]() {
                openFileInExternalEditor(index);
            });
            
            menu->addAction("Показать в проводнике", [this, index]() {
                showFileInExplorer(index);
            });
            
            menu->addAction("Копировать путь", [this, index]() {
                copyFilePath(index);
            });
            
            menu->addSeparator();
            
            // Проверка/снятие галочки
            bool isChecked = data(index, IsCheckedRole).toBool();
            if (isChecked) {
                menu->addAction("Исключить из промпта", [this, index]() {
                    setData(index, Qt::Unchecked, Qt::CheckStateRole);
                });
            } else {
                menu->addAction("Включить в промпт", [this, index]() {
                    setData(index, Qt::Checked, Qt::CheckStateRole);
                });
            }
            
            menu->addSeparator();
            
            // Дифф-действия (если есть сохраненная версия)
            if (fileHashes.contains(filePath)) {
                menu->addAction("Сравнить с сохраненной версией", [this, filePath]() {
                    QString currentContent = readFileContent(filePath);
                    // Здесь нужно получить сохраненную версию
                    emit requestFileDiff(filePath, "", currentContent);
                });
            }
        } else {
            // Действия для папок
            menu->addAction("Выбрать все в папке", [this, index]() {
                QStandardItem *item = itemFromIndex(index);
                if (item) {
                    updateChildCheckStates(item, Qt::Checked);
                }
            });
            
            menu->addAction("Снять все в папке", [this, index]() {
                QStandardItem *item = itemFromIndex(index);
                if (item) {
                    updateChildCheckStates(item, Qt::Unchecked);
                }
            });
            
            menu->addAction("Выбрать по шаблону...", [this, index]() {
                bool ok;
                QString pattern = QInputDialog::getText(parent, 
                    "Выбор по шаблону",
                    "Введите шаблон (например, *.cpp или test*.py):",
                    QLineEdit::Normal, "", &ok);
                if (ok && !pattern.isEmpty()) {
                    checkByPatternInFolder(index, pattern);
                }
            });
        }
        
        menu->addSeparator();
    }
    
    // Действия на уровне проекта
    menu->addAction("Выбрать все файлы", this, &ProjectTreeModel::checkAll);
    menu->addAction("Снять все", this, &ProjectTreeModel::uncheckAll);
    menu->addAction("Выбрать по расширению...", [this, parent]() {
        QStringList extensions;
        QSet<QString> allFiles = getAllFiles();
        for (const QString &file : allFiles) {
            QFileInfo info(file);
            extensions << "*" + info.suffix();
        }
        extensions.removeDuplicates();
        
        bool ok;
        QString extension = QInputDialog::getItem(parent,
            "Выбор по расширению",
            "Выберите расширение файлов:",
            extensions, 0, false, &ok);
        if (ok) {
            checkByPattern(extension);
        }
    });
    
    menu->addSeparator();
    menu->addAction("Обновить проект", this, &ProjectTreeModel::refreshProject);
    
    return menu;
}

void ProjectTreeModel::checkByPattern(const QString &pattern)
{
    QSet<QString> allFiles = getAllFiles();
    QRegularExpression regex(QRegularExpression::wildcardToRegularExpression(pattern),
                           QRegularExpression::CaseInsensitiveOption);
    
    for (const QString &filePath : allFiles) {
        QFileInfo info(filePath);
        if (regex.match(info.fileName()).hasMatch()) {
            QStandardItem *item = findItemByPath(filePath);
            if (item) {
                item->setCheckState(Qt::Checked);
            }
        }
    }
    emit selectionChanged(getSelectedFiles().size());
}
```

## 2. DiffViewer - виджет для просмотра различий

```cpp
// diffviewer.h
#pragma once

#include <QDialog>
#include <QTextEdit>
#include <QPushButton>
#include <QLabel>
#include <QHBoxLayout>
#include <QSplitter>
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
        QString type; // "added", "removed", "modified"
    };
    QVector<Change> changes;
    int currentChangeIndex;
};
```

### Реализация DiffViewer:

```cpp
// diffviewer.cpp
#include "diffviewer.h"
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QGroupBox>
#include <QScrollBar>
#include <QTextBlock>
#include <QMessageBox>
#include <QTextCursor>
#include <QPainter>
#include <QTextCharFormat>
#include <QRegularExpression>

DiffHighlighter::DiffHighlighter(QTextDocument *parent)
    : QSyntaxHighlighter(parent)
{
    // Подсветка для удаленных строк
    HighlightingRule removedRule;
    removedRule.pattern = QRegularExpression("^\\-.*");
    removedRule.format.setForeground(Qt::red);
    removedRule.format.setBackground(QColor(255, 230, 230));
    highlightingRules.append(removedRule);
    
    // Подсветка для добавленных строк
    HighlightingRule addedRule;
    addedRule.pattern = QRegularExpression("^\\+.*");
    addedRule.format.setForeground(Qt::darkGreen);
    addedRule.format.setBackground(QColor(230, 255, 230));
    highlightingRules.append(addedRule);
    
    // Подсветка для информационных строк
    HighlightingRule infoRule;
    infoRule.pattern = QRegularExpression("^@@.*@@");
    infoRule.format.setForeground(Qt::blue);
    infoRule.format.setFontWeight(QFont::Bold);
    highlightingRules.append(infoRule);
}

void DiffHighlighter::highlightBlock(const QString &text)
{
    for (const HighlightingRule &rule : highlightingRules) {
        QRegularExpressionMatchIterator matchIterator = rule.pattern.globalMatch(text);
        while (matchIterator.hasNext()) {
            QRegularExpressionMatch match = matchIterator.next();
            setFormat(match.capturedStart(), match.capturedLength(), rule.format);
        }
    }
}

DiffViewer::DiffViewer(QWidget *parent)
    : QDialog(parent)
    , currentChangeIndex(-1)
{
    setupUI();
    setWindowTitle("Просмотр изменений");
    resize(1200, 800);
}

void DiffViewer::setupUI()
{
    QVBoxLayout *mainLayout = new QVBoxLayout(this);
    
    // Панель инструментов
    QHBoxLayout *toolbarLayout = new QHBoxLayout;
    
    fileNameLabel = new QLabel;
    fileNameLabel->setStyleSheet("font-weight: bold; font-size: 14px;");
    toolbarLayout->addWidget(fileNameLabel);
    
    toolbarLayout->addStretch();
    
    sideBySideBtn = new QPushButton("Раздельный вид");
    sideBySideBtn->setCheckable(true);
    sideBySideBtn->setChecked(true);
    connect(sideBySideBtn, &QPushButton::toggled, this, &DiffViewer::showSideBySide);
    toolbarLayout->addWidget(sideBySideBtn);
    
    unifiedBtn = new QPushButton("Объединенный вид");
    unifiedBtn->setCheckable(true);
    connect(unifiedBtn, &QPushButton::toggled, this, &DiffViewer::showUnified);
    toolbarLayout->addWidget(unifiedBtn);
    
    toolbarLayout->addStretch();
    
    prevChangeBtn = new QPushButton("← Предыдущее изменение");
    prevChangeBtn->setEnabled(false);
    connect(prevChangeBtn, &QPushButton::clicked, this, &DiffViewer::navigatePrevChange);
    toolbarLayout->addWidget(prevChangeBtn);
    
    changeCounterLabel = new QLabel("0/0");
    toolbarLayout->addWidget(changeCounterLabel);
    
    nextChangeBtn = new QPushButton("Следующее изменение →");
    nextChangeBtn->setEnabled(false);
    connect(nextChangeBtn, &QPushButton::clicked, this, &DiffViewer::navigateNextChange);
    toolbarLayout->addWidget(nextChangeBtn);
    
    mainLayout->addLayout(toolbarLayout);
    
    // Область просмотра diff
    splitter = new QSplitter(Qt::Horizontal);
    
    // Левый редактор (оригинал)
    leftEditor = new QTextEdit;
    leftEditor->setReadOnly(true);
    leftEditor->setFont(QFont("Consolas", 10));
    leftEditor->setLineWrapMode(QTextEdit::NoWrap);
    splitter->addWidget(leftEditor);
    
    // Правый редактор (новая версия)
    rightEditor = new QTextEdit;
    rightEditor->setFont(QFont("Consolas", 10));
    rightEditor->setLineWrapMode(QTextEdit::NoWrap);
    splitter->addWidget(rightEditor);
    
    // Объединенный редактор
    unifiedEditor = new QPlainTextEdit;
    unifiedEditor->setReadOnly(true);
    unifiedEditor->setFont(QFont("Consolas", 10));
    unifiedEditor->setVisible(false);
    unifiedHighlighter = new DiffHighlighter(unifiedEditor->document());
    
    mainLayout->addWidget(splitter);
    mainLayout->addWidget(unifiedEditor);
    
    // Кнопки действий
    QHBoxLayout *buttonLayout = new QHBoxLayout;
    
    applyBtn = new QPushButton("Применить изменения");
    applyBtn->setStyleSheet("QPushButton { background-color: #4CAF50; color: white; font-weight: bold; }");
    connect(applyBtn, &QPushButton::clicked, this, &DiffViewer::applyChanges);
    buttonLayout->addWidget(applyBtn);
    
    rejectBtn = new QPushButton("Отклонить изменения");
    rejectBtn->setStyleSheet("QPushButton { background-color: #f44336; color: white; }");
    connect(rejectBtn, &QPushButton::clicked, this, &DiffViewer::keepOriginal);
    buttonLayout->addWidget(rejectBtn);
    
    buttonLayout->addStretch();
    
    cancelBtn = new QPushButton("Отмена");
    connect(cancelBtn, &QPushButton::clicked, this, &QDialog::reject);
    buttonLayout->addWidget(cancelBtn);
    
    mainLayout->addLayout(buttonLayout);
}

void DiffViewer::setDiff(const QString &filePath, 
                        const QString &oldContent, 
                        const QString &newContent)
{
    this->filePath = filePath;
    this->originalContent = oldContent;
    this->newContent = newContent;
    
    QFileInfo fileInfo(filePath);
    fileNameLabel->setText(QString("Файл: %1").arg(fileInfo.fileName()));
    
    // Устанавливаем содержимое
    leftEditor->setPlainText(oldContent);
    rightEditor->setPlainText(newContent);
    
    // Вычисляем diff
    calculateDiff();
    
    if (!changes.isEmpty()) {
        currentChangeIndex = 0;
        navigateToChange(currentChangeIndex);
    }
    
    updateNavigationButtons();
}

void DiffViewer::calculateDiff()
{
    changes.clear();
    
    QStringList oldLines = originalContent.split('\n');
    QStringList newLines = newContent.split('\n');
    
    // Простой алгоритм для поиска различий
    int i = 0, j = 0;
    while (i < oldLines.size() || j < newLines.size()) {
        if (i < oldLines.size() && j < newLines.size() && 
            oldLines[i] == newLines[j]) {
            i++; j++;
        } else {
            Change change;
            change.startLine = j;
            
            // Поиск удаленных строк
            if (i < oldLines.size() && 
                (j >= newLines.size() || oldLines[i] != newLines[j])) {
                change.type = "removed";
                while (i < oldLines.size() && 
                       (j >= newLines.size() || oldLines[i] != newLines[j])) {
                    i++;
                }
            }
            // Поиск добавленных строк
            else if (j < newLines.size()) {
                change.type = "added";
                while (j < newLines.size() && 
                       (i >= oldLines.size() || oldLines[i] != newLines[j])) {
                    j++;
                }
            }
            
            change.endLine = j;
            changes.append(change);
        }
    }
    
    // Генерация unified diff
    QString unifiedDiff;
    for (const Change &change : changes) {
        unifiedDiff += QString("@@ -%1,+%2 @@\n").arg(change.startLine).arg(change.endLine);
        // Здесь можно добавить конкретные строки изменений
    }
    unifiedEditor->setPlainText(unifiedDiff);
}

void DiffViewer::navigateToChange(int index)
{
    if (index < 0 || index >= changes.size()) return;
    
    const Change &change = changes[index];
    
    // Подсветка в правом редакторе
    QTextCursor cursor(rightEditor->document()->findBlockByLineNumber(change.startLine));
    QTextEdit::ExtraSelection selection;
    selection.cursor = cursor;
    selection.cursor.movePosition(QTextCursor::Down, QTextCursor::KeepAnchor, 
                                 change.endLine - change.startLine);
    
    QColor highlightColor = (change.type == "added") ? QColor(200, 255, 200) : 
                           (change.type == "removed") ? QColor(255, 200, 200) : 
                           QColor(255, 255, 200);
    
    selection.format.setBackground(highlightColor);
    selection.format.setProperty(QTextFormat::FullWidthSelection, true);
    
    QList<QTextEdit::ExtraSelection> extraSelections;
    extraSelections.append(selection);
    rightEditor->setExtraSelections(extraSelections);
    
    // Прокрутка к изменению
    rightEditor->setTextCursor(cursor);
    rightEditor->ensureCursorVisible();
    
    // Обновление счетчика
    changeCounterLabel->setText(QString("%1/%2").arg(index + 1).arg(changes.size()));
}

void DiffViewer::applyChanges()
{
    QString merged = rightEditor->toPlainText();
    
    // Создаем backup оригинала
    QString backupPath = filePath + ".backup";
    QFile::copy(filePath, backupPath);
    
    // Сохраняем изменения
    QFile file(filePath);
    if (file.open(QIODevice::WriteOnly | QIODevice::Text)) {
        QTextStream stream(&file);
        stream << merged;
        file.close();
        
        emit acceptedWithChanges();
        accept();
    } else {
        QMessageBox::critical(this, "Ошибка", 
            "Не удалось сохранить файл: " + file.errorString());
    }
}

void DiffViewer::keepOriginal()
{
    // Восстанавливаем оригинал из backup или просто закрываем
    QMessageBox::StandardButton reply = QMessageBox::question(this,
        "Отклонить изменения",
        "Вы уверены, что хотите отклонить все изменения?",
        QMessageBox::Yes | QMessageBox::No);
    
    if (reply == QMessageBox::Yes) {
        emit rejectedChanges();
        reject();
    }
}
```

## 3. Интеграция в MainWindow

```cpp
// mainwindow.cpp (дополнения)
void MainWindow::setupConnections()
{
    // ... существующие соединения ...
    
    // Контекстное меню дерева файлов
    projectTreeView->setContextMenuPolicy(Qt::CustomContextMenu);
    connect(projectTreeView, &QTreeView::customContextMenuRequested,
            this, &MainWindow::showTreeContextMenu);
    
    // Дифф-запросы
    ProjectTreeModel *model = static_cast<ProjectTreeModel*>(projectModel);
    connect(model, &ProjectTreeModel::requestFileDiff,
            this, &MainWindow::showFileDiff);
    
    // Отслеживание изменений файлов
    connect(model, &ProjectTreeModel::fileContentChanged,
            this, &MainWindow::onFileChangedExternally);
}

void MainWindow::showTreeContextMenu(const QPoint &pos)
{
    ProjectTreeModel *model = static_cast<ProjectTreeModel*>(projectModel);
    QModelIndex index = projectTreeView->indexAt(pos);
    
    QMenu *menu = model->createContextMenu(index, this);
    if (menu) {
        menu->exec(projectTreeView->viewport()->mapToGlobal(pos));
        delete menu;
    }
}

void MainWindow::showFileDiff(const QString &filePath, 
                             const QString &oldContent, 
                             const QString &newContent)
{
    DiffViewer *diffViewer = new DiffViewer(this);
    diffViewer->setDiff(filePath, oldContent, newContent);
    
    connect(diffViewer, &DiffViewer::acceptedWithChanges, this, [this, filePath]() {
        // Обновляем модель после применения изменений
        ProjectTreeModel *model = static_cast<ProjectTreeModel*>(projectModel);
        model->refreshFile(filePath);
        statusLabel->setText("Изменения применены: " + QFileInfo(filePath).fileName());
    });
    
    connect(diffViewer, &DiffViewer::rejectedChanges, this, [this, filePath]() {
        statusLabel->setText("Изменения отклонены: " + QFileInfo(filePath).fileName());
    });
    
    diffViewer->exec();
    diffViewer->deleteLater();
}

void MainWindow::processAndApplyCode(const QString &markdownResponse)
{
    auto codeBlocks = codeProcessor->extractCodeBlocks(markdownResponse);
    
    if (!codeBlocks.isEmpty()) {
        // Спрашиваем пользователя о подтверждении
        QMessageBox msgBox(this);
        msgBox.setWindowTitle("Обнаружен код");
        msgBox.setText(QString("Найдено %1 блоков кода. Применить изменения к файлам?")
                      .arg(codeBlocks.size()));
        msgBox.setStandardButtons(QMessageBox::Yes | QMessageBox::No | QMessageBox::Cancel);
        msgBox.setDefaultButton(QMessageBox::Yes);
        
        QAbstractButton *showDiffButton = msgBox.addButton("Показать различия", 
                                                          QMessageBox::ActionRole);
        
        int result = msgBox.exec();
        
        if (msgBox.clickedButton() == showDiffButton) {
            // Показываем дифф для каждого файла
            for (const auto &block : codeBlocks) {
                if (!block.filePath.isEmpty()) {
                    QString currentContent = codeProcessor->readCurrentFile(block.filePath);
                    showFileDiff(block.filePath, currentContent, block.code);
                }
            }
        } else if (result == QMessageBox::Yes) {
            // Автоматически применяем все изменения
            if (codeProcessor->applyCodeToFiles(codeBlocks)) {
                statusLabel->setText("Код успешно применен");
            }
        } else {
            statusLabel->setText("Действие отменено");
        }
    }
}
```

## 4. Дополнительные улучшения CodeProcessor

```cpp
// codeprocessor.cpp (дополнения)
QList<CodeProcessor::CodeBlock> CodeProcessor::extractCodeBlocks(const QString &markdown)
{
    QList<CodeBlock> blocks;
    
    QRegularExpression codeBlockRegex("```(\\w+)?\\n([\\s\\S]*?)```");
    QRegularExpressionMatchIterator i = codeBlockRegex.globalMatch(markdown);
    
    while (i.hasNext()) {
        QRegularExpressionMatch match = i.next();
        CodeBlock block;
        block.language = match.captured(1).toLower();
        block.code = match.captured(2).trimmed();
        
        // Пытаемся определить файл по контексту
        block.filePath = detectFilePath(block.code, block.language, availableFiles);
        
        blocks.append(block);
    }
    
    return blocks;
}

QString CodeProcessor::detectFilePath(const QString &code, const QString &language,
                                     const QSet<QString> &availableFiles)
{
    // 1. Ищем упоминания файлов в комментариях
    QRegularExpression fileCommentRegex("//\\s*File:\\s*(.+?)\\s*$|"
                                       "#\\s*File:\\s*(.+?)\\s*$|"
                                       "--\\s*File:\\s*(.+?)\\s*$",
                                       QRegularExpression::MultilineOption);
    
    QRegularExpressionMatch match = fileCommentRegex.match(code);
    if (match.hasMatch()) {
        QString suggestedFile;
        for (int i = 1; i <= 3; ++i) {
            if (!match.captured(i).isEmpty()) {
                suggestedFile = match.captured(i);
                break;
            }
        }
        
        // Проверяем существование файла
        if (availableFiles.contains(suggestedFile)) {
            return suggestedFile;
        }
    }
    
    // 2. Определяем по расширению, соответствующему языку
    QMap<QString, QStringList> languageExtensions = {
        {"cpp", {"cpp", "h", "hpp"}},
        {"python", {"py"}},
        {"javascript", {"js", "ts", "jsx", "tsx"}},
        {"java", {"java"}},
        {"go", {"go"}},
        {"rust", {"rs"}},
        // ... другие языки
    };
    
    if (languageExtensions.contains(language)) {
        QStringList extensions = languageExtensions[language];
        for (const QString &file : availableFiles) {
            QFileInfo info(file);
            if (extensions.contains(info.suffix())) {
                // Проверяем сигнатуру файла (первые строки)
                QString signature = getFileSignature(file);
                if (code.contains(signature)) {
                    return file;
                }
            }
        }
    }
    
    return QString();
}

bool CodeProcessor::applyCodeToFiles(const QList<CodeBlock> &codeBlocks)
{
    bool allSuccess = true;
    
    for (const CodeBlock &block : codeBlocks) {
        if (!block.filePath.isEmpty()) {
            // Создаем backup
            QString backupPath = block.filePath + ".ai_backup";
            QFile::copy(block.filePath, backupPath);
            
            // Сохраняем изменения
            QFile file(block.filePath);
            if (file.open(QIODevice::WriteOnly | QIODevice::Text)) {
                QTextStream stream(&file);
                stream << block.code;
                file.close();
                
                emit fileUpdated(block.filePath);
            } else {
                emit errorOccurred("Не удалось сохранить файл: " + block.filePath);
                allSuccess = false;
            }
        }
    }
    
    return allSuccess;
}
```

## 5. Дополнительные функции для контекстного меню

```cpp
// mainwindow.cpp (дополнительные функции)
void MainWindow::onFileChangedExternally(const QString &filePath)
{
    QMessageBox::information(this, "Файл изменен",
        QString("Файл %1 был изменен вне приложения. Обновить содержимое?")
        .arg(QFileInfo(filePath).fileName()));
    
    // Обновляем модель
    ProjectTreeModel *model = static_cast<ProjectTreeModel*>(projectModel);
    model->refreshFile(filePath);
}

void MainWindow::setupMenu()
{
    // ... существующее меню ...
    
    QMenu *viewMenu = menuBar()->addMenu("Вид");
    
    QAction *expandAllAction = new QAction("Развернуть все", this);
    connect(expandAllAction, &QAction::triggered, projectTreeView, &QTreeView::expandAll);
    viewMenu->addAction(expandAllAction);
    
    QAction *collapseAllAction = new QAction("Свернуть все", this);
    connect(collapseAllAction, &QAction::triggered, projectTreeView, &QTreeView::collapseAll);
    viewMenu->addAction(collapseAllAction);
    
    viewMenu->addSeparator();
    
    QAction *showHiddenFilesAction = new QAction("Показывать скрытые файлы", this);
    showHiddenFilesAction->setCheckable(true);
    // Здесь нужно добавить логику фильтрации
    viewMenu->addAction(showHiddenFilesAction);
    
    QAction *filterExtensionsAction = new QAction("Фильтр по расширениям...", this);
    connect(filterExtensionsAction, &QAction::triggered, this, &MainWindow::showExtensionFilterDialog);
    viewMenu->addAction(filterExtensionsAction);
}
```

## Ключевые особенности реализации:

1. **Контекстное меню**:
   - Динамическое меню в зависимости от типа элемента
   - Быстрые действия (открыть, показать в проводнике, копировать путь)
   - Управление выбором файлов (выбрать все в папке, по шаблону)
   - Интеграция с внешними редакторами

2. **Diff-просмотр**:
   - Два режима: side-by-side и unified
   - Навигация по изменениям
   - Подсветка синтаксиса для diff
   - Backup файлов перед изменением
   - Подтверждение/отклонение изменений

3. **Интеграция в рабочий процесс**:
   - Автоматическое определение файлов по контексту кода
   - Умное сопоставление блоков кода с файлами проекта
   - Отслеживание внешних изменений файлов

4. **Пользовательский опыт**:
   - Визуальная обратная связь о выбранных файлах
   - Подтверждение перед массовым применением изменений
   - Возможность просмотра каждого изменения отдельно

Эта реализация превращает приложение в полноценную среду для работы программиста с AI-ассистентами, предоставляя полный контроль над процессом и защищая от нежелательных изменений в коде.
