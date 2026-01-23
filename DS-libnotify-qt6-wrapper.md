Вот реализация класса-обертки для libnotify на Qt 6.9:

## 1. Заголовочный файл `libnotifywrapper.h`:

```cpp
#ifndef LIBNOTIFYWRAPPER_H
#define LIBNOTIFYWRAPPER_H

#include <QObject>
#include <QString>
#include <QStringList>
#include <QIcon>
#include <QPixmap>
#include <memory>

// Форвардное объявление для скрытия деталей libnotify
struct NotifyNotification;

/**
 * @brief Класс-обертка для библиотеки libnotify
 * 
 * Предоставляет интерфейс для отправки десктоп-уведомлений
 * из любого места в программе на Qt 6.9
 */
class LibNotifyWrapper : public QObject
{
    Q_OBJECT

public:
    // Типы уведомлений (соответствуют стандартным категориям libnotify)
    enum class NotificationType {
        Info,       // Информационное сообщение
        Warning,    // Предупреждение
        Critical,   // Критическое сообщение
        Urgent      // Срочное уведомление
    };
    Q_ENUM(NotificationType)

    // Приоритеты уведомлений
    enum class NotificationPriority {
        Low,        // Низкий приоритет
        Normal,     // Обычный приоритет
        High        // Высокий приоритет
    };
    Q_ENUM(NotificationPriority)

    /**
     * @brief Получить экземпляр синглтона
     */
    static LibNotifyWrapper& instance();

    /**
     * @brief Инициализировать библиотеку libnotify
     * @param appName Имя приложения (будет отображаться в уведомлениях)
     * @return true если инициализация прошла успешно
     */
    bool initialize(const QString& appName = QCoreApplication::applicationName());

    /**
     * @brief Проверить, инициализирована ли библиотека
     */
    bool isInitialized() const;

    /**
     * @brief Отправить простое уведомление
     * @param title Заголовок уведомления
     * @param message Текст сообщения
     * @param timeoutMs Время показа в миллисекундах (-1 для стандартного)
     */
    void sendNotification(const QString& title, 
                          const QString& message,
                          int timeoutMs = 5000);

    /**
     * @brief Отправить уведомление с указанием типа
     * @param title Заголовок уведомления
     * @param message Текст сообщения
     * @param type Тип уведомления
     * @param timeoutMs Время показа в миллисекундах
     */
    void sendNotification(const QString& title,
                          const QString& message,
                          NotificationType type,
                          int timeoutMs = 5000);

    /**
     * @brief Отправить уведомление с иконкой
     * @param title Заголовок уведомления
     * @param message Текст сообщения
     * @param iconName Имя иконки (по стандарту FreeDesktop)
     * @param timeoutMs Время показа в миллисекундах
     */
    void sendNotification(const QString& title,
                          const QString& message,
                          const QString& iconName,
                          int timeoutMs = 5000);

    /**
     * @brief Отправить уведомление с QIcon
     * @param title Заголовок уведомления
     * @param message Текст сообщения
     * @param icon Иконка Qt
     * @param timeoutMs Время показа в миллисекундах
     */
    void sendNotification(const QString& title,
                          const QString& message,
                          const QIcon& icon,
                          int timeoutMs = 5000);

    /**
     * @brief Отправить уведомление с приоритетом
     * @param title Заголовок уведомления
     * @param message Текст сообщения
     * @param priority Приоритет уведомления
     * @param iconName Имя иконки (опционально)
     * @param timeoutMs Время показа в миллисекундах
     */
    void sendNotification(const QString& title,
                          const QString& message,
                          NotificationPriority priority,
                          const QString& iconName = QString(),
                          int timeoutMs = 5000);

    /**
     * @brief Отправить уведомление с действиями (кнопками)
     * @param title Заголовок уведомления
     * @param message Текст сообщения
     * @param actions Список действий в формате "id1,label1,id2,label2,..."
     * @param callback Функция обратного вызова при нажатии на действие
     * @param iconName Имя иконки (опционально)
     * @param timeoutMs Время показа в миллисекундах
     */
    void sendNotificationWithActions(const QString& title,
                                     const QString& message,
                                     const QStringList& actions,
                                     std::function<void(const QString& actionId)> callback = nullptr,
                                     const QString& iconName = QString(),
                                     int timeoutMs = 5000);

    /**
     * @brief Показать уведомление с прогресс-баром
     * @param title Заголовок уведомления
     * @param message Текст сообщения
     * @param value Текущее значение прогресса (0-100)
     * @param iconName Имя иконки (опционально)
     * @return ID уведомления для последующего обновления
     */
    QString showProgressNotification(const QString& title,
                                     const QString& message,
                                     int value,
                                     const QString& iconName = QString());

    /**
     * @brief Обновить прогресс в уведомлении
     * @param notificationId ID уведомления
     * @param value Новое значение прогресса (0-100)
     * @param newMessage Новый текст сообщения (если пустое - не меняется)
     */
    void updateProgressNotification(const QString& notificationId,
                                    int value,
                                    const QString& newMessage = QString());

    /**
     * @brief Закрыть уведомление по ID
     * @param notificationId ID уведомления
     */
    void closeNotification(const QString& notificationId);

    /**
     * @brief Закрыть все уведомления приложения
     */
    void closeAllNotifications();

    /**
     * @brief Установить категорию уведомлений
     * @param category Категория (например, "email.arrived", "im.received")
     */
    void setCategory(const QString& category);

    /**
     * @brief Проверить поддержку уведомлений в системе
     */
    static bool isSupported();

    /**
     * @brief Получить информацию о сервере уведомлений
     */
    static QString getServerInfo();

    /**
     * @brief Получить возможности сервера уведомлений
     */
    static QStringList getServerCapabilities();

signals:
    /**
     * @brief Сигнал при закрытии уведомления
     * @param notificationId ID уведомления
     * @param reason Причина закрытия (1 - истекло время, 2 - закрыто пользователем, 3 - ошибка)
     */
    void notificationClosed(const QString& notificationId, int reason);

    /**
     * @brief Сигнал при нажатии на уведомление
     * @param notificationId ID уведомления
     */
    void notificationClicked(const QString& notificationId);

    /**
     * @brief Сигнал при выборе действия в уведомлении
     * @param notificationId ID уведомления
     * @param actionId ID выбранного действия
     */
    void actionInvoked(const QString& notificationId, const QString& actionId);

protected:
    explicit LibNotifyWrapper(QObject* parent = nullptr);
    ~LibNotifyWrapper() override;

private:
    // Внутренний класс для скрытия деталей реализации
    class Private;
    std::unique_ptr<Private> d;

    Q_DISABLE_COPY(LibNotifyWrapper)
};

#endif // LIBNOTIFYWRAPPER_H
```

