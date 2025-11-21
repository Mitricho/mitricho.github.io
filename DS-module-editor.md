# Редактор модульных прямоугольников

Создам интерактивный редактор с модульной сеткой, где можно создавать и настраивать прямоугольники с различными свойствами.

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактор модульных прямоугольников</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            padding: 20px;
        }
        
        .container {
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        header {
            background-color: #4a6fa5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        h1 {
            margin-bottom: 10px;
        }
        
        .main-content {
            display: flex;
            flex-wrap: wrap;
            padding: 20px;
            gap: 20px;
        }
        
        .canvas-container {
            flex: 1;
            min-width: 500px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            background-color: #f9f9f9;
        }
        
        #canvas {
            display: block;
            background-color: white;
            cursor: crosshair;
        }
        
        .controls {
            flex: 0 0 300px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .control-group {
            background-color: #f0f0f0;
            border-radius: 5px;
            padding: 15px;
        }
        
        .control-group h3 {
            margin-bottom: 10px;
            color: #4a6fa5;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input, select, button {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button {
            background-color: #4a6fa5;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        
        button:hover {
            background-color: #3a5a80;
        }
        
        .color-input {
            display: flex;
            gap: 10px;
        }
        
        .color-input input {
            flex: 1;
        }
        
        .color-preview {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .grid-settings {
            display: flex;
            gap: 10px;
        }
        
        .grid-settings input {
            flex: 1;
        }
        
        .tools {
            display: flex;
            gap: 10px;
        }
        
        .tools button {
            flex: 1;
        }
        
        .selected-rectangle {
            background-color: #e6f2ff;
        }
        
        footer {
            text-align: center;
            padding: 15px;
            background-color: #f0f0f0;
            color: #666;
            font-size: 0.9em;
        }
        
        @media (max-width: 900px) {
            .main-content {
                flex-direction: column;
            }
            
            .canvas-container {
                min-width: 100%;
            }
            
            .controls {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Редактор модульных прямоугольников</h1>
            <p>Создавайте и настраивайте прямоугольники на модульной сетке</p>
        </header>
        
        <div class="main-content">
            <div class="canvas-container">
                <canvas id="canvas" width="800" height="600"></canvas>
            </div>
            
            <div class="controls">
                <div class="control-group">
                    <h3>Настройки сетки</h3>
                    <div class="form-group">
                        <label for="gridSize">Размер сетки (пикселей):</label>
                        <div class="grid-settings">
                            <input type="number" id="gridSizeX" value="16" min="4" max="64">
                            <input type="number" id="gridSizeY" value="16" min="4" max="64">
                        </div>
                    </div>
                    <button id="applyGrid">Применить сетку</button>
                </div>
                
                <div class="control-group">
                    <h3>Создание прямоугольника</h3>
                    <div class="form-group">
                        <label for="rectWidth">Ширина (в модулях):</label>
                        <input type="number" id="rectWidth" value="4" min="1" max="32">
                    </div>
                    <div class="form-group">
                        <label for="rectHeight">Высота (в модулях):</label>
                        <input type="number" id="rectHeight" value="4" min="1" max="32">
                    </div>
                    <button id="createRect">Создать прямоугольник</button>
                </div>
                
                <div class="control-group selected-rectangle" id="rectProperties">
                    <h3>Свойства прямоугольника</h3>
                    <div class="form-group">
                        <label for="rectText">Текст:</label>
                        <input type="text" id="rectText" placeholder="Введите текст">
                    </div>
                    <div class="form-group">
                        <label for="textAlign">Выравнивание текста:</label>
                        <select id="textAlign">
                            <option value="left">По левому краю</option>
                            <option value="center" selected>По центру</option>
                            <option value="right">По правому краю</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="borderRadius">Скругление углов:</label>
                        <input type="range" id="borderRadius" min="0" max="50" value="0">
                        <span id="borderRadiusValue">0%</span>
                    </div>
                    <div class="form-group">
                        <label for="borderWidth">Толщина границы:</label>
                        <input type="range" id="borderWidth" min="0" max="10" value="1">
                        <span id="borderWidthValue">1px</span>
                    </div>
                    <div class="form-group">
                        <label>Цвет границы:</label>
                        <div class="color-input">
                            <input type="color" id="borderColor" value="#000000">
                            <div class="color-preview" id="borderColorPreview" style="background-color: #000000;"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="fillType">Тип заливки:</label>
                        <select id="fillType">
                            <option value="color">Цвет</option>
                            <option value="gradient">Градиент</option>
                            <option value="image">Изображение</option>
                        </select>
                    </div>
                    <div class="form-group" id="colorFill">
                        <label>Цвет заливки:</label>
                        <div class="color-input">
                            <input type="color" id="fillColor" value="#4a6fa5">
                            <div class="color-preview" id="fillColorPreview" style="background-color: #4a6fa5;"></div>
                        </div>
                    </div>
                    <div class="form-group" id="gradientFill" style="display: none;">
                        <label>Градиент:</label>
                        <div class="color-input">
                            <input type="color" id="gradientColor1" value="#4a6fa5">
                            <div class="color-preview" id="gradientColor1Preview" style="background-color: #4a6fa5;"></div>
                        </div>
                        <div class="color-input">
                            <input type="color" id="gradientColor2" value="#6fa5d0">
                            <div class="color-preview" id="gradientColor2Preview" style="background-color: #6fa5d0;"></div>
                        </div>
                    </div>
                    <div class="form-group" id="imageFill" style="display: none;">
                        <label>URL изображения:</label>
                        <input type="text" id="imageUrl" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="tools">
                        <button id="updateRect">Применить изменения</button>
                        <button id="deleteRect" style="background-color: #d9534f;">Удалить</button>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <p>Редактор модульных прямоугольников &copy; 2023</p>
        </footer>
    </div>

    <script>
        // Основные переменные
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        let gridSizeX = 16;
        let gridSizeY = 16;
        let rectangles = [];
        let selectedRect = null;
        let isDrawing = false;
        let startX, startY;
        
        // Элементы управления
        const gridSizeXInput = document.getElementById('gridSizeX');
        const gridSizeYInput = document.getElementById('gridSizeY');
        const applyGridBtn = document.getElementById('applyGrid');
        const rectWidthInput = document.getElementById('rectWidth');
        const rectHeightInput = document.getElementById('rectHeight');
        const createRectBtn = document.getElementById('createRect');
        const rectTextInput = document.getElementById('rectText');
        const textAlignSelect = document.getElementById('textAlign');
        const borderRadiusInput = document.getElementById('borderRadius');
        const borderRadiusValue = document.getElementById('borderRadiusValue');
        const borderWidthInput = document.getElementById('borderWidth');
        const borderWidthValue = document.getElementById('borderWidthValue');
        const borderColorInput = document.getElementById('borderColor');
        const borderColorPreview = document.getElementById('borderColorPreview');
        const fillTypeSelect = document.getElementById('fillType');
        const fillColorInput = document.getElementById('fillColor');
        const fillColorPreview = document.getElementById('fillColorPreview');
        const gradientColor1Input = document.getElementById('gradientColor1');
        const gradientColor1Preview = document.getElementById('gradientColor1Preview');
        const gradientColor2Input = document.getElementById('gradientColor2');
        const gradientColor2Preview = document.getElementById('gradientColor2Preview');
        const imageUrlInput = document.getElementById('imageUrl');
        const updateRectBtn = document.getElementById('updateRect');
        const deleteRectBtn = document.getElementById('deleteRect');
        
        // Класс прямоугольника
        class Rectangle {
            constructor(x, y, width, height) {
                this.x = x;
                this.y = y;
                this.width = width;
                this.height = height;
                this.text = '';
                this.textAlign = 'center';
                this.borderRadius = 0;
                this.borderWidth = 1;
                this.borderColor = '#000000';
                this.fillType = 'color';
                this.fillColor = '#4a6fa5';
                this.gradientColor1 = '#4a6fa5';
                this.gradientColor2 = '#6fa5d0';
                this.imageUrl = '';
                this.image = null;
            }
            
            // Проверка, находится ли точка внутри прямоугольника
            contains(x, y) {
                return x >= this.x && x <= this.x + this.width && 
                       y >= this.y && y <= this.y + this.height;
            }
            
            // Отрисовка прямоугольника
            draw() {
                ctx.save();
                
                // Создание пути для прямоугольника со скругленными углами
                const radius = Math.min(this.borderRadius, this.width / 2, this.height / 2);
                ctx.beginPath();
                ctx.moveTo(this.x + radius, this.y);
                ctx.lineTo(this.x + this.width - radius, this.y);
                ctx.quadraticCurveTo(this.x + this.width, this.y, this.x + this.width, this.y + radius);
                ctx.lineTo(this.x + this.width, this.y + this.height - radius);
                ctx.quadraticCurveTo(this.x + this.width, this.y + this.height, this.x + this.width - radius, this.y + this.height);
                ctx.lineTo(this.x + radius, this.y + this.height);
                ctx.quadraticCurveTo(this.x, this.y + this.height, this.x, this.y + this.height - radius);
                ctx.lineTo(this.x, this.y + radius);
                ctx.quadraticCurveTo(this.x, this.y, this.x + radius, this.y);
                ctx.closePath();
                
                // Заполнение прямоугольника
                if (this.fillType === 'color') {
                    ctx.fillStyle = this.fillColor;
                    ctx.fill();
                } else if (this.fillType === 'gradient') {
                    const gradient = ctx.createLinearGradient(
                        this.x, this.y, 
                        this.x + this.width, this.y + this.height
                    );
                    gradient.addColorStop(0, this.gradientColor1);
                    gradient.addColorStop(1, this.gradientColor2);
                    ctx.fillStyle = gradient;
                    ctx.fill();
                } else if (this.fillType === 'image' && this.image) {
                    ctx.fill();
                    ctx.clip();
                    ctx.drawImage(this.image, this.x, this.y, this.width, this.height);
                }
                
                // Рисование границы
                if (this.borderWidth > 0) {
                    ctx.strokeStyle = this.borderColor;
                    ctx.lineWidth = this.borderWidth;
                    ctx.stroke();
                }
                
                // Рисование текста
                if (this.text) {
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '14px Arial';
                    ctx.textBaseline = 'middle';
                    
                    let textX;
                    if (this.textAlign === 'left') {
                        textX = this.x + 10;
                        ctx.textAlign = 'left';
                    } else if (this.textAlign === 'right') {
                        textX = this.x + this.width - 10;
                        ctx.textAlign = 'right';
                    } else {
                        textX = this.x + this.width / 2;
                        ctx.textAlign = 'center';
                    }
                    
                    const textY = this.y + this.height / 2;
                    ctx.fillText(this.text, textX, textY);
                }
                
                ctx.restore();
                
                // Выделение выбранного прямоугольника
                if (this === selectedRect) {
                    ctx.strokeStyle = '#ff0000';
                    ctx.lineWidth = 2;
                    ctx.setLineDash([5, 5]);
                    ctx.strokeRect(this.x - 2, this.y - 2, this.width + 4, this.height + 4);
                    ctx.setLineDash([]);
                }
            }
        }
        
        // Инициализация
        function init() {
            drawGrid();
            setupEventListeners();
        }
        
        // Отрисовка сетки
        function drawGrid() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Рисование сетки
            ctx.strokeStyle = '#e0e0e0';
            ctx.lineWidth = 1;
            
            // Вертикальные линии
            for (let x = 0; x <= canvas.width; x += gridSizeX) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, canvas.height);
                ctx.stroke();
            }
            
            // Горизонтальные линии
            for (let y = 0; y <= canvas.height; y += gridSizeY) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(canvas.width, y);
                ctx.stroke();
            }
            
            // Отрисовка всех прямоугольников
            rectangles.forEach(rect => rect.draw());
        }
        
        // Настройка обработчиков событий
        function setupEventListeners() {
            // Применение настроек сетки
            applyGridBtn.addEventListener('click', () => {
                gridSizeX = parseInt(gridSizeXInput.value);
                gridSizeY = parseInt(gridSizeYInput.value);
                drawGrid();
            });
            
            // Создание прямоугольника
            createRectBtn.addEventListener('click', () => {
                const width = parseInt(rectWidthInput.value) * gridSizeX;
                const height = parseInt(rectHeightInput.value) * gridSizeY;
                
                // Создаем прямоугольник в центре холста
                const x = Math.floor((canvas.width / 2 - width / 2) / gridSizeX) * gridSizeX;
                const y = Math.floor((canvas.height / 2 - height / 2) / gridSizeY) * gridSizeY;
                
                const rect = new Rectangle(x, y, width, height);
                rectangles.push(rect);
                selectedRect = rect;
                updatePropertiesPanel();
                drawGrid();
            });
            
            // Обработка событий мыши на холсте
            canvas.addEventListener('mousedown', handleMouseDown);
            canvas.addEventListener('mousemove', handleMouseMove);
            canvas.addEventListener('mouseup', handleMouseUp);
            
            // Обновление свойств прямоугольника
            updateRectBtn.addEventListener('click', updateRectangle);
            
            // Удаление прямоугольника
            deleteRectBtn.addEventListener('click', () => {
                if (selectedRect) {
                    const index = rectangles.indexOf(selectedRect);
                    if (index !== -1) {
                        rectangles.splice(index, 1);
                        selectedRect = null;
                        drawGrid();
                    }
                }
            });
            
            // Обновление предпросмотра цветов
            borderColorInput.addEventListener('input', () => {
                borderColorPreview.style.backgroundColor = borderColorInput.value;
            });
            
            fillColorInput.addEventListener('input', () => {
                fillColorPreview.style.backgroundColor = fillColorInput.value;
            });
            
            gradientColor1Input.addEventListener('input', () => {
                gradientColor1Preview.style.backgroundColor = gradientColor1Input.value;
            });
            
            gradientColor2Input.addEventListener('input', () => {
                gradientColor2Preview.style.backgroundColor = gradientColor2Input.value;
            });
            
            // Переключение типа заливки
            fillTypeSelect.addEventListener('change', () => {
                document.getElementById('colorFill').style.display = 'none';
                document.getElementById('gradientFill').style.display = 'none';
                document.getElementById('imageFill').style.display = 'none';
                
                if (fillTypeSelect.value === 'color') {
                    document.getElementById('colorFill').style.display = 'block';
                } else if (fillTypeSelect.value === 'gradient') {
                    document.getElementById('gradientFill').style.display = 'block';
                } else if (fillTypeSelect.value === 'image') {
                    document.getElementById('imageFill').style.display = 'block';
                }
            });
            
            // Обновление значений ползунков
            borderRadiusInput.addEventListener('input', () => {
                borderRadiusValue.textContent = `${borderRadiusInput.value}%`;
            });
            
            borderWidthInput.addEventListener('input', () => {
                borderWidthValue.textContent = `${borderWidthInput.value}px`;
            });
        }
        
        // Обработка нажатия мыши
        function handleMouseDown(e) {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Проверяем, попали ли мы в существующий прямоугольник
            selectedRect = null;
            for (let i = rectangles.length - 1; i >= 0; i--) {
                if (rectangles[i].contains(x, y)) {
                    selectedRect = rectangles[i];
                    updatePropertiesPanel();
                    drawGrid();
                    return;
                }
            }
            
            // Если не попали в существующий прямоугольник, начинаем рисование нового
            isDrawing = true;
            startX = Math.floor(x / gridSizeX) * gridSizeX;
            startY = Math.floor(y / gridSizeY) * gridSizeY;
        }
        
        // Обработка перемещения мыши
        function handleMouseMove(e) {
            if (!isDrawing) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const endX = Math.floor(x / gridSizeX) * gridSizeX;
            const endY = Math.floor(y / gridSizeY) * gridSizeY;
            
            const width = Math.abs(endX - startX);
            const height = Math.abs(endY - startY);
            
            // Временный прямоугольник для предпросмотра
            drawGrid();
            ctx.strokeStyle = '#4a6fa5';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 5]);
            ctx.strokeRect(
                Math.min(startX, endX),
                Math.min(startY, endY),
                width,
                height
            );
            ctx.setLineDash([]);
        }
        
        // Обработка отпускания мыши
        function handleMouseUp(e) {
            if (!isDrawing) return;
            
            isDrawing = false;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const endX = Math.floor(x / gridSizeX) * gridSizeX;
            const endY = Math.floor(y / gridSizeY) * gridSizeY;
            
            const width = Math.abs(endX - startX);
            const height = Math.abs(endY - startY);
            
            // Создаем прямоугольник только если размер больше нуля
            if (width > 0 && height > 0) {
                const newRect = new Rectangle(
                    Math.min(startX, endX),
                    Math.min(startY, endY),
                    width,
                    height
                );
                rectangles.push(newRect);
                selectedRect = newRect;
                updatePropertiesPanel();
            }
            
            drawGrid();
        }
        
        // Обновление панели свойств
        function updatePropertiesPanel() {
            if (selectedRect) {
                rectTextInput.value = selectedRect.text;
                textAlignSelect.value = selectedRect.textAlign;
                borderRadiusInput.value = selectedRect.borderRadius;
                borderRadiusValue.textContent = `${selectedRect.borderRadius}%`;
                borderWidthInput.value = selectedRect.borderWidth;
                borderWidthValue.textContent = `${selectedRect.borderWidth}px`;
                borderColorInput.value = selectedRect.borderColor;
                borderColorPreview.style.backgroundColor = selectedRect.borderColor;
                fillTypeSelect.value = selectedRect.fillType;
                fillColorInput.value = selectedRect.fillColor;
                fillColorPreview.style.backgroundColor = selectedRect.fillColor;
                gradientColor1Input.value = selectedRect.gradientColor1;
                gradientColor1Preview.style.backgroundColor = selectedRect.gradientColor1;
                gradientColor2Input.value = selectedRect.gradientColor2;
                gradientColor2Preview.style.backgroundColor = selectedRect.gradientColor2;
                imageUrlInput.value = selectedRect.imageUrl;
                
                // Показываем соответствующую панель заливки
                document.getElementById('colorFill').style.display = 'none';
                document.getElementById('gradientFill').style.display = 'none';
                document.getElementById('imageFill').style.display = 'none';
                
                if (selectedRect.fillType === 'color') {
                    document.getElementById('colorFill').style.display = 'block';
                } else if (selectedRect.fillType === 'gradient') {
                    document.getElementById('gradientFill').style.display = 'block';
                } else if (selectedRect.fillType === 'image') {
                    document.getElementById('imageFill').style.display = 'block';
                }
            }
        }
        
        // Обновление свойств прямоугольника
        function updateRectangle() {
            if (!selectedRect) return;
            
            selectedRect.text = rectTextInput.value;
            selectedRect.textAlign = textAlignSelect.value;
            selectedRect.borderRadius = parseInt(borderRadiusInput.value);
            selectedRect.borderWidth = parseInt(borderWidthInput.value);
            selectedRect.borderColor = borderColorInput.value;
            selectedRect.fillType = fillTypeSelect.value;
            selectedRect.fillColor = fillColorInput.value;
            selectedRect.gradientColor1 = gradientColor1Input.value;
            selectedRect.gradientColor2 = gradientColor2Input.value;
            selectedRect.imageUrl = imageUrlInput.value;
            
            // Загрузка изображения, если указан URL
            if (selectedRect.fillType === 'image' && selectedRect.imageUrl) {
                const img = new Image();
                img.onload = function() {
                    selectedRect.image = img;
                    drawGrid();
                };
                img.onerror = function() {
                    alert('Не удалось загрузить изображение. Проверьте URL.');
                };
                img.src = selectedRect.imageUrl;
            }
            
            drawGrid();
        }
        
        // Запуск приложения
        init();
    </script>
</body>
</html>
```

## Особенности реализации:

1. **Модульная сетка**:
   - Настраиваемый размер сетки по горизонтали и вертикали
   - Все элементы привязываются к узлам сетки

2. **Создание прямоугольников**:
   - Можно создавать прямоугольники через панель управления с заданными размерами
   - Или рисовать прямоугольники прямо на холсте с помощью мыши

3. **Настройка свойств**:
   - Текст с различным выравниванием
   - Скругление углов (от 0% до 50%)
   - Настраиваемая граница (толщина и цвет)
   - Различные типы заливки: цвет, градиент, изображение

4. **Интерфейс**:
   - Интуитивно понятный интерфейс с группировкой элементов управления
   - Предпросмотр цветов
   - Адаптивный дизайн для разных размеров экрана

Редактор позволяет создавать и настраивать прямоугольники с различными свойствами, строго привязанные к модульной сетке.
