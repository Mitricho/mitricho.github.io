Для эффективного поиска статических кадров без полной итерации по всему списку можно использовать несколько подходов:

## 1. Ленивая проверка (Lazy Evaluation)

```cpp
class FrameModel : public QAbstractListModel
{
private:
    QList<Frame> m_frames;
    mutable QHash<int, bool> m_staticCache; // Кэш для статических кадров
    
    bool isStaticFrame(int index) const {
        if (m_staticCache.contains(index)) {
            return m_staticCache[index];
        }
        
        bool isStatic = false;
        if (index > 0) {
            isStatic = (m_frames[index] == m_frames[index - 1]);
        }
        if (!isStatic && index < m_frames.size() - 1) {
            isStatic = (m_frames[index] == m_frames[index + 1]);
        }
        
        m_staticCache[index] = isStatic;
        return isStatic;
    }
    
public:
    void invalidateCache(int from = -1) {
        if (from == -1) {
            m_staticCache.clear();
        } else {
            // Удаляем кэш начиная с измененной позиции
            auto it = m_staticCache.begin();
            while (it != m_staticCache.end()) {
                if (it.key() >= from) {
                    it = m_staticCache.erase(it);
                } else {
                    ++it;
                }
            }
        }
    }
};
```

## 2. Инкрементальное обновление

```cpp
class FrameModel : public QAbstractListModel
{
private:
    QList<Frame> m_frames;
    QBitArray m_staticFrames; // Битовая маска статических кадров
    
    void updateStaticFlags(int changedIndex) {
        // Обновляем только измененный кадр и соседние
        for (int i = qMax(0, changedIndex - 1); 
             i <= qMin(m_frames.size() - 1, changedIndex + 1); ++i) {
            updateSingleStaticFlag(i);
        }
    }
    
    void updateSingleStaticFlag(int index) {
        bool isStatic = false;
        if (index > 0) {
            isStatic = (m_frames[index] == m_frames[index - 1]);
        }
        if (!isStatic && index < m_frames.size() - 1) {
            isStatic = (m_frames[index] == m_frames[index + 1]);
        }
        
        if (m_staticFrames.size() <= index) {
            m_staticFrames.resize(m_frames.size());
        }
        m_staticFrames.setBit(index, isStatic);
    }
    
public:
    // При изменении данных
    void framesChanged(int start, int end) {
        for (int i = start; i <= end; ++i) {
            updateStaticFlags(i);
        }
        emit dataChanged(/* ... */);
    }
};
```

## 3. Фоновая обработка

```cpp
class FrameModel : public QAbstractListModel
{
private:
    QList<Frame> m_frames;
    QBitArray m_staticFrames;
    QFuture<void> m_processingFuture;
    
    void startBackgroundProcessing() {
        if (m_processingFuture.isRunning()) {
            m_processingFuture.cancel();
        }
        
        m_processingFuture = QtConcurrent::run([this]() {
            processStaticFrames();
        });
    }
    
    void processStaticFrames() {
        QBitArray newStaticFrames(m_frames.size());
        
        for (int i = 0; i < m_frames.size(); ++i) {
            if (QThread::currentThread()->isInterruptionRequested()) {
                return;
            }
            
            bool isStatic = false;
            if (i > 0) isStatic = (m_frames[i] == m_frames[i - 1]);
            if (!isStatic && i < m_frames.size() - 1) {
                isStatic = (m_frames[i] == m_frames[i + 1]);
            }
            newStaticFrames.setBit(i, isStatic);
            
            // Периодически обновляем прогресс
            if (i % 1000 == 0) {
                QMetaObject::invokeMethod(this, [this, i, newStaticFrames]() {
                    m_staticFrames = newStaticFrames;
                    emit staticFramesProcessed(i);
                });
            }
        }
        
        QMetaObject::invokeMethod(this, [this, newStaticFrames]() {
            m_staticFrames = newStaticFrames;
            emit staticFramesProcessingFinished();
        });
    }
};
```

## 4. Оптимизированный подход для больших данных

```cpp
class FrameModel : public QAbstractListModel
{
private:
    struct FrameGroup {
        int startIndex;
        int count;
        Frame frame;
    };
    
    QList<Frame> m_frames;
    QVector<FrameGroup> m_frameGroups;
    
    void buildFrameGroups() {
        m_frameGroups.clear();
        if (m_frames.isEmpty()) return;
        
        FrameGroup currentGroup{0, 1, m_frames[0]};
        
        for (int i = 1; i < m_frames.size(); ++i) {
            if (m_frames[i] == currentGroup.frame) {
                currentGroup.count++;
            } else {
                m_frameGroups.append(currentGroup);
                currentGroup = {i, 1, m_frames[i]};
            }
        }
        m_frameGroups.append(currentGroup);
    }
    
    bool isStaticFrame(int index) const {
        // Бинарный поиск по группам
        auto it = std::upper_bound(m_frameGroups.begin(), m_frameGroups.end(), 
                                  index, [](int idx, const FrameGroup& group) {
            return idx < group.startIndex + group.count - 1;
        });
        
        if (it != m_frameGroups.end()) {
            return it->count > 1; // Группа из одного кадра не статична
        }
        return false;
    }
};
```

## Рекомендации:

1. **Для частых обновлений** - используйте инкрементальное обновление
2. **Для редких изменений** - используйте фоновую обработку  
3. **Для очень больших данных** - используйте группировку кадров
4. **Для экономии памяти** - используйте битовые массивы вместо хэш-таблиц

Выбор подхода зависит от конкретных требований к производительности и частоте изменений данных в вашей модели.
