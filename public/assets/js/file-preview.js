function useFilePreview(files) {
    const { reactive, onMounted } = Vue;
    const types = { image: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], video: ['mp4', 'webm', 'ogv'],
        audio: ['mp3', 'wav', 'ogg'], pdf: ['pdf'] };
    const mediaType = (file) => Object.keys(types).find((type) => types[type].includes(file.extension?.toLowerCase()));
    const previewState = reactive({ src: '', index: 0, list: [], type: '', filename: '' });
    let modal;
    onMounted(() => { modal = new bootstrap.Modal(document.getElementById('previewModal')); });
    const update = () => {
        const file = previewState.list[previewState.index];
        if (!file) return;
        previewState.filename = file.name;
        previewState.src = Api.route('api/download') + '?path=' + encodeURIComponent(file.path) + '&inline=1';
        previewState.type = mediaType(file);
    };
    const openPreview = (file) => {
        previewState.list = files().filter(mediaType);
        previewState.index = previewState.list.findIndex((item) => item.path === file.path);
        if (previewState.index < 0) {
            previewState.list = [file];
            previewState.index = 0;
        }
        store.addToRecent(file);
        update();
        modal.show();
    };
    const nextPreview = () => {
        if (previewState.index < previewState.list.length - 1) { previewState.index++; update(); }
    };
    const prevPreview = () => {
        if (previewState.index > 0) { previewState.index--; update(); }
    };
    return { previewState, openPreview, nextPreview, prevPreview, isPreviewable: (file) => Boolean(mediaType(file)) };
}
