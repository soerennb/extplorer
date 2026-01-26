# Agent Instructions

This project uses **bd** (beads) for issue tracking. Run `bd onboard` to get started.

## Quick Reference

```bash
bd ready              # Find available work
bd show <id>          # View issue details
bd update <id> --status in_progress  # Claim work
bd close <id>         # Complete work
bd sync               # Sync with git
```

## CSP Implementation Rules (Must Follow)

To avoid CSP warnings and regressions:

- **No inline scripts/styles**: prefer external JS/CSS files. If inline is unavoidable, always add `<?= csp_script_nonce() ?>` / `<?= csp_style_nonce() ?>` to the `<script>`/`<style>` tag.
- **No inline event handlers**: avoid `onclick=`, `onload=`, etc. Use JS event listeners in a script file or a nonce’d script block.
- **No `style=` attributes**: move styles into CSS classes and include them in a nonce’d `<style>` block or external stylesheet.
- **Keep CSP strict**: do not add `unsafe-inline` back to `script-src` or `style-src`. If a new use case requires it, refactor instead.
- **Before finishing**: scan `app/Views` for inline scripts/styles/handlers and ensure they follow the rules above.

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
   bd sync
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
