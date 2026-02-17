
```cpp
#include <QPlainTextEdit>
#include <QPainter>
#include <QTextBlock>

class MyEditor : public QPlainTextEdit {
    Q_OBJECT
public:
    MyEditor(QWidget *parent = nullptr) : QPlainTextEdit(parent) {
        // Принудительно обновляем viewport при прокрутке
        connect(verticalScrollBar(), &QScrollBar::valueChanged, [this]() {
            viewport()->update();
        });
    }

protected:
    void paintEvent(QPaintEvent *event) override;
};

```

```cpp
void MyEditor::paintEvent(QPaintEvent *event) {
    // 1. Сначала отрисовываем стандартный текст
    QPlainTextEdit::paintEvent(event);

    QPainter painter(viewport());
    
    // 2. Получаем первый видимый блок текста
    QTextBlock block = firstVisibleBlock();
    int top = (int) blockBoundingGeometry(block).translated(contentOffset()).top();
    int bottom = top + (int) blockBoundingRect(block).height();

    // 3. Проходим по всем видимым блокам
    while (block.isValid() && top <= event->rect().bottom()) {
        if (block.isVisible() && bottom >= event->rect().top()) {
            
            // ПРОВЕРКА: Изменен ли блок? (Ваша логика)
            if (block.userData() != nullptr) { // Например, вы храните пометку в userData
                painter.fillRect(0, top, 5, bottom - top, Qt::green); // Полоска слева
                
                // Или полупрозрачный фон на всю строку
                // painter.fillRect(event->rect().left(), top, event->rect().width(), bottom - top, QColor(0, 255, 0, 30));
            }
        }

        // Переходим к следующему блоку
        block = block.next();
        top = bottom;
        bottom = top + (int) blockBoundingRect(block).height();
    }
}

```
