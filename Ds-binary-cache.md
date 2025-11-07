Для вашей задачи с undo/redo и хранением на диске я предлагаю несколько эффективных подходов:

## 1. Бинарное хранение с инкрементальными изменениями

```cpp
struct Point {
    qreal x, y;
    int id;
    QString name;
    
    QByteArray toByteArray() const {
        QByteArray data;
        QDataStream stream(&data, QIODevice::WriteOnly);
        stream << x << y << id << name;
        return data;
    }
    
    static Point fromByteArray(const QByteArray &data) {
        Point point;
        QDataStream stream(data);
        stream >> point.x >> point.y >> point.id >> point.name;
        return point;
    }
};

class UndoRedoManager {
private:
    QString basePath;
    int currentVersion = 0;
    int maxVersion = 0;
    
public:
    UndoRedoManager(const QString &storagePath) : basePath(storagePath) {
        QDir().mkpath(basePath);
        loadStateIndex();
    }
    
    void saveState(const QList<Point> &points) {
        QString filename = QString("%1/state_%2.dat").arg(basePath).arg(currentVersion);
        
        QFile file(filename);
        if (file.open(QIODevice::WriteOnly)) {
            QDataStream stream(&file);
            
            // Записываем количество точек
            stream << static_cast<quint32>(points.size());
            
            // Записываем каждую точку
            for (const Point &point : points) {
                stream << point.x << point.y << point.id << point.name;
            }
            
            file.close();
            currentVersion++;
            maxVersion = currentVersion;
            saveStateIndex();
        }
    }
    
    QList<Point> undo() {
        if (currentVersion <= 0) return {};
        
        currentVersion--;
        return loadState(currentVersion);
    }
    
    QList<Point> redo() {
        if (currentVersion >= maxVersion) return {};
        
        currentVersion++;
        return loadState(currentVersion);
    }
    
private:
    QList<Point> loadState(int version) {
        QString filename = QString("%1/state_%2.dat").arg(basePath).arg(version);
        QList<Point> points;
        
        QFile file(filename);
        if (file.open(QIODevice::ReadOnly)) {
            QDataStream stream(&file);
            
            quint32 count;
            stream >> count;
            
            for (quint32 i = 0; i < count; ++i) {
                Point point;
                stream >> point.x >> point.y >> point.id >> point.name;
                points.append(point);
            }
            
            file.close();
        }
        
        return points;
    }
    
    void saveStateIndex() {
        QFile indexFile(basePath + "/index.dat");
        if (indexFile.open(QIODevice::WriteOnly)) {
            QDataStream stream(&indexFile);
            stream << currentVersion << maxVersion;
        }
    }
    
    void loadStateIndex() {
        QFile indexFile(basePath + "/index.dat");
        if (indexFile.open(QIODevice::ReadOnly)) {
            QDataStream stream(&indexFile);
            stream >> currentVersion >> maxVersion;
        }
    }
};
```

## 2. Оптимизированная версия с дельтами

```cpp
class OptimizedUndoRedoManager {
private:
    struct StateDelta {
        QList<Point> fullState; // Полное состояние для чекпоинтов
        QList<Point> changes;   // Изменения для дельт
        bool isDelta = false;
    };
    
    QString basePath;
    int checkpointInterval = 10; // Каждые 10 состояний - полный снимок
    
public:
    void saveState(const QList<Point> &currentState, const QList<Point> &previousState) {
        int version = getNextVersion();
        
        if (version % checkpointInterval == 0) {
            // Полное сохранение состояния
            saveFullState(version, currentState);
        } else {
            // Сохранение дельты
            saveDelta(version, currentState, previousState);
        }
    }
    
private:
    void saveFullState(int version, const QList<Point> &state) {
        QString filename = QString("%1/full_%2.dat").arg(basePath).arg(version);
        // ... сохранение как в предыдущем примере
    }
    
    void saveDelta(int version, const QList<Point> &current, const QList<Point> &previous) {
        // Находим различия между состояниями
        QList<Point> changes = findChanges(current, previous);
        
        QString filename = QString("%1/delta_%2.dat").arg(basePath).arg(version);
        // Сохраняем только изменения
    }
    
    QList<Point> findChanges(const QList<Point> &current, const QList<Point> &previous) {
        // Простая реализация - для вашего случая с 2-10 точками
        // можно сохранять всё текущее состояние как дельту
        return current;
    }
    
    QList<Point> applyDelta(const QList<Point> &base, const QList<Point> &delta) {
        // Для упрощения, дельта заменяет всё состояние
        return delta;
    }
};
```