## 2. Файл реализации `libnotifywrapper.cpp`:

```cpp
#include "libnotifywrapper.h"
#include <QCoreApplication>
#include <QDebug>
#include <QFile>
#include <QTemporaryFile>
#include <QDateTime>
#include <QTimer>
#include <QMap>
#include <libnotify/notify.h>

// Внутренний класс для скрытия деталей реализации
class LibNotifyWrapper::Private
{
public:
    Private(LibNotifyWrapper* q) : q_ptr(q), initialized(false) {}
    
    LibNotifyWrapper* q_ptr;
    bool initialized;
    QString appName;
    QString category;
    
    // Карта для хранения уведомлений и их обработчиков
    QMap<QString, NotifyNotification*> notifications;
    
    // Карта для callback-функций действий
    QMap<QString, std::function<void(const QString&)>> actionCallbacks;
    
    // Генерация уникального ID для уведомления
    QString generateNotificationId() {
        return QString("notify_%1_%2").arg(QDateTime::currentMSecsSinceEpoch()).arg(++idCounter);
    }
    
private:
    static quint64 idCounter;
};

quint64 LibNotifyWrapper::Private::idCounter = 0;

// Callback-функции для libnotify
static void notificationClosedCallback(NotifyNotification* notification, 
                                       gpointer user_data) {
    auto wrapper = reinterpret_cast<LibNotifyWrapper*>(user_data);
    if (wrapper) {
        wrapper->handleNotificationClosed(notification);
    }
}

static void notificationClickedCallback(NotifyNotification* notification,
                                        gpointer user_data) {
    auto wrapper = reinterpret_cast<LibNotifyWrapper*>(user_data);
    if (wrapper) {
        wrapper->handleNotificationClicked(notification);
    }
}

static void actionCallback(NotifyNotification* notification,
                           char* action,
                           gpointer user_data) {
    auto wrapper = reinterpret_cast<LibNotifyWrapper*>(user_data);
    if (wrapper) {
        wrapper->handleActionInvoked(notification, QString::fromUtf8(action));
    }
}

LibNotifyWrapper::LibNotifyWrapper(QObject* parent)
    : QObject(parent)
    , d(std::make_unique<Private>(this))
{
}

LibNotifyWrapper::~LibNotifyWrapper()
{
    if (d->initialized) {
        closeAllNotifications();
        notify_uninit();
    }
}

LibNotifyWrapper& LibNotifyWrapper::instance()
{
    static LibNotifyWrapper instance;
    return instance;
}

bool LibNotifyWrapper::initialize(const QString& appName)
{
    if (d->initialized) {
        return true;
    }
    
    d->appName = appName.isEmpty() ? QCoreApplication::applicationName() : appName;
    
    if (d->appName.isEmpty()) {
        d->appName = "QtApplication";
    }
    
    // Проверяем наличие библиотеки
    if (!QFile::exists("/usr/lib/libnotify.so.4") && 
        !QFile::exists("/usr/lib/x86_64-linux-gnu/libnotify.so.4")) {
        qWarning() << "libnotify not found on system";
        return false;
    }
    
    // Инициализируем libnotify
    gboolean result = notify_init(d->appName.toUtf8().constData());
    d->initialized = result;
    
    if (!d->initialized) {
        qWarning() << "Failed to initialize libnotify";
    }
    
    return d->initialized;
}

bool LibNotifyWrapper::isInitialized() const
{
    return d->initialized;
}

void LibNotifyWrapper::sendNotification(const QString& title, 
                                        const QString& message,
                                        int timeoutMs)
{
    sendNotification(title, message, NotificationType::Info, timeoutMs);
}

void LibNotifyWrapper::sendNotification(const QString& title,
                                        const QString& message,
                                        NotificationType type,
                                        int timeoutMs)
{
    if (!d->initialized && !initialize()) {
        qWarning() << "Cannot send notification: libnotify not initialized";
        return;
    }
    
    QString iconName;
    switch (type) {
    case NotificationType::Info:
        iconName = "dialog-information";
        break;
    case NotificationType::Warning:
        iconName = "dialog-warning";
        break;
    case NotificationType::Critical:
        iconName = "dialog-error";
        break;
    case NotificationType::Urgent:
        iconName = "dialog-error";
        break;
    }
    
    sendNotification(title, message, iconName, timeoutMs);
}

void LibNotifyWrapper::sendNotification(const QString& title,
                                        const QString& message,
                                        const QString& iconName,
                                        int timeoutMs)
{
    if (!d->initialized && !initialize()) {
        qWarning() << "Cannot send notification: libnotify not initialized";
        return;
    }
    
    NotifyNotification* notification = notify_notification_new(
        title.toUtf8().constData(),
        message.toUtf8().constData(),
        iconName.isEmpty() ? nullptr : iconName.toUtf8().constData()
    );
    
    if (!notification) {
        qWarning() << "Failed to create notification";
        return;
    }
    
    // Устанавливаем таймаут
    if (timeoutMs > 0) {
        notify_notification_set_timeout(notification, timeoutMs);
    }
    
    // Устанавливаем категорию, если задана
    if (!d->category.isEmpty()) {
        notify_notification_set_category(notification, d->category.toUtf8().constData());
    }
    
    // Устанавливаем callback-функции
    g_signal_connect(notification, "closed", 
                     G_CALLBACK(notificationClosedCallback), this);
    
    // Отправляем уведомление
    GError* error = nullptr;
    gboolean result = notify_notification_show(notification, &error);
    
    if (!result) {
        qWarning() << "Failed to show notification:" 
                   << (error ? QString::fromUtf8(error->message) : "Unknown error");
        if (error) {
            g_error_free(error);
        }
        g_object_unref(notification);
        return;
    }
    
    // Сохраняем уведомление
    QString notificationId = d->generateNotificationId();
    d->notifications[notificationId] = notification;
}

void LibNotifyWrapper::sendNotification(const QString& title,
                                        const QString& message,
                                        const QIcon& icon,
                                        int timeoutMs)
{
    // Создаем временный файл для иконки
    QTemporaryFile tempFile;
    if (tempFile.open()) {
        QString tempPath = tempFile.fileName() + ".png";
        QPixmap pixmap = icon.pixmap(48, 48);
        if (pixmap.save(tempPath, "PNG")) {
            sendNotification(title, message, tempPath, timeoutMs);
        } else {
            // Если не удалось сохранить иконку, отправляем без нее
            sendNotification(title, message, timeoutMs);
        }
    } else {
        sendNotification(title, message, timeoutMs);
    }
}

void LibNotifyWrapper::sendNotification(const QString& title,
                                        const QString& message,
                                        NotificationPriority priority,
                                        const QString& iconName,
                                        int timeoutMs)
{
    if (!d->initialized && !initialize()) {
        qWarning() << "Cannot send notification: libnotify not initialized";
        return;
    }
    
    NotifyNotification* notification = notify_notification_new(
        title.toUtf8().constData(),
        message.toUtf8().constData(),
        iconName.isEmpty() ? nullptr : iconName.toUtf8().constData()
    );
    
    if (!notification) {
        qWarning() << "Failed to create notification";
        return;
    }
    
    // Устанавливаем приоритет
    NotifyUrgency urgency = NOTIFY_URGENCY_NORMAL;
    switch (priority) {
    case NotificationPriority::Low:
        urgency = NOTIFY_URGENCY_LOW;
        break;
    case NotificationPriority::Normal:
        urgency = NOTIFY_URGENCY_NORMAL;
        break;
    case NotificationPriority::High:
        urgency = NOTIFY_URGENCY_CRITICAL;
        break;
    }
    
    notify_notification_set_urgency(notification, urgency);
    
    // Устанавливаем таймаут
    if (timeoutMs > 0) {
        notify_notification_set_timeout(notification, timeoutMs);
    }
    
    // Устанавливаем callback-функции
    g_signal_connect(notification, "closed", 
                     G_CALLBACK(notificationClosedCallback), this);
    
    // Отправляем уведомление
    GError* error = nullptr;
    gboolean result = notify_notification_show(notification, &error);
    
    if (!result) {
        qWarning() << "Failed to show notification:" 
                   << (error ? QString::fromUtf8(error->message) : "Unknown error");
        if (error) {
            g_error_free(error);
        }
        g_object_unref(notification);
        return;
    }
    
    // Сохраняем уведомление
    QString notificationId = d->generateNotificationId();
    d->notifications[notificationId] = notification;
}

void LibNotifyWrapper::sendNotificationWithActions(const QString& title,
                                                   const QString& message,
                                                   const QStringList& actions,
                                                   std::function<void(const QString&)> callback,
                                                   const QString& iconName,
                                                   int timeoutMs)
{
    if (!d->initialized && !initialize()) {
        qWarning() << "Cannot send notification: libnotify not initialized";
        return;
    }
    
    if (actions.size() % 2 != 0) {
        qWarning() << "Actions list must contain pairs of id and label";
        return;
    }
    
    NotifyNotification* notification = notify_notification_new(
        title.toUtf8().constData(),
        message.toUtf8().constData(),
        iconName.isEmpty() ? nullptr : iconName.toUtf8().constData()
    );
    
    if (!notification) {
        qWarning() << "Failed to create notification";
        return;
    }
    
    // Добавляем действия
    for (int i = 0; i < actions.size(); i += 2) {
        const QString& actionId = actions[i];
        const QString& label = actions[i + 1];
        notify_notification_add_action(
            notification,
            actionId.toUtf8().constData(),
            label.toUtf8().constData(),
            G_CALLBACK(actionCallback),
            this,
            nullptr
        );
    }
    
    // Устанавливаем таймаут
    if (timeoutMs > 0) {
        notify_notification_set_timeout(notification, timeoutMs);
    }
    
    // Сохраняем callback
    QString notificationId = d->generateNotificationId();
    if (callback) {
        d->actionCallbacks[notificationId] = callback;
    }
    
    // Устанавливаем callback-функции
    g_signal_connect(notification, "closed", 
                     G_CALLBACK(notificationClosedCallback), this);
    g_signal_connect(notification, "clicked", 
                     G_CALLBACK(notificationClickedCallback), this);
    
    // Отправляем уведомление
    GError* error = nullptr;
    gboolean result = notify_notification_show(notification, &error);
    
    if (!result) {
        qWarning() << "Failed to show notification:" 
                   << (error ? QString::fromUtf8(error->message) : "Unknown error");
        if (error) {
            g_error_free(error);
        }
        g_object_unref(notification);
        d->actionCallbacks.remove(notificationId);
        return;
    }
    
    // Сохраняем уведомление
    d->notifications[notificationId] = notification;
}

QString LibNotifyWrapper::showProgressNotification(const QString& title,
                                                   const QString& message,
                                                   int value,
                                                   const QString& iconName)
{
    if (!d->initialized && !initialize()) {
        qWarning() << "Cannot send notification: libnotify not initialized";
        return QString();
    }
    
    QString notificationId = d->generateNotificationId();
    
    NotifyNotification* notification = notify_notification_new(
        title.toUtf8().constData(),
        message.toUtf8().constData(),
        iconName.isEmpty() ? nullptr : iconName.toUtf8().constData()
    );
    
    if (!notification) {
        qWarning() << "Failed to create notification";
        return QString();
    }
    
    // Устанавливаем прогресс
    notify_notification_set_hint_int32(notification, "value", qBound(0, value, 100));
    
    // Отключаем автоматическое закрытие
    notify_notification_set_timeout(notification, NOTIFY_EXPIRES_NEVER);
    
    // Сохраняем и показываем уведомление
    d->notifications[notificationId] = notification;
    
    GError* error = nullptr;
    if (!notify_notification_show(notification, &error)) {
        qWarning() << "Failed to show progress notification:" 
                   << (error ? QString::fromUtf8(error->message) : "Unknown error");
        if (error) {
            g_error_free(error);
        }
        d->notifications.remove(notificationId);
        return QString();
    }
    
    return notificationId;
}

void LibNotifyWrapper::updateProgressNotification(const QString& notificationId,
                                                  int value,
                                                  const QString& newMessage)
{
    if (!d->notifications.contains(notificationId)) {
        return;
    }
    
    NotifyNotification* notification = d->notifications[notificationId];
    
    // Обновляем прогресс
    notify_notification_set_hint_int32(notification, "value", qBound(0, value, 100));
    
    // Обновляем сообщение, если задано
    if (!newMessage.isEmpty()) {
        notify_notification_update(notification,
                                   nullptr,
                                   newMessage.toUtf8().constData(),
                                   nullptr);
    }
    
    // Показываем обновленное уведомление
    GError* error = nullptr;
    if (!notify_notification_show(notification, &error)) {
        qWarning() << "Failed to update progress notification:" 
                   << (error ? QString::fromUtf8(error->message) : "Unknown error");
        if (error) {
            g_error_free(error);
        }
    }
}

void LibNotifyWrapper::closeNotification(const QString& notificationId)
{
    if (!d->notifications.contains(notificationId)) {
        return;
    }
    
    NotifyNotification* notification = d->notifications[notificationId];
    notify_notification_close(notification, nullptr);
    
    // Очищаем ресурсы
    d->actionCallbacks.remove(notificationId);
    g_object_unref(notification);
    d->notifications.remove(notificationId);
}

void LibNotifyWrapper::closeAllNotifications()
{
    for (auto it = d->notifications.begin(); it != d->notifications.end(); ++it) {
        notify_notification_close(it.value(), nullptr);
        g_object_unref(it.value());
    }
    
    d->notifications.clear();
    d->actionCallbacks.clear();
}

void LibNotifyWrapper::setCategory(const QString& category)
{
    d->category = category;
}

bool LibNotifyWrapper::isSupported()
{
    return notify_is_initted() || notify_get_server_info(nullptr, nullptr, nullptr);
}

QString LibNotifyWrapper::getServerInfo()
{
    char* name = nullptr;
    char* vendor = nullptr;
    char* version = nullptr;
    
    if (notify_get_server_info(&name, &vendor, &version)) {
        QString info = QString("Name: %1\nVendor: %2\nVersion: %3")
            .arg(name ? QString::fromUtf8(name) : "Unknown")
            .arg(vendor ? QString::fromUtf8(vendor) : "Unknown")
            .arg(version ? QString::fromUtf8(version) : "Unknown");
        
        g_free(name);
        g_free(vendor);
        g_free(version);
        
        return info;
    }
    
    return "Unable to get server info";
}

QStringList LibNotifyWrapper::getServerCapabilities()
{
    QStringList capabilities;
    GList* caps = notify_get_server_caps();
    
    for (GList* iter = caps; iter != nullptr; iter = iter->next) {
        capabilities.append(QString::fromUtf8(static_cast<char*>(iter->data)));
    }
    
    g_list_foreach(caps, (GFunc)g_free, nullptr);
    g_list_free(caps);
    
    return capabilities;
}

// Внутренние обработчики callback-функций
void LibNotifyWrapper::handleNotificationClosed(NotifyNotification* notification)
{
    QString notificationId;
    for (auto it = d->notifications.begin(); it != d->notifications.end(); ++it) {
        if (it.value() == notification) {
            notificationId = it.key();
            
            // Определяем причину закрытия
            int reason = 1; // По умолчанию - истекло время
            
            // В реальности нужно получить причину из уведомления,
            // но libnotify не предоставляет эту информацию в callback
            
            emit notificationClosed(notificationId, reason);
            
            // Очищаем ресурсы
            d->actionCallbacks.remove(notificationId);
            g_object_unref(notification);
            d->notifications.erase(it);
            break;
        }
    }
}

void LibNotifyWrapper::handleNotificationClicked(NotifyNotification* notification)
{
    QString notificationId;
    for (auto it = d->notifications.begin(); it != d->notifications.end(); ++it) {
        if (it.value() == notification) {
            notificationId = it.key();
            emit notificationClicked(notificationId);
            break;
        }
    }
}

void LibNotifyWrapper::handleActionInvoked(NotifyNotification* notification, 
                                           const QString& actionId)
{
    QString notificationId;
    for (auto it = d->notifications.begin(); it != d->notifications.end(); ++it) {
        if (it.value() == notification) {
            notificationId = it.key();
            
            // Вызываем callback, если есть
            if (d->actionCallbacks.contains(notificationId)) {
                d->actionCallbacks[notificationId](actionId);
            }
            
            emit actionInvoked(notificationId, actionId);
            break;
        }
    }
}
```

