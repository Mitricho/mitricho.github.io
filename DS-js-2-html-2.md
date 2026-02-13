Вот JavaScript код, который конвертирует SVG в HTML элементы с CSS:

```javascript
(function() {
    'use strict';

    /**
     * Конвертирует SVG в HTML элементы с CSS
     * Запускать после полной загрузки страницы
     */
    function convertSVGtoHTML() {
        // Находим все div элементы содержащие SVG
        const svgContainers = document.querySelectorAll('div:has(svg)');
        
        svgContainers.forEach(container => {
            const svg = container.querySelector('svg');
            if (!svg) return;
            
            // Получаем размеры и позицию SVG
            const svgRect = svg.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            
            // Создаем фрагмент документа для новых элементов
            const fragment = document.createDocumentFragment();
            
            // Функция для рекурсивной обработки элементов
            function processElement(element, parentTransform = new DOMMatrix()) {
                // Пропускаем <path> элементы
                if (element.tagName.toLowerCase() === 'path') {
                    return null;
                }
                
                // Получаем текущую трансформацию элемента
                let currentTransform = parentTransform;
                const transformAttr = element.getAttribute('transform');
                
                if (transformAttr) {
                    try {
                        // Парсим transform атрибут
                        const transformMatrix = parseTransform(transformAttr);
                        currentTransform = parentTransform.multiply(transformMatrix);
                    } catch (e) {
                        console.warn('Ошибка парсинга transform:', e);
                    }
                }
                
                // Создаем HTML элемент для текущего SVG элемента
                if (element.tagName.toLowerCase() !== 'svg' && 
                    element.tagName.toLowerCase() !== 'g') {
                    
                    const htmlElement = createHTMLElement(element, currentTransform, svgRect);
                    if (htmlElement) {
                        return htmlElement;
                    }
                }
                
                // Обрабатываем дочерние элементы
                const children = Array.from(element.children);
                const childElements = [];
                
                children.forEach(child => {
                    // Пропускаем <path> элементы
                    if (child.tagName.toLowerCase() === 'path') return;
                    
                    const childElement = processElement(child, currentTransform);
                    if (childElement) {
                        if (Array.isArray(childElement)) {
                            childElements.push(...childElement);
                        } else {
                            childElements.push(childElement);
                        }
                    }
                });
                
                return childElements.length ? childElements : null;
            }
            
            /**
             * Парсит SVG transform атрибут в DOMMatrix
             */
            function parseTransform(transformString) {
                let matrix = new DOMMatrix();
                
                // Парсим различные transform функции
                const transforms = transformString.match(/(\w+)\s*\(([^)]+)\)/g);
                
                if (transforms) {
                    transforms.forEach(transform => {
                        const [_, type, values] = transform.match(/(\w+)\s*\(([^)]+)\)/);
                        const nums = values.trim().split(/[\s,]+/).map(Number);
                        
                        switch(type) {
                            case 'matrix':
                                if (nums.length === 6) {
                                    matrix = matrix.multiply(new DOMMatrix([
                                        nums[0], nums[1], nums[2],
                                        nums[3], nums[4], nums[5], 0, 0, 1
                                    ]));
                                }
                                break;
                            case 'translate':
                                if (nums.length === 1) nums[1] = 0;
                                matrix = matrix.translate(nums[0], nums[1]);
                                break;
                            case 'scale':
                                if (nums.length === 1) nums[1] = nums[0];
                                matrix = matrix.scale(nums[0], nums[1]);
                                break;
                            case 'rotate':
                                matrix = matrix.rotate(nums[0]);
                                break;
                            case 'skewX':
                                matrix = matrix.skewX(nums[0]);
                                break;
                            case 'skewY':
                                matrix = matrix.skewY(nums[0]);
                                break;
                        }
                    });
                }
                
                return matrix;
            }
            
            /**
             * Создает HTML элемент из SVG элемента
             */
            function createHTMLElement(svgElement, transform, svgRect) {
                const tagName = svgElement.tagName.toLowerCase();
                let htmlElement = null;
                
                // Получаем bounding box элемента
                let bbox;
                try {
                    bbox = svgElement.getBBox();
                } catch (e) {
                    console.warn('Ошибка получения BBox:', e);
                    return null;
                }
                
                // Применяем трансформацию к координатам
                const point = new DOMPoint(bbox.x, bbox.y);
                const transformedPoint = transform.transformPoint(point);
                
                // Вычисляем позицию относительно родительского контейнера
                const left = transformedPoint.x;
                const top = transformedPoint.y;
                const right = svgRect.width - (bbox.x + bbox.width);
                const bottom = svgRect.height - (bbox.y + bbox.height);
                
                // Выбираем CSS свойство с меньшим значением
                const useLeft = Math.abs(left) <= Math.abs(right);
                const useTop = Math.abs(top) <= Math.abs(bottom);
                
                // Создаем HTML элемент в зависимости от типа SVG элемента
                switch(tagName) {
                    case 'circle':
                        htmlElement = document.createElement('div');
                        htmlElement.style.borderRadius = '50%';
                        htmlElement.style.width = bbox.width + 'px';
                        htmlElement.style.height = bbox.height + 'px';
                        htmlElement.style.background = svgElement.getAttribute('fill') || 'black';
                        break;
                        
                    case 'rect':
                        htmlElement = document.createElement('div');
                        htmlElement.style.width = bbox.width + 'px';
                        htmlElement.style.height = bbox.height + 'px';
                        htmlElement.style.background = svgElement.getAttribute('fill') || 'black';
                        
                        // Обрабатываем скругление углов
                        const rx = svgElement.getAttribute('rx');
                        const ry = svgElement.getAttribute('ry');
                        if (rx) htmlElement.style.borderRadius = rx + 'px';
                        if (ry) htmlElement.style.borderRadius = ry + 'px';
                        break;
                        
                    case 'ellipse':
                        htmlElement = document.createElement('div');
                        htmlElement.style.borderRadius = '50%';
                        htmlElement.style.width = bbox.width + 'px';
                        htmlElement.style.height = bbox.height + 'px';
                        htmlElement.style.background = svgElement.getAttribute('fill') || 'black';
                        break;
                        
                    case 'line':
                        htmlElement = document.createElement('div');
                        htmlElement.style.height = '1px';
                        htmlElement.style.width = bbox.width + 'px';
                        htmlElement.style.background = svgElement.getAttribute('stroke') || 'black';
                        htmlElement.style.transform = `rotate(${Math.atan2(bbox.height, bbox.width)}rad)`;
                        break;
                        
                    case 'text':
                        htmlElement = document.createElement('div');
                        htmlElement.textContent = svgElement.textContent;
                        htmlElement.style.color = svgElement.getAttribute('fill') || 'black';
                        htmlElement.style.fontSize = svgElement.getAttribute('font-size') || '16px';
                        htmlElement.style.fontFamily = svgElement.getAttribute('font-family') || 'sans-serif';
                        break;
                        
                    default:
                        htmlElement = document.createElement('div');
                        htmlElement.style.width = bbox.width + 'px';
                        htmlElement.style.height = bbox.height + 'px';
                        htmlElement.style.background = svgElement.getAttribute('fill') || 'transparent';
                }
                
                if (htmlElement) {
                    // Применяем CSS свойства
                    applyCSSProperties(svgElement, htmlElement);
                    
                    // Устанавливаем позицию
                    if (useLeft) {
                        htmlElement.style.left = left + 'px';
                    } else {
                        htmlElement.style.right = right + 'px';
                    }
                    
                    if (useTop) {
                        htmlElement.style.top = top + 'px';
                    } else {
                        htmlElement.style.bottom = bottom + 'px';
                    }
                    
                    // Устанавливаем общие стили
                    htmlElement.style.position = 'absolute';
                    htmlElement.style.boxSizing = 'border-box';
                    
                    // Копируем классы и id
                    if (svgElement.id) htmlElement.id = svgElement.id;
                    if (svgElement.className) {
                        htmlElement.className = svgElement.className.baseVal || svgElement.className;
                    }
                }
                
                return htmlElement;
            }
            
            /**
             * Применяет CSS свойства из SVG элемента к HTML элементу
             */
            function applyCSSProperties(svgElement, htmlElement) {
                // Копируем инлайн стили
                const style = svgElement.getAttribute('style');
                if (style) {
                    style.split(';').forEach(rule => {
                        const [prop, value] = rule.split(':').map(s => s.trim());
                        if (prop && value) {
                            // Конвертируем SVG CSS свойства в обычные CSS
                            const cssProp = convertSVGCSSProperty(prop);
                            htmlElement.style[cssProp] = value;
                        }
                    });
                }
                
                // Копируем отдельные атрибуты стилей
                const styleAttributes = [
                    'fill', 'stroke', 'stroke-width', 'opacity',
                    'font-family', 'font-size', 'font-weight',
                    'font-style', 'text-decoration'
                ];
                
                styleAttributes.forEach(attr => {
                    const value = svgElement.getAttribute(attr);
                    if (value) {
                        const cssProp = convertSVGCSSProperty(attr);
                        htmlElement.style[cssProp] = value;
                    }
                });
            }
            
            /**
             * Конвертирует SVG CSS свойства в обычные CSS
             */
            function convertSVGCSSProperty(svgProp) {
                const propertyMap = {
                    'fill': 'background',
                    'fill-opacity': 'opacity',
                    'stroke': 'border-color',
                    'stroke-width': 'border-width',
                    'font-family': 'fontFamily',
                    'font-size': 'fontSize',
                    'font-weight': 'fontWeight',
                    'font-style': 'fontStyle',
                    'text-decoration': 'textDecoration'
                };
                
                return propertyMap[svgProp] || svgProp;
            }
            
            // Запускаем конвертацию
            const newElements = processElement(svg);
            
            if (newElements && newElements.length) {
                // Очищаем контейнер
                container.innerHTML = '';
                
                // Добавляем новые элементы
                newElements.forEach(element => {
                    if (element) {
                        container.appendChild(element);
                    }
                });
                
                // Устанавливаем стили контейнера
                container.style.position = 'relative';
                container.style.width = svgRect.width + 'px';
                container.style.height = svgRect.height + 'px';
            }
        });
    }
    
    // Запускаем конвертацию после загрузки страницы
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', convertSVGtoHTML);
    } else {
        convertSVGtoHTML();
    }
    
    // Добавляем возможность ручного запуска
    window.convertSVGtoHTML = convertSVGtoHTML;
    
})();
```

## Как использовать:

1. **Вставьте код** в консоль браузера или добавьте в тег `<script>` на странице
2. **Убедитесь**, что SVG находятся внутри div элементов
3. **Код автоматически** конвертирует все SVG при загрузке страницы
4. **Для ручного запуска** используйте `window.convertSVGtoHTML()`

## Особенности:

- **Не конвертирует** `<path>` элементы
- **Схлопывает** группы `<g>`
- **Использует** DOMMatrix для точного преобразования трансформаций
- **Автоматически выбирает** между left/right и top/bottom для "резинового" дизайна
- **Конвертирует** SVG CSS свойства в обычные CSS
- **Сохраняет** классы и идентификаторы элементов

Код создает абсолютно позиционированные HTML элементы с CSS стилями, максимально соответствующими оригинальному SVG дизайну.