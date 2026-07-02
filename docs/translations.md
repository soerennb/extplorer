# Translation Maintenance

eXtplorer keeps editable translation sources in `resources/i18n/` and builds the browser/runtime files in `public/assets/i18n/`.

## Editing Existing Text

1. Find the English key in `resources/i18n/en/`.
2. Update the same key in every locale directory: `de`, `fr`, and `sk`.
3. Keep placeholders identical across languages. For example, `{count}` in English must also be present as `{count}` in every translation.
4. Run:

```bash
composer i18n:build
composer i18n:check
```

If a translation is not known yet, copy the English value temporarily instead of leaving the key empty or missing.

## Adding New Text

1. Reuse an existing key when the existing wording fits.
2. Add the new key to the appropriate domain file under every locale directory.
3. Prefer descriptive keys grouped by feature, such as `admin_settings_*`, `shared_*`, `transfer_*`, or `mount_*`.
4. Run the build and check commands before committing.

## Adding A Language

1. Add a new entry to `resources/i18n/locales.json`.
2. Create `resources/i18n/<locale>/` with the same domain files as `resources/i18n/en/`.
3. Copy English values first, then translate incrementally.
4. Run `composer i18n:build` and `composer i18n:check`.

The generated files in `public/assets/i18n/` are committed so deployed builds can load translations without a Node.js step at runtime.
