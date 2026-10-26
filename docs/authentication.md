# External authentication

eXtplorer can use an existing website's login or an OpenID Connect (OIDC)
identity provider. Authentication remains separate from file authorization:
external identities receive persistent local accounts with the existing
roles, groups, home directories and authorization-version checks.

External authentication is disabled until the server operator sets
`EXTPLORER_AUTH_CONFIG_FILE` to an absolute path to a JSON configuration.
The configuration, extension code and secret files must be outside `public/`
and every application-managed storage directory. Keep them operator-owned,
read-only for the PHP runtime, and outside all local-mount allowlists.
These capabilities cannot be configured through the application settings UI.

## OpenID Connect

Start with `examples/auth/auth.json`. Register a confidential OIDC client
with authorization code flow, PKCE S256 and RS256 signed ID tokens.
Configure the exact callback URL shown below (including installation path
and `index.php`, when configured):

```text
https://files.example.com/extplorer/index.php/login/external/company/callback
```

Set `issuer` to the exact provider issuer, `client_id` to the registered
client ID, and `client_secret_file` to a readable secret file. The client
uses `client_secret_basic`; no access or refresh tokens are stored.
Discovery, token and JWKS requests all use the restricted HTTPS transport.
Automatic HTTP redirects and environment proxies are disabled; DNS results
are checked and pinned while TLS still verifies the configured hostname.
Responses are limited to 1 MiB and requests to 15 seconds.

The issuer's HTTPS origin is permitted by default. Additional token, key or
authorization endpoint origins require explicit `allowed_origins`, for
example `["https://keys.example.com"]`. For an intentionally private IdP,
set `allow_private_targets: true`; this permits private addresses only for
the already configured origins. It never disables certificate verification.
Trust your private CA through the PHP runtime's standard CA configuration.

`group_claims` lists ID-token claim paths, for example `groups` and
`realm_access.roles` for Keycloak. Each present claim must be a list of
strings. Request/configure those claims in the provider's ID-token mapper;
the application does not fetch userinfo or depend on unverified profile data.
For a required authentication assurance policy, set `acr_values` to the
space-separated permitted ACR values. The returned `acr` must match one.
MFA enforcement belongs to the IdP; a normal SSO login alone does not prove MFA.

## Admission, provisioning and account management

Every provider requires an existing `base_role` and a nonempty `group_map`.
Keys are external group/role values; values are lists of existing eXtplorer
groups. A user must match at least one key to be admitted. An empty target
list grants the base role only:

```json
{
    "base_role": "user",
    "group_map": {
        "extplorer-users": [],
        "file-editors": ["Editors"]
    }
}
```

Create the role and target groups before enabling the provider. Use a
restricted base role when the built-in `user` role is too broad. The operator
may deliberately map administrative privileges; the provider cannot inject
arbitrary permissions, role names or home paths through a browser request.

The identity key is `(provider ID, issuer, subject)`. Its local username is
an opaque, path-safe identifier. Matching names or emails never link an
existing account. Keep provider IDs and issuer/subject values stable.
Each first login gets a distinct Home under `/<home_prefix>/<local username>`;
`home_prefix` defaults to `external`. The normal common Shared mount remains
common storage. Administrators can adjust local home and file-extension
policies, but external role/group assignments and credentials are managed
by the provider. Rights synchronize at every successful login. Changes bump
`auth_version`, invalidating earlier sessions rather than upgrading them.

Block an account in Users to revoke local access immediately. Synchronization
never re-enables a blocked account. Deleting an account does not deny a
still-admitted external identity: its next login provisions a new account;
use blocking for persistent denial. Keep a local administrator for recovery.
Existing accounts remain local without a migration or password conversion.

## Custom website adapter

Implement `ExternalAuthProviderInterface` in a class in the `ExtplorerAuth`
namespace. Set `extension_path` to its operator-owned directory and configure
the simple class name. The constructor receives that provider's settings.
The core loads no code from writable application data.

```json
{
    "extension_path": "/etc/extplorer-auth/providers",
    "providers": {
        "website": {
            "type": "custom",
            "class": "WebsiteProvider",
            "label": "My website",
            "issuer": "https://www.example.com",
            "login_url": "https://www.example.com/extplorer-bridge.php",
            "signing_key_file": "/etc/extplorer-auth/website-key",
            "supports_reauthentication": true,
            "base_role": "user",
            "group_map": {"file-manager-users": []}
        }
    }
}
```

