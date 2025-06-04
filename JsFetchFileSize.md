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


Чтобы вычислить **MD5-хэш** файла, скачанного по HTTP в JavaScript, можно использовать нативную реализацию или библиотеки (например, `crypto-js` или встроенный `SubtleCrypto` API в браузере).  

### 🔹 **Способ 1: Использование `crypto-js` (простой способ)**
Установите библиотеку:
```bash
npm install crypto-js
```
Или подключите через CDN:
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
```

**Пример кода:**
```javascript
async function downloadAndCalculateMD5(url) {
  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    
    const blob = await response.blob();
    const arrayBuffer = await blob.arrayBuffer();
    const wordArray = CryptoJS.lib.WordArray.create(arrayBuffer);
    
    const md5Hash = CryptoJS.MD5(wordArray).toString();
    console.log('MD5:', md5Hash);
    return md5Hash;
  } catch (error) {
    console.error('Error:', error);
    return null;
  }
}

// Пример использования
downloadAndCalculateMD5('https://example.com/file.zip');
```

### 🔹 **Способ 2: Использование встроенного `SubtleCrypto` (без библиотек)**
Браузерный API `SubtleCrypto` поддерживает SHA-1, SHA-256, SHA-512, но **не поддерживает MD5** (из-за его небезопасности).  
Если вам **обязательно нужен MD5**, лучше использовать `crypto-js`.  

**Пример с `SubtleCrypto` (SHA-256):**
```javascript
async function calculateSHA256(arrayBuffer) {
  const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

async function downloadAndCalculateHash(url) {
  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    
    const blob = await response.blob();
    const arrayBuffer = await blob.arrayBuffer();
    const sha256Hash = await calculateSHA256(arrayBuffer);
    
    console.log('SHA-256:', sha256Hash);
    return sha256Hash;
  } catch (error) {
    console.error('Error:', error);
    return null;
  }
}

// Пример использования
downloadAndCalculateHash('https://example.com/file.zip');
```

### 🔹 **Способ 3: Чтение файла чанками (для больших файлов)**
Если файл очень большой, можно вычислять хэш по частям, чтобы не загружать его целиком в память:
```javascript
async function calculateMD5InChunks(url, chunkSize = 1024 * 1024) {
  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const reader = response.body.getReader();
    let md5 = CryptoJS.algo.MD5.create();
    
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      
      const chunkWordArray = CryptoJS.lib.WordArray.create(value);
      md5.update(chunkWordArray);
    }
    
    const md5Hash = md5.finalize().toString();
    console.log('MD5:', md5Hash);
    return md5Hash;
  } catch (error) {
    console.error('Error:', error);
    return null;
  }
}

// Пример использования
calculateMD5InChunks('https://example.com/large-file.zip');
```

### ⚠️ **Важно:**
1. **MD5 считается криптографически небезопасным**, но подходит для проверки целостности файлов.
2. **CORS**: Убедитесь, что сервер разрешает запросы с вашего домена (`Access-Control-Allow-Origin`).
3. **Размер файла**: Для больших файлов лучше использовать **поточное чтение (чанки)**.

Если вам нужен именно **MD5**, используйте `crypto-js`. Если можно использовать более современные хэши (SHA-256), лучше выбрать `SubtleCrypto`.