## 3. Файл CMakeLists.txt для сборки:

```cmake
cmake_minimum_required(VERSION 3.16)
project(LibNotifyWrapper VERSION 1.0.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

# Поиск Qt6
find_package(Qt6 REQUIRED COMPONENTS Core)

# Поиск libnotify
find_package(PkgConfig REQUIRED)
pkg_check_modules(LIBNOTIFY REQUIRED libnotify)

# Создание библиотеки
add_library(libnotifywrapper STATIC
    libnotifywrapper.cpp
)

target_link_libraries(libnotifywrapper
    Qt6::Core
    ${LIBNOTIFY_LIBRARIES}
)

target_include_directories(libnotifywrapper
    PUBLIC
        ${CMAKE_CURRENT_SOURCE_DIR}
        ${LIBNOTIFY_INCLUDE_DIRS}
)

target_compile_definitions(libnotifywrapper
    PRIVATE
        QT_CORE_LIB
)
```

## 4. Пример использования:

```cpp
#include "libnotifywrapper.h"
#include <QCoreApplication>
#include <QDebug>

int main(int argc, char *argv[])
{
    QCoreApplication app(argc, argv);
    
    // Инициализация
    LibNotifyWrapper& notifier = LibNotifyWrapper::instance();
    if (!notifier.initialize("MyApp")) {
        qWarning() << "Failed to initialize libnotify";
        return 1;
    }
    
    // Проверка поддержки
    if (LibNotifyWrapper::isSupported()) {
        qDebug() << "Notification server info:" 
                 << LibNotifyWrapper::getServerInfo();
        qDebug() << "Server capabilities:" 
                 << LibNotifyWrapper::getServerCapabilities();
    }
    
    // Простое уведомление
    notifier.sendNotification("Привет!", "Это тестовое уведомление");
    
    // Уведомление с типом
    notifier.sendNotification("Внимание!", 
                             "Произошла ошибка",
                             LibNotifyWrapper::NotificationType::Warning);
    
    // Уведомление с иконкой
    notifier.sendNotification("Успех!", 
                             "Операция завершена успешно",
                             "dialog-ok");
    
    // Уведомление с действиями
    QStringList actions = {"reply", "Ответить", "ignore", "Игнорировать"};
    
    notifier.sendNotificationWithActions(
        "Новое сообщение",
        "У вас новое сообщение от пользователя",
        actions,
        [](const QString& actionId) {
            if (actionId == "reply") {
                qDebug() << "Пользователь выбрал: Ответить";
            } else if (actionId == "ignore") {
                qDebug() << "Пользователь выбрал: Игнорировать";
            }
        },
        "mail-unread"
    );
    
    // Уведомление с прогрессом
    QString progressId = notifier.showProgressNotification(
        "Загрузка файла",
        "Загрузка начата...",
        0,
        "system-download"
    );
    
    if (!progressId.isEmpty()) {
        // Симуляция прогресса
        for (int i = 0; i <= 100; i += 10) {
            QThread::sleep(1);
            notifier.updateProgressNotification(progressId, i, 
                                                QString("Загружено: %1%").arg(i));
        }
        
        notifier.closeNotification(progressId);
    }
    
    return app.exec();
}
```

## 5. Установка зависимостей (Ubuntu/Debian):

```bash
sudo apt-get update
sudo apt-get install libnotify-dev qt6-base-dev cmake build-essential
```

## Особенности реализации:

1. **Синглтон**: Класс реализован как синглтон для доступа из любой части программы
2. **Обработка ошибок**: Все методы проверяют инициализацию и обрабатывают ошибки libnotify
3. **Callback-сигналы**: Используются Qt-сигналы для обработки событий уведомлений
4. **Прогресс-бар**: Поддержка уведомлений с прогрессом
5. **Действия**: Поддержка кнопок действий в уведомлениях
6. **Иконки**: Работа с QIcon и системными иконками
7. **Категории**: Поддержка категорий уведомлений

Этот класс предоставляет полный функционал для работы с уведомлениями в Linux-системах через libnotify и совместим с Qt 6.9.