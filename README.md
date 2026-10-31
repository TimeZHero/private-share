# Private Share

[![linter](https://github.com/TimeZHero/private-share/actions/workflows/lint.yml/badge.svg)](https://github.com/TimeZHero/private-share/actions/workflows/lint.yml)
[![tests](https://github.com/TimeZHero/private-share/actions/workflows/tests.yml/badge.svg)](https://github.com/TimeZHero/private-share/actions/workflows/tests.yml)

A self-hosted secret sharing app with **end-to-end encryption**. Share notes, passwords, or sensitive data securely. The encryption key never leaves your browser.

Built with Laravel and Tailwind CSS.

## Features

- **End-to-End Encryption** — Content is encrypted in the browser; the server only stores ciphertext
- **Burn After Reading** — Secrets are automatically deleted once viewed
- **Auto-Expiration** — Unread secrets expire after 30 days
- **Password Protection** — Optional additional password layer
- **Confirmation Required** — Recipients always confirm with a click before a secret is revealed
- **Markdown Support** — Write and preview formatted content

## How It Works

1. You write a secret and click "Share"
2. The browser encrypts the content and sends only ciphertext to the server
3. A link is generated with the decryption key in the URL fragment (`#key`)
4. The recipient opens the link; decryption happens entirely in their browser
5. After viewing, the secret is permanently deleted from the server

> The `#fragment` is never sent to the server. Only the recipient's browser can decrypt the content.

## Self-Hosting

This project is **not a service**. Fork it and deploy your own instance for maximum trust.
This repository does not address deployment and the docker files are intended for development only.

### Development

```bash
git clone git@github.com:TimeZHero/private-share.git
cd private-share
composer install
sail up -d
```

### Testing

```bash
sail artisan test
```

### Branding

Customize branding via environment variables or `config/branding.php`:

### Going Live

Before exposing an instance, check:

- **Trusted proxies.** Rate limiting (including password brute-force protection) is keyed on the client IP. Outside `APP_ENV=local`, `X-Forwarded-*` headers are ignored unless `TRUSTED_PROXIES` lists the reverse proxies in front of the app:
    - Nothing in front of the app: leave it empty.
    - Reverse proxy on a private network (nginx, Traefik, Docker networks, a load balancer in your VPC): `TRUSTED_PROXIES=PRIVATE_SUBNETS`, or the exact IPs/CIDRs.
    - Public CDN (Cloudflare, CloudFront…): list the CDN's published IP ranges plus any proxy hops behind it.

    If it is wrong, every visitor appears to come from the proxy and shares one rate-limit bucket. Never use `*`: it lets any client pick its own IP through `X-Forwarded-For` and bypass the limits.

- **Environment.** `APP_URL` set to the exact public URL (scheme, host and port): with authentication enabled, the API only accepts the browser's session from that origin, so a mismatch makes sharing fail with 401. `APP_ENV=production`, `APP_DEBUG=false`, a fresh `APP_KEY`. Session cookies are `Secure` by default outside `APP_ENV=local`; leave `SESSION_SECURE_COOKIE` unset.
- **HTTPS.** The app does not enforce HTTPS itself. Terminate TLS in front of it (reverse proxy, load balancer or CDN), redirect HTTP to HTTPS there and send `Strict-Transport-Security` (for example `max-age=31536000; includeSubDomains`). Secure cookies mean signing in does not work over plain HTTP anyway, but links and secrets would still travel in clear text.
- **File storage (`FILE_DISK`).** Files are encrypted in the browser, but storage must stay private all the same: a readable bucket or directory exposes every stored file and its size to anyone.
    - `local` (default): files live in `storage/app/private`, outside the web root, and are only served through the app. Do not symlink or expose that directory, and keep it on a persistent volume.
    - `s3` driver (AWS S3 or any S3-compatible store such as MinIO, Cloudflare R2 or DigitalOcean Spaces): keep the bucket private with public access blocked (no public ACLs or bucket policy), give the app credentials limited to reading, writing and deleting objects under `shared-files/` in that one bucket, and allow `GET` from your `APP_URL` origin in the bucket's CORS rules, because browsers download directly from the bucket through 5-minute signed URLs. Optionally add a lifecycle rule that expires `shared-files/` after 30 days, as a backstop for objects whose delayed delete job never ran.
- **Content Security Policy.** The app sends a strict CSP: scripts need a per-request nonce, and pages may only load images, fonts and data from the app itself, Bunny Fonts, Google avatars (with sign-in enabled) and, when `FILE_DISK` uses the `s3` driver, that bucket's endpoint. Injected code therefore cannot run or ship keys elsewhere, and remote images in markdown secrets do not load. If downloads go through another host (a `temporary_url` CDN in front of the bucket, for example), add it to `CSP_CONNECT_SRC`. Security headers are sent by the app (`X-Frame-Options: SAMEORIGIN`, `nosniff`, `Referrer-Policy: no-referrer`, `Permissions-Policy`, CSP); a reverse proxy adding the same values is harmless, conflicting ones are not.
- **Who can sign in** (when `FEATURE_AUTH=true`). List the Google Workspace domains in `AUTH_ALLOWED_DOMAINS` (e.g. `example.com,partner.io`), matched exactly. Only verified accounts managed by one of those Workspaces get in. An empty list means nobody can sign in.

    > **Upgrading:** `AUTH_ALLOWED_EMAILS` (regex patterns) is no longer supported. While it is still set, sign-in stays closed; move your domains to `AUTH_ALLOWED_DOMAINS` and remove it in the same deploy.

- **Background processes.** Run the scheduler (`php artisan schedule:run` every minute) and a queue worker: expired secrets and downloaded cloud files are only deleted by them.

## License

MIT
