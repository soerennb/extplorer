# External authentication lifecycle validation — 2026-10-26

This report covers the implementation of Beads `ex3-42y` and `ex3-q4h`
in the commit containing this report, based on `555b7bf`. The 3.1.4 archives
are local validation artifacts for unreleased code; no release was published.

## Implemented behavior

- Shared external session registrations, independent of the PHP session handler.
- Optional confirmed provider logout; local logout completes before provider I/O.
- Signed RS256 OIDC backchannel logout and HMAC-authenticated JSON account/session events.
- Identity-level suspension and monotonic account snapshots, retained after account deletion.
- Atomic account/session/credential updates, pending-login barriers and current-rights checks.
- External WebDAV opt-in, fresh provider step-up, maximum 30-day app-password lifetime,
  permanent revocation on account/rights/provider changes, and expiry shown in the profile.
- Public Home, Shared and transfer shares pause with their owner's suspension.
- Explicit DAV method registration and preservation of SabreDAV response statuses.

## Environment

- PHP 8.2.34, 8.3.35, 8.4.26 and 8.5.11; Docker PHP 8.5.12, Alpine 3.24.3.
- Composer 2.10.3, Node 24.22.1, PHPUnit 11.5.57, PHPStan 2.3.1.
- Keycloak 26.8.1, pinned image digest
  `sha256:a78d6b79572168cb25886b80a8b5b8d4ed4390743e3eecb810538683e209dd6b`.
- Trivy 0.75.0, vulnerability database refreshed during this validation.
- No application dependency or lockfile changes.

## Results

- Full PHP suite on all four versions: 544 tests, 2,480 assertions,
  nine existing environment-dependent skips per run. The subsequent event-route
  header regression adds eight assertions and passes its focused suite.
- PHPStan: no errors. Frontend: 25 passing tests. Release publication contracts:
  20 passing tests. Runtime i18n: four locales, 829 consistent keys.
- File and SQL lifecycle tests cover session scope, logout/app-password separation,
  suspension/deletion/reactivation, local administrator blocks, expired credentials,
  provider policy changes/removal, stale proofs, encrypted hints, callback replay,
  share suspension and simultaneous account-event writers with independent connections.
- Signed OIDC tests reject invalid issuer/audience/expiry/issue time, missing logout
  events, nonce-bearing tokens, unsafe headers and invalid signatures.
- Real Keycloak and shipped website adapter: eight provider/layout cases pass in
  the checkout and packaged application. Both local and provider logout are tested;
  a second browser session sharing the upstream session is revoked by the real
  Keycloak backchannel or website event. DAV PROPFIND/PUT/GET/DELETE runs over HTTPS,
  remains usable after logout, and rejects signed account suspension immediately.
- Native ZIP routing: root/subdirectory, each with rewritten and explicit-index URLs,
  all four pass. Tests verify current-password confirmation and regenerated CSRF headers.
- Both ZIP and TAR.GZ pass the packaged translation gate without source translations.
- Docker image smoke: all six tests pass; non-root runtime, health, login/logout,
  settings step-up, file editing/history and administration remain functional.
- Composer/NPM audits: no advisories. Copied browser asset inventory: 43 assets,
  seven package records. Trivy: zero HIGH/CRITICAL findings, including unfiltered
  findings without the `--ignore-unfixed` gate option.
- CSP source scan: no unsafe inline scripts/styles, handlers or style attributes.
  Browser CSP/page-error checks pass. Desktop and 390px profile captures inspected;
  lifetime, local DAV username and per-credential expiry remain readable.
- Isolated browser contexts, fixture servers/containers and Docker smoke volumes are
  closed on completion. A fixture proxy crash during development was cleaned up
  explicitly, then rerun successfully.

## Validation artifacts

Logs and captures are retained locally under `build/external-auth/`.
The production application tree matches the archived application tree.

| Artifact | SHA-256 |
| --- | --- |
| ZIP | `cb753f4708ae71074a3df3bf3edd9235b4d10a64817e1d625c86611639d67f6f` |
| TAR.GZ | `2108ef2957c74c6a42b5da56e4a351f587039272af6049c3162e9567f03614cd` |
| Docker image ID | `d4d185b8e57dbd445721ead8c1868def58e419b6a51dfc924df1dbdffbe41444` |

The archives were built in `build/external-auth/package-source/` with production
Composer dependencies. Browser verification also runs against the extracted ZIP
under `build/external-auth/extracted-final/`; no checkout application code is loaded.

## Operator handoff

Capabilities default to disabled. Enable provider logout/backchannel/WebDAV per
provider, register exact callback/event URLs including installation/index prefixes,
and deploy the website/IdP lifecycle bridge where account events are required.
Use a separate events key and a durable event outbox. Without lifecycle events,
external DAV remains bounded by expiry and local revocation, rather than immediate
upstream deprovisioning. Run readiness on every configuration deployment and schedule
`php spark auth:cleanup` hourly. Existing external sessions must sign in again.
See `docs/authentication.md` and the shipped examples for configuration and protocol details.
