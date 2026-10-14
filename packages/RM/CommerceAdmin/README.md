# RM Commerce Admin Kit

A reusable Laravel 12 / Blade admin presentation kit for commerce projects. The same Persian RTL dashboard, tables, forms, navigation, charts and interaction layer can be reused; each host application keeps its own authorization, persistence, routes and business rules.

## Package boundary

Included here:
- shared Blade screens and RTL design system;
- CSS/JavaScript interactions;
- package defaults and configurable money/date formatters;
- asset publishing for Composer consumers.

Owned by the host application:
- admin authentication and authorization middleware;
- the `commerce-admin.*` named route contract and controller implementations;
- database schema, Eloquent model relationships, FormRequests and commerce services;
- payment-provider integrations, inventory rules, order transitions and business-specific policies.

The views do not fake saves or store dashboard metrics in browser storage. They submit normal Laravel forms; host controllers must validate requests and persist through Eloquent/database transactions. Dashboard metrics must query host application records.

## Install from this monorepo checkout

This package currently lives inside the VISION repository, not at a standalone Git repository root. Composer VCS repositories cannot install a subdirectory of that repository directly. For development while both projects are checked out locally, add a Composer path repository pointing to the package directory:

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

Then run:

```bash
composer require rm/commerce-admin-kit:*
php artisan vendor:publish --tag=commerce-admin-config
php artisan vendor:publish --tag=commerce-admin-assets
```

For independent installation with `composer require` from GitHub on another machine/server, the package still needs to be split into its own repository (with this `composer.json` at repository root) or published to a Composer registry. Do not treat the whole VISION application repository as this package.

## Assets

The installed-package default uses published files at `public/vendor/commerce-admin`. Run the asset publish command after install/update. In a monorepo development checkout, set `COMMERCE_ADMIN_ASSET_MODE=vite` and include the package CSS/JS paths in the host Vite inputs (as the VISION app does).

## Host integration contract

The host must define the named `commerce-admin.*` routes used by these views and return the expected records/collections from its own controllers. The current VISION routes are the reference implementation. Configure `models`, `admin_flag_column`, `currency_symbol`, `formatters`, `items_per_page` and media directories in `config/commerce-admin.php` for the host schema.

Models need the relationships required by their screens. For example, products use variants/gallery media/category/brand/approved-review relationships; categories with cover uploads need a polymorphic `media()` relationship and `coverMedia()` relation. Keep project-specific validation and transaction semantics in host FormRequests/controllers/services rather than client-side success handlers.
