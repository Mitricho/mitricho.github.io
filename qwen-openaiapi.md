Ниже представлен полный, готовый к компиляции проект на Qt 6. Я объединил лучшие практики и функционал из обоих файлов:

1.  **`OpenAiChatClient`**: Реализует поддержку обоих эндпоинтов (`/responses` и `/chat/completions`), режим `Auto` с фолбэком, корректный SSE-парсер, отмену запросов и сохранение настроек через `QSettings`.
2.  **`AiSettingsDialog`**: Диалог настроек с выбором режима API.
3.  **`MainWindow`**: Демонстрационное приложение с интерфейсом чата.
4.  **`CMakeLists.txt`**: Файл сборки для CMake (стандарт для Qt 6).

### Структура проекта

Создайте папку проекта и разместите в ней следующие файлы:

#### 1. `CMakeLists.txt`

```cmake
cmake_minimum_required(VERSION 3.16)
project(AiChatClientDemo LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(Qt6 REQUIRED COMPONENTS Core Widgets Network)

qt_standard_project_setup()

qt_add_executable(AiChatClientDemo
    main.cpp
    MainWindow.h
    MainWindow.cpp
    OpenAiChatClient.h
    OpenAiChatClient.cpp
    AiSettingsDialog.h
    AiSettingsDialog.cpp
)

target_link_libraries(AiChatClientDemo PRIVATE
    Qt6::Core
    Qt6::Widgets
    Qt6::Network
)
```

#### 2. `OpenAiChatClient.h`

```cpp
#pragma once

#include <QObject>
#include <QNetworkAccessManager>
#include <QNetworkReply>
#include <QSettings>

class OpenAiChatClient : public QObject
{
    Q_OBJECT

public:
    enum class ApiMode
    {
        Auto,
        Responses,
        ChatCompletions
    };
    Q_ENUM(ApiMode)

    explicit OpenAiChatClient(QObject *parent = nullptr);

    void loadSettings();
    void saveSettings();

    void setBaseUrl(const QString &url);
    void setApiKey(const QString &key);
    void setApiMode(ApiMode mode);

    QString baseUrl() const;
    QString apiKey() const;
    ApiMode apiMode() const;

    void listModels();

public slots:
    void createResponse(const QString &prompt,
                        const QString &model,
                        bool stream = true);

    void cancel();

signals:
    void modelsReceived(const QStringList &models);

    void responseChunk(const QString &text);
    void responseFinished(const QString &text);

    void requestCancelled();

    void errorOccurred(const QString &message);

private slots:
    void onModelsFinished();
    void onReadyRead();
    void onRequestFinished();

private:
    enum class RequestKind
    {
        Responses,
        ChatCompletions
    };

    void startResponsesRequest(const QString &prompt,
                               const QString &model,
                               bool stream);

    void startChatCompletionsRequest(const QString &prompt,
                                     const QString &model,
                                     bool stream);

    QNetworkRequest createRequest(const QString &path) const;

    void processSseBuffer();
    void processSseEvent(const QByteArray &eventData);

private:
    QNetworkAccessManager m_network;

    QString m_baseUrl;
    QString m_apiKey;

    ApiMode m_apiMode = ApiMode::Auto;

    QNetworkReply *m_reply = nullptr;

    QString m_fullText;

    QByteArray m_sseBuffer;

    RequestKind m_requestKind = RequestKind::Responses;

    QString m_lastPrompt;
    QString m_lastModel;
    bool m_lastStream = true;
};
```

#### 3. `OpenAiChatClient.cpp`

