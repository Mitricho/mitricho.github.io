Ваш .deb-пакет, вероятно, правильно устанавливает файлы для MIME-типов (в /usr/share/mime/packages/) и иконок (в /usr/share/icons/), но в Ubuntu 24.04 (Noble Numbat) могут быть нюансы с обновлением кэшей из-за изменений в GNOME 46 и управлении системными ресурсами. В Ubuntu 20.04 (Focal Fossa) это работало smoother, потому что там старше версии shared-mime-info и меньше строгих проверок. Основная проблема — кэши MIME и иконок не обновляются автоматически после установки, или есть конфликт с desktop-файлами.

Вот что можно попробовать поэтапно, чтобы починить. Я опишу как для пользователя (после установки пакета), так и для доработки самого .deb (если вы его собираете). Все команды выполняйте в терминале. После каждого шага проверьте ассоциации: правой кнопкой на файле → "Открыть с помощью" или `xdg-mime query default application/x-your-mime` (замените на ваш MIME-тип).

### 1. **Вручное обновление кэшей после установки .deb**
   Это самый простой и частый фикс. В Ubuntu 24.04 postinst-скрипт .deb может не запускать обновления автоматически из-за изменений в dpkg-triggers или политик.
   
   - Установите пакет (если ещё не): `sudo dpkg -i your-package.deb` (или через GUI).
   - Обновите MIME-кэш:  
     `sudo update-mime-database /usr/share/mime`
   - Обновите кэш иконок (для стандартной темы hicolor; если ваша иконка в другой папке, укажите её):  
     `sudo gtk-update-icon-cache -f /usr/share/icons/hicolor`
     (Если иконка в custom-теме, например /usr/share/icons/your-theme: `sudo gtk-update-icon-cache -f /usr/share/icons/your-theme`)
   - Перезапустите файловый менеджер (Nautilus):  
     `nautilus -q` (или `killall nautilus`)
   - Если не помогло, перелогиньтесь или перезагрузитесь — GNOME иногда требует полного рестарта сессии для применения изменений.

   **Проверка:** Создайте тестовый файл вашего типа (например, touch test.your-ext) и посмотрите, применилась ли иконка и ассоциация в Nautilus.

### 2. **Проверьте установленные файлы пакета**
   Убедитесь, что .deb действительно кладёт нужные файлы на место.
   
   - `dpkg -L your-package-name | grep -E "(mime|icon|desktop)"`  
     Это покажет пути к XML-файлу MIME (должен быть в /usr/share/mime/packages/your-mime.xml), иконке (.png/.svg в /usr/share/icons/hicolor/[размер]/mimetypes/) и .desktop-файлу (в /usr/share/applications/).
   
   - Если файлы не там: Доработайте .deb (см. ниже). MIME-XML должен выглядеть примерно так:  
     ```xml
     <?xml version="1.0" encoding="UTF-8"?>
     <mime-info xmlns="http://www.freedesktop.org/standards/shared-mime-info">
       <mime-type type="application/x-your-mime">
         <comment>Your file type</comment>
         <glob pattern="*.your-ext"/>
         <icon name="your-icon-name"/>
       </mime-type>
     </mime-info>
     ```
     Иконка — в формате PNG, размер 48x48 или scalable SVG.

### 3. **Настройте ассоциации в .desktop-файле**
   Если MIME-тип распознаётся, но не ассоциируется с приложением (или иконка не применяется), проблема в .desktop.
   
   - Откройте /usr/share/applications/your-app.desktop:  
     `sudo nano /usr/share/applications/your-app.desktop`
   - Убедитесь, что есть строки:  
     ```
     MimeType=application/x-your-mime;
     Icon=your-icon-name
     Exec=your-app %U  # %U для URI, %F для файлов — обязательно для ассоциаций!
     ```
     (Без %U ассоциация не сработает в GNOME.)
   - Сохраните и обновите кэш desktop:  
     `sudo update-desktop-database /usr/share/applications`
   - Установите дефолтное приложение:  
     `xdg-mime default your-app.desktop application/x-your-mime`

### 4. **Доработайте .deb-пакет для автоматического фикса**
   Если вы собираете .deb (через dh_make или вручную), добавьте в postinst-скрипт (debian/your-package.postinst) команды обновления:
   ```
   #!/bin/sh
   set -e
   update-mime-database /usr/share/mime
   gtk-update-icon-cache -f /usr/share/icons/hicolor
   update-desktop-database /usr/share/applications
   ```
   - Сделайте скрипт исполняемым: `chmod +x debian/your-package.postinst`
   - Пересоберите .deb: `dpkg-buildpackage -us -uc`
   - В preinst добавьте очистку старого кэша, если нужно.

   Для иконок: Убедитесь, что они в hicolor-теме (стандарт для Ubuntu). Если SVG — проверьте, поддерживается ли в 24.04 (да, но PNG надёжнее для thumbnails).

### 5. **Дополнительные проверки и фиксы для Ubuntu 24.04**
   - **Проблемы с thumbnails/иконками на десктопе:** В 24.04 есть баг с DING-экстеншеном GNOME, который блокирует thumbnails из security. Установите фикс:  
     `sudo apt install gnome-shell-extension-ding` (если не стоит), или отключите extension через `gnome-tweaks` → Extensions → Desktop Icons NG.  
     Или обновитесь до последней версии: `sudo apt update && sudo apt upgrade`.
   - **Если Nautilus в snap (редко, но бывает):** `sudo apt install nautilus` (deb-версия лучше для custom MIME).
   - **Зависимости:** Убедитесь, что пакет зависит от shared-mime-info и librsvg2-common (для SVG): добавьте в debian/control:  
     ```
     Depends: shared-mime-info, librsvg2-common
     ```
   - **Тестируйте MIME:** `xdg-mime query filetype your-file.your-ext` — должно вернуть ваш MIME-тип. Если нет — проблема в XML или глобах.
   - **Логи:** Проверьте ошибки: `journalctl -xe | grep mime` или после установки `dpkg-reconfigure your-package`.

Если ничего не помогает, поделитесь деталями: содержимым MIME-XML, .desktop, выводом `dpkg -L your-package` и ошибками (если есть). Это должно сработать в 90% случаев — в 24.04 ничего радикально не сломано, просто кэши строже.
