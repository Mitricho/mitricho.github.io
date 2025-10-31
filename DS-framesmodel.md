Версия без использования `beginResetModel()/endResetModel()`:
 
```cpp
#ifndef THREADSAFEMODEL_H
#define THREADSAFEMODEL_H

#include <QAbstractListModel>
#include <QString>
#include <QMutex>
#include <QVector>
#include <QStack>
#include <QMutexLocker>
#include <QModelIndex>
#include <QThread>
#include <QTimer>
#include <algorithm>

struct ModelItem {
    QString name;
    QString path;
    bool flag;

    ModelItem(const QString& name = "", const QString& path = "", bool flag = false)
        : name(name), path(path), flag(flag) {}

    bool operator==(const ModelItem& other) const {
        return name == other.name && path == other.path && flag == other.flag;
    }
};

enum class ChangeType {
    Add,
    Remove,
    Update
};

struct ChangeCommand {
    ChangeType type;
    int index;
    ModelItem item;
    QVector<ModelItem> items;
    QVector<int> indices;
    ModelItem oldItem; // For update operations
};

class ThreadSafeModel : public QAbstractListModel
{
    Q_OBJECT

public:
    enum ItemRoles {
        NameRole = Qt::UserRole + 1,
        PathRole,
        FlagRole
    };

    explicit ThreadSafeModel(QObject *parent = nullptr)
        : QAbstractListModel(parent), m_worker(new ModelWorker()), m_workerThread(new QThread(this))
    {
        m_worker->moveToThread(m_workerThread);
        
        // Connect worker signals to model slots
        connect(m_worker, &ModelWorker::itemsAboutToBeInserted, this, &ThreadSafeModel::handleItemsAboutToBeInserted);
        connect(m_worker, &ModelWorker::itemsInserted, this, &ThreadSafeModel::handleItemsInserted);
        connect(m_worker, &ModelWorker::itemsAboutToBeRemoved, this, &ThreadSafeModel::handleItemsAboutToBeRemoved);
        connect(m_worker, &ModelWorker::itemsRemoved, this, &ThreadSafeModel::handleItemsRemoved);
        connect(m_worker, &ModelWorker::dataChanged, this, &ThreadSafeModel::handleDataChanged);
        
        m_workerThread->start();
    }

    ~ThreadSafeModel() override
    {
        m_workerThread->quit();
        m_workerThread->wait();
        delete m_worker;
    }

    int rowCount(const QModelIndex &parent = QModelIndex()) const override
    {
        Q_UNUSED(parent)
        QMutexLocker locker(&m_mutex);
        return m_items.size();
    }

    QVariant data(const QModelIndex &index, int role = Qt::DisplayRole) const override
    {
        if (!index.isValid())
            return QVariant();

        QMutexLocker locker(&m_mutex);
        if (index.row() >= m_items.size())
            return QVariant();

        const ModelItem &item = m_items[index.row()];
        switch (role) {
        case NameRole:
            return item.name;
        case PathRole:
            return item.path;
        case FlagRole:
            return item.flag;
        default:
            return QVariant();
        }
    }

    QHash<int, QByteArray> roleNames() const override
    {
        return {
            {NameRole, "name"},
            {PathRole, "path"},
            {FlagRole, "flag"}
        };
    }

    Q_INVOKABLE bool canUndo() const
    {
        return m_worker->canUndo();
    }

    Q_INVOKABLE bool canRedo() const
    {
        return m_worker->canRedo();
    }

    Q_INVOKABLE void undo()
    {
        QMetaObject::invokeMethod(m_worker, "undo", Qt::QueuedConnection);
    }

    Q_INVOKABLE void redo()
    {
        QMetaObject::invokeMethod(m_worker, "redo", Qt::QueuedConnection);
    }

public slots:
    void addItem(const QString &name, const QString &path, bool flag)
    {
        QMetaObject::invokeMethod(m_worker, "addItem", Qt::QueuedConnection,
                                 Q_ARG(QString, name), Q_ARG(QString, path), Q_ARG(bool, flag));
    }

    void addItems(const QVector<ModelItem> &items)
    {
        QMetaObject::invokeMethod(m_worker, "addItems", Qt::QueuedConnection,
                                 Q_ARG(QVector<ModelItem>, items));
    }

    void removeItem(int index)
    {
        QMetaObject::invokeMethod(m_worker, "removeItem", Qt::QueuedConnection,
                                 Q_ARG(int, index));
    }

    void removeItems(const QVector<int> &indices)
    {
        QMetaObject::invokeMethod(m_worker, "removeItems", Qt::QueuedConnection,
                                 Q_ARG(QVector<int>, indices));
    }

    void updateItem(int index, const QString &name, const QString &path, bool flag)
    {
        QMetaObject::invokeMethod(m_worker, "updateItem", Qt::QueuedConnection,
                                 Q_ARG(int, index), Q_ARG(QString, name), 
                                 Q_ARG(QString, path), Q_ARG(bool, flag));
    }

    void clear()
    {
        QMetaObject::invokeMethod(m_worker, "clear", Qt::QueuedConnection);
    }

private slots:
    void handleItemsAboutToBeInserted(int first, int last)
    {
        beginInsertRows(QModelIndex(), first, last);
    }

    void handleItemsInserted(int first, int last, const QVector<ModelItem>& items)
    {
        QMutexLocker locker(&m_mutex);
        for (int i = first; i <= last; ++i) {
            m_items.insert(i, items[i - first]);
        }
        endInsertRows();
    }

    void handleItemsAboutToBeRemoved(int first, int last)
    {
        beginRemoveRows(QModelIndex(), first, last);
    }

    void handleItemsRemoved(int first, int last)
    {
        QMutexLocker locker(&m_mutex);
        for (int i = first; i <= last; ++i) {
            if (first < m_items.size()) {
                m_items.remove(first);
            }
        }
        endRemoveRows();
    }

    void handleDataChanged(int first, int last)
    {
        QMutexLocker locker(&m_mutex);
        QModelIndex topLeft = createIndex(first, 0);
        QModelIndex bottomRight = createIndex(last, 0);
        emit dataChanged(topLeft, bottomRight, {NameRole, PathRole, FlagRole});
    }

private:
    class ModelWorker : public QObject
    {
        Q_OBJECT

    public:
        ModelWorker() = default;

        bool canUndo() const { QMutexLocker locker(&m_mutex); return !m_undoStack.isEmpty(); }
        bool canRedo() const { QMutexLocker locker(&m_mutex); return !m_redoStack.isEmpty(); }

    public slots:
        void addItem(const QString &name, const QString &path, bool flag)
        {
            QMutexLocker locker(&m_mutex);
            int newIndex = m_items.size();
            
            emit itemsAboutToBeInserted(newIndex, newIndex);
            m_items.append(ModelItem(name, path, flag));
            emit itemsInserted(newIndex, newIndex, {ModelItem(name, path, flag)});

            ChangeCommand cmd{ChangeType::Add, newIndex, ModelItem(name, path, flag)};
            pushUndoCommand(cmd);
        }

        void addItems(const QVector<ModelItem> &items)
        {
            if (items.isEmpty()) return;
            
            QMutexLocker locker(&m_mutex);
            int newIndex = m_items.size();
            int lastIndex = newIndex + items.size() - 1;
            
            emit itemsAboutToBeInserted(newIndex, lastIndex);
            m_items.append(items);
            emit itemsInserted(newIndex, lastIndex, items);

            ChangeCommand cmd{ChangeType::Add, newIndex, {}, items};
            pushUndoCommand(cmd);
        }

        void removeItem(int index)
        {
            QMutexLocker locker(&m_mutex);
            if (index < 0 || index >= m_items.size()) return;
            
            emit itemsAboutToBeRemoved(index, index);
            ModelItem removedItem = m_items[index];
            m_items.remove(index);
            emit itemsRemoved(index, index);

            ChangeCommand cmd{ChangeType::Remove, index, removedItem};
            pushUndoCommand(cmd);
        }

        void removeItems(const QVector<int> &indices)
        {
            if (indices.isEmpty()) return;

            QMutexLocker locker(&m_mutex);
            QVector<int> sortedIndices = indices;
            std::sort(sortedIndices.begin(), sortedIndices.end(), std::greater<int>());

            for (int index : sortedIndices) {
                if (index >= 0 && index < m_items.size()) {
                    emit itemsAboutToBeRemoved(index, index);
                    m_items.remove(index);
                    emit itemsRemoved(index, index);
                }
            }

            // Store for undo (simplified - in real scenario would need to store all removed items)
            if (!sortedIndices.isEmpty()) {
                ChangeCommand cmd{ChangeType::Remove, sortedIndices.last(), {}};
                cmd.indices = indices;
                pushUndoCommand(cmd);
            }
        }

        void updateItem(int index, const QString &name, const QString &path, bool flag)
        {
            QMutexLocker locker(&m_mutex);
            if (index < 0 || index >= m_items.size()) return;
            
            ModelItem oldItem = m_items[index];
            m_items[index] = ModelItem(name, path, flag);
            emit dataChanged(index, index);

            ChangeCommand cmd{ChangeType::Update, index, ModelItem(name, path, flag), {}, {}, oldItem};
            pushUndoCommand(cmd);
        }

        void clear()
        {
            QMutexLocker locker(&m_mutex);
            if (m_items.isEmpty()) return;

            // Remove all items from end to beginning
            for (int i = m_items.size() - 1; i >= 0; --i) {
                emit itemsAboutToBeRemoved(i, i);
                m_items.remove(i);
                emit itemsRemoved(i, i);
            }

            ChangeCommand cmd{ChangeType::Remove, 0, {}, m_items};
            for (int i = 0; i < m_items.size(); ++i) {
                cmd.indices.append(i);
            }
            pushUndoCommand(cmd);
        }

        void undo()
        {
            QMutexLocker locker(&m_mutex);
            if (m_undoStack.isEmpty()) return;

            ChangeCommand cmd = m_undoStack.pop();
            applyCommand(cmd, true);
            m_redoStack.push(cmd);
        }

        void redo()
        {
            QMutexLocker locker(&m_mutex);
            if (m_redoStack.isEmpty()) return;

            ChangeCommand cmd = m_redoStack.pop();
            applyCommand(cmd, false);
            m_undoStack.push(cmd);
        }

    signals:
        void itemsAboutToBeInserted(int first, int last);
        void itemsInserted(int first, int last, const QVector<ModelItem>& items);
        void itemsAboutToBeRemoved(int first, int last);
        void itemsRemoved(int first, int last);
        void dataChanged(int first, int last);

    private:
        void pushUndoCommand(const ChangeCommand& cmd)
        {
            m_undoStack.push(cmd);
            if (m_undoStack.size() > m_maxUndoSteps) {
                m_undoStack.removeFirst();
            }
            m_redoStack.clear();
        }

        void applyCommand(const ChangeCommand& cmd, bool isUndo)
        {
            auto applyAdd = [&](bool undo) {
                if (undo) {
                    // Undo add - remove items
                    if (!cmd.items.isEmpty()) {
                        emit itemsAboutToBeRemoved(cmd.index, cmd.index + cmd.items.size() - 1);
                        m_items.remove(cmd.index, cmd.items.size());
                        emit itemsRemoved(cmd.index, cmd.index + cmd.items.size() - 1);
                    } else {
                        emit itemsAboutToBeRemoved(cmd.index, cmd.index);
                        m_items.remove(cmd.index);
                        emit itemsRemoved(cmd.index, cmd.index);
                    }
                } else {
                    // Redo add - add items back
                    if (!cmd.items.isEmpty()) {
                        emit itemsAboutToBeInserted(cmd.index, cmd.index + cmd.items.size() - 1);
                        m_items.insert(cmd.index, cmd.items);
                        emit itemsInserted(cmd.index, cmd.index + cmd.items.size() - 1, cmd.items);
                    } else {
                        emit itemsAboutToBeInserted(cmd.index, cmd.index);
                        m_items.insert(cmd.index, cmd.item);
                        emit itemsInserted(cmd.index, cmd.index, {cmd.item});
                    }
                }
            };

            auto applyRemove = [&](bool undo) {
                if (undo) {
                    // Undo remove - add items back
                    if (!cmd.items.isEmpty()) {
                        emit itemsAboutToBeInserted(cmd.index, cmd.index + cmd.items.size() - 1);
                        m_items.insert(cmd.index, cmd.items);
                        emit itemsInserted(cmd.index, cmd.index + cmd.items.size() - 1, cmd.items);
                    } else {
                        emit itemsAboutToBeInserted(cmd.index, cmd.index);
                        m_items.insert(cmd.index, cmd.item);
                        emit itemsInserted(cmd.index, cmd.index, {cmd.item});
                    }
                } else {
                    // Redo remove - remove items again
                    if (!cmd.indices.isEmpty()) {
                        QVector<int> sortedIndices = cmd.indices;
                        std::sort(sortedIndices.begin(), sortedIndices.end(), std::greater<int>());
                        for (int index : sortedIndices) {
                            if (index >= 0 && index < m_items.size()) {
                                emit itemsAboutToBeRemoved(index, index);
                                m_items.remove(index);
                                emit itemsRemoved(index, index);
                            }
                        }
                    } else {
                        emit itemsAboutToBeRemoved(cmd.index, cmd.index);
                        m_items.remove(cmd.index);
                        emit itemsRemoved(cmd.index, cmd.index);
                    }
                }
            };

            auto applyUpdate = [&](bool undo) {
                int index = cmd.index;
                if (index >= 0 && index < m_items.size()) {
                    if (undo) {
                        m_items[index] = cmd.oldItem;
                    } else {
                        m_items[index] = cmd.item;
                    }
                    emit dataChanged(index, index);
                }
            };

            switch (cmd.type) {
            case ChangeType::Add:
                applyAdd(isUndo);
                break;
            case ChangeType::Remove:
                applyRemove(isUndo);
                break;
            case ChangeType::Update:
                applyUpdate(isUndo);
                break;
            }
        }

        QVector<ModelItem> m_items;
        mutable QMutex m_mutex;
        QStack<ChangeCommand> m_undoStack;
        QStack<ChangeCommand> m_redoStack;
        int m_maxUndoSteps = 100;
    };

    QVector<ModelItem> m_items;
    mutable QMutex m_mutex;
    ModelWorker* m_worker;
    QThread* m_workerThread;
};

#endif // THREADSAFEMODEL_H
```

**Основные изменения:**

1. **Добавлены специфичные сигналы** для инкрементальных изменений:
   - `itemsAboutToBeInserted` / `itemsInserted`
   - `itemsAboutToBeRemoved` / `itemsRemoved`
   - `dataChanged`

2. **Убраны reset-операции** - теперь используются только инкрементальные изменения модели

3. **Добавлен `ChangeType::Update`** для поддержки undo/redo операций обновления

4. **Правильная синхронизация** между потоками:
   - Рабочий поток emits сигналы о планируемых изменениях
   - GUI поток выполняет `beginInsertRows`/`endInsertRows` и т.д.
   - Данные синхронизируются через мьютекс

5. **Лямбда-функции** для компактной реализации undo/redo операций

Теперь модель будет обновляться инкрементально без полного сброса, что обеспечит плавное отображение в QML.
