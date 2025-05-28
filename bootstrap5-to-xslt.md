# Python-скрипт для генерации XSLT-шаблонов из Bootstrap 5.3 HTML

Вот скрипт, который анализирует HTML-код с компонентами Bootstrap 5.3 и генерирует соответствующие XSLT-шаблоны:

```python
from bs4 import BeautifulSoup
import re
from typing import Dict, List, Optional

class BootstrapToXSLTConverter:
    def __init__(self):
        self.components = []
        self.current_id = 1
        self.indent = "    "
        
    def convert_html_to_xslt(self, html: str) -> str:
        """Основной метод преобразования HTML в XSLT"""
        soup = BeautifulSoup(html, 'html.parser')
        self._extract_components(soup)
        
        xslt = self._generate_xslt_header()
        xslt += self._generate_components()
        xslt += self._generate_xslt_footer()
        
        return xslt
    
    def _extract_components(self, soup: BeautifulSoup) -> None:
        """Идентифицирует компоненты Bootstrap в HTML"""
        # Находим все компоненты по классам Bootstrap
        for component in soup.find_all(class_=re.compile(r'\bbtn\b|\balert\b|\bcard\b|\bmodal\b|\bnavbar\b')):
            component_type = self._detect_component_type(component)
            if component_type:
                self.components.append({
                    'type': component_type,
                    'html': str(component),
                    'id': f"comp_{self.current_id}"
                })
                self.current_id += 1
    
    def _detect_component_type(self, component) -> Optional[str]:
        """Определяет тип компонента Bootstrap"""
        classes = component.get('class', [])
        
        if any(c.startswith('btn') for c in classes):
            return 'button'
        elif any(c.startswith('alert') for c in classes):
            return 'alert'
        elif any(c.startswith('card') for c in classes):
            return 'card'
        elif any(c.startswith('modal') for c in classes):
            return 'modal'
        elif any(c.startswith('navbar') for c in classes):
            return 'navbar'
        elif any(c.startswith('dropdown') for c in classes):
            return 'dropdown'
        elif any(c.startswith('carousel') for c in classes):
            return 'carousel'
        elif any(c.startswith('accordion') for c in classes):
            return 'accordion'
        
        return None
    
    def _generate_xslt_header(self) -> str:
        """Генерирует заголовок XSLT-документа"""
        return f"""<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:bs="http://bootstrap.com/components">
    
    <xsl:output method="html" doctype-system="about:legacy-compat" encoding="UTF-8" indent="yes"/>
    <xsl:strip-space elements="*"/>
    
    <!-- Bootstrap CSS -->
    <xsl:template name="bootstrap-css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    </xsl:template>
    
    <!-- Bootstrap JS Bundle -->
    <xsl:template name="bootstrap-js">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </xsl:template>

"""
    
    def _generate_components(self) -> str:
        """Генерирует XSLT-шаблоны для всех найденных компонентов"""
        components_xslt = ""
        for component in self.components:
            components_xslt += self._generate_component_template(component) + "\n\n"
        return components_xslt
    
    def _generate_component_template(self, component: Dict) -> str:
        """Генерирует XSLT-шаблон для конкретного компонента"""
        component_type = component['type']
        html = component['html']
        
        # Анализируем HTML компонента
        soup = BeautifulSoup(html, 'html.parser')
        element = soup.find()
        
        # Извлекаем параметры из классов
        params = self._extract_params_from_classes(element.get('class', []))
        
        # Генерируем параметры шаблона
        params_declaration = "\n".join(
            f'{self.indent*2}<xsl:param name="{p}" select="\'\'"/>'
            for p in params.keys()
        )
        
        # Генерируем применение параметров
        params_application = ""
        for param, default in params.items():
            params_application += f'\n{self.indent*3}<xsl:attribute name="class">\n'
            params_application += f'{self.indent*4}<xsl:value-of select="\'{default}\'"/>\n'
            params_application += f'{self.indent*4}<xsl:if test="${param}">\n'
            params_application += f'{self.indent*5}<xsl:value-of select="\' \'"/>\n'
            params_application += f'{self.indent*5}<xsl:value-of select="${param}"/>\n'
            params_application += f'{self.indent*4}</xsl:if>\n'
            params_application += f'{self.indent*3}</xsl:attribute>\n'
        
        # Генерируем шаблон
        return f"""    <!-- Bootstrap {component_type.capitalize()} Component -->
    <xsl:template name="bs-{component_type}">
{params_declaration}
        <xsl:element name="{element.name}">
{params_application}
            <xsl:apply-templates select="node()"/>
        </xsl:element>
    </xsl:template>"""
    
    def _extract_params_from_classes(self, classes: List[str]) -> Dict[str, str]:
        """Извлекает параметры из классов Bootstrap"""
        params = {}
        for cls in classes:
            if cls.startswith(('btn-', 'alert-', 'bg-', 'text-', 'border-')):
                param_name = cls.replace('-', '_')
                params[param_name] = cls
        return params
    
    def _generate_xslt_footer(self) -> str:
        """Генерирует завершающую часть XSLT-документа"""
        return "</xsl:stylesheet>"


# Пример использования
if __name__ == "__main__":
    # Пример HTML с компонентами Bootstrap 5.3
    bootstrap_html = """
    <button class="btn btn-primary">Primary Button</button>
    <div class="alert alert-warning alert-dismissible fade show">Warning Alert</div>
    <div class="card" style="width: 18rem;">
        <img src="..." class="card-img-top" alt="...">
        <div class="card-body">
            <h5 class="card-title">Card title</h5>
            <p class="card-text">Some card content.</p>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
        </div>
    </nav>
    """
    
    converter = BootstrapToXSLTConverter()
    xslt_result = converter.convert_html_to_xslt(bootstrap_html)
    
    # Сохраняем результат в файл
    with open('bootstrap-components.xsl', 'w', encoding='utf-8') as f:
        f.write(xslt_result)
    
    print("XSLT-шаблоны успешно сгенерированы в файл 'bootstrap-components.xsl'")
```