```cpp
#include "OpenAiChatClient.h"

#include <QJsonArray>
#include <QJsonDocument>
#include <QJsonObject>
#include <QUrl>
#include <QDebug>

OpenAiChatClient::OpenAiChatClient(QObject *parent)
    : QObject(parent)
{
    loadSettings();
}

void OpenAiChatClient::loadSettings()
{
    QSettings s;
    m_baseUrl = s.value("ai/baseUrl", "https://api.openai.com/v1").toString();
    m_apiKey = s.value("ai/apiKey").toString();
    
    // Default to Auto if not set or invalid
    int modeInt = s.value("ai/apiMode", 0).toInt();
    if (modeInt >= 0 && modeInt <= 2) {
        m_apiMode = static_cast<ApiMode>(modeInt);
    } else {
        m_apiMode = ApiMode::Auto;
    }
}

void OpenAiChatClient::saveSettings()
{
    QSettings s;
    s.setValue("ai/baseUrl", m_baseUrl);
    s.setValue("ai/apiKey", m_apiKey);
    s.setValue("ai/apiMode", static_cast<int>(m_apiMode));
}

void OpenAiChatClient::setBaseUrl(const QString &url)
{
    m_baseUrl = url;
}

void OpenAiChatClient::setApiKey(const QString &key)
{
    m_apiKey = key;
}

void OpenAiChatClient::setApiMode(ApiMode mode)
{
    m_apiMode = mode;
}

QString OpenAiChatClient::baseUrl() const
{
    return m_baseUrl;
}

QString OpenAiChatClient::apiKey() const
{
    return m_apiKey;
}

OpenAiChatClient::ApiMode OpenAiChatClient::apiMode() const
{
    return m_apiMode;
}

QNetworkRequest OpenAiChatClient::createRequest(const QString &path) const
{
    // Ensure base URL doesn't end with slash if path starts with one, or vice versa
    QString cleanBase = m_baseUrl;
    if (cleanBase.endsWith('/')) {
        cleanBase.chop(1);
    }
    
    QUrl url(cleanBase + path);
    QNetworkRequest req(url);
    req.setHeader(QNetworkRequest::ContentTypeHeader, "application/json");

    if (!m_apiKey.isEmpty()) {
        req.setRawHeader("Authorization", "Bearer " + m_apiKey.toUtf8());
    }

    return req;
}

void OpenAiChatClient::listModels()
{
    // Usually /v1/models
    QNetworkReply *reply = m_network.get(createRequest("/models"));
    connect(reply, &QNetworkReply::finished, this, &OpenAiChatClient::onModelsFinished);
}

void OpenAiChatClient::onModelsFinished()
{
    auto *reply = qobject_cast<QNetworkReply*>(sender());
    if (!reply) return;

    QByteArray data = reply->readAll();
    if (reply->error() != QNetworkReply::NoError) {
        emit errorOccurred(reply->errorString());
        reply->deleteLater();
        return;
    }

    QStringList models;
    QJsonDocument doc = QJsonDocument::fromJson(data);
    
    // Handle both {"data": [...]} and simple array responses if necessary
    if (doc.isObject() && doc.object().contains("data")) {
        QJsonArray arr = doc.object()["data"].toArray();
        for (const auto &v : arr) {
            if (v.isObject() && v.toObject().contains("id")) {
                models << v.toObject()["id"].toString();
            }
        }
    } else if (doc.isArray()) {
        for (const auto &v : doc.array()) {
            if (v.isObject() && v.toObject().contains("id")) {
                models << v.toObject()["id"].toString();
            }
        }
    }

    emit modelsReceived(models);
    reply->deleteLater();
}

void OpenAiChatClient::cancel()
{
    if (!m_reply) return;

    disconnect(m_reply, nullptr, this, nullptr);
    m_reply->abort();
    m_reply->deleteLater();
    m_reply = nullptr;

    emit requestCancelled();
}

void OpenAiChatClient::createResponse(const QString &prompt,
                                      const QString &model,
                                      bool stream)
{
    cancel();

    m_fullText.clear();
    m_sseBuffer.clear();

    m_lastPrompt = prompt;
    m_lastModel = model;
    m_lastStream = stream;

    switch (m_apiMode) {
    case ApiMode::Responses:
        startResponsesRequest(prompt, model, stream);
        break;
    case ApiMode::ChatCompletions:
        startChatCompletionsRequest(prompt, model, stream);
        break;
    case ApiMode::Auto:
        // Try Responses first, fallback to Chat Completions on error
        startResponsesRequest(prompt, model, stream);
        break;
    }
}

void OpenAiChatClient::startResponsesRequest(const QString &prompt,
                                             const QString &model,
                                             bool stream)
{
    QJsonObject body;
    body["model"] = model;
    body["input"] = prompt;
    body["stream"] = stream;

    m_requestKind = RequestKind::Responses;

    m_reply = m_network.post(createRequest("/responses"),
                             QJsonDocument(body).toJson());

    connect(m_reply, &QNetworkReply::readyRead, this, &OpenAiChatClient::onReadyRead);
    connect(m_reply, &QNetworkReply::finished, this, &OpenAiChatClient::onRequestFinished);
}

void OpenAiChatClient::startChatCompletionsRequest(const QString &prompt,
                                                   const QString &model,
                                                   bool stream)
{
    QJsonObject body;
    body["model"] = model;
    body["stream"] = stream;

    QJsonArray messages;
    messages.append(QJsonObject{{"role", "user"}, {"content", prompt}});
    body["messages"] = messages;

    m_requestKind = RequestKind::ChatCompletions;

    m_reply = m_network.post(createRequest("/chat/completions"),
                             QJsonDocument(body).toJson());

    connect(m_reply, &QNetworkReply::readyRead, this, &OpenAiChatClient::onReadyRead);
    connect(m_reply, &QNetworkReply::finished, this, &OpenAiChatClient::onRequestFinished);
}

void OpenAiChatClient::onReadyRead()
{
    if (!m_reply) return;
    m_sseBuffer += m_reply->readAll();
    processSseBuffer();
}

void OpenAiChatClient::processSseBuffer()
{
    while (true) {
        // SSE events are separated by double newline (\n\n or \r\n\r\n)
        int separator = m_sseBuffer.indexOf("\n\n");
        if (separator < 0) {
            separator = m_sseBuffer.indexOf("\r\n\r\n");
        }

        if (separator < 0) {
            return; // Wait for more data
        }

        QByteArray event = m_sseBuffer.left(separator);
        
        // Calculate skip length based on separator found
        int skip = m_sseBuffer.mid(separator, 4).startsWith("\r\n\r\n") ? 4 : 2;
        
        m_sseBuffer.remove(0, separator + skip);

        processSseEvent(event);
    }
}

void OpenAiChatClient::processSseEvent(const QByteArray &eventData)
{
    QList<QByteArray> lines = eventData.split('\n');
    QByteArray json;
    QString eventType;

    for (QByteArray line : lines) {
        line = line.trimmed();
        if (line.startsWith("event: ")) {
            eventType = line.mid(7).trimmed();
        } else if (line.startsWith("data: ")) {
            // Accumulate data lines if multiple exist for one event
            if (!json.isEmpty()) json += "\n"; 
            json += line.mid(5).trimmed();
        }
    }

    if (json.isEmpty()) return;
    if (json == "[DONE]") return;

    QJsonParseError err;
    QJsonDocument doc = QJsonDocument::fromJson(json, &err);

    if (err.error != QJsonParseError::NoError) {
        // qDebug() << "JSON Parse Error:" << err.errorString();
        return;
    }

    QJsonObject obj = doc.object();
    QString chunk;

    if (m_requestKind == RequestKind::Responses) {
        // OpenAI Responses API structure
        // Note: Structure might vary slightly by provider, but standard is:
        // type: response.output_text.delta -> delta: "..."
        QString type = obj["type"].toString();
        if (type == "response.output_text.delta") {
            chunk = obj["delta"].toString();
        } else if (obj.contains("delta")) {
             // Fallback for some implementations that just send delta
             chunk = obj["delta"].toString();
        }
    } else {
        // Chat Completions API structure
        // choices[0].delta.content
        auto choices = obj["choices"].toArray();
        if (!choices.isEmpty()) {
            auto choiceObj = choices[0].toObject();
            auto delta = choiceObj["delta"].toObject();
            chunk = delta["content"].toString();
        }
    }

    if (!chunk.isEmpty()) {
        m_fullText += chunk;
        emit responseChunk(chunk);
    }
}

void OpenAiChatClient::onRequestFinished()
{
    if (!m_reply) return;

    int status = m_reply->attribute(QNetworkRequest::HttpStatusCodeAttribute).toInt();

    if (m_reply->error() != QNetworkReply::NoError) {
        // Check for Auto mode fallback conditions
        if (m_apiMode == ApiMode::Auto &&
            m_requestKind == RequestKind::Responses &&
            (status == 404 || status == 400 || status == 501)) 
        {
            qDebug() << "Responses API failed with status" << status << ". Falling back to Chat Completions.";
            
            m_reply->deleteLater();
            m_reply = nullptr;
            
            // Retry with Chat Completions
            startChatCompletionsRequest(m_lastPrompt, m_lastModel, m_lastStream);
            return;
        }

        emit errorOccurred(m_reply->errorString());
    } else {
        emit responseFinished(m_fullText);
    }

    m_reply->deleteLater();
    m_reply = nullptr;
}
```

