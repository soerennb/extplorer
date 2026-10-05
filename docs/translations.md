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

## Runtime and Release Contract

`resources/i18n/` contains editable build inputs. PHP pages (including login and public shares) and the browser load
messages from `public/assets/i18n/<locale>.json`. New server-side translation loaders must use these runtime bundles;
an installed application must work without `resources/i18n/`.

Every deployable ZIP, TAR.GZ and container image must include `public/assets/i18n/locales.json` and the generated JSON
bundle for every locale listed in that manifest, including the English fallback. Translation sources may also be packaged,
but must never be required at runtime.

Before packaging a build from a checkout, generate and validate the committed bundles:

```bash
composer i18n:build
composer i18n:check
```

Commit any generated changes together with their source changes. Then create the archives with `./build.sh` and verify
each final archive, for example:

```bash
php scripts/check-release-i18n.php builds/extplorer3-3.0.0.zip
php scripts/check-release-i18n.php builds/extplorer3-3.0.0.tar.gz
```

Replace the version in these examples with the version in `app/Config/App.php`. The checker requires PHP with ZIP
support and the `tar` executable for TAR.GZ archives. It extracts the archive into a temporary directory, removes any
packaged translation sources and runs the packaged Login controller in a separate PHP process using the packaged
autoloader. Every manifest locale must have
a readable, valid runtime bundle and all login translations must resolve to the bundled texts rather than key names.
The checker exits nonzero on failure and removes its temporary files on success or failure.

`./build.sh` automatically generates and validates bundles, then checks every archive it creates. The GitHub release
workflow packages the committed bundles validated by Quality and checks its final ZIP before generating the checksum or
publishing. The archive check always runs, including releases that reuse successful checks for the same commit.
A failed archive check blocks release; a passing checkout translation check alone does not verify the deployed package.
