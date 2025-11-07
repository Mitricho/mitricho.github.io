В QML есть несколько способов сохранить и восстановить позицию прокрутки в ListView. Вот основные подходы:

## 1. Использование свойства `currentIndex` и привязки к модели

```qml
ListView {
    id: listView
    orientation: ListView.Horizontal
    model: yourModel
    
    // Сохраняем текущий индекс
    property int savedIndex: 0
    
    // При изменении модели восстанавливаем позицию
    onModelChanged: {
        if (model && model.count > 0) {
            positionViewAtIndex(savedIndex, ListView.Center)
        }
    }
    
    // Запоминаем индекс при прокрутке
    onMovementEnded: {
        savedIndex = indexAt(contentX + width / 2, contentY + height / 2)
    }
    
    // Или при клике на элемент
    onClicked: {
        savedIndex = index
    }
}
```

## 2. Сохранение позиции в настройках приложения

```qml
ListView {
    id: listView
    orientation: ListView.Horizontal
    model: yourModel
    
    // Восстанавливаем позицию после загрузки модели
    Component.onCompleted: {
        if (model && model.count > 0) {
            var savedIndex = Qt.application.settings.value("listViewIndex", 0)
            positionViewAtIndex(savedIndex, ListView.Center)
            currentIndex = savedIndex
        }
    }
    
    // Сохраняем позицию при изменении
    onCurrentIndexChanged: {
        Qt.application.settings.setValue("listViewIndex", currentIndex)
    }
    
    // Или при завершении прокрутки
    onMovementEnded: {
        var centerIndex = indexAt(contentX + width / 2, contentY + height / 2)
        if (centerIndex !== -1) {
            Qt.application.settings.setValue("listViewIndex", centerIndex)
        }
    }
}
```

## 3. Использование делегата с запоминанием состояния

```qml
ListView {
    id: listView
    orientation: ListView.Horizontal
    model: yourModel
    cacheBuffer: 1000 // Увеличиваем кеш для плавности
    
    // Сохраняем и восстанавливаем contentX
    property real savedContentX: 0
    
    onModelChanged: {
        if (model) {
            // Даем время на обновление модели
            Qt.callLater(function() {
                contentX = savedContentX
            })
        }
    }
    
    onContentXChanged: {
        if (!moving) { // Сохраняем только когда прокрутка завершена
            savedContentX = contentX
        }
    }
    
    // Альтернативно - сохраняем при завершении движения
    onMovementEnded: {
        savedContentX = contentX
    }
}
```

## 4. Полный пример с надежным сохранением позиции

```qml
ListView {
    id: listView
    orientation: ListView.Horizontal
    model: yourModel
    spacing: 10
    
    property real lastContentX: 0
    property int lastValidIndex: 0
    
    // Восстановление позиции после обновления модели
    Connections {
        target: listView.model
        function onCountChanged() {
            if (listView.model.count > 0) {
                // Ждем завершения обновления layout
                Qt.callLater(function() {
                    if (lastValidIndex < listView.model.count) {
                        listView.positionViewAtIndex(lastValidIndex, ListView.Center)
                    } else if (listView.model.count > 0) {
                        listView.positionViewAtIndex(listView.model.count - 1, ListView.Center)
                    }
                })
            }
        }
    }
    
    // Запоминание позиции
    onMovementEnded: {
        var centerX = contentX + width / 2
        var centerIndex = indexAt(centerX, height / 2)
        
        if (centerIndex !== -1) {
            lastValidIndex = centerIndex
            lastContentX = contentX
        }
    }
    
    // Также сохраняем при ручном выборе элемента
    onClicked: {
        lastValidIndex = index
        lastContentX = contentX
    }
    
    // Инициализация
    Component.onCompleted: {
        if (model && model.count > 0) {
            positionViewAtIndex(lastValidIndex, ListView.Center)
        }
    }
}
```

## 5. Для динамически загружаемых моделей

```qml
ListView {
    id: listView
    orientation: ListView.Horizontal
    model: yourModel
    
    property bool isModelReady: false
    property int pendingIndex: -1
    
    // Ждем полной загрузки модели
    Connections {
        target: listView.model
        function onModelReset() {
            isModelReady = false
            // Даем время на обновление
            Qt.callLater(function() {
                isModelReady = true
                if (pendingIndex !== -1) {
                    positionViewAtIndex(pendingIndex, ListView.Center)
                    pendingIndex = -1
                }
            })
        }
    }
    
    function savePosition() {
        var index = indexAt(contentX + width / 2, contentY + height / 2)
        if (index !== -1) {
            if (isModelReady) {
                positionViewAtIndex(index, ListView.Center)
            } else {
                pendingIndex = index
            }
        }
    }
    
    onMovementEnded: savePosition()
    onClicked: savePosition()
}
```

**Рекомендации:**
- Используйте `Qt.callLater()` для отложенного выполнения после обновления модели
- Проверяйте границы индексов перед восстановлением
- Для больших списков лучше сохранять `contentX`, а не индекс
- Используйте `cacheBuffer` для улучшения производительности при прокрутке

Выберите подход, который лучше подходит для вашего конкретного случая использования.