#### 4. `AiSettingsDialog.h`

```cpp
#pragma once

#include <QDialog>
#include "OpenAiChatClient.h"

class QLineEdit;
class QComboBox;

class AiSettingsDialog : public QDialog
{
    Q_OBJECT

public:
    explicit AiSettingsDialog(QWidget *parent = nullptr);

    QString baseUrl() const;
    QString apiKey() const;
    OpenAiChatClient::ApiMode apiMode() const;

    void setBaseUrl(const QString &url);
    void setApiKey(const QString &key);
    void setApiMode(OpenAiChatClient::ApiMode mode);

private:
    QLineEdit *m_urlEdit;
    QLineEdit *m_apiKeyEdit;
    QComboBox *m_apiModeCombo;
};
```

#### 5. `AiSettingsDialog.cpp`

```cpp
#include "AiSettingsDialog.h"

#include <QDialogButtonBox>
#include <QFormLayout>
#include <QLineEdit>
#include <QVBoxLayout>
#include <QComboBox>
#include <QLabel>

AiSettingsDialog::AiSettingsDialog(QWidget *parent)
    : QDialog(parent)
{
    setWindowTitle("AI Settings");

    m_urlEdit = new QLineEdit(this);
    m_apiKeyEdit = new QLineEdit(this);
    m_apiKeyEdit->setEchoMode(QLineEdit::Password);

    m_apiModeCombo = new QComboBox(this);
    m_apiModeCombo->addItem("Auto (Try Responses, then Chat)", static_cast<int>(OpenAiChatClient::ApiMode::Auto));
    m_apiModeCombo->addItem("Responses API (/responses)", static_cast<int>(OpenAiChatClient::ApiMode::Responses));
    m_apiModeCombo->addItem("Chat Completions (/chat/completions)", static_cast<int>(OpenAiChatClient::ApiMode::ChatCompletions));

    auto *form = new QFormLayout;
    form->addRow("Base URL:", m_urlEdit);
    form->addRow("API Key:", m_apiKeyEdit);
    form->addRow("Endpoint Mode:", m_apiModeCombo);

    auto *hintLabel = new QLabel("Use 'Auto' for maximum compatibility with local servers like llama.cpp, Ollama, etc.", this);
    hintLabel->setWordWrap(true);
    hintLabel->setStyleSheet("color: gray; font-size: 10pt;");
    form->addRow(hintLabel);

    auto *buttons = new QDialogButtonBox(QDialogButtonBox::Ok | QDialogButtonBox::Cancel);
    connect(buttons, &QDialogButtonBox::accepted, this, &QDialog::accept);
    connect(buttons, &QDialogButtonBox::rejected, this, &QDialog::reject);

    auto *layout = new QVBoxLayout(this);
    layout->addLayout(form);
    layout->addWidget(buttons);
}

QString AiSettingsDialog::baseUrl() const
{
    return m_urlEdit->text();
}

QString AiSettingsDialog::apiKey() const
{
    return m_apiKeyEdit->text();
}

OpenAiChatClient::ApiMode AiSettingsDialog::apiMode() const
{
    return static_cast<OpenAiChatClient::ApiMode>(m_apiModeCombo->currentData().toInt());
}

void AiSettingsDialog::setBaseUrl(const QString &url)
{
    m_urlEdit->setText(url);
}

void AiSettingsDialog::setApiKey(const QString &key)
{
    m_apiKeyEdit->setText(key);
}

void AiSettingsDialog::setApiMode(OpenAiChatClient::ApiMode mode)
{
    int index = m_apiModeCombo->findData(static_cast<int>(mode));
    if (index != -1) {
        m_apiModeCombo->setCurrentIndex(index);
    }
}
```

