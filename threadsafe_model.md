# Enhanced Thread-Safe Model with Undo/Redo Support

Here's the improved version of the thread-safe model that includes undo/redo functionality for add/remove operations:

## Updated Model Header (ThreadSafeModel.h)

```cpp
#ifndef THREADSAFEMODEL_H
#define THREADSAFEMODEL_H

#include <QAbstractListModel>
#include <QString>
#include <QMutex>
#include <QVector>
#include <QStack>

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
    Remove
};

struct ChangeCommand {
    ChangeType type;
    int index;
    ModelItem item;
    QVector<ModelItem> items; // For batch operations
    QVector<int> indices;     // For batch removal
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

    explicit ThreadSafeModel(QObject *parent = nullptr);

    // Basic functionality:
    int rowCount(const QModelIndex &parent = QModelIndex()) const override;
    QVariant data(const QModelIndex &index, int role = Qt::DisplayRole) const override;
    QHash<int, QByteArray> roleNames() const override;

    // Undo/redo functionality
    Q_INVOKABLE bool canUndo() const;
    Q_INVOKABLE bool canRedo() const;
    Q_INVOKABLE void undo();
    Q_INVOKABLE void redo();

public slots:
    void addItem(const QString &name, const QString &path, bool flag);
    void addItems(const QVector<ModelItem> &items);
    void removeItem(int index);
    void removeItems(const QVector<int> &indices);
    void updateItem(int index, const QString &name, const QString &path, bool flag);
    void clear();

private:
    void pushUndoCommand(const ChangeCommand& cmd);
    void applyCommand(const ChangeCommand& cmd, bool isUndo);

    QVector<ModelItem> m_items;
    mutable QMutex m_mutex;

    QStack<ChangeCommand> m_undoStack;
    QStack<ChangeCommand> m_redoStack;
    int m_maxUndoSteps = 100; // Limit undo history
};

#endif // THREADSAFEMODEL_H
```

## Updated Model Implementation (ThreadSafeModel.cpp)

