# MCP (Model Context Protocol) Documentation

## Overview

FA WPMCP exposes WordPress functionality to AI agents through the **Model Context Protocol (MCP)**. This allows AI assistants to interact with your WordPress site programmatically by calling registered abilities.

## What is MCP?

Model Context Protocol is a standardized way for AI models to interact with external systems. It defines how AI agents can:
- Discover available **tools** (write operations) and **resources** (read operations)
- Call these tools with structured inputs
- Receive structured outputs
- Authenticate and maintain security

## Server Information

| Property | Value |
|----------|-------|
| **Server ID** | `fa-wpmcp` |
| **Namespace** | `fa-wpmcp` |
| **Transport** | HTTP |
| **Authentication** | WordPress Application Passwords |
| **Base URL** | `https://your-site.com/wp-json/abilities/v1/` |

## Authentication

MCP access requires WordPress Application Passwords:

1. Navigate to **Users → Your Profile** in WordPress admin
2. Scroll to **Application Passwords** section
3. Generate a new application password
4. Use HTTP Basic Auth with your username and application password

**Example:**
```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  https://your-site.com/wp-json/abilities/v1/execute/fa-wpmcp/list-posts
```

## Available Abilities

FA WPMCP provides **21 WordPress abilities** across 5 categories:

| Category | Abilities | Operations |
|----------|-----------|------------|
| **Posts & Pages** | List Posts, Get Post, Create Post, Update Post | CRUD for posts, pages, and custom post types (including WooCommerce products) |
| **Comments** | List Comments, Get Comment, Create Comment, Update Comment, Delete Comment | Complete comment management |
| **Media** | List Media, Get Media, Update Media, Upload Media | Media library management with base64/URL uploads |
| **Taxonomies** | List Terms, Get Term, Create Term, Update Term | Manage categories, tags, and custom taxonomies |
| **Settings** | Get Option, Update Option, Delete Option, List Options | WordPress options management with search and pagination |

### 1. List Posts

Retrieve a paginated list of WordPress posts with filtering options.

**Ability Name:** `fa-wpmcp/list-posts`
**Category:** `posts-pages`
**Operation Type:** `READ`
**Required Capability:** `read`

#### Input Schema

