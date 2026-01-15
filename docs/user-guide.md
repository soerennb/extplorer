# User Guide

## 1. Logging In
*   **Local:** Use the username/password provided by your administrator.
*   **FTP/SFTP:** Select "FTP" or "SFTP" from the dropdown. Enter the remote Host, Port, Username, and Password. This mode connects directly to the remote server; the local eXtplorer user database is bypassed.

## 2. File Operations
*   **Navigation:** Click folders to enter. Use the breadcrumb bar to jump back.
*   **Selection:**
    *   Click to select a file.
    *   `Ctrl+Click` (Cmd+Click) for multiple selection.
    *   `Shift+Click` for range selection.
*   **Actions:** Right-click any file to see the context menu:
    *   **Open:** Opens files in the Editor or Viewer.
    *   **Rename:** Change file name.
    *   **Cut/Copy/Paste:** Standard clipboard operations.
    *   **Download:** Download file to your computer.
    *   **Archive:** Zip/Tar selected files.
    *   **Permissions:** Change chmod (e.g., 0755).

## 3. The Editor
eXtplorer features a powerful code editor (Ace).
*   **Syntax Highlighting:** Automatically detects file type.
*   **Save:** `Ctrl+S` (Cmd+S).
*   **Search:** `Ctrl+F` (Cmd+F).

## 4. User Management (Admin Only)
Navigate to **Admin > Users**.

*   **Add User:**
    *   **Username/Password:** Credentials for login.
    *   **Role:**
        *   `Admin`: Full access.
        *   `User`: Standard access (defined by Role permissions).
    *   **Home Directory:** Restrict the user to a specific subfolder (e.g., `/home/john`). The user CANNOT see outside this folder.
*   **Roles:** Define sets of permissions (e.g., "ReadOnly" role with only `read` and `download` permissions).

## 5. WebDAV Access
You can mount your eXtplorer files as a network drive on your computer.

*   **URL:** `https://your-site.com/dav`
*   **Username/Password:** Your eXtplorer credentials.

**Windows:**
1.  Open File Explorer > Map Network Drive.
2.  Folder: `https://your-site.com/dav`
3.  Check "Connect using different credentials".

**macOS:**
1.  Finder > Go > Connect to Server (`Cmd+K`).
2.  Address: `https://your-site.com/dav`

*Note: WebDAV requires HTTPS for reliable operation on Windows and macOS.*
