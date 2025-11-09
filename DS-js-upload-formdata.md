JavaScript:

```javascript
/**
 * Отправляет данные на сервер
 * @param {string} text - Текст для отправки
 * @param {string} url - URL сервера для отправки
 * @param {string} imgId - ID тега img для получения base64 данных
 */
function sendDataToServer(text, url, imgId) {
    // Получаем элемент изображения по ID
    const imgElement = document.getElementById(imgId);
    
    if (!imgElement) {
        console.error(`Элемент с ID "${imgId}" не найден`);
        return;
    }
    
    // Получаем src атрибут (base64 данные)
    const base64Data = imgElement.getAttribute('src');
    
    if (!base64Data) {
        console.error(`Атрибут src у элемента с ID "${imgId}" не найден или пуст`);
        return;
    }
    
    // Создаем объект FormData
    const formData = new FormData();
    
    // Добавляем текст
    formData.append('text', text);
    
    // Добавляем изображение как файл
    // Преобразуем base64 в blob
    try {
        const blob = base64ToBlob(base64Data);
        formData.append('image', blob, 'image.png');
    } catch (error) {
        console.error('Ошибка при преобразовании base64 в blob:', error);
        return;
    }
    
    // Отправляем данные на сервер
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Данные успешно отправлены:', data);
    })
    .catch(error => {
        console.error('Ошибка при отправке данных:', error);
    });
}

/**
 * Преобразует base64 строку в Blob объект
 * @param {string} base64Data - Base64 строка с данными изображения
 * @returns {Blob} - Blob объект
 */
function base64ToBlob(base64Data) {
    // Проверяем формат base64 строки
    const matches = base64Data.match(/^data:(.+);base64,(.+)$/);
    
    if (!matches || matches.length !== 3) {
        throw new Error('Некорректный формат base64 данных');
    }
    
    const mimeType = matches[1];
    const base64String = matches[2];
    
    // Декодируем base64 строку
    const byteCharacters = atob(base64String);
    const byteArrays = [];
    
    for (let offset = 0; offset < byteCharacters.length; offset += 512) {
        const slice = byteCharacters.slice(offset, offset + 512);
        
        const byteNumbers = new Array(slice.length);
        for (let i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }
        
        const byteArray = new Uint8Array(byteNumbers);
        byteArrays.push(byteArray);
    }
    
    return new Blob(byteArrays, { type: mimeType });
}

// Пример использования:
// sendDataToServer('Привет, мир!', '/api/upload', 'myImage');
```

**Альтернативная версия с более простой обработкой base64:**

```javascript
/**
 * Упрощенная версия отправки данных
 * @param {string} text - Текст для отправки
 * @param {string} url - URL сервера для отправки
 * @param {string} imgId - ID тега img
 */
function sendDataSimple(text, url, imgId) {
    const imgElement = document.getElementById(imgId);
    
    if (!imgElement || !imgElement.src) {
        console.error('Изображение не найдено');
        return;
    }
    
    const formData = new FormData();
    formData.append('text', text);
    formData.append('imageData', imgElement.src); // Отправляем base64 как строку
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => console.log('Успех:', data))
    .catch(error => console.error('Ошибка:', error));
}
```

**Пример HTML для тестирования:**

```html
<!DOCTYPE html>
<html>
<head>
    <title>Пример отправки данных</title>
</head>
<body>
    <img id="myImage" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==" alt="Пример изображения">
    
    <button onclick="testSend()">Отправить данные</button>

    <script>
        function testSend() {
            // Пример вызова функции
            sendDataToServer(
                'Это тестовый текст',
                'https://your-server.com/api/upload',
                'myImage'
            );
        }
        
        // Вставьте здесь функции sendDataToServer и base64ToBlob
    </script>
</body>
</html>
```

**Ключевые особенности кода:**

1. **Валидация**: Проверка существования элемента и данных
2. **Обработка ошибок**: Подробные сообщения об ошибках
3. **Преобразование base64**: Функция `base64ToBlob` корректно обрабатывает base64 данные
4. **FormData**: Использование современного API для отправки данных
5. **Fetch API**: Асинхронная отправка данных на сервер

Выберите первую версию, если нужно отправлять изображение как файл, или вторую упрощенную версию, если достаточно отправить base64 строку как есть.