```cpp
#include "ThreadSafeModel.h"

ThreadSafeModel::ThreadSafeModel(QObject *parent)
    : QAbstractListModel(parent)
{
}

int ThreadSafeModel::rowCount(const QModelIndex &parent) const
{
    Q_UNUSED(parent)
    QMutexLocker locker(&m_mutex);
    return m_items.size();
}

QVariant ThreadSafeModel::data(const QModelIndex &index, int role) const
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

QHash<int, QByteArray> ThreadSafeModel::roleNames() const
{
    return {
        {NameRole, "name"},
        {PathRole, "path"},
        {FlagRole, "flag"}
    };
}

void ThreadSafeModel::pushUndoCommand(const ChangeCommand& cmd)
{
    m_undoStack.push(cmd);
    if (m_undoStack.size() > m_maxUndoSteps) {
        m_undoStack.removeFirst();
    }
    m_redoStack.clear(); // Clear redo stack when new action is performed
}

void ThreadSafeModel::applyCommand(const ChangeCommand& cmd, bool isUndo)
{
    switch (cmd.type) {
    case ChangeType::Add: {
        if (isUndo) {
            // Undo add means remove
            if (!cmd.items.isEmpty()) {
                beginRemoveRows(QModelIndex(), cmd.index, cmd.index + cmd.items.size() - 1);
                m_items.remove(cmd.index, cmd.items.size());
                endRemoveRows();
            } else {
                beginRemoveRows(QModelIndex(), cmd.index, cmd.index);
                m_items.remove(cmd.index);
                endRemoveRows();
            }
        } else {
            // Redo add means add again
            if (!cmd.items.isEmpty()) {
                beginInsertRows(QModelIndex(), cmd.index, cmd.index + cmd.items.size() - 1);
                m_items.insert(cmd.index, cmd.items.size(), ModelItem());
                for (int i = 0; i < cmd.items.size(); ++i) {
                    m_items[cmd.index + i] = cmd.items[i];
                }
                endInsertRows();
            } else {
                beginInsertRows(QModelIndex(), cmd.index, cmd.index);
                m_items.insert(cmd.index, cmd.item);
                endInsertRows();
            }
        }
        break;
    }
    case ChangeType::Remove: {
        if (isUndo) {
            // Undo remove means add back
            if (!cmd.items.isEmpty()) {
                beginInsertRows(QModelIndex(), cmd.index, cmd.index + cmd.items.size() - 1);
                m_items.insert(cmd.index, cmd.items.size(), ModelItem());
                for (int i = 0; i < cmd.items.size(); ++i) {
                    m_items[cmd.index + i] = cmd.items[i];
                }
                endInsertRows();
            } else {
                beginInsertRows(QModelIndex(), cmd.index, cmd.index);
                m_items.insert(cmd.index, cmd.item);
                endInsertRows();
            }
        } else {
            // Redo remove means remove again
            if (!cmd.indices.isEmpty()) {
                // Sort indices in descending order for removal
                QVector<int> sortedIndices = cmd.indices;
                std::sort(sortedIndices.begin(), sortedIndices.end(), std::greater<int>());
                for (int index : sortedIndices) {
                    if (index >= 0 && index < m_items.size()) {
                        beginRemoveRows(QModelIndex(), index, index);
                        m_items.remove(index);
                        endRemoveRows();
                    }
                }
            } else {
                beginRemoveRows(QModelIndex(), cmd.index, cmd.index);
                m_items.remove(cmd.index);
                endRemoveRows();
            }
        }
        break;
    }
    }
}

bool ThreadSafeModel::canUndo() const
{
    QMutexLocker locker(&m_mutex);
    return !m_undoStack.isEmpty();
}

bool ThreadSafeModel::canRedo() const
{
    QMutexLocker locker(&m_mutex);
    return !m_redoStack.isEmpty();
}

void ThreadSafeModel::undo()
{
    QMutexLocker locker(&m_mutex);
    if (m_undoStack.isEmpty())
        return;

    ChangeCommand cmd = m_undoStack.pop();
    applyCommand(cmd, true);
    m_redoStack.push(cmd);
}

void ThreadSafeModel::redo()
{
    QMutexLocker locker(&m_mutex);
    if (m_redoStack.isEmpty())
        return;

    ChangeCommand cmd = m_redoStack.pop();
    applyCommand(cmd, false);
    m_undoStack.push(cmd);
}

void ThreadSafeModel::addItem(const QString &name, const QString &path, bool flag)
{
    QMutexLocker locker(&m_mutex);
    int newIndex = m_items.size();
    beginInsertRows(QModelIndex(), newIndex, newIndex);
    m_items.append(ModelItem(name, path, flag));
    endInsertRows();

    ChangeCommand cmd;
    cmd.type = ChangeType::Add;
    cmd.index = newIndex;
    cmd.item = ModelItem(name, path, flag);
    pushUndoCommand(cmd);
}

void ThreadSafeModel::addItems(const QVector<ModelItem> &items)
{
    if (items.isEmpty())
        return;

    QMutexLocker locker(&m_mutex);
    int newIndex = m_items.size();
    beginInsertRows(QModelIndex(), newIndex, newIndex + items.size() - 1);
    m_items.append(items);
    endInsertRows();

    ChangeCommand cmd;
    cmd.type = ChangeType::Add;
    cmd.index = newIndex;
    cmd.items = items;
    pushUndoCommand(cmd);
}

void ThreadSafeModel::removeItem(int index)
{
    QMutexLocker locker(&m_mutex);
    if (index < 0 || index >= m_items.size())
        return;

    ChangeCommand cmd;
    cmd.type = ChangeType::Remove;
    cmd.index = index;
    cmd.item = m_items[index];

    beginRemoveRows(QModelIndex(), index, index);
    m_items.remove(index);
    endRemoveRows();

    pushUndoCommand(cmd);
}

void ThreadSafeModel::removeItems(const QVector<int> &indices)
{
    if (indices.isEmpty())
        return;

    QMutexLocker locker(&m_mutex);

    // Sort indices in descending order to avoid shifting issues
    QVector<int> sortedIndices = indices;
    std::sort(sortedIndices.begin(), sortedIndices.end(), std::greater<int>());

    // Store items being removed for undo
    QVector<ModelItem> removedItems;
    removedItems.reserve(sortedIndices.size());
    for (int index : sortedIndices) {
        if (index >= 0 && index < m_items.size()) {
            removedItems.prepend(m_items[index]); // prepend to maintain original order
        }
    }

    // Perform removal
    for (int index : sortedIndices) {
        if (index >= 0 && index < m_items.size()) {
            beginRemoveRows(QModelIndex(), index, index);
            m_items.remove(index);
            endRemoveRows();
        }
    }

    if (!removedItems.isEmpty()) {
        ChangeCommand cmd;
        cmd.type = ChangeType::Remove;
        cmd.index = sortedIndices.last(); // Store the first index (since we prepended)
        cmd.items = removedItems;
        cmd.indices = indices; // Store original indices for redo
        pushUndoCommand(cmd);
    }
}

void ThreadSafeModel::updateItem(int index, const QString &name, const QString &path, bool flag)
{
    QMutexLocker locker(&m_mutex);
    if (index < 0 || index >= m_items.size())
        return;

    // For undo/redo, we need to store the previous state
    ModelItem oldItem = m_items[index];
    m_items[index] = ModelItem(name, path, flag);
    
    QModelIndex modelIndex = createIndex(index, 0);
    emit dataChanged(modelIndex, modelIndex, {NameRole, PathRole, FlagRole});

    // Note: If you want undo for updates, you would need to add another ChangeType
    // and handle it similarly to add/remove
}

void ThreadSafeModel::clear()
{
    QMutexLocker locker(&m_mutex);
    if (m_items.isEmpty())
        return;

    // Store all items for undo
    ChangeCommand cmd;
    cmd.type = ChangeType::Remove;
    cmd.index = 0;
    cmd.items = m_items;
    for (int i = 0; i < m_items.size(); ++i) {
        cmd.indices.append(i);
    }

    beginRemoveRows(QModelIndex(), 0, m_items.size() - 1);
    m_items.clear();
    endRemoveRows();

    pushUndoCommand(cmd);
}
```

