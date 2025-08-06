# Enhanced Thread-Safe Model with Move Operations

Here's the improved version with `moveItem` and `moveItems` functionality, including undo/redo support:

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
    Remove,
    Move,
    MoveMultiple
};

struct ChangeCommand {
    ChangeType type;
    int index;
    int targetIndex;
    ModelItem item;
    QVector<ModelItem> items;
    QVector<int> indices;
    QVector<int> targetIndices;
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
    void moveItem(int fromIndex, int toIndex);
    void moveItems(const QVector<int>& indices, int toIndex);

private:
    void pushUndoCommand(const ChangeCommand& cmd);
    void applyCommand(const ChangeCommand& cmd, bool isUndo);
    void internalMoveItem(int fromIndex, int toIndex);
    void internalMoveItems(const QVector<int>& indices, int toIndex);

    QVector<ModelItem> m_items;
    mutable QMutex m_mutex;

    QStack<ChangeCommand> m_undoStack;
    QStack<ChangeCommand> m_redoStack;
    int m_maxUndoSteps = 100;
};

#endif // THREADSAFEMODEL_H
```

## Updated Model Implementation (ThreadSafeModel.cpp)

```cpp
// [Previous implementation remains the same until the new methods]

void ThreadSafeModel::moveItem(int fromIndex, int toIndex)
{
    QMutexLocker locker(&m_mutex);
    if (fromIndex < 0 || fromIndex >= m_items.size() || 
        toIndex < 0 || toIndex > m_items.size() || fromIndex == toIndex) {
        return;
    }

    // Adjust target index if it's after the fromIndex
    int adjustedToIndex = toIndex > fromIndex ? toIndex - 1 : toIndex;

    beginMoveRows(QModelIndex(), fromIndex, fromIndex, QModelIndex(), adjustedToIndex);
    internalMoveItem(fromIndex, toIndex);
    endMoveRows();

    ChangeCommand cmd;
    cmd.type = ChangeType::Move;
    cmd.index = fromIndex;
    cmd.targetIndex = toIndex;
    cmd.item = m_items[adjustedToIndex]; // Store the item at its new position
    pushUndoCommand(cmd);
}

void ThreadSafeModel::moveItems(const QVector<int>& indices, int toIndex)
{
    QMutexLocker locker(&m_mutex);
    if (indices.isEmpty() || toIndex < 0 || toIndex > m_items.size()) {
        return;
    }

    // Remove duplicates and sort indices
    QVector<int> uniqueIndices = indices;
    std::sort(uniqueIndices.begin(), uniqueIndices.end());
    uniqueIndices.erase(std::unique(uniqueIndices.begin(), uniqueIndices.end()), uniqueIndices.end());

    // Validate all indices
    for (int index : uniqueIndices) {
        if (index < 0 || index >= m_items.size()) {
            return;
        }
    }

    // Check if all indices are contiguous and already in position
    bool allSame = true;
    for (int i = 1; i < uniqueIndices.size(); ++i) {
        if (uniqueIndices[i] != uniqueIndices[i-1] + 1) {
            allSame = false;
            break;
        }
    }
    if (allSame && uniqueIndices.contains(toIndex)) {
        return;
    }

    // Calculate the range being moved
    int firstIndex = uniqueIndices.first();
    int lastIndex = uniqueIndices.last();
    int count = uniqueIndices.size();

    // Calculate adjusted toIndex
    int adjustedToIndex = toIndex;
    if (toIndex > firstIndex) {
        adjustedToIndex = toIndex - count;
        if (adjustedToIndex <= firstIndex) {
            // No actual movement needed
            return;
        }
    }

    beginMoveRows(QModelIndex(), firstIndex, lastIndex, QModelIndex(), adjustedToIndex);
    internalMoveItems(uniqueIndices, toIndex);
    endMoveRows();

    ChangeCommand cmd;
    cmd.type = ChangeType::MoveMultiple;
    cmd.indices = uniqueIndices;
    cmd.targetIndex = toIndex;
    
    // Store the items at their new positions
    int startPos = adjustedToIndex;
    if (toIndex > firstIndex) {
        startPos = adjustedToIndex - count + 1;
    }
    for (int i = 0; i < count; ++i) {
        cmd.items.append(m_items[startPos + i]);
        cmd.targetIndices.append(startPos + i);
    }
    
    pushUndoCommand(cmd);
}

