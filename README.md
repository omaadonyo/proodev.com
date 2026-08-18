# ProoDev — Proof over claims

> Evidence-backed engineering identity. Paste a repository, article, or project URL — ProoDev reads the real work, analyzes it with AI, and turns it into an explainable **Engineering Magnitude** score that cannot be faked.

ProoDev is a developer-intelligence platform that replaces self-reported resumes with verified evidence. Engineers connect actual work (repositories, shipped projects, published articles), AI produces engineering reports, and every point of the score ties back to inspectable proof. A public passport makes that proof shareable — and a hiring side built on the same evidence lets companies and recruiters search, match, and compare verified engineers instead of scanning blind resumes.

## Features

**For engineers**
- **Evidence library** — add GitHub, GitLab, Bitbucket, npm/Packagist, articles, talks, and project URLs; AI classifies, fetches, and drafts an engineering report for each source.
- **Engineering Magnitude** — an explainable 0–1000 score across evidence quality, technical depth, knowledge sharing, breadth, consistency, community trust, verification, and open-source contribution.
- **Public passport** — a shareable, verified identity (public by default, private on demand) with short share links.
- **Projects & journal** — publish detailed project write-ups (problem, solution, architecture, decisions, lessons) and weekly engineering journal entries as evidence.
- **Live feed & presence** — a realtime stream of evidence activity across the community.
- **Vouches & verification** — peer endorsements and confirmed roles/contributions feed the trust signal.
- **Realtime chat** — direct messaging between engineers with typing indicators and presence via Reverb.

**For companies & recruiters**
- **Evidence search** — filter verified engineers by skill and evidence relevance, not keywords.
- **AI match analysis** — score open roles against a candidate's evidence-backed profile and focus on real fits.
- **Talent pools, comparison & interviews** — save shortlists, compare candidates side-by-side, and schedule interviews.
- **Jobs & applicants** — post roles (with AI-drafted descriptions), manage applications in a pipeline.

**Platform**
- **News & announcements** — public news feed with article views and an SEO sitemap.
- **Billing** — payments with printable invoices/receipts, credits, and a featured sales ledger.
- **Admin suite** — analytics, users, verifications, reports, plagiarism strikes, payments, subscriptions, AI settings, ads/sponsors, and a one-click system reset that rebuilds a clean 50-engineer demo.
- **Authentication** — Fortify-powered login/registration, social login (Socialite), passkeys, and two-factor authentication.
- **Feature flags** — product features toggled at runtime via Laravel Pennant.

## Tech stack

- **Backend** — PHP 8.3+ / [Laravel 13](https://laravel.com)
- **Frontend** — [Livewire 4](https://livewire.laravel.com) single-file components, [Flux UI](https://fluxui.dev), Tailwind CSS 4, Vite
- **Realtime** — [Laravel Reverb](https://reverb.laravel.com) + Laravel Echo; Wirechat for messaging
- **Auth** — Laravel Fortify (passkeys, 2FA, social via Socialite)
- **Other** — Pennant (feature flags), DomPDF (invoices), maatwebsite/excel (exports), Pest (testing)

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ / npm
- SQLite (default), MySQL, or PostgreSQL
- Redis (recommended for queues/cache/broadcasting)

## Installation

```bash
# 1. Install PHP + JS dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database — defaults to SQLite
touch database/database.sqlite
php artisan migrate --force

# 4. Seed a working demo (admin account, news, sample content)
php artisan db:seed --force

# 5. Build assets
npm run build
```

> The seeder creates a stable admin account (`adonyo@proodev.com`) and a demo engineer. The admin system reset (`/admin/system`) rebuilds a clean state with 50 realistic engineers from around the world.

## Running locally

```bash
composer run dev
```

This starts the web server, queue worker, and Vite dev server together. Open the `APP_URL` printed in the terminal.

For realtime features (feed presence, chat, broadcast notifications), configure the `REVERB_*` and `BROADCAST_CONNECTION=reverb` values in `.env` and run `php artisan reverb:start`.

## Realtime broadcasting

See `config/broadcasting.php` and the Echo setup in `resources/js/app.js`. Events are broadcast on private/presence channels and listened for via Laravel Echo.

## Testing

```bash
composer test
```

Runs Pint (lint), PHPStan (types), and the Pest test suite. Feature tests cover admin, evidence, projects, journal, vouches, news, billing, notifications, chat, and more.

```bash
php artisan test --filter=NewsTest
php artisan test --filter=NotificationsBellTest
```

## Code quality

```bash
composer lint:check   # Pint style check
composer types:check  # PHPStan analysis
```

## License

MIT. Built by [Aletheia Uganda Software Company Limited](https://proodev.com).
