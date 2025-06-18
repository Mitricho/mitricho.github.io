# JavaScript Cache API Example Using `put()`

Here's a complete example of how to use the Cache API to store both text and images on a user's device using the `put()` method:

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
async function cacheAssets() {
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
      }
    }));
    
    console.log('All assets cached successfully!');
  } catch (error) {
    console.error('Cache opening failed:', error);
  }
}

// Function to cache a single item (text or image) dynamically
async function cacheItem(url, content, contentType) {
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
  } catch (error) {
    console.error(`Error caching ${url}:`, error);
  }
}

// Example usage:
// Cache a text file
cacheItem('/data/custom-text.txt', 'This is some important text content', 'text/plain');

// Cache an image from a blob (e.g., from canvas)
async function cacheCanvasImage() {
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
  
  // Convert canvas to blob
  canvas.toBlob(async (blob) => {
    await cacheItem('/images/canvas-image.png', blob, 'image/png');
  }, 'image/png');
}

// Call the initial caching function when the page loads
window.addEventListener('load', () => {
  cacheAssets();
  cacheCanvasImage();
});

// Function to retrieve cached data
async function getCachedData(url) {
  try {
    const cache = await caches.open(CACHE_NAME);
    const response = await cache.match(url);
    
    if (!response) {
      console.log(`No cached data found for ${url}`);
      return null;
    }
    
    // Handle different response types
    const contentType = response.headers.get('Content-Type');
    
    if (contentType.includes('image')) {
      return await response.blob();
    } else if (contentType.includes('text')) {
      return await response.text();
    } else {
      return response;
    }
  } catch (error) {
    console.error(`Error retrieving cached data for ${url}:`, error);
    return null;
  }
}

// Example of retrieving cached data
async function displayCachedData() {
  const textData = await getCachedData('/data/important-text.txt');
  console.log('Cached text:', textData);
  
  const imageBlob = await getCachedData('/images/logo.png');
  if (imageBlob) {
    const imageUrl = URL.createObjectURL(imageBlob);
    const imgElement = document.createElement('img');
    imgElement.src = imageUrl;
    document.body.appendChild(imgElement);
  }
}

// Call this when you need to display cached data
displayCachedData();
```

## Key Points:

1. **Using `put()` instead of `add()`**:
   - `put()` gives you more control as you can provide the response object directly
   - With `add()`, the browser fetches and stores the response automatically
   - With `put()`, you handle the fetch and response creation yourself

2. **Caching Different Content Types**:
   - The example shows how to cache both text and images
   - For text, you can create a Response with a string
   - For images, you can use Blobs or fetch existing images

3. **Retrieving Data**:
   - The `getCachedData()` function demonstrates how to retrieve different types of cached content
   - It handles both text and image responses appropriately

4. **Error Handling**:
   - Each operation includes try-catch blocks to handle potential errors

Remember that the Cache API is only available in secure contexts (HTTPS or localhost) and in service workers. In a real application, you might want to implement most of this caching logic in a service worker.