#### 6. `MainWindow.h`

```cpp
#pragma once

#include <QMainWindow>
#include <QTextEdit>
#include <QLineEdit>
#include <QPushButton>
#include <QProgressBar>
#include <QLabel>
#include <QComboBox>
#include "OpenAiChatClient.h"
#include "AiSettingsDialog.h"

class MainWindow : public QMainWindow
{
    Q_OBJECT

public:
    explicit MainWindow(QWidget *parent = nullptr);
    ~MainWindow();

private slots:
    void onSendClicked();
    void onCancelClicked();
    void onSettingsClicked();
    
    void onModelsReceived(const QStringList &models);
    void onResponseChunk(const QString &text);
    void onResponseFinished(const QString &text);
    void onErrorOccurred(const QString &message);
    void onRequestCancelled();

private:
    void appendMessage(const QString &role, const QString &text);
    void updateUiState(bool isLoading);

    OpenAiChatClient *m_client;
    
    QTextEdit *m_chatDisplay;
    QLineEdit *m_inputField;
    QPushButton *m_sendBtn;
    QPushButton *m_cancelBtn;
    QPushButton *m_settingsBtn;
    QComboBox *m_modelCombo;
    QProgressBar *m_progressBar;
    QLabel *m_statusLabel;

    AiSettingsDialog *m_settingsDialog;
};
```

