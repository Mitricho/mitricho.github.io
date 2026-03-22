

# Blog Single Page Application

## File: `posts.md`

```markdown
---POST---
title: The Future of Artificial Intelligence
subtitle: How AI is reshaping industries and everyday life
image: https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&q=80
date: 2025-01-15
tags: #AI #Technology #Future #MachineLearning

# The Future of Artificial Intelligence

![AI Banner](https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1200&q=80)

Artificial intelligence has moved from the realm of science fiction into our daily lives. From the recommendations on your streaming service to the voice assistant on your phone, **AI is everywhere**.

## The Current Landscape

The rapid advancement of large language models (LLMs) has fundamentally changed how we interact with technology. Companies like OpenAI, Google, and Anthropic are pushing the boundaries of what's possible.

> "The development of full artificial intelligence could spell the end of the human race… It would take off on its own, and re-design itself at an ever-increasing rate." — Stephen Hawking

### Key Areas of Impact

- **Healthcare**: AI-powered diagnostics can detect diseases earlier than ever
- **Finance**: Algorithmic trading and fraud detection
- **Transportation**: Self-driving vehicles and route optimization
- **Education**: Personalized learning experiences

## What's Next?

The next frontier involves **multimodal AI** — systems that can seamlessly process text, images, audio, and video simultaneously. This convergence will unlock applications we haven't yet imagined.

```python
# Simple example of AI integration
from transformers import pipeline

classifier = pipeline("sentiment-analysis")
result = classifier("AI is transforming the world!")
print(result)  # [{'label': 'POSITIVE', 'score': 0.9998}]
```

The journey has just begun, and the most exciting developments are still ahead of us. Stay tuned as we continue to explore this fascinating field.

#AI #Technology #Future #MachineLearning

---POST---
title: Mastering Modern CSS
subtitle: Advanced techniques for beautiful web layouts
image: https://images.unsplash.com/photo-1507721999472-8ed4421c4af2?w=800&q=80
date: 2025-01-10
tags: #CSS #WebDev #Design #Frontend

# Mastering Modern CSS

![CSS Art](https://images.unsplash.com/photo-1507721999472-8ed4421c4af2?w=1200&q=80)

CSS has evolved dramatically in recent years. Features like **Container Queries**, **CSS Grid**, **Subgrid**, and the `:has()` selector have transformed the way we build layouts.

## CSS Grid: The Layout Revolution

CSS Grid allows us to create complex two-dimensional layouts with ease:

```css
.gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
}
```

## Container Queries

Unlike media queries that respond to the viewport, container queries respond to the **parent container's size**:

```css
@container (min-width: 400px) {
  .card {
    flex-direction: row;
  }
}
```

## The Power of Custom Properties

CSS custom properties (variables) enable dynamic theming:

```css
:root {
  --primary: #6366f1;
  --surface: #1e1e2e;
  --text: #cdd6f4;
}
```

### Tips for Better CSS

1. Use logical properties (`margin-inline`, `padding-block`)
2. Embrace `clamp()` for fluid typography
3. Leverage `aspect-ratio` for responsive media
4. Use `color-mix()` for dynamic color variations

Modern CSS is powerful enough to replace many JavaScript-based solutions. Embrace the platform!

#CSS #WebDev #Design #Frontend

---POST---
title: Building RESTful APIs
subtitle: Best practices for designing scalable APIs
image: https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&q=80
date: 2025-01-05
tags: #API #Backend #WebDev #Architecture

# Building RESTful APIs

![Server Room](https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200&q=80)

A well-designed API is the backbone of modern applications. Whether you're building a mobile app, a web platform, or a microservices architecture, understanding REST principles is essential.

## Core REST Principles

- **Stateless**: Each request contains all necessary information
- **Resource-Based**: URLs represent resources, not actions
- **HTTP Methods**: Use GET, POST, PUT, DELETE appropriately
- **HATEOAS**: Responses include links to related resources

## Endpoint Design

```
GET    /api/v1/articles          # List all articles
GET    /api/v1/articles/:id      # Get single article
POST   /api/v1/articles          # Create article
PUT    /api/v1/articles/:id      # Update article
DELETE /api/v1/articles/:id      # Delete article
```

## Error Handling

Always return meaningful error responses:

```json
{
  "error": {
    "code": 404,
    "message": "Article not found",
    "details": "No article exists with ID 42"
  }
}
```

## Authentication

Use **JWT tokens** or **OAuth 2.0** for securing your endpoints. Never expose sensitive data in URLs.

Good API design takes time, but it pays dividends in maintainability and developer experience.

#API #Backend #WebDev #Architecture

---POST---
title: The Art of Photography
subtitle: Capturing moments that tell stories
image: https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=800&q=80
date: 2024-12-28
tags: #Photography #Art #Creative #Storytelling

# The Art of Photography

![Camera](https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=1200&q=80)

Photography is more than just pointing a camera and pressing a button. It's about seeing the world differently and capturing **moments that matter**.

## The Exposure Triangle

Every photograph is governed by three fundamental settings:

1. **Aperture** (f-stop): Controls depth of field
2. **Shutter Speed**: Controls motion blur
3. **ISO**: Controls sensor sensitivity

## Composition Rules

### Rule of Thirds
Divide your frame into a 3×3 grid. Place key elements along the lines or at intersections.

### Leading Lines
Use natural lines in your scene to guide the viewer's eye toward the subject.

### Negative Space
Sometimes what you *don't* include is more powerful than what you do.

> "Your first 10,000 photographs are your worst." — Henri Cartier-Bresson

## Post-Processing

Modern photography workflow often includes:
- RAW file processing
- Color grading and tone curves
- Selective adjustments
- Cropping and straightening

Remember: post-processing should **enhance**, not replace, good photography fundamentals.

#Photography #Art #Creative #Storytelling

---POST---
title: Getting Started with Rust
subtitle: Why Rust is becoming the language of choice for systems programming
image: https://images.unsplash.com/photo-1623479322729-28b25c16b011?w=800&q=80
date: 2024-12-20
tags: #Rust #Programming #Systems #Technology

# Getting Started with Rust

![Code](https://images.unsplash.com/photo-1623479322729-28b25c16b011?w=1200&q=80)

Rust has consistently been voted the **most loved programming language** for years. But what makes it so special?

## Why Rust?

- **Memory Safety** without garbage collection
- **Zero-cost abstractions**
- **Fearless concurrency**
- **Excellent tooling** (Cargo, rustfmt, clippy)

## Hello, Rust!

```rust
fn main() {
    let message = String::from("Hello, Rust!");
    println!("{}", message);
    
    // Ownership in action
    let numbers = vec![1, 2, 3, 4, 5];
    let sum: i32 = numbers.iter().sum();
    println!("Sum: {}", sum);
}
```

## The Ownership System

Rust's most unique feature is its ownership model:

```rust
fn take_ownership(s: String) {
    println!("{}", s);
} // s is dropped here

