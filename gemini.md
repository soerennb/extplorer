# eXtplorer 3 - Gemini Context

## 1. Project Overview
eXtplorer 3 is a modern, web-based file manager application. It is the successor to eXtplorer 2, rebuilt as a standalone Single Page Application (SPA).
*   **Type:** Web Application (PHP Backend + Vue.js Frontend).
*   **Goal:** Speed, security, and ease of deployment (Single Bundle / No Composer required for end-users).
*   **Key Features:** Virtual File System (Local/FTP/SSH), WebDAV Access, Rich Editor (Ace), RBAC, Archives, Light/Dark modes.

## 2. Technology Stack

### Backend
*   **Language:** PHP 8.1+
*   **Framework:** CodeIgniter 4 (CI4).
*   **Dependencies:** Managed via Composer for dev, but "vendorized" (committed or bundled) for releases so end-users don't need Composer.
*   **Key Libraries:** `sabre/dav` (WebDAV), `codeigniter4/framework`.

### Frontend
*   **Framework:** Vue.js 3 (Global Build / No Build Step).
*   **UI Library:** Bootstrap 5.
*   **Icons:** RemixIcon.
*   **Editor:** Ace Editor.
*   **Approach:** Frontend assets are standard JS/CSS files in `public/assets`. No Webpack/Vite build process is required for the frontend; Vue components are written in a way that allows direct browser execution or simple concatenation.

## 3. Architecture

### Backend (MVC + Services)
*   **Controllers:** Handle HTTP requests, auth, and routing (`app/Controllers`).
*   **Models:** File-based models (JSON) for Users/Settings (`app/Models`), avoiding SQL databases for portability.
*   **Services:**
    *   **VFS (Virtual File System):** A crucial abstraction layer (`app/Services/VFS/IFileSystem.php`).
    *   **Adapters:** `LocalAdapter`, `FtpAdapter`, `Ssh2Adapter`.
    *   **Rule:** **NEVER** use native PHP file functions (`fopen`, `scandir`) directly in Controllers to manage user files. Always use the VFS service.

### Security Mandate
*   **Home Directory Jail:** Users must **strictly** be confined to their assigned home directory. All file paths must be validated to ensure they do not traverse outside this root.

### Frontend (SPA)
*   Communicates with Backend via JSON API.
*   State management handled via Vue 3 Reactivity.
*   Components include File Tree (recursive), File List (Grid/List), and Editor.

## 4. Key Directory Structure

*   `app/` - Core application logic (CI4 structure).
    *   `Config/` - App configuration.
    *   `Controllers/` - API endpoints and view rendering.
    *   `Services/` - Business logic, specifically `VFS/` and `Dav/`.
    *   `Views/` - Initial HTML templates (mostly empty shells for Vue).
*   `public/` - Web root.
    *   `assets/` - JS, CSS, Fonts, Vendor libs (Vue, Bootstrap).
    *   `index.php` - Entry point.
*   `writable/` - Storage for logs, sessions, cache, and **User Data** (JSON models).
*   `tests/` - PHPUnit tests.
*   `requirements/` - Detailed architectural documentation.

## 5. Development Guidelines

*   **Dependency Management:** While `composer.json` exists, the application aims to be self-contained. When adding dependencies, consider the impact on the "unzip and run" deployment model.
*   **Code Style:** Follow PSR-12 and CodeIgniter 4 conventions.
*   **Testing:** Run tests via `vendor/bin/phpunit` or `composer test`.
*   **Building:** The `build.sh` script creates the release archive.
*   **Safety:** The `writable/file_manager_root` directory is often used as a sandbox for testing file operations.

## 6. Common Commands

*   **Test:** `composer test`
*   **Build:** `./build.sh`
*   **Serve (Dev):** `php spark serve` (CodeIgniter built-in server)

## 7. Internationalization (i18n)

*   **Requirement:** All user-facing text **MUST** be localizable. Never hardcode strings in the UI.
*   **Frontend:**
    *   Strings are stored in `public/assets/i18n/` (e.g., `en.json`, `de.json`).
    *   Format: Flat JSON `{ "key": "Value" }`.
    *   **Workflow:** When adding a new UI element, add the corresponding key-value pair to `en.json` immediately.
*   **Backend:**
    *   Strings (validation errors, system messages) are in `app/Language/{locale}/`.
    *   Follow CodeIgniter 4 localization patterns.

## 8. Issue Tracking

This project uses **bd (beads)** for issue tracking.
Run `bd prime` for workflow context, or install hooks (`bd hooks install`) for auto-injection.

**Quick reference:**
- `bd ready` - Find unblocked work
- `bd create "Title" --type task --priority 2` - Create issue
- `bd close <id>` - Complete work
- `bd sync` - Sync with git (run at session end)

For full workflow details: `bd prime`

## 9. Testing & Verification (AI Agent Guidelines)

When implementing new features or fixing bugs, the AI agent should use the following commands to ensure quality and correctness.

### 9.1 Backend Testing
*   **Linting (Syntax Check):** Always run this on modified files.
    ```bash
    php -l app/Controllers/ApiController.php
    ```
*   **Unit Tests:** Run the full suite or specific test files.
    ```bash
    composer test
    # Or for a specific file:
    vendor/bin/phpunit tests/unit/HealthTest.php
    ```
*   **API Verification (Manual):** Use `curl` to simulate frontend requests. Note that most endpoints require a session/auth.
    ```bash
    # Example: Test if the API responds (may require mock auth in dev)
    curl -I http://localhost:8080/api/ls
    ```

### 9.2 Frontend Testing
*   **I18n Validation:** Ensure JSON files are valid after adding keys.
    ```bash
    cat public/assets/i18n/en.json | python3 -m json.tool > /dev/null
    ```
*   **JS Logic:** Review `public/assets/js/app.js` and `store.js` for reactive state consistency.

### 9.3 Security & Permissions
*   **Path Traversal Check:** Verify that new VFS operations cannot access files outside `writable/file_manager_root`.
*   **Permissions Check:** Verify that directory creation uses `0755` and files use `0644`.
    ```bash
    ls -la writable/file_manager_root
    ```

### 9.4 Quality Gates
Before "Landing the Plane" (session end), ensure:
1. All modified PHP files pass `php -l`.
2. `composer test` passes (if applicable tests exist).
3. Any new i18n keys are present in at least `en.json`.