#### 7. `MainWindow.cpp`

```cpp
#include "MainWindow.h"

#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QGroupBox>
#include <QMessageBox>
#include <QScrollBar>
#include <QDateTime>

MainWindow::MainWindow(QWidget *parent)
    : QMainWindow(parent)
{
    setWindowTitle("Qt OpenAI Compatible Client Demo");
    resize(800, 600);

    m_client = new OpenAiChatClient(this);

    // --- UI Setup ---
    m_chatDisplay = new QTextEdit(this);
    m_chatDisplay->setReadOnly(true);
    m_chatDisplay->setPlaceholderText("Chat history will appear here...");

    m_inputField = new QLineEdit(this);
    m_inputField->setPlaceholderText("Type your message...");
    m_inputField->setClearButtonEnabled(true);

    m_sendBtn = new QPushButton("Send", this);
    m_cancelBtn = new QPushButton("Cancel", this);
    m_cancelBtn->setEnabled(false);
    m_settingsBtn = new QPushButton("Settings", this);

    m_modelCombo = new QComboBox(this);
    m_modelCombo->setEditable(true);
    m_modelCombo->addItem("gpt-4o-mini"); // Default placeholder

    m_progressBar = new QProgressBar(this);
    m_progressBar->setVisible(false);
    m_progressBar->setRange(0, 0); // Indeterminate

    m_statusLabel = new QLabel("Ready", this);

    // Layouts
    auto *topLayout = new QHBoxLayout;
    topLayout->addWidget(new QLabel("Model:"));
    topLayout->addWidget(m_modelCombo);
    topLayout->addStretch();
    topLayout->addWidget(m_settingsBtn);

    auto *inputLayout = new QHBoxLayout;
    inputLayout->addWidget(m_inputField);
    inputLayout->addWidget(m_sendBtn);
    inputLayout->addWidget(m_cancelBtn);

    auto *mainLayout = new QVBoxLayout;
    mainLayout->addLayout(topLayout);
    mainLayout->addWidget(m_chatDisplay);
    mainLayout->addWidget(m_progressBar);
    mainLayout->addWidget(m_statusLabel);
    mainLayout->addLayout(inputLayout);

    auto *centralWidget = new QWidget(this);
    centralWidget->setLayout(mainLayout);
    setCentralWidget(centralWidget);

    // --- Connections ---
    connect(m_sendBtn, &QPushButton::clicked, this, &MainWindow::onSendClicked);
    connect(m_cancelBtn, &QPushButton::clicked, this, &MainWindow::onCancelClicked);
    connect(m_settingsBtn, &QPushButton::clicked, this, &MainWindow::onSettingsClicked);
    connect(m_inputField, &QLineEdit::returnPressed, this, &MainWindow::onSendClicked);

    connect(m_client, &OpenAiChatClient::modelsReceived, this, &MainWindow::onModelsReceived);
    connect(m_client, &OpenAiChatClient::responseChunk, this, &MainWindow::onResponseChunk);
    connect(m_client, &OpenAiChatClient::responseFinished, this, &MainWindow::onResponseFinished);
    connect(m_client, &OpenAiChatClient::errorOccurred, this, &MainWindow::onErrorOccurred);
    connect(m_client, &OpenAiChatClient::requestCancelled, this, &MainWindow::onRequestCancelled);

    // Load initial models
    m_client->listModels();
}

MainWindow::~MainWindow()
{
}

void MainWindow::onSendClicked()
{
    QString text = m_inputField->text().trimmed();
    if (text.isEmpty()) return;

    QString model = m_modelCombo->currentText();
    if (model.isEmpty()) {
        QMessageBox::warning(this, "Warning", "Please select or enter a model name.");
        return;
    }

    appendMessage("User", text);
    m_inputField->clear();
    
    updateUiState(true);
    m_statusLabel->setText("Sending request...");
    
    // Start streaming response
    m_client->createResponse(text, model, true);
}

void MainWindow::onCancelClicked()
{
    m_client->cancel();
}

void MainWindow::onSettingsClicked()
{
    if (!m_settingsDialog) {
        m_settingsDialog = new AiSettingsDialog(this);
    }

    // Load current client settings into dialog
    m_settingsDialog->setBaseUrl(m_client->baseUrl());
    m_settingsDialog->setApiKey(m_client->apiKey());
    m_settingsDialog->setApiMode(m_client->apiMode());

    if (m_settingsDialog->exec() == QDialog::Accepted) {
        m_client->setBaseUrl(m_settingsDialog->baseUrl());
        m_client->setApiKey(m_settingsDialog->apiKey());
        m_client->setApiMode(m_settingsDialog->apiMode());
        m_client->saveSettings();
        
        // Refresh models list with new settings
        m_modelCombo->clear();
        m_modelCombo->addItem("Loading...");
        m_client->listModels();
    }
}

void MainWindow::onModelsReceived(const QStringList &models)
{
    m_modelCombo->clear();
    if (models.isEmpty()) {
        m_modelCombo->addItem("gpt-4o-mini"); // Fallback
        m_statusLabel->setText("No models found or error loading. Using default.");
    } else {
        m_modelCombo->addItems(models);
        m_statusLabel->setText(QString("Loaded %1 models").arg(models.size()));
    }
}

void MainWindow::onResponseChunk(const QString &text)
{
    // Append chunk to the last assistant message block
    // For simplicity in this demo, we just append to the document directly
    // A robust chat would track message IDs. Here we assume the last block is assistant.
    
    QTextCursor cursor = m_chatDisplay->textCursor();
    cursor.movePosition(QTextCursor::End);
    
    // If the last block was user, we need to start a new block for assistant
    // But since we are streaming, we usually start the assistant block on first chunk.
    // To keep it simple: we just insert text. 
    
    // Better approach for visual separation:
    // We'll rely on the fact that we appended "Assistant:\n" in onResponseFinished start? 
    // No, let's do it dynamically.
    
    // Simplest robust way for demo:
    // Just append text. The user sees it flowing.
    cursor.insertText(text);
    m_chatDisplay->setTextCursor(cursor);
    m_chatDisplay->ensureCursorVisible();
}

void MainWindow::onResponseFinished(const QString &text)
{
    updateUiState(false);
    m_statusLabel->setText("Response finished.");
    // Add a newline for separation from next user message
    QTextCursor cursor = m_chatDisplay->textCursor();
    cursor.movePosition(QTextCursor::End);
    cursor.insertText("\n\n");
    m_chatDisplay->setTextCursor(cursor);
}

void MainWindow::onErrorOccurred(const QString &message)
{
    updateUiState(false);
    m_statusLabel->setText("Error occurred.");
    QMessageBox::critical(this, "Error", message);
}

void MainWindow::onRequestCancelled()
{
    updateUiState(false);
    m_statusLabel->setText("Request cancelled.");
    QTextCursor cursor = m_chatDisplay->textCursor();
    cursor.movePosition(QTextCursor::End);
    cursor.insertText("\n[Cancelled]\n\n");
    m_chatDisplay->setTextCursor(cursor);
}

void MainWindow::appendMessage(const QString &role, const QString &text)
{
    QTextCursor cursor = m_chatDisplay->textCursor();
    cursor.movePosition(QTextCursor::End);
    
    QTextCharFormat format;
    format.setFontWeight(QFont::Bold);
    format.setForeground(role == "User" ? Qt::darkBlue : Qt::darkGreen);
    
    cursor.setCharFormat(format);
    cursor.insertText(role + ":\n");
    
    format.setFontWeight(QFont::Normal);
    format.setForeground(Qt::black);
    cursor.setCharFormat(format);
    cursor.insertText(text + "\n\n");
    
    m_chatDisplay->setTextCursor(cursor);
    m_chatDisplay->ensureCursorVisible();
}

void MainWindow::updateUiState(bool isLoading)
{
    m_sendBtn->setEnabled(!isLoading);
    m_cancelBtn->setEnabled(isLoading);
    m_inputField->setEnabled(!isLoading);
    m_modelCombo->setEnabled(!isLoading);
    m_settingsBtn->setEnabled(!isLoading);
    m_progressBar->setVisible(isLoading);
}
```