fn borrow(s: &String) {
    println!("{}", s);
} // s is NOT dropped, just borrowed
```

## When to Use Rust

Rust excels at:
- Command-line tools
- Web servers (via Actix, Axum)
- WebAssembly applications
- Embedded systems
- Game engines

The learning curve is steep, but the rewards are immense. Start with [The Rust Book](https://doc.rust-lang.org/book/) and build something today!

#Rust #Programming #Systems #Technology

---POST---
title: Mindful Living in a Digital Age
subtitle: Finding balance between connectivity and inner peace
image: https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&q=80
date: 2024-12-15
tags: #Mindfulness #Wellness #Lifestyle #DigitalDetox

# Mindful Living in a Digital Age

![Meditation](https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=1200&q=80)

In a world of constant notifications, endless scrolling, and digital overwhelm, **mindfulness** has become not just a luxury—but a necessity.

## The Digital Dilemma

The average person checks their phone **96 times per day**. That's once every 10 minutes. This constant connectivity comes at a cost:

- Reduced attention span
- Increased anxiety
- Poor sleep quality
- Diminished real-world connections

## Practical Mindfulness Techniques

### 1. The 5-4-3-2-1 Grounding Exercise
- **5** things you can see
- **4** things you can touch
- **3** things you can hear
- **2** things you can smell
- **1** thing you can taste

### 2. Digital Sunset
Stop using screens **one hour before bed**. Use this time for reading, journaling, or gentle stretching.

### 3. Mindful Breathing
```
Inhale for 4 counts
Hold for 7 counts
Exhale for 8 counts
Repeat 3 times
```

## Creating Boundaries

- Set specific times for checking email and social media
- Use "Do Not Disturb" mode liberally
- Designate tech-free zones in your home
- Practice single-tasking instead of multi-tasking

> "Almost everything will work again if you unplug it for a few minutes, including you." — Anne Lamott

The goal isn't to eliminate technology, but to use it **intentionally** rather than compulsively.

#Mindfulness #Wellness #Lifestyle #DigitalDetox
```

## File: `index.html`

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Modern Blog</title>
<style>
/* ===== CSS RESET & VARIABLES ===== */
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

:root {
  --bg-primary: #0a0a0f;
  --bg-secondary: #12121a;
  --bg-card: #1a1a2e;
  --bg-card-hover: #222240;
  --text-primary: #e4e4f0;
  --text-secondary: #9999b0;
  --text-muted: #66668a;
  --accent: #7c5cfc;
  --accent-light: #9d85fd;
  --accent-glow: rgba(124, 92, 252, 0.3);
  --accent-secondary: #f472b6;
  --gradient-1: linear-gradient(135deg, #7c5cfc, #f472b6);
  --gradient-2: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
  --border-color: rgba(255,255,255,0.06);
  --border-hover: rgba(124, 92, 252, 0.3);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 20px;
  --radius-xl: 28px;
  --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
  --shadow-md: 0 4px 24px rgba(0,0,0,0.4);
  --shadow-lg: 0 8px 48px rgba(0,0,0,0.5);
  --shadow-glow: 0 0 40px rgba(124, 92, 252, 0.15);
  --transition-fast: 0.2s ease;
  --transition-med: 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  --transition-slow: 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  --header-height: 70px;
  --max-width: 1400px;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', Roboto, sans-serif;
  background: var(--bg-primary);
  color: var(--text-primary);
  line-height: 1.7;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 3px; }

/* ===== ANIMATED BACKGROUND ===== */
.bg-orbs {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.bg-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0.08;
  animation: floatOrb 20s ease-in-out infinite;
}

.bg-orb:nth-child(1) {
  width: 600px; height: 600px;
  background: var(--accent);
  top: -200px; left: -200px;
  animation-duration: 25s;
}

.bg-orb:nth-child(2) {
  width: 500px; height: 500px;
  background: var(--accent-secondary);
  bottom: -200px; right: -200px;
  animation-duration: 30s;
  animation-delay: -5s;
}

.bg-orb:nth-child(3) {
  width: 400px; height: 400px;
  background: #34d399;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  animation-duration: 22s;
  animation-delay: -10s;
}

@keyframes floatOrb {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(80px, -60px) scale(1.1); }
  50% { transform: translate(-40px, 80px) scale(0.9); }
  75% { transform: translate(60px, 40px) scale(1.05); }
}