`begin(request, context)` returns a verified `ExternalIdentity` immediately
when the adapter can securely validate the website's existing session, or
an HTTPS URL for a browser round trip. `complete(request, context)` verifies
the external proof and returns the identity. Never trust a request username,
unsigned headers, or an unvalidated cookie; never write eXtplorer's session
fields or replace its PHP session handler. Local privileges come from the
configured mapping, not from an identity object's arbitrary session data.

For a separate website, copy `examples/auth/WebsiteProvider.php` to the
extension directory and adapt `examples/auth/website-bridge.php` to the
website's real authentication API. The template deliberately requires your
own `requireSignedInUser()` and `requireFreshLogin()` functions. Register the
exact callback in that bridge; do not accept arbitrary callback URLs. Share
a random key of at least 32 bytes through secret files. The example signs a
60-second assertion bound to issuer, callback audience, state and nonce.
Keep bridge/callback query strings out of access logs and analytics; they
contain short-lived assertions and identity data. No website password is
sent to eXtplorer. The core consumes the session-bound transaction once,
including failure paths.

Only advertise `supportsReauthentication()` when the adapter actually
forces a fresh website authentication. Return its real `authenticatedAt`;
do not simply use the current time for an already logged-in session.

## Browser sessions, step-up and logout

The login page shows configured providers alongside local sign-in.
An optional top-level `default_provider` automatically starts that provider.
`/login?local=1` always provides local recovery access. Explicit logout and
expired-session prompts suppress automatic redirection.

`session_ttl_seconds` defaults to 3600 and may be 60–86400. External browser
sessions have this absolute maximum in addition to the ordinary idle limit.
They do not issue remember-me or trusted-device cookies. Removing a provider
or changing its configuration invalidates its existing browser sessions.

Sensitive operations use a separate authentication window and preserve the
original page's draft. OIDC requests `prompt=login&max_age=0`; the core checks
fresh `auth_time` and requires the same provider identity, current account
and authorization version. Custom adapters without this capability cannot
authorize sensitive operations. Transactions expire after five minutes.
The original window exchanges its memory-only proof for the normal
ten-minute grant. Grant tokens never enter URLs, cookies or browser storage.
Public provider start/callback routes share a limit of 20 requests per minute
per IP before provider/network work; step-up creation also keeps its existing
account/IP limit. Rate-limit responses use HTTP 429 with `Retry-After`.

Local logout ends the current eXtplorer session. An optional, separately confirmed
provider logout also ends the upstream login and may affect other applications.
Enable `rp_logout_enabled: true` per provider. OIDC uses its discovered
`end_session_endpoint`; register the exact `login/external-logout/callback` URL
as a post-logout redirect. The local session is destroyed before discovery or
provider navigation, including on failure. The return transaction is single-use,
browser-bound and expires after five minutes. Verified ID-token hints are
stored encrypted only when this option is enabled, submitted by POST, and never
placed in URLs. Access and refresh tokens are never persisted.

Custom adapters can optionally implement `ExternalLogoutProviderInterface`
without changing their login interface. `logoutRequest()` returns an HTTPS
`LogoutRequest` with POST parameters; the core renders a CSP-restricted handoff.
See `examples/auth/WebsiteProvider.php` and `website-logout.php`. The website
must receive its login cookie on the cross-site POST (`SameSite=None; Secure`
when the applications use different sites), validate the signed request, and
clear its own login. Include a stable, opaque `sid` in the signed login assertion
if session-specific server revocation is needed.

### Upstream logout and lifecycle events

