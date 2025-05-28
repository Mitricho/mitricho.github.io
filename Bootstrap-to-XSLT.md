# Подробное руководство по преобразованию Bootstrap в XSLT

Преобразование шаблонов Bootstrap 5 в XSLT требует понимания обеих технологий. Вот детальный подход к этому процессу.

## 1. Архитектура решения

### Структура проекта
```
project/
├── xsl/
│   ├── bootstrap-core.xsl      # Базовые компоненты
│   ├── bootstrap-components.xsl # Комплексные компоненты
│   └── main.xsl               # Главный преобразователь
├── css/
│   └── bootstrap.min.css      # CSS Bootstrap 5
└── output/                    # Генерируемые HTML-файлы
```

## 2. Основные принципы преобразования

### Динамизация статических классов
```xslt
<!-- Вместо фиксированного класса -->
<div class="btn btn-primary">

<!-- Используем параметризованный вариант -->
<xsl:template name="button">
  <xsl:param name="style" select="'primary'"/>
  <xsl:param name="text"/>
  
  <button class="btn btn-{$style}">
    <xsl:value-of select="$text"/>
  </button>
</xsl:template>
```

### Обработка условий
```xslt
<xsl:template name="alert">
  <xsl:param name="type"/>
  <xsl:param name="dismissible" select="false()"/>
  
  <div class="alert alert-{$type}">
    <xsl:if test="$dismissible">
      <xsl:attribute name="class">alert alert-{$type} alert-dismissible fade show</xsl:attribute>
      <button type="button" class="btn-close" data-bs-dismiss="alert"/>
    </xsl:if>
    <xsl:apply-templates/>
  </div>
</xsl:template>
```

## 3. Полное преобразование компонентов

### Навигационное меню (Navbar)
**Bootstrap HTML:**
```html
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Navbar</a>
    <button class="navbar-toggler" type="button">...</button>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" href="#">Home</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
```

**XSLT-версия:**
```xslt
<xsl:template name="navbar">
  <xsl:param name="brand"/>
  <xsl:param name="items"/>
  <xsl:param name="theme" select="'light'"/>
  <xsl:param name="background" select="'light'"/>
  
  <nav class="navbar navbar-expand-lg navbar-{$theme} bg-{$background}">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <xsl:value-of select="$brand"/>
      </a>
      
      <xsl:if test="$items">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse">
          <span class="navbar-toggler-icon"/>
        </button>
        
        <div class="collapse navbar-collapse">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <xsl:for-each select="$items/item">
              <li class="nav-item">
                <a class="nav-link">
                  <xsl:if test="position() = 1">
                    <xsl:attribute name="class">nav-link active</xsl:attribute>
                  </xsl:if>
                  <xsl:attribute name="href">
                    <xsl:value-of select="@href"/>
                  </xsl:attribute>
                  <xsl:value-of select="text()"/>
                </a>
              </li>
            </xsl:for-each>
          </ul>
        </div>
      </xsl:if>
    </div>
  </nav>
</xsl:template>
```

## 4. Работа с динамическими данными

### Карточки (Cards)
```xslt
<xsl:template match="products">
  <div class="row row-cols-1 row-cols-md-3 g-4">
    <xsl:for-each select="product">
      <div class="col">
        <div class="card h-100">
          <img src="{image}" class="card-img-top" alt="{name}"/>
          <div class="card-body">
            <h5 class="card-title">
              <xsl:value-of select="name"/>
            </h5>
            <p class="card-text">
              <xsl:value-of select="description"/>
            </p>
          </div>
          <div class="card-footer">
            <small class="text-muted">
              Price: $<xsl:value-of select="price"/>
            </small>
          </div>
        </div>
      </div>
    </xsl:for-each>
  </div>
</xsl:template>
```

## 5. Автоматизация процесса

### XSLT-шаблонизатор
```xslt
<xsl:stylesheet version="2.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:bs="http://bootstrap.com/components">

  <!-- Импорт всех компонентов Bootstrap -->
  <xsl:include href="bootstrap-button.xsl"/>
  <xsl:include href="bootstrap-card.xsl"/>
  <xsl:include href="bootstrap-navbar.xsl"/>
  
  <!-- Корневой шаблон -->
  <xsl:template match="/">
    <html>
      <head>
        <link href="css/bootstrap.min.css" rel="stylesheet"/>
      </head>
      <body>
        <xsl:apply-templates select="page/content"/>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
```

## 6. Продвинутые техники

### Модальные окна с параметрами
```xslt
<xsl:template name="modal">
  <xsl:param name="id"/>
  <xsl:param name="title"/>
  <xsl:param name="content"/>
  
  <div class="modal fade" id="{$id}" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <xsl:value-of select="$title"/>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"/>
        </div>
        <div class="modal-body">
          <xsl:copy-of select="$content"/>
        </div>
      </div>
    </div>
  </div>
</xsl:template>
```

### Формы с валидацией
```xslt
<xsl:template name="form-input">
  <xsl:param name="type" select="'text'"/>
  <xsl:param name="id"/>
  <xsl:param name="label"/>
  <xsl:param name="required" select="false()"/>
  
  <div class="mb-3">
    <label for="{$id}" class="form-label">
      <xsl:value-of select="$label"/>
      <xsl:if test="$required">
        <span class="text-danger">*</span>
      </xsl:if>
    </label>
    <input type="{$type}" class="form-control" id="{$id}">
      <xsl:if test="$required">
        <xsl:attribute name="required">required</xsl:attribute>
      </xsl:if>
    </input>
  </div>
</xsl:template>
```

## 7. Инструменты и оптимизация

1. **Генератор XSLT из Bootstrap**:
   - Напишите скрипт (Python/Node.js), который анализирует HTML Bootstrap и генерирует XSLT-шаблоны

2. **Сниппеты для редакторов**:
   - Создайте коллекцию сниппетов для VS Code/Sublime Text для быстрой вставки компонентов

3. **Тестирование**:
   ```bash
   # Использование Saxon для трансформации
   java -jar saxon-he.jar -s:input.xml -xsl:main.xsl -o:output.html
   ```

4. **Оптимизация**:
   - Используйте `<xsl:import> вместо <xsl:include>` для переопределения шаблонов
   - Применяйте `<xsl:key>` для быстрого доступа к часто используемым данным

Такой подход позволяет создать полноценную систему для генерации Bootstrap-интерфейсов через XSLT-преобразования с поддержкой динамического контента.