/* ===== HEADER ===== */
.header {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: var(--header-height);
  z-index: 100;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  background: rgba(10, 10, 15, 0.8);
  border-bottom: 1px solid var(--border-color);
  transition: transform var(--transition-med), box-shadow var(--transition-med);
}

.header.scrolled {
  box-shadow: var(--shadow-md);
}

.header-inner {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: 0 2rem;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  font-size: 1.5rem;
  font-weight: 800;
  background: var(--gradient-1);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
  cursor: pointer;
  transition: transform var(--transition-fast);
}

.logo:hover {
  transform: scale(1.05);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.search-toggle {
  background: none;
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  padding: 0.5rem 1rem;
  border-radius: 100px;
  cursor: pointer;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all var(--transition-fast);
}

.search-toggle:hover {
  border-color: var(--accent);
  color: var(--accent-light);
}

.search-toggle svg {
  width: 16px;
  height: 16px;
}

/* ===== MAIN CONTENT ===== */
.main {
  position: relative;
  z-index: 1;
  padding-top: var(--header-height);
}

.container {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: 0 2rem;
}

/* ===== LOADING STATE ===== */
.loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 1.5rem;
}

.loader-spinner {
  width: 48px;
  height: 48px;
  border: 3px solid var(--border-color);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loader-text {
  color: var(--text-muted);
  font-size: 0.9rem;
}

/* ===== HASHTAG FILTERS ===== */
.filter-section {
  padding: 2rem 0 1rem;
  opacity: 0;
  transform: translateY(20px);
  animation: fadeInUp 0.6s ease forwards;
  animation-delay: 0.2s;
}

.filter-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--text-muted);
  margin-bottom: 0.75rem;
}

.filter-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding-bottom: 0.5rem;
}

.filter-tag {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  padding: 0.4rem 1rem;
  border-radius: 100px;
  cursor: pointer;
  font-size: 0.82rem;
  transition: all var(--transition-fast);
  user-select: none;
  white-space: nowrap;
}

.filter-tag:hover {
  border-color: var(--accent);
  color: var(--accent-light);
  transform: translateY(-1px);
}

.filter-tag.active {
  background: var(--accent);
  border-color: var(--accent);
  color: #fff;
  box-shadow: 0 2px 12px var(--accent-glow);
}

.filter-clear {
  background: none;
  border: 1px dashed var(--border-color);
  color: var(--text-muted);
  padding: 0.4rem 1rem;
  border-radius: 100px;
  cursor: pointer;
  font-size: 0.82rem;
  transition: all var(--transition-fast);
  display: none;
}

.filter-clear.visible {
  display: inline-block;
}

.filter-clear:hover {
  border-color: var(--accent-secondary);
  color: var(--accent-secondary);
}

/* ===== FEATURED POST ===== */
.featured-section {
  padding: 1.5rem 0 2rem;
}

.featured-card {
  position: relative;
  border-radius: var(--radius-xl);
  overflow: hidden;
  cursor: pointer;
  min-height: 500px;
  display: flex;
  align-items: flex-end;
  opacity: 0;
  transform: translateY(30px);
  animation: fadeInUp 0.8s ease forwards;
  animation-delay: 0.3s;
  transition: transform var(--transition-med), box-shadow var(--transition-med);
}

.featured-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-glow);
}

.featured-card-bg {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.8s ease;
}

.featured-card:hover .featured-card-bg {
  transform: scale(1.03);
}

.featured-card-overlay {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: linear-gradient(
    to top,
    rgba(10, 10, 15, 0.95) 0%,
    rgba(10, 10, 15, 0.6) 40%,
    rgba(10, 10, 15, 0.1) 100%
  );
}

.featured-card-content {
  position: relative;
  z-index: 2;
  padding: 3rem;
  width: 100%;
}

.featured-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: var(--accent);
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  padding: 0.3rem 0.8rem;
  border-radius: 100px;
  margin-bottom: 1rem;
}

.featured-badge svg {
  width: 12px; height: 12px;
}

.featured-title {
  font-size: clamp(1.8rem, 4vw, 3rem);
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 0.75rem;
  letter-spacing: -1px;
}

.featured-subtitle {
  font-size: clamp(1rem, 2vw, 1.2rem);
  color: var(--text-secondary);
  margin-bottom: 1rem;
  max-width: 600px;
}

.featured-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.featured-date {
  color: var(--text-muted);
  font-size: 0.85rem;
}

.featured-tags {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.featured-tags .tag {
  font-size: 0.75rem;
  color: var(--accent-light);
  background: rgba(124, 92, 252, 0.15);
  padding: 0.2rem 0.6rem;
  border-radius: 100px;
}

.read-more-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
  padding: 0.7rem 1.5rem;
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.15);
  color: #fff;
  border-radius: 100px;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.read-more-btn:hover {
  background: var(--accent);
  border-color: var(--accent);
  transform: translateX(4px);
}

.read-more-btn svg {
  width: 16px; height: 16px;
  transition: transform var(--transition-fast);
}

.read-more-btn:hover svg {
  transform: translateX(4px);
}

/* ===== ARTICLES GRID ===== */
.articles-section {
  padding: 1rem 0 4rem;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  opacity: 0;
  transform: translateY(20px);
  animation: fadeInUp 0.6s ease forwards;
  animation-delay: 0.5s;
}

.section-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text-primary);
}

.article-count {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.articles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

/* ===== ARTICLE CARD ===== */
.article-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  overflow: hidden;
  cursor: pointer;
  transition: all var(--transition-med);
  opacity: 0;
  transform: translateY(30px);
}

.article-card.visible {
  opacity: 1;
  transform: translateY(0);
}

