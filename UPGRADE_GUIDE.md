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
- **extensions.json values must be fully qualified class names** — e.g. `"IPS\\gddealer\\extensions\\core\\Notifications\\DealerNotifications"`, NOT just `"DealerNotifications"`. IPS calls `class_exists()` on the value and short names always return false.
- **Never call `\IPS\Member::load()` inside `parse_*()` notification methods** — if the member doesn't exist it throws `OutOfRangeException` which IPS catches silently and hides the entire notification. Use `NULL` for `author` or wrap in try/catch returning `NULL` on failure.

## Current version history

- `10000` — 1.0.0 — Initial install baseline.
- `10001` — 1.0.1 — Added `dealer_slug`, trial/billing fields, dashboard prefs, full review dispute workflow columns on `gd_dealer_ratings`, and new `gd_dealer_dispute_counts` table. Backfills slugs for any dealer rows that predate the column. Seeds `dashboardCustomize`, `dealerRegister`, and `dealerDirectory` front templates via `setup/templates_10001.php`.
- `10002` — 1.0.2 — Re-runs the `templates_10001.php` seeder to recover any install that upgraded to 10001 before template seeding was wired in. No schema changes.
- `10003` — 1.0.3 — Fixed `rate()` in `modules/front/dealers/profile.php` inserting a non-existent `disputed` column into `gd_dealer_ratings`. Replaced with `dispute_status => 'none'`. Code-only fix, no schema or data migration.
- `10004` — 1.0.4 — Redesigned `dealerProfile` review card (reviewer header with avatar, per-category star ratings, score badge, dispute badge, dealer response with timestamp). Controller `manage()` in `modules/front/dealers/profile.php` now populates `reviewer_name`, `reviewer_avatar`, `avg_score`, `created_at_formatted`, `response_at`, and pre-rendered `stars_pricing`/`stars_shipping`/`stars_service`. Updates the template content in `core_theme_templates` via `setup/templates_10004.php` so upgraded installs pick up the new layout.
- `10005` — 1.0.5 — IPS notification fires to the dealer when a new review is submitted (`new_dealer_review` notification type registered via `extensions/core/Notifications/DealerNotifications.php`). Adds `last_review_check` DATETIME column on `gd_dealer_feed_config` to track when the dealer last visited their reviews tab; an unread-count badge now renders on the My Reviews tab via `$dealer['new_reviews']`. `setup/templates_10005.php` updates the `dealerShell` template; `dealerSummary()` in the dashboard controller computes the count, and `reviews()` writes the timestamp on every visit.
- `10006` — 1.0.6 — Full dispute workflow notifications. Customer receives IPS notification + email when dealer contests their review (`review_disputed`). Admins receive IPS notification + email when customer responds to a dispute (`dispute_admin_review`). Dealer receives IPS notification + email when admin upholds (`dispute_upheld`) or dismisses (`dispute_dismissed`) a contest. Four new notification types added to `DealerNotifications.php`. Code-only change, no schema or data migration.
- `10007` — 1.0.7 — Admin review deletion and PM-to-reviewer on dealer/admin actions. Adds `deleteReview()` and standalone `reviews()` (All Reviews) ACP actions; Recent Reviews table on dealer detail view with per-row delete buttons. New acpmenu entry `reviews`, new `allReviews` admin template. `setup/templates_10007.php` syncs the updated `dealerDetail` template and installs the new `allReviews` template. No schema changes.
- `10008` — 1.0.8 — Removed all `\IPS\Notification` and `\IPS\core\Messenger\Conversation` API calls that were causing EX0 errors — all notifications now use `\IPS\Email::buildFromTemplate()` only. Added four new email templates: `dealerResponded` (reviewer gets email when dealer responds), `newDealerReview` (dealer gets email on new review), `disputeUpheld` (dealer notified of upheld contest), `disputeOutcome` (reviewer notified of dispute resolution). Fixed dispute deadline display to use human-readable format (`F j, Y`). Updated `dealerProfile` template to reference `deadline_formatted`. `setup/templates_10008.php` patches the template via targeted string replacement.
- `10009` — 1.0.9 — Re-enabled IPS inline notifications and Messenger PMs using the correct IPS 5 API. `DealerNotifications.php` now extends `\IPS\Extensions\NotificationsAbstract` with `configurationOptions()` and `parse_KEY()` methods for each of six notification types (`new_dealer_review`, `review_disputed`, `dispute_admin_review`, `dispute_upheld`, `dispute_dismissed`, `dealer_responded`). PMs use the correct IPS 5 signature: `Conversation::createItem($sender, $ipAddress, $dateTime)` followed by `$commentClass::create()` and `$conversation->authorize()`. PMs gated by `Conversation::memberCanReceiveNewMessage()`. Emails continue to fire alongside notifications as a guaranteed fallback. No schema changes.
- `10010` — 1.0.10 — Clearer `disputeNotify` email with ACTION REQUIRED subject, explicit deadline consequences, and `{contact_email}` placeholder. Admin dispute queue shows days remaining until auto-resolve. Auto-resolve task (`ResolveExpiredDisputes`) now includes `{dealer_name}` in the customer notification email. Public dealer profile shows an "Action Required" banner on the reviewer's own review card when dispute status is `pending_customer`, with a direct "Respond Now" link. `setup/templates_10010.php` patches `disputeQueue` and `dealerProfile` templates for upgraded installs. No schema changes.
- `10011` — 1.0.11 — Review author can edit their own review. New `editReview()` action in `modules/front/dealers/profile.php` renders an edit form (GET) and saves changes (POST); blocked while the review is under active dispute (`pending_customer`/`pending_admin`). Per-row `edit_review_url` is pre-built in the controller and the `dealerProfile` template shows "Edit your review" on own reviews in `dispute_status=none`. New `front/dealers/editReview` template. `setup/templates_10011.php` seeds the new template and patches `dealerProfile` for upgraded installs. Two new lang keys: `gddealer_review_updated`, `gddealer_cannot_edit_disputed`. No schema changes.
- `10012` — 1.0.12 — Registers notification defaults in `core_notification_defaults` so the six gddealer notification types (`new_dealer_review`, `review_disputed`, `dealer_responded`, `dispute_admin_review`, `dispute_upheld`, `dispute_dismissed`) appear in member notification preferences. `upg_10012/upgrade.php` seeds defaults on upgrade; `setup/install.php` seeds them on fresh install. Adds IPS-required notification lang keys: `notifications__gddealer_DealerNotifications`(+`_desc`), `mailsub__gddealer_notification_KEY` for each type (email subject when inline notification is delivered by email), and `mailani__gddealer_notification_KEY` for each type (email body). Replaces the six `gddealer_notif_KEY` / `_desc` titles/descriptions with concise versions used on the member notification preferences page. No schema changes.
- `10013` — 1.0.13 — Added `data/extensions.json` enumerating the four gddealer `core` extensions (`ContentRouter` → DealerFollow, `Notifications` → DealerNotifications, `EmailTemplates` → DealerEmails, `FrontNavigation` → DealerNav) so IPS registers them via the explicit registry instead of relying on a filesystem scan alone. `upg_10013/upgrade.php` unsets `\IPS\Data\Store::i()->extensions` and `applications` so upgraded installs immediately re-scan and pick up the Notifications extension (fixes `$app->extensions('core','Notifications')` returning empty on installs that cached the extension list before the registry existed). `setup/install.php` also clears the extensions cache on fresh install. No schema changes.
- `10014` — 1.0.14 — Fixed `data/extensions.json` to use fully qualified class names (e.g. `IPS\gddealer\extensions\core\Notifications\DealerNotifications`) instead of short names. Clears extension cache on upgrade. No schema changes.
- `10015` — 1.0.15 — Fixed `data/extensions.json` structure: per-type values are now objects keyed by short class name mapping to the FQCN string (e.g. `"DealerNotifications": "IPS\\gddealer\\extensions\\core\\Notifications\\DealerNotifications"`), not plain arrays of FQCN strings. Clears extension cache on upgrade. No schema changes.
- `10016` — 1.0.16 — Fixed all `parse_*()` methods in `DealerNotifications.php` to set `'author' => NULL` instead of calling `\IPS\Member::load()`. `Member::load()` throws `OutOfRangeException` on invalid member IDs which IPS catches silently, hiding the entire notification. Removed unused `IPS\Member` import. No schema changes.
