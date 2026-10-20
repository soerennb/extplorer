# Release v3.1.3 validation

This corrective security release includes the complete
[3.1.2 hardening and validation](release-3.1.2.md) and
[security review](security-2026-10-20.md). Tracking: Beads `ex3-45y.6` and
`ex3-45y.9`. The immutable tag and release body identify the final source,
published ZIP checksum, multiarch manifest and retained verification evidence.

## SMTP IPv6 regression

A real local socket check found that PHP cannot connect to bare `::1` through
`fsockopen`; `[::1]` connects. The 3.1.2 validator accepted the former and
rejected the latter. Publication cancellation raced with successful 3.1.2
publication; that version's immutable artifacts were preserved.

Version 3.1.3 accepts bare and bracketed IPv6 literals, stores a canonical bare
address and adds socket brackets only to the effective SMTP transport. Equivalent
addresses have identical delivery fingerprints. Bracketed hostnames, wrappers
and host/port overrides remain rejected. A disposable IPv6 loopback SMTP server
now verifies actual delivery through EmailService in the normal PHP test matrix.

## Verification

- PHP 8.5.11: 384 tests, 1,628 assertions, nine environment-dependent skips;
  PHPStan 2.3.1: no errors. Mail security: 27 tests, 87 assertions, including
  actual IPv6 delivery and canonical fingerprints.
- All earlier security boundary, delegation, remote, routing and packaging
  regressions remain in the suite. The Release workflow checks the actual final
  ZIP in all four native URL layouts and exercises upgrade/rollback from the
  published 3.1.1 ZIP. Both actual architecture images require runtime/browser
  checks and full vulnerability reports before publication.
- The local ZIP and TAR.GZ passed packaged-login translation checks for all
  four locales. The final local ZIP passed root/subdirectory routing, both
  rewritten and explicit `index.php`. Native and Docker 3.1.1 upgrade, restart,
  code rollback and backup restoration passed all 14 phases (514 assertions).
- The full local AMD64 image scan reported zero vulnerabilities at all severities,
  including unfixed findings, and zero secrets. The report's image ID matches
  the actual image below.
- Tool versions are unchanged from the 3.1.2 validation environment.
- No production application, state or credentials are used. Tests contact only
  isolated fixtures, loopback endpoints, dependency registries and release APIs.

| Local candidate | SHA-256 |
| --- | --- |
| ZIP | `d8939d8fc980abcdceaa7214c858cbf2f89d5dfc9a4edca3ac236404dee8d185` |
| TAR.GZ | `7957751d0cd13a0488dfd21bc18d4124247e6361c180202d2ccdfb822e53c86a` |
| AMD64 image | `f772b9ef5c854a3c89554cf711f660312830d9522ba38954951cd3a44bf558e6` |

Source: `36d4698ae080b127a2a8f2577b1474ed7fa0c7e4`.
All exact-source checks passed:
[Quality](https://github.com/soerennb/extplorer/actions/runs/39391425555),
[Security audit](https://github.com/soerennb/extplorer/actions/runs/39391425924),
[Secret scanning](https://github.com/soerennb/extplorer/actions/runs/39391425788).
Quality includes actual SMTP IPv6 delivery across PHP 8.2–8.5, all database and
web-server integrations, real Sendmail/remote fixtures and six Chromium tests.
The [Release run](https://github.com/soerennb/extplorer/actions/runs/39391924657)
reuses those successful exact-SHA checkout gates while independently checking
the final archive and both actual architecture images.

## Published artifacts and final verification

[Version 3.1.3](https://github.com/soerennb/extplorer/releases/tag/v3.1.3)
was published as the latest stable release at 2026-10-20 08:19:42 UTC.
The complete Release run succeeded, including final archive and both image
architecture gates. The immutable tag points to the source SHA above.

| Published artifact | Verified digest |
| --- | --- |
| `eXtplorer-3.1.3-release.zip` | SHA-256 `09aa87f8c8c51472e9dbba71ba1fe61dd7fa839a9e4ab9f162586c4f82acac19` |
| AMD64/ARM64 manifest | `sha256:a147d3075ec3a9ac040918e4ad1a35e1fc5b7b933839bf8b774e063cb4fbb311` |
| AMD64 build/attestation index | `sha256:15ec7ea2e75325c3dd7841ca30ad9e69cd1cd555e49b38de71d1f433597821de` |
| ARM64 build/attestation index | `sha256:cbf6c213a241b2656dd8784265e0e170cfbe159ae3fb2636244501a8aa883eea` |

Both architecture reports contain **zero vulnerabilities** (all severities,
including unfixed findings) and **zero secrets**. Runtime package checks and
all six Chromium tests passed on both native architecture runners. Both images
contain `nghttp2-libs=1.70.0-r0`. CI's pinned Trivy action used 0.70.0; the separate
local full scan used 0.75.0. The final ZIP
passed packaged translations, all four root/subdirectory and index-page routing
layouts and all seven native upgrade/rollback phases from 3.1.1 (257 assertions).

Unauthenticated downloads of the published ZIP and checksum matched the verified
bytes above. Its packaged-login translation gate passed again for all four
locales. Registry inspection confirmed `3.1.3`, `3.1`, `3` and `latest` resolve to
the same verified multiarch digest; the latest GitHub release is neither draft
nor prerelease. The published notes link directly to the inherited 3.1.2 security
and migration requirements. Immutable artifacts were not replaced.

Previous `3`, `3.1` and `latest` digest, retained for rollback:
`sha256:9b8268ecb9035a23621897b436598b769c53581b71f691ce53f9a193385786dd`.
The Release run retains archive/routing/upgrade reports, complete architecture
scans and `publication-state.json` for 30 days. All temporary application test
containers and volumes were removed. No production application was deployed.

## Upgrade

Install 3.1.3 when upgrading from 3.1.1 or earlier; it includes all 3.1.2 security
fixes. Preserve writable data/configuration and the encryption key. Set custom
Sendmail paths in the operator environment and retest saved mail settings.
Delegation restrictions and the strict-mode FTPS refusal remain effective.
See [configuration and migration](../configuration.md).