.article-card:hover {
  border-color: var(--border-hover);
  transform: translateY(-6px);
  box-shadow: var(--shadow-lg), var(--shadow-glow);
}

.article-card.hidden {
  display: none;
}

.article-card-image {
  position: relative;
  width: 100%;
  aspect-ratio: 16/10;
  overflow: hidden;
}

.article-card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.article-card:hover .article-card-image img {
  transform: scale(1.08);
}

.article-card-image::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0;
  width: 100%; height: 40%;
  background: linear-gradient(to top, var(--bg-card), transparent);
}

.article-card-body {
  padding: 1.25rem 1.5rem 1.5rem;
}

.article-card-title {
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
  line-height: 1.3;
  transition: color var(--transition-fast);
}

.article-card:hover .article-card-title {
  color: var(--accent-light);
}

.article-card-subtitle {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.article-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.article-card-date {
  font-size: 0.78rem;
  color: var(--text-muted);
}

.article-card-tags {
  display: flex;
  gap: 0.3rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.article-card-tags .tag {
  font-size: 0.7rem;
  color: var(--accent-light);
  background: rgba(124, 92, 252, 0.1);
  padding: 0.15rem 0.5rem;
  border-radius: 100px;
}

/* ===== NO RESULTS ===== */
.no-results {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--text-muted);
  display: none;
}

.no-results.visible {
  display: block;
}

.no-results-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-results h3 {
  font-size: 1.2rem;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
}

/* ===== ARTICLE VIEW ===== */
.article-view {
  display: none;
  padding: 2rem 0 4rem;
  opacity: 0;
  transform: translateY(20px);
}

.article-view.active {
  display: block;
  animation: fadeInUp 0.5s ease forwards;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  padding: 0.6rem 1.2rem;
  border-radius: 100px;
  cursor: pointer;
  font-size: 0.85rem;
  margin-bottom: 2rem;
  transition: all var(--transition-fast);
}

.back-btn:hover {
  border-color: var(--accent);
  color: var(--accent-light);
  transform: translateX(-4px);
}

.back-btn svg {
  width: 16px; height: 16px;
}

.article-view-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 3rem;
  align-items: start;
}

/* ===== ARTICLE CONTENT ===== */
.article-content {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-xl);
  padding: 3rem;
  overflow: hidden;
}

.article-content .article-hero {
  margin: -3rem -3rem 2rem;
  position: relative;
  aspect-ratio: 21/9;
  overflow: hidden;
}

.article-content .article-hero img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.article-content .article-hero::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0;
  width: 100%; height: 30%;
  background: linear-gradient(to top, var(--bg-card), transparent);
}

.article-content h1 {
  font-size: clamp(1.8rem, 3vw, 2.5rem);
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 0.5rem;
  letter-spacing: -0.5px;
}

.article-content .article-meta-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border-color);
  flex-wrap: wrap;
}

.article-content .article-meta-header .date {
  color: var(--text-muted);
  font-size: 0.85rem;
}

.article-content .article-meta-header .tags {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.article-content .article-meta-header .tags .tag {
  font-size: 0.75rem;
  color: var(--accent-light);
  background: rgba(124, 92, 252, 0.12);
  padding: 0.2rem 0.6rem;
  border-radius: 100px;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.article-content .article-meta-header .tags .tag:hover {
  background: var(--accent);
  color: #fff;
}

/* ===== MARKDOWN RENDERED CONTENT ===== */
.article-body h1 {
  display: none; /* Hide first H1 since we show title separately */
}

.article-body h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 2rem 0 1rem;
  color: var(--text-primary);
}

.article-body h3 {
  font-size: 1.2rem;
  font-weight: 600;
  margin: 1.5rem 0 0.75rem;
  color: var(--text-primary);
}

.article-body p {
  margin-bottom: 1.2rem;
  color: var(--text-secondary);
  line-height: 1.8;
}

.article-body img {
  max-width: 100%;
  border-radius: var(--radius-md);
  margin: 1.5rem 0;
  display: none; /* Hide inline images since we show hero */
}

.article-body strong {
  color: var(--text-primary);
  font-weight: 600;
}

.article-body em {
  font-style: italic;
  color: var(--text-secondary);
}

.article-body a {
  color: var(--accent-light);
  text-decoration: none;
  border-bottom: 1px solid transparent;
  transition: border-color var(--transition-fast);
}

.article-body a:hover {
  border-bottom-color: var(--accent-light);
}

.article-body ul, .article-body ol {
  margin: 1rem 0 1.5rem 1.5rem;
  color: var(--text-secondary);
}

.article-body li {
  margin-bottom: 0.5rem;
  line-height: 1.7;
}

.article-body blockquote {
  border-left: 3px solid var(--accent);
  padding: 1rem 1.5rem;
  margin: 1.5rem 0;
  background: rgba(124, 92, 252, 0.05);
  border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
}

.article-body blockquote p {
  color: var(--text-primary);
  font-style: italic;
  margin-bottom: 0;
}

.article-body pre {
  background: #0d0d14;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.5rem;
  margin: 1.5rem 0;
  overflow-x: auto;
  font-size: 0.85rem;
  line-height: 1.6;
}

.article-body code {
  font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace;
  font-size: 0.85em;
}

.article-body p code {
  background: rgba(124, 92, 252, 0.1);
  color: var(--accent-light);
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
}

.article-body pre code {
  background: none;
  color: var(--text-secondary);
  padding: 0;
}

.article-body hr {
  border: none;
  border-top: 1px solid var(--border-color);
  margin: 2rem 0;
}

/* ===== SIDEBAR IN ARTICLE VIEW ===== */
.sidebar {
  position: sticky;
  top: calc(var(--header-height) + 2rem);
}

.sidebar-title {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--text-muted);
  margin-bottom: 1rem;
  padding-left: 0.5rem;
}

