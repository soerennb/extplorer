# Architecture Overview

## 1. Technology Stack
*   **Backend:** PHP 8.1+ with CodeIgniter 4 Framework.
*   **Frontend:** Vue.js 3 (No-Build implementation) + Bootstrap 5.
*   **Data Storage:** JSON Files (`writable/*.json`). No SQL database required.

## 2. Virtual File System (VFS)
The core of eXtplorer is the VFS abstraction layer. It decouples the UI from the physical storage.

*   **Adapters:**
    *   `LocalAdapter`: Manipulates files on the server's disk.
    *   `FtpAdapter`: Connects to an FTP server.
    *   `Ssh2Adapter`: Connects via SSH/SFTP.
*   **Jails:**
    *   All Local users are jailed to `writable/file_manager_root` (or a subdirectory thereof).
    *   Path traversal attempts (e.g., `../../etc/passwd`) are blocked by the VFS layer.

## 3. Security Model
*   **Authentication:** Session-based.
*   **Authorization:** Role-Based Access Control (RBAC).
    *   Permissions are granular (e.g., `file.delete`, `dir.create`).
    *   Roles and Groups are defined in JSON.
*   **CSRF Protection:** Enabled globally for API requests.

## 4. Frontend Architecture
The frontend is a Single Page Application (SPA).
*   **Assets:** Located in `public/assets`.
*   **Router:** Hash-based routing (e.g., `#/path/to/folder`).
*   **State:** Uses Vue 3's Reactivity system to sync the file list and user state.
*   **No Build Step:** Vue components are loaded directly or concatenated, allowing for easy modification without Node.js tools.
