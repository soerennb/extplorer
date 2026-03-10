# Agent Instructions

## Issue Tracking

This project uses **bd (beads)** for issue tracking.
Run `bd prime` for workflow context, or install hooks with `bd hooks install` for auto-injection.

In this repository, prefer `scripts/bd-local ...` over raw `bd ...` because it applies the working local Dolt settings on this machine.

**Quick reference:**

```bash
scripts/bd-local ready                         # Find unblocked work
scripts/bd-local show <id>                     # View issue details
scripts/bd-local update <id> --status in_progress  # Claim work
scripts/bd-local create --title="Title" --type task --priority 2
scripts/bd-local close <id>                    # Complete work
scripts/bd-local dolt push                     # Push beads to Dolt remote
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

- **Check existing strings**: Before adding a new key, search `public/assets/i18n/en.json` (frontend) or `app/Language/en/` (backend) to see if an appropriate string or key already exists.
- **Update all files**: New strings **MUST** be added to all available language files.
    - Frontend: `public/assets/i18n/en.json`, `de.json`, and `fr.json`.
    - Backend: `app/Language/en/` and any other locale directories present.
- **Maintain Consistency**: Keep keys identical across all files. If a translation is unknown, use the English version as a temporary placeholder rather than leaving the key out.
- **Verify JSON**: After editing, ensure the JSON files remain valid (no trailing commas, correct nesting).

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
   scripts/bd-local dolt push
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
