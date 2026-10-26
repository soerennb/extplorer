# External authentication validation — 2026-10-26

Implementation: Beads `ex3-0ic` and discussion #75. Initial validation used
base `4e46763`; the feature was rebased onto security update `4b8459b`.
The integrated tree also passes with Node 24.22.1 and the patched Docker image.
This report covers the implementation working tree. Archives use the current
3.1.4 development filename; they are validation artifacts for unreleased code.

## Environment and dependencies

- Host PHP 8.5.11, Docker application PHP 8.5.12, compatibility PHP 8.2.35.
- Composer 2.10.3, Node 24.22.1, PHPUnit 11.5.57.
- `facile-it/php-openid-client` 1.0.0, Nyholm PSR-7 1.8.2,
  Symfony HTTP Client 7.4.21. Twenty added packages; existing locked packages
  retained. HTTP discovery's optional Composer plugin is explicitly disabled.
- Real Keycloak fixture: 26.8.1, image digest
  `sha256:a78d6b79572168cb25886b80a8b5b8d4ed4390743e3eecb810538683e209dd6b`.
  Its test TLS certificate is explicitly trusted by PHP; verification stays enabled.

## Results

- Full PHP suite: 519 tests, 2,179 assertions, 9 service-dependent skips.
- PHP 8.2 compatibility: focused authentication suite passes; the fork-based
  concurrency case is skipped because that image does not provide pcntl.
  File-backed concurrent first-login provisioning is covered on the host.
- PHPStan: no errors, with an accurate vendor stub for Psalm-only OIDC map aliases.
- Frontend: 25 passing tests, including in-flight cancellation of provider
  reauthentication. Release/publication contracts: 20 passing tests.
- Composer locked audit and full NPM audit: no reported advisories.
- Public provider start/callback routes reject throttled requests before provider work.
- Source security rules and their fixtures: no findings. Views have no unsafe
  inline scripts/styles, style attributes or event handlers.
- Translation build/check: four locales, 824 consistent runtime keys.
  Vue assets compile; the copied vendor-asset inventory passes.
- Login desktop/mobile inspection: provider choices remain in the existing
  design; narrow displays hide the side panel and allow the form to fit/scroll.

## Browser and deployment coverage

The external-auth test passes both real Keycloak and the shipped website
adapter against the packaged application. It covers automatic provider
entry, local recovery, distinct provisioned identities, real provider
reauthentication, a protected settings mutation, external credential-change
denial, CSRF refresh headers, logout suppression and clean CSP/browser state.

| Native installation | Existing archive routing gate | OIDC | Website adapter |
| --- | --- | --- | --- |
| Root, rewritten | Passed | Passed | Passed |
| Subdirectory, rewritten | Passed | Passed | Passed |
| Root, `index.php` | Passed | Passed | Passed |
| Subdirectory, `index.php` | Passed | Passed | Passed |

The existing routing gate extracts the final ZIP and removes translation
sources before checking binary upload and password-based step-up. Both final
ZIP and TAR.GZ also pass `scripts/check-release-i18n.php` without sources.

The Docker image builds with production dependencies and includes the custom
adapter examples. Its existing six-test smoke suite passes with a non-root,
read-only application container: local login/logout, installer protection,
password step-up, file editing/version restoration and recursive copying.
Test browser contexts, native servers and owned containers are cleaned up.

## Archive checksums

| Validation archive | SHA-256 |
| --- | --- |
| `extplorer3-3.1.4.zip` | `67b1d8580b8bb584bf193d659e48292d3cfae0fc0d0b962d1f83d0fa0441e965` |
| `extplorer3-3.1.4.tar.gz` | `6b3989ff7ac7663883292dbad97e4ece014dd2002146376b7396f3d2f38375f3` |

## Follow-up boundaries

Central logout/upstream revocation is tracked in `ex3-42y`; external WebDAV
credential lifecycle in `ex3-q4h`. V1 uses bounded browser sessions and
immediate local blocking. Shares retain their independent lifetime and
current local owner-policy checks, as documented in `docs/authentication.md`.