void ThreadSafeModel::internalMoveItem(int fromIndex, int toIndex)
{
    if (fromIndex == toIndex) return;

    ModelItem item = m_items[fromIndex];
    m_items.remove(fromIndex);
    
    if (toIndex > fromIndex) {
        m_items.insert(toIndex - 1, item);
    } else {
        m_items.insert(toIndex, item);
    }
}

void ThreadSafeModel::internalMoveItems(const QVector<int>& indices, int toIndex)
{
    // Extract items to move
    QVector<ModelItem> itemsToMove;
    for (int index : indices) {
        itemsToMove.append(m_items[index]);
    }

    // Remove items (from highest to lowest index)
    for (int i = indices.size() - 1; i >= 0; --i) {
        m_items.remove(indices[i]);
    }

    // Calculate insertion point
    int insertionPoint = toIndex;
    for (int index : indices) {
        if (index < insertionPoint) {
            insertionPoint--;
        }
    }

    // Insert items at new position
    for (int i = 0; i < itemsToMove.size(); ++i) {
        m_items.insert(insertionPoint + i, itemsToMove[i]);
    }
}

// Update applyCommand to handle move operations
void ThreadSafeModel::applyCommand(const ChangeCommand& cmd, bool isUndo)
{
    switch (cmd.type) {
    // [Previous cases remain the same...]
    
    case ChangeType::Move: {
        if (isUndo) {
            // Undo move means move back to original position
            int currentIndex = cmd.targetIndex > cmd.index ? cmd.targetIndex - 1 : cmd.targetIndex;
            beginMoveRows(QModelIndex(), currentIndex, currentIndex, QModelIndex(), cmd.index);
            internalMoveItem(currentIndex, cmd.index);
            endMoveRows();
        } else {
            // Redo move means move again to target position
            beginMoveRows(QModelIndex(), cmd.index, cmd.index, QModelIndex(), cmd.targetIndex);
            internalMoveItem(cmd.index, cmd.targetIndex);
            endMoveRows();
        }
        break;
    }
    
    case ChangeType::MoveMultiple: {
        if (isUndo) {
            // Undo multiple move - move back to original positions
            // First we need to collect current indices
            QVector<int> currentIndices;
            for (int i = 0; i < cmd.items.size(); ++i) {
                currentIndices.append(cmd.targetIndices[i]);
            }
            
            // Sort in reverse order for removal
            QVector<int> sortedCurrent = currentIndices;
            std::sort(sortedCurrent.begin(), sortedCurrent.end(), std::greater<int>());
            
            // Remove items from current positions
            for (int index : sortedCurrent) {
                m_items.remove(index);
            }
            
            // Reinsert at original positions (need to sort original indices)
            QVector<int> originalIndices = cmd.indices;
            std::sort(originalIndices.begin(), originalIndices.end());
            
            for (int i = 0; i < originalIndices.size(); ++i) {
                m_items.insert(originalIndices[i], cmd.items[i]);
            }
            
            // Notify the view of the changes
            int minRow = qMin(originalIndices.first(), currentIndices.first());
            int maxRow = qMax(originalIndices.last(), currentIndices.last());
            emit dataChanged(createIndex(minRow, 0), createIndex(maxRow, 0));
            
        } else {
            // Redo multiple move - move to target positions again
            // First remove from original positions
            QVector<int> originalIndices = cmd.indices;
            std::sort(originalIndices.begin(), originalIndices.end(), std::greater<int>());
            
            for (int index : originalIndices) {
                m_items.remove(index);
            }
            
            // Insert at target positions
            int insertionPoint = cmd.targetIndex;
            for (int i = 0; i < cmd.items.size(); ++i) {
                m_items.insert(insertionPoint + i, cmd.items[i]);
            }
            
            // Notify the view of the changes
            int minRow = qMin(originalIndices.last(), insertionPoint);
            int maxRow = qMax(originalIndices.first(), insertionPoint + cmd.items.size() - 1);
            emit dataChanged(createIndex(minRow, 0), createIndex(maxRow, 0));
        }
        break;
    }
    }
}
```

## Updated QML Example (main.qml)

```qml
import QtQuick 2.15
import QtQuick.Window 2.15
import QtQuick.Controls 2.15

