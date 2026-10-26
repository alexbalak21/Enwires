# Enwires CMS v3
# Block Builder & Page Builder Architecture Guide

## Overview

This document describes the complete architecture, storage model, rendering system, editor experience, and implementation strategy for the next generation of Enwires CMS.

The goal is to create a professional visual CMS while preserving the project's original principles:

- Lightweight
- Framework-independent backend
- No Composer requirement
- JSON-first architecture
- Multilingual
- Block-based editing
- Easy deployment
- Human-readable data
- Future-ready API support

The architecture separates:

1. Content
2. Structure
3. Presentation

This separation prevents editor content from becoming coupled to Bootstrap, Tailwind, or any future frontend technology.

---

# Philosophy

The CMS must never store:

- HTML layouts
- CSS classes
- Bootstrap classes
- Tailwind classes
- JavaScript snippets

Instead it stores:

- Semantic content
- Layout intentions
- Block settings

Example:

Good:

```json
{
  "layout": "two-columns",
  "theme": "dark"
}
```

Bad:

```json
{
  "class": "container mx-auto flex gap-8"
}
```

The first approach allows changing renderer technology without touching content.

The second approach permanently couples content to a CSS framework.

---

# Core Concepts

The CMS contains six primary entities:

```text
Site
 ├── Pages
 ├── Blocks
 ├── Media
 ├── Languages
 ├── Users
 └── Settings
```

---

# Content Model

## Site

Represents the entire website.

Contains:

```text
Site Settings
Navigation
Footer
Languages
Domains
SEO Defaults
```

---

## Pages

Pages are containers.

Pages do not contain design.

Pages contain references to blocks.

Example:

```text
Home Page

Hero
Features
Gallery
CTA
Footer
```

---

## Blocks

Blocks are reusable content sections.

Examples:

```text
Hero
Cards
Text + Image
Gallery
FAQ
Testimonials
CTA
Pricing Table
```

Every block has:

```text
Type
Settings
Content
Translations
```

---

## Media

Media represents uploaded assets.

Examples:

```text
Images
Documents
Videos
SVG
```

---

## Languages

Languages contain translated text only.

Structure remains shared.

---

# Recommended Folder Structure

```text
content/

├── pages/
│   ├── page-home.json
│   ├── page-product.json
│   └── page-contact.json
│
├── blocks/
│   ├── hero-1.json
│   ├── hero-2.json
│   ├── gallery-1.json
│   └── cta-1.json
│
├── translations/
│   ├── en.json
│   ├── fr.json
│   ├── zh.json
│   └── ja.json
│
├── media.json
│
└── settings.json
```

---

# Page Builder

## Purpose

The Page Builder is used by editors.

Its purpose is assembling pages.

Editors should not create block types.

Editors only use existing block types.

---

## Capabilities

A page editor can:

- Create pages
- Rename pages
- Duplicate pages
- Delete pages
- Reorder blocks
- Add blocks
- Remove blocks
- Configure block settings

---

## Example Page

```json
{
  "id": "home",
  "title": "Home",
  "slug": "",
  "status": "published",

  "seo": {
    "title": "Enwires",
    "description": "Industrial wiring solutions"
  },

  "blocks": [
    "hero_1",
    "features_1",
    "gallery_1",
    "cta_1"
  ]
}
```

---

# Page Builder User Interface

## Left Side

Page structure.

```text
HOME PAGE

[ Hero ]
[ Features ]
[ Gallery ]
[ CTA ]

+ Add Block
```

---

## Right Side

Live preview.

Rendered exactly as the public page.

---

## Toolbar

```text
Save Draft
Publish
History
Preview
SEO
Settings
```

---

# Block Builder

## Purpose

The Block Builder is used by developers and administrators.

It defines block schemas.

It creates the building blocks available in the Page Builder.

Think of it like:

```text
Page Builder = Use Blocks

Block Builder = Create Blocks
```

---

# Block Definition

Each block type requires:

```text
Name
Category
Fields
Default Values
Renderer
Validation Rules
```

---

# Block Registry

Single source of truth.

Example:

```php
return [

'hero' => [

    'label' => 'Hero',

    'category' => 'Marketing',

    'icon' => 'hero',

    'fields' => [

        [
            'name' => 'eyebrow',
            'type' => 'text'
        ],

        [
            'name' => 'title',
            'type' => 'text'
        ],

        [
            'name' => 'description',
            'type' => 'textarea'
        ],

        [
            'name' => 'image',
            'type' => 'image'
        ]

    ]

]

];
```

---

# Block Storage

## Hero Example

```json
{
  "id": "hero_1",

  "type": "hero",

  "settings": {

    "theme": "dark",

    "container": "wide",

    "spacing": "large"

  },

  "content": {

    "eyebrow": "Industrial Excellence",

    "title": "Custom Wiring Solutions",

    "description": "Built for demanding environments"

  }
}
```

---

# Content vs Settings

## Content

Content is translated.

Example:

