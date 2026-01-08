# Non-Functional Requirements

## 1. Security
*   **Access Control:** Strict enforcement of "Home Directory" jails. Users must never be able to access files outside their assigned path via URL manipulation or API calls.
*   **Input Sanitization:** All user inputs (filenames, paths, search terms) must be rigorously sanitized to prevent Path Traversal, XSS, and Command Injection attacks.
*   **Authentication:** Passwords must be hashed using strong algorithms (e.g., bcrypt, Argon2) if stored locally.
*   **Session Management:** Secure handling of user sessions with timeouts and protection against hijacking.
*   **Hidden Files:** Configurable option to hide dotfiles (e.g., `.env`, `.git`) from standard users.

## 2. Performance
*   **Lazy Loading:** Directory trees should load content on demand (AJAX) rather than rendering the entire filesystem structure at startup.
*   **Pagination:** Large directories (thousands of files) should use pagination or virtual scrolling to prevent browser lag.
*   **Efficient Transfers:** File transfers (up/down) should stream data where possible to avoid memory exhaustion on the server.

## 3. Compatibility
*   **Browser Support:** Must work on all modern desktop browsers (Chrome, Firefox, Safari, Edge).
*   **Server Support:** Should be compatible with common web server environments (Apache, Nginx, IIS) and standard PHP configurations.
*   **OS Independence:** The core logic should handle file path separators and system commands in a way that supports both Linux/Unix and Windows servers.

## 4. Usability
*   **Responsive Design:** While primarily a desktop-class tool, the layout should adapt reasonably well to tablet screens.
*   **Feedback:** Visual indicators for long-running operations (loading spinners, progress bars).
*   **Shortcuts:** Keyboard shortcuts for common actions (Delete, Copy, Save) are highly desirable.

## 5. Extensibility
*   **Modular Architecture:** The system should be designed so that new "action" types (e.g., a new editor or a new protocol) can be added without rewriting the core.
