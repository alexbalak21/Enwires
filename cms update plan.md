# Architectural Blueprint: Custom PHP Nested Block CMS with Tailwind CSS

This document outlines the detailed technical architecture, database schemas, frontend implementation strategies, and production rollout steps for a custom PHP Content Management System featuring nested visual blocks and a dynamic route compiler.

---

## 1. System & Folder Architecture

To ensure separation of concerns, the application follows a modern monolithic MVC pattern without heavy dependencies.

```text
custom-cms/
├── config/
│   └── database.php       # PDO Connection Singleton
├── core/
│   ├── Router.php         # Dynamic Route Engine
│   └── BlockRenderer.php  # Recursive Template Processor
├── public/
│   ├── .htaccess          # URL Apache Rewrite rules
│   ├── index.php          # Front Controller (Application Entry)
│   └── assets/
│       ├── css/
│       └── js/
│           └── builder.js # Drag-and-drop / nesting engine
├── views/
│   ├── admin/
│   │   ├── dashboard.php  # Page Listing
│   │   └── builder.php    # Layout Workspace (Alpine.js Canvas)
│   └── public/
│       └── layout.php     # Public rendering wrapper
└── storage/
    └── tailwind-cache/    # Static class cache files
```

---

## 2. Database Schema Definition

A Relational Tree mapping structure is used. The `blocks` table handles deep nesting by referencing its own primary key via `parent_id`.

```sql
-- 1. Create Pages Table
CREATE TABLE `pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `meta_description` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create Blocks Table (Self-Referencing Tree)
CREATE TABLE `blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT NOT NULL,
    `parent_id` INT NULL DEFAULT NULL,
    `type` VARCHAR(50) NOT NULL, -- e.g., 'div', 'section', 'p', 'h1', 'img'
    `tailwind_classes` TEXT NULL, -- e.g., 'flex flex-row gap-4 p-6 w-full'
    `content` TEXT NULL, -- Inner text or element specific raw variables
    `sort_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `blocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Indexes for fast recursive lookups
CREATE INDEX idx_page_blocks ON blocks(page_id, parent_id, sort_order);
```

---

## 3. Core Engine Implementation

### Dynamic Router (`core/Router.php`)
This module captures inbound requests, matches them against database entries, and resolves the context payload dynamically.

```php
<?php
namespace Core;

use PDO;

class Router {
    private PDO $db;

    public function __construct(PDO $db) {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db = $db;
    }

    public function resolve(string $requestUri): array {
        // Sanitize and normalize path string
        $slug = trim(parse_url($requestUri, PHP_URL_PATH), '/');
        if ($slug === '') {
            $slug = 'home';
        }

        // Match against database records
        $stmt = $this->db->prepare("SELECT id, title, meta_description FROM pages WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$page) {
            return ['status' => 404, 'data' => null];
        }

        return ['status' => 200, 'data' => $page];
    }
}
```

### Recursive Multi-Layer Block Renderer (`core/BlockRenderer.php`)
Resolves multi-level arrays using a performance-optimized recursive algorithm to print nested components natively.

```php
<?php
namespace Core;

class BlockRenderer {
    /**
     * Converts a flat layout array into a multi-layered object tree
     */
    public static function buildTree(array &$elements, $parentId = null): array {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = self::buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    /**
     * Traverses the node tree structure and pipes out functional HTML
     */
    public static function render(array $matrix): string {
        $html = '';
        foreach ($matrix as $node) {
            $tag = htmlspecialchars($node['type']);
            $classes = htmlspecialchars($node['tailwind_classes'] ?? '');
            
            // Render specific self-closing tags
            if ($tag === 'img') {
                $src = htmlspecialchars($node['content'] ?? '');
                $html .= "<img class=\"{$classes}\" src=\"{$src}\" alt=\"CMS Image Layout\" />";
                continue;
            }

            // Open element container wrapper
            $html .= "<{$tag} class=\"{$classes}\">";

            // Recurse into children nodes if present
            if (!empty($node['children'])) {
                $html .= self::render($node['children']);
            } 
            // Otherwise, render plain-text/inline variables
            elseif (!empty($node['content'])) {
                $html .= htmlspecialchars($node['content']);
            }

            // Close current element scope
            $html .= "</{$tag}>";
        }
        return $html;
    }
}
```

---

## 4. Front Controller Entry Point (`public/index.php`)

```php
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/BlockRenderer.php';

use Core\Router;
use Core\BlockRenderer;

// Initialize Database connection singleton
$db = \Config\Database::connect();

$router = new Router($db);
$route = $router->resolve($_SERVER['REQUEST_URI']);

if ($route['status'] === 404) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Error: Page Not Found</h1>";
    exit;
}

$page = $route['data'];

// Query all layout items matching current view context sorted explicitly
$stmt = $db->prepare("SELECT * FROM blocks WHERE page_id = :page_id ORDER BY sort_order ASC");
$stmt->execute(['page_id' => $page['id']]);
$flatBlocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map flat components to recursive structures
$layoutTree = BlockRenderer::buildTree($flatBlocks);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title']); ?></title>
    <!-- Live Dynamic Utility Compilation Script Engine -->
    <script src="https://tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {} }
        }
    </script>
</head>
<body class="antialiased min-h-screen">
    <!-- Execute layout streaming parsing context -->
    <?php echo BlockRenderer::render($layoutTree); ?>
</body>
</html>
```

---

## 5. Visual Workspace Admin Interface Strategy

To manage recursive nested nesting visually without complex JavaScript architectures (like React or Vue bundles), choose between two lightweight runtime setups:

### Strategy A: Alpine.js + Dragula / SortableJS (Highly Interactive UI)
* **How it operates:** Maintain the page structure as a dynamic JSON object array inside an Alpine.js store component wrapper.
* **Component Layering:** Render the blocks using standard `<template x-for="block in blocks">` expressions recursively.
* **Drag-and-Drop Execution:** Bind HTML elements using `SortableJS`. When an item drops inside another block context window element, catch the `onEnd` mutation event payload hook, extract the target container ID attributes, and update the nested JS array state dynamically before pushing a structural JSON update back to the server storage endpoint via `fetch()`.

### Strategy B: HTMX + HTML Partial Canvas (Server-Driven Visual Building)
* **How it operates:** The builder canvas works directly with database updates on the fly.
* **Action Handlers:** Modifying styling strings, shifting ordering parameters, or injecting a child element uses custom backend triggers via HTMX commands (e.g., `hx-post="/admin/api/add-child-block"`).
* **DOM Swapping Execution:** The backend instantly records changes to the MySQL database tables, calls `BlockRenderer::render()` on the parent component layout section context, and returns purely composed HTML directly back to the active page view without forcing standard full page refreshes.

---

## 6. CSS Optimization & Asset Compiling Pipeline

Utility classes typed straight into an admin panel dashboard bypass the standard offline static compilation paths of the Tailwind CSS CLI. Choose an approach based on the target runtime environment:

### Phase 1: Development Environment Workflow
Inject the dedicated Tailwind Play engine script context explicitly inside the workspace templates header:
```html
<script src="https://tailwindcss.com"></script>
```
* **Benefit:** Allows rapid UI modification and layout styling directly within the browser client context.

### Phase 2: Production Safe Optimization Strategy
To remove client-side styling compilation pipelines entirely and serve optimal stylesheets:
1. Set up an administrative listener script hook that triggers whenever a page layout workspace save transaction resolves.
