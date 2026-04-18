# GDDealer Upgrade Guide

How to add a new version to the `gddealer` IPS application so existing installs get schema changes and data migrations automatically instead of losing data on re-install.

## How IPS upgrades work

On upgrade IPS compares the `app_long_version` integer stored in `core_applications` against each entry in `data/versions.json`. For every entry whose integer is greater than the stored value, IPS runs:

1. `setup/upg_XXXXX/queries.json` — structured `IPS\Db` helper calls (addColumn, addIndex, createTable, etc.). Each entry supports `"silence_errors": true` so operations that are already applied (e.g. column already exists) do not abort the upgrade.
2. `setup/upg_XXXXX/upgrade.php` — a class `upgrade extends _upgrade` with one or more `stepN()` methods for data migrations. IPS calls `step1()`, `step2()` in order; each must return `TRUE` to continue.

Fresh installs skip all upg_ scripts and run `setup/install.php` which seeds everything from `data/schema.json`.

## Adding a new version

1. Pick the next integer. Current is `10001`; next would be `10002`.
2. Add the mapping to `data/versions.json`:
   ```json
   {
     "10000": "1.0.0",
     "10001": "1.0.1",
     "10002": "1.0.2"
   }
   ```
3. Create `setup/upg_10002/queries.json` with the DDL. Example:
   ```json
   [
     {
       "method": "addColumn",
       "params": [ "gd_dealer_feed_config", {
         "name": "my_new_col", "type": "VARCHAR", "length": 100, "allow_null": true, "default": null
       } ],
       "silence_errors": true
     }
   ]
   ```
4. Create `setup/upg_10002/upgrade.php` with any data backfill. Follow the class pattern used in `upg_10001/upgrade.php` (`_upgrade` + `upgrade` alias, `stepN(): bool` methods).
5. Create `setup/upg_10002/index.html` (blank).
6. Bump `data/application.json`:
   ```json
   "app_version": "1.0.2",
   "app_long_version": 10002
   ```
7. Mirror the new columns/tables into `data/schema.json` so fresh installs get them via `setup/install.php`.
8. Rebuild the tar with `php build-gddealer.php` and push.

## Rules for queries.json

- Use `addColumn` / `addIndex` / `createTable` helpers — not raw SQL strings. IPS schema helpers call `CREATE IF NOT EXISTS` internally where applicable.
- Always set `"silence_errors": true` so re-running is idempotent.
- Never `DROP` or rename columns in `queries.json`. Handle those in `upgrade.php` inside try/catch so failures don't abort.

## Current version history

- `10000` — 1.0.0 — Initial install baseline.
- `10001` — 1.0.1 — Added `dealer_slug`, trial/billing fields, dashboard prefs, full review dispute workflow columns on `gd_dealer_ratings`, and new `gd_dealer_dispute_counts` table. Backfills slugs for any dealer rows that predate the column.
