В Linux при подключении веб-камеры могут создаваться два устройства `/dev/video*`, где одно (`/dev/videoX`) соответствует реальному видеоустройству (например, для захвата кадров), а второе (`/dev/videoY`) — интерфейсу метаданных (например, для управления параметрами камеры).  

Чтобы отличить физическое устройство от устройства метаданных, можно использовать **libudev** для анализа свойств устройства.  

### Код на C++ для определения реального физического устройства:
```cpp
#include <libudev.h>
#include <iostream>
#include <string>
#include <vector>

// Функция для проверки, является ли устройство физическим (а не метаданными)
bool is_physical_video_device(udev_device *dev) {
    const char *name = udev_device_get_sysname(dev);
    const char *devpath = udev_device_get_devpath(dev);

    // Устройства метаданных часто имеют в имени "metadata" или находятся в определенном подкаталоге
    if (strstr(name, "metadata") != nullptr || strstr(devpath, "metadata") != nullptr) {
        return false;
    }

    // Проверяем, есть ли у устройства атрибут, указывающий на его назначение (например, video4linux)
    const char *subsystem = udev_device_get_subsystem(dev);
    if (!subsystem || strcmp(subsystem, "video4linux") != 0) {
        return false;
    }

    // Проверяем, поддерживает ли устройство захват видео (CAPTURE)
    udev_device *parent = udev_device_get_parent_with_subsystem_devtype(dev, "usb", nullptr);
    if (!parent) {
        parent = udev_device_get_parent_with_subsystem_devtype(dev, "pci", nullptr);
    }

    if (parent) {
        const char *capabilities = udev_device_get_property_value(parent, "CAPABILITIES");
        if (capabilities && strstr(capabilities, "CAPTURE") != nullptr) {
            return true;
        }
    }

    return false;
}

int main() {
    udev *udev_ctx = udev_new();
    if (!udev_ctx) {
        std::cerr << "Не удалось создать контекст udev!" << std::endl;
        return 1;
    }

    udev_enumerate *enumerate = udev_enumerate_new(udev_ctx);
    udev_enumerate_add_match_subsystem(enumerate, "video4linux");
    udev_enumerate_scan_devices(enumerate);

    udev_list_entry *devices = udev_enumerate_get_list_entry(enumerate);
    udev_list_entry *entry;

    std::vector<std::string> physical_devices;

    udev_list_entry_foreach(entry, devices) {
        const char *path = udev_list_entry_get_name(entry);
        udev_device *dev = udev_device_new_from_syspath(udev_ctx, path);

        if (dev && is_physical_video_device(dev)) {
            const char *devnode = udev_device_get_devnode(dev);
            if (devnode) {
                physical_devices.push_back(devnode);
                std::cout << "Физическое устройство: " << devnode << std::endl;
            }
        }

        if (dev) {
            udev_device_unref(dev);
        }
    }

    udev_enumerate_unref(enumerate);
    udev_unref(udev_ctx);

    if (physical_devices.empty()) {
        std::cout << "Не найдено физических видеоустройств!" << std::endl;
    }

    return 0;
}
```

### Объяснение:
1. **Фильтрация устройств**:
   - Используется `udev` для перечисления всех устройств в подсистеме `video4linux`.
   - Проверяется, содержит ли имя устройства или путь к нему строку `"metadata"` (часто используется для устройств метаданных).
   - Проверяется, поддерживает ли родительское устройство (USB/PCI) возможность `CAPTURE` (захват видео).

2. **Сбор физических устройств**:
   - Если устройство соответствует критериям, его путь (`/dev/videoX`) добавляется в список физических устройств.

3. **Вывод результата**:
   - Программа печатает все обнаруженные физические устройства.

### Сборка и запуск:
```bash
g++ -std=c++11 detect_physical_camera.cpp -o detect_camera -ludev
./detect_camera
```

Этот код поможет отличить реальное видеоустройство от устройства метаданных в Linux.
