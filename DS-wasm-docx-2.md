Отличное дополнение! Вот расширенный код с интеграцией libxml2 и libxslt:

## Основной код C++ с minizip, libxml2 и libxslt

```cpp
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <emscripten.h>
#include <emscripten/fetch.h>

// Подключаем miniz
#include "miniz.h"

// Подключаем libxml2 и libxslt
#include <libxml/parser.h>
#include <libxml/tree.h>
#include <libxslt/xslt.h>
#include <libxslt/xsltInternals.h>
#include <libxslt/transform.h>
#include <libxslt/xsltutils.h>

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
    FileData xsl_template;
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

// Функция для загрузки XSLT шаблона
FileData load_xslt_template() {
    FileData result = {NULL, 0, 0};
    
    // В реальном приложении шаблон может загружаться из файла или сети
    // Здесь для примера создаем простой шаблон
    const char* simple_xslt = 
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        "<xsl:stylesheet version=\"1.0\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">\n"
        "  <xsl:param name=\"styles\"/>\n"
        "  <xsl:param name=\"numbering\"/>\n"
        "  <xsl:output method=\"text\" encoding=\"UTF-8\"/>\n"
        "  \n"
        "  <xsl:template match=\"/\">\n"
        "    <xsl:text>=== XSLT Transformation Result ===\n</xsl:text>\n"
        "    <xsl:text>Document XML processed with styles and numbering parameters\n</xsl:text>\n"
        "    <xsl:text>Styles param length: </xsl:text><xsl:value-of select=\"string-length($styles)\"/>\n"
        "    <xsl:text>\nNumbering param length: </xsl:text><xsl:value-of select=\"string-length($numbering)\"/>\n"
        "    <xsl:text>\n\nDocument content preview:\n</xsl:text>\n"
        "    <xsl:apply-templates select=\"//w:t\"/>\n"
        "  </xsl:template>\n"
        "  \n"
        "  <xsl:template match=\"w:t\">\n"
        "    <xsl:value-of select=\".\"/>\n"
        "  </xsl:template>\n"
        "</xsl:stylesheet>";
    
    result.size = strlen(simple_xslt);
    result.data = (char*)malloc(result.size + 1);
    strcpy(result.data, simple_xslt);
    result.found = 1;
    
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

// Функция для применения XSLT преобразования
char* apply_xslt_transformation(const char* xml_data, const char* xslt_data,
                               const char* styles_param, const char* numbering_param) {
    xmlDocPtr xml_doc = NULL;
    xmlDocPtr xsl_doc = NULL;
    xsltStylesheetPtr xslt = NULL;
    xmlDocPtr result_doc = NULL;
    char* result = NULL;
    
    // Инициализируем библиотеку
    xmlInitParser();
    xsltInit();
    
    printf("Начинаем XSLT преобразование...\n");
    
    // Парсим XML документ
    xml_doc = xmlParseMemory(xml_data, strlen(xml_data));
    if (!xml_doc) {
        printf("Ошибка: не удалось распарсить XML документ\n");
        goto cleanup;
    }
    
    // Парсим XSLT шаблон
    xsl_doc = xmlParseMemory(xslt_data, strlen(xslt_data));
    if (!xsl_doc) {
        printf("Ошибка: не удалось распарсить XSLT шаблон\n");
        goto cleanup;
    }
    
    // Компилируем XSLT шаблон
    xslt = xsltParseStylesheetDoc(xsl_doc);
    if (!xslt) {
        printf("Ошибка: не удалось скомпилировать XSLT шаблон\n");
        goto cleanup;
    }
    
    // Устанавливаем параметры
    const char* params[5];
    params[0] = "styles";
    params[1] = styles_param ? styles_param : "";
    params[2] = "numbering";
    params[3] = numbering_param ? numbering_param : "";
    params[4] = NULL;
    
    printf("Устанавливаем параметры XSLT...\n");
    
    // Применяем XSLT преобразование
    result_doc = xsltApplyStylesheet(xslt, xml_doc, params);
    if (!result_doc) {
        printf("Ошибка: не удалось применить XSLT преобразование\n");
        goto cleanup;
    }
    
    // Сохраняем результат в строку
    xmlChar* xml_result;
    int length;
    xsltSaveResultToString(&xml_result, &length, result_doc, xslt);
    
    if (xml_result && length > 0) {
        result = (char*)malloc(length + 1);
        memcpy(result, xml_result, length);
        result[length] = '\0';
        printf("XSLT преобразование успешно завершено, результат: %d байт\n", length);
        xmlFree(xml_result);
    } else {
        printf("Ошибка: пустой результат после XSLT преобразования\n");
    }
    
cleanup:
    // Освобождаем ресурсы
    if (result_doc) xmlFreeDoc(result_doc);
    if (xslt) xsltFreeStylesheet(xslt);
    if (xml_doc) xmlFreeDoc(xml_doc);
    // xsl_doc освобождается автоматически при вызове xsltFreeStylesheet
    
    xsltCleanup();
    xmlCleanupParser();
    
    return result;
}

// Основная функция распаковки DOCX
int extract_docx_files(const char* zip_data, size_t zip_size, ExtractedFiles* extracted) {
    memset(extracted, 0, sizeof(ExtractedFiles));
    
    printf("Начинаем распаковку DOCX файла...\n");
    
    // Извлекаем основные XML файлы из DOCX
    extracted->document_xml = extract_file_from_memory(zip_data, zip_size, "word/document.xml");
    extracted->styles_xml = extract_file_from_memory(zip_data, zip_size, "word/styles.xml");
    extracted->numbering_xml = extract_file_from_memory(zip_data, zip_size, "word/numbering.xml");
    
    // Загружаем XSLT шаблон
    extracted->xsl_template = load_xslt_template();
    
    int files_found = 0;
    if (extracted->document_xml.found) files_found++;
    if (extracted->styles_xml.found) files_found++;
    if (extracted->numbering_xml.found) files_found++;
    if (extracted->xsl_template.found) files_found++;
    
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
        
        // Применяем XSLT преобразование если есть document.xml и шаблон
        char* transformation_result = NULL;
        if (extracted.document_xml.found && extracted.xsl_template.found) {
            const char* styles_param = extracted.styles_xml.found ? extracted.styles_xml.data : "";
            const char* numbering_param = extracted.numbering_xml.found ? extracted.numbering_xml.data : "";
            
            transformation_result = apply_xslt_transformation(
                extracted.document_xml.data,
                extracted.xsl_template.data,
                styles_param,
                numbering_param
            );
        }
        
        // Передаем результаты в JavaScript
        EM_ASM({
            var result = {
                documentXml: null,
                stylesXml: null,
                numberingXml: null,
                transformationResult: null,
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
            if ($9) {
                result.transformationResult = UTF8ToString($9);
            }
            
            // Вызываем callback в JavaScript
            if (typeof window.onDocxExtracted === 'function') {
                window.onDocxExtracted(result);
            }
        }, 
        extracted.document_xml.found, extracted.document_xml.data, extracted.document_xml.size,
        extracted.styles_xml.found, extracted.styles_xml.data, extracted.styles_xml.size,
        extracted.numbering_xml.found, extracted.numbering_xml.data, extracted.numbering_xml.size,
        transformation_result);
        
        // Освобождаем память результата преобразования
        if (transformation_result) {
            free(transformation_result);
        }
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
    free_file_data(&extracted.xsl_template);
    
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
    
    // Функция для применения XSLT к уже загруженным данным
    EMSCRIPTEN_KEEPALIVE
    char* applyXsltTransformation(const char* xml_data, const char* styles_data, const char* numbering_data) {
        FileData xsl_template = load_xslt_template();
        if (!xsl_template.found) {
            printf("Ошибка: не удалось загрузить XSLT шаблон\n");
            return NULL;
        }
        
        char* result = apply_xslt_transformation(
            xml_data,
            xsl_template.data,
            styles_data,
            numbering_data
        );
        
        free_file_data(&xsl_template);
        return result;
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
    printf("WASM модуль для обработки DOCX файлов с miniz, libxml2 и libxslt инициализирован\n");
    return 0;
}
```

