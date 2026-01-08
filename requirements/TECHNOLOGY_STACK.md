# Technology Stack & Implementation Guide

## Core Constraint
**"No Composer / Single Bundle"**: The application must run on a standard PHP web server without requiring a dependency manager (`composer`) or a complex build process on the server side. All dependencies must be "vendorized" (included in the repository) or the code must rely on native PHP extensions.

## Backend (PHP)
To maintain a clean architecture without Composer autoloading while using a **modern** codebase (PHP 8.1+), we need a framework that officially supports manual installation.

### 1. Framework / Core
*   **Primary Choice:** **CodeIgniter 4**.
    *   **Reasoning:** CI4 is a complete rewrite of the classic framework. It is modern, strictly typed, and **officially supports "Manual Installation"** (downloading a ZIP and dropping it in).
    *   **Features:** Standard MVC architecture, fast performance, built-in Routing, Security (CSRF, XSS), and Validations.
    *   **Why it fits:** It bridges the gap between "Single Bundle" portability and "Enterprise" coding standards.

*   **Alternative:** **Flight PHP (v3)**.
    *   **Reasoning:** If you prefer a "Micro-framework" (like F3) but want something actively maintained and modern. It is extremely lightweight and extensible.

### 2. Authentication & Authorization Strategy
We will use CodeIgniter's **Filters** (Middleware) and **Events**.
*   **Session Handling:** CI4's native `Session` library.
*   **Auth Implementation:**
    *   **Filters:** Create an `AuthFilter` that intercepts requests to `/admin` or `/api` routes.
    *   **Logic:** Check for a valid session. If missing, redirect to login.
    *   **Adapters:**
        *   `LocalAuth`: Checks credentials against `writable/users.json`.
        *   `FtpAuth`: Tries to connect via FTP.
*   **Authorization (ACL):**
    *   A Service that validates if the requested `path` is within the user's `home_dir`.

### 3. Database / Storage
*   **Choice:** **File-Based Models**.
*   **Reasoning:** CI4 usually uses SQL, but we can easily create a `UserModel` that extends a custom `JsonModel` base class instead of a database connection.
*   **Location:** Data will be stored in CI4's `writable/data/` directory.

### 4. File System
*   **Choice:** **Native PHP Wrappers** (Custom `VFS` implementation).
*   **Reasoning:** We will wrap native functions in a service class `App\Services\VFS` that follows a clean interface.
    *   `LocalAdapter`: Uses `scandir`, `file_get_contents`.
    *   `FtpAdapter`: Uses `ftp_*` functions.

### 5. Archive Handling
*   **Choice:** **ZipArchive** (Native Extension).
*   **Reasoning:** Built into PHP, standard and efficient.

## Frontend (JavaScript/CSS)
To ensure a "Single Bundle" feel, we will use libraries that offer pre-built distribution files ("dist") and implement the core UI logic via **Custom Vue Components** to avoid the need for a build step (Webpack/Vite).

### 1. UI Framework
*   **Choice:** **Vue.js 3 (Global Build)**.
*   **Reasoning:** Vue.js can be included via a single `<script>` tag. It allows for building complex, reactive components without a transpilation step.
*   **Styling:** **Bootstrap 5**. It has zero dependency on jQuery, offers a grid system, modals, and dropdowns out of the box.

### 2. Core UI Components (The "File Manager" Parts)
Since most "Tree" or "Context Menu" libraries require a Node.js build process, we will build these as lightweight **Custom Vue Components**:

*   **File Tree:** A **Recursive Vue Component**.
    *   *Why:* Allows us to easily handle "Lazy Loading" of subdirectories via API calls when a folder is expanded.
*   **File List:** A **Dynamic Grid/List Component**.
    *   *Why:* We can toggle between "Details View" (Table) and "Icon View" (Grid) using simple Bootstrap classes and a Vue `v-if`.
*   **Context Menu:** A **Custom Floating Component**.
    *   *Why:* We need to intercept the `@contextmenu` event on file items to show actions (Rename, Delete) at the cursor position.

### 3. Editor & Utilities
*   **Code Editor:** **Ace Editor**.
    *   *Reasoning:* It is designed to be dropped into a folder. It supports syntax highlighting for 100+ languages and works instantly.
*   **Icons:** **RemixIcon** (Webfont).
    *   *Reasoning:* A huge set of open-source system icons that work with a single CSS file.
*   **Dialogs:** **SweetAlert2**.
    *   *Reasoning:* Replaces browser `alert()` and `confirm()` with beautiful, responsive modals for actions like "Are you sure you want to delete this?".

## Directory Structure Strategy
The project will follow a strict structure to support the "Vendorized" approach:

```
/
├── assets/             # Publicly accessible assets
│   ├── css/            # Bootstrap.css, Custom.css
│   ├── js/             # Vue.global.js, Bootstrap.js, App.js
│   └── vendor/         # Ace Editor folder
├── src/
│   ├── Core/           # Router, Autoloader, View
│   ├── Controllers/    # API and View Controllers
│   ├── Models/         # User, Settings
│   └── Services/       # VFS Adapters (Local, FTP)
├── templates/          # PHP View templates
├── data/               # JSON storage for users/config
├── index.php           # Entry point
└── config.php          # Base configuration
```

## Build Process (Optional)
While the app runs without a build, a simple `make` command or shell script can be used to concat `App.js` components into a single file if the frontend code grows, but we will aim for standard ES modules or global components for simplicity.