.sidebar-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-height: calc(100vh - var(--header-height) - 6rem);
  overflow-y: auto;
  padding-right: 0.5rem;
}

.sidebar-item {
  display: flex;
  gap: 1rem;
  padding: 0.75rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.sidebar-item:hover {
  border-color: var(--border-hover);
  background: var(--bg-card-hover);
  transform: translateX(4px);
}

.sidebar-item.active {
  border-color: var(--accent);
  box-shadow: 0 0 12px var(--accent-glow);
}

.sidebar-item-image {
  width: 70px;
  height: 55px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  flex-shrink: 0;
}

.sidebar-item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.sidebar-item-info {
  flex: 1;
  min-width: 0;
}

.sidebar-item-title {
  font-size: 0.85rem;
  font-weight: 600;
  line-height: 1.3;
  margin-bottom: 0.2rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-item-date {
  font-size: 0.72rem;
  color: var(--text-muted);
}

/* ===== ANIMATIONS ===== */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideInFromRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.stagger-1 { animation-delay: 0.1s; }
.stagger-2 { animation-delay: 0.2s; }
.stagger-3 { animation-delay: 0.3s; }
.stagger-4 { animation-delay: 0.4s; }
.stagger-5 { animation-delay: 0.5s; }
.stagger-6 { animation-delay: 0.6s; }

/* ===== PAGE TRANSITIONS ===== */
.home-view {
  transition: opacity var(--transition-med);
}

.home-view.hidden {
  display: none;
}

/* ===== FOOTER ===== */
.footer {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--text-muted);
  font-size: 0.8rem;
  border-top: 1px solid var(--border-color);
}

/* ===== RESPONSIVE: TABLET ===== */
@media (max-width: 1024px) {
  .article-view-layout {
    grid-template-columns: 1fr 300px;
    gap: 2rem;
  }
  
  .article-content {
    padding: 2rem;
  }
  
  .article-content .article-hero {
    margin: -2rem -2rem 1.5rem;
  }
}

/* ===== RESPONSIVE: MOBILE ===== */
@media (max-width: 768px) {
  :root {
    --header-height: 60px;
  }
  
  .container {
    padding: 0 1rem;
  }
  
  .header-inner {
    padding: 0 1rem;
  }
  
  .search-toggle span {
    display: none;
  }
  
  .featured-card {
    min-height: 380px;
    border-radius: var(--radius-lg);
  }
  
  .featured-card-content {
    padding: 1.5rem;
  }
  
  .featured-title {
    font-size: 1.5rem;
  }
  
  .articles-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .article-view-layout {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .sidebar {
    position: static;
  }
  
  .sidebar-list {
    max-height: none;
    flex-direction: row;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 0.5rem;
    gap: 0.75rem;
  }
  
  .sidebar-item {
    min-width: 250px;
    flex-shrink: 0;
  }
  
  .article-content {
    padding: 1.5rem;
    border-radius: var(--radius-lg);
  }
  
  .article-content .article-hero {
    margin: -1.5rem -1.5rem 1.5rem;
  }
  
  .article-content h1 {
    font-size: 1.6rem;
  }
  
  .filter-tags {
    overflow-x: auto;
    flex-wrap: nowrap;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 0.5rem;
  }
  
  .filter-tags::-webkit-scrollbar {
    display: none;
  }
  
  .read-more-btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 420px) {
  .featured-card {
    min-height: 320px;
  }
  
  .featured-badge {
    font-size: 0.65rem;
  }
  
  .article-content {
    padding: 1.25rem;
  }
  
  .article-content .article-hero {
    margin: -1.25rem -1.25rem 1rem;
  }
}
</style>
</head>
<body>

<!-- Animated Background -->
<div class="bg-orbs">
  <div class="bg-orb"></div>
  <div class="bg-orb"></div>
  <div class="bg-orb"></div>
</div>

<!-- Header -->
<header class="header" id="header">
  <div class="header-inner">
    <div class="logo" id="logo-btn">✦ TheBlog</div>
    <div class="header-actions">
      <button class="search-toggle" id="home-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span>Home</span>
      </button>
    </div>
  </div>
</header>

<!-- Main -->
<main class="main" id="main">
  <div class="container">
    <!-- Loader -->
    <div class="loader" id="loader">
      <div class="loader-spinner"></div>
      <div class="loader-text">Loading articles...</div>
    </div>

    <!-- Home View -->
    <div class="home-view" id="home-view" style="display:none;">
      
      <!-- Filters -->
      <div class="filter-section">
        <div class="filter-label">Filter by topic</div>
        <div class="filter-tags" id="filter-tags">
          <!-- Dynamically generated -->
        </div>
      </div>

      <!-- Featured -->
      <div class="featured-section" id="featured-section">
        <!-- Dynamically generated -->
      </div>

      <!-- Articles Grid -->
      <div class="articles-section">
        <div class="section-header">
          <h2 class="section-title">Latest Articles</h2>
          <span class="article-count" id="article-count"></span>
        </div>
        <div class="articles-grid" id="articles-grid">
          <!-- Dynamically generated -->
        </div>
        <div class="no-results" id="no-results">
          <div class="no-results-icon">🔍</div>
          <h3>No articles found</h3>
          <p>Try selecting a different tag filter.</p>
        </div>
      </div>
    </div>

    <!-- Article View -->
    <div class="article-view" id="article-view">
      <button class="back-btn" id="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"/>
          <polyline points="12 19 5 12 12 5"/>
        </svg>
        Back to all articles
      </button>
      <div class="article-view-layout">
        <div class="article-content" id="article-content">
          <!-- Dynamically generated -->
        </div>
        <div class="sidebar">
          <div class="sidebar-title">More Articles</div>
          <div class="sidebar-list" id="sidebar-list">
            <!-- Dynamically generated -->
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Footer -->
<footer class="footer">
  <p>✦ TheBlog — Built with vanilla HTML, CSS & JavaScript</p>
</footer>

<script>
(function() {
  'use strict';

  // ===== STATE =====
  let posts = [];
  let allTags = [];
  let activeFilters = new Set();
  let currentView = 'home'; // 'home' | 'article'
  let currentArticleIndex = -1;

  // ===== DOM REFS =====
  const loader = document.getElementById('loader');
  const homeView = document.getElementById('home-view');
  const articleView = document.getElementById('article-view');
  const filterTagsContainer = document.getElementById('filter-tags');
  const featuredSection = document.getElementById('featured-section');
  const articlesGrid = document.getElementById('articles-grid');
  const articleCount = document.getElementById('article-count');
  const noResults = document.getElementById('no-results');
  const articleContent = document.getElementById('article-content');
  const sidebarList = document.getElementById('sidebar-list');
  const backBtn = document.getElementById('back-btn');
  const logoBtn = document.getElementById('logo-btn');
  const homeBtn = document.getElementById('home-btn');
  const header = document.getElementById('header');

  // ===== MARKDOWN PARSER (simple) =====
  function parseMd(md) {
    let html = md;

    // Code blocks (``` ... ```)
    html = html.replace(/```(\w*)\n([\s\S]*?)```/g, function(m, lang, code) {
      return '<pre><code class="lang-' + lang + '">' + escapeHtml(code.trim()) + '</code></pre>';
    });

    // Images
    html = html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" loading="lazy">');

    // Links
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

    // Headers
    html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
    html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');

    // Blockquotes
    html = html.replace(/^> (.+)$/gm, '<blockquote><p>$1</p></blockquote>');
    // Merge consecutive blockquotes
    html = html.replace(/<\/blockquote>\s*<blockquote>/g, '');

    // Bold
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

    // Italic
    html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

    // Horizontal rule
    html = html.replace(/^---$/gm, '<hr>');

    // Unordered lists
    html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
    html = html.replace(/((?:<li>.*<\/li>\n?)+)/g, '<ul>$1</ul>');

    // Ordered lists
    html = html.replace(/^\d+\. (.+)$/gm, '<oli>$1</oli>');
    html = html.replace(/((?:<oli>.*<\/oli>\n?)+)/g, function(m) {
      return '<ol>' + m.replace(/<\/?oli>/g, function(t) { return t.replace('oli', 'li'); }) + '</ol>';
    });

    // Paragraphs
    const lines = html.split('\n');
    let result = [];
    let inBlock = false;

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i].trim();
      if (line.startsWith('<h') || line.startsWith('<ul') || line.startsWith('<ol') ||
          line.startsWith('<li') || line.startsWith('<pre') || line.startsWith('<blockquote') ||
          line.startsWith('<hr') || line.startsWith('<img') || line === '') {
        result.push(line);
      } else if (line.startsWith('</')) {
        result.push(line);
      } else if (!line.startsWith('<')) {
        // Check if it's just hashtags line at the end
        if (/^#\w/.test(line) && line.split(' ').every(w => w.startsWith('#'))) {
          // Skip hashtag-only lines
          continue;
        }
        result.push('<p>' + line + '</p>');
      } else {
        result.push(line);
      }
    }

    return result.join('\n');
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // ===== PARSE POSTS FILE =====
  function parsePosts(text) {
    const rawPosts = text.split('---POST---').filter(s => s.trim());
    return rawPosts.map((raw, index) => {
      const lines = raw.trim().split('\n');
      const meta = {};
      let bodyStart = 0;

      for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (line === '') {
          bodyStart = i + 1;
          break;
        }
        const colonIdx = line.indexOf(':');
        if (colonIdx > -1) {
          const key = line.substring(0, colonIdx).trim();
          const value = line.substring(colonIdx + 1).trim();
          meta[key] = value;
        }
      }

      const body = lines.slice(bodyStart).join('\n').trim();
      const tags = (meta.tags || '').split(/\s+/).filter(t => t.startsWith('#'));

      return {
        index,
        title: meta.title || 'Untitled',
        subtitle: meta.subtitle || '',
        image: meta.image || '',
        date: meta.date || '',
        tags,
        body,
        bodyHtml: parseMd(body)
      };
    });
  }

  // ===== FORMAT DATE =====
  function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
      const d = new Date(dateStr + 'T00:00:00');
      return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    } catch {
      return dateStr;
    }
  }

  // ===== RENDER FILTER TAGS =====
  function renderFilters() {
    const tagsSet = new Set();
    posts.forEach(p => p.tags.forEach(t => tagsSet.add(t)));
    allTags = Array.from(tagsSet).sort();

    let html = allTags.map(tag =>
      `<span class="filter-tag" data-tag="${tag}">${tag}</span>`
    ).join('');
    html += '<button class="filter-clear" id="filter-clear">Clear filters</button>';
    filterTagsContainer.innerHTML = html;

    // Event listeners
    filterTagsContainer.querySelectorAll('.filter-tag').forEach(el => {
      el.addEventListener('click', () => toggleFilter(el.dataset.tag));
    });

    const clearBtn = document.getElementById('filter-clear');
    clearBtn.addEventListener('click', clearFilters);
  }

  function toggleFilter(tag) {
    if (activeFilters.has(tag)) {
      activeFilters.delete(tag);
    } else {
      activeFilters.add(tag);
    }
    updateFilterUI();
    filterArticles();
  }

  function clearFilters() {
    activeFilters.clear();
    updateFilterUI();
    filterArticles();
  }

  function updateFilterUI() {
    filterTagsContainer.querySelectorAll('.filter-tag').forEach(el => {
      el.classList.toggle('active', activeFilters.has(el.dataset.tag));
    });
    const clearBtn = document.getElementById('filter-clear');
    if (clearBtn) {
      clearBtn.classList.toggle('visible', activeFilters.size > 0);
    }
  }

  function filterArticles() {
    const filteredPosts = getFilteredPosts();

    // Update featured
    if (filteredPosts.length > 0) {
      renderFeatured(filteredPosts[0]);
      featuredSection.style.display = 'block';
    } else {
      featuredSection.style.display = 'none';
    }

    // Update grid (skip featured)
    const gridPosts = filteredPosts.slice(1);
    const cards = articlesGrid.querySelectorAll('.article-card');

    cards.forEach(card => {
      const idx = parseInt(card.dataset.index);
      const isVisible = gridPosts.some(p => p.index === idx);
      if (isVisible) {
        card.classList.remove('hidden');
        // Re-trigger animation
        card.style.animation = 'none';
        card.offsetHeight; // trigger reflow
        card.style.animation = '';
        card.classList.add('visible');
      } else {
        card.classList.add('hidden');
        card.classList.remove('visible');
      }
    });

    // Also handle if featured changed, we need to show/hide its old card position
    // and update grid cards list
    renderGrid(filteredPosts);

    // Article count
    articleCount.textContent = `${filteredPosts.length} article${filteredPosts.length !== 1 ? 's' : ''}`;

    // No results
    noResults.classList.toggle('visible', filteredPosts.length === 0);
  }

  function getFilteredPosts() {
    if (activeFilters.size === 0) return posts;
    return posts.filter(p => {
      return Array.from(activeFilters).some(f => p.tags.includes(f));
    });
  }

  // ===== RENDER FEATURED =====
  function renderFeatured(post) {
    featuredSection.innerHTML = `
      <div class="featured-card" data-index="${post.index}">
        <img class="featured-card-bg" src="${post.image}" alt="${post.title}" loading="lazy">
        <div class="featured-card-overlay"></div>
        <div class="featured-card-content">
          <div class="featured-badge">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            Featured
          </div>
          <h1 class="featured-title">${post.title}</h1>
          <p class="featured-subtitle">${post.subtitle}</p>
          <div class="featured-meta">
            <span class="featured-date">${formatDate(post.date)}</span>
            <div class="featured-tags">
              ${post.tags.map(t => `<span class="tag">${t}</span>`).join('')}
            </div>
          </div>
          <button class="read-more-btn">
            Read Article
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"/>
              <polyline points="12 5 19 12 12 19"/>
            </svg>
          </button>
        </div>
      </div>
    `;

    featuredSection.querySelector('.featured-card').addEventListener('click', () => {
      showArticle(post.index);
    });
  }

  // ===== RENDER GRID =====
  function renderGrid(filteredPosts) {
    const gridPosts = filteredPosts.slice(1); // Skip featured

    articlesGrid.innerHTML = gridPosts.map((post, i) => `
      <div class="article-card" data-index="${post.index}" style="animation: fadeInUp 0.6s ease forwards; animation-delay: ${0.1 + i * 0.1}s; opacity: 0;">
        <div class="article-card-image">
          <img src="${post.image}" alt="${post.title}" loading="lazy">
        </div>
        <div class="article-card-body">
          <h3 class="article-card-title">${post.title}</h3>
          <p class="article-card-subtitle">${post.subtitle}</p>
          <div class="article-card-footer">
            <span class="article-card-date">${formatDate(post.date)}</span>
            <div class="article-card-tags">
              ${post.tags.slice(0, 2).map(t => `<span class="tag">${t}</span>`).join('')}
            </div>
          </div>
        </div>
      </div>
    `).join('');

    articlesGrid.querySelectorAll('.article-card').forEach(card => {
      card.addEventListener('click', () => {
        showArticle(parseInt(card.dataset.index));
      });
    });

    articleCount.textContent = `${filteredPosts.length} article${filteredPosts.length !== 1 ? 's' : ''}`;
    noResults.classList.toggle('visible', filteredPosts.length === 0);
  }

  // ===== SHOW ARTICLE =====
  function showArticle(index) {
    currentArticleIndex = index;
    const post = posts[index];

    // Render article content
    articleContent.innerHTML = `
      <div class="article-hero">
        <img src="${post.image}" alt="${post.title}" loading="lazy">
      </div>
      <h1>${post.title}</h1>
      <div class="article-meta-header">
        <span class="date">${formatDate(post.date)}</span>
        <div class="tags">
          ${post.tags.map(t => `<span class="tag" data-tag="${t}">${t}</span>`).join('')}
        </div>
      </div>
      <div class="article-body">${post.bodyHtml}</div>
    `;

    // Tag clicks in article
    articleContent.querySelectorAll('.article-meta-header .tag').forEach(el => {
      el.addEventListener('click', () => {
        activeFilters.clear();
        activeFilters.add(el.dataset.tag);
        goHome();
      });
    });

    // Render sidebar
    const otherPosts = posts.filter(p => p.index !== index);
    sidebarList.innerHTML = otherPosts.map((p, i) => `
      <div class="sidebar-item" data-index="${p.index}" style="animation: slideInFromRight 0.4s ease forwards; animation-delay: ${0.1 + i * 0.08}s; opacity: 0;">
        <div class="sidebar-item-image">
          <img src="${p.image}" alt="${p.title}" loading="lazy">
        </div>
        <div class="sidebar-item-info">
          <div class="sidebar-item-title">${p.title}</div>
          <div class="sidebar-item-date">${formatDate(p.date)}</div>
        </div>
      </div>
    `).join('');

    sidebarList.querySelectorAll('.sidebar-item').forEach(item => {
      item.addEventListener('click', () => {
        const idx = parseInt(item.dataset.index);
        showArticle(idx);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });

    // Switch views
    currentView = 'article';
    homeView.style.display = 'none';
    articleView.classList.add('active');
    articleView.style.display = 'block';
    
    // Re-trigger animation
    articleView.style.animation = 'none';
    articleView.offsetHeight;
    articleView.style.animation = 'fadeInUp 0.5s ease forwards';

    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Update URL hash
    history.pushState(null, '', '#article-' + index);
  }

  // ===== GO HOME =====
  function goHome() {
    currentView = 'home';
    currentArticleIndex = -1;

    articleView.classList.remove('active');
    articleView.style.display = 'none';

    homeView.style.display = 'block';
    homeView.style.animation = 'fadeInUp 0.5s ease forwards';

    updateFilterUI();
    filterArticles();

    window.scrollTo({ top: 0, behavior: 'smooth' });
    history.pushState(null, '', window.location.pathname);
  }

  // ===== HEADER SCROLL EFFECT =====
  let lastScroll = 0;
  window.addEventListener('scroll', () => {
    const scrollY = window.scrollY;
    header.classList.toggle('scrolled', scrollY > 20);
    lastScroll = scrollY;
  });

  // ===== EVENT LISTENERS =====
  backBtn.addEventListener('click', goHome);
  logoBtn.addEventListener('click', () => {
    activeFilters.clear();
    goHome();
  });
  homeBtn.addEventListener('click', () => {
    activeFilters.clear();
    goHome();
  });

  // Handle browser back/forward
  window.addEventListener('popstate', () => {
    const hash = window.location.hash;
    if (hash.startsWith('#article-')) {
      const idx = parseInt(hash.replace('#article-', ''));
      if (!isNaN(idx) && idx >= 0 && idx < posts.length) {
        showArticle(idx);
      }
    } else {
      goHome();
    }
  });

  // ===== INTERSECTION OBSERVER FOR CARD ANIMATIONS =====
  function setupScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.article-card').forEach(card => {
      observer.observe(card);
    });
  }

  // ===== LOAD & INIT =====
  async function init() {
    try {
      // Try loading from same directory
      const response = await fetch('posts.md');
      if (!response.ok) throw new Error('Failed to load posts.md');
      const text = await response.text();
      
      posts = parsePosts(text);

      if (posts.length === 0) {
        loader.innerHTML = '<p style="color:var(--text-muted)">No posts found.</p>';
        return;
      }

      // Hide loader, show content
      loader.style.display = 'none';

      // Render
      renderFilters();

      // Check if URL has article hash
      const hash = window.location.hash;
      if (hash.startsWith('#article-')) {
        const idx = parseInt(hash.replace('#article-', ''));
        if (!isNaN(idx) && idx >= 0 && idx < posts.length) {
          homeView.style.display = 'none';
          showArticle(idx);
          return;
        }
      }

      // Show home view
      homeView.style.display = 'block';
      renderFeatured(posts[0]);
      renderGrid(posts);
      
      articleCount.textContent = `${posts.length} article${posts.length !== 1 ? 's' : ''}`;

      // Setup scroll animations after a tick
      requestAnimationFrame(setupScrollAnimations);

    } catch (err) {
      console.error('Error loading posts:', err);
      loader.innerHTML = `
        <div style="text-align:center; color: var(--text-muted);">
          <p style="font-size:2rem; margin-bottom:1rem;">📄</p>
          <h3 style="color: var(--text-secondary); margin-bottom:0.5rem;">Could not load posts</h3>
          <p>Make sure <code style="color:var(--accent-light)">posts.md</code> is served from the same origin.</p>
          <p style="margin-top:0.5rem; font-size:0.8rem;">Error: ${err.message}</p>
        </div>
      `;
    }
  }

  // Start
  init();
})();
</script>
</body>
</html>
```

## How to Use

1. **Save both files** (`index.html` and `posts.md`) in the same directory.

2. **Serve them with any static server** (required for `fetch` to work):
   ```bash
   # Using Python
   python -m http.server 8000
   
   # Using Node.js
   npx serve .
   
   # Using PHP
   php -S localhost:8000
   ```

3. **Open** `http://localhost:8000` in your browser.

## Features Implemented

- **Single `.md` file** containing all posts separated by `---POST---` delimiters with YAML-like metadata headers
- **Custom Markdown parser** (no libraries) supporting headings, bold, italic, code blocks, blockquotes, lists, images, links, and horizontal rules
- **Featured article** — the first (or first filtered) post gets a large hero card with image overlay, gradient, and animated hover effects
- **Article cards grid** — remaining posts shown as responsive cards with image previews, titles, subtitles, dates, and tags
- **Hashtag filtering** — clickable tag pills at the top; posts are filtered if they match ANY selected tag; clear button to reset
- **Article view** — clicking any post opens a full article layout with rendered markdown content and a sidebar listing other articles for easy navigation
- **Responsive design** — desktop shows grid + sidebar; tablet adjusts columns; mobile shows single column with horizontal scrolling sidebar
- **Animations** — floating background orbs, staggered fadeInUp for cards, slideInFromRight for sidebar items, hover transforms with glow shadows, smooth page transitions
- **URL hash routing** — `#article-N` in URL so browser back/forward works
- **Dark theme** with accent color scheme, glassmorphism header, custom scrollbar
- **Zero dependencies** — pure vanilla HTML, CSS, and JavaScript