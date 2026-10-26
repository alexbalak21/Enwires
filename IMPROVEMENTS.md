# Enwires CMS - Improvement Roadmap

## Vision

Enwires should evolve from a website-specific block editor into a complete lightweight CMS while preserving its original philosophy:

- No framework
- No Composer dependency
- Human-readable code
- Easy deployment
- Shared hosting compatibility
- File-based first architecture
- Modern editing experience
- Multilingual by design

The goal is not to compete with WordPress, but to become the best self-hosted CMS for small businesses and developers who want simplicity, maintainability, and full control.

---

# Priority 1 - Core Content Workflow

## Draft / Publish Workflow

### Current

Every save becomes public immediately.

### New States

- Draft
- Published
- Scheduled
- Archived

### Features

- Save Draft
- Publish Now
- Schedule Publication
- Unpublish
- Archive Page

### Builder UI

```text
[DRAFT]
[PUBLISHED]
[SCHEDULED]
[ARCHIVED]
```

### Storage Example

```json
{
  "status": "draft",
  "published_at": null,
  "updated_at": "2026-10-26T12:00:00Z"
}
```

### Benefits

- Safe editing
- Editorial workflow
- Content preparation before launch
- Scheduled releases

---

# Version History

## Current

Backups exist but restoration is manual.

## Improvements

### Features

- Automatic version creation
- Version browser
- Restore version
- Compare versions
- Version notes
- Author tracking

### Storage Structure

```text
data/versions/
  home/
    v1.json
    v2.json
    v3.json
```

### UI

```text
History

26 Oct 2026
Alex
Updated Hero CTA

[View]
[Restore]
[Compare]
```

### Diff View

```diff
- Request Information
+ Request a Quote
```

---

# Autosave

### Features

- Save draft every 10 seconds
- Auto-recover after browser crash
- Restore previous session

### UI

```text
Saved automatically 12 seconds ago
```

---

# Content Locking

Prevent simultaneous modifications.

### Examples

```text
Alex is currently editing this page.
```

Options:

```text
View Only
Request Control
Force Unlock (Admin)
```

---

# Priority 2 - Dynamic Page Management

## Dynamic Pages

### Current

Pages are hardcoded:

- Home
- Product
- Contact
- 404

### Goal

Allow users to create unlimited pages.

### Examples

```text
About Us
Services
Careers
FAQ
Pricing
News
Partners
```

### Required Fields

```text
Title
Slug
Status
SEO Data
Template
Parent
```

### Storage

```json
{
  "id": "page_001",
  "slug": "about",
  "template": "default",
  "status": "published"
}
```

---

# Page Templates

Provide multiple page types.

### Templates

#### Standard

```text
Header
Blocks
Footer
```

#### Landing Page

```text
Blocks Only
```

#### Legal

```text
Header
Content
Footer
```

#### Blog Listing

```text
Header
Post List
Footer
```

#### Product Catalog

```text
Header
Products
Footer
```

---

# Page Duplication

### Features

- Duplicate page
- Duplicate structure only
- Duplicate page with translations

---

# Priority 3 - Media Management

## Media Library

### New Section

```text
Media
```

### Features

- Upload
- Search
- Organize
- Reuse

### Metadata

```text
Filename
Size
Dimensions
Upload Date
Type
Alt Text
Usage Count
```

---

## Folder Organization

### Examples

```text
Products/
Hero/
Backgrounds/
Blog/
Downloads/
```

---

## Media Search

Search by:

- Filename
- Alternative text
- Folder
- File type

---

## Unused Asset Detection

Show:

```text
Unused Assets

hero-old.jpg
banner-2024.webp
```

Actions:

```text
Delete
Archive
Download
```

---

## Bulk Operations

### Features

- Multi-select
- Bulk delete
- Bulk move
- Bulk edit metadata

---

## Image Optimization

Automatically generate:

```text
hero-xl.webp
hero-lg.webp
hero-md.webp
hero-sm.webp
```

### Benefits

- Smaller page sizes
- Faster loading
- Better Core Web Vitals

---

## Focal Point Selection

Choose image focus:

```text
      X
```

Used for:

- Hero sections
- Mobile crops
- Open Graph previews

---

# Priority 4 - Advanced Block System

## Global Blocks

Create reusable blocks.

### Examples

```text
Newsletter CTA
Company Banner
Global Notice
Contact CTA
```

Changes update everywhere.

---

## Block Templates

Provide predefined layouts.

### Examples

```text
Hero + CTA
Features Grid
Statistics
FAQ
Testimonials
Pricing Table
Contact Section
```

---

## Block Categories

```text
Layout
Content
Media
Marketing
Forms
Commerce
```

---

## Nested Blocks

Allow blocks inside other blocks.

### Example

```text
Section
 ├── Hero
 ├── Cards
 └── CTA
```

---

## Custom CSS Classes

Allow advanced styling.

### Example

```text
custom-shadow
animated
highlight
```

---

## Visibility Rules

Show block only:

- Desktop
- Tablet
- Mobile

Or:

- Logged-in users
- Specific languages

---

# Priority 5 - Blogging Platform

## Blog Module

### Features

- Posts
- Categories
- Tags
- Authors
- Featured Images

---

## Post Fields

```text
Title
Slug
Excerpt
Content
Featured Image
SEO Title
SEO Description
Publication Date
```

---

## Categories

### Examples

```text
Industry
Products
Company News
Guides
```

---

## Tags

Examples:

```text
Wires
Energy
Manufacturing
Innovation
```

---

## Related Articles

Generated automatically using:

- Categories
- Tags

---

## RSS Feed

Generate:

```text
/feed.xml
```

