# GDDealer Upgrade Guide

How to add a new version to the `gddealer` IPS application so existing installs get schema changes and data migrations automatically instead of losing data on re-install.

## The Golden Rule

**Every time you make ANY change to the plugin — new feature, bug fix, template change, new column, anything — you must bump the version before building the tar. No exceptions.**

### The version bump checklist (do this every single time)

1. Look at the current highest version in `data/versions.json`.
2. Increment by 1 (e.g. `10002` → `10003`).
3. Add a new entry to `data/versions.json`:
   ```json
   "1.0.3": 10003
   ```
4. Create `setup/upg_10003/queries.json` — put any new `ALTER TABLE` or `CREATE TABLE` statements here (via the IPS schema helpers), or `[]` if the schema didn't change.
5. Create `setup/upg_10003/upgrade.php` using the exact class structure below:
   ```php
   <?php

   namespace IPS\gddealer\setup\upg_10003;

   use function defined;

   if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
   {
       header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
       exit;
   }

   class _upgrade
   {
       public function step1(): bool
       {
           // Describe what changed in this version
           // Put data migrations here
           // Seed any new templates here using \IPS\Db::i()->insert() with ON DUPLICATE KEY UPDATE
           return TRUE;
       }
   }

   class upgrade extends _upgrade {}
   ```
6. If new templates were added, seed them in `step1()` of the upgrade file.
7. Rebuild the tar and push.

**Never build a tar without doing all 7 steps first.**

## How IPS upgrades work

On upgrade IPS compares the version integer stored in `core_applications.app_long_version` (DB column) against each entry in `data/versions.json`. For every entry whose integer is greater than the stored value, IPS runs:

1. `setup/upg_XXXXX/queries.json` — structured `IPS\Db` helper calls (addColumn, addIndex, createTable, etc.). Each entry supports `"silence_errors": true` so operations that are already applied (e.g. column already exists) do not abort the upgrade.
2. `setup/upg_XXXXX/upgrade.php` — a class `upgrade extends _upgrade` with one or more `stepN()` methods for data migrations. IPS calls `step1()`, `step2()` in order; each must return `TRUE` to continue.

Fresh installs skip all upg_ scripts and run `setup/install.php` which seeds everything from `data/schema.json`.

## Adding a new version

1. Pick the next integer. Current is `10001`; next would be `10002`.
2. Add the mapping to `data/versions.json`:
   ```json
   {
     "1.0.0": 10000,
     "1.0.1": 10001,
     "1.0.2": 10002
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
6. Mirror the new columns/tables into `data/schema.json` so fresh installs get them via `setup/install.php`.
7. Rebuild the tar with `php build-gddealer.php` and push.

**Do NOT add version fields to `data/application.json`.** IPS 5 reads version data exclusively from `data/versions.json`. See Common mistakes below.

## Rules for queries.json

- Use `addColumn` / `addIndex` / `createTable` helpers — not raw SQL strings. IPS schema helpers call `CREATE IF NOT EXISTS` internally where applicable.
- Always set `"silence_errors": true` so re-running is idempotent.
- Never `DROP` or rename columns in `queries.json`. Handle those in `upgrade.php` inside try/catch so failures don't abort.

## Common mistakes

- **Do NOT put version fields in application.json** — IPS reads version exclusively from versions.json. Adding `app_version` or `app_version_human` to application.json causes "Unknown column" DB errors on upgrade.
- **versions.json key/value direction:** Keys are the human-readable version string (e.g. `"1.0.2"`), values are the integer (e.g. `10002`). NOT the other way around. IPS iterates keys as display labels and compares values against the stored version integer.
- **Integer vs string types matter in versions.json:** Values must be bare integers (`10002`), not quoted strings (`"10002"`). Keys must be quoted strings (`"1.0.2"`), which JSON requires for object keys anyway.
- **Highest integer in versions.json wins:** That entry is treated as the current version and triggers all `upg_XXXXX` scripts whose integer is between the stored DB value and the new highest.
- **upgrade.php must be a class, not a plain PHP file** — IPS autoloads `upgrade.php` as a class. A plain PHP file without the `_upgrade`/`upgrade` class structure causes "Class could not be loaded". Required structure:
  - Namespace must be `IPS\gddealer\setup\upg_XXXXX` — the version number is part of the namespace.
  - Must have `class _upgrade` with at least `step1(): bool` returning `TRUE`.
  - Must have `class upgrade extends _upgrade {}` as the final line.
  - Include `use function defined;` after the namespace declaration.
- **Bump versions.json for every change** — every time ANY change is made to the plugin, add a new version entry to `data/versions.json` and create a corresponding `setup/upg_XXXXX/` directory (even if `queries.json` is `[]` and `step1()` is a no-op).

## Current version history

- `10000` — 1.0.0 — Initial install baseline.
- `10001` — 1.0.1 — Added `dealer_slug`, trial/billing fields, dashboard prefs, full review dispute workflow columns on `gd_dealer_ratings`, and new `gd_dealer_dispute_counts` table. Backfills slugs for any dealer rows that predate the column. Seeds `dashboardCustomize`, `dealerRegister`, and `dealerDirectory` front templates via `setup/templates_10001.php`.
- `10002` — 1.0.2 — Re-runs the `templates_10001.php` seeder to recover any install that upgraded to 10001 before template seeding was wired in. No schema changes.
- `10003` — 1.0.3 — Fixed `rate()` in `modules/front/dealers/profile.php` inserting a non-existent `disputed` column into `gd_dealer_ratings`. Replaced with `dispute_status => 'none'`. Code-only fix, no schema or data migration.
