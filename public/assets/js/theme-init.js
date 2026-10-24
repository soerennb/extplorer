// Applies the saved colour theme before the first paint, so pages that follow a
// dark preference do not flash light while the application scripts load.
(function () {
    const root = document.documentElement;
    const query = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const saved = () => {
        try { return localStorage.getItem('extplorer_theme') || 'auto'; } catch (e) { return 'auto'; }
    };
    const apply = () => {
        const theme = saved();
        const dark = theme === 'dark' || (theme === 'auto' && Boolean(query && query.matches));
        root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
    };

    apply();
    if (query && query.addEventListener) {
        query.addEventListener('change', () => { if (saved() === 'auto') apply(); });
    }
})();
