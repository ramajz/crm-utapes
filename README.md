<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## CRM-Utapes

CRM lead management untuk Utapes berbasis Laravel, Livewire, Alpine.js, dan Tailwind CSS.

### Current Features

- Lead and customer management with follow-up status, funnel stage, notes, and audit history.
- Scalev webhook sync for orders, payments, customers, and leads.
- Automatic lead assignment to active CS handlers.
- Assignment strategies: `least_loaded` (default) and `round_robin`.
- **Wajib Follow-Up**: Manager marks priority leads; CS sees them in a dedicated menu and marks them done.
- **Bulk Reassign CS**: Manager moves multiple leads to another CS at once.
- **Branches**: Lumajang & Kediri with NocoBase mapping (`branch_id` on leads, orders, handlers).
- **WhatsApp Message Templates**: Predefined templates with dynamic variables (`{nama}`, `{order_id}`, `{size}`, `{total}`, `{handler}`) that open `wa.me` with a pre-filled message.
- Local development with SQLite; production uses PostgreSQL.

### Import Real Data (AppScript)

```bash
php artisan migrate:sheets --looker="App-Utapes - Leads_Jul_2026.csv" --flush
```

- Supports AppScript headers (`Phone (WA)`, `Handler (CS)`, `Financial Status`, etc.)
- Maps CS aliases to canonical names (Lana → Hafiz, Kiki ternyata → Kiki, etc.)
- `--flush` clears old data first; `--dry-run` previews without writing.

### Lead Assignment

Configure optional assignment behavior in `.env`:

```env
LEAD_AUTO_ASSIGN=true
LEAD_ASSIGN_STRATEGY=least_loaded
```

To preview or assign existing leads without a handler:

```bash
php artisan leads:assign-unassigned --dry-run
php artisan leads:assign-unassigned
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
