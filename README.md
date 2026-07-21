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

## SaaS Multi-Tenancy, Concurrency, and Production Readiness Design

### 1. Multi-Tenancy Strategy & Trade-Offs

We chose **Row-Level Scoping (Shared Database, Shared Schema)** for tenant isolation. Below is an architectural trade-off analysis:

| Criteria | Row-Level Scoping (Chosen) | Database-per-Tenant | Schema-per-Tenant |
| :--- | :--- | :--- | :--- |
| **Data Isolation** | Logical (via `TenantScope` & traits) | Physical (Separate DB instances) | Schema isolation (PostgreSQL schemas) |
| **Operational Overhead** | Extremely Low (Single migration run) | High (Run migrations across $N$ DBs) | Medium (Schema-specific migrations) |
| **Cost Efficiency** | High (Shared compute/storage pool) | Low (Each DB incurs independent cost) | Medium (Shared engine, schema limits) |
| **Noisy Neighbor Mitigation** | Harder (Requires query rate limits) | Absolute (Isolated compute resources) | Harder (Shared DB server resources) |

#### Choice Rationale
For an enterprise-grade project management RESTful API, row-level scoping provides the highest development velocity and cost efficiency. We neutralized data leak concerns using:
- **`TenantScope`:** Transparently appends `WHERE company_id = auth()->user()->company_id` on all model queries.
- **`BelongsToTenant`:** Resolves `company_id` directly from `auth()->user()->company_id` during model creation, avoiding unsafe client-provided request payloads or URL parameters.

---

### 2. Concurrency & Race Condition Handling
To prevent state inconsistency (e.g. multiple users updating a task's status or assignee concurrently):
- **Database Transactions:** All dependent database operations (like creating/updating tasks and executing background jobs) are enclosed inside `DB::transaction()`.
- **Pessimistic Locking (`lockForUpdate`):** In `TaskController@update`, the task row is loaded using `Task::lockForUpdate()->findOrFail($id)`. This blocks concurrent read-for-updates/writes on that specific database row until the current transaction commits.

---

### 3. Isolated Audit Trail
Data modifications (`created`, `updated`, `deleted`) on `Project` and `Task` are recorded via the `Auditable` trait. Since `AuditLog` utilizes the `BelongsToTenant` trait, audit trail queries are strictly restricted to the user's company context, ensuring complete tenant isolation.

---

### 4. Tech Stack Rationale
- **Laravel Framework:** Provides expressive syntax, high developer velocity, and robust modules ecosystem via `nwidart/laravel-modules`.
- **Laravel Sanctum & Fortify:** Standard for headless token-based API authentication and multi-factor/passkey security management.
- **Spatie Laravel Permission:** Configured with `teams => true` and `team_foreign_key => company_id` to dynamically manage company-specific RBAC roles.
- **MySQL/PostgreSQL:** Relational database with full support for composite indexes and transaction ACID compliance.

---

### 5. Scope Trade-Offs & Future Enhancements
Due to time constraints, some enterprise features were deferred:
- **Email Notifications:** The queue job (`TaskAssignedNotificationJob`) logs mail contents to mock logs. Production would connect to SMTP/Mailgun.
- **Caching Layer:** Redis would be utilized to cache tenant-specific tasks/projects list queries.
- **Audit Log UI:** A dedicated management view would be created for Tenant Admins to inspect change logs.

---

### 6. Technical Decisions & Hesitations
- **Spatie Teams Resolution:** Setting the team context (`setPermissionsTeamId()`) manually in every controller is error-prone. We hesitated between custom RBAC checks or Spatie's team features.
- **Resolution:** We resolved this by implementing [TenantTeamMiddleware.php](file:///c:/Users/tmaul/Herd/mini-project-management/app/Http/Middleware/TenantTeamMiddleware.php) which intercept requests after `auth:sanctum` and dynamically configures Spatie's active team context to the user's `company_id` transparently.

---

### 7. Local Setup & Execution Guide

#### Local Quickstart
1. Clone the repository and copy the environment template:
   ```bash
   copy .env.example .env
   ```
2. Install PHP Composer dependencies:
   ```bash
   composer install
   ```
3. Generate the application key:
   ```bash
   php artisan key:generate
   ```
4. Set your database credentials in `.env` (e.g. SQLite, MySQL, or PostgreSQL) and run migrations & seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```
5. Run the async queue worker to process notifications:
   ```bash
   php artisan queue:work
   ```
6. Run the automated test suite:
   ```bash
   php artisan test
   ```

#### Running with Docker
1. Start the services using Docker Compose:
   ```bash
   docker-compose up -d --build
   ```
2. Run migrations inside the PHP app container:
   ```bash
   docker-compose exec app php artisan migrate:fresh --seed
   ```
3. The API will be accessible locally at `http://localhost:8000`.

---

### 8. Submission Details
- **Fixture Seeders:** Configures Default Spatie Roles (`Admin`, `Member`) out-of-the-box.
- **Time Spent:** ~5.5 hours.


