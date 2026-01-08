const Api = {
    async get(endpoint, params = {}) {
        // baseUrl is defined in the main layout: <script>const baseUrl = "<?= base_url() ?>";</script>
        // It includes the trailing slash.
        const url = new URL('api/' + endpoint, baseUrl); 
        
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        const res = await fetch(url);
        const data = await res.json();
        if (!res.ok) throw new Error(data.messages?.error || res.statusText);
        return data;
    },
    async post(endpoint, data = {}) {
        const url = baseUrl + 'api/' + endpoint;
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.messages?.error || res.statusText);
        return json;
    }
};
