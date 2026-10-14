# Commerce Admin UI Kit

Presentation-only Laravel package for the VISION Commerce Admin interface.

## What's inside

- Namespaced Blade views under `commerce-admin::`
- `admin-kit.css` for the responsive admin layout and components
- `admin-kit.js` for the UI interactions
- A small service provider that registers the views and publishes the static assets

## What stays in the host application

Routes, controllers, authentication, admin authorization, Eloquent models, business workflows, configuration and database migrations belong to the Laravel application. The UI kit must not own or run those concerns.

The host application is responsible for defining the named routes consumed by the views: `commerce-admin.login`, `commerce-admin.login.store`, `commerce-admin.logout`, `commerce-admin.dashboard`, `commerce-admin.analytics`, `commerce-admin.analytics.export`, plus the `commerce-admin.products.*`, `commerce-admin.orders.*` and `commerce-admin.reviews.*` route families.

The views use the host application's `commerce-admin.*` configuration for display labels, currency and the authentication guard. Keep that configuration in the host application.

## Publish assets

After registering the provider, publish the CSS and JavaScript to the application's public directory:

```bash
php artisan vendor:publish --tag=commerce-admin-assets --force
```

This publishes:

- `public/vendor/commerce-admin/admin-kit.css`
- `public/vendor/commerce-admin/admin-kit.js`

The views reference these stable public URLs; no Vite entry specific to the package's source path is required in the consuming application.

## Monorepo conventions

In this repository the package source lives under `packages/RM/CommerceAdmin`; the runtime routes, controllers, middleware, command, configuration and migrations live under the normal application directories. The package provider is deliberately limited to view registration and asset publishing.
