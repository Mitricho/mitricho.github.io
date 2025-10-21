<?php
/**
 * Оптимизатор веб-страниц - удаляет неиспользуемый CSS код
 * с поддержкой медиа-запросов, псевдоклассов и импортов
 */

// Проверяем наличие URL параметра
if (!isset($_GET['url']) || empty($_GET['url'])) {
    die("Ошибка: Не указан URL параметр. Используйте: ?url=http://example.com");
}

$targetUrl = $_GET['url'];

// Проверяем валидность URL
if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
    die("Ошибка: Неверный формат URL");
}

// Глобальные переменные для отслеживания обработанных файлов
$processedCssFiles = [];
$allUsedSelectors = [];

try {
    // Получаем HTML страницы
    $htmlContent = fetchUrlContent($targetUrl);
    if (!$htmlContent) {
        die("Ошибка: Не удалось загрузить страницу");
    }
    
    // Парсим HTML и извлекаем все теги и селекторы
    $allUsedSelectors = parseHtmlForSelectors($htmlContent);
    
    // Получаем все CSS файлы со страницы (включая inline стили)
    $cssResources = extractCssResources($htmlContent, $targetUrl);
    
    // Обрабатываем все CSS ресурсы
    $optimizedCss = processCssResources($cssResources, $allUsedSelectors, $targetUrl);
    
    // Выводим результат
    header('Content-Type: text/css');
    echo "/* Оптимизированный CSS для: " . $targetUrl . " */\n";
    echo "/* Обработано CSS файлов: " . count($processedCssFiles) . " */\n";
    echo "/* Найдено используемых селекторов: " . count($allUsedSelectors) . " */\n\n";
    echo $optimizedCss;
    
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}

/**
 * Загружает содержимое по URL
 */
function fetchUrlContent($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => '' // Автоматическое распознавание кодировки
    ]);
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode === 200) ? $content : false;
}

/**
 * Парсит HTML и извлекает все используемые теги и селекторы
 */
function parseHtmlForSelectors($html) {
    $dom = new DOMDocument();
    @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    
    $selectors = [];
    
    // Извлекаем все теги
    $elements = $dom->getElementsByTagName('*');
    foreach ($elements as $element) {
        $tagName = strtolower($element->tagName);
        $selectors[$tagName] = true;
        
        // Добавляем псевдоклассы для основных тегов
        $selectors[$tagName . ':hover'] = true;
        $selectors[$tagName . ':focus'] = true;
        $selectors[$tagName . ':active'] = true;
        
        // Извлекаем классы
        if ($element->hasAttribute('class')) {
            $classes = explode(' ', $element->getAttribute('class'));
            foreach ($classes as $class) {
                $class = trim($class);
                if ($class) {
                    $selectors['.' . $class] = true;
                    $selectors['.' . $class . ':hover'] = true;
                    $selectors['.' . $class . ':focus'] = true;
                    $selectors['.' . $class . ':active'] = true;
                }
            }
        }
        
        // Извлекаем ID
        if ($element->hasAttribute('id')) {
            $id = trim($element->getAttribute('id'));
            if ($id) {
                $selectors['#' . $id] = true;
                $selectors['#' . $id . ':hover'] = true;
                $selectors['#' . $id . ':focus'] = true;
            }
        }
        
        // Извлекаем атрибуты
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attr) {
                $attrName = strtolower($attr->nodeName);
                if (!in_array($attrName, ['class', 'id', 'style'])) {
                    $selectors['[' . $attrName . ']'] = true;
                    $selectors['[' . $attrName . '="' . $attr->nodeValue . '"]'] = true;
                }
            }
        }
    }
    
    // Извлекаем селекторы из атрибута style
    preg_match_all('/style="([^"]*)"/i', $html, $styleMatches);
    foreach ($styleMatches[1] as $styleContent) {
        preg_match_all('/([a-zA-Z-]+)\s*:/', $styleContent, $propertyMatches);
        foreach ($propertyMatches[1] as $property) {
            $selectors['--' . strtolower(trim($property))] = true;
        }
    }
    
    return array_keys($selectors);
}

/**
 * Извлекает все CSS ресурсы из HTML (внешние файлы + inline стили)
 */
