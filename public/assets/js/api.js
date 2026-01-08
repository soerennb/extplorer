const Api = {
    async get(endpoint, params = {}) {
        // window.baseUrl is defined in the main layout
        const url = new URL('api/' + endpoint, window.baseUrl); 
        
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        const res = await fetch(url);
        const data = await res.json();
        if (!res.ok) throw new Error(data.messages?.error || res.statusText);
        return data;
    },
    async post(endpoint, data = {}) {
        return this.request(endpoint, 'POST', data);
    },
    async put(endpoint, data = {}) {
        return this.request(endpoint, 'PUT', data);
    },
    async delete(endpoint, data = {}) {
        return this.request(endpoint, 'DELETE', data);
    },
    async request(endpoint, method, data = {}) {
        const url = window.baseUrl + 'api/' + endpoint;
        const headers = { 
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        if (window.csrfTokenName && window.csrfHash) {
            headers['X-CSRF-TOKEN'] = window.csrfHash;
        }

        const res = await fetch(url, {
            method: method,
            headers: headers,
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.messages?.error || res.statusText);
        return json;
    }
};
