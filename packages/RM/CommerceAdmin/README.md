# Commerce Admin UI Resources

This directory is the **presentation layer only** for VISION's store administrator.

- `resources/views`: Blade screens and shared layout
- `resources/css/admin-kit.css`: admin design system, IRANYekanX and RTL rules
- `resources/js/admin-kit.js`: navigation and view interactions

Application behavior lives in `app/Http/Controllers/Admin`, `app/Http/Middleware`, `app/Console/Commands`, `app/Services`, and `routes/commerce-admin.php`. Database migrations and runtime configuration stay in the normal Laravel application directories.

The application provider `App\Providers\CommerceAdminViewServiceProvider` exposes these templates as the `commerce-admin::` view namespace. Vite includes the styles and scripts from this directory.
