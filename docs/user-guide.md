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
*   **Username:** Your eXtplorer username.
*   **Password:** Create a revocable WebDAV app password under **Profile → Security**. Local accounts without 2FA may also use their normal password; accounts with 2FA must use an app password.

For website or company sign-in, WebDAV must first be enabled by the operator.
Confirm your identity with the provider when creating an app password, then use
the WebDAV username shown in your profile and the password displayed once.
These passwords expire after at most 30 days; create a replacement before the
displayed expiry. Browser sign-out leaves them active. Account suspension or
permission changes revoke them permanently.

The regular sign-out button ends your eXtplorer session. If your operator enables
provider sign-out, the separate option also signs you out at your website or
identity provider and may sign you out of other applications. It asks for confirmation.

**Windows:**
1.  Open File Explorer > Map Network Drive.
2.  Folder: `https://your-site.com/dav`
3.  Check "Connect using different credentials".

**macOS:**
1.  Finder > Go > Connect to Server (`Cmd+K`).
2.  Address: `https://your-site.com/dav`

*Note: WebDAV requires HTTPS in production. If external upload scanning is enabled, WebDAV remains available for reads and file management but rejects new PUT uploads because DAV clients cannot represent the asynchronous quarantine state safely.*