## Как работает скрипт:

1. **Анализ HTML**:
   - Использует BeautifulSoup для парсинга HTML
   - Идентифицирует компоненты Bootstrap по характерным классам (btn-, alert-, card- и т.д.)

2. **Генерация XSLT**:
   - Создает отдельный шаблон для каждого типа компонента
   - Автоматически определяет параметры из классов Bootstrap
   - Генерирует параметризованные XSLT-шаблоны

3. **Особенности**:
   - Поддерживает Bootstrap 5.3+
   - Сохраняет семантику компонентов
   - Позволяет динамически изменять классы через параметры XSLT

## Требования:
- Python 3.7+
- Установленные зависимости:
  ```
  pip install beautifulsoup4
  ```

## Расширение функциональности:
Вы можете добавить обработку дополнительных компонентов Bootstrap, расширив метод `_detect_component_type()` и добавив соответствующие шаблоны генерации.

Скрипт создает базовые XSLT-шаблоны, которые можно дополнительно настроить под конкретные нужды проекта.



# В Python есть несколько способов прочитать текстовый файл в переменную. Вот основные методы:

### 1. Простое чтение всего файла (рекомендуемый способ)
```python
with open('file.txt', 'r', encoding='utf-8') as file:
    content = file.read()

print(content)
```

### 2. Чтение по строкам в список
```python
with open('file.txt', 'r', encoding='utf-8') as file:
    lines = file.readlines()  # Возвращает список строк

print(lines)
```

### 3. Чтение файла построчно (эффективно для больших файлов)
```python
with open('file.txt', 'r', encoding='utf-8') as file:
    for line in file:
        print(line.strip())  # strip() удаляет символы переноса строки
```

### 4. Чтение файла в одну строку (без использования менеджера контекста)
```python
file = open('file.txt', 'r', encoding='utf-8')
content = file.read()
file.close()

print(content)
```

### 5. Чтение с обработкой возможных ошибок
```python
try:
    with open('file.txt', 'r', encoding='utf-8') as file:
        content = file.read()
    print(content)
except FileNotFoundError:
    print("Файл не найден")
except IOError:
    print("Ошибка чтения файла")
```

### 6. Чтение файла из другого каталога
```python
import os

file_path = os.path.join('folder', 'subfolder', 'file.txt')
with open(file_path, 'r', encoding='utf-8') as file:
    content = file.read()
```

### 7. Чтение с разными кодировками (если utf-8 не работает)
```python
encodings = ['utf-8', 'windows-1251', 'iso-8859-1']

for encoding in encodings:
    try:
        with open('file.txt', 'r', encoding=encoding) as file:
            content = file.read()
        break
    except UnicodeDecodeError:
        continue

print(content)
```

### Важные замечания:
1. Всегда лучше использовать конструкцию `with` (менеджер контекста), так как она автоматически закрывает файл после использования
2. Указывайте кодировку (`encoding='utf-8'`), чтобы избежать проблем с разными платформами
3. Для больших файлов лучше читать построчно или блоками
4. В Windows может потребоваться параметр `newline=''` для корректной обработки переносов строк

Выберите подходящий метод в зависимости от вашей конкретной задачи и размера файла.
