# Librarium

A personal library management application.

[![Tests](https://github.com/doguskysilva/librarium/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/doguskysilva/librarium/actions/workflows/tests.yml)
[![Lint](https://github.com/doguskysilva/librarium/actions/workflows/lint.yml/badge.svg?branch=main)](https://github.com/doguskysilva/librarium/actions/workflows/lint.yml)
[![Docker publish](https://github.com/doguskysilva/librarium/actions/workflows/docker-publish.yml/badge.svg?branch=main)](https://github.com/doguskysilva/librarium/actions/workflows/docker-publish.yml)
[![Production deploy](https://github.com/doguskysilva/librarium/actions/workflows/deploy-production.yml/badge.svg?branch=main)](https://github.com/doguskysilva/librarium/actions/workflows/deploy-production.yml)

## Inventory import

The versioned interchange contract is [librarium.inventory 1.0.0](schemas/librarium.inventory-1.0.0.schema.json). Keep the source JSON and its field provenance as separate artifacts; imports do not overwrite populated catalog fields or remove relationships.

Validate and preview an inventory first:

```sh
php artisan inventory:import /path/to/inventory.json --report=/path/to/import-report.json
```

Only records marked `review_status: "verified"` are eligible. Applying requires `inventory.is_complete` to be `true`; inspect the dry-run report and back up the production database before applying:

```sh
php artisan inventory:import /path/to/inventory.json --apply --report=/path/to/import-report.json
```

Books match in order by stable `source_key`, exact ISBN with matching title and author set, then provider/type/value identifier with the same edition check. Title-only matching is never used. Ambiguous or conflicting records are reported for manual resolution.
