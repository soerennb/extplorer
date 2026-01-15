# Plan: Virtual File System with Mount Points

## 1. Objective
Replace the current single-root file system with a **Virtual File System (VFS)** that supports multiple **Mount Points**. This allows users to see a "Virtual Root" containing folders like `/Personal`, `/Shared`, `/Network`, etc., which map to different physical locations or adapters.

## 2. Architecture Changes

### A. New Service: `VirtualAdapter`
Create `app/Services/VFS/VirtualAdapter.php` implementing `IFileSystem`.

**Key Responsibilities:**
1.  **Mount Management:** Store a map of `alias => adapter`.
    *   Example: `['Personal' => LocalAdapter(...), 'Shared' => LocalAdapter(...)]`
2.  **Path Routing:**
    *   Parse incoming paths (e.g., `/Personal/docs/file.txt`).
    *   Extract the first segment (`Personal`).
    *   Look up the corresponding adapter.
    *   Strip the alias and pass the remaining path (`docs/file.txt`) to that adapter.
3.  **Root Listing (`ls /`):**
    *   Return the list of mount points as "Virtual Directories".
4.  **Cross-Mount Operations:**
    *   `move` and `copy` must detect if `source` and `dest` are on different mounts.
    *   If different, perform a manual Read-Stream-Write (and Delete for move) operation.

### B. Updated `ApiController`
Refactor the constructor to use a `VfsFactory` or inline logic to compose the `VirtualAdapter` based on user context.

**Configuration Logic:**
*   **Root:** The top-level is always virtual.
*   **Default Mounts:**
    *   `/Personal`: Maps to `writable/users/{username}/`.
    *   `/Shared`: Maps to `writable/shared/`.
    *   `/Public`: Maps to `writable/public/`.
*   **Backward Compatibility:** If a user has a legacy "Jail" configured, we might mount that as `/Home` or just `/Personal`.

### C. User Provisioning
Ensure directories for `Personal` mounts are created automatically when a user logs in or is created, as the `VirtualAdapter` cannot "create" a mount point dynamically via `mkdir`.

## 3. Implementation Steps

### Step 1: Create `VirtualAdapter` Class
*   Implement `IFileSystem`.
*   Add `mount(string $alias, IFileSystem $adapter)` method.
*   Implement path parsing helper.
*   Implement `listDirectory` to merge mount points.
*   Implement `move`/`copy` with cross-mount support.

### Step 2: Create `VfsFactory` (Optional but Cleaner)
*   Encapsulate the creation logic.
*   `VfsFactory::createForUser(array $user)` returns `IFileSystem`.

### Step 3: Refactor `ApiController`
*   Replace direct `LocalAdapter` instantiation with the factory/virtual logic.

### Step 4: Testing
*   Verify `ls /` shows mounts.
*   Verify file operations inside a mount.
*   Verify moving files between mounts.

## 4. Migration Strategy
*   **Phase 1:** Implement `VirtualAdapter` but keep using `LocalAdapter` in `ApiController` (No breaking change).
*   **Phase 2:** Switch `ApiController` to use `VirtualAdapter` with a single `/Home` mount pointing to the old root (Backward compatible).
*   **Phase 3:** Expand to `/Personal` and `/Shared` mounts.

## 5. Notes
*   **Search:** Searching from `/` will need to iterate over all mounts and merge results.
*   **Permissions:** Each mount can have different permission sets (e.g., `/Shared` might be Read-Only for some users).