```json
{
  "page": 1,
  "per_page": 10,
  "status": "publish",
  "author": 5,
  "category": 3,
  "search": "keyword",
  "orderby": "date",
  "order": "DESC"
}
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number for pagination |
| `per_page` | integer | No | 10 | Posts per page (max 100) |
| `status` | string | No | `publish` | Filter by status: `publish`, `draft`, `pending`, `private`, `future`, `trash`, `any` |
| `author` | integer | No | - | Filter by author user ID |
| `category` | integer | No | - | Filter by category ID |
| `search` | string | No | - | Search term to filter posts |
| `orderby` | string | No | `date` | Order by: `date`, `title`, `modified`, `ID`, `author`, `name` |
| `order` | string | No | `DESC` | Sort order: `ASC` or `DESC` |

#### Output Schema

```json
{
  "posts": [
    {
      "id": 123,
      "title": "Post Title",
      "excerpt": "Post excerpt...",
      "status": "publish",
      "type": "post",
      "slug": "post-slug",
      "permalink": "https://example.com/post-slug",
      "date": "2026-01-21 10:00:00",
      "modified": "2026-01-21 10:30:00",
      "author": {
        "id": 1,
        "name": "Author Name"
      }
    }
  ],
  "total": 42,
  "pages": 5,
  "current_page": 1,
  "per_page": 10
}
```

---

### 2. Get Post

Retrieve a single WordPress post by ID with full details.

**Ability Name:** `fa-wpmcp/get-post`
**Category:** `posts-pages`
**Operation Type:** `READ`
**Required Capability:** `read`

#### Input Schema

```json
{
  "post_id": 123
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | **Yes** | The ID of the post to retrieve (minimum: 1) |

#### Output Schema

```json
{
  "post": {
    "id": 123,
    "title": "Post Title",
    "content": "Full HTML content...",
    "excerpt": "Post excerpt...",
    "status": "publish",
    "type": "post",
    "slug": "post-slug",
    "permalink": "https://example.com/post-slug",
    "edit_url": "https://example.com/wp-admin/post.php?post=123&action=edit",
    "date": "2026-01-21 10:00:00",
    "modified": "2026-01-21 10:30:00",
    "featured_image": "https://example.com/uploads/image.jpg",
    "author": {
      "id": 1,
      "name": "Author Name"
    },
    "categories": [
      {
        "id": 3,
        "name": "Technology",
        "slug": "technology"
      }
    ],
    "tags": [
      {
        "id": 5,
        "name": "WordPress",
        "slug": "wordpress"
      }
    ],
    "meta": {
      "custom_field": "value"
    }
  }
}
```

**Note:** The `meta` object only includes custom fields (excludes internal WordPress meta keys starting with `_`).

---

### 3. Create Post

Create a new WordPress post with content and metadata.

**Ability Name:** `fa-wpmcp/create-post`
**Category:** `posts-pages`
**Operation Type:** `WRITE`
**Required Capability:** `publish_posts`

#### Input Schema

```json
{
  "title": "New Post Title",
  "content": "<p>Post content with HTML...</p>",
  "excerpt": "Short excerpt",
  "status": "draft",
  "author": 1,
  "categories": [3, 5],
  "tags": ["WordPress", "Tutorial"]
}
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `title` | string | **Yes** | - | The post title |
| `content` | string | No | - | Post content (HTML allowed, sanitized with `wp_kses_post`) |
| `excerpt` | string | No | - | Post excerpt |
| `status` | string | No | `draft` | Post status: `publish`, `draft`, `pending`, `private`, `future` |
| `author` | integer | No | Current user | Author user ID |
| `categories` | array[integer] | No | - | Array of category IDs |
| `tags` | array[string] | No | - | Array of tag names or IDs |

**Safety Note:** Posts default to `draft` status for safety. Explicitly set `status: "publish"` to publish immediately.

#### Output Schema

```json
{
  "post_id": 456,
  "permalink": "https://example.com/new-post",
  "status": "draft",
  "edit_url": "https://example.com/wp-admin/post.php?post=456&action=edit"
}
```

---

### 4. Update Post

Update an existing WordPress post. Only provided fields are modified.

**Ability Name:** `fa-wpmcp/update-post`
**Category:** `posts-pages`
**Operation Type:** `WRITE`
**Required Capability:** `edit_posts`

#### Input Schema

```json
{
  "post_id": 456,
  "title": "Updated Title",
  "content": "<p>Updated content...</p>",
  "excerpt": "Updated excerpt",
  "status": "publish",
  "categories": [3, 7],
  "tags": ["WordPress", "Updated"]
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | **Yes** | The ID of the post to update (minimum: 1) |
| `title` | string | No | Updated post title |
| `content` | string | No | Updated content (HTML allowed, sanitized) |
| `excerpt` | string | No | Updated excerpt |
| `status` | string | No | New status: `publish`, `draft`, `pending`, `private`, `future`, `trash` |
| `categories` | array[integer] | No | Array of category IDs (replaces existing) |
| `tags` | array[string] | No | Array of tag names or IDs (replaces existing) |

**Partial Updates:** Only fields you provide will be updated. Omitted fields remain unchanged.

#### Output Schema

```json
{
  "post_id": 456,
  "permalink": "https://example.com/updated-post",
  "status": "publish",
  "edit_url": "https://example.com/wp-admin/post.php?post=456&action=edit",
  "updated": true
}
```

---

### 5. List Comments

Retrieve a paginated list of WordPress comments with filtering options.

**Ability Name:** `fa-wpmcp/list-comments`
**Category:** `comments`
**Operation Type:** `READ`
**Required Capability:** `read`

#### Input Schema

```json
{
  "page": 1,
  "per_page": 10,
  "post_id": 123,
  "status": "approve"
}
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number for pagination |
| `per_page` | integer | No | 10 | Comments per page (max 100) |
| `post_id` | integer | No | - | Filter by post ID |
| `status` | string | No | - | Filter by status: `approve`, `hold`, `spam`, `trash`, `all` |

#### Output Schema

```json
{
  "comments": [
    {
      "id": 42,
      "post_id": 123,
      "author": "John Doe",
      "email": "john@example.com",
      "content": "Great article!",
      "date": "2026-01-21 10:00:00",
      "status": "approve",
      "link": "https://example.com/post#comment-42"
    }
  ],
  "total": 150,
  "page": 1,
  "per_page": 10
}
```

---

### 6. Get Comment

Retrieve a single WordPress comment by ID with full details.

**Ability Name:** `fa-wpmcp/get-comment`
**Category:** `comments`
**Operation Type:** `READ`
**Required Capability:** `read`

#### Input Schema

```json
{
  "comment_id": 42
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `comment_id` | integer | **Yes** | The ID of the comment to retrieve (minimum: 1) |

#### Output Schema

```json
{
  "comment": {
    "id": 42,
    "post_id": 123,
    "author": "John Doe",
    "email": "john@example.com",
    "content": "Great article!",
    "date": "2026-01-21 10:00:00",
    "status": "approve",
    "link": "https://example.com/post#comment-42"
  }
}
```

---

### 7. Create Comment

Create a new WordPress comment on a post.

**Ability Name:** `fa-wpmcp/create-comment`
**Category:** `comments`
**Operation Type:** `WRITE`
**Required Capability:** `edit_posts`

#### Input Schema

```json
{
  "post_id": 123,
  "content": "This is a great post!",
  "author": "John Doe",
  "author_email": "john@example.com",
  "author_url": "https://johndoe.com",
  "status": "approve"
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | **Yes** | The ID of the post to comment on |
| `content` | string | **Yes** | Comment content |
| `author` | string | No | Comment author name |
| `author_email` | string | No | Comment author email |
| `author_url` | string | No | Comment author URL |
| `status` | string | No | Status: `approve`, `hold` (defaults to WordPress settings) |

#### Output Schema

```json
{
  "comment_id": 42,
  "comment": {
    "id": 42,
    "post_id": 123,
    "author": "John Doe",
    "email": "john@example.com",
    "content": "This is a great post!",
    "date": "2026-01-21 10:00:00",
    "status": "approve",
    "link": "https://example.com/post#comment-42"
  }
}
```

---

### 8. Update Comment

Update an existing WordPress comment.

**Ability Name:** `fa-wpmcp/update-comment`
**Category:** `comments`
**Operation Type:** `WRITE`
**Required Capability:** `moderate_comments`

#### Input Schema

```json
{
  "comment_id": 42,
  "content": "Updated comment text",
  "status": "approve"
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `comment_id` | integer | **Yes** | The ID of the comment to update |
| `content` | string | No | Updated comment content |
| `status` | string | No | New status: `approve`, `hold`, `spam`, `trash` |

#### Output Schema

```json
{
  "comment_id": 42,
  "comment": {
    "id": 42,
    "post_id": 123,
    "author": "John Doe",
    "email": "john@example.com",
    "content": "Updated comment text",
    "date": "2026-01-21 10:00:00",
    "status": "approve",
    "link": "https://example.com/post#comment-42"
  }
}
```

---

### 9. Delete Comment

Permanently delete a WordPress comment.

**Ability Name:** `fa-wpmcp/delete-comment`
**Category:** `comments`
**Operation Type:** `DELETE`
**Required Capability:** `moderate_comments`

#### Input Schema

```json
{
  "comment_id": 42
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `comment_id` | integer | **Yes** | The ID of the comment to delete (minimum: 1) |

#### Output Schema

```json
{
  "comment_id": 42,
  "deleted": true
}
```

---

### 10. List Media

Retrieve a paginated list of WordPress media library items with filtering.

**Ability Name:** `fa-wpmcp/list-media`
**Category:** `media`
**Operation Type:** `READ`
**Required Capability:** `upload_files`

#### Input Schema

```json
{
  "page": 1,
  "per_page": 10,
  "mime_type": "image/*",
  "search": "logo",
  "orderby": "date",
  "order": "DESC"
}
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Page number for pagination |
| `per_page` | integer | No | 10 | Media items per page (max 100) |
| `mime_type` | string | No | - | Filter by MIME type (e.g., `image/jpeg`, `image/*`, `video/mp4`) |
| `search` | string | No | - | Search term for filename or title |
| `orderby` | string | No | `date` | Order by: `date`, `title`, `modified`, `ID` |
| `order` | string | No | `DESC` | Sort order: `ASC` or `DESC` |

#### Output Schema

```json
{
  "media": [
    {
      "id": 789,
      "title": "Company Logo",
      "filename": "logo.png",
      "url": "https://example.com/uploads/2026/01/logo.png",
      "mime_type": "image/png",
      "file_size": 45678,
      "width": 800,
      "height": 600,
      "date": "2026-01-21 10:00:00",
      "author": {
        "id": 1,
        "name": "Admin User"
      }
    }
  ],
  "total": 250,
  "pages": 25,
  "current_page": 1,
  "per_page": 10
}
```

---

### 11. Get Media

Retrieve a single WordPress media item by ID with full metadata.

**Ability Name:** `fa-wpmcp/get-media`
**Category:** `media`
**Operation Type:** `READ`
**Required Capability:** `upload_files`

#### Input Schema

```json
{
  "media_id": 789
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `media_id` | integer | **Yes** | The ID of the media item to retrieve (minimum: 1) |

#### Output Schema

```json
{
  "media": {
    "id": 789,
    "title": "Company Logo",
    "caption": "Our official company logo",
    "description": "High-resolution logo for marketing materials",
    "alt_text": "Company Logo",
    "filename": "logo.png",
    "url": "https://example.com/uploads/2026/01/logo.png",
    "mime_type": "image/png",
    "file_size": 45678,
    "width": 800,
    "height": 600,
    "sizes": {
      "thumbnail": {
        "url": "https://example.com/uploads/2026/01/logo-150x150.png",
        "width": 150,
        "height": 150
      },
      "medium": {
        "url": "https://example.com/uploads/2026/01/logo-300x225.png",
        "width": 300,
        "height": 225
      }
    },
    "date": "2026-01-21 10:00:00",
    "author": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

---

### 12. Update Media

Update WordPress media metadata (title, caption, description, alt text).

**Ability Name:** `fa-wpmcp/update-media`
**Category:** `media`
**Operation Type:** `WRITE`
**Required Capability:** `upload_files`

#### Input Schema

```json
{
  "media_id": 789,
  "title": "Updated Company Logo",
  "caption": "Our new official logo",
  "description": "Updated high-resolution logo",
  "alt_text": "Updated Company Logo"
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `media_id` | integer | **Yes** | The ID of the media item to update |
| `title` | string | No | Updated title |
| `caption` | string | No | Updated caption |
| `description` | string | No | Updated description |
| `alt_text` | string | No | Updated alt text (for images) |

#### Output Schema

```json
{
  "media_id": 789,
  "updated": true
}
```

---

### 13. Upload Media

Upload a new media file to the WordPress media library.

**Ability Name:** `fa-wpmcp/upload-media`
**Category:** `media`
**Operation Type:** `WRITE`
**Required Capability:** `upload_files`

#### Input Schema

```json
{
  "filename": "logo.png",
  "data": "iVBORw0KGgoAAAANSUhEUgAA...",
  "title": "Company Logo",
  "caption": "Our official logo",
  "alt_text": "Company Logo"
}
```

**OR**

```json
{
  "url": "https://example.com/remote-image.jpg",
  "title": "Imported Image"
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `filename` | string | **Yes** (with `data`) | Filename for the uploaded file |
| `data` | string | **Yes** (with `filename`) | Base64-encoded file data |
| `url` | string | **Yes** (alternative) | URL to import file from |
| `title` | string | No | Media title |
| `caption` | string | No | Media caption |
| `description` | string | No | Media description |
| `alt_text` | string | No | Alt text (for images) |

**Note:** Maximum file size is 10MB (configurable via `FA_WPMCP_MAX_UPLOAD_SIZE` constant).

#### Output Schema

```json
{
  "media_id": 789,
  "url": "https://example.com/uploads/2026/01/logo.png",
  "media": {
    "id": 789,
    "title": "Company Logo",
    "filename": "logo.png",
    "url": "https://example.com/uploads/2026/01/logo.png",
    "mime_type": "image/png",
    "file_size": 45678,
    "width": 800,
    "height": 600
  }
}
```

---

### 14. List Terms

Retrieve terms from WordPress taxonomies (categories, tags, or custom taxonomies).

**Ability Name:** `fa-wpmcp/list-terms`
**Category:** `taxonomies`
**Operation Type:** `READ`
**Required Capability:** `read`

#### Input Schema

```json
{
  "taxonomy": "category",
  "page": 1,
  "per_page": 10,
  "hide_empty": false,
  "parent": 0,
  "search": "tech",
  "orderby": "name",
  "order": "ASC"
}
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `taxonomy` | string | No | `category` | Taxonomy name (`category`, `post_tag`, or custom taxonomy slug) |
| `page` | integer | No | 1 | Page number for pagination |
| `per_page` | integer | No | 10 | Terms per page (max 100) |
| `hide_empty` | boolean | No | `false` | Hide terms with no posts assigned |
| `parent` | integer | No | - | Filter by parent term ID (0 for root terms) |
| `search` | string | No | - | Search terms by name |
| `orderby` | string | No | `name` | Order by: `name`, `count`, `term_id`, `slug` |
| `order` | string | No | `ASC` | Sort order: `ASC` or `DESC` |

#### Output Schema

```json
{
  "terms": [
    {
      "term_id": 5,
      "name": "Technology",
      "slug": "technology",
      "description": "Posts about technology",
      "count": 42,
      "parent": 0,
      "taxonomy": "category"
    }
  ],
  "total": 25,
  "pages": 3,
  "current_page": 1,
  "per_page": 10
}
```

---

### 15. Get Term

Retrieve a single taxonomy term by ID with full details and metadata.

**Ability Name:** `fa-wpmcp/get-term`
**Category:** `taxonomies`
**Operation Type:** `READ`
**Required Capability:** `read`

#### Input Schema

```json
{
  "term_id": 5,
  "taxonomy": "category"
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `term_id` | integer | **Yes** | The ID of the term to retrieve (minimum: 1) |
| `taxonomy` | string | **Yes** | Taxonomy name (`category`, `post_tag`, or custom taxonomy slug) |

#### Output Schema

```json
{
  "term": {
    "term_id": 5,
    "name": "Technology",
    "slug": "technology",
    "description": "Posts about technology and innovation",
    "count": 42,
    "parent": 0,
    "taxonomy": "category",
    "meta": {
      "custom_field": "value"
    }
  }
}
```

---

### 16. Create Term

Create a new taxonomy term (category, tag, or custom taxonomy term).

**Ability Name:** `fa-wpmcp/create-term`
**Category:** `taxonomies`
**Operation Type:** `WRITE`
**Required Capability:** `manage_categories`

#### Input Schema

```json
{
  "taxonomy": "category",
  "name": "New Category",
  "slug": "new-category",
  "description": "Description of the new category",
  "parent": 5
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `taxonomy` | string | **Yes** | Taxonomy name (`category`, `post_tag`, or custom taxonomy slug) |
| `name` | string | **Yes** | Term name |
| `slug` | string | No | Term slug (auto-generated from name if not provided) |
| `description` | string | No | Term description |
| `parent` | integer | No | Parent term ID (for hierarchical taxonomies, 0 for root) |

#### Output Schema

```json
{
  "term_id": 25,
  "term": {
    "term_id": 25,
    "name": "New Category",
    "slug": "new-category",
    "description": "Description of the new category",
    "count": 0,
    "parent": 5,
    "taxonomy": "category",
    "meta": {}
  }
}
```

---

### 17. Update Term

Update an existing taxonomy term's name, slug, description, or parent.

**Ability Name:** `fa-wpmcp/update-term`
**Category:** `taxonomies`
**Operation Type:** `WRITE`
**Required Capability:** `manage_categories`

#### Input Schema

```json
{
  "term_id": 25,
  "taxonomy": "category",
  "name": "Updated Category Name",
  "slug": "updated-category",
  "description": "Updated description",
  "parent": 10
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `term_id` | integer | **Yes** | The ID of the term to update |
| `taxonomy` | string | **Yes** | Taxonomy name (`category`, `post_tag`, or custom taxonomy slug) |
| `name` | string | No | Updated term name |
| `slug` | string | No | Updated term slug |
| `description` | string | No | Updated term description |
| `parent` | integer | No | Updated parent term ID (for hierarchical taxonomies) |

#### Output Schema

```json
{
  "term_id": 25,
  "term": {
    "term_id": 25,
    "name": "Updated Category Name",
    "slug": "updated-category",
    "description": "Updated description",
    "count": 12,
    "parent": 10,
    "taxonomy": "category",
    "meta": {}
  }
}
```

---

### 18. Get Option

Retrieve a WordPress option by name with existence detection.

**Ability Name:** `fa-wpmcp/get-option`
**Category:** `settings`
**Operation Type:** `READ`
**Required Capability:** `manage_options`

#### Input Schema

```json
{
  "option_name": "blogname",
  "default": "My Blog"
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `option_name` | string | **Yes** | The name of the option to retrieve |
| `default` | mixed | No | Default value if option does not exist |

#### Output Schema

```json
{
  "option_name": "blogname",
  "value": "My WordPress Site",
  "exists": true
}
```

| Field | Type | Description |
|-------|------|-------------|
| `option_name` | string | The name of the option |
| `value` | mixed | The option value or default value |
| `exists` | boolean | Whether the option exists in the database |

---

### 19. Update Option

Update or create a WordPress option with optional autoload setting.

**Ability Name:** `fa-wpmcp/update-option`
**Category:** `settings`
**Operation Type:** `WRITE`
**Required Capability:** `manage_options`

#### Input Schema

```json
{
  "option_name": "blogdescription",
  "value": "Just another WordPress site",
  "autoload": "yes"
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `option_name` | string | **Yes** | The name of the option to update |
| `value` | mixed | **Yes** | The value to set for the option |
| `autoload` | string | No | Whether to autoload the option: `yes` or `no` |

#### Output Schema

```json
{
  "option_name": "blogdescription",
  "updated": true
}
```

---

### 20. Delete Option

Permanently delete a WordPress option from the database.

**Ability Name:** `fa-wpmcp/delete-option`
**Category:** `settings`
**Operation Type:** `WRITE`
**Required Capability:** `manage_options`

#### Input Schema

```json
{
  "option_name": "custom_setting"
}
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `option_name` | string | **Yes** | The name of the option to delete |

#### Output Schema

```json
{
  "option_name": "custom_setting",
  "deleted": true
}
```

---

### 21. List Options

List WordPress options with search filtering and pagination.

**Ability Name:** `fa-wpmcp/list-options`
**Category:** `settings`
**Operation Type:** `READ`
**Required Capability:** `manage_options`

#### Input Schema

```json
{
  "search": "blog",
  "limit": 100,
  "offset": 0
}
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search term to filter option names |
| `limit` | integer | No | 100 | Maximum number of options to return (max 1000) |
| `offset` | integer | No | 0 | Number of options to skip for pagination |

#### Output Schema

```json
{
  "options": [
    {
      "option_name": "blogname",
      "option_value": "My WordPress Site"
    },
    {
      "option_name": "blogdescription",
      "option_value": "Just another WordPress site"
    }
  ],
  "total": 150
}
```

| Field | Type | Description |
|-------|------|-------------|
| `options` | array | Array of option objects with name and value |
| `total` | integer | Total number of options matching the search |

---

## Security & Monitoring

### Permission System

Abilities are controlled by a **three-level permission system**:

1. **Global Permissions** - Enable/disable all read or write operations
2. **Category Permissions** - Enable/disable read/write per category (e.g., `posts-pages`)
3. **Ability Permissions** - Enable/disable individual abilities

Access the permission settings at **WordPress Admin → Settings → FA WPMCP**.

### Rate Limiting

Protection against abuse via configurable rate limits:

- **Per-User Limits:** Based on WordPress user ID
- **Per-IP Limits:** For anonymous/unauthenticated requests
- **Default Limits:** 60 requests/minute, 1000 requests/hour

When rate limits are exceeded, the API returns HTTP **429 Too Many Requests**.

### Activity Logging

All ability executions are logged to the database:

- **User ID** and **IP address**
- **Ability name** and **operation type**
- **Input/output data** (with PII redaction)
- **Execution time** and **correlation ID**
- **Success/failure status**

View activity logs in **WordPress Admin → FA WPMCP → Activity Log**.

### Privacy Redaction

Sensitive data is automatically redacted from logs and webhooks:

**Redacted Fields:**
- `password`
- `token`
- `api_key`
- `secret`
- `user_pass`
- `apikey`
- `access_token`
- `refresh_token`

Field matching is case-insensitive and recursive through nested structures.

### Webhook Notifications

Receive real-time notifications for ability executions:

**Event Types:**
- `ability.before` - Before ability execution
- `ability.after` - After successful execution
- `ability.failed` - After execution failure

**Webhook Payload:**
```json
{
  "event": "ability.after",
  "ability": "fa-wpmcp/create-post",
  "user_id": 1,
  "ip_address": "192.168.1.1",
  "input": { "title": "..." },
  "output": { "post_id": 456 },
  "timestamp": "2026-01-21T10:30:00Z",
  "success": true,
  "correlation_id": "abc123"
}
```

**Security:** Webhooks include HMAC-SHA256 signatures in the `X-Webhook-Signature` header.

Configure webhooks at **WordPress Admin → Settings → FA WPMCP → Webhooks**.

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | When Used |
|------|---------|-----------|
| `200` | Success | Ability executed successfully |
| `400` | Bad Request | Invalid input (schema validation failed) |
| `401` | Unauthorized | Missing or invalid authentication |
| `403` | Forbidden | Permission denied (user lacks capability or ability disabled) |
| `404` | Not Found | Resource not found (e.g., post doesn't exist) |
| `405` | Method Not Allowed | Invalid HTTP method |
| `409` | Conflict | Resource conflict (e.g., duplicate slug) |
| `429` | Too Many Requests | Rate limit exceeded |
| `500` | Internal Server Error | Server-side error |

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "PERMISSION_DENIED",
    "message": "You do not have permission to create posts",
    "http_status": 403
  },
  "timestamp": "2026-01-21T10:30:00Z"
}
```

**Standard Error Codes:**
- `VALIDATION_ERROR` - Input validation failed
- `PERMISSION_DENIED` - Insufficient permissions
- `NOT_FOUND` - Resource not found
- `RATE_LIMIT_EXCEEDED` - Too many requests
- `INTERNAL_ERROR` - Server error
- `METHOD_NOT_ALLOWED` - Invalid HTTP method
- `CONFLICT` - Resource conflict

---

## Usage Examples

### Example 1: List Recent Published Posts

**Request:**
```bash
curl -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{"page": 1, "per_page": 5, "status": "publish", "orderby": "date", "order": "DESC"}' \
  https://your-site.com/wp-json/abilities/v1/execute/fa-wpmcp/list-posts
```

**Response:**
```json
{
  "success": true,
  "data": {
    "posts": [
      {
        "id": 123,
        "title": "Latest Post",
        "excerpt": "...",
        "status": "publish",
        "permalink": "https://your-site.com/latest-post",
        "date": "2026-01-21 10:00:00",
        "author": {"id": 1, "name": "Admin"}
      }
    ],
    "total": 42,
    "pages": 9,
    "current_page": 1,
    "per_page": 5
  },
  "timestamp": "2026-01-21T10:30:00Z"
}
```

### Example 2: Get a Specific Post

**Request:**
```bash
curl -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{"post_id": 123}' \
  https://your-site.com/wp-json/abilities/v1/execute/fa-wpmcp/get-post
```

### Example 3: Create a Draft Post

**Request:**
```bash
curl -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My New Post",
    "content": "<p>This is the post content.</p>",
    "status": "draft",
    "categories": [3],
    "tags": ["WordPress", "MCP"]
  }' \
  https://your-site.com/wp-json/abilities/v1/execute/fa-wpmcp/create-post
```

**Response:**
```json
{
  "success": true,
  "data": {
    "post_id": 456,
    "permalink": "https://your-site.com/my-new-post",
    "status": "draft",
    "edit_url": "https://your-site.com/wp-admin/post.php?post=456&action=edit"
  },
  "timestamp": "2026-01-21T10:35:00Z"
}
```

### Example 4: Update and Publish Post

**Request:**
```bash
curl -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "post_id": 456,
    "title": "Updated Post Title",
    "status": "publish"
  }' \
  https://your-site.com/wp-json/abilities/v1/execute/fa-wpmcp/update-post
```

---

## Configuration

### Admin Settings

Access settings at **WordPress Admin → Settings → FA WPMCP**.

#### Global Permissions
- **Enable All Read:** Master switch for all read operations
- **Enable All Write:** Master switch for all write operations

#### Category Permissions
- **Posts & Pages Read:** Enable/disable all post/page read abilities
- **Posts & Pages Write:** Enable/disable all post/page write abilities

#### Individual Ability Settings
- Toggle each ability on/off independently
- View required WordPress capability for each ability

#### Rate Limiting
- Configure per-minute and per-hour limits
- Set different limits per ability or category
- Enable/disable rate limiting globally

#### Webhooks
- Add/remove webhook URLs
- Configure which events trigger webhooks
- View webhook delivery logs

#### Activity Log
- View all logged ability executions
- Filter by user, ability, date range
- Export logs as CSV
- Configure log retention (auto-delete after N days)

---

## Extending the Plugin

### Adding New Abilities

Create a new ability class extending `AbstractAbility`:

```php
<?php
namespace FAWpmcp\Abilities\CustomCategory;

use FAWpmcp\Abilities\AbstractAbility;

class MyCustomAbility extends AbstractAbility {
    public function get_name(): string {
        return 'fa-wpmcp/my-custom-ability';
    }

    public function get_category(): string {
        return 'custom-category';
    }

    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'param' => ['type' => 'string']
            ],
            'required' => ['param']
        ];
    }

    public function get_output_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'result' => ['type' => 'string']
            ]
        ];
    }

    public function do_execute(array $input): array {
        return ['result' => 'success'];
    }
}
```

Place the file in `src/Abilities/CustomCategory/MyCustomAbility.php` and the plugin will automatically discover and register it.

---

## Troubleshooting

### Common Issues

**Issue:** `401 Unauthorized` responses
**Solution:** Verify Application Password is correctly generated and used in Basic Auth header.

**Issue:** `403 Forbidden` for read abilities
**Solution:** Check that global read permissions and category read permissions are enabled in settings.

**Issue:** `429 Too Many Requests`
**Solution:** Reduce request frequency or increase rate limits in admin settings.

**Issue:** Abilities not appearing in MCP
**Solution:** Ensure abilities are properly registered. Check PHP error logs for registration failures.

---

## Support & Resources

- **Plugin Repository:** [github.com/featherart/fa-wpmcp](https://github.com/featherart/fa-wpmcp)
- **WordPress Abilities API:** [WordPress 6.9+ Core Documentation](https://developer.wordpress.org/reference/functions/wp_register_ability/)
- **Model Context Protocol:** [modelcontextprotocol.io](https://modelcontextprotocol.io)

---

**Last Updated:** 2026-01-21
**Plugin Version:** 1.0.0
