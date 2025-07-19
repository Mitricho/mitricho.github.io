# JavaScript Code to Add Script and CSS to a Web Page Programmatically

Here's a complete solution to dynamically add JavaScript and CSS files to a web page:

## 1. Function to Load JavaScript Files

```javascript
/**
 * Loads a JavaScript file dynamically
 * @param {string} src - The URL of the JavaScript file to load
 * @param {function} [callback] - Optional callback function to execute after script loads
 * @param {string} [id] - Optional ID to assign to the script element
 * @returns {HTMLElement} The created script element
 */
function loadScript(src, callback, id) {
    const script = document.createElement('script');
    script.src = src;
    script.type = 'text/javascript';
    script.async = true;
    
    if (id) {
        script.id = id;
    }
    
    if (callback) {
        script.onload = callback;
        script.onerror = function() {
            console.error(`Failed to load script: ${src}`);
        };
    }
    
    document.head.appendChild(script);
    return script;
}
```

## 2. Function to Load CSS Files

```javascript
/**
 * Loads a CSS file dynamically
 * @param {string} href - The URL of the CSS file to load
 * @param {function} [callback] - Optional callback function to execute after CSS loads
 * @param {string} [id] - Optional ID to assign to the link element
 * @returns {HTMLElement} The created link element
 */
function loadCSS(href, callback, id) {
    const link = document.createElement('link');
    link.href = href;
    link.rel = 'stylesheet';
    link.type = 'text/css';
    
    if (id) {
        link.id = id;
    }
    
    if (callback) {
        link.onload = callback;
        link.onerror = function() {
            console.error(`Failed to load CSS: ${href}`);
        };
    }
    
    document.head.appendChild(link);
    return link;
}
```

## 3. Function to Add Inline CSS

```javascript
/**
 * Adds inline CSS to the page
 * @param {string} css - The CSS rules to add
 * @param {string} [id] - Optional ID to assign to the style element
 * @returns {HTMLElement} The created style element
 */
function addInlineCSS(css, id) {
    const style = document.createElement('style');
    style.type = 'text/css';
    
    if (id) {
        style.id = id;
    }
    
    // For IE
    if (style.styleSheet) {
        style.styleSheet.cssText = css;
    } else {
        style.appendChild(document.createTextNode(css));
    }
    
    document.head.appendChild(style);
    return style;
}
```

## 4. Function to Add Inline JavaScript

```javascript
/**
 * Adds inline JavaScript to the page
 * @param {string} code - The JavaScript code to execute
 * @param {string} [id] - Optional ID to assign to the script element
 * @returns {HTMLElement} The created script element
 */
function addInlineJS(code, id) {
    const script = document.createElement('script');
    script.type = 'text/javascript';
    
    if (id) {
        script.id = id;
    }
    
    // For IE
    if (script.text) {
        script.text = code;
    } else {
        script.appendChild(document.createTextNode(code));
    }
    
    document.head.appendChild(script);
    return script;
}
```

## Usage Examples

```javascript
// Load external JavaScript file
loadScript('https://example.com/script.js', function() {
    console.log('Script loaded successfully!');
}, 'my-script');

// Load external CSS file
loadCSS('https://example.com/styles.css', function() {
    console.log('CSS loaded successfully!');
}, 'my-styles');

// Add inline CSS
addInlineCSS(`
    body {
        background-color: #f0f0f0;
    }
    .my-class {
        color: red;
    }
`, 'my-inline-styles');

// Add inline JavaScript
addInlineJS(`
    console.log('Inline script executed!');
    document.title = 'Modified by inline script';
`, 'my-inline-script');
```

## Advanced Version with Promise Support

For modern browsers, you can use Promises:

```javascript
/**
 * Loads a JavaScript file with Promise support
 * @param {string} src - The URL of the JavaScript file to load
 * @param {string} [id] - Optional ID to assign to the script element
 * @returns {Promise} A promise that resolves when the script loads
 */
function loadScriptAsync(src, id) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.type = 'text/javascript';
        script.async = true;
        
        if (id) {
            script.id = id;
        }
        
        script.onload = resolve;
        script.onerror = reject;
        
        document.head.appendChild(script);
    });
}

// Usage with async/await
(async function() {
    try {
        await loadScriptAsync('https://example.com/script.js', 'my-script');
        console.log('Script loaded successfully!');
    } catch (error) {
        console.error('Script failed to load:', error);
    }
})();
```

This code provides a comprehensive solution for dynamically adding both external and inline JavaScript and CSS to your web pages. The functions handle error cases and provide optional callbacks for when the resources are loaded.
