# Librarium

A personal library catalog for keeping track of books and organizing an existing collection.

[![Tests](https://github.com/doguskysilva/librarium/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/doguskysilva/librarium/actions/workflows/tests.yml)
[![Lint](https://github.com/doguskysilva/librarium/actions/workflows/lint.yml/badge.svg?branch=main)](https://github.com/doguskysilva/librarium/actions/workflows/lint.yml)
[![Docker publish](https://github.com/doguskysilva/librarium/actions/workflows/docker-publish.yml/badge.svg?branch=main)](https://github.com/doguskysilva/librarium/actions/workflows/docker-publish.yml)
[![Production deploy](https://github.com/doguskysilva/librarium/actions/workflows/deploy-production.yml/badge.svg?branch=main)](https://github.com/doguskysilva/librarium/actions/workflows/deploy-production.yml)

## About the name

*Librarium* evokes a library: a place to gather and care for books. The project focuses on making a personal catalog easy to maintain, browse, and import—not on enforcing a complex bibliographic workflow.

## Local setup

```sh
git clone https://github.com/doguskysilva/librarium.git
cd librarium
composer run setup
composer run dev
```

The setup script installs dependencies, creates `.env` from `.env.example` if needed, generates an application key, runs migrations, and builds frontend assets. The development server is available at `http://localhost:8000`.

Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env` before seeding to choose the initial account. `php artisan db:seed` creates it through `UserSeeder` along with sample catalog data. If either value is blank, the seeder uses `test@example.com` or `password` for that value; change these defaults for any non-local environment.

## Project focus

Librarium focuses on a practical personal book catalog and careful import of existing inventory, favoring human review and preservation of catalog data over automatic guesses.

## Inventory import

The versioned interchange contract is [librarium.inventory 1.0.0](schemas/librarium.inventory-1.0.0.schema.json). Keep the source JSON and its field provenance as separate artifacts; imports do not overwrite populated catalog fields or remove relationships.

You can upload and review an inventory from the authenticated **Import inventory** page in the sidebar, or use the CLI below. Both options support a no-write preview before applying verified records, and show results and errors for each record.

Validate and preview an inventory first:

```sh
php artisan inventory:import /path/to/inventory.json --report=/path/to/import-report.json
```

Only records marked `review_status: "verified"` are eligible. Applying requires `inventory.is_complete` to be `true`; inspect the dry-run report and back up the production database before applying:

```sh
php artisan inventory:import /path/to/inventory.json --apply --report=/path/to/import-report.json
```

Books match in order by stable `source_key`, exact ISBN with matching title and author set, then provider/type/value identifier with the same edition check. Title-only matching is never used. Ambiguous or conflicting records are reported for manual resolution.
