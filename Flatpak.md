Here's a detailed guide to packaging a GTK application as a Flatpak, with a complete working example:

### 1. Prerequisites
First, install Flatpak and the GNOME SDK:
```bash
sudo apt install flatpak flatpak-builder  # Debian/Ubuntu
sudo dnf install flatpak flatpak-builder  # Fedora
flatpak remote-add --if-not-exists flathub https://flathub.org/repo/flathub.flatpakrepo
flatpak install flathub org.gnome.Sdk//45 org.gnome.Platform//45
```

### 2. Basic Application Structure
Create this directory structure:
```
my-gtk-app/
├── data/
│   └── com.example.MyGtkApp.desktop
├── src/
│   └── main.py (or your source files)
├── icons/
│   └── com.example.MyGtkApp.png
├── com.example.MyGtkApp.json (manifest)
└── com.example.MyGtkApp.yml (optional, for CI)
```

### 3. Example Files

**A. Desktop Entry File** (`data/com.example.MyGtkApp.desktop`):
```ini
[Desktop Entry]
Name=My GTK App
Exec=my-gtk-app
Icon=com.example.MyGtkApp
Type=Application
Categories=GTK;Utility;
StartupNotify=true
```

**B. Manifest File** (`com.example.MyGtkApp.json`):
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
    "modules": [
        {
            "name": "my-gtk-app",
            "buildsystem": "simple",
            "build-commands": [
                "install -Dm755 src/main.py /app/bin/my-gtk-app",
                "install -Dm644 data/com.example.MyGtkApp.desktop /app/share/applications/com.example.MyGtkApp.desktop",
                "install -Dm644 icons/com.example.MyGtkApp.png /app/share/icons/hicolor/512x512/apps/com.example.MyGtkApp.png"
            ],
            "sources": [
                {
                    "type": "dir",
                    "path": "src"
                },
                {
                    "type": "dir",
                    "path": "data"
                },
                {
                    "type": "dir",
                    "path": "icons"
                }
            ]
        }
    ]
}
```

**C. Simple Python GTK Example** (`src/main.py`):
```python
#!/usr/bin/python3
import gi
gi.require_version('Gtk', '3.0')
from gi.repository import Gtk

class MyWindow(Gtk.Window):
    def __init__(self):
        Gtk.Window.__init__(self, title="Hello Flatpak")
        self.set_default_size(400, 300)
        
        self.button = Gtk.Button(label="Click Here")
        self.button.connect("clicked", self.on_button_clicked)
        self.add(self.button)
    
    def on_button_clicked(self, widget):
        print("Hello Flatpak!")

win = MyWindow()
win.connect("destroy", Gtk.main_quit)
win.show_all()
Gtk.main()
```

### 4. Building and Installing
```bash
# Build the Flatpak
flatpak-builder --user --install build-dir com.example.MyGtkApp.json --force-clean

# Run the application
flatpak run com.example.MyGtkApp
```

### 5. Advanced Features

**Adding Python Dependencies**:
Modify your manifest to include Python packages:
```json
{
    "name": "python-dependencies",
    "buildsystem": "simple",
    "build-commands": [
        "pip3 install --prefix=/app --no-deps my-python-package"
    ],
    "sources": [
        {
            "type": "file",
            "url": "https://files.pythonhosted.org/packages/.../package.tar.gz",
            "sha256": "123abc..."
        }
    ]
}
```

**Adding System Dependencies**:
```json
"build-options": {
    "append-path": "/usr/lib/sdk/rust-stable/bin",
    "prepend-ld-library-path": "/usr/lib/sdk/rust-stable/lib"
}
```

### 6. Publishing to Flathub

1. Create a repository on GitHub
2. Add `flathub.json`:
```json
{
    "only-arches": ["x86_64", "aarch64"],
    "skip-appstream-check": true
}
```

3. Submit your app to Flathub via their [GitHub repository](https://github.com/flathub/flathub)

### 7. Debugging Tips

```bash
# Enter the build environment
flatpak-builder --run build-dir com.example.MyGtkApp.json bash

# View logs
flatpak logs com.example.MyGtkApp

# Check installed files
flatpak ps
flatpak enter com.example.MyGtkApp
```

This example provides a complete, working GTK3 Python application packaged as a Flatpak. For C applications, you would modify the build system to use meson or autotools instead of the simple build system shown here.
