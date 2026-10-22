// SweetAlert2 renders `title` and the positional second argument as HTML.
// Application alerts show server messages, filenames and remote responses, so
// both are treated as text. Explicit `html:` templates remain available.
(function () {
    const Swal = window.Swal;
    if (!Swal || typeof Swal.fire !== 'function' || Object.prototype.hasOwnProperty.call(Swal, '__extplorerTextAlerts')) return;

    const fire = Swal.fire;
    const asText = (value) => (value === undefined || value === null ? undefined : String(value));

    Swal.fire = function (...args) {
        const first = args[0];
        if (args.length > 0 && (first === null || typeof first !== 'object')) {
            const [title, text, icon] = args;
            return fire.call(this, { titleText: asText(title), text: asText(text), icon });
        }
        if (args.length === 1 && Object.prototype.hasOwnProperty.call(first, 'title')
            && !Object.prototype.hasOwnProperty.call(first, 'titleText')) {
            const { title, ...params } = first;
            return fire.call(this, { ...params, titleText: asText(title) });
        }
        return fire.apply(this, args);
    };
    Object.defineProperty(Swal, '__extplorerTextAlerts', { value: true });
})();