## 3. Использование SQLite для эффективного хранения

```cpp
#include <QSqlDatabase>
#include <QSqlQuery>
#include <QSqlError>

class SQLiteUndoRedoManager {
private:
    QSqlDatabase db;
    
public:
    SQLiteUndoRedoManager(const QString &dbPath) {
        db = QSqlDatabase::addDatabase("QSQLITE", "undo_redo_connection");
        db.setDatabaseName(dbPath);
        
        if (db.open()) {
            initializeDatabase();
        }
    }
    
    void saveState(const QList<Point> &points, const QString &description = "") {
        QSqlQuery query(db);
        query.prepare(
            "INSERT INTO undo_states (description, timestamp) VALUES (?, ?)"
        );
        query.addBindValue(description);
        query.addBindValue(QDateTime::currentDateTime());
        query.exec();
        
        int stateId = query.lastInsertId().toInt();
        
        // Сохраняем точки
        query.prepare(
            "INSERT INTO points (state_id, x, y, id, name) VALUES (?, ?, ?, ?, ?)"
        );
        
        for (const Point &point : points) {
            query.addBindValue(stateId);
            query.addBindValue(point.x);
            query.addBindValue(point.y);
            query.addBindValue(point.id);
            query.addBindValue(point.name);
            query.exec();
        }
    }
    
    QList<Point> loadState(int stateId) {
        QList<Point> points;
        
        QSqlQuery query(db);
        query.prepare(
            "SELECT x, y, id, name FROM points WHERE state_id = ? ORDER BY rowid"
        );
        query.addBindValue(stateId);
        
        if (query.exec()) {
            while (query.next()) {
                Point point;
                point.x = query.value(0).toReal();
                point.y = query.value(1).toReal();
                point.id = query.value(2).toInt();
                point.name = query.value(3).toString();
                points.append(point);
            }
        }
        
        return points;
    }
    
private:
    void initializeDatabase() {
        QSqlQuery query(db);
        query.exec(
            "CREATE TABLE IF NOT EXISTS undo_states ("
            "id INTEGER PRIMARY KEY AUTOINCREMENT,"
            "description TEXT,"
            "timestamp DATETIME DEFAULT CURRENT_TIMESTAMP)"
        );
        
        query.exec(
            "CREATE TABLE IF NOT EXISTS points ("
            "state_id INTEGER,"
            "x REAL, y REAL,"
            "id INTEGER,"
            "name TEXT,"
            "FOREIGN KEY(state_id) REFERENCES undo_states(id))"
        );
        
        query.exec("CREATE INDEX IF NOT EXISTS idx_points_state ON points(state_id)");
    }
};
```

## Рекомендации

1. **Для максимальной простоты и скорости** - используйте первый подход с бинарным хранением
2. **Для экономии места** - используйте второй подход с дельтами
3. **Для надежности и возможностей поиска** - используйте SQLite

Учитывая, что у вас всего 2-10 точек, **первый подход наиболее предпочтителен** - он прост в реализации и будет достаточно быстрым даже для сотен состояний undo/redo.

Оптимизации которые можно добавить:
- Сжатие данных с qCompress()
- Кэширование последних состояний в памяти
- Фоновая загрузка состояний для предсказания действий пользователя
