# Agent Instructions

## Issue Tracking

This project uses **bd (beads)** for issue tracking.
Run `bd prime` for workflow context, or install hooks with `bd hooks install` for auto-injection.

**Quick reference:**

```bash
bd ready                         # Find unblocked work
bd show <id>                     # View issue details
bd update <id> --status in_progress  # Claim work
bd create --title="Title" --type task --priority 2
bd close <id>                    # Complete work
bd dolt push                     # Push beads to Dolt remote
```

For full workflow details: `bd prime`

## CSP Implementation Rules (Must Follow)

To avoid CSP warnings and regressions:

- **No inline scripts/styles**: prefer external JS/CSS files. If inline is unavoidable, always add `<?= csp_script_nonce() ?>` / `<?= csp_style_nonce() ?>` to the `<script>`/`<style>` tag.
- **No inline event handlers**: avoid `onclick=`, `onload=`, etc. Use JS event listeners in a script file or a nonce’d script block.
- **No `style=` attributes**: move styles into CSS classes and include them in a nonce’d `<style>` block or external stylesheet.
- **Keep CSP strict**: do not add `unsafe-inline` back to `script-src` or `style-src`. If a new use case requires it, refactor instead.
- **Before finishing**: scan `app/Views` for inline scripts/styles/handlers and ensure they follow the rules above.

## Localization & Translations

When adding new user-facing strings to the application:

- **Check existing strings**: Before adding a new key, search `resources/i18n/en/` (frontend) or `app/Language/en/` (backend) to see if an appropriate string or key already exists.
- **Update all files**: New strings **MUST** be added to all available language files.
    - Frontend: matching domain files in `resources/i18n/en/`, `de/`, `fr/`, and `sk/`.
    - Backend: `app/Language/en/` and any other locale directories present.
- **Maintain Consistency**: Keep keys identical across all files. If a translation is unknown, use the English version as a temporary placeholder rather than leaving the key out.
- **Verify JSON**: After editing, run `composer i18n:build` and `composer i18n:check`.

## Translation Runtime & Build Contract

- PHP and browser translation loaders must use `public/assets/i18n/<locale>.json`. `resources/i18n/` is a build input and must not be required by an installed application.
- Every deployable archive and image must contain `public/assets/i18n/locales.json` and every language bundle listed in it, including English.
- Generate and validate runtime bundles before packaging. Verify each final ZIP and TAR.GZ with `php scripts/check-release-i18n.php <archive>` before release; local and GitHub archive builds enforce this automatically.
- Preserve the archive gate even when CI checks are reused. It tests the packaged Login controller without translation sources; checkout tests alone do not catch missing packaged files.
- See [the translation runtime and release contract](docs/translations.md#runtime-and-release-contract) for the build sequence and requirements.

## HTTP Routing & CSRF Regression Contract

- Match HTTP routes using `IncomingRequest::getPath()`, which excludes the installation directory and configured index page. Do not classify routes using the full URL path.
- Preserve `X-CSRF-HASH` on API and public-share responses, including the `428` step-up challenge and controller validation errors. Keep CSRF randomization and regeneration enabled.
- Test routing changes at the domain root and in a subdirectory, both with and without `index.php`, using real `SiteURI` instances and the CSRF verifier.
- Before releasing a native archive, run the [native release routing checks](tests/e2e/README.md#native-release-routing-checks) against the extracted final ZIP. The existing Docker smoke suite uses root URLs and does not cover this deployment matrix.

## Dependency Maintenance & Release Checks

- Prioritize security fixes, then patch/minor updates within the existing release lines. Track major migrations separately; never suppress an advisory to avoid a required migration.
- Keep Docker, Compose, CI and documented tool versions consistent. Update lockfiles with the package manager and minimize unrelated dependency changes.
- Audit locked Composer/NPM packages and the built container before release. Network errors and jobs that never acquire a GitHub runner are incomplete checks, not successful audits; rerun them.
- Review the copied browser asset inventory as well as package-managed dependencies. Do not infer a version for unidentified bundles.
- Record the commit, tool/dependency versions, audit and test results, archive checksums and all four native routing results with release evidence.
- Follow the [dependency update and release checks](docs/dependency-updates.md) for commands, inventory and required evidence.

## Docker Note

- Docker uses an init container to populate the shared code volume; updates refresh automatically based on the image version marker.

## Landing the Plane (Session Completion)

**When ending a work session**, you MUST complete ALL steps below. Work is NOT complete until `git push` succeeds.

**MANDATORY WORKFLOW:**

1. **File issues for remaining work** - Create issues for anything that needs follow-up
2. **Run quality gates** (if code changed) - Tests, linters, builds
3. **Update issue status** - Close finished work, update in-progress items
4. **PUSH TO REMOTE** - This is MANDATORY:
   ```bash
   git pull --rebase
   bd dolt push
   git push
   git status  # MUST show "up to date with origin"
   ```
5. **Clean up** - Clear stashes, prune remote branches
6. **Verify** - All changes committed AND pushed
7. **Hand off** - Provide context for next session

**CRITICAL RULES:**
- Work is NOT complete until `git push` succeeds
- NEVER stop before pushing - that leaves work stranded locally
- NEVER say "ready to push when you are" - YOU must push
- If push fails, resolve and retry until it succeeds