#### 8. `main.cpp`

```cpp
#include <QApplication>
#include "MainWindow.h"

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);
    
    // Set organization name for QSettings
    app.setOrganizationName("QtAiDemo");
    app.setApplicationName("AiChatClient");

    MainWindow window;
    window.show();

    return app.exec();
}
```

### Инструкция по сборке и запуску

1.  Убедитесь, что у вас установлен **Qt 6.5+** и **CMake**.
2.  Создайте директорию, например `build`, внутри папки проекта.
3.  Откройте терминал в папке `build`.
4.  Выполните команды:
    ```bash
    cmake ..
    cmake --build .
    ```
5.  Запустите полученный исполняемый файл (`AiChatClientDemo` или `.exe`).

### Особенности реализации

*   **Универсальность**: Клиент автоматически определяет, какой API использует сервер. Если вы подключитесь к `llama.cpp` или `Ollama`, которые не поддерживают новый эндпоинт `/responses`, клиент получит ошибку 404/400 и автоматически переключится на стандартный `/chat/completions`.
*   **Настройки**: Все настройки (URL, Ключ, Режим) сохраняются в системном реестре/файле конфигурации через `QSettings` и восстанавливаются при перезапуске.
*   **Потоковая передача (Streaming)**: Реализован надежный парсер SSE (Server-Sent Events), который корректно обрабатывает куски данных, даже если они приходят частями по сети.
*   **Отмена**: Кнопка "Cancel" мгновенно прерывает сетевой запрос и освобождает ресурсы.