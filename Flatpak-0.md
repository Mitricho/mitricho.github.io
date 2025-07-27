При упаковке приложения в Flatpak важно правильно настроить sandbox permissions, чтобы обеспечить баланс между безопасностью и функциональностью. Flatpak по умолчанию запускает приложения в изолированной песочнице, и вам нужно явно запросить доступ к необходимым ресурсам.

### Основные разрешения (finish-args)

В манифесте (`your.app.id.json`) раздел `"finish-args"` определяет, какие ресурсы будут доступны приложению:

#### 1. **Базовые разрешения для GUI-приложений**
```json
"finish-args": [
    "--share=ipc",        // Общий IPC (межпроцессное взаимодействие)
    "--socket=wayland",   // Доступ к Wayland (для современных GUI)
    "--socket=x11",       // Доступ к X11 (для совместимости)
    "--device=dri",       // Аппаратное ускорение графики
    "--filesystem=xdg-download",  // Доступ к папке загрузок
    "--talk-name=org.freedesktop.Notifications"  // Системные уведомления
]
```

#### 2. **Доступ к файловой системе**
```json
"--filesystem=home",          // Вся домашняя директория (не рекомендуется)
"--filesystem=~/Documents",   // Только папка Documents
"--filesystem=xdg-data/appname",  // Специфичная папка данных
"--filesystem=/path/to/need:ro",  // Только чтение (read-only)
"--persist=.appname"          // Постоянное хранилище в ~/.var/app/
```

#### 3. **Сетевые разрешения**
```json
"--share=network",       // Полный доступ к сети
"--socket=ssh-auth",     // Доступ к SSH-агенту
"--system-talk-name=org.freedesktop.NetworkManager"  // Управление сетью
```

#### 4. **Аппаратный доступ**
```json
"--device=all",          // Все устройства (опасно!)
"--device=input",        // Устройства ввода (клавиатура/мышь)
"--device=kvm",          // Виртуализация
"--talk-name=org.freedesktop.portal.Camera"  // Камера
```

### Практические примеры

#### Для медиаплеера:
```json
"finish-args": [
    "--share=network",
    "--filesystem=xdg-music:ro",
    "--filesystem=xdg-videos",
    "--socket=pulseaudio",
    "--device=dri"
]
```

#### Для разработчика:
```json
"finish-args": [
    "--share=network",
    "--filesystem=host:ro",
    "--socket=ssh-auth",
    "--talk-name=org.freedesktop.Flatpak"
]
```

### Важные принципы:

1. **Минимальные привилегии**: Давайте только те права, которые действительно нужны
2. **Избегайте `--filesystem=host`**: Это полностью отключает sandbox
3. **Используйте порталы (Portals)** для безопасного доступа к ресурсам:
   - `org.freedesktop.portal.FileChooser` - выбор файлов
   - `org.freedesktop.portal.OpenURI` - открытие URI
   - `org.freedesktop.portal.Print` - печать

### Проверка разрешений

1. Посмотреть текущие разрешения:
   ```bash
   flatpak info org.yourname.yourapp --show-permissions
   ```

2. Запустить с временными разрешениями:
   ```bash
   flatpak run --filesystem=/path/you/need org.yourname.yourapp
   ```

### Отладка проблем

Если приложение не работает из-за ограничений sandbox:
1. Проверьте журналы:
   ```bash
   journalctl -f -o cat
   ```
2. Запустите в терминале для просмотра ошибок:
   ```bash
   flatpak run org.yourname.yourapp
   ```
3. Используйте `flatpak-spawn` для взаимодействия с хост-системой

Для сложных случаев можно временно отключить sandbox (только для отладки!):
```bash
flatpak run --env=GTK_DEBUG=all --devel org.yourname.yourapp
```

Правильная настройка разрешений критически важна для безопасности и пользовательского опыта. Всегда тестируйте приложение с минимально необходимыми разрешениями.
