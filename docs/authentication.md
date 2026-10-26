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

V1 logout destroys the eXtplorer session only. The IdP/website may still have
its own login session, so the next explicit SSO login can be immediate.
There is no central or backchannel logout. External group changes and
upstream account disabling are discovered on the next provider login;
local account blocking is immediate. Public shares retain their own
lifetime and use current local owner permissions; they are not bounded by
the owner's browser-session expiry. WebDAV is unavailable for external
accounts in this first version. Local account authentication is unchanged.

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
the four native installation layouts:

```sh
node tests/e2e/external-auth.cjs
```

Pass an extracted archive directory as its first argument to verify the
packaged application instead of the checkout. See also the existing native
archive routing gate in `tests/e2e/README.md`.
