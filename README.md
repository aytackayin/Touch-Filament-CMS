# Advanced Laravel 12 & Filament 4 CMS

Antigravity CMS is a professional, modern, and highly-scalable Content Management System built on the latest **Laravel 12** framework and **Filament 4** admin panel. It provides a robust architecture for managing blogs, media, and system settings with a focus on user experience and performance.

> **v1.0.5 Update:** YouTube to Blog Integration with Browser Extension. Automated thumbnail downloading, hashtag-to-tag extraction, and secure API system introduced.
> **v1.0.4 Update:** Extended "My Profile" system introduced with custom tabs. Universal Live Preview engine for Blogs and other resources implemented with frontend-mirroring typography.
> **v1.0.3 Update:** User-specific Table Settings introduced. Persist your column visibility, view type (grid/list), and records per page preferences across all resources.
> **v1.0.1 Update:** Public Frontend added for testing purposes.

## 🚀 Teck Stack

- **Core:** Laravel 12 & PHP 8.2+
- **Admin Panel:** Filament 4 (Premium UI/UX)
- **Frontend Auth:** Laravel Breeze (Livewire/Volt)
- **Admin Auth:** Filament Breezy (Custom profile pages & security)
- **Public Frontend:** Livewire 3 + Tailwind CSS (Glassmorphism, Dark Mode, Responsive)
- **Database:** MySQL / PostgreSQL / SQLite
- **Permissions:** Filament Shield (Advanced RBAC system)
- **Editor:** [aytackayin/tinymce](https://github.com/aytackayin/tinymce) (Specially integrated with File Manager)
- **UI Components:** [aytackayin/filament-select-icon](https://github.com/aytackayin/filament-select-icon)
- **Media:** Custom File Manager with storage synchronization

## ✨ Key Features

### 1. Advanced Blog Management
- **Hierarchical Categories:** Cascade-style category system with infinite nesting support.
- **Rich Text Editor:** Custom TinyMCE integration designed to work seamlessly with the internal File Manager for media embedding.
- **Smart Sync:** Seamless integration with the internal File Manager.
- **Grid vs List:** Dynamic view switcher for blog listings with modern grid cards.
- **Video Support:** Intelligent video thumbnail generation without requiring FFmpeg on the server (client-side processing).
- **SEO Ready:** Optimized for slugs, tags, and metadata.

### 2. Powerful File Manager
- **Hierarchical Structure:** Folder and file management with tree-view selection.
- **System Sync:** Ability to synchronize the database with physical storage automatically.
- **Media Preview:** Built-in previewer for images, videos, and documents.
- **Ownership:** Multi-user support with private/public file visibility.

### 3. Localization & Multi-language
- Unified language management via custom **Language Resource**.
- Seamless integration with **Filament Language Switch**.
- Translation-ready architecture for all resources and site components.

### 4. Dynamic Site Settings
- Powered by `spatie/laravel-settings`.
- Manage site title, description, keywords, and custom configuration directly from the panel.
- Dynamic form generation for flexible setting groups.

### 5. Server Management Console
- Dedicated **Server Commands** page for administrators.
- Execute Artisan commands (Maintenance, Cache, Optimization) with one click.
- Detailed descriptions and tooltips for each command to ensure safe operations.

### 6. Security & Roles
- **Filament Shield:** Granular role-based access control (RBAC).
- Pre-defined roles: `super_admin`, `admin`, `blog_writer`, and `panel_user`.
- Permission-based visibility for widgets, actions, and navigation items.

### 7. Global Search
- Fully customized global search experience.
- Instant search results for Blogs, Categories, and Media with high-speed indexing.

### 8. User-Specific Table Settings
- **Persistence:** Column visibility, view types (Grid/List), and pagination limits are saved per user in the database.
- **Dynamic Options:** Automatically syncs with each Resource's specific table configuration.
- **Improved UX:** Settings are applied instantly without session conflicts.
+
+### 9. Extended My Profile
+- **Tabbed Management:** Comprehensive profile settings organized into Personal Info, Social Media, Preferences, and Security tabs.
+- **Social Integration:** Add and manage multiple social media links (Instagram, Twitter, LinkedIn, etc.) directly.

### 9. Extended My Profile
- **Tabbed Management:** Comprehensive profile settings organized into Personal Info, Social Media, Preferences, and Security tabs.
- **Social Integration:** Add and manage multiple social media links (Instagram, Twitter, LinkedIn, etc.) directly.
- **User Preferences:** Choose your workspace experience, such as the preferred Default Editor (TinyMCE, Markdown, or Simple).

### 10. Universal Live Preview Engine
- **Frontend Mirroring:** Isolated Iframe-based preview that uses frontend fonts and Tailwind Typography (prose-lg) to show content exactly as it appears to visitors.
- **Dynamic & Reusable:** A single universal route handles previews for Blogs, Pages, and any other model dynamically via parameters.
- **Secure by Design:** Integrated with Laravel Policies to ensure only authorized users with 'view' permission can access previews.
- **Auto-Height:** Intelligently adjusts preview container height based on content length for a seamless admin experience.

### 11. YouTube to Blog Integration
- **Browser Extension:** Dedicated Chrome/Opera extension for one-click blog creation while browsing YouTube.
- **Metadata Extraction:** Automatically captures clean titles (bypassing notifications) and full descriptions with repaired links.
- **Smart Asset Handling:** Automatically downloads the highest quality video thumbnail as the blog's cover image.
- **Auto-Tagging:** Extracts hashtags from the video description and automatically registers them as system tags.
- **Hiyerarşik Kategori:** Access and select hierarchical categories directly within the extension's interface.
- **Draft Workflow:** Saves content as drafts with direct links to the Filament admin panel for final editing.


## 🛠 Installation

Follow these steps for a clean installation:

### 1. Clone & Install Dependencies
```bash
git clone https://github.com/your-repo/filament_cms.git
cd filament_cms
composer install
npm install
```

### 2. Environment Setup
```bash
copy .env.example .env
php artisan key:generate
```
*Don't forget to configure your database settings in the `.env` file.*

### 3. Migrations & Seeding
Antigravity CMS uses a modular seeding system. You can choose to install a clean system or one with sample data.

**Standard Installation (Empty CMS):**
```bash
php artisan migrate:fresh --seed
```
*This will create the database structure, roles, default languages, and settings.*

**Demo Data Installation (Optional):**
```bash
# Seed nested blog categories
php artisan db:seed --class=BlogCategorySeeder

# Seed sample blog posts
php artisan db:seed --class=BlogSeeder
```

### 4. Storage Link
```bash
php artisan storage:link
```

### 5. Run the Application
```bash
npm run dev
php artisan serve
```

## 🧩 Browser Extension

Antigravity CMS comes with a companion browser extension for Opera, Chrome, and Edge.

### Installation
1. Open your browser's extensions page (`opera://extensions` or `chrome://extensions`).
2. Enable **Developer Mode**.
3. Click **Load Unpacked**.
4. Select the `extensions/youtube-to-blog` folder from this project.

### Configuration
1. Go to **My Profile > Chrome Eklentisi** in your CMS panel.
2. If authorized, generate your **API Key**.
3. Open the extension, click **Settings (⚙️)**, and enter your Site URL and API Key.

## 🔐 Default Credentials (Local)
- **Super Admin:** `admin@admin.com` / `password`
- **Admin User:** `admin_user@admin.com` / `password`
- **Blog Writer:** `writer@writer.com` / `password`

## 🤝 Contribution
Contributions are welcome! Please feel free to submit a Pull Request.

---
*Built with ❤️ using Antigravity principles for high performance.*
