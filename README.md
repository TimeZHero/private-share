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

- **Environment.** `APP_URL` set to the exact public URL (scheme, host and port): with authentication enabled, the API only accepts the browser's session from that origin, so a mismatch makes sharing fail with 401. `APP_ENV=production`, `APP_DEBUG=false`, a fresh `APP_KEY`, `SESSION_SECURE_COOKIE=true`, HTTPS with HSTS at the edge.
- **Content Security Policy.** The app only lets its scripts connect to itself and to the S3 bucket configured as `FILE_DISK`, so injected code cannot ship keys elsewhere. If downloads go through another host (a `temporary_url` CDN in front of the bucket, for example), add it to `CSP_CONNECT_SRC`. Headers are sent by the app (`X-Frame-Options: SAMEORIGIN`, `nosniff`, CSP); a reverse proxy adding the same values is harmless, conflicting ones are not.
- **Who can sign in** (when `FEATURE_AUTH=true`). List the Google Workspace domains in `AUTH_ALLOWED_DOMAINS` (e.g. `example.com,partner.io`), matched exactly. Only verified accounts managed by one of those Workspaces get in. An empty list means nobody can sign in.

    > **Upgrading:** `AUTH_ALLOWED_EMAILS` (regex patterns) is no longer supported. While it is still set, sign-in stays closed; move your domains to `AUTH_ALLOWED_DOMAINS` and remove it in the same deploy.

- **Background processes.** Run the scheduler (`php artisan schedule:run` every minute) and a queue worker: expired secrets and downloaded cloud files are only deleted by them.

## License

MIT
