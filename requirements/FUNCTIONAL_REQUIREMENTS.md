# Functional Requirements

## 1. File Management Core
The system must provide a robust file manager interface accessible via a web browser.

### 1.1 Navigation & Browsing
*   **Directory Tree:** Display a collapsible tree view of the directory structure.
*   **File List:** Display files in the current directory with columns for Name, Size, Type, Permissions, and Modification Date.
*   **Grid/List Views:** Support toggling between list and grid views (thumbnails).
*   **Sorting:** Allow sorting by any column (Name, Size, Date).
*   **Filtering:** Provide a filter input to quickly narrow down the visible file list.

### 1.2 File Operations
*   **CRUD:** Support Create, Read (View), Update (Edit), and Delete operations for files and folders.
*   **Manipulation:** Support Move, Copy, and Rename actions.
*   **Bulk Actions:** Allow selecting multiple files for batch operations (Delete, Move, Copy).
*   **Drag & Drop:** (Optional but recommended) Support dragging files to move them between folders.

### 1.3 Upload & Download
*   **File Upload:** Support uploading single or multiple files simultaneously. Ideally, support drag-and-drop from the user's OS.
*   **Large File Support:** Handle uploads efficiently, respecting server limits but providing progress feedback.
*   **Download:** Allow downloading single files.
*   **Folder Download:** (Optional) Automatically compress a folder into a zip/tarball when requested for download.

### 1.4 Editing & Viewing
*   **Code Editor:** Integrated text editor for code files (PHP, HTML, JS, CSS, etc.).
    *   **Syntax Highlighting:** Support coloring for common programming languages.
    *   **Line Numbers:** Display line numbers.
*   **Image Viewer:** Preview common image formats (PNG, JPG, GIF) directly in the browser.
*   **Diff Viewer:** Ability to compare two files and show differences (visual diff).

### 1.5 Archives
*   **Compression:** Create archives from selected files/folders. Supported formats should include at least ZIP and TAR/GZ.
*   **Extraction:** Extract common archive formats to a specified destination.

### 1.6 Permissions
*   **Chmod:** Visual interface to change file permissions (Read/Write/Execute for Owner/Group/World).
*   **Ownership:** (Admin only) Ability to change file owner/group if the server environment permits.

## 2. User Management & Authentication
The system must have its own authentication layer or integrate with the host system.

### 2.1 Authentication Methods
*   **Local Auth:** Store users and hashed passwords in a local configuration file or database.
*   **FTP Auth:** Authenticate users against an FTP server (using the FTP credentials to log in).
*   **System/PAM Auth:** (Optional) Authenticate against the underlying OS users.

### 2.2 User Roles
*   **Administrator:** Full access to all files (root equivalent within the web scope) and user management features.
*   **Standard User:** Restricted to their specific home directory and its subdirectories.

### 2.3 User Profile
*   **Home Directory:** Each user is assigned a specific root path they cannot navigate above.
*   **Password Management:** Users can change their own passwords.

## 3. Remote Connectivity
*   **FTP Client:** Ability to connect to external FTP servers, treating them as the file system.
*   **SSH2/SFTP:** Support for secure file transfer protocols.
*   **WebDAV Server:** (Optional) Expose the managed file system via WebDAV protocol, allowing users to mount it as a network drive on their OS.

## 4. Internationalization (i18n)
*   **Multi-language Support:** The interface must support multiple languages.
*   **Language Files:** All text strings should be loaded from external language files to allow easy translation.