```text
Title
Subtitle
Description
Button Labels
```

---

## Settings

Settings remain shared.

Example:

```text
Background Image
Dark Theme
Container Width
Spacing
Alignment
```

---

# Translation System

## Recommendation

Keep translations separated.

### English

```json
{
  "hero_1": {

    "title": "Welcome",

    "description": "Hello world"

  }
}
```

### French

```json
{
  "hero_1": {

    "title": "Bienvenue",

    "description": "Bonjour le monde"

  }
}
```

---

# Shared Structure Rule

Only content is translated.

Structure remains shared.

Good:

```text
Hero
Features
Gallery
CTA
```

Exists once.

---

Bad:

```text
English Page Layout
French Page Layout
Japanese Page Layout
```

This becomes impossible to maintain.

---

# Supported Field Types

## Text

```json
{
  "type": "text"
}
```

---

## Textarea

```json
{
  "type": "textarea"
}
```

---

## Rich Text

```json
{
  "type": "richtext"
}
```

---

## Image

```json
{
  "type": "image"
}
```

---

## Gallery

```json
{
  "type": "gallery"
}
```

---

## Link

```json
{
  "type": "link"
}
```

---

## Select

```json
{
  "type": "select"
}
```

---

## Color

```json
{
  "type": "color"
}
```

---

## Checkbox

```json
{
  "type": "checkbox"
}
```

---

## Repeater

```json
{
  "type": "repeater"
}
```

Used for:

```text
Cards
FAQ
Testimonials
Pricing Plans
```

---

# Repeater Example

FAQ Block:

```json
{
  "questions": [

    {
      "title": "Question 1",
      "text": "Answer 1"
    },

    {
      "title": "Question 2",
      "text": "Answer 2"
    }

  ]
}
```

---

# Rendering Pipeline

## Step 1

Load page.

```php
$page = loadPage($slug);
```

---

## Step 2

Load block references.

```php
$blocks = loadBlocks($page['blocks']);
```

---

## Step 3

Load translations.

```php
$lang = loadLanguage('fr');
```

---

## Step 4

Merge translated content.

```php
$block = mergeContent(
    $block,
    $translations
);
```

---

## Step 5

Render block template.

```php
renderBlock($block);
```

---

# Block Rendering

Example:

```php
switch ($block['type']) {

    case 'hero':
        include 'blocks/hero.php';
        break;

    case 'gallery':
        include 'blocks/gallery.php';
        break;
}
```

---

# Tailwind Strategy

Never store Tailwind classes.

Instead create mappings.

Example:

```php
$spacing = [

    'small' => 'py-8',
    'medium' => 'py-16',
    'large' => 'py-32'

];
```

---

Block stores:

```json
{
  "spacing": "large"
}
```

Renderer decides:

```html
<section class="py-32">
```

---

# Bootstrap Strategy

Same data.

Different renderer.

Block:

```json
{
  "spacing": "large"
}
```

Renderer:

```html
<section class="py-5">
```

Same content.

Different frontend.

---

# Theme System

Supported themes:

```text
Default
Dark
Light
Primary
Secondary
```

Stored as:

```json
{
  "theme": "dark"
}
```

---

# Container System

```json
{
  "container": "boxed"
}
```

Options:

```text
Narrow
Boxed
Wide
Full Width
```

---

# Spacing System

Stored:

```json
{
  "spacing": "large"
}
```

Values:

```text
None
Small
Medium
Large
Extra Large
```

---

# Global Blocks

Reusable blocks.

Examples:

```text
Newsletter CTA
Footer Banner
Global Notice
```

Storage:

```json
{
  "id": "global_cta",
  "global": true
}
```

Pages only reference them.

---

# Draft System

Every page has:

```json
{
  "status": "draft"
}
```

Possible values:

```text
draft
published
scheduled
archived
```

---

# Version System

Every save creates:

```text
versions/

home/
 ├── v1.json
 ├── v2.json
 ├── v3.json
```

---

# Autosave

Every 10 seconds:

```text
autosaves/

home/

draft.json
```

---

# Media Library

Media file example:

```json
{
  "id": "img_001",

  "filename": "factory.jpg",

  "alt": "Factory production line",

  "width": 1920,

  "height": 1080,

  "size": 245000
}
```

---

# Future API

## Page

```http
GET /api/pages
```

---

## Single Page

```http
GET /api/pages/home
```

---

## Block

```http
GET /api/blocks/hero_1
```

---

# Future Headless Support

Because content is stored without HTML, the CMS can later support:

```text
PHP Website
React
Vue
Next.js
Nuxt
Mobile Application
Desktop Application
```

without changing stored content.

---

# Architecture Summary

The CMS must follow one fundamental rule:

```text
Pages define structure.

Blocks define content sections.

Translations define language.

Settings define appearance.

Renderers define HTML.
```

Never store frontend implementation details inside content.

Store only semantic information and let renderers transform it into Bootstrap, Tailwind, custom CSS, React components, or any future presentation layer.