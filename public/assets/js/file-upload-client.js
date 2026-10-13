const FileUploadClient = {
    chunkSize: 1024 * 1024,
    async upload(file, { path, relativePath = '', conflict = 'replace', signal, onProgress = () => {} }) {
        if (file.size <= this.chunkSize) {
            const form = new FormData();
            form.append('file', file);
            form.append('path', path);
            form.append('relativePath', relativePath);
            form.append('conflict', conflict);
            return Api.upload('api/upload', form, { signal, onProgress });
        }
        const totalChunks = Math.ceil(file.size / this.chunkSize);
        let id;
        try {
            const session = await Api.post('upload/session', {
                filename: file.name, path, relativePath, conflict, totalSize: file.size,
                chunkSize: this.chunkSize, totalChunks
            }, { signal });
            id = session.id;
            if (!id) throw new Error(Api.genericServerError());
            const endpoint = 'upload/session/' + encodeURIComponent(id);
            for (let index = 0; index < totalChunks; index++) {
                await Api.put(endpoint + '/chunk/' + index,
                    file.slice(index * this.chunkSize, (index + 1) * this.chunkSize),
                    { contentType: 'application/octet-stream', signal });
                onProgress((index + 1) / totalChunks);
            }
            const result = await Api.post(endpoint + '/complete', {}, { signal });
            id = null;
            return result;
        } finally {
            // A canceled request must still release its server-side chunks and reservation.
            if (id) await Api.delete('upload/session/' + encodeURIComponent(id)).catch(() => {});
        }
    }
};