## Updated QML Example (main.qml)

```qml
import QtQuick 2.15
import QtQuick.Window 2.15
import QtQuick.Controls 2.15

Window {
    width: 640
    height: 480
    visible: true
    title: "Thread-Safe Model with Undo/Redo"

    ListView {
        id: listView
        anchors {
            top: parent.top
            left: parent.left
            right: parent.right
            bottom: buttonRow.top
        }
        model: threadSafeModel
        delegate: ItemDelegate {
            width: ListView.view.width
            height: 60
            text: name + " - " + path
            checked: flag
            checkable: true
        }
    }

    Row {
        id: buttonRow
        anchors.bottom: parent.bottom
        anchors.horizontalCenter: parent.horizontalCenter
        spacing: 10
        padding: 10

        Button {
            text: "Add Random Item"
            onClicked: {
                threadSafeModel.addItem(
                    "Random " + Math.floor(Math.random() * 1000),
                    "/random/path",
                    Math.random() > 0.5
                )
            }
        }

        Button {
            text: "Remove Selected"
            onClicked: {
                let indices = [];
                for (let i = 0; i < listView.count; i++) {
                    if (listView.itemAtIndex(i).checked) {
                        indices.push(i);
                    }
                }
                threadSafeModel.removeItems(indices);
            }
        }

        Button {
            text: "Undo"
            enabled: threadSafeModel.canUndo
            onClicked: threadSafeModel.undo()
        }

        Button {
            text: "Redo"
            enabled: threadSafeModel.canRedo
            onClicked: threadSafeModel.redo()
        }

        Button {
            text: "Clear All"
            onClicked: threadSafeModel.clear()
        }
    }
}
```

## Key Enhancements:

1. **Undo/Redo Stack**: Added QStack-based undo/redo functionality
2. **Command Pattern**: Implemented a ChangeCommand structure to track operations
3. **Batch Operation Support**: Undo/redo works for both single and batch operations
4. **Thread Safety**: Maintained thread safety while adding undo/redo functionality
5. **QML Integration**: Added Q_INVOKABLE methods for easy QML access

## How to Use the New Features:

1. **Undo**: Call `undo()` to revert the last operation
2. **Redo**: Call `redo()` to reapply the last undone operation
3. **Check Availability**: Use `canUndo()` and `canRedo()` to enable/disable UI buttons
4. **All Operations**: Undo/redo works for addItem, addItems, removeItem, removeItems, and clear

The implementation maintains all the original thread-safe characteristics while adding comprehensive undo/redo support. The undo history is limited to 100 steps (configurable) to prevent memory issues.
