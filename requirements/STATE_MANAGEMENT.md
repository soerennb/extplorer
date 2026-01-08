# Application State Management

## 1. Store Pattern (Simple Global State)
Since we are not using a bundler, we cannot easily import `Pinia` or `Vuex`. Instead, we will use Vue 3's `reactive()` system to create a simple global store.

```javascript
// assets/js/store.js
const store = Vue.reactive({
    cwd: '/',          // Current Working Directory
    files: [],         // List of files in CWD
    selectedItems: [], // Currently selected files/folders
    clipboard: {       // Copy/Cut buffer
        items: [],
        mode: null     // 'copy' or 'cut'
    },
    viewMode: 'grid',  // 'grid' or 'list'
    isLoading: false,  // Global loading spinner
    
    // Actions
    setCwd(path) { this.cwd = path; },
    toggleSelection(file) { ... }
});
```

## 2. Component Communication
*   **Events:** Components (like the `FileTree`) will emit events (e.g., `@navigate`) that the main `App` component listens to.
*   **Shared State:** The `store` object will be available to all components to read the current file list or selection status.

## 3. Persistent Settings
We will use `localStorage` to persist user preferences across page reloads:
*   `extplorer_theme` (light/dark)
*   `extplorer_view_mode` (grid/list)
*   `extplorer_sidebar_width`

## 4. API Layer
A simple `api.js` wrapper will handle all fetch requests to the backend:
*   `Api.ls(path)`
*   `Api.content(path)`
*   `Api.write(path, content)`
*   `Api.delete(paths)`
This layer handles CSRF tokens and standardizes error responses (displaying SweetAlert2 on failure).