## Команда компиляции

```bash
emcc -o docx_extractor.js docx_extractor.cpp \
    -s USE_ZLIB=1 \
    -s FETCH=1 \
    -s USE_LIBXML2=1 \
    -s ALLOW_MEMORY_GROWTH=1 \
    -s EXPORTED_FUNCTIONS='["_downloadAndExtractDocx", "_applyXsltTransformation", "_free_memory", "_main", "_free"]' \
    -s EXPORTED_RUNTIME_METHODS='["ccall", "cwrap", "UTF8ToString", "allocate", "ALLOC_NORMAL"]' \
    -O3 \
    --no-entry
```

## Обновленный HTML для тестирования

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DOCX Extractor with XSLT</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        input[type="text"] { width: 400px; padding: 5px; }
        button { padding: 5px 15px; margin-left: 10px; }
        textarea { width: 100%; height: 200px; margin-top: 10px; font-family: monospace; }
        .file-input { margin: 20px 0; }
        .tab { margin: 10px 0; }
        .tab-button { padding: 5px 10px; margin-right: 5px; cursor: pointer; }
        .tab-content { display: none; border: 1px solid #ccc; padding: 10px; }
        .active { display: block; }
        .status { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>DOCX Extractor with XSLT Transformation</h1>
        
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
            <button class="tab-button" onclick="showTab('transformation')">XSLT Result</button>
            <button class="tab-button" onclick="showTab('document')">document.xml</button>
            <button class="tab-button" onclick="showTab('styles')">styles.xml</button>
            <button class="tab-button" onclick="showTab('numbering')">numbering.xml</button>
        </div>
        
        <div id="transformation" class="tab-content active">
            <h4>Результат XSLT преобразования:</h4>
            <textarea id="transformationOutput" readonly></textarea>
        </div>
        
        <div id="document" class="tab-content">
            <h4>document.xml:</h4>
            <textarea id="documentOutput" readonly></textarea>
        </div>
        
        <div id="styles" class="tab-content">
            <h4>styles.xml:</h4>
            <textarea id="stylesOutput" readonly></textarea>
        </div>
        
        <div id="numbering" class="tab-content">
            <h4>numbering.xml:</h4>
            <textarea id="numberingOutput" readonly></textarea>
        </div>
    </div>

    <script>
        // Глобальная функция для обработки результатов
        window.onDocxExtracted = function(result) {
            const statusDiv = document.getElementById('status');
            
            if (result.status === 'success') {
                statusDiv.innerHTML = '<div class="status success">Файл успешно обработан! Применено XSLT преобразование.</div>';
                
                if (result.transformationResult) {
                    document.getElementById('transformationOutput').value = result.transformationResult;
                }
                if (result.documentXml) {
                    document.getElementById('documentOutput').value = result.documentXml;
                }
                if (result.stylesXml) {
                    document.getElementById('stylesOutput').value = result.stylesXml;
                }
                if (result.numberingXml) {
                    document.getElementById('numberingOutput').value = result.numberingXml;
                }
                
                // Показываем вкладку с результатом преобразования
                showTab('transformation');
            } else {
                statusDiv.innerHTML = '<div class="status error">Ошибка: ' + (result.message || 'Неизвестная ошибка') + '</div>';
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
                
                // Здесь можно добавить более сложную логику для извлечения всех файлов
                // и применения XSLT преобразования
                
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
            document.getElementById('transformationOutput').value = '';
            document.getElementById('documentOutput').value = '';
            document.getElementById('stylesOutput').value = '';
            document.getElementById('numberingOutput').value = '';
        }
    </script>
    
    <script src="docx_extractor.js"></script>
</body>
</html>
```

## Ключевые особенности реализации:

1. **Интеграция libxml2/libxslt**: Используются `xsltApplyStylesheet` для применения XSLT преобразований
2. **Передача параметров**: Параметры `styles` и `numbering` передаются в XSLT шаблон
3. **Обработка в памяти**: Все преобразования выполняются в памяти без сохранения временных файлов
4. **Управление ресурсами**: Правильное освобождение ресурсов libxml2/libxslt
5. **Расширенный вывод**: Результат XSLT преобразования выводится в отдельную вкладку

XSLT шаблон в коде - простой пример, который можно заменить на ваш собственный `template.xsl`.
