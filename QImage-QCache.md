QFuture<QImage> future = QtConcurrent::run(loadImageInBackground, "path/to/your/image.jpg");
QFutureWatcher<QImage> *watcher = new QFutureWatcher<QImage>(this);
connect(watcher, &QFutureWatcher<QImage>::finished, this, [this, watcher]() {
    QImage newImage = watcher->result();
    const QString key = newImage.text(QStringLiteral("key")).toString();
    if(!newImage.isNull() && !key.isEmpty()){
      imageCache.insert(key, new QImage(newImage), newImage.byteCount() / 1024);
      m_renderableImage.swap(newImage);
    }    
    delete watcher; // Clean up the watcher
});
watcher->setFuture(future);
