function useFileDragDrop(performUpload, resetUploadProgress, reload) {
    const { ref } = Vue;
    const dragIndicatorMode = ref(null);

    const getInternalDropOperation = (e) => (e.ctrlKey || e.metaKey ? 'copy' : 'move');

    const isExternalFileDrag = (e) => {
        const items = e.dataTransfer?.items;
        return Boolean(items && items.length > 0 && items[0].kind === 'file');
    };

    const onDragStart = (e, f) => {
        const items = store.isSelected(f) ? store.selectedItems : [f];
        e.dataTransfer.setData('text/plain', JSON.stringify(items));
        e.dataTransfer.effectAllowed = 'copyMove';
    };

    const onDragEnd = () => {
        dragIndicatorMode.value = null;
        store.isDraggingOver = false;
    };

    const onDragOver = (e, file = null) => {
        e.preventDefault();
        const operation = isExternalFileDrag(e) ? 'copy' : getInternalDropOperation(e);
        e.dataTransfer.dropEffect = operation === 'copy' ? 'copy' : 'move';
        dragIndicatorMode.value = isExternalFileDrag(e) ? null : operation;
        if (file && file.type === 'dir') {
            file.isDragOver = true;
        } else if (!file) {
            store.isDraggingOver = true;
        }
    };

    const onDragLeave = (file = null) => {
        if (file) file.isDragOver = false;
        else {
            store.isDraggingOver = false;
            dragIndicatorMode.value = null;
        }
    };

    const traverseFileTree = async (item, path) => {
        if (item.isFile) {
            const file = await new Promise((resolve, reject) => item.file(resolve, reject));
            await performUpload([file], path, true);
        } else if (item.isDirectory) {
            const newPath = (path ? path + '/' : '') + item.name;
            try { await Api.post('mkdir', { path: newPath }); } catch(e) {} // Ignore if exists
            const dirReader = item.createReader();
            const entries = await readAllEntries(dirReader);
            for (let entry of entries) {
                await traverseFileTree(entry, newPath);
            }
        }
    };

    const countEntries = async (item) => {
        if (item.isFile) return 1;
        if (item.isDirectory) {
            let count = 0;
            const dirReader = item.createReader();
            const entries = await new Promise(resolve => dirReader.readEntries(resolve));
            for (let entry of entries) count += await countEntries(entry);
            return count;
        }
        return 0;
    };

    const onDrop = async (e, target) => {
        e.preventDefault();
        store.isDraggingOver = false;
        dragIndicatorMode.value = null;
        if (target) target.isDragOver = false;

        // 1. External Files/Folders (Upload)
        const items = e.dataTransfer.items;
        if (items && items.length > 0 && items[0].kind === 'file') {
            const targetPath = target ? target.path : store.cwd;
            store.isLoading = true;
            store.uploadProgress = 0;
            store.uploadCurrent = 0;
            store.uploadTotal = 0;

            // Scan for total items first
            try {
                for (let i = 0; i < items.length; i++) {
                    const entry = items[i].webkitGetAsEntry();
                    if (entry) store.uploadTotal += await countEntries(entry);
                }

                for (let i = 0; i < items.length; i++) {
                    const entry = items[i].webkitGetAsEntry();
                    if (entry) await traverseFileTree(entry, targetPath);
                }
                Swal.fire(i18n.t('uploaded'), '', 'success');
            } catch (e) {
                Swal.fire(i18n.t('error'), e.message, 'error');
            } finally {
                store.isLoading = false;
                resetUploadProgress();
                reload();
                store.refreshTree();
            }
            return;
        }

        // 2. Internal Transfer
        try {
            const s = e.dataTransfer.getData('text/plain');
            if (!s) return;
            const items = JSON.parse(s);
            const list = Array.isArray(items) ? items : [items];
            if (!target) return;
            const operation = getInternalDropOperation(e);

            for (let item of list) {
                if (item.path === target.path) continue;
                await Api.post(operation === 'copy' ? 'cp' : 'mv', { from: item.path, to: target.path + '/' + item.name });
            }
            reload();
            store.refreshTree();
            store.selectedItems = [];
        } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
    };
    async function readAllEntries(reader) {
        const entries = [];
        while (true) {
            const batch = await new Promise((resolve, reject) => reader.readEntries(resolve, reject));
            if (batch.length === 0) return entries;
            entries.push(...batch);
        }
    }
    return { dragIndicatorMode, onDragStart, onDragEnd, onDragOver, onDragLeave, onDrop };
}
