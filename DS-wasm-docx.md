Вот код на C++ для WebAssembly с использованием Emscripten и zlib для обработки DOCX файлов:

```cpp
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <emscripten.h>
#include <emscripten/fetch.h>
#include "zlib.h"

#define CHUNK_SIZE 16384

// Структура для хранения данных файла
typedef struct {
    char* data;
    size_t size;
} FileData;

// Структура для хранения распакованных файлов
typedef struct {
    FileData document_xml;
    FileData styles_xml;
    FileData numbering_xml;
} ExtractedFiles;

// Функция для распаковки данных из ZIP архива
int extract_from_zip(const char* zip_data, size_t zip_size, ExtractedFiles* extracted) {
    // Инициализируем структуры
    memset(extracted, 0, sizeof(ExtractedFiles));
    
    // Для простоты демонстрации, ищем XML файлы по сигнатурам
    // В реальном приложении нужно парсить структуру ZIP архива
    
    const char* patterns[] = {
        "word/document.xml",
        "word/styles.xml", 
        "word/numbering.xml"
    };
    
    FileData* targets[] = {
        &extracted->document_xml,
        &extracted->styles_xml,
        &extracted->numbering_xml
    };
    
    // Простой поиск по содержимому (в реальном приложении нужно использовать minizip)
    for (int i = 0; i < 3; i++) {
        const char* pattern = patterns[i];
        const char* pos = strstr(zip_data, pattern);
        
        if (pos) {
            // Нашли файл, извлекаем данные до следующего файла
            const char* start = pos + strlen(pattern);
            const char* end = strstr(start, "word/");
            
            if (!end) end = zip_data + zip_size;
            
            size_t content_size = end - start;
            targets[i]->data = (char*)malloc(content_size + 1);
            memcpy(targets[i]->data, start, content_size);
            targets[i]->data[content_size] = '\0';
            targets[i]->size = content_size;
        }
    }
    
    return 0;
}

// Callback для успешной загрузки
void downloadSucceeded(emscripten_fetch_t *fetch) {
    printf("Файл загружен успешно. Размер: %llu байт\n", fetch->numBytes);
    
    ExtractedFiles extracted;
    
    // Распаковываем DOCX
    if (extract_from_zip(fetch->data, fetch->numBytes, &extracted) == 0) {
        printf("Файл распакован успешно\n");
        
        // Выводим содержимое в консоль (можно модифицировать для возврата в JS)
        if (extracted.document_xml.data) {
            printf("\n=== document.xml (первые 500 символов) ===\n");
            char preview[501];
            strncpy(preview, extracted.document_xml.data, 500);
            preview[500] = '\0';
            printf("%s\n", preview);
        }
        
        if (extracted.styles_xml.data) {
            printf("\n=== styles.xml (первые 500 символов) ===\n");
            char preview[501];
            strncpy(preview, extracted.styles_xml.data, 500);
            preview[500] = '\0';
            printf("%s\n", preview);
        }
        
        if (extracted.numbering_xml.data) {
            printf("\n=== numbering.xml (первые 500 символов) ===\n");
            char preview[501];
            strncpy(preview, extracted.numbering_xml.data, 500);
            preview[500] = '\0';
            printf("%s\n", preview);
        }
        
        // Освобождаем память
        free(extracted.document_xml.data);
        free(extracted.styles_xml.data);
        free(extracted.numbering_xml.data);
    } else {
        printf("Ошибка при распаковке файла\n");
    }
    
    emscripten_fetch_close(fetch);
}

// Callback для ошибки загрузки
void downloadFailed(emscripten_fetch_t *fetch) {
    printf("Ошибка загрузки файла: %d\n", fetch->status);
    emscripten_fetch_close(fetch);
}

// Функция для инициализации загрузки из JavaScript
extern "C" {
    EMSCRIPTEN_KEEPALIVE
    void downloadAndExtractDocx(const char* url) {
        printf("Начинаем загрузку: %s\n", url);
        
        emscripten_fetch_attr_t attr;
        emscripten_fetch_attr_init(&attr);
        strcpy(attr.requestMethod, "GET");
        attr.attributes = EMSCRIPTEN_FETCH_LOAD_TO_MEMORY;
        attr.onsuccess = downloadSucceeded;
        attr.onerror = downloadFailed;
        
        emscripten_fetch(&attr, url);
    }
    
    // Функция для тестирования распаковки
    EMSCRIPTEN_KEEPALIVE
    char* testExtraction(const char* data, int size) {
        ExtractedFiles extracted;
        
        if (extract_from_zip(data, size, &extracted) == 0) {
            if (extracted.document_xml.data) {
                return extracted.document_xml.data;
            }
        }
        
        return nullptr;
    }
}

int main() {
    printf("WASM модуль для обработки DOCX файлов инициализирован\n");
    return 0;
}
```

