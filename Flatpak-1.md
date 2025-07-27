Чтобы собрать и упаковать ваш C++ проект из Qt Creator в формате Flatpak, выполните следующие шаги:

### 1. Установка необходимых инструментов
```bash
sudo apt install flatpak flatpak-builder
flatpak remote-add --if-not-exists flathub https://flathub.org/repo/flathub.flatpakrepo
```

### 2. Создайте структуру файлов для Flatpak
В корне вашего проекта создайте следующую структуру:
```
ваш_проект/
├── flatpak/
│   ├── your.app.id.json (манифест)
│   └── your.app.id.appdata.xml (метаданные)
└── ... (исходные файлы проекта)
```

### 3. Создайте манифест (your.app.id.json)
Пример для Qt-приложения:
```json
{
  "app-id": "org.yourname.yourapp",
  "runtime": "org.kde.Platform",
  "runtime-version": "6.5",
  "sdk": "org.kde.Sdk",
  "command": "yourapp",
  "finish-args": [
    "--share=ipc",
    "--socket=wayland",
    "--socket=x11",
    "--device=dri"
  ],
  "modules": [
    {
      "name": "yourapp",
      "buildsystem": "cmake-ninja",
      "sources": [
        {
          "type": "dir",
          "path": ".."
        }
      ]
    }
  ]
}
```

### 4. Создайте файл метаданных (your.app.id.appdata.xml)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<component type="desktop">
  <id>org.yourname.yourapp</id>
  <metadata_license>MIT</metadata_license>
  <project_license>GPL-3.0</project_license>
  <name>Your App</name>
  <summary>Description of your app</summary>
  <description>
    <p>Detailed description here.</p>
  </description>
</component>
```

### 5. Сборка и установка
```bash
flatpak-builder --user --install build-dir flatpak/your.app.id.json --force-clean
```

### 6. Запуск приложения
```bash
flatpak run org.yourname.yourapp
```

### Дополнительные рекомендации:
1. Для Qt-приложений лучше использовать KDE Runtime (`org.kde.Platform`)
2. Убедитесь, что в вашем проекте есть `.desktop`-файл
3. Для сложных проектов может потребоваться добавить зависимости в манифест
4. Используйте `flatpak run --command=bash org.yourname.yourapp` для отладки

### Альтернативный способ (если проект уже собирается в Qt Creator):
1. Соберите проект как обычно
2. В манифесте укажите тип сборки как "simple" и укажите путь к собранному бинарнику
3. Добавьте все необходимые библиотеки и ресурсы

Для публикации на Flathub потребуется дополнительная подготовка, включая правильную настройку sandbox permissions.