---

# Priority 6 - Search

## Global Search

Search across:

- Pages
- Blocks
- Translations
- Images
- Users
- Blog Posts

### Example

```text
Search:

factory
```

Results:

```text
Home Hero Title
Product Description
Blog Article
French Translation
Image Metadata
```

---

## Command Palette

Shortcut:

```text
Ctrl + K
```

Examples:

```text
Open Product
Create Page
Upload Image
Manage Users
```

---

# Priority 7 - Multilingual Enhancements

## Translation Dashboard

Display completion percentage.

### Example

```text
English     100%
French       97%
Chinese      83%
Japanese     74%
```

---

## Missing Translation Report

Show untranslated fields.

### Example

```text
Home Hero

French:
Missing CTA Text

Japanese:
Missing Description
```

---

## Translation Memory

Automatically suggest previous translations.

### Example

```text
Contact Us

Suggested:
Contactez-nous
```

---

## Translation Export

Export:

```text
JSON
CSV
XLIFF
```

---

## Machine Translation Integration

Optional provider support:

- DeepL
- Google Translate
- Azure Translation

Suggestions only, never automatic publication.

---

# Priority 8 - User Management

## Roles

### Super Admin

Full access.

### Admin

Manage content and settings.

### Editor

Manage content only.

### Translator

Edit translations only.

### Viewer

Read-only access.

---

## Permissions Matrix

```text
Feature            Admin Editor Translator Viewer

Pages                ✓      ✓         -       -
Translations         ✓      ✓         ✓       -
Media                ✓      ✓         -       -
Users                ✓      -         -       -
Settings             ✓      -         -       -
```

---

## Activity Log

Track all actions.

### Example

```text
Alex
Updated Product Hero

26 Oct 2026
14:32
```

---

## Audit Trail

Store:

```text
Before
After
User
Date
IP
```

---

# Priority 9 - Security

## Login Rate Limiting

### Example

```text
5 failed attempts
↓
15-minute lockout
```

---

## Progressive Delays

```text
Attempt 1 = 0s
Attempt 2 = 2s
Attempt 3 = 5s
Attempt 4 = 10s
```

---

## Two-Factor Authentication

Methods:

- Email
- TOTP Authenticator App

---

## Session Management

Display:

```text
Current Device
Last Activity
IP Address
```

Allow:

```text
Terminate Session
```

---

## Security Notifications

Notify admins for:

- Multiple login failures
- Password changes
- New device login

---

# Priority 10 - SEO Suite

## Sitemap Generator

Generate automatically:

```text
sitemap.xml
```

Include:

- Pages
- Blog Posts
- Languages

---

## Robots.txt Generator

Create and update automatically.

### Examples

```text
Disallow: /admin/
Allow: /
```

---

## SEO Analyzer

Check:

- Missing title
- Missing description
- Duplicate keywords
- Missing H1
- Missing image alt text

---

## Open Graph Preview

Preview appearance for:

- Facebook
- LinkedIn
- X
- Discord

---

## Structured Data

Support:

```json
Organization
Website
Breadcrumb
Article
Product
FAQ
```

---

# Priority 11 - Forms

## Form Builder

Create forms visually.

### Field Types

- Text
- Email
- Phone
- Number
- Date
- Select
- Checkbox
- Textarea
- File Upload

---

## Form Management

### Features

- Email notifications
- CSV export
- Spam protection
- Submission history

---

## Saved Submissions

New module:

```text
Form Entries
```

---

# Priority 12 - Analytics

## Dashboard Statistics

Display:

- Pages
- Posts
- Images
- Users
- Languages

---

## Content Statistics

Show:

- Most visited pages
- Most edited pages
- Most used images
- Missing translations

---

## SEO Statistics

Track:

- Indexed pages
- Missing metadata
- Broken links

---

# Priority 13 - Developer Experience

## JSON Schema Validation

Validate before saving.

Checks:

- Required fields
- Block structure
- Translation integrity
- Media references

---

## API Layer

### Public API

```http
GET /api/pages
GET /api/page/{slug}
GET /api/navigation
GET /api/blog
```

---

## Authenticated API

```http
POST /api/page
PUT /api/page
DELETE /api/page
```

---

## Webhooks

Trigger events:

```text
Page Published
Page Updated
Media Uploaded
User Created
```

---

## Headless Mode

Support:

- React
- Vue
- Next.js
- Nuxt
- Mobile Apps

---

## Plugin System

Structure:

```text
plugins/
```

Examples:

```text
Newsletter
Analytics
CRM
Events
eCommerce
```

---

# Priority 14 - Backup and Recovery

## Recovery Dashboard

Restore directly from admin.

### Features

- Browse backups
- Compare backups
- Restore in one click

---

## Scheduled Backups

Options:

```text
Daily
Weekly
Monthly
```

---

## Export Entire Website

Generate:

```text
site-backup.zip
```

Containing:

```text
Content
Settings
Images
Users
Structure
```

---

# Priority 15 - Future Enterprise Features

## Workflow Approval

```text
Editor
↓
Reviewer
↓
Publisher
↓
Live
```

---

## Scheduled Publishing

Example:

```text
Publish on:

2027-01-15
09:00
```

---

## Content Expiration

Automatically unpublish.

### Example

```text
Promotion ends:

2027-02-01
```

---

## Multi-Site Support

Manage:

```text
Site A
Site B
Site C
```

from one installation.

---

# Long-Term Goal

Position Enwires as a modern, self-hosted, multilingual, block-based CMS designed for agencies, small businesses, and developers who want the flexibility of modern page builders without the complexity, overhead, and maintenance burden of WordPress or enterprise CMS platforms.