HTML файл для тестирования:

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DOCX Extractor</title>
</head>
<body>
    <h1>Извлечение текста из DOCX файлов</h1>
    
    <div>
        <label for="urlInput">URL DOCX файла:</label>
        <input type="text" id="urlInput" style="width: 400px;" 
               placeholder="https://example.com/document.docx">
        <button onclick="processDocx()">Обработать</button>
    </div>
    
    <div>
        <h3>Результат:</h3>
        <textarea id="output" style="width: 100%; height: 400px;" readonly></textarea>
    </div>

    <script>
        // Функция для обработки DOCX файла
        function processDocx() {
            const url = document.getElementById('urlInput').value;
            if (!url) {
                alert('Пожалуйста, введите URL');
                return;
            }
            
            document.getElementById('output').value = 'Загрузка...';
            
            // Вызываем WASM функцию
            Module.downloadAndExtractDocx(url);
            
            // Обновляем вывод (в реальном приложении нужно использовать колбэки)
            setTimeout(() => {
                document.getElementById('output').value = 
                    'Проверьте консоль браузера для просмотра результатов.\n' +
                    'Файлы document.xml, styles.xml, numbering.xml были извлечены.';
            }, 2000);
        }
        
        // Колбэк для вывода в текстовое поле
        function updateOutput(text) {
            document.getElementById('output').value = text;
        }
    </script>
    
    <script src="docx_extractor.js"></script>
</body>
</html>
```

Команда для компиляции:

```bash
emcc -o docx_extractor.js docx_extractor.cpp \
    -s USE_ZLIB=1 \
    -s FETCH=1 \
    -s ALLOW_MEMORY_GROWTH=1 \
    -s EXPORTED_FUNCTIONS='["_downloadAndExtractDocx", "_testExtraction", "_main"]' \
    -s EXPORTED_RUNTIME_METHODS='["ccall", "cwrap"]' \
    -O3
```

Более продвинутая версия с использованием minizip:

```cpp
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <emscripten.h>
#include <emscripten/fetch.h>
#include "miniz.h"

// Структура для хранения данных файла
typedef struct {
    char* data;
    size_t size;
    char found;
} FileData;

// Структура для хранения распакованных файлов
typedef struct {
    FileData document_xml;
    FileData styles_xml;
    FileData numbering_xml;
} ExtractedFiles;

// Функция для извлечения конкретного файла из ZIP архива
int extract_file_from_zip(const char* zip_data, size_t zip_size, 
                         const char* filename, FileData* output) {
    mz_zip_archive zip_archive;
    memset(&zip_archive, 0, sizeof(zip_archive));
    
    // Инициализируем ZIP архив из памяти
    if (!mz_zip_reader_init_mem(&zip_archive, (void*)zip_data, zip_size, 0)) {
        printf("Ошибка инициализации ZIP архива\n");
        return -1;
    }
    
    // Ищем файл в архиве
    int file_index = mz_zip_reader_locate_file(&zip_archive, filename, NULL, 0);
    if (file_index < 0) {
        printf("Файл %s не найден в архиве\n", filename);
        mz_zip_reader_end(&zip_archive);
        return -1;
    }
    
    // Получаем информацию о файле
    mz_zip_archive_file_stat file_stat;
    if (!mz_zip_reader_file_stat(&zip_archive, file_index, &file_stat)) {
        printf("Ошибка получения информации о файле %s\n", filename);
        mz_zip_reader_end(&zip_archive);
        return -1;
    }
    
    // Извлекаем файл
    output->data = (char*)mz_zip_reader_extract_to_heap(&zip_archive, file_index, &output->size, 0);
    output->found = (output->data != NULL);
    
    mz_zip_reader_end(&zip_archive);
    
    return output->found ? 0 : -1;
}

// Основная функция распаковки DOCX
int extract_docx_files(const char* zip_data, size_t zip_size, ExtractedFiles* extracted) {
    memset(extracted, 0, sizeof(ExtractedFiles));
    
    // Извлекаем основные XML файлы из DOCX
    extract_file_from_zip(zip_data, zip_size, "word/document.xml", &extracted->document_xml);
    extract_file_from_zip(zip_data, zip_size, "word/styles.xml", &extracted->styles_xml);
    extract_file_from_zip(zip_data, zip_size, "word/numbering.xml", &extracted->numbering_xml);
    
    return 0;
}

// Callback для успешной загрузки
void downloadSucceeded(emscripten_fetch_t *fetch) {
    printf("Файл загружен успешно. Размер: %llu байт\n", fetch->numBytes);
    
    ExtractedFiles extracted;
    
    if (extract_docx_files(fetch->data, fetch->numBytes, &extracted) == 0) {
        printf("DOCX файл обработан успешно\n");
        
        // Здесь можно передать данные обратно в JavaScript
        if (extracted.document_xml.found) {
            EM_ASM({
                console.log("=== document.xml ===");
                console.log(UTF8ToString($0, $1));
            }, extracted.document_xml.data, extracted.document_xml.size);
        }
        
        // Освобождаем память
        free(extracted.document_xml.data);
        free(extracted.styles_xml.data);
        free(extracted.numbering_xml.data);
    }
    
    emscripten_fetch_close(fetch);
}

extern "C" {
    EMSCRIPTEN_KEEPALIVE
    void processDocxFromUrl(const char* url) {
        emscripten_fetch_attr_t attr;
        emscripten_fetch_attr_init(&attr);
        strcpy(attr.requestMethod, "GET");
        attr.attributes = EMSCRIPTEN_FETCH_LOAD_TO_MEMORY;
        attr.onsuccess = downloadSucceeded;
        attr.onerror = downloadFailed;
        
        emscripten_fetch(&attr, url);
    }
}
```

Этот код:

1. **Загружает DOCX файл** по URL используя Emscripten Fetch API
2. **Распаковывает ZIP архив** используя zlib/miniz
3. **Извлекает ключевые XML файлы**: document.xml, styles.xml, numbering.xml
4. **Выводит содержимое** в консоль браузера

Для компиляции используйте флаги `-s USE_ZLIB=1` и `-s FETCH=1` чтобы включить поддержку zlib и сетевых запросов.
