# Architecture Overview

## 1. System Design Pattern
The application should follow a **Model-View-Controller (MVC)** or similar separation of concerns.

*   **Front-End (View):** A Single Page Application (SPA) or a rich JavaScript-driven interface. It communicates with the backend via AJAX/Fetch APIs. It handles the rendering of the file tree, file lists, and modal dialogs (editors, settings).
*   **Back-End (Controller):** A central dispatcher (e.g., `index.php`) that receives requests, authenticates the session, and routes the action to the appropriate handler.
*   **Data Layer (Model):** Abstracted file system access. The application should not call native PHP file functions (`fopen`, `scandir`) directly in the controllers. Instead, it should use a **Virtual File System (VFS)** abstraction layer.

## 2. Virtual File System (VFS) Abstraction
To support Local Files, FTP, and SSH2 uniformly, the system requires an abstraction interface:

*   **Interface:** `IFileSystem`
    *   Methods: `listDirectory()`, `readFile()`, `writeFile()`, `delete()`, `rename()`, `chmod()`, etc.
*   **Adapters:**
    *   `LocalAdapter`: Implements `IFileSystem` using standard PHP filesystem functions.
    *   `FTPAdapter`: Implements `IFileSystem` using PHP's FTP extension.
    *   `SSH2Adapter`: Implements `IFileSystem` using the SSH2 extension.

## 3. Plugin/Action System
Features should be implemented as isolated modules or "Actions" that are registered with the main application.

*   **Core Actions:** List, View, Download.
*   **Optional Actions:** Edit, Archive, UserAdmin.
*   **Mechanism:** The frontend requests an action (e.g., `action=edit&file=test.txt`). The backend checks if the user has permission for that action on that resource, instantiates the Action class, and returns the result (HTML for the editor or JSON status).

## 4. Configuration & State
*   **Configuration:** Stored in a dedicated file (e.g., PHP array or JSON) separate from the code.
*   **State:** User preferences (e.g., last view mode, sorting preference) can be stored in the browser (LocalStorage) or user session.

## 5. Internationalization Strategy
*   Dictionaries stored in key-value format (PHP arrays or JSON).
*   The frontend or backend rendering engine replaces placeholders (e.g., `L_FILE_DELETE`) with the translated string based on the user's selected language.
