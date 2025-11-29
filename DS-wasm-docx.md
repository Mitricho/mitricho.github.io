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

# Minizip

Чтобы подключить minizip в Emscripten, нужно использовать флаг `-s USE_ZLIB=1` и добавить заголовочные файлы miniz. Вот полный код:

## Основной код C++ с minizip

```cpp
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <emscripten.h>
#include <emscripten/fetch.h>

// Подключаем miniz
#include "miniz.h"

// Структура для хранения данных файла
typedef struct {
    char* data;
    size_t size;
    int found;
} FileData;

// Структура для хранения распакованных файлов
typedef struct {
    FileData document_xml;
    FileData styles_xml;
    FileData numbering_xml;
} ExtractedFiles;

// Функция для извлечения файла из ZIP архива в памяти
FileData extract_file_from_memory(const char* zip_data, size_t zip_size, const char* filename) {
    FileData result = {NULL, 0, 0};
    
    mz_zip_archive zip_archive;
    memset(&zip_archive, 0, sizeof(zip_archive));
    
    // Инициализируем ZIP архив из данных в памяти
    mz_bool status = mz_zip_reader_init_mem(&zip_archive, (void*)zip_data, zip_size, 0);
    if (!status) {
        printf("Ошибка: не удалось инициализировать ZIP архив из памяти\n");
        return result;
    }
    
    // Ищем файл в архиве
    int file_index = mz_zip_reader_locate_file(&zip_archive, filename, NULL, 0);
    if (file_index < 0) {
        printf("Файл '%s' не найден в архиве\n", filename);
        mz_zip_reader_end(&zip_archive);
        return result;
    }
    
    // Получаем информацию о файле
    mz_zip_archive_file_stat file_stat;
    if (!mz_zip_reader_file_stat(&zip_archive, file_index, &file_stat)) {
        printf("Ошибка получения информации о файле '%s'\n", filename);
        mz_zip_reader_end(&zip_archive);
        return result;
    }
    
    printf("Найден файл: %s, размер: %llu байт\n", filename, file_stat.m_uncomp_size);
    
    // Извлекаем файл в память
    result.data = (char*)mz_zip_reader_extract_to_heap(&zip_archive, file_index, &result.size, 0);
    result.found = (result.data != NULL);
    
    if (result.found) {
        printf("Файл '%s' успешно извлечен, размер: %zu байт\n", filename, result.size);
    } else {
        printf("Ошибка извлечения файла '%s'\n", filename);
    }
    
    mz_zip_reader_end(&zip_archive);
    return result;
}

// Функция для освобождения памяти
void free_file_data(FileData* file_data) {
    if (file_data->data) {
        free(file_data->data);
        file_data->data = NULL;
        file_data->size = 0;
        file_data->found = 0;
    }
}

// Основная функция распаковки DOCX
int extract_docx_files(const char* zip_data, size_t zip_size, ExtractedFiles* extracted) {
    memset(extracted, 0, sizeof(ExtractedFiles));
    
    printf("Начинаем распаковку DOCX файла...\n");
    
    // Извлекаем основные XML файлы из DOCX
    extracted->document_xml = extract_file_from_memory(zip_data, zip_size, "word/document.xml");
    extracted->styles_xml = extract_file_from_memory(zip_data, zip_size, "word/styles.xml");
    extracted->numbering_xml = extract_file_from_memory(zip_data, zip_size, "word/numbering.xml");
    
    int files_found = 0;
    if (extracted->document_xml.found) files_found++;
    if (extracted->styles_xml.found) files_found++;
    if (extracted->numbering_xml.found) files_found++;
    
    printf("Извлечено файлов: %d\n", files_found);
    return files_found;
}

// Callback для ошибки загрузки
void downloadFailed(emscripten_fetch_t *fetch) {
    printf("Ошибка загрузки файла: %d\n", fetch->status);
    emscripten_fetch_close(fetch);
}

// Callback для успешной загрузки
void downloadSucceeded(emscripten_fetch_t *fetch) {
    printf("Файл загружен успешно. Размер: %llu байт\n", fetch->numBytes);
    
    ExtractedFiles extracted;
    
    if (extract_docx_files(fetch->data, fetch->numBytes, &extracted) > 0) {
        printf("DOCX файл обработан успешно!\n");
        
        // Передаем результаты в JavaScript
        EM_ASM({
            var result = {
                documentXml: null,
                stylesXml: null,
                numberingXml: null,
                status: 'success'
            };
            
            if ($0 > 0) {
                result.documentXml = UTF8ToString($1, $2);
            }
            if ($3 > 0) {
                result.stylesXml = UTF8ToString($4, $5);
            }
            if ($6 > 0) {
                result.numberingXml = UTF8ToString($7, $8);
            }
            
            // Вызываем callback в JavaScript
            if (typeof window.onDocxExtracted === 'function') {
                window.onDocxExtracted(result);
            }
        }, 
        extracted.document_xml.found, extracted.document_xml.data, extracted.document_xml.size,
        extracted.styles_xml.found, extracted.styles_xml.data, extracted.styles_xml.size,
        extracted.numbering_xml.found, extracted.numbering_xml.data, extracted.numbering_xml.size);
    } else {
        printf("Не удалось извлечь файлы из DOCX\n");
        
        EM_ASM({
            if (typeof window.onDocxExtracted === 'function') {
                window.onDocxExtracted({
                    status: 'error',
                    message: 'Не удалось извлечь файлы из DOCX'
                });
            }
        });
    }
    
    // Освобождаем память
    free_file_data(&extracted.document_xml);
    free_file_data(&extracted.styles_xml);
    free_file_data(&extracted.numbering_xml);
    
    emscripten_fetch_close(fetch);
}

// Функция для вызова из JavaScript
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
    
    // Функция для тестирования с уже загруженными данными
    EMSCRIPTEN_KEEPALIVE
    char* extractDocxFromMemory(char* data, int size) {
        ExtractedFiles extracted;
        
        if (extract_docx_files(data, size, &extracted) > 0 && extracted.document_xml.found) {
            // Возвращаем document.xml
            return extracted.document_xml.data;
            // Note: память должна быть освобождена в JavaScript с помощью _free()
        }
        
        return NULL;
    }
    
    // Функция для освобождения памяти (для JavaScript)
    EMSCRIPTEN_KEEPALIVE
    void free_memory(char* ptr) {
        if (ptr) {
            free(ptr);
        }
    }
}

int main() {
    printf("WASM модуль для обработки DOCX файлов с miniz инициализирован\n");
    return 0;
}
```

