<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

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

---

## SaaS Multi-Tenancy & Concurrency Locking Strategy

### 1. Row-Level Tenant Isolation
We implement zero-tolerance tenant isolation using:
- **Global Scope Scoping:** The `TenantScope` class automatically appends a `WHERE company_id = auth()->user()->company_id` filter on all queries targetting tenant-scoped models (`Project`, `Task`, `AuditLog`).
- **Unified Resolution:** The tenant context is strictly resolved from the authenticated user context (`auth()->user()->company_id`) in `BelongsToTenant` during model creation, avoiding unsafe URL or request payload resolver hacks.
- **Nested Resource Route Safety:** `TaskController` manually verifies that nested task resources belong to the mapped project (`$task->project_id === $project->id`) to prevent unauthorized cross-project data tampering.

### 2. Concurrency & Race Condition Handling
To prevent race conditions during concurrent modifications (especially when multiple members or admins edit status or assignees):
- **Database Transactions:** All multi-row updates and dependent operations (e.g. creating/updating tasks and triggering async queued jobs) are wrapped in `DB::transaction()`.
- **Pessimistic Locking:** In `TaskController@update`, we retrieve the task model inside the transaction using `Task::lockForUpdate()->findOrFail($id)`. This blocks concurrent read-for-updates/writes on that specific database row until the active transaction commits, ensuring state updates remain sequential and correct.

### 3. Isolated Audit Trail
Data modifications (`created`, `updated`, `deleted`) on `Project` and `Task` are automatically logged via the `Auditable` trait. Since the `AuditLog` model is hooked up to `BelongsToTenant`, audit trail queries are securely restricted to the user's company context.

