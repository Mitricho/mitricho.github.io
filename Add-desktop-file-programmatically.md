```
#include <QStandardPaths>
#include <QFile>
#include <QTextStream>
#include <QDebug>

void createDesktopFile() {
    QString appName = "MyQtApp"; // Your application name
    QString iconName = "icon.png"; // Your icon file name
    QString executablePath = "my_qt_app"; // Your executable name

    // 1. Determine the correct directory
    QString appDirPath = QStandardPaths::writableLocation(QStandardPaths::ApplicationsLocation);
    if (appDirPath.isEmpty()) {
        qDebug() << "Could not get applications directory.";
        return;
    }
    QString desktopFilePath = appDirPath + "/" + appName + ".desktop";

    // 2. Create the *.desktop file content
    QString fileContent =
        "[Desktop Entry]\n"
        "Version=1.0\n"
        "Type=Application\n"
        "Name=" + appName + "\n"
        "Comment=My Awesome Qt Application\n"
        "Exec=" + executablePath + "\n" // Ensure executablePath is correct
        "Icon=" + iconName + "\n" // Ensure this icon path is accessible or an absolute path
        "Categories=Utility;\n";

    // 3. Write the file using QFile and QTextStream
    QFile desktopFile(desktopFilePath);
    if (desktopFile.open(QIODevice::WriteOnly | QIODevice::Text)) {
        QTextStream out(&desktopFile);
        out << fileContent;
        desktopFile.close();
        qDebug() << "Desktop file created successfully at:" << desktopFilePath;
    } else {
        qDebug() << "Failed to create desktop file:" << desktopFile.errorString();
    }
}
```