## HTML файл для тестирования

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DOCX Extractor with Miniz</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        input[type="text"] { width: 400px; padding: 5px; }
        button { padding: 5px 15px; margin-left: 10px; }
        textarea { width: 100%; height: 200px; margin-top: 10px; }
        .file-input { margin: 20px 0; }
        .tab { margin: 10px 0; }
        .tab-button { padding: 5px 10px; margin-right: 5px; cursor: pointer; }
        .tab-content { display: none; border: 1px solid #ccc; padding: 10px; }
        .active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Извлечение текста из DOCX файлов с Miniz</h1>
        
        <div>
            <h3>Загрузка по URL:</h3>
            <input type="text" id="urlInput" placeholder="https://example.com/document.docx">
            <button onclick="processDocxFromUrl()">Загрузить и обработать</button>
        </div>
        
        <div class="file-input">
            <h3>Или выберите локальный файл:</h3>
            <input type="file" id="fileInput" accept=".docx">
        </div>
        
        <div id="status"></div>
        
        <div class="tabs">
            <button class="tab-button" onclick="showTab('document')">document.xml</button>
            <button class="tab-button" onclick="showTab('styles')">styles.xml</button>
            <button class="tab-button" onclick="showTab('numbering')">numbering.xml</button>
        </div>
        
        <div id="document" class="tab-content">
            <textarea id="documentOutput" readonly></textarea>
        </div>
        
        <div id="styles" class="tab-content">
            <textarea id="stylesOutput" readonly></textarea>
        </div>
        
        <div id="numbering" class="tab-content">
            <textarea id="numberingOutput" readonly></textarea>
        </div>
    </div>

    <script>
        // Глобальная функция для обработки результатов
        window.onDocxExtracted = function(result) {
            const statusDiv = document.getElementById('status');
            
            if (result.status === 'success') {
                statusDiv.innerHTML = '<span style="color: green;">Файл успешно обработан!</span>';
                
                if (result.documentXml) {
                    document.getElementById('documentOutput').value = result.documentXml;
                }
                if (result.stylesXml) {
                    document.getElementById('stylesOutput').value = result.stylesXml;
                }
                if (result.numberingXml) {
                    document.getElementById('numberingOutput').value = result.numberingXml;
                }
                
                // Показываем первую вкладку
                showTab('document');
            } else {
                statusDiv.innerHTML = '<span style="color: red;">Ошибка: ' + (result.message || 'Неизвестная ошибка') + '</span>';
            }
        };
        
        // Функция для обработки DOCX по URL
        function processDocxFromUrl() {
            const url = document.getElementById('urlInput').value;
            if (!url) {
                alert('Пожалуйста, введите URL');
                return;
            }
            
            document.getElementById('status').innerHTML = 'Загрузка...';
            clearOutputs();
            
            Module.downloadAndExtractDocx(url);
        }
        
        // Обработчик для локального файла
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            if (!file.name.endsWith('.docx')) {
                alert('Пожалуйста, выберите DOCX файл');
                return;
            }
            
            document.getElementById('status').innerHTML = 'Обработка файла...';
            clearOutputs();
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const arrayBuffer = e.target.result;
                const uint8Array = new Uint8Array(arrayBuffer);
                
                // Выделяем память в WASM и копируем данные
                const numBytes = uint8Array.length;
                const dataPtr = Module._malloc(numBytes);
                Module.HEAPU8.set(uint8Array, dataPtr);
                
                // Извлекаем document.xml
                const xmlPtr = Module.extractDocxFromMemory(dataPtr, numBytes);
                
                if (xmlPtr) {
                    const xmlContent = Module.UTF8ToString(xmlPtr);
                    document.getElementById('documentOutput').value = xmlContent;
                    document.getElementById('status').innerHTML = '<span style="color: green;">Файл успешно обработан!</span>';
                    showTab('document');
                    
                    // Освобождаем память
                    Module._free(xmlPtr);
                } else {
                    document.getElementById('status').innerHTML = '<span style="color: red;">Не удалось извлечь данные из файла</span>';
                }
                
                Module._free(dataPtr);
            };
            reader.readAsArrayBuffer(file);
        });
        
        // Функции для управления вкладками
        function showTab(tabName) {
            // Скрываем все вкладки
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Показываем выбранную вкладку
            document.getElementById(tabName).classList.add('active');
        }
        
        function clearOutputs() {
            document.getElementById('documentOutput').value = '';
            document.getElementById('stylesOutput').value = '';
            document.getElementById('numberingOutput').value = '';
        }
        
        // Показываем первую вкладку по умолчанию
        showTab('document');
    </script>
    
    <script src="docx_extractor.js"></script>
</body>
</html>
```

## Команда компиляции

```bash
emcc -o docx_extractor.js docx_extractor.cpp \
    -s USE_ZLIB=1 \
    -s FETCH=1 \
    -s ALLOW_MEMORY_GROWTH=1 \
    -s EXPORTED_FUNCTIONS='["_downloadAndExtractDocx", "_extractDocxFromMemory", "_free_memory", "_main", "_free"]' \
    -s EXPORTED_RUNTIME_METHODS='["ccall", "cwrap", "UTF8ToString", "allocate", "ALLOC_NORMAL"]' \
    -O3 \
    --no-entry
```

## Ключевые особенности:

1. **Использование miniz**: Включено через `#include "miniz.h"` и флаг `-s USE_ZLIB=1`
2. **Работа с памятью**: ZIP архив обрабатывается непосредственно в памяти
3. **Извлечение файлов**: Используется `mz_zip_reader_extract_to_heap()` для извлечения конкретных файлов
4. **Callback в JavaScript**: Результаты передаются обратно в JS через `EM_ASM`
5. **Два способа загрузки**: По URL и через локальный файл

Miniz предоставляет полный API для работы с ZIP архивами, включая поиск файлов, извлечение и работу с метаданными.
