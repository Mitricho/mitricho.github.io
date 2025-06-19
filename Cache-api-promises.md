```javascript
    if(canvasAvailable)
    {
        const img = new Image();
        img.src = window.URL.createObjectURL(blob);
        img.onload = function()
        {
            let _w = Math.min(img.width,maxPicWidth);
            const scaleFactor = _w / img.width;
            const elem = document.createElement('canvas');
                  elem.width = _w;
                  elem.height = img.height * scaleFactor;
            const ctx = elem.getContext('2d');// img.width and img.height will contain the original dimensions
            ctx.drawImage(img, 0, 0, _w, img.height * scaleFactor);
            const base64 = ctx.canvas.toDataURL(mime,0.8);
            localforage.setItem(imgname,base64).then(function(value){
                listOfImages.push(imgname);
            }).catch(function(err){
                console.log(err);
            });
        }
    }
```
You can access CacheStorage through the `Window.caches` property in windows or through the `WorkerGlobalScope.caches` property in workers.
```
if ('caches' in window)...
if ('caches' in self)...
```

Here's the rewritten code with all functions returning Promises to enable proper chaining of execution:

```javascript
// Define the cache name
const CACHE_NAME = 'my-app-cache-v1';

// URLs to cache
const assetsToCache = [
  '/styles/main.css',
  '/scripts/main.js',
  '/images/logo.png',
  '/data/important-text.txt'
];

// Function to open the cache and add all assets
function cacheAssets() {
  return new Promise(async (resolve, reject) => {
    try {
      // Open the cache (creates it if it doesn't exist)
      const cache = await caches.open(CACHE_NAME);
      
      console.log('Cache opened, starting to add assets...');
      
      // Use Promise.all to wait for all assets to be cached
      await Promise.all(assetsToCache.map(async (assetUrl) => {
        try {
          // Fetch the asset
          const response = await fetch(assetUrl);
          
          if (!response.ok) {
            throw new Error(`Failed to fetch ${assetUrl}: ${response.statusText}`);
          }
          
          // Use put() to store the response in the cache
          await cache.put(assetUrl, response);
          console.log(`Cached: ${assetUrl}`);
        } catch (error) {
          console.error(`Error caching ${assetUrl}:`, error);
          throw error; // Re-throw to fail the Promise.all
        }
      }));
      
      console.log('All assets cached successfully!');
      resolve();
    } catch (error) {
      console.error('Cache opening failed:', error);
      reject(error);
    }
  });
}

// Function to cache a single item (text or image) dynamically
function cacheItem(url, content, contentType) {
  return new Promise(async (resolve, reject) => {
    try {
      const cache = await caches.open(CACHE_NAME);
      
      // Create a new response object
      const response = new Response(content, {
        headers: {
          'Content-Type': contentType
        }
      });
      
      // Use put() to store the response
      await cache.put(url, response);
      console.log(`Successfully cached: ${url}`);
      resolve();
    } catch (error) {
      console.error(`Error caching ${url}:`, error);
      reject(error);
    }
  });
}

// Function to generate and cache a canvas image
function cacheCanvasImage() {
  return new Promise(async (resolve, reject) => {
    try {
      const canvas = document.createElement('canvas');
      canvas.width = 200;
      canvas.height = 200;
      const ctx = canvas.getContext('2d');
      
      // Draw something on the canvas
      ctx.fillStyle = 'blue';
      ctx.fillRect(0, 0, 200, 200);
      ctx.fillStyle = 'white';
      ctx.font = '30px Arial';
      ctx.fillText('Cached!', 50, 100);
      
      // Convert canvas to blob and cache it
      canvas.toBlob(async (blob) => {
        try {
          await cacheItem('/images/canvas-image.png', blob, 'image/png');
          resolve();
        } catch (error) {
          reject(error);
        }
      }, 'image/png');
    } catch (error) {
      console.error('Error creating canvas image:', error);
      reject(error);
    }
  });
}

// Function to retrieve cached data
function getCachedData(url) {
  return new Promise(async (resolve, reject) => {
    try {
      const cache = await caches.open(CACHE_NAME);
      const response = await cache.match(url);
      
      if (!response) {
        console.log(`No cached data found for ${url}`);
        resolve(null);
        return;
      }
      
      // Handle different response types
      const contentType = response.headers.get('Content-Type');
      
      if (contentType.includes('image')) {
        resolve(await response.blob());
      } else if (contentType.includes('text')) {
        resolve(await response.text());
      } else {
        resolve(response);
      }
    } catch (error) {
      console.error(`Error retrieving cached data for ${url}:`, error);
      reject(error);
    }
  });
}

// Function to display cached data
function displayCachedData() {
  return new Promise(async (resolve, reject) => {
    try {
      const textData = await getCachedData('/data/important-text.txt');
      console.log('Cached text:', textData);
      
      const imageBlob = await getCachedData('/images/logo.png');
      if (imageBlob) {
        const imageUrl = URL.createObjectURL(imageBlob);
        const imgElement = document.createElement('img');
        imgElement.src = imageUrl;
        document.body.appendChild(imgElement);
      }
      
      resolve();
    } catch (error) {
      console.error('Error displaying cached data:', error);
      reject(error);
    }
  });
}

// Chain execution example
window.addEventListener('load', () => {
  cacheAssets()
    .then(() => cacheCanvasImage())
    .then(() => displayCachedData())
    .then(() => console.log('All caching operations completed successfully!'))
    .catch(error => console.error('Error in caching chain:', error));
});

// Alternative usage with async/await
async function executeCachingChain() {
  try {
    await cacheAssets();
    await cacheCanvasImage();
    await displayCachedData();
    console.log('All caching operations completed successfully!');
  } catch (error) {
    console.error('Error in caching chain:', error);
  }
}

// Uncomment to use the alternative version
// window.addEventListener('load', executeCachingChain);
```

### Key Improvements:

1. **All functions now return Promises**:
   - Each function wraps its operations in a Promise
   - Resolves on success, rejects on error
   - Enables proper chaining with `.then()` and `.catch()`

2. **Chainable execution**:
   - Operations can be chained: `cacheAssets().then(cacheCanvasImage).then(displayCachedData)`
   - Errors propagate through the chain and can be caught at the end

3. **Two usage patterns**:
   - Traditional Promise chain (shown in the load event listener)
   - Async/await pattern (shown in `executeCachingChain`)

4. **Better error handling**:
   - Errors are properly propagated through the chain
   - Each step only proceeds if the previous one succeeded

This structure makes it much easier to control the flow of your caching operations and handle errors appropriately.
