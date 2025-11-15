// Saving QPainterPath to a file
QFile file("path.dat");
if (file.open(QIODevice::WriteOnly)) {
    QDataStream stream(&file);
    QPainterPath path;
    // Add elements to your path
    path.moveTo(0, 0);
    path.lineTo(100, 0);
    path.arcTo(50, -50, 100, 100, 0, 90);
    stream << path; // Serialize the path
    file.close();
}

// Loading QPainterPath from a file
QFile file_in("path.dat");
if (file_in.open(QIODevice::ReadOnly)) {
    QDataStream stream_in(&file_in);
    QPainterPath loadedPath;
    stream_in >> loadedPath; // Deserialize the path
    file_in.close();
    // Use loadedPath for drawing or further processing
}