function extractCssResources($html, $baseUrl) {
    $cssResources = [];
    
    // Извлекаем ссылки на CSS файлы
    preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
    
    foreach ($matches[1] as $cssPath) {
        $cssUrl = resolveUrl($cssPath, $baseUrl);
        $cssResources[] = [
            'type' => 'external',
            'url' => $cssUrl,
            'content' => null
        ];
    }
    
    // Извлекаем inline стили
    preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $html, $styleMatches);
    foreach ($styleMatches[1] as $styleContent) {
        $cssResources[] = [
            'type' => 'inline',
            'url' => $baseUrl . '#inline-style',
            'content' => $styleContent
        ];
    }
    
    return $cssResources;
}

/**
 * Обрабатывает все CSS ресурсы рекурсивно
 */
function processCssResources($cssResources, &$usedSelectors, $baseUrl) {
    global $processedCssFiles;
    
    $optimizedCss = '';
    
    foreach ($cssResources as $resource) {
        if ($resource['type'] === 'external') {
            $cssUrl = $resource['url'];
            
            // Проверяем, не обрабатывали ли уже этот файл
            if (in_array($cssUrl, $processedCssFiles)) {
                continue;
            }
            
            $cssContent = fetchUrlContent($cssUrl);
            if ($cssContent) {
                $processedCssFiles[] = $cssUrl;
                $optimizedCss .= "/* CSS файл: " . $cssUrl . " */\n";
                $optimizedCss .= processCssContent($cssContent, $usedSelectors, $baseUrl, $cssUrl) . "\n\n";
            }
        } else {
            // Обрабатываем inline стили
            $optimizedCss .= "/* Inline стили */\n";
            $optimizedCss .= processCssContent($resource['content'], $usedSelectors, $baseUrl, $resource['url']) . "\n\n";
        }
    }
    
    return $optimizedCss;
}

/**
 * Обрабатывает содержимое CSS с рекурсивной обработкой импортов
 */
function processCssContent($cssContent, &$usedSelectors, $baseUrl, $currentCssUrl) {
    // Сначала обрабатываем @import правила
    $processedContent = processCssImports($cssContent, $usedSelectors, $baseUrl, $currentCssUrl);
    
    // Затем оптимизируем основной CSS
    return optimizeCss($processedContent, $usedSelectors);
}

/**
 * Обрабатывает @import правила рекурсивно
 */
function processCssImports($cssContent, &$usedSelectors, $baseUrl, $currentCssUrl) {
    $importPattern = '/@import\s+(url\()?["\']([^"\']+)["\'](\))?[^;]*;/i';
    
    return preg_replace_callback($importPattern, function($matches) use (&$usedSelectors, $baseUrl, $currentCssUrl) {
        $importUrl = $matches[2];
        
        // Разрешаем URL импорта относительно текущего CSS файла
        $fullImportUrl = resolveUrl($importUrl, $currentCssUrl);
        
        // Загружаем и обрабатываем импортируемый CSS
        $importedCss = fetchUrlContent($fullImportUrl);
        if ($importedCss) {
            global $processedCssFiles;
            if (!in_array($fullImportUrl, $processedCssFiles)) {
                $processedCssFiles[] = $fullImportUrl;
                return processCssContent($importedCss, $usedSelectors, $baseUrl, $fullImportUrl);
            }
        }
        
        // Если не удалось загрузить, оставляем оригинальный import
        return $matches[0];
    }, $cssContent);
}

/**
 * Оптимизирует CSS, оставляя только используемые селекторы с поддержкой медиа-запросов
 */
function optimizeCss($cssContent, $usedSelectors) {
    // Удаляем комментарии (но сохраняем важные)
    $cssContent = preg_replace('/\/\*\![\s\S]*?\*\//', '', $cssContent); // Сохраняем важные комментарии
    $cssContent = preg_replace('/\/\*[\s\S]*?\*\//', '', $cssContent);
    
    $optimizedCss = '';
    $currentMediaQuery = '';
    
    // Обрабатываем медиа-запросы
    $mediaPattern = '/(@media[^{]+)\{([\s\S]*?)\}\s*\}/';
    
    // Сначала извлекаем и обрабатываем медиа-запросы
    $cssWithoutMedia = preg_replace_callback($mediaPattern, function($matches) use ($usedSelectors, &$optimizedCss) {
        $mediaRule = $matches[1];
        $mediaContent = $matches[2];
        
        $optimizedMediaContent = processCssRules($mediaContent, $usedSelectors);
        
        if (!empty(trim($optimizedMediaContent))) {
            $optimizedCss .= $mediaRule . " {\n" . $optimizedMediaContent . "\n}\n\n";
        }
        
        return ''; // Удаляем обработанный медиа-запрос из основного контента
    }, $cssContent);
    
    // Обрабатываем оставшийся CSS (вне медиа-запросов)
    $optimizedCss .= processCssRules($cssWithoutMedia, $usedSelectors);
    
    return $optimizedCss;
}

