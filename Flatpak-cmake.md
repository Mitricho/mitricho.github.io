Here's a complete **C++/GTK/CMake** example packaged with Flatpak:

### 1. Project Structure
```
my-gtk-app/
├── CMakeLists.txt
├── src/
│   ├── main.cpp
│   └── resources.cpp (if using GResources)
├── data/
│   ├── com.example.MyGtkApp.desktop
│   └── com.example.MyGtkApp.appdata.xml
├── icons/
│   └── com.example.MyGtkApp.png
├── po/ (for translations)
└── com.example.MyGtkApp.json
```

### 2. Key Files

**A. C++ GTK Example** (`src/main.cpp`):
```cpp
#include <gtkmm/application.h>
#include <gtkmm/window.h>
#include <gtkmm/button.h>
#include <iostream>

class MyWindow : public Gtk::Window {
public:
    MyWindow() {
        set_title("Flatpak C++ App");
        set_default_size(400, 300);
        
        m_button.set_label("Click Me");
        m_button.signal_clicked().connect(
            []() { std::cout << "Hello from Flatpak!" << std::endl; }
        );
        
        add(m_button);
        show_all_children();
    }

private:
    Gtk::Button m_button;
};

int main(int argc, char *argv[]) {
    auto app = Gtk::Application::create(argc, argv, "com.example.MyGtkApp");
    MyWindow window;
    return app->run(window);
}
```

**B. CMakeLists.txt**:
```cmake
cmake_minimum_required(VERSION 3.20)
project(my-gtk-app)

find_package(PkgConfig REQUIRED)
pkg_check_modules(GTKMM REQUIRED gtkmm-3.0)

add_executable(my-gtk-app src/main.cpp)
target_compile_features(my-gtk-app PRIVATE cxx_std_17)
target_link_libraries(my-gtk-app ${GTKMM_LIBRARIES})
target_include_directories(my-gtk-app PRIVATE ${GTKMM_INCLUDE_DIRS})
target_compile_options(my-gtk-app PRIVATE ${GTKMM_CFLAGS_OTHER})

install(TARGETS my-gtk-app DESTINATION bin)
install(FILES data/com.example.MyGtkApp.desktop DESTINATION share/applications)
install(FILES icons/com.example.MyGtkApp.png DESTINATION share/icons/hicolor/512x512/apps)
```

**C. Flatpak Manifest** (`com.example.MyGtkApp.json`):
```json
{
    "app-id": "com.example.MyGtkApp",
    "runtime": "org.gnome.Platform",
    "runtime-version": "45",
    "sdk": "org.gnome.Sdk",
    "command": "my-gtk-app",
    "finish-args": [
        "--share=ipc",
        "--socket=wayland",
        "--socket=x11",
        "--filesystem=host"
    ],
    "cleanup": [
        "/include",
        "/lib/pkgconfig",
        "/man",
        "/share/doc",
        "/share/gtk-doc",
        "/share/man"
    ],
    "modules": [
        {
            "name": "my-gtk-app",
            "buildsystem": "cmake",
            "config-opts": [
                "-DCMAKE_BUILD_TYPE=Release",
                "-DCMAKE_INSTALL_PREFIX=/app"
            ],
            "sources": [
                {
                    "type": "git",
                    "url": "https://github.com/yourusername/my-gtk-app.git",
                    "branch": "main"
                }
            ]
        }
    ]
}
```

### 3. Building and Installation

```bash
# Build and install
flatpak-builder --user --install --force-clean build-dir com.example.MyGtkApp.json

# Run the application
flatpak run com.example.MyGtkApp

# Alternative: Build without installing
flatpak-builder --repo=myrepo --force-clean build-dir com.example.MyGtkApp.json
```

### 4. Advanced CMake Configuration

For better Flatpak integration, modify your CMakeLists.txt to support GResources:

```cmake
# Add to CMakeLists.txt
find_program(GLIB_COMPILE_RESOURCES glib-compile-resources)

# Handle resources
file(GLOB_RECURSE RESOURCES data/*.ui data/*.css data/*.gresource.xml)
add_custom_command(
    OUTPUT resources.cpp
    COMMAND ${GLIB_COMPILE_RESOURCES} --target=resources.cpp --generate-source data/resources.gresource.xml
    DEPENDS ${RESOURCES}
    WORKING_DIRECTORY ${CMAKE_SOURCE_DIR}
)
```

### 5. Handling Dependencies

For additional C++ libraries, add them to your manifest:

```json
"modules": [
    {
        "name": "fmt",
        "buildsystem": "cmake",
        "config-opts": ["-DBUILD_SHARED_LIBS=ON"],
        "sources": [
            {
                "type": "archive",
                "url": "https://github.com/fmtlib/fmt/releases/download/9.1.0/fmt-9.1.0.zip",
                "sha256": "5dea48d1fcddc3ec571ce2058e13910a0d4a6bab4cc09a809d8b1dd1c88ae6f2"
            }
        ]
    },
    {
        "name": "my-gtk-app",
        "buildsystem": "cmake",
        "config-opts": [
            "-DCMAKE_BUILD_TYPE=Release",
            "-DFMT_INCLUDE_DIR=/app/include"
        ],
        "sources": [...]
    }
]
```

### 6. Debugging Tips

```bash
# Debug build:
flatpak-builder --user --install --force-clean build-dir com.example.MyGtkApp.json --build-shell=my-gtk-app

# Inside the build shell:
mkdir build && cd build
cmake .. -DCMAKE_INSTALL_PREFIX=/app -DCMAKE_BUILD_TYPE=Debug
make VERBOSE=1
```

### 7. Windows Cross-Compilation Note

While this focuses on Flatpak (Linux), for Windows you would:
1. Use MSYS2 with `mingw-w64-x86_64-gtkmm3`
2. Bundle all DLLs using `windeployqt`-like scripts
3. Create NSIS or WiX installer

Would you like me to elaborate on any specific aspect of this C++/CMake/Flatpak setup?