Enable `backchannel_logout_enabled: true` for OIDC and register the exact
`auth/oidc/<provider>/backchannel-logout` URL with the IdP. Send form-encoded
`logout_token` JWTs signed with RS256. Issuer, client audience, signature, fresh
`iat` (five minutes), `exp`, `jti`, logout event and absence of `nonce` are
validated. Token lifetime is capped at one hour. Only configured HTTPS metadata
and JWKS origins are used. A `sid` revokes that upstream session; `sub` without
`sid` revokes all sessions for the identity. See the
[OIDC backchannel specification](https://openid.net/specs/openid-connect-backchannel-1_0.html).
Logout alone leaves accounts, shares and app-passwords active.

For website providers and OIDC lifecycle bridges, configure a separate
`events_secret_file` with at least 32 random bytes and POST JSON to the exact
`auth/events/<provider>` URL. Use HTTPS. An example full account snapshot is:

```json
{
  "iss": "https://www.example.com", "aud": "https://files.example.com/auth/events/website",
  "provider": "website", "id": "unique-event-id", "iat": 1793000000,
  "type": "account.updated", "revision": 42, "sub": "immutable-user-id",
  "status": "suspended", "groups": []
}
```

Set `X-Extplorer-Event-Time` to the decimal `iat` and
`X-Extplorer-Event-Signature` to `v1=` plus hexadecimal HMAC-SHA256 of
`timestamp + "." + raw JSON body`. The clock tolerance is five minutes.
`examples/auth/send-event.php` demonstrates delivery. Use a durable outbox;
retry account snapshots with the same event ID and revision, refreshing `iat`
and signature. Retry session-revocation events only within their original
five-minute timestamp window; do not re-date an old logout as a new logout.
`revision` must increase per immutable identity and snapshots must include the
complete group list. Older revisions and duplicate deliveries are harmless.
The sender must securely source account status/groups; eXtplorer does not poll
the provider. OIDC account suspension/group changes therefore require a bridge
that translates the provider's administrative events into these signed snapshots.

`account.updated` updates provider-managed roles/groups, revokes browser sessions,
step-up grants and app-passwords, and blocks public shares while suspended or
not admitted by the group map. Only a later explicit `active` snapshot clears
external suspension; a local administrator's disabled flag remains effective.
Shares retain their own expiry and resume under current rights after reactivation.
Revoked app-passwords never resume. Identity tombstones survive account deletion;
a login started before a newer lifecycle event cannot overwrite it.

A `session.revoked` event uses the same envelope, with `sub` and/or `sid`, and
omits revision/status/groups. It has the same session-only effect as OIDC logout.
Only these two exact POST event routes bypass browser CSRF and login checks;
they require their own cryptographic authentication, limit bodies to 64 KiB and
allow 120 requests per minute per provider/IP. Other browser routes retain CSRF.

Sessions and replay/transaction records live in shared `external_auth` state,
independently of the PHP session handler. File installations must share the state
volume; SQL installations must use the same state database. Keep encryption keys
consistent across replicas. Schedule `php spark auth:cleanup` hourly to remove
expired sessions, replay records and transactions. Permanent identity tombstones
are retained. Existing external browser sessions from before this upgrade must
sign in again.

### WebDAV for external accounts

External WebDAV is disabled by default. Set `webdav_enabled: true` per provider
to allow fresh provider step-up to issue app-passwords in the profile's security
tab. `webdav_ttl_seconds` defaults to 2592000 (30 days), may be shortened to at
least 60 seconds, and cannot exceed 30 days. Credentials cannot be renewed;
create a replacement with another step-up. The UI shows the actual local
`ext_…` WebDAV username and each credential's expiry. Never use the website/IdP
password in a DAV client. HTTPS remains required for DAV.

An event source is recommended but is not required to enable WebDAV. Without
one, upstream account changes are learned at the next provider login, and an
already issued credential may remain valid until its bounded expiry or local
revocation. Every DAV request checks current local rights, status, identity,
authorization version, expiry and provider policy. Suspension, rights changes,
provider configuration changes and provider removal permanently invalidate old
credentials. Run `system:readiness` on every deployed configuration change,
including removal, before serving traffic; it records the change even when no
DAV request observes the intermediate configuration. Local and provider logout
do not revoke app-passwords. Local-account DAV behavior is unchanged.

## Deployment and verification

Native deployments set `EXTPLORER_AUTH_CONFIG_FILE` in the server environment
or `.env`. In Docker, use `docker-compose.auth.yml.example` as an additional
Compose file. Mount the complete configuration/extension/secret directory
read-only and grant the non-root PHP user read access (uid 82 in the Alpine
image). JSON secret paths are not rewritten by the root secret-staging
wrapper. Example files ship in archives and at
`/usr/local/share/extplorer-auth-examples` in the image.

`system:readiness` checks configuration, mappings, extension interfaces and
secret readability without making the IdP a startup dependency. Restart or
replace PHP workers when changing deployed extension code.

Run `composer test`, `composer static:phpstan`, `npm run test:frontend`,
`composer i18n:build`, and `composer i18n:check`. The external-auth browser
test uses an isolated Keycloak container plus an example website and covers
the four native installation layouts, including provider logout, signed suspension,
CSRF headers, and DAV PROPFIND/PUT/GET/DELETE over a real TLS proxy:

```sh
node tests/e2e/external-auth.cjs
```

Pass an extracted archive directory as its first argument to verify the
packaged application instead of the checkout. See also the existing native
archive routing gate in `tests/e2e/README.md`.
