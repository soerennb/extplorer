# eXtplorer 3 - New Features & Enhancements (Session Jan 13, 2026)

This session focused on hardening security, improving user transparency, and introducing advanced file management capabilities.

## 1. 🛡️ Security Hardening
*   **Encrypted 2FA Secrets:** Two-Factor Authentication secrets and recovery codes are now encrypted at rest using server-side keys.
*   **Automatic Encryption Key Management:** The app now automatically generates and manages a secure encryption key (`writable/secret.key`) if not provided in the environment, ensuring "unzip and run" security.
*   **Intelligent Upload Protection:** 
    *   Implemented a **System Default Blocklist** for dangerous extensions (`php`, `exe`, `sh`, etc.).
    *   Uploads are now pre-validated on the client side.
*   **Safer Downloads:** Restricted inline file opening to safe media types to prevent XSS via malicious file uploads.
*   **Secure Permissions:** Updated directory creation to use `0755` instead of `0777`.

## 2. ♻️ Recycle Bin (Trash Can)
*   **Safe Deletion:** Files are no longer immediately unlinked. They are moved to a user-specific trash area.
*   **Management UI:** A new "Trash" view in the sidebar allows users to:
    *   Restore files to their original location (with auto-rename on collision).
    *   Permanently delete specific items.
    *   Empty the entire trash.

## 3. 👤 User Profile & Security Settings
*   **Centralized Settings:** A new "Profile & Settings" modal replaces the simple password change dialog.
*   **2FA Integration:** Users can now enable TOTP-based Two-Factor Authentication via a built-in setup wizard (QR Code).
*   **Transparency:** Users and Admins can now see exactly which file extensions are allowed or blocked by system policy directly in the profile and user management panels.

## 4. 🔗 Public Share Links
*   **External Access:** Users can generate unique, secure links for files and folders.
*   **Advanced Options:** Supports password protection and expiration dates (1, 7, or 30 days).
*   **Public View:** A dedicated, lightweight "Shared" view for external visitors with preview and download capabilities.
*   **Visual Indicators:** Shared items are marked with a specific icon in the main file explorer.

## 5. 👁️ Advanced Media Previews
*   **Expanded Support:** Beyond images, the app now natively previews **PDFs**, **Videos** (MP4, WebM), and **Audio** (MP3, WAV) directly in the browser.
*   **Unified Previewer:** A refactored modal provides a consistent experience across all media types with gallery navigation.

## 6. 🐞 Key Bug Fixes & UX Improvements
*   **Dynamic Title:** The browser tab title now updates to reflect the current path.
*   **Upload Feedback:** Fixed a bug where failed uploads (due to restrictions) were incorrectly reported as successful.
*   **UTF-8 Robustness:** Hardened the VFS layer to ensure full compatibility with emojis and multi-byte characters in filenames across different OS locales.
*   **Reliable Refresh:** Fixed a race condition where the file list wouldn't update after batch uploads.
