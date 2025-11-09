Вот код для Qt 6.9, который отслеживает самые левый и правый изменившиеся индексы:

```cpp
#include <QAbstractListModel>
#include <QList>
#include <QDebug>

class MyListModel : public QAbstractListModel
{
    Q_OBJECT

private:
    QList<QVariant> m_data;
    int m_minChangedIndex = -1;
    int m_maxChangedIndex = -1;
    bool m_trackingChanges = false;

public:
    explicit MyListModel(QObject *parent = nullptr) : QAbstractListModel(parent) {}

    // Базовые методы модели
    int rowCount(const QModelIndex &parent = QModelIndex()) const override
    {
        Q_UNUSED(parent)
        return m_data.size();
    }

    QVariant data(const QModelIndex &index, int role = Qt::DisplayRole) const override
    {
        if (!index.isValid() || index.row() >= m_data.size())
            return QVariant();

        if (role == Qt::DisplayRole || role == Qt::EditRole)
            return m_data.at(index.row());

        return QVariant();
    }

    bool setData(const QModelIndex &index, const QVariant &value, int role = Qt::EditRole) override
    {
        if (!index.isValid() || index.row() >= m_data.size() || role != Qt::EditRole)
            return false;

        if (m_data.at(index.row()) != value) {
            m_data[index.row()] = value;
            
            // Обновляем границы изменений
            updateChangedRange(index.row());
            
            emit dataChanged(index, index, {role});
            return true;
        }
        return false;
    }

    Qt::ItemFlags flags(const QModelIndex &index) const override
    {
        if (!index.isValid())
            return Qt::NoItemFlags;
        return Qt::ItemIsEnabled | Qt::ItemIsSelectable | Qt::ItemIsEditable;
    }

    // Методы для работы с данными
    void append(const QVariant &value)
    {
        beginInsertRows(QModelIndex(), m_data.size(), m_data.size());
        m_data.append(value);
        endInsertRows();
        
        // Для вставки тоже отслеживаем изменения
        updateChangedRange(m_data.size() - 1);
    }

    void insert(int row, const QVariant &value)
    {
        if (row < 0 || row > m_data.size())
            return;

        beginInsertRows(QModelIndex(), row, row);
        m_data.insert(row, value);
        endInsertRows();
        
        updateChangedRange(row);
    }

    void remove(int row)
    {
        if (row < 0 || row >= m_data.size())
            return;

        beginRemoveRows(QModelIndex(), row, row);
        m_data.removeAt(row);
        endRemoveRows();
        
        // Для удаления тоже обновляем границы
        updateChangedRange(row);
    }

    void setDataList(const QList<QVariant> &newData)
    {
        beginResetModel();
        m_data = newData;
        endResetModel();
        
        // При полной замене данных сбрасываем отслеживание
        resetChangeTracking();
    }

    // Методы для отслеживания изменений
    void startTrackingChanges()
    {
        m_trackingChanges = true;
        resetChangeTracking();
    }

    void stopTrackingChanges()
    {
        m_trackingChanges = false;
        resetChangeTracking();
    }

    void resetChangeTracking()
    {
        m_minChangedIndex = -1;
        m_maxChangedIndex = -1;
    }

    QPair<int, int> getChangedRange() const
    {
        return qMakePair(m_minChangedIndex, m_maxChangedIndex);
    }

    bool hasChanges() const
    {
        return m_minChangedIndex != -1 && m_maxChangedIndex != -1;
    }

    void printChangedRange() const
    {
        if (hasChanges()) {
            qDebug() << "Changed range: [" << m_minChangedIndex << ", " << m_maxChangedIndex << "]";
        } else {
            qDebug() << "No changes tracked";
        }
    }

private:
    void updateChangedRange(int changedIndex)
    {
        if (!m_trackingChanges)
            return;

        if (m_minChangedIndex == -1 || changedIndex < m_minChangedIndex) {
            m_minChangedIndex = changedIndex;
        }
        
        if (m_maxChangedIndex == -1 || changedIndex > m_maxChangedIndex) {
            m_maxChangedIndex = changedIndex;
        }
        
        qDebug() << "Updated change range. Min:" << m_minChangedIndex << "Max:" << m_maxChangedIndex;
    }

    // Переопределяем методы для массовых операций
public:
    bool insertRows(int row, int count, const QModelIndex &parent = QModelIndex()) override
    {
        if (row < 0 || row > m_data.size() || count <= 0)
            return false;

        beginInsertRows(parent, row, row + count - 1);
        for (int i = 0; i < count; ++i) {
            m_data.insert(row + i, QVariant());
        }
        endInsertRows();
        
        // Для массовой вставки отслеживаем весь диапазон
        if (m_trackingChanges) {
            updateChangedRange(row);
            updateChangedRange(row + count - 1);
        }
        
        return true;
    }

    bool removeRows(int row, int count, const QModelIndex &parent = QModelIndex()) override
    {
        if (row < 0 || row + count > m_data.size() || count <= 0)
            return false;

        beginRemoveRows(parent, row, row + count - 1);
        for (int i = 0; i < count; ++i) {
            m_data.removeAt(row);
        }
        endRemoveRows();
        
        // Для массового удаления тоже отслеживаем
        if (m_trackingChanges) {
            updateChangedRange(row);
        }
        
        return true;
    }
};
```

**Пример использования:**

```cpp
// Создание и использование модели
MyListModel model;

// Включаем отслеживание изменений
model.startTrackingChanges();

// Добавляем данные
model.append("Item 1");
model.append("Item 2");
model.append("Item 3");

// Изменяем отдельные элементы
QModelIndex index1 = model.index(0);
model.setData(index1, "Modified Item 1");

QModelIndex index3 = model.index(2);
model.setData(index3, "Modified Item 3");

// Получаем диапазон изменений
auto range = model.getChangedRange();
qDebug() << "Final changed range:" << range.first << "to" << range.second;
// Выведет: Final changed range: 0 to 2

// Выводим информацию
model.printChangedRange();

// Сбрасываем отслеживание
model.resetChangeTracking();
```

**Ключевые особенности:**

1. **Отслеживание границ**: `m_minChangedIndex` и `m_maxChangedIndex` хранят минимальный и максимальный измененные индексы.

2. **Контроль отслеживания**: Методы `startTrackingChanges()` и `stopTrackingChanges()` позволяют включать/выключать отслеживание.

3. **Обработка всех операций**: Код обрабатывает изменения через `setData()`, вставку, удаление и массовые операции.

4. **Сброс состояния**: `resetChangeTracking()` сбрасывает отслеживаемый диапазон.

5. **Qt 6.9 совместимость**: Использует актуальный API Qt 6.9.

6. **Гибкость**: Можно легко расширить для отслеживания конкретных ролей или типов изменений.

Этот код будет корректно работать в Qt 6.9 и предоставлять информацию о самом левом и самом правом измененных индексах при каждом изменении модели.