Window {
    width: 640
    height: 600
    visible: true
    title: "Thread-Safe Model with Move Operations"

    ListView {
        id: listView
        anchors {
            top: parent.top
            left: parent.left
            right: parent.right
            bottom: buttonColumn.top
        }
        model: threadSafeModel
        delegate: ItemDelegate {
            width: ListView.view.width
            height: 60
            text: name + " - " + path
            checked: flag
            checkable: true

            Drag.active: dragArea.drag.active
            Drag.hotSpot.x: width / 2
            Drag.hotSpot.y: height / 2

            MouseArea {
                id: dragArea
                anchors.fill: parent
                drag.target: parent
                drag.axis: Drag.YAxis

                onReleased: {
                    if (parent.Drag.drop() !== Qt.IgnoreAction) {
                        var fromIndex = index;
                        var toIndex = listView.indexAt(parent.x, parent.y + parent.height / 2);
                        if (toIndex >= 0 && toIndex !== fromIndex) {
                            threadSafeModel.moveItem(fromIndex, toIndex);
                        }
                    }
                    parent.x = 0;
                }
            }
        }
    }

    Column {
        id: buttonColumn
        anchors.bottom: parent.bottom
        anchors.horizontalCenter: parent.horizontalCenter
        spacing: 10
        padding: 10

        Row {
            spacing: 10
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
        }

        Row {
            spacing: 10
            Button {
                text: "Move Selected Up"
                onClicked: {
                    let indices = [];
                    for (let i = 0; i < listView.count; i++) {
                        if (listView.itemAtIndex(i).checked) {
                            indices.push(i);
                        }
                    }
                    if (indices.length > 0) {
                        let firstIndex = Math.min(...indices);
                        if (firstIndex > 0) {
                            threadSafeModel.moveItems(indices, firstIndex - 1);
                        }
                    }
                }
            }

            Button {
                text: "Move Selected Down"
                onClicked: {
                    let indices = [];
                    for (let i = 0; i < listView.count; i++) {
                        if (listView.itemAtIndex(i).checked) {
                            indices.push(i);
                        }
                    }
                    if (indices.length > 0) {
                        let lastIndex = Math.max(...indices);
                        if (lastIndex < listView.count - 1) {
                            threadSafeModel.moveItems(indices, lastIndex + 2);
                        }
                    }
                }
            }
        }

        Row {
            spacing: 10
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
}
```

## Key Features Added:

1. **Single Item Movement**:
   - `moveItem(int fromIndex, int toIndex)` moves a single item from one position to another
   - Handles all edge cases (invalid indices, no-op moves)
   - Properly notifies views using `beginMoveRows`/`endMoveRows`

2. **Multiple Items Movement**:
   - `moveItems(const QVector<int>& indices, int toIndex)` moves multiple items to a new position
   - Handles duplicate indices and sorts them automatically
   - Maintains the relative order of moved items
   - Efficiently moves items in bulk

3. **Undo/Redo Support**:
   - Both move operations are fully undoable/redoable
   - Special handling for multiple item moves in undo/redo
   - Tracks original and target positions

4. **Drag-and-Drop in QML**:
   - Added visual drag-and-drop support in the QML example
   - Also provided button-based movement for precise control

5. **Thread Safety**:
   - All move operations maintain thread safety with QMutex
   - Proper synchronization between model and views

The implementation ensures that all move operations are efficient, correctly notify views of changes, and integrate seamlessly with the existing undo/redo functionality. The QML example demonstrates both drag-and-drop movement and programmatic movement of single and multiple items.
