Чтобы получить размер скачиваемого файла до его загрузки с помощью JavaScript, вы можете использовать HTTP-запрос `HEAD` вместо `GET`. Запрос `HEAD` возвращает только заголовки ответа без тела файла, что позволяет узнать метаданные (включая размер) без загрузки самого содержимого.

### Пример реализации:

```javascript
async function getFileSize(url) {
  try {
    const response = await fetch(url, { method: 'HEAD' });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const contentLength = response.headers.get('Content-Length');
    
    if (contentLength === null) {
      throw new Error('Content-Length header is missing');
    }
    
    return parseInt(contentLength, 10);
  } catch (error) {
    console.error('Error fetching file size:', error);
    return null;
  }
}

// Использование
getFileSize('https://example.com/file.zip')
  .then(size => {
    if (size !== null) {
      console.log(`File size is: ${size} bytes`);
      console.log(`Approx. ${(size / 1024 / 1024).toFixed(2)} MB`);
    }
  });
```

### Важные нюансы:
1. **Сервер должен поддерживать HEAD-запросы** (большинство серверов поддерживают).
2. **Заголовок `Content-Length` должен присутствовать** в ответе. Если сервер использует chunked-передачу, заголовок может отсутствовать.
3. **CORS**: Для кросс-доменных запросов сервер должен отправить правильные CORS-заголовки (`Access-Control-Allow-Origin` и др.).
4. **Реальные размеры**: Для сжатых файлов (например, `.gz`) `Content-Length` может не соответствовать итоговому размеру после распаковки.

### Альтернативный подход (если HEAD не работает):
Если сервер не поддерживает `HEAD`, можно сделать обычный `GET`, но с отменой запроса после получения заголовков:

```javascript
const controller = new AbortController();
const signal = controller.signal;

fetch(url, { signal })
  .then(response => {
    controller.abort(); // Отменяем загрузку тела
    const size = response.headers.get('Content-Length');
    console.log('File size:', size);
  });
```

Этот метод менее эффективен, так как всё равно инициирует загрузку (хотя и прерывает её сразу).
