# eXtplorer 3

eXtplorer 3 is a modern, web-based file manager built for speed, security, and ease of deployment. It is the direct successor to the classic eXtplorer 2, completely rewritten from the ground up to meet modern web standards while retaining the powerful features that made the original a favorite for system administrators.

**Note:** Starting with version 3, eXtplorer is only a **standalone application**. It no longer functions as a Joomla! component or integration module, allowing for a cleaner codebase and broader compatibility with any PHP server environment.

## 🚀 Key Features

*   **Modern UI:** A sleek, responsive Single Page Application (SPA) powered by **Vue.js 3** and **Bootstrap 5**.
*   **Virtual File System (VFS):** Manage files on your **Local server**, or connect remotely via **FTP** or **SFTP (SSH)** using the same interface.
*   **Full File Operations:** Create, Rename, Move, Copy, Delete, and Change Permissions (Chmod/Chown) with ease.
*   **Advanced Editor:** Integrated **Ace Editor** with syntax highlighting for 100+ languages.
*   **Visual Assets:** Instant image thumbnails in grid view and a built-in lightbox with navigation.
*   **Diff Viewer:** Compare two files side-by-side to see changes.
*   **Archives:** Create and extract ZIP, TAR, and TAR.GZ archives directly in the browser.
*   **User Management:** Robust Role-Based Access Control (RBAC) with support for Groups and granular permissions.
*   **Keyboard Friendly:** Desktop-like experience with keyboard shortcuts and right-click context menus.
*   **Multilingual:** Full support for English, German, and French (i18n ready).
*   **Appearance:** Dark Mode, Light Mode, and automatic System Theme detection.

## 📋 Server Requirements

To run eXtplorer 3, your server must meet the following requirements:

*   **PHP:** Version 8.1 or higher.
*   **PHP Extensions:**
    *   `intl` (Required for CodeIgniter 4)
    *   `mbstring`, `json`, `xml`, `curl`
    *   `gd` (Required for image thumbnails)
    *   `zip` (Required for ZIP archive support)
    *   `phar` (Required for TAR/GZ support)
    *   `ftp` (Optional, for remote FTP management)
    *   `ssh2` (Optional, for SFTP management)
*   **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx.

## 📦 Installation

eXtplorer 3 is designed to be deployed as a single, self-contained bundle.

1.  **Download:** Grab the latest release ZIP from the [GitHub Releases](https://github.com/soerennb/extplorer/releases) page.
2.  **Upload:** Extract the contents to a directory on your web server.
3.  **Permissions:** Ensure the `writable/` directory has write permissions for the web server user.
4.  **Access:** Navigate to the URL in your browser.

## 🛠 Development & Building

If you are contributing to eXtplorer or building from source:

1.  Clone the repository.
2.  Install development dependencies: `./composer install`.
3.  To create a deployable archive, run: `./build.sh`.