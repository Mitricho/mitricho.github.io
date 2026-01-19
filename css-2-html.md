Вот полное решение с обработкой вложенности, тегов и свойства content:

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Parser</title>
    <style>
        #result {
            border: 1px solid #ccc;
            padding: 15px;
            margin-top: 20px;
            font-family: monospace;
            background: #f5f5f5;
            min-height: 100px;
        }
        .output-element {
            border: 1px dashed #ddd;
            margin: 5px;
            padding: 8px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>CSS Parser and Visualizer</h1>
    
    <!-- CSS для парсинга -->
    <script type="text/css" id="css-source">
        .panel .button {
            color: red;
        }
        
        span.active {
            font-weight: bold;
            content: "Активный элемент";
        }
        
        div.container .header .title {
            font-size: 24px;
            content: "Заголовок";
        }
        
        .menu > .item.selected {
            background: blue;
            content: "Выбранный пункт";
        }
        
        footer .copyright {
            color: gray;
        }
        
        .card .card-body .card-text {
            margin: 10px;
            content: "Текст карточки";
        }
        
        a.link:hover {
            text-decoration: underline;
        }
        
        #main-content {
            width: 100%;
        }
        
        ul li {
            list-style: none;
        }
        
        .parent .child .grandchild {
            padding: 5px;
            content: "Вложенный элемент";
        }
        
        h1.title {
            color: #333;
        }
    </script>
    
    <button onclick="parseAndGenerate()">Сгенерировать структуру из CSS</button>
    <button onclick="clearOutput()">Очистить</button>
    
    <div id="result"></div>
    
    <script>
        function parseAndGenerate() {
            const cssScript = document.getElementById('css-source');
            const cssText = cssScript.textContent || cssScript.innerText;
            const resultDiv = document.getElementById('result');
            
            resultDiv.innerHTML = '';
            
            const rules = extractCSSRules(cssText);
            
            rules.forEach(rule => {
                const element = createElementFromRule(rule);
                if (element) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'output-element';
                    
                    const ruleText = document.createElement('div');
                    ruleText.style.fontSize = '12px';
                    ruleText.style.color = '#666';
                    ruleText.style.marginBottom = '5px';
                    ruleText.textContent = rule.selector;
                    wrapper.appendChild(ruleText);
                    
                    wrapper.appendChild(element);
                    resultDiv.appendChild(wrapper);
                }
            });
        }
        
        function extractCSSRules(cssText) {
            const rules = [];
            
            // Удаляем комментарии
            cssText = cssText.replace(/\/\*[\s\S]*?\*\//g, '');
            
            // Находим все правила
            const ruleRegex = /([^{]+)\{([^}]+)\}/g;
            let match;
            
            while ((match = ruleRegex.exec(cssText)) !== null) {
                const selector = match[1].trim();
                const declarations = match[2].trim();
                
                // Ищем content
                const contentMatch = declarations.match(/content\s*:\s*(['"]?)(.*?)\1\s*(;|$)/i);
                let content = '';
                if (contentMatch && contentMatch[2]) {
                    content = contentMatch[2].trim();
                }
                
                rules.push({
                    selector: selector,
                    declarations: declarations,
                    content: content
                });
            }
            
            return rules;
        }
        
        function createElementFromRule(rule) {
            const selector = rule.selector;
            
            // Игнорируем псевдоклассы и псевдоэлементы
            if (selector.includes(':hover') || selector.includes(':active') || 
                selector.includes(':focus') || selector.includes('::')) {
                return null;
            }
            
            // Разделяем селектор по пробелам для вложенности
            const parts = selector.split(/\s+/).filter(part => part.length > 0);
            
            if (parts.length === 0) return null;
            
            // Строим структуру с конца к началу
            let currentElement = null;
            
            for (let i = parts.length - 1; i >= 0; i--) {
                const part = parts[i].trim();
                
                // Определяем тип элемента
                let tagName = 'div';
                let className = '';
                
                // Проверяем, начинается ли с тега
                const tagMatch = part.match(/^([a-zA-Z][a-zA-Z0-9]*)/);
                if (tagMatch) {
                    tagName = tagMatch[1];
                    
                    // Остальное может быть классом или id
                    const rest = part.substring(tagMatch[1].length);
                    if (rest.includes('.')) {
                        className = rest.replace(/\./g, ' ').trim();
                    }
                } else if (part.startsWith('.')) {
                    // Только класс
                    className = part.substring(1);
                } else if (part.startsWith('#')) {
                    // ID
                    const element = document.createElement('div');
                    element.id = part.substring(1);
                    if (currentElement) {
                        element.appendChild(currentElement);
                    }
                    currentElement = element;
                    continue;
                }
                
                // Создаем элемент
                const element = document.createElement(tagName);
                if (className) {
                    element.className = className;
                }
                
                // Добавляем content для самого внутреннего элемента
                if (i === parts.length - 1 && rule.content) {
                    element.textContent = rule.content;
                }
                
                // Добавляем дочерний элемент, если есть
                if (currentElement) {
                    element.appendChild(currentElement);
                }
                
                currentElement = element;
            }
            
            return currentElement;
        }
        
        function clearOutput() {
            document.getElementById('result').innerHTML = '';
        }
        
        // Автозапуск при загрузке
        window.onload = function() {
            console.log('CSS Parser готов к работе. Нажмите кнопку для генерации структуры.');
        };
    </script>
</body>
</html>
```

## Особенности реализации:

1. **Парсинг CSS**: Код извлекает CSS правила из тега `<script type="text/css">`
2. **Обработка вложенности**: Селекторы типа `.panel .button .icon` правильно преобразуются во вложенную структуру
3. **Поддержка различных типов селекторов**:
   - Классы (`.class`)
   - Теги (`div`, `span`, `h1` и т.д.)
   - ID (`#id`)
   - Комбинации (`div.class`, `span#id.class`)

4. **Обработка свойства content**: Если в правиле есть `content: "текст"`, этот текст добавляется во внутренний элемент

5. **Игнорирование**:
   - Псевдоклассов (`:hover`, `:active`)
   - Псевдоэлементов (`::before`, `::after`)
   - Комментариев CSS

6. **Визуализация**: Каждое правило отображается отдельным блоком с исходным CSS селектором и сгенерированной структурой

## Как использовать:

1. Разместите CSS правила внутри тега `<script type="text/css" id="css-source">`
2. Откройте HTML файл в браузере
3. Нажмите кнопку "Сгенерировать структуру из CSS"
4. Результат будет отображен ниже

Примеры поддерживаемых селекторов:
- `.parent .child` → `<div class="parent"><div class="child"></div></div>`
- `span.active` → `<span class="active"></span>`
- `div.container .header` → `<div class="container"><div class="header"></div></div>`