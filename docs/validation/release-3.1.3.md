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
- Tool versions are unchanged from the 3.1.2 validation environment. Final CI
  links, artifact hashes and architecture reports are recorded after publication.
- No production application, state or credentials are used. Tests contact only
  isolated fixtures, loopback endpoints, dependency registries and release APIs.

## Upgrade

Install 3.1.3 when upgrading from 3.1.1 or earlier; it includes all 3.1.2 security
fixes. Preserve writable data/configuration and the encryption key. Set custom
Sendmail paths in the operator environment and retest saved mail settings.
Delegation restrictions and the strict-mode FTPS refusal remain effective.
See [configuration and migration](../configuration.md).