/**
 * Обрабатывает CSS правила внутри блока
 */
function processCssRules($cssContent, $usedSelectors) {
    $rules = [];
    
    // Разбиваем на отдельные правила (учитывая вложенные конструкции)
    $pattern = '/([^{]+)\{([^}]*)\}/';
    preg_match_all($pattern, $cssContent, $ruleMatches, PREG_SET_ORDER);
    
    foreach ($ruleMatches as $match) {
        $selectorBlock = trim($match[1]);
        $declarations = trim($match[2]);
        
        // Пропускаем пустые declaration blocks
        if (empty($declarations)) {
            continue;
        }
        
        // Разбиваем групповые селекторы
        $selectors = array_map('trim', explode(',', $selectorBlock));
        
        $keepRule = false;
        $optimizedSelectors = [];
        
        foreach ($selectors as $selector) {
            if (isSelectorUsed($selector, $usedSelectors)) {
                $keepRule = true;
                $optimizedSelectors[] = $selector;
            }
        }
        
        if ($keepRule && !empty($optimizedSelectors) && !empty($declarations)) {
            $optimizedSelectorBlock = implode(', ', $optimizedSelectors);
            $rules[] = $optimizedSelectorBlock . ' { ' . $declarations . ' }';
        }
    }
    
    return implode("\n", $rules);
}

/**
 * Проверяет, используется ли селектор (с поддержкой псевдоклассов)
 */
function isSelectorUsed($selector, $usedSelectors) {
    $selector = trim($selector);
    
    // Проверяем точное совпадение
    if (in_array($selector, $usedSelectors)) {
        return true;
    }
    
    // Обрабатываем псевдоклассы и псевдоэлементы
    $baseSelector = preg_replace('/::?[a-zA-Z-]+(\([^)]*\))?/', '', $selector);
    $baseSelector = trim($baseSelector);
    
    // Проверяем базовый селектор без псевдоклассов
    if (!empty($baseSelector) && in_array($baseSelector, $usedSelectors)) {
        return true;
    }
    
    // Проверяем основные псевдоклассы
    $commonPseudoClasses = ['hover', 'focus', 'active', 'visited', 'link', 'before', 'after', 'first-child', 'last-child'];
    foreach ($commonPseudoClasses as $pseudo) {
        if (strpos($selector, ':' . $pseudo) !== false) {
            $cleanSelector = preg_replace('/:' . $pseudo . '.*/', '', $selector);
            if (in_array($cleanSelector, $usedSelectors)) {
                return true;
            }
        }
    }
    
    // Разбираем сложные селекторы
    $simpleSelectors = preg_split('/\s*[\s>+~]\s*/', $baseSelector);
    
    foreach ($simpleSelectors as $simpleSelector) {
        // Очищаем селектор от атрибутов
        $cleanSelector = preg_replace('/\[.*\]/', '', $simpleSelector);
        $cleanSelector = trim($cleanSelector);
        
        if (!empty($cleanSelector) && in_array($cleanSelector, $usedSelectors)) {
            return true;
        }
        
        // Проверяем базовые теги
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $cleanSelector) && in_array($cleanSelector, $usedSelectors)) {
            return true;
        }
    }
    
    // Всегда сохраняем ключевые CSS правила
    $importantRules = ['@keyframes', '@font-face', '@page', '@charset'];
    foreach ($importantRules as $rule) {
        if (strpos($selector, $rule) === 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Преобразует относительный URL в абсолютный
 */
function resolveUrl($relativeUrl, $baseUrl) {
    if (strpos($relativeUrl, 'http') === 0 || strpos($relativeUrl, '//') === 0) {
        return $relativeUrl;
    }
    
    $base = parse_url($baseUrl);
    $basePath = isset($base['path']) ? dirname($base['path']) : '';
    
    if (strpos($relativeUrl, '/') === 0) {
        // Абсолютный путь
        return $base['scheme'] . '://' . $base['host'] . $relativeUrl;
    } else if (strpos($relativeUrl, './') === 0) {
        // Относительный путь с ./
        return $base['scheme'] . '://' . $base['host'] . $basePath . '/' . substr($relativeUrl, 2);
    } else {
        // Относительный путь
        return $base['scheme'] . '://' . $base['host'] . $basePath . '/' . $relativeUrl;
    }
}
?>
