# Private Share

A self-hosted secret sharing app with **end-to-end encryption**. Share notes, passwords, or sensitive data securely. The encryption key never leaves your browser.

Built with Laravel, Folio, and Tailwind CSS.

## Features

- **End-to-End Encryption** — Content is encrypted in the browser; the server only stores ciphertext
- **Burn After Reading** — Secrets are automatically deleted once viewed
- **Auto-Expiration** — Unread secrets expire after 30 days
- **Password Protection** — Optional additional password layer
- **Confirmation Required** — Optional "reveal" confirmation before decryption
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

### Quick Start

```bash
git clone git@github.com:TimeZHero/private-share.git
cd private-share
composer install
sail up -d
```

### Customization

Customize branding via environment variables or `config/branding.php`:

```env
APP_NAME="My Secret Share"
BRANDING_PRIMARY_COLOR=blue
BRANDING_SECONDARY_COLOR=indigo
BRANDING_TAGLINE="Share secrets securely"
BRANDING_LOGO_TYPE=image
BRANDING_LOGO_IMAGE="my-logo.svg"
```

## License

MIT
