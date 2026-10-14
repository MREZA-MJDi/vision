# RM Commerce Admin Kit

Reusable Laravel 12 / Blade admin UI for commerce projects. It provides Persian RTL screens, tables, forms, navigation, charts, CSS/JavaScript interactions, package defaults and configurable money/date formatters.

## Package boundary

**This package owns:** Blade views, UI styles/scripts, configuration defaults, and reusable formatters.

**The host application owns:** admin authentication and authorization; the `commerce-admin.*` named-route contract and controllers; database schema and Eloquent relationships; FormRequests and persistence; inventory/order rules and payment-provider integration.

This separation is deliberate: the same dashboard UI can be reused across store projects while each project wires it to its own models and services. Views submit ordinary Laravel forms; the host must validate and persist through its backend. Dashboard metrics must come from host database queries, not browser storage or hardcoded demo arrays.

## Installation from the current monorepo checkout

This package is currently nested inside the VISION repository. Composer VCS repositories cannot install a subdirectory of a repository as an independent package. For local development while both projects are checked out, configure a Composer path repository in the consumer app:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../vision/packages/RM/CommerceAdmin",
      "options": { "symlink": true }
    }
  ]
}
```

Then:

```bash
composer require rm/commerce-admin-kit:*
php artisan vendor:publish --tag=commerce-admin-config
php artisan vendor:publish --tag=commerce-admin-assets
```

For direct `composer require` from GitHub on a separate machine/server, the package still must be extracted to its own repository (with this `composer.json` at repository root) or published to a Composer registry. The current nested directory alone is not a standalone Git VCS install target.

## Assets

Composer consumers default to published assets under `public/vendor/commerce-admin`. Re-publish after package updates. In this VISION monorepo, set `COMMERCE_ADMIN_ASSET_MODE=vite`; Vite already includes the package CSS/JS entries.

## Host integration contract

The host must define the named `commerce-admin.*` routes used by the views and provide the records/collections each screen expects. Configure `models`, `admin_flag_column`, `currency_symbol`, `formatters`, pagination, and media directory settings in `config/commerce-admin.php`.

Product screens rely on relationships such as variants/gallery media/category/brand/approved reviews. Category covers require a polymorphic `media()` relationship and a `coverMedia()` relationship. Keep authorization, business-specific validation, database transactions and payment workflows in the host application.
