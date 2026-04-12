GUN DEALS PLATFORM
IPS Community Suite — Three Plugin Architecture

COMPLETE TECHNICAL SPECIFICATION
Version 2.9.16  |  April 2026  |  Confidential  |  GunRack


Visual Reference — Platform Page Mockups
The following mockups show the intended visual layout for each major page of GunRack.deals. All mockups reflect the GunRack Dark theme with navy primary color, red accent, and the two-column layout pattern used throughout. These are design reference documents — the developer implements these layouts using IPS templates and the plugin front-end code as specified in each numbered section.
Figure 1 — Loadout Builder (Tier 3 layout)  ·  Section 14.3.3

Figure 2 — Homepage — /gunrack.deals

Homepage  ·  Section references throughout spec
Figure 3 — Price Comparison — /products/{category}/{slug}

Price comparison page  ·  Section references throughout spec
Figure 4 — Dealer Dashboard — /dealers/dashboard

Dealer dashboard  ·  Section references throughout spec
Figure 5 — Community Deals Feed — /deals

Community deals feed  ·  Section references throughout spec
Figure 6 — Reviews Hub — /reviews

Reviews hub  ·  Section references throughout spec
Figure 7 — Rebates — /rebates

Rebates page  ·  Section references throughout spec
Figure 8 — Loadouts Hub — /loadouts

Loadouts hub  ·  Section references throughout spec
Figure 9 — Forum Thread — Builds & Loadouts category

Forum thread with loadout embed  ·  Section references throughout spec
Figure 10 — Member Profile — IPS native profile extended with custom tabs

Member profile  ·  Section references throughout spec
Figure 11 — Community Leaderboard — /leaderboard

Community leaderboard  ·  Section references throughout spec

# 1. Project Overview
This document is the complete, approved technical specification for a firearms price comparison and community platform built on IPS Community Suite. It covers all three plugins in full detail. No development work begins on any plugin until the corresponding section of this document has been reviewed, annotated with any questions, and signed off.
The platform is designed to directly compete with gun.deals by offering everything gun.deals provides plus: price history charts, total-cost-with-shipping display, ATF/NFA compliance indicators, per-state shipping restriction flags, an FFL locator, community dealer ratings, and a superior search experience powered by OpenSearch.


## 1.1 Plugin Architecture


## 1.2 Parallel Build Schedule
Plugin 1 is built first and must be fully stable before Plugin 2 and Plugin 3 begin. Plugins 2 and 3 are then built in parallel since they do not depend on each other — only both depend on Plugin 1.


## 1.3 Catalog Bootstrap Strategy
A price comparison platform with no dealer listings is not useful to consumers. The following bootstrap strategy ensures the platform has meaningful content from day one without scraping competitor data.


### 1.3.1 Why Not Scrape gun.deals
- gun.deals Terms of Service explicitly prohibit scraping — a cease and desist at launch would be damaging
- Scraped prices are immediately stale — no live feed infrastructure behind them
- Any scraped data would need to be replaced with real feed data anyway, creating a migration problem
- Building on scraped data starts the platform on a legally and reputationally weak foundation


### 1.3.2 Distributor Catalog as Foundation
The six distributor feeds (Plugin 1) provide a complete product catalog with real product data before any dealer signs up. At launch, product pages will show:
- Full product details: title, brand, specs, images, description, MSRP — all from distributor feeds
- Category browsing and full-text search — fully functional from day one
- Price comparison table area showing a placeholder: 'No dealers have listed this product yet. Are you a dealer? Add your feed.'
- FFL locator, NFA indicators, state compliance flags — all functional regardless of dealer count
This is a legitimate and useful launch state. Users can research products, compare MSRPs, find FFLs, and browse categories — even before dealers are onboarded.


### 1.3.3 Founding Dealer Program
⚠  CRITICAL PRE-DEVELOPMENT ACTION — START IMMEDIATELY: Distributor feed access and founding dealer outreach must begin in parallel with development. Feed credentials from RSR Group and Sports South are required to test Plugin 1. Without them the developer will be building against dummy data for the first 2–3 months. See Section 1.7 for the full pre-development checklist.

Before public launch, personally onboard 2–3 FFL dealers from existing GunRack business relationships as founding dealers. Offer them:
- 90-day free trial at the Pro tier ($399/mo value)
- 'Founding Dealer' badge on their dealer profile — permanent
- Input into platform features during the trial period
- Locked-in pricing guarantee when trial ends
These founding dealers populate the platform with real live pricing across their inventory before the first public visitor arrives. Their feeds create immediate value for consumers and demonstrate the platform's core function to prospective paying dealers.


### 1.3.4 Empty State Messaging
The developer must implement thoughtful empty state messages rather than blank sections. Spec for each empty state:


## 1.7 Pre-Development Checklist — Start Immediately
The following actions must be completed before or during the first two weeks of development. These are business and infrastructure tasks, not code. Blocking development on any of these will cost weeks.


Items 1 and 2 are the most important. If RSR and Sports South take 4–6 weeks to respond (common for technology partner applications), the developer can build Plugin 1's ingestion engine against a dummy feed in the same format — but they cannot populate the catalog with real data until credentials arrive. Start the outreach on day one.


# 2. Plugin 1 — GD Master Catalog
The Master Catalog plugin is the data foundation of the entire platform. It ingests product data from six wholesale distributors, resolves conflicts using a strict priority hierarchy, and maintains a single canonical product record per UPC. No pricing data is stored here — only product identity, specifications, and metadata.

⚠  Plugins 2 and 3 must not begin development until Plugin 1 has completed at least two full successful import cycles across all six distributors and all records are confirmed indexed and searchable in OpenSearch.


## 2.1 Six-Distributor Registry


## 2.2 Conflict Resolution Rules
This section defines exactly how data conflicts are resolved when multiple distributors carry the same UPC. These rules are non-negotiable and must be implemented precisely.

⚠  The developer must implement conflict resolution as a discrete, testable function. Unit tests must be written for every rule listed below before the importer is considered complete.


### 2.2.1 Standard Priority Rule
For all fields not listed in Section 2.2.2, the standard priority rule applies:
- Check if RSR Group has a non-empty value for the field.
- If yes: use RSR value. No other distributor can overwrite it — ever.
- If RSR value is empty: check Sports South. If non-empty, use it.
- If Sports South is also empty: check Davidson's. Continue down the priority chain.
- If all six distributors have empty values: field remains empty on the product record.
- On every subsequent import: re-apply these rules. A lower-priority source that previously filled a gap is replaced if a higher-priority source later provides a value for that field.


### 2.2.2 Field-Level Special Rules
The following fields use rules that override the standard priority hierarchy:


### 2.2.3 Admin Manual Override
Any field on any product record can be manually locked by an admin. Locked fields are immune to all future feed imports from all distributors.
- Admin edits a field on a product record and checks 'Lock this field'
- Field name is added to the locked_fields JSON array on the record
- All subsequent import passes skip this field for this UPC
- Admin dashboard shows total locked field count with a review list
- Admin can unlock any field to re-enable automatic conflict resolution


### 2.2.4 Conflict Log Table


CREATE TABLE gd_conflict_log (
id               BIGINT AUTO_INCREMENT PRIMARY KEY,
upc              VARCHAR(20)  NOT NULL,
field_name       VARCHAR(100) NOT NULL,
winning_source   VARCHAR(100) NOT NULL,
winning_value    TEXT,
losing_source    VARCHAR(100) NOT NULL,
losing_value     TEXT,
rule_applied     VARCHAR(50)  NOT NULL,
-- priority | longest | highest_res | highest_val
-- | flagged_for_review | admin_override
resolved_at      DATETIME NOT NULL,
admin_override   TINYINT(1) DEFAULT 0,
INDEX idx_upc    (upc),
INDEX idx_field  (field_name),
INDEX idx_source (winning_source)
);


## 2.3 Products Database Schema


## 2.4 Category Taxonomy
All categories must be pre-created in IPS before the first import run. Category mapping configs translate each distributor's category strings to these IPS category IDs.

- Handguns — Pistols, Revolvers, Derringers
- Rifles — Semi-Automatic, Bolt-Action, Lever-Action, Single-Shot, Muzzleloaders
- Shotguns — Semi-Automatic, Pump-Action, Break-Action, Over/Under, Side-by-Side
- Ammunition — Handgun Ammo, Rifle Ammo, Shotgun Ammo, Rimfire, Specialty/Exotic
- NFA Items — Suppressors, Short-Barreled Rifles, Short-Barreled Shotguns, Machine Guns, AOW
- Magazines — Handgun, Rifle, Shotgun, Drum
- Optics — Red Dots, Rifle Scopes, LPVOs, Prism Scopes, Night Vision, Thermal, Magnifiers
- Parts & Accessories — Barrels, Triggers, Stocks, Grips, Rails, Handguards, Muzzle Devices
- Holsters & Carry — IWB, OWB, Shoulder, Ankle, Appendix, Duty, Vehicle
- Storage & Safety — Gun Safes, Hard Cases, Soft Cases, Lock Boxes, Trigger Locks
- Cleaning & Maintenance — Cleaning Kits, Lubricants, Solvents, Bore Snakes, Patches
- Tactical Gear — Weapon Lights, Lasers, Bipods, Slings, Foregrips, Vertical Grips
- Hunting Gear — Game Calls, Scent Control, Blinds, Feeders, Trail Cameras


## 2.5 Distributor Feed Config Fields


## 2.6 Import Process — Per Feed
- Fetch feed from URL using configured authentication method
- Parse according to format (XML / JSON / CSV)
- For each record: extract UPC, apply field mapping, apply category mapping
- Skip any record with no UPC value — log as skipped
- Check if UPC exists in Products database
- If NEW UPC: create record, set distributor_sources, run all field rules, set record_status = Active
- If EXISTING UPC: for each field, check locked_fields — if locked skip. If not locked, apply correct conflict rule from Section 2.2
- Write conflict log entry for every field where a conflict was detected and resolved
- Update distributor_sources to include this distributor if not already listed
- Update primary_source if this distributor won more fields than the current primary_source
- Update last_updated timestamp
- After full feed complete: any record from this distributor not seen in this run for 3 consecutive runs — set record_status = Discontinued if no other distributor still carries it
- Queue OpenSearch re-index for all created and updated records
- Write run summary to gd_import_log table


## 2.7 Import Log Table

CREATE TABLE gd_import_log (
id                BIGINT AUTO_INCREMENT PRIMARY KEY,
feed_id           INT      NOT NULL,
distributor       VARCHAR(100) NOT NULL,
run_start         DATETIME NOT NULL,
run_end           DATETIME NULL,
records_total     INT      DEFAULT 0,
records_created   INT      DEFAULT 0,
records_updated   INT      DEFAULT 0,
records_skipped   INT      DEFAULT 0,
records_errored   INT      DEFAULT 0,
conflicts_logged  INT      DEFAULT 0,
error_log         LONGTEXT,
status            ENUM('running','completed','failed') DEFAULT 'running',
INDEX idx_feed    (feed_id),
INDEX idx_start   (run_start)
);


## 2.8 OpenSearch Index — Products


## 2.9 Admin Dashboard — Master Catalog
- Total product count with breakdown by category and by distributor source
- Per-distributor stats: last run time, status, records created/updated/errored
- Manual import trigger per feed — runs immediately as foreground task
- Conflict log browser: filter by field, distributor, date range, rule applied
- Admin Review queue: all records flagged record_status = Admin Review with one-click resolve
- Locked fields report: all manually overridden fields with ability to unlock
- OpenSearch status: last full index timestamp, record count, Rebuild Index button
- Product search and browse: find any record by UPC, title, brand, category — edit inline


## 2.10 Distributor Compliance Flag Ingestion
Distributors are increasingly including compliance and restriction data directly in their product feeds — state shipping restrictions, FFL-only flags, hazmat fees, and age verification requirements. This section defines how that data is captured, mapped, stored, and applied automatically so admin does not need to manually input restrictions the distributor already provides.


### 2.10.1 Restriction Types and Default Behavior
At launch, only state-level shipping restrictions are captured from distributor feeds. Other restriction types (FFL-only, age verification, hazmat, consumer prohibited) continue to be managed manually by admin in the catalog — distributor data for those types is not yet trusted as a reliable source. This scope can be expanded in a future version as distributor data quality improves.


The listing-always-visible approach is intentional. Automatically hiding listings based on distributor flags risks hiding valid inventory if the distributor data is stale or incorrect. The badge gives the buyer the information — they can verify with the dealer directly before purchasing.


### 2.10.2 Trust Model — All Flags Require Admin Review
All state restriction flags from all distributors are queued for admin review before going live. No flag is applied automatically regardless of distributor. This is the correct default position — distributor compliance data can be wrong, outdated, or entered in error, and a restriction that hides a badge on a buyer's listing is a meaningful action that should always have admin sign-off.


### 2.10.3 Conflict Resolution Rules


### 2.10.4 Compliance Flags Database Schema

CREATE TABLE gd_compliance_flags (
id                  INT AUTO_INCREMENT PRIMARY KEY,
upc                 VARCHAR(20)   NOT NULL,
listing_id          INT           NULL,
-- NULL = product-level (catalog) flag. Set = dealer listing-level flag.
distributor_id      INT           NOT NULL,
flag_type           ENUM('state_restriction','ffl_required','age_verification',
'hazmat','consumer_prohibited') NOT NULL,
flag_value          VARCHAR(500)  NOT NULL,
-- state_restriction: comma-separated state codes e.g. 'CA,NY,MA'
-- ffl_required/consumer_prohibited: '1'
-- age_verification: '18' or '21'
-- hazmat: '1' or dollar fee amount e.g. '35.00'
source              ENUM('distributor','admin_manual','admin_override') DEFAULT 'distributor',
status              ENUM('active','pending_review','rejected') DEFAULT 'active',
-- All flags require admin review — trust level is not variable
first_seen_at       DATETIME      NOT NULL,
last_confirmed_at   DATETIME      NOT NULL,
removed_by_dist_at  DATETIME      NULL,
admin_reviewed_by   INT           NULL,
admin_reviewed_at   DATETIME      NULL,
INDEX idx_upc     (upc),
INDEX idx_listing (listing_id),
INDEX idx_type    (flag_type),
INDEX idx_status  (status)
);


gd_compliance_flags is the single source of truth for all restriction data regardless of origin. The catalog table references this table for display — it does not duplicate restriction fields. This means a new restriction type can be added in the future by adding a new enum value and the display layer picks it up automatically.


### 2.10.5 Acceptance Criteria — Compliance Flag Ingestion
- Distributor feed with restricted_states field: flag queued in compliance review panel — badge does NOT appear publicly until admin approves
- Admin approves flag: [STATE RESTRICTED] badge appears on dealer listing immediately
- Admin rejects flag: no badge shown — listing appears normally with no restriction indicator
- Listing with [STATE RESTRICTED] badge remains visible to all users — badge is informational, listing is not hidden
- Admin-set restriction persists when distributor removes theirs — conflict notification email sent
- Non-standard field name mapped in ACP: flag applies correctly on next sync
- Daily digest email correctly summarizes compliance changes from previous 24 hours
- Audit log records every flag change with source, timestamp, and admin action


## 2.11 Admin-Set Restrictions and Feed Conflict Detection
Two systems work together to give admin full control over catalog data integrity. Admin-set restrictions allow manual state restriction entry at product or listing level, independent of distributor feeds. Feed conflict detection flags any incoming compliance field data that disagrees with what is in the database — holding the existing value until admin reviews and resolves.


### 2.11.1 Admin-Set State Restrictions

- Admin-set restrictions stored with source = admin_manual in gd_compliance_flags — never overwritten by feed imports
- If a feed import conflicts with an admin-set restriction, the conflict is flagged for review but the admin-set entry remains active
- Admin-set restrictions show a lock icon and 'Admin set' label in ACP — visually distinct from distributor-sourced flags
- Admin can remove or modify admin-set restrictions at any time from the Restrictions tab


### 2.11.2 Feed Conflict Detection — Configurable Per Distributor
Admin configures which fields trigger a conflict review flag on a per-distributor basis in ACP. Different distributors have different data quality levels — a field that is reliable from RSR might be unreliable from a smaller distributor. This configuration lives under ACP → Plugin 1 Settings → Feed Configuration → [Distributor] → Conflict Detection Fields.

Default Field Configuration
The following fields are pre-configured as conflict-flagged for all distributors by default. Admin can add or remove fields per distributor:


Price, stock quantity, and shipping cost are never conflict-flagged regardless of configuration — they must update freely on every import to keep pricing current.

Per-Distributor Configuration in ACP
- ACP → Plugin 1 Settings → Feed Configuration → [Select Distributor] → Conflict Detection tab
- Toggle list of all available fields — checked fields trigger review flags for this distributor, unchecked fields update silently
- Changes take effect on the next import run
- 'Reset to defaults' button restores the default configuration for that distributor

- Import detects a change in a conflict-configured field — record written to gd_feed_conflicts with current value, incoming value, distributor, timestamp
- Existing database value kept live — incoming value not applied
- Admin notified: '[N] feed conflicts detected in latest [distributor] import'
- 48-hour timer starts (auto_resolve_at = detected_at + 48 hours)
- Admin reviews in unified compliance review panel — can act before 48 hours to control outcome
- If no admin action after 48 hours: incoming value automatically applied, status = auto_accepted, admin digest email sent


### 2.11.3 Unified Compliance Review Panel — ACP


### 2.11.4 Conflict Resolution Options

The 48-hour auto-accept is the right default because most feed conflicts are legitimate updates — a distributor correcting a caliber typo, adding a new state restriction, etc. Requiring admin action for every conflict would create an unmanageable queue. Admin intervention is reserved for cases where something looks wrong — the 48-hour window gives enough time to catch those without blocking routine data corrections.

- Lock reason note: required field for Keep existing and Set custom — admin types why. Shown in Locked fields tab so future admins understand context.
- Locked fields show a lock icon on the product edit page in ACP
- Hard-locked fields show a different icon — padlock with X — distinguishing them from distributor-specific locks


### 2.11.5 How Locks Interact With Feed Imports
- For each compliance field in incoming feed: check gd_field_locks for matching UPC + field_name
- No lock found: update normally
- Distributor-specific lock for THIS distributor: skip update, log skipped in import log
- Distributor-specific lock for a DIFFERENT distributor: update normally — only the locked distributor is blocked
- Hard lock: skip update from ANY distributor, log skipped
- Incoming value differs from locked value: write gd_feed_conflicts record regardless — admin can see the distributor is sending different data even when field is locked

Step 6 is the audit trail that tells admin when their locked value may need revisiting — e.g. if RSR starts sending a different caliber, admin sees it even though the field is protected.


### 2.11.6 Database Schema

CREATE TABLE gd_feed_conflicts (
id                  INT AUTO_INCREMENT PRIMARY KEY,
upc                 VARCHAR(20)   NOT NULL,
listing_id          INT           NULL,
distributor_id      INT           NOT NULL,
field_name          VARCHAR(100)  NOT NULL,
current_value       TEXT          NOT NULL,
incoming_value      TEXT          NOT NULL,
import_id           INT           NOT NULL,
detected_at         DATETIME      NOT NULL,
status              ENUM('pending','accepted','kept','custom','auto_accepted') DEFAULT 'pending',
-- auto_accepted = 48-hour timer elapsed, incoming value applied automatically
auto_resolve_at     DATETIME      NOT NULL,
-- Set to detected_at + 48 hours on creation
resolved_by         INT           NULL,
-- NULL for auto-resolved conflicts
resolved_at         DATETIME      NULL,
resolution_note     TEXT          NULL,
INDEX idx_upc    (upc),
INDEX idx_status (status),
INDEX idx_dist   (distributor_id)
);


CREATE TABLE gd_field_locks (
id                    INT AUTO_INCREMENT PRIMARY KEY,
upc                   VARCHAR(20)   NOT NULL,
listing_id            INT           NULL,
field_name            VARCHAR(100)  NOT NULL,
locked_value          TEXT          NOT NULL,
lock_type             ENUM('distributor_specific','hard') NOT NULL,
locked_distributor_id INT           NULL,
-- NULL for hard locks. Set for distributor-specific locks.
locked_by             INT           NOT NULL,
locked_at             DATETIME      NOT NULL,
lock_reason           TEXT          NOT NULL,
INDEX idx_upc   (upc),
INDEX idx_field (field_name)
);


### 2.11.7 Acceptance Criteria
- Admin sets product-level state restriction: [STATE RESTRICTED] badge on ALL dealer listings for that UPC
- Admin sets listing-level state restriction: badge on that dealer only, other dealers unaffected
- Feed import conflicts with admin-set restriction: conflict flagged, admin-set entry stays active
- Admin-set restrictions show lock icon and 'Admin set' label in ACP compliance flags list
- ACP conflict detection config: unchecking a field for a distributor means that field updates silently on next import — no conflict logged
- ACP conflict detection config: checking a field for a distributor enables conflict flagging — takes effect on next import
- Feed import changes nfa_item (conflict-configured field): conflict record created, existing value stays live, auto_resolve_at set to +48 hours
- Feed import changes price/stock: no conflict flagged — updates apply immediately regardless of config
- 48-hour timer elapses with no admin action: incoming value auto-applied, status = auto_accepted, digest email lists the resolution
- Admin acts before 48 hours and accepts: database updates to feed value, no lock created
- Admin keeps existing: existing value stays, distributor-specific lock created, reason note required and saved
- Admin sets custom value: custom value applied, hard lock created, reason note required
- Lock icon visible on locked fields in ACP product edit page — hard lock shows distinct icon
- Distributor-specific lock: import from locked distributor skips field — confirmed in import log
- Distributor-specific lock: import from a DIFFERENT distributor still updates field normally — confirmed
- Hard lock: import from ANY distributor skips field — confirmed with test imports from two distributors
- Conflict record written for hard-locked field when incoming value differs — visible in audit log
- Lock reason note displays correctly in Locked fields tab
- Admin unlocks a field: next import updates it normally

# 3. Plugin 2 — GD Dealer Manager
The Dealer Manager plugin handles the full dealer lifecycle: registration, feed configuration, billing via IPS Commerce, XML/JSON/CSV feed ingestion, UPC matching against the master catalog, price history tracking, and a self-service dealer dashboard. This plugin is the primary revenue engine of the platform.


## 3.1 Subscription Tiers

All tiers are configured as IPS Commerce subscription products. Billing, renewals, failed payment handling, and cancellations are managed entirely by IPS Commerce with Stripe as the gateway. No custom billing code is required.


## 3.2 IPS Member Group Configuration
IPS member groups control what each user type can see and do on the platform. The following three groups must be configured before any plugin development begins. The Dealers group assignment is fully automated via IPS Commerce — no manual admin action is required when a dealer subscribes or cancels.


### 3.2.1 IPS Commerce Group Promotion Configuration
Each subscription product in IPS Commerce must be configured with the following group promotion settings. This is an IPS native feature — no custom code required.


The subscription_tier field on Dealer Listing records (Section 3.5) is set by the plugin at import time based on which IPS Commerce subscription product the dealer currently holds. If a dealer upgrades from Basic to Pro mid-month, their next feed import run updates subscription_tier on all their listings, triggering the faster sync schedule and unlocking priority placement in the price comparison table.

⚠  IPS Commerce must be fully configured with Stripe live keys and group promotion settings tested with a real transaction before Plugin 2 development begins. A test subscription purchase, upgrade, and cancellation must all be verified to correctly add and remove the Dealers group before the developer writes a single line of Plugin 2 code.


## 3.3 Dealer Onboarding Flow
- Dealer visits /dealers/join
- Completes dealer registration form (Section 3.3)
- Selects subscription tier — pricing table displayed with feature comparison
- Proceeds to IPS Commerce checkout — enters payment details via Stripe
- On successful payment: dealer member group assigned, API key generated, welcome email with setup instructions sent
- Dealer redirected to /dealers/dashboard — first feed import queued immediately
- Dealer receives second email when first import completes with stats


## 3.4 Dealer Registration Form


## 3.5 Feed Format Support
All three formats must be supported with full parity in features. The format is selected during dealer registration and can be changed by the dealer from their dashboard.

XML Feed Requirements
- Standard XML with repeating product elements
- Supports both attribute-based and element-based field values
- XPath-style field mapping configuration
- Handles nested elements e.g. <images><image>url</image></images>
- Supports namespaced XML

JSON Feed Requirements
- Standard JSON array of product objects
- Supports nested objects for field values via dot-notation in field mapping e.g. 'images.primary'
- Supports JSON arrays within products e.g. additional images
- Handles both string and numeric field values

CSV Feed Requirements
- Standard comma-separated with header row
- Supports tab-separated as a variant — auto-detected
- Header row field names mapped via field mapping config
- Handles quoted fields containing commas
- Configurable encoding — UTF-8 default, Latin-1 supported


## 3.6 Dealer Listings Database Schema
IPS Pages Database named 'Dealer Listings'. One record per dealer per UPC.


## 3.7 Price History Table
Custom MySQL table outside IPS Pages. Written only when price changes — not on every import — to prevent table bloat.


CREATE TABLE gd_price_history (
id            BIGINT AUTO_INCREMENT PRIMARY KEY,
upc           VARCHAR(20)    NOT NULL,
dealer_id     INT            NOT NULL,
price         DECIMAL(10,2)  NOT NULL,
in_stock      TINYINT(1)     NOT NULL,
recorded_at   DATETIME       NOT NULL,
INDEX idx_upc          (upc),
INDEX idx_dealer       (dealer_id),
INDEX idx_upc_dealer   (upc, dealer_id),
INDEX idx_recorded     (recorded_at)
);


Price history is written when: (1) price changes from previous snapshot for that dealer+UPC, or (2) in_stock status changes. This means a dealer whose price never changes generates only one history row per UPC — the initial import row.


## 3.8 Dealer Feed Ingestion Logic
Each dealer feed runs as a queued IPS background task on the schedule matching their subscription tier. All three formats (XML/JSON/CSV) use the same post-parse logic below.

- Fetch dealer feed from configured URL
- Parse according to configured format — apply dealer's field mapping
- Validate each record: UPC must be present, price must be a valid positive decimal
- For each valid record: look up UPC in Products database
- If UPC not found in Products: log to gd_unmatched_upcs — skip this record
- If UPC found: check if Dealer Listing exists for this dealer + UPC
- If NEW listing: create Dealer Listing record, insert first row into gd_price_history, set listing_status = Active
- If EXISTING listing: compare dealer_price and in_stock against current record
- If price changed OR stock status changed: update Dealer Listing, insert new row into gd_price_history
- If no changes: update last_seen_in_feed timestamp only — no history write, no OpenSearch update
- After full feed processed: any Dealer Listing whose last_seen_in_feed is older than current run start time — set listing_status = Out of Stock
- After full feed processed: trigger price alert check for all UPCs where price decreased this run (see Section 4.7)
- Queue OpenSearch re-index for all updated Dealer Listings
- After all listings for a UPC are updated in this run: recalculate and update denormalized pricing fields on the Product record — total_min_price (lowest dealer_price + shipping_cost across all active in-stock listings), free_shipping_available (true if any active in-stock listing has free_shipping = true), min_cpr (lowest CPR across all active in-stock ammo listings — null for non-ammo). These fields power the OpenSearch price and shipping filters.
- Write run summary to gd_dealer_import_log


## 3.9 Unmatched UPC Table

CREATE TABLE gd_unmatched_upcs (
id            INT AUTO_INCREMENT PRIMARY KEY,
upc           VARCHAR(20)  NOT NULL,
dealer_id     INT          NOT NULL,
first_seen    DATETIME     NOT NULL,
last_seen     DATETIME     NOT NULL,
occurrence_count INT       DEFAULT 1,
admin_excluded   TINYINT(1) DEFAULT 0,
UNIQUE KEY uq_upc_dealer (upc, dealer_id),
INDEX idx_upc    (upc)
);


- occurrence_count increments every time this UPC appears in this dealer's feed
- admin_excluded = 1 means admin has deliberately excluded this UPC — no longer shown in review queue
- Admin can create a Product record directly from this table by clicking 'Add to Catalog' on any row


## 3.10 Dealer Import Log Table

CREATE TABLE gd_dealer_import_log (
id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
dealer_id           INT      NOT NULL,
subscription_tier   VARCHAR(20),
run_start           DATETIME NOT NULL,
run_end             DATETIME NULL,
records_total       INT      DEFAULT 0,
records_created     INT      DEFAULT 0,
records_updated     INT      DEFAULT 0,
records_unchanged   INT      DEFAULT 0,
records_unmatched   INT      DEFAULT 0,
price_drops         INT      DEFAULT 0,
price_increases     INT      DEFAULT 0,
alerts_triggered    INT      DEFAULT 0,
status              ENUM('running','completed','failed') DEFAULT 'running',
error_log           LONGTEXT,
INDEX idx_dealer    (dealer_id),
INDEX idx_start     (run_start)
);


## 3.11 Dealer Dashboard
Accessible at /dealers/dashboard. Visible only to members in the Dealers member group with an active subscription.

Overview Tab
- Active subscription tier, next billing date, cancel/upgrade links
- Total active listings, out-of-stock listings, unmatched UPC count
- Last feed import: time, status, records processed, error count
- Total click-throughs to dealer listings in last 7 / 30 days
- Click trend sparkline chart

Feed Settings Tab
- View and edit feed URL, format, field mapping
- Manual import trigger — runs immediately
- Import history: last 50 runs with stats and expandable error log

Listings Tab
- Paginated table: Product Name, UPC, Current Price, Stock Status, Condition, Last Updated
- Filter by: In Stock, Out of Stock, Active, Discontinued, Suspended
- Search by UPC or product name
- Export listings as CSV

Unmatched UPCs Tab
- Table of all UPCs from dealer feed with no catalog match
- Columns: UPC, First Seen, Times Seen, Exclude button
- Download full list as CSV
- Note when a previously unmatched UPC gets added to the catalog

Analytics Tab — Pro and Enterprise only
- Click-through count per listing — last 30 days
- Price competitiveness: for each UPC they carry, are they lowest / mid / high
- Top 20 most-clicked listings
- Revenue opportunity indicator: products where they are not lowest price with gap amount shown

Subscription Tab
- Current plan, features included, next billing amount and date
- Upgrade / downgrade options
- Billing history via IPS Commerce
- Cancel subscription — triggers confirmation flow, sets listing_status = Suspended for all listings on cancellation


## 3.12 Admin — Dealer Management Panel
ACP > GD Dealer Manager:
- All dealers listed with tier, status, listing count, last import run, MRR contribution
- Per-dealer detail view: full import log, listing table, billing history, unmatched UPCs
- Suspend / unsuspend dealer — immediately hides all their listings from frontend
- Force feed import for any dealer
- MRR dashboard: total MRR, breakdown by tier, new signups last 30 days, churn last 30 days
- Unmatched UPCs across all dealers — sortable by occurrence count, with Add to Catalog action

# 4. Plugin 3 — GD Price Comparison
The Price Comparison plugin is the consumer-facing layer. It presents aggregated dealer listings in a searchable, filterable interface and includes every differentiating feature that gun.deals lacks. All differentiators listed in Section 4 are Phase 1 — they ship at launch.


## 4.1 Product Detail Page
URL pattern: /products/{category-slug}/{upc-title-slug}
The core page of the platform. Renders data from both the Products database (Plugin 1) and the Dealer Listings database (Plugin 2).


### 4.1.1 Product Header Block
- Product image — primary image from master catalog, click to expand full size
- Image gallery strip if additional_images contains multiple URLs
- Product title, brand, model
- Category breadcrumb: Home > Category > Subcategory
- Key spec pills: Caliber | Action Type | Barrel Length | Capacity | Finish
- Badge row: [NFA ITEM] in red if nfa_item = true | [FFL REQUIRED] in orange if requires_ffl = true
- UPC displayed in small text for reference
- 'Watch This Product' button — triggers watchlist modal for logged-in users


### 4.1.2 Price Comparison Table
Lists all active Dealer Listings for this UPC sorted by Total Cost ascending by default. Pro and Enterprise dealer listings appear before Basic listings when prices are equal — this is the priority placement benefit.


- Lowest total cost row gets a 'Best Price' green badge in the Dealer column
- Out-of-stock listings shown in a collapsed 'Out of Stock' section below in-stock listings
- Logged-out users see a prompt to log in to save watchlist and set state preferences
- 'Report a pricing error' link below the table — routes to moderation queue

Per-Listing Report Function
- Each row in the price comparison table has a Report button — uses IPS native content reporting system
- IPS report dialog presents pre-filled report reasons specific to dealer listings: Incorrect Price | Item Not Available | Wrong Product | Shipping Cost Incorrect | Dealer No Longer Active | Other
- Report submitted to IPS moderation queue — admin reviews via standard IPS report center
- When a listing receives 3 or more reports: listing automatically flagged in admin queue with elevated priority
- Reported listings remain visible to users — admin decides whether to suspend the listing after review
- Dealer notified via IPS notification when one of their listings is reported — shows report reason but not reporter identity
- Report button is available to both logged-in and guest users — guest reports tracked by IP to prevent abuse
- Dealers in the Enterprise tier receive an immediate notification on report — Basic and Pro receive a daily digest of reports


### 4.1.3 Price History Chart — PHASE 1 DIFFERENTIATOR
Chart.js line chart rendered below the comparison table. This feature does not exist on gun.deals.

- Time range selector: 7 days | 30 days | 90 days | 1 year | All Time
- One line per dealer (color-coded, dealer name in legend, each line toggleable on/off)
- Y axis: price in dollars
- X axis: date
- Hover tooltip: exact price, dealer name, date, stock status at that point
- 'Lowest Ever' price annotated with a dashed horizontal line and label
- 'Current Best Price' annotated on the right axis
- Data sourced from gd_price_history table — one data point per price change event


### 4.1.4 Community Section
- IPS native comment system embedded below product content
- Users discuss product quality, share experiences, post additional deal links
- Upvote/downvote on individual comments
- Moderators can pin or remove comments


## 4.2 Ammo-Specific Display
When is_ammo = true on the product record, additional display logic activates. Ammo pricing has two distinct views depending on whether the user includes shipping in their cost-per-round calculation.

True Cost-Per-Round Toggle
- A prominent toggle above the ammo comparison table reads: 'Include Shipping in CPR' — OFF by default
- When OFF: CPR = dealer_price / rounds_per_box. Shows item cost only.
- When ON: CPR = (dealer_price + shipping_cost) / rounds_per_box. Shows true delivered cost per round.
- Toggle state is remembered in the user's session — if they turn it on, it stays on while they browse ammo
- Toggle clearly labeled so users understand what they are seeing: 'Showing price per round including shipping' or 'Showing price per round excluding shipping'
- Dealers with free_shipping = true are unaffected — their CPR is the same whether toggle is on or off. A 'FREE SHIP' indicator shown next to their CPR so users see the advantage clearly.
- Dealers with null shipping_cost (varies): when toggle is ON their rows show CPR with a '+ ship' note and sort after dealers with known shipping costs — same behavior as the main total cost column

Ammo Comparison Table Columns
- CPR column always visible — calculated per toggle state above
- Comparison table default sort: CPR ascending (cheapest per round first) — regardless of toggle state
- Total column for ammo shows full box price + shipping, not CPR — shown as secondary reference
- Rounds per box displayed in a column so users can compare pack sizes at a glance
- Price history chart Y axis shows CPR — toggle state applies to the chart as well
- Bulk quantity pricing: if dealer offers multiple box quantities, display CPR for each quantity tier in an expandable row


## 4.3 Total Cost with Shipping Calculator — PHASE 1 DIFFERENTIATOR
The comparison table Total column always reflects the true delivered cost to the buyer — not just the item price. This is implemented as follows:
- If free_shipping = true: Total = dealer_price. Row displays 'FREE' in the shipping column in green.
- If shipping_cost has a value: Total = dealer_price + shipping_cost
- If shipping_cost is null (varies): Total column shows dealer_price with a '+ shipping' note. These rows sort after rows with known totals.
- For ammo: Total CPR uses the same logic applied per round — see Section 4.2 for toggle behavior
- Default sort is always Total ascending — cheapest delivered price first

Price Filter Behavior with Shipping
- The price range filter (Section 4.4) operates on Total — not on dealer_price alone
- A user filtering '$400–$500' sees products where the cheapest available dealer's total delivered cost (price + shipping) falls within that range
- This is intentional — a $380 gun with $30 shipping is not actually cheaper than a $395 gun with free shipping. The filter respects true cost.
- Filter label reads 'Total Price (inc. shipping)' to make this explicit
- Products where all dealers have null shipping_cost are included in price filter results at their item price — labeled with a note

Shipping Rate Filter
- A dedicated Shipping filter in the sidebar lets users filter listings by shipping cost independent of item price
- Options: Free Shipping Only | Under $10 | Under $20 | Any
- Shipping filter applies at the listing level — a product with at least one dealer matching the shipping filter appears in results
- When Shipping filter is active, non-matching dealer listings are hidden from the comparison table on the product page, but the product itself still appears in browse/search
- Filter label and tooltip explain: 'Filters to products where at least one dealer offers this shipping rate'


## 4.4 Category Browse
URL pattern: /products/{category-slug}

Left Sidebar Filters — All OpenSearch-powered

Product Listing Cards
- Product image thumbnail
- Title and brand
- Lowest current total price across all active dealers — labeled 'From $X (inc. shipping)'
- Shipping indicator on the card: 'Free Shipping Available' badge if any dealer offers free_shipping = true for this product
- For ammo: lowest CPR shown as primary price display — 'From X.X¢/rd' — with toggle state applied
- Number of active dealers carrying this item
- In-stock indicator — 'X dealers in stock'
- Key spec pills: caliber, action type
- [NFA] and [FFL REQUIRED] badges where applicable
- [STATE RESTRICTED] badge if user's state is set and any restriction applies

Sort Options
- Lowest Total Price (default) — sorts by cheapest dealer's total delivered cost (price + shipping)
- Lowest Item Price — sorts by cheapest dealer's item price before shipping
- Lowest CPR — ammo category only — sorts by cost per round per toggle state
- Free Shipping First — products with at least one free-shipping dealer ranked first, then by price within each group
- Most Dealers
- Newest Added to Catalog
- Most Viewed


## 4.5 State Compliance Flags — PHASE 1 DIFFERENTIATOR
gun.deals has no reliable state compliance layer. This is a meaningful differentiator especially for users in highly regulated states.

State Detection and Storage
- First visit: prompt displayed asking user to set their state — stored in cookie and session
- Logged-in users: state stored in IPS profile field — persists across sessions and devices
- State selector widget displayed persistently in site header — user can change at any time
- State is never required — all features work without it, state-aware features enhance experience when set

Restriction Data Sources
- Dealer-level restrictions: ships_to_restriction field on each Dealer Listing — populated from dealer feed or dealer dashboard manual entry
- Product-level restrictions: a product restriction table seeded from existing State Shipping Restrictions plugin data — covers known universal restrictions such as certain magazine capacities in CA, NY, NJ, MA, CT, MD, HI, CO
- NFA state restrictions: a separate NFA restrictions table covering which states restrict or ban specific NFA item types

Restriction Display Rules
- Listing level: if user state is set and dealer ships_to_restriction includes that state — show orange [RESTRICTED] badge in Ships To column
- Product level: if product has a known restriction for user's state — show orange [STATE RESTRICTED] badge on the product card and product header
- NFA items: if user's state restricts the NFA item type — show a red warning banner at the top of the product page
- Listings are NOT hidden or suppressed — badge is shown and user decides. This is consistent with how compliance works: the dealer is responsible, not the platform.
- 'Ships to My State Only' filter in sidebar hides listings with restrictions — opt-in, not default


## 4.6 ATF / NFA Indicators — PHASE 1 DIFFERENTIATOR
Products with nfa_item = true receive special treatment throughout the platform:
- Red [NFA ITEM] badge on all product cards, search results, and product page headers
- Product page includes an NFA Information section: which ATF form is required (Form 4 for consumer transfers), current NICS wait time context, $200 tax stamp cost noted
- Dealer listing for NFA items shows whether that dealer is SOT-qualified — a field on the dealer profile that admin sets manually
- NFA item filter in sidebar: default is 'include NFA items' — user can toggle to show only NFA or hide all NFA
- State-specific NFA warning: if user's state restricts or bans the NFA item type (e.g. suppressor-banned states) — red warning banner shown on product page


## 4.7 FFL Locator — PHASE 1 (Standalone)
An FFL dealer locator embedded on firearm and NFA product pages to help users find a nearby transfer dealer. Standalone implementation at launch — GunRack API integration is a Phase 2 enhancement.

Data Source
- ATF publishes the full FFL dealer list as a public dataset — updated monthly
- Import ATF FFL data into a custom gd_ffl_dealers table on the platform
- Geocode each FFL address to latitude/longitude on import
- Monthly scheduled task to refresh from ATF data

Frontend
- 'Find an FFL Near Me' section on all requires_ffl = true product pages
- User enters ZIP code — returns nearest 10 FFL dealers within configurable radius (default 25 miles)
- Results show: dealer name, address, distance, license type, phone if available
- Google Maps embed showing dealer pins (Google Maps API key required — configured in ACP)
- 'Copy FFL Info' button to help users share their dealer's details with the retailer

FFL Dealers Table

CREATE TABLE gd_ffl_dealers (
id            INT AUTO_INCREMENT PRIMARY KEY,
lic_regn      VARCHAR(5),
lic_dist      VARCHAR(5),
lic_cnty      VARCHAR(10),
lic_type      VARCHAR(5),
lic_xprdte    VARCHAR(10),
lic_seqn      VARCHAR(10),
licensee_name VARCHAR(200),
business_name VARCHAR(200),
premise_street VARCHAR(200),
premise_city  VARCHAR(100),
premise_state VARCHAR(2),
premise_zip   VARCHAR(10),
mail_street   VARCHAR(200),
mail_city     VARCHAR(100),
mail_state    VARCHAR(2),
mail_zip      VARCHAR(10),
voice_phone   VARCHAR(20),
lat           DECIMAL(10,7),
lng           DECIMAL(10,7),
last_updated  DATETIME,
INDEX idx_zip (premise_zip),
INDEX idx_state (premise_state),
SPATIAL INDEX idx_coords (lat, lng)
);


## 4.8 Search
- OpenSearch-powered site-wide search via search bar in site header
- Dealer Listings indexed with shipping_cost (float) and free_shipping (boolean) fields to power the shipping rate filter and total cost sort — these fields are per-listing not per-product, requiring a nested or join query strategy at search time
The developer must determine the correct OpenSearch query strategy for filtering products by their cheapest listing's total cost. Recommended approach: a pre-computed total_min_price and free_shipping_available field denormalized onto the Product record and updated on every dealer feed import. This avoids expensive nested queries at search time.
- Searches across: title, brand, model, caliber, UPC, description — weighted by field importance
- Typo tolerance — OpenSearch fuzzy matching handles common misspellings
- Autocomplete suggestions appear after 3 characters — shows matching product titles and category shortcuts
- Direct UPC entry — if exact UPC match found, redirect directly to product page
- Results page uses same facet sidebar as category browse
- 'No results' page suggests related categories and most viewed products
- Search query logged for analytics — surfaced in admin dashboard as 'Top Searches' and 'Zero Result Searches'


## 4.9 Price Alerts and Watchlist

User-Facing
- 'Watch This Product' button on every product page — opens modal for logged-in users
- Modal: shows current lowest price, optional target price field, confirm button
- If target price set: alert fires when any dealer's price drops to or below that amount
- If no target price: alert fires on any price decrease by any dealer for this UPC
- /account/watchlist — paginated list of all watched products with current lowest price and target price
- Remove watch or edit target price from watchlist page
- 'Notify me when back in stock' option for out-of-stock products — fires when any dealer lists in_stock = true

Alert Trigger Logic — called after every dealer feed import
- Plugin 2 feed importer collects a list of all UPCs that had a price decrease in this run
- For each decreased UPC: query gd_watchlist for all member_ids watching that UPC
- For each watching member: if new lowest price <= their target_price (or they have no target_price): queue alert
- Alert queued as IPS notification — fires on-site bell notification and email
- Email content: product image, product name, previous price, new price, dealer name, direct link to product page
- Update last_alerted on watchlist record to prevent duplicate alerts within 24 hours

Watchlist Table

CREATE TABLE gd_watchlist (
id              INT AUTO_INCREMENT PRIMARY KEY,
member_id       INT             NOT NULL,
upc             VARCHAR(20)     NOT NULL,
target_price    DECIMAL(10,2)   NULL,
notify_in_stock TINYINT(1)      DEFAULT 0,
created_at      DATETIME        NOT NULL,
last_alerted    DATETIME        NULL,
UNIQUE KEY uq_member_upc (member_id, upc),
INDEX idx_upc    (upc),
INDEX idx_member (member_id)
);


## 4.10 Dealer Profiles
Public page at /dealers/{dealer-slug}:
- Store name, logo (uploaded by dealer), website link, address, FFL number
- Subscription tier badge — if dealer opts in to display it
- Aggregate star rating with count (minimum 3 ratings required to display)
- Breakdown of rating categories: Pricing Accuracy, Shipping Speed, Customer Service
- Total active listings count
- Recently added/updated listings grid
- Community reviews section with reviewer name, date, text, star ratings
- 'Report this dealer' link — routes to admin moderation queue


## 4.11 Dealer Rating System
- Any registered member can rate a dealer 1–5 stars across three categories: Pricing Accuracy, Shipping Speed, Customer Service
- Optional written review (280 character minimum, 2000 maximum)
- Written reviews held for admin moderation before display — star ratings show immediately
- Weighted average of all three categories displayed as overall score
- Overall score displayed next to dealer name in price comparison tables
- Admin can remove abusive reviews from moderation queue
- One rating per member per dealer — member can update their rating


## 4.12 Outbound Click Tracking

CREATE TABLE gd_click_log (
id           BIGINT AUTO_INCREMENT PRIMARY KEY,
listing_id   INT         NOT NULL,
upc          VARCHAR(20) NOT NULL,
dealer_id    INT         NOT NULL,
member_id    INT         NULL,
user_state   VARCHAR(2)  NULL,
clicked_at   DATETIME    NOT NULL,
INDEX idx_listing  (listing_id),
INDEX idx_dealer   (dealer_id),
INDEX idx_clicked  (clicked_at)
);


- Every Buy button routes through /go/{listing-id} — 302 redirect to dealer URL
- Click logged before redirect executes
- member_id = null for guest clicks
- Daily aggregation task totals clicks per listing per day — surfaced in dealer analytics

# 5. Technical Requirements


## 5.1 Server Requirements


## 5.2 OpenSearch — Self-Hosted Configuration
OpenSearch is used as the search engine instead of Elasticsearch. OpenSearch is fully API-compatible with Elasticsearch, meaning IPS's built-in Elasticsearch integration works without modification — the endpoint URL is simply pointed at the local OpenSearch instance instead of an Elasticsearch or Elastic Cloud endpoint. This eliminates all Elastic Cloud subscription costs.


### 5.2.1 OpenSearch Installation Requirements
- Java 11 or higher installed on VPS (OpenSearch dependency)
- OpenSearch 2.x installed and running as a system service
- OpenSearch listening on localhost:9200 — not exposed to public internet
- Security plugin configured: basic auth enabled for the IPS connection
- IPS ACP > Search > Search Engine set to Elasticsearch, endpoint set to http://localhost:9200
- Test query run from IPS ACP confirming connection before any plugin development begins
- vm.max_map_count kernel parameter set to 262144 (required by OpenSearch)

⚠  OpenSearch must never be exposed on a public port. It must only be accessible on localhost or a private VPC network. The developer must verify this is correctly firewalled before launch.


## 5.3 IPS Prerequisites
- IPS Community Suite 4.7.x or higher
- IPS Elasticsearch integration enabled in ACP (pointed at OpenSearch endpoint) and confirmed working before Plugin 1 development begins
- Redis configured as IPS cache handler
- IPS Commerce enabled with Stripe gateway configured and tested with a real transaction
- IPS background task processing confirmed working — either via cron or IPS task runner
- Google Maps API key obtained and configured — required for FFL locator map embed
- Member groups created: Dealers (custom group — created as part of Plugin 2 install)
- SSL certificate active — required for Stripe


## 5.4 All Custom Database Tables

All custom tables must use the IPS table prefix and must be created via the plugin install routine and dropped via the plugin uninstall routine. No manual table creation.


## 5.5 Background Task Schedule

# 6. Acceptance Criteria and Launch Checklist
Every item in this section must be verified and checked off before any plugin is considered complete and before the platform launches. The developer must demonstrate each item working against real data, not test data.


## 6.1 Infrastructure Prerequisites
- OpenSearch installed and running as a system service on VPS
- OpenSearch NOT exposed on a public port — confirmed with firewall rule check
- vm.max_map_count kernel parameter set to 262144 — confirmed
- IPS Elasticsearch integration in ACP pointed at OpenSearch localhost:9200 endpoint — test search query confirmed working
- Redis installed and confirmed as active IPS cache driver
- IPS Commerce active with Stripe live keys — test subscription created and charged
- IPS Commerce group promotion verified: test purchase adds Dealers secondary group, cancellation removes it — no manual admin step
- Dealer dashboard /dealers/dashboard returns 403 for a Registered Member not in the Dealers group
- Dealer dashboard fully accessible immediately after successful test subscription purchase
- Google Maps API key configured — FFL locator map rendering on test page
- SSL certificate active — required for Stripe
- Cron confirmed running IPS background tasks — verified in task log
- Automated database backup running — restore test completed successfully
- Founding dealer program: at least 2 FFL contacts confirmed for free 90-day trial before public launch


## 6.2 Plugin 1 Acceptance Criteria
- All six distributor feeds configured and importing without errors
- Conflict resolution rules verified: create a UPC present in two distributors with different field values and confirm correct winner per Section 2.2
- Field-level special rules verified individually: image resolution winner, longest description winner, highest MSRP winner, rounds_per_box conflict flagging
- nfa_item and requires_ffl conservative flags verified — if any source sets true, record shows true
- Admin manual override locks a field and subsequent imports do not overwrite it
- Conflict log writing a row for every detected conflict
- record_status = Admin Review triggered correctly for rounds_per_box conflicts
- OpenSearch index rebuilt — all products searchable by UPC, title, brand, caliber
- Admin dashboard showing accurate counts per distributor
- Import log showing accurate run stats


## 6.3 Plugin 2 Acceptance Criteria
- Dealer can register, select a tier, and complete Stripe checkout — subscription activates immediately
- Dealer member group assigned on activation
- API key generated and included in welcome email
- XML feed imported correctly — all fields parsed and mapped
- JSON feed imported correctly — all fields parsed and mapped
- CSV feed imported correctly — all fields parsed and mapped
- UPC matching correctly links dealer listing to product record
- Price change detected: gd_price_history row created, OpenSearch re-indexed
- No price change: gd_price_history NOT written, last_seen_in_feed updated only
- UPC not in catalog: logged to gd_unmatched_upcs, not imported
- Items missing from feed after run: listing_status set to Out of Stock
- Dealer suspension: all listings set to Suspended, hidden from all frontend queries
- Dealer dashboard showing accurate data across all tabs
- Pro and Enterprise analytics tabs visible only to correct tiers
- Basic dealers do not see analytics tab


## 6.4 Plugin 3 Acceptance Criteria
- Product detail page renders correctly from both Products and Dealer Listings databases
- Price comparison table sorted by total cost ascending by default
- Pro/Enterprise listings appear before Basic when prices are equal
- 'Best Price' badge on lowest total cost row
- Out-of-stock listings shown below in-stock listings in collapsed section
- Price history chart renders with accurate data points from gd_price_history
- 'Lowest Ever' annotation displayed correctly on chart
- Time range selector changes chart data correctly
- Ammo product: CPR calculated correctly and displayed as primary sort metric
- Total cost column: free shipping shows price only, flat rate adds shipping, null shows price + note
- Price range filter filters on total_min_price (inc. shipping) — verified with test listings at different shipping costs
- Shipping rate filter: 'Free Shipping Only' shows only products where free_ship_avail = true
- CPR range filter: filters correctly on min_cpr when toggle is OFF, min_cpr_shipped when toggle is ON
- True CPR toggle: OFF shows item-price CPR, ON shows shipping-inclusive CPR — both values accurate
- Free Shipping badge appears on listing cards for products where free_ship_avail = true
- Sort by 'Lowest Total Price' uses total_min_price — verified cheaper delivered total ranks first even if item price is higher
- Sort by 'Lowest Item Price' uses dealer_price only — ignores shipping cost
- Sort by 'Free Shipping First' ranks free-shipping products before paid-shipping products
- Denormalized fields update correctly: after a dealer feed import changes a price, total_min_price on the Product record updates within the same import run
- Report button on each listing row opens IPS report dialog with pre-filled reasons
- Third report on a listing triggers elevated priority flag in admin queue
- Enterprise dealer receives immediate notification on listing report — Basic/Pro receive daily digest
- State compliance: user sets state, restricted listings show [RESTRICTED] badge
- 'Ships to My State' filter hides restricted listings only — does not hide unrestricted listings
- [NFA ITEM] badge displays on all NFA products
- NFA information section renders on NFA product pages
- NFA state restriction warning displays for users in restricted states
- FFL locator: ZIP code entered returns nearby dealers on map and in list
- FFL locator: results are accurate against ATF data
- Search: keyword search returns relevant results
- Search: UPC direct entry redirects to correct product page
- Search: autocomplete suggestions appear after 3 characters
- Category browse: facet filters narrow results correctly
- Watchlist: user adds product, price drops on next feed import, alert email received
- Watchlist: target price alert fires only when price drops below target, not above
- 'Notify when back in stock' alert fires when in_stock changes from false to true
- Outbound click tracked: /go/{listing-id} logs click and redirects to dealer URL
- Dealer rating: user submits rating, aggregate score updates on dealer profile


## 6.5 Legal and Content
- Terms of Service updated to cover dealer feed submission and data usage agreements
- Privacy Policy updated to cover state cookie, watchlist data, click tracking
- Dealer Terms of Service presented and acceptance recorded during registration
- FFL number format validation tested against real FFL number formats — all region variants
- State restriction seed data imported from State Shipping Restrictions plugin and verified


# 7. Plugin 4 — GD Product Reviews
The Product Reviews plugin provides a full review system for products in the master catalog — separate from the commenting system and accessible from a dedicated Reviews Hub. This is a major differentiator — no firearms deal aggregator has a structured product review system with verified purchase tracking, media attachments, and product-type-specific rating categories.


## 7.1 Confirmed Design Decisions


## 7.2 Review Form Structure
The review form is completely uniform across all product types. The same fields, labels, and prompts are shown regardless of whether the product is a firearm, scope, ammunition, or holster. This simplifies both the frontend implementation and the user experience — reviewers see the same familiar form every time.

The product_type column is still stored on the review record (copied from the Product record at submission time) so future enhancements can add type-specific features without a schema migration. But the form itself makes no use of it at launch.


## 7.3 Review Submission
Eligibility
- Any registered member (not guest) can submit a review for any product in the catalog
- Members who have clicked a Buy link for that UPC receive a Verified Purchase badge on their review
- One review per member per UPC — member can edit their review, editing logs a revision history
- Review must include at least an overall star rating and 50 characters of written text to submit
- Images: up to 5 per review, max 5MB each, JPEG/PNG/WEBP accepted

Review Form Fields

Review Moderation
- Reviews are held for moderation before public display — admin reviews queue in ACP
- Admin can: Approve, Reject with reason, Request Edit (sends notification to reviewer with specific feedback)
- Approved reviews immediately visible on product page and indexed in OpenSearch
- Rejected reviews send notification to member with rejection reason — member can revise and resubmit
- Flagged reviews (community flagged) automatically enter moderation queue for re-review


## 7.4 Review Database Schema

CREATE TABLE gd_reviews (
id                  INT AUTO_INCREMENT PRIMARY KEY,
upc                 VARCHAR(20)   NOT NULL,
member_id           INT           NOT NULL,
verified_purchase   TINYINT(1)    DEFAULT 0,
status              ENUM('pending','approved','rejected','flagged') DEFAULT 'pending',
overall_rating      TINYINT       NOT NULL,
-- Single 1-5 star rating only. No sub-category rating columns.
product_type        VARCHAR(50)   NOT NULL,
-- Copied from Product record at submission time for prompt routing.
title               VARCHAR(150)  NOT NULL,
body                TEXT          NOT NULL,
pros                VARCHAR(500)  NULL,
cons                VARCHAR(500)  NULL,
would_recommend     TINYINT(1)    NULL,
usage_context       VARCHAR(50)   NULL,
time_owned          VARCHAR(50)   NULL,
-- rounds_fired removed: uniform form, no product-type-specific fields
images              TEXT          NULL,
-- JSON array of uploaded image paths. Max 5 images.
helpful_yes         INT           DEFAULT 0,
helpful_no          INT           DEFAULT 0,
dispute_status      ENUM('none','contested','resolved') DEFAULT 'none',
created_at          DATETIME      NOT NULL,
updated_at          DATETIME      NULL,
moderated_at        DATETIME      NULL,
moderated_by        INT           NULL,
rejection_reason    TEXT          NULL,
INDEX idx_upc       (upc),
INDEX idx_member    (member_id),
INDEX idx_status    (status),
INDEX idx_overall   (overall_rating),
INDEX idx_type      (product_type),
UNIQUE KEY uq_upc_member (upc, member_id)
);


CREATE TABLE gd_review_helpfulness (
id          INT AUTO_INCREMENT PRIMARY KEY,
review_id   INT NOT NULL,
member_id   INT NOT NULL,
helpful     TINYINT(1) NOT NULL,
voted_at    DATETIME NOT NULL,
UNIQUE KEY uq_review_member (review_id, member_id)
);


## 7.5 Product Page — Reviews Tab
Product detail pages gain a Reviews tab alongside the existing Price Comparison and Comments tabs.

Reviews Tab — Summary Block
- Overall star rating (average of all approved, non-resolved reviews) displayed large
- Total review count
- Star distribution bar chart: % of reviews at each star level (1–5)
- Recommendation percentage: % of reviewers who selected Would Recommend = Yes
- Recommendation percentage summary: 'X% of reviewers recommend this product'
- Verified Purchase count noted: 'X of Y reviews are verified purchases'
- 'Write a Review' button — opens review form inline for logged-in users, prompts login for guests

Reviews Tab — Individual Reviews
- Default sort: Most Helpful (helpful_yes - helpful_no descending)
- Sort options: Most Helpful, Most Recent, Highest Rated, Lowest Rated, Verified Only
- Filter options: Star rating filter (show only 5-star, 4-star, etc.), Verified Purchases Only, With Images
- Each review card shows: reviewer username, verified badge if applicable, overall stars, category stars, title, body, pros/cons, images, usage context, time owned, helpful vote buttons, flag button
- Resolved reviews shown at bottom of list with green [RESOLVED] badge — excluded from rating averages
- Contested reviews show dealer response thread inline — see Plugin 6 Section 9


## 7.6 Reviews Hub — /reviews
Top-level navigation section providing a dedicated home for product reviews independent of the price comparison interface. URL: /reviews

Hub Homepage
- Hero banner: 'Find real reviews from gun owners'
- Latest Reviews feed: most recently approved reviews across all products — shows product image, title, reviewer, star rating, review excerpt
- Top Rated Products this month: products with highest average rating and minimum 5 reviews
- Most Reviewed Products: products with highest review count
- Verified Purchase Reviews: feed of only verified purchase reviews
- Featured Review: admin-curated highlight of a high-quality review

Hub Navigation Tabs
- Latest Reviews — chronological feed of all approved reviews
- Top Rated — products sorted by average rating, filterable by category
- By Brand — brand directory with aggregate brand ratings and review counts
- By Category — category directory with aggregate ratings per category
- Most Helpful — reviews sorted by helpful vote score
- Verified Purchases — feed filtered to verified purchase reviews only

Hub Filtering and Search
- OpenSearch powers all hub pages — reviews are indexed alongside products
- Filter by: Category, Brand, Star Rating, Verified Purchase, Time Period, Usage Context
- Search: keyword search across review titles and bodies
- Brand page at /reviews/brand/{brand-slug}: aggregate brand rating, all reviews for that brand's products, top-rated models
- Category page at /reviews/category/{category-slug}: aggregate category rating, latest reviews, top-rated products in category


## 7.7 OpenSearch Index — Reviews


## 7.8 Admin — Reviews Management
- Moderation queue: all pending reviews with Approve, Reject, Request Edit actions
- Flagged reviews queue: community-flagged reviews requiring re-review
- Review analytics: review count by product, by brand, by category, by month
- Top reviewers leaderboard: members with most approved reviews and highest helpful vote scores
- Edit or remove any review
- Featured Review selection for hub homepage

# 8. Plugin 5 — GD Rebates
The Rebates plugin is the only comprehensive firearms rebate aggregator in the market. No equivalent exists on gun.deals or any major competitor. It covers manufacturer rebates for firearms, ammunition, optics, and accessories with full submission tracking and community success rate reporting.


## 8.1 Confirmed Design Decisions

⚠  The scraper must respect robots.txt and rate limits for all manufacturer websites. It should identify itself with a legitimate user agent string. The scraper extracts publicly available rebate information — this is legally distinct from scraping competitive pricing data. However, if any manufacturer sends a cease and desist, their URL must be immediately removable from the scraper config without code changes. This is why admin-managed URLs are required.


## 8.2 Rebate Scraper Architecture
The scraper is a background task that visits manufacturer rebate pages nightly, extracts structured rebate data using configured extraction rules, and creates or updates rebate records. Because manufacturer rebate pages have no standard structure, each manufacturer URL requires its own extraction configuration.


### 8.2.1 Manufacturer URL Registry
Admin manages manufacturer scraper targets in ACP > GD Rebates > Scraper Targets:


### 8.2.2 Extraction Configuration
Each manufacturer URL has a JSON extraction config that maps page elements to rebate fields. The developer must build a flexible extraction engine that supports both CSS selector and XPath extraction:


// Example extraction config for a manufacturer rebate page
{
"rebate_container": ".rebate-item",
"fields": {
"title":               { "selector": ".rebate-title", "type": "text" },
"rebate_amount":       { "selector": ".rebate-value", "type": "currency" },
"rebate_type":         { "selector": ".rebate-type",  "type": "text", "map": {"Mail-In Rebate":"mail_in","Instant Savings":"instant"} },
"eligible_models":     { "selector": ".eligible-products", "type": "text" },
"start_date":          { "selector": ".offer-start", "type": "date", "format": "MM/DD/YYYY" },
"end_date":            { "selector": ".offer-end",   "type": "date", "format": "MM/DD/YYYY" },
"submission_deadline": { "selector": ".submit-by",   "type": "date", "format": "MM/DD/YYYY" },
"rebate_form_url":     { "selector": "a.rebate-form", "type": "href" },
"description":         { "selector": ".rebate-details", "type": "html_to_text" }
}
}


Not every manufacturer page will be cleanly parseable. When the scraper cannot confidently extract required fields (title, rebate_amount, end_date), it must NOT create a partial record. Instead it logs a parse failure for admin review. Partial rebate records are worse than missing rebates.


### 8.2.3 Scraper Process Logic
- Scheduled task runs per configured frequency (nightly default) — fetches all active manufacturer targets from registry
- For each target: check robots.txt — if crawling disallowed, skip and log
- Fetch rebate page with configured rate limit delay
- Apply extraction config — extract all rebate containers from page
- For each extracted rebate: validate required fields (title, end_date, rebate_amount) are present and parseable
- If validation fails: log parse failure with page snapshot URL for admin review — do not create record
- Generate a deduplication hash from: manufacturer + title + end_date + rebate_amount
- Check if hash already exists in gd_rebates — if yes and status is active: update fields if changed, skip if unchanged
- If hash is new: create new rebate record with source = 'scraped' and submitter = 0 (system)
- If manufacturer is Known Manufacturer: set status = active immediately
- If manufacturer is not Known Manufacturer: set status = pending, add to admin approval queue
- Check all existing scraped rebates for this manufacturer — any not seen in this crawl that are still active: flag for review (rebate may have been removed from manufacturer site)
- Write crawl summary to gd_scrape_log


### 8.2.4 Deduplication Between Scraped and Community Submissions
When a community member submits a rebate that was already scraped, or vice versa:
- Deduplication hash checked on community submission — if scraped record exists: community submission merged into existing record, submitter credited as 'additional source'
- If community submission arrives before scraper finds the rebate: community record created normally, scraper later finds same rebate, merges data and updates source to 'scraped + community'
- If community submission has additional detail the scraper missed (e.g. step-by-step instructions): admin prompted to review and merge the extra detail into the scraped record


### 8.2.5 Scraper Log Table

CREATE TABLE gd_scrape_log (
id                  INT AUTO_INCREMENT PRIMARY KEY,
manufacturer        VARCHAR(150) NOT NULL,
scrape_url          VARCHAR(500) NOT NULL,
run_at              DATETIME     NOT NULL,
rebates_found       INT          DEFAULT 0,
rebates_created     INT          DEFAULT 0,
rebates_updated     INT          DEFAULT 0,
rebates_unchanged   INT          DEFAULT 0,
parse_failures      INT          DEFAULT 0,
status              ENUM('success','partial','failed','robots_blocked') DEFAULT 'success',
error_log           TEXT         NULL,
INDEX idx_mfr       (manufacturer),
INDEX idx_run       (run_at)
);


## 8.3 Rebate Database Schema

CREATE TABLE gd_rebates (
id                  INT AUTO_INCREMENT PRIMARY KEY,
manufacturer        VARCHAR(150)  NOT NULL,
brand               VARCHAR(150)  NOT NULL,
title               VARCHAR(255)  NOT NULL,
description         TEXT          NOT NULL,
rebate_amount       DECIMAL(10,2) NULL,
rebate_type         ENUM('mail_in','instant','online','combo') NOT NULL,
product_type        ENUM('firearm','ammo','optic','accessory','suppressor','any') NOT NULL,
eligible_upcs       TEXT          NULL,
eligible_models     TEXT          NULL,
start_date          DATE          NOT NULL,
end_date            DATE          NOT NULL,
submission_deadline DATE          NULL,
rebate_form_url     VARCHAR(500)  NULL,
rebate_pdf_url      VARCHAR(500)  NULL,
manufacturer_url    VARCHAR(500)  NULL,
submission_steps    TEXT          NULL,
status              ENUM('active','expired','archived','pending','rejected') DEFAULT 'pending',
source              ENUM('scraped','community','scraped_community') DEFAULT 'community',
dedup_hash          VARCHAR(64)   NULL,
-- SHA256 of manufacturer+title+end_date+rebate_amount for deduplication
submitted_by        INT           NOT NULL,
-- 0 = system (scraped). Member ID if community submitted.
verified_by         INT           NULL,
created_at          DATETIME      NOT NULL,
expires_archived_at DATETIME      NULL,
flag_count          INT           DEFAULT 0,
INDEX idx_manufacturer (manufacturer),
INDEX idx_brand        (brand),
INDEX idx_type         (product_type),
INDEX idx_status       (status),
INDEX idx_end_date     (end_date)
);


CREATE TABLE gd_rebate_tracking (
id              INT AUTO_INCREMENT PRIMARY KEY,
rebate_id       INT     NOT NULL,
member_id       INT     NOT NULL,
status          ENUM('saved','submitted','received','rejected','expired') DEFAULT 'saved',
submitted_date  DATE    NULL,
received_date   DATE    NULL,
notes           TEXT    NULL,
created_at      DATETIME NOT NULL,
updated_at      DATETIME NULL,
UNIQUE KEY uq_rebate_member (rebate_id, member_id),
INDEX idx_rebate  (rebate_id),
INDEX idx_member  (member_id)
);


CREATE TABLE gd_rebate_flags (
id          INT AUTO_INCREMENT PRIMARY KEY,
rebate_id   INT          NOT NULL,
member_id   INT          NOT NULL,
reason      ENUM('expired','incorrect_amount','broken_link','duplicate','other') NOT NULL,
notes       TEXT         NULL,
flagged_at  DATETIME     NOT NULL,
reviewed    TINYINT(1)   DEFAULT 0,
UNIQUE KEY uq_rebate_member (rebate_id, member_id)
);


## 8.4 Rebate Submission — Community Form
Any registered member can submit a rebate at /rebates/submit. Admin reviews all submissions before they go live.


## 8.5 Rebate Detail Page
URL: /rebates/{id}-{rebate-slug}

Header Block
- Manufacturer name and logo if available
- Rebate title and amount displayed prominently
- Source label: [AUTO-DETECTED] with manufacturer page link shown for scraped rebates. [COMMUNITY SUBMITTED] with submitter username shown for community rebates. [AUTO-DETECTED + COMMUNITY VERIFIED] for merged records.
- Rebate type badge: [MAIL-IN] [INSTANT] [ONLINE] [COMBINATION]
- Status badge: [ACTIVE] in green or [EXPIRES IN X DAYS] in orange or [EXPIRED] in red
- Start date, end date, submission deadline displayed

Step-by-Step Instructions Block
- Numbered steps for submitting the rebate — entered by submitter, editable by admin
- Direct links to rebate form and PDF prominently displayed
- 'Copy Rebate Instructions' button — copies all steps to clipboard for easy reference

User Tracking Block — logged in users only
- Status selector: Saved / Submitted / Received / Rejected — user updates as they progress
- Date fields: When did you submit? When did you receive?
- Notes field: personal notes about the submission
- 'I submitted this rebate' button for quick status update

Community Success Rate Block
- Total community tracking count for this rebate
- Success rate: % of trackers who marked status = Received
- Average days to receive: calculated from submitted_date to received_date across all trackers
- Status breakdown bar: % Submitted / % Received / % Rejected — visual indicator of rebate reliability
- Recent activity feed: 'MemberX received this rebate 3 days ago' (anonymized to username only)

Linked Products Block
- If eligible_upcs or eligible_models are populated: show matching product cards from the catalog
- Each linked product shows current lowest dealer price and a Buy button
- 'Buy this product + claim the rebate' effective price displayed: lowest price minus rebate amount


## 8.6 Rebates Hub — /rebates

Hub Homepage
- Active rebates count and total potential savings displayed in hero
- Featured Rebate: admin-selected highlight — high-value or expiring soon
- Expiring Soon: rebates ending within 7 days
- Newest Rebates: most recently added
- By Product Type: quick links to Firearm Rebates, Ammo Rebates, Optic Rebates, Accessory Rebates
- Top Manufacturers: brands with the most active rebates

Hub Browse Filters
- Product Type: Firearm / Ammo / Optic / Accessory / Suppressor
- Rebate Type: Mail-In / Instant / Online / Combination
- Brand / Manufacturer: multi-select
- Rebate Amount: range slider
- Status: Active / Expiring Soon / Expired
- Sort: Highest Value / Ending Soonest / Newest / Highest Success Rate

Expired / Archived
- Expired rebates remain visible with [EXPIRED] badge for 30 days
- After 30 days: status set to archived — removed from active browse but accessible via direct URL and search
- Archived tab on hub: searchable archive of all past rebates — useful for users researching manufacturer rebate history


## 8.7 Flagging and Quality Control
- Any logged-in user can flag a rebate as expired, incorrect amount, broken link, duplicate, or other
- Flagging requires selecting a reason and optionally adding a note
- flag_count on rebate record increments on each unique flag
- When flag_count reaches 3: rebate automatically enters admin review queue
- Admin reviews flagged rebates: can Confirm Active, Mark Expired, Edit, or Remove
- Submitter notified when their rebate is flagged or actioned by admin


## 8.8 Admin — Rebates Management
- Submission queue: all pending community submissions with Approve, Edit+Approve, Reject actions
- Scraper approval queue: new rebates from unknown manufacturers requiring admin review before going live
- Scraper targets management: full CRUD for manufacturer URL registry — add, edit, disable, delete targets
- Scrape log browser: per-manufacturer crawl history with success rates, parse failure details, robots.txt blocks
- Active rebates list: all live rebates with source badge (scraped/community/both), edit, expire, archive actions
- Flag queue: rebates with flag_count >= 3 requiring review
- Deduplication conflicts: cases where scraper and community submission found the same rebate — admin reviews and merges
- Expiry automation: daily task checks end_date — sets status = expired when end_date passes
- Archive automation: 30 days after expiry sets status = archived
- Analytics: most tracked rebates, highest success rates, most flagged manufacturers, scraper coverage by manufacturer
- Featured Rebate selection for hub homepage
- Bulk import tool: admin can import multiple rebates from CSV — useful during major rebate seasons

# 9. Plugin 6 — GD Dealer Review Dispute System
The Dealer Review Dispute System provides a transparent, structured process for dealers to contest negative reviews, communicate with customers, and have resolved issues reflected in their rating. This system is designed to be fully public — every interaction is visible to all site visitors. The goal is not to hide bad reviews but to demonstrate dealer accountability and responsiveness. A dealer who resolves issues well should be rewarded in their rating. A dealer who ignores disputes should be penalized by the visible lack of response.


## 9.1 Confirmed Design Decisions

The resolved review being excluded from the star average rather than hidden is a deliberate choice. It rewards dealers who fix problems without erasing the record that a problem existed. Customers can read the full story and judge for themselves. This is more trustworthy than a system that simply removes bad reviews.


## 9.2 Dispute Lifecycle


## 9.3 Dispute Database Schema

CREATE TABLE gd_review_disputes (
id                  INT AUTO_INCREMENT PRIMARY KEY,
review_id           INT           NOT NULL,
dealer_id           INT           NOT NULL,
customer_id         INT           NOT NULL,
status              ENUM('awaiting_dealer','dealer_responded','customer_replied',
'resolved','expired_no_response','expired_no_resolution')
DEFAULT 'awaiting_dealer',
dealer_response_due DATETIME      NOT NULL,
customer_reply_due  DATETIME      NULL,
resolved_at         DATETIME      NULL,
resolved_by         INT           NULL,
created_at          DATETIME      NOT NULL,
INDEX idx_review    (review_id),
INDEX idx_dealer    (dealer_id),
INDEX idx_status    (status)
);


CREATE TABLE gd_dispute_messages (
id              INT AUTO_INCREMENT PRIMARY KEY,
dispute_id      INT           NOT NULL,
author_id       INT           NOT NULL,
author_type     ENUM('dealer','customer','admin') NOT NULL,
message         TEXT          NOT NULL,
is_official     TINYINT(1)    DEFAULT 0,
created_at      DATETIME      NOT NULL,
edited_at       DATETIME      NULL,
INDEX idx_dispute (dispute_id)
);


## 9.4 Dealer-Facing Dispute Interface
Accessible from dealer dashboard under a new Disputes tab.

Disputes Tab — Dashboard
- Summary: Open disputes, Awaiting your response, Resolved this month, Expired (no response) count
- Response rate displayed prominently: '% of reviews responded to within 14 days' — this is the accountability metric
- Resolution rate: '% of disputes resolved by customer'
- List of all disputes with status, review rating, days remaining to respond, last activity date

Dispute Detail Page — Dealer View
- Full review text and star ratings displayed at top
- Reviewer username (not email — privacy preserved)
- Message thread showing all previous responses
- Response composer: rich text editor, character limit 2000, preview before submit
- Guidance prompt: 'Explain how you resolved the issue or what steps you are taking. Responses are public.'
- Days remaining indicator — red when less than 3 days
- 'Mark as Fully Resolved on Our End' button — sends customer notification asking them to mark resolved


## 9.5 Customer-Facing Dispute Interface
Customers manage their dispute from their review on the product page and from /account/reviews.

Notification Flow
- Customer receives IPS notification + email when dealer responds to their review
- Notification links directly to the dispute thread on the product page
- Reminder notification sent to customer 3 days before their 14-day reply window closes
- Customer receives notification when dealer marks 'Resolved on Our End'

Resolution Controls
- 'Mark as Resolved' button appears on the review once dealer has responded
- Clicking shows a confirmation modal: 'By marking this resolved, your review will remain visible but will no longer count against this dealer's star rating. Only mark resolved if your issue has been satisfactorily addressed.'
- After marking resolved: review displays [RESOLVED] green badge, dispute_status = resolved, review excluded from dealer star average
- Customer can add a closing comment when marking resolved: 'Dealer replaced the defective item promptly. Issue resolved.'
- Resolved status is permanent — customer cannot un-resolve


## 9.6 Public Dispute Display
The public-facing dispute thread is shown in two places: on the product page review card and on the dealer profile page.

On Product Page — Review Card
- Dealer response shown directly below the customer review, indented, labeled '[DEALER RESPONSE — StoreName]'
- Response timestamp and 'Official Response' badge shown
- Customer follow-up replies shown below dealer response in thread format
- [RESOLVED] green badge shown on review card header if resolved — with customer's closing comment if provided
- If dealer never responded within 14 days: '[NO RESPONSE FROM DEALER]' label in grey shown on review card after expiry

On Dealer Profile Page
- Separate Disputes tab on dealer public profile
- Response rate prominently displayed: 'Responds to X% of reviews'
- Resolution rate: 'X% of disputes resolved by customers'
- Open disputes listed: shows customer rating, days since posted, whether dealer has responded
- Resolved disputes listed with full thread collapsed and expandable
- No Response disputes listed — these are the most damaging to dealer reputation and intentionally visible


## 9.7 Dealer Reputation Metrics
These metrics are calculated and stored on the dealer record, updated after every dispute event:


Both the adjusted (resolved excluded) and raw star average are displayed on dealer profiles. The adjusted average is the headline number used in price comparison tables. The raw average is shown in smaller text with a label like 'X.X raw — X.X after resolved reviews excluded' so users have full context.


## 9.8 Automated Expiry Tasks
- Daily task: check all disputes with status = awaiting_dealer — if dealer_response_due has passed: set status = expired_no_response, increment dealer no_response count, update response rate metric
- Daily task: check all disputes with status = dealer_responded — if customer_reply_due has passed and not resolved: set status = expired_no_resolution — review remains at full weight
- Both expiry events trigger IPS notification to relevant parties
- Admin notified of high no-response rate dealers — configurable threshold (default: 3+ no-response disputes)


## 9.9 Admin — Dispute Management
- All active disputes with status, dealer, days remaining, last activity
- Flagged disputes: either party can flag a dispute message as abusive — admin reviews
- Admin can post to any dispute thread as Admin — for escalated situations
- Admin can force-resolve a dispute if both parties are unresponsive after 60 days total
- Dealer accountability report: dealers ranked by no-response count — used for identifying bad actors
- Platform dispute stats: average response time, resolution rate, no-response rate — shown on About page as trust signals


# 10. Plugin 7 — GD Power Features
Plugin 7 consolidates eight advanced features that collectively differentiate the platform from every competitor in the firearms deal space. These features are grouped into a single plugin for development efficiency since they share notification infrastructure, user account hooks, and OpenSearch queries. They are designed to drive daily return visits, increase session depth, and create switching costs that make users reluctant to go back to gun.deals.


## 10.1 Confirmed Design Decisions


## 10.2 VIP Consumer Membership
The VIP membership is a $9.99/month paid consumer tier separate from dealer subscriptions. It gives engaged buyers a meaningful upgrade path and generates recurring consumer revenue independent of the dealer business. Implemented as an IPS Commerce subscription product that assigns a VIP secondary member group — identical architecture to dealer tier group promotion.


### 10.2.1 Updated Member Group Structure

A member can hold Dealers and VIP secondary groups simultaneously. A dealer who subscribes to VIP gets both sets of permissions. IPS handles multiple secondary groups natively with no custom code.


### 10.2.2 Free vs VIP Feature Comparison


### 10.2.3 IPS Commerce Configuration
- Monthly product: 'GD VIP Membership — $9.99/month' — group promotion: add VIP on purchase, remove on expiry/cancel
- Annual product: 'GD VIP Membership — $99/year' — same group logic, 12-month term
- Cancellation: member retains VIP access until end of current billing period then group removed
- Downgrade behavior: wishlists 2–10 set to private but NOT deleted. Search alerts 4+ paused but NOT deleted. Restored on re-subscribe.

'Preserve but pause' on downgrade is intentional. A lapsed VIP who resubscribes should find everything intact. Deleting data on cancellation would increase churn anxiety and deter re-subscription.


### 10.2.4 Limit Enforcement — Free Members
- 2nd wishlist creation: upgrade prompt shown — 'VIP members get up to 10 wishlists for $9.99/month'
- 4th search alert creation: upgrade prompt shown
- Price history beyond 90 days: blurred overlay with upgrade prompt
- Compliance triggers beyond enacted: shown as locked in /account/compliance-alerts with upgrade prompt
- Early notifications: Fire badge and below-MAP alerts are queued for free members and released 30 minutes after VIP batch fires


### 10.2.5 VIP Acceptance Criteria
- Free member creating 2nd wishlist sees upgrade prompt — wishlist not created
- VIP member can create exactly 10 wishlists — 11th shows max limit message
- Free member creating 4th search alert sees upgrade prompt — alert not created
- On VIP cancellation: wishlists 2–10 set to private, alerts 4+ paused — none deleted
- On VIP re-subscribe: all wishlists restored to original visibility, all alerts reactivated
- VIP alerts processed before free member alerts — verified by timestamp comparison in test
- 30-minute delay on free member Fire/MAP alerts confirmed with timed import test
- Ad-free: no ads displayed on any page for VIP member — verified across homepage, browse, product pages
- Annual plan assigns VIP group for full 12 months without requiring monthly renewals


## 10.3 Deal Heat Score
The Deal Heat Score is a calculated numeric value (0–100) per dealer listing that reflects how exceptional a price is relative to historical data and current community activity. It drives a badge label shown on every listing and product card across the platform.


### 10.3.1 Heat Score Formula
The score is computed from four weighted components recalculated after every dealer feed import:


### 10.3.2 Badge Thresholds
The heat score is calculated internally (0–100) but only the badge label is shown to users — no numeric score is displayed. This keeps the UI clean and prevents users from gaming the system.


### 10.3.3 Historic Low and All-Time Low Badges
- When current_price equals the all-time lowest recorded price for that UPC across all dealers: product card and product page show a red [ALL-TIME LOW] badge
- When current_price is the lowest price seen in the last 90 days but not all-time: orange [90-DAY LOW] badge
- These badges display independently of the heat score — a product can show both a Fire badge and an ALL-TIME LOW badge simultaneously
- Historic low data sourced from gd_price_history table — all-time low is min(price) across all records for that UPC


### 10.3.4 Heat Score Storage

-- Denormalized fields added to gd_dealer_listings (Plugin 2):
ALTER TABLE gd_dealer_listings ADD COLUMN heat_score TINYINT DEFAULT 0;
ALTER TABLE gd_dealer_listings ADD COLUMN heat_label ENUM('cold','warm','hot','fire') DEFAULT 'cold';
ALTER TABLE gd_dealer_listings ADD COLUMN is_alltime_low TINYINT(1) DEFAULT 0;
ALTER TABLE gd_dealer_listings ADD COLUMN is_90day_low TINYINT(1) DEFAULT 0;
ALTER TABLE gd_dealer_listings ADD COLUMN price_vs_avg_pct DECIMAL(5,2) DEFAULT 0;
-- Recalculated after every dealer feed import run.


### 10.3.5 Heat Score Display Locations
- Product listing cards in category browse and search results: badge label + score below
- Price comparison table: heat score column per dealer listing row
- Product page header: if any dealer listing is Hot or Fire, show the best badge prominently in the header block
- Homepage: Hot Deals feed — top 20 listings with Fire or Hot badge sorted by score descending
- Browser extension popup: heat badge shown for matched product


## 10.4 Search Query Alerts
Users can save any search query as an alert. When new products or price drops match that query, the user is notified. This is the most powerful retention feature on the platform — it turns passive browsers into engaged subscribers.


### 10.3.1 Saving a Search Alert
- A 'Save this Search as an Alert' button appears on every search results page and category browse page
- Clicking opens a modal: shows the current query/filters, lets user name the alert (optional), set a max price threshold (optional), and choose notification frequency: Instant / Daily Digest / Weekly Digest
- Alert is saved with the full filter state: keywords, category, brand, caliber, price range, shipping filter, in-stock only, condition — everything currently applied
- Users can create unlimited search alerts


### 10.4.2 Alert Trigger Logic
- After every dealer feed import: run all active search alerts against newly created or price-decreased listings
- For each alert: execute the saved query against OpenSearch with a filter for records updated in this import run only
- If results found AND (no max price set OR results contain listings at or below max price): queue notification
- Notification frequency setting determines delivery: Instant = immediate IPS notification + email, Daily = batched into one email per day, Weekly = batched into one email per week
- Notification includes: alert name, number of matching products found, top 3 results with price and heat badge, link to full results page with filters pre-applied
- Duplicate suppression: if the same product triggered this alert within the last 24 hours (instant) or last digest period (daily/weekly), suppress the duplicate


### 10.4.3 Alert Management — /alerts
- Dedicated page at /alerts listing all user's saved search alerts
- Each alert shows: name, saved query summary, notification frequency, last triggered date, total notifications sent
- Edit: change name, max price, notification frequency
- Pause: temporarily disable without deleting
- Delete alert
- 'Run Now' button: immediately executes the alert query and shows results — useful for testing
- Alert history: last 10 notifications sent for each alert with timestamp and match count


CREATE TABLE gd_search_alerts (
id                  INT AUTO_INCREMENT PRIMARY KEY,
member_id           INT           NOT NULL,
alert_name          VARCHAR(150)  NULL,
query_string        VARCHAR(500)  NULL,
filters_json        TEXT          NOT NULL,
max_price           DECIMAL(10,2) NULL,
frequency           ENUM('instant','daily','weekly') DEFAULT 'instant',
active              TINYINT(1)    DEFAULT 1,
last_triggered      DATETIME      NULL,
total_notifications INT           DEFAULT 0,
created_at          DATETIME      NOT NULL,
INDEX idx_member    (member_id),
INDEX idx_active    (active)
);


## 10.5 Wishlist
The wishlist is a named, shareable collection of products a user wants to buy. It is distinct from the watchlist — the watchlist is a price-alert tool, the wishlist is a personal collection and discovery tool. Users can have multiple named wishlists and choose visibility per list.


### 10.5.1 Wishlist Structure
- Free members: 1 wishlist (named 'My Wishlist' by default). VIP members: up to 10 named wishlists.
- A default wishlist is created automatically on registration for all members
- Wishlist visibility: Public (discoverable, followable) or Unlisted (link only). No private option — all wishlists are shareable by design.
- Products added via 'Add to Wishlist' button on product pages, search results, and listing cards — if user has multiple wishlists (VIP), a dropdown lets them choose
- VIP members only: each wishlist item can have an optional personal note — e.g. 'want the FDE version' or 'wait for price under $350'. Free members see the notes field locked with an upgrade prompt.
- Each wishlist item can have an optional target price — triggers a notification when any dealer hits that price


### 10.5.2 Wishlist Notification Triggers
Users configure notification preferences per wishlist — all triggers are opt-in and can be toggled individually:


### 10.5.3 Wishlist Public Features
- Public wishlists discoverable at /wishlists — browsable by other users, filtered by category or username
- Public wishlist URL: /wishlist/{username}/{wishlist-slug}
- Unlisted wishlists: accessible only via direct link — not indexed, not discoverable in browse
- All wishlists default to Unlisted on creation — user changes to Public explicitly
- Other users can follow any Public wishlist — they see the list and can browse it, but receive no notifications
- Wishlist owner sees follower count displayed on their wishlist page
- Following does NOT grant notification access — follower gets zero alerts. Only the wishlist owner receives alerts.
- VIP members who own public wishlists see follower count and a follower growth trend


### 10.5.4 Wishlist Database Schema

CREATE TABLE gd_wishlists (
id              INT AUTO_INCREMENT PRIMARY KEY,
member_id       INT           NOT NULL,
name            VARCHAR(150)  NOT NULL,
slug            VARCHAR(160)  NOT NULL,
visibility      ENUM('unlisted','public') DEFAULT 'unlisted',
notify_price_drop       TINYINT(1) DEFAULT 1,
notify_back_in_stock    TINYINT(1) DEFAULT 1,
notify_out_of_stock     TINYINT(1) DEFAULT 0,
notify_new_dealer       TINYINT(1) DEFAULT 0,
notify_heat_fire        TINYINT(1) DEFAULT 1,
notify_alltime_low      TINYINT(1) DEFAULT 1,
follower_count  INT           DEFAULT 0,
created_at      DATETIME      NOT NULL,
UNIQUE KEY uq_member_slug (member_id, slug),
INDEX idx_member     (member_id),
INDEX idx_visibility (visibility)
);


CREATE TABLE gd_wishlist_items (
id              INT AUTO_INCREMENT PRIMARY KEY,
wishlist_id     INT           NOT NULL,
upc             VARCHAR(20)   NOT NULL,
target_price    DECIMAL(10,2) NULL,
notes           VARCHAR(500)  NULL,  -- VIP members only. NULL enforced for free members at application layer.
added_at        DATETIME      NOT NULL,
last_notified   DATETIME      NULL,
UNIQUE KEY uq_list_upc (wishlist_id, upc),
INDEX idx_wishlist (wishlist_id),
INDEX idx_upc      (upc)
);


CREATE TABLE gd_wishlist_followers (
id            INT AUTO_INCREMENT PRIMARY KEY,
wishlist_id   INT      NOT NULL,
member_id     INT      NOT NULL,
followed_at   DATETIME NOT NULL,
UNIQUE KEY uq_list_member (wishlist_id, member_id)
);


## 10.6 Ammo Bulk Buy Optimizer
The Ammo Bulk Buy Optimizer helps users find the cheapest way to buy a specific quantity of ammunition across all dealers and box sizes. No equivalent tool exists anywhere in the market. It is available as a standalone tool at /tools/ammo-calculator and embedded on every ammo product page.


### 10.6.1 Optimizer Inputs


### 10.6.2 Optimizer Logic
- User submits inputs — query runs against Dealer Listings for matching ammo UPCs
- For each matching dealer listing: calculate available box quantities and CPR at each quantity
- Determine how many boxes of each type are needed to reach or exceed target_quantity
- Calculate total cost: (boxes_needed * dealer_price) + shipping_cost per order
- Generate all valid single-dealer solutions: one dealer, one box size combination that meets target quantity
- Generate multi-dealer solutions where splitting between two dealers yields a lower total cost than any single dealer option — limit to two-dealer splits for complexity
- Rank all solutions by total cost ascending
- Return top 5 solutions with full breakdown


### 10.6.3 Results Display
- Results table: Rank, Dealer(s), Box Size, Quantity, Boxes Needed, Item Total, Shipping, Grand Total, CPR
- Best result highlighted with a green banner: 'Best Deal — X rounds for $Y ($Z per round delivered)'
- Overage displayed: 'You will receive X rounds (Y extra)' when target quantity isn't exactly divisible by box size
- Multi-dealer splits clearly labeled: 'Split order — buy X boxes from Dealer A + Y boxes from Dealer B'
- Buy buttons for each solution link to the relevant dealer product pages via tracked /go/ redirects
- 'Save this search' option saves the caliber/quantity/budget as a search alert for future price drops


### 10.6.4 Embedded on Ammo Product Pages
- Collapsed widget below the main comparison table labeled 'Bulk Buy Optimizer'
- Pre-filled with this product's caliber and grain weight
- User only needs to enter target quantity — all other fields pre-filled from product context
- Expands inline — no page navigation required


## 10.7 Compliance Change Notifications
Users receive notifications when firearms legislation changes in states they are subscribed to. Powered by the existing Firearms Bill Tracker plugin infrastructure — this is a direct integration that no competitor can easily replicate.


### 10.7.1 Subscription Model
- Users are automatically subscribed to their own state on registration (if state is set in profile)
- Users can manually add or remove any state from their compliance notification subscriptions
- Subscription management at /account/compliance-alerts
- Users can subscribe to federal-level changes as a separate subscription option
- Notification frequency: Instant for enacted laws, Weekly digest for bills in progress


### 10.7.2 Firearms Bill Tracker Integration
- The Firearms Bill Tracker plugin already monitors legislation via LegiScan API — this plugin hooks into that data feed
- Trigger events: Bill enacted into law / Bill passed committee / Bill introduced / Bill defeated
- Notification content: state, bill title, brief plain-English summary, link to full bill detail page
- Enacted law notifications include a compliance impact summary: what changes for gun owners in that state
- Admin can write and attach impact summaries to enacted bills — shown in the notification


CREATE TABLE gd_compliance_subscriptions (
id            INT AUTO_INCREMENT PRIMARY KEY,
member_id     INT         NOT NULL,
state_code    VARCHAR(3)  NOT NULL,
-- 'FED' for federal, 2-letter state code otherwise
trigger_enacted   TINYINT(1) DEFAULT 1,
trigger_passed    TINYINT(1) DEFAULT 0,
trigger_introduced TINYINT(1) DEFAULT 0,
trigger_defeated  TINYINT(1) DEFAULT 0,
created_at    DATETIME    NOT NULL,
UNIQUE KEY uq_member_state (member_id, state_code),
INDEX idx_member (member_id),
INDEX idx_state  (state_code)
);


## 10.8 MAP Violation Tracker
When a dealer lists a product below the manufacturer's suggested retail price by a meaningful threshold, it is flagged as a potential MAP (Minimum Advertised Price) violation. These are often the best deals on the platform and buyers actively look for them. All three levels of MAP tracking are implemented.


### 10.8.1 MAP Detection Logic
- MAP threshold: dealer_price is 10% or more below the Product record's msrp field — configurable by admin
- Calculated as: ((msrp - dealer_price) / msrp) * 100 >= threshold
- Only applies to products where msrp is populated in the master catalog — no msrp = no MAP badge
- MAP check runs after every dealer feed import — below_map flag updated on Dealer Listing record
- Admin can configure the MAP threshold percentage in ACP (default 10%)
- Admin can exclude specific brands or products from MAP tracking (some manufacturers don't enforce MAP)


### 10.8.2 MAP Badge Display
- Dealer listing rows with below_map = true show a green [BELOW MAP] badge in the price comparison table
- Product cards in browse/search show [BELOW MAP] badge when the lowest available listing is below MAP
- Badge tooltip: 'This price is X% below the manufacturer's suggested retail price of $Y'


### 10.8.3 Watcher Notifications
- When a product's listing drops below MAP for the first time (below_map flips from false to true): all users with this UPC in their watchlist OR wishlist receive a notification
- Notification labeled: 'Price Alert — Below MAP Deal: [Product] just dropped to $X — X% below MSRP'
- This fires in addition to regular price drop alerts — it is a separate, higher-priority notification


### 10.8.4 Below MAP Deals Section
- Dedicated section at /deals/below-map
- Lists all currently active below-MAP listings sorted by discount percentage descending
- Filterable by: category, brand, caliber, discount percentage
- Linked from homepage as a featured section: 'Below Retail Price Deals'
- RSS feed available at /deals/below-map/feed for power users


-- Field added to Dealer Listings table (Plugin 2):
ALTER TABLE gd_dealer_listings ADD COLUMN below_map TINYINT(1) DEFAULT 0;
ALTER TABLE gd_dealer_listings ADD COLUMN map_discount_pct DECIMAL(5,2) DEFAULT 0;
-- map_discount_pct = ((msrp - dealer_price) / msrp) * 100
-- Recalculated after every dealer feed import.


## 10.9 Dealer Response Time Leaderboard
A public ranking of dealers by fulfillment performance metrics sourced from community reports. Lives at /dealers/leaderboard and drives accountability among dealers.


### 10.9.1 Leaderboard Metrics


### 10.9.2 Leaderboard Page — /dealers/leaderboard
- Top 50 dealers ranked by Overall Score — paginated
- Filterable by: subscription tier, category specialization (if dealer has tagged their specialties), state
- Each dealer row shows: rank, store name, overall score, star rating, response rate, resolution rate, listing count, member since date
- 'Ships Fast' badge: awarded to dealers in top quartile of listing freshness
- 'Trusted Seller' badge: awarded to dealers with response rate > 90% and star rating > 4.5 and minimum 20 reviews
- 'Top Rated' badge: awarded to dealers in top 10 overall score with minimum 50 reviews
- Dealer leaderboard refreshed daily — not real-time
- Dealer leaderboard is a separate tab within the consolidated IPS leaderboard — not a standalone page. Dealer metrics (response rate, resolution rate, listing freshness) are distinct from community reputation points and render via a custom tab component. See Section 16.
- Dealers can embed their leaderboard badge on their own website — a shareable badge image generated at /dealers/{slug}/badge.png


### 10.9.3 Leaderboard Badges on Dealer Profiles and Comparison Tables
- Ships Fast, Trusted Seller, and Top Rated badges shown on dealer profile pages
- In the price comparison table: dealer name column shows earned badges as small icons with tooltip
- Buyers can filter the comparison table to 'Trusted Sellers Only' — shows only dealers with the Trusted Seller badge


## 10.10 Browser Extension
A browser extension for Chrome, Firefox, and Edge that activates on firearms retailer product pages and shows competing prices from the platform in a lightweight popup. This is the primary passive user acquisition channel — it turns every competitor's website into a referral funnel by intercepting the buyer at the moment of purchase decision.


### 10.10.1 Extension Architecture
The extension is built as a standard Manifest V3 browser extension — the current required format for Chrome, Edge, and Firefox. It consists of three components: a content script that runs on retailer pages, a background service worker that manages API calls and caching, and a popup UI that renders the price comparison results.


### 10.10.2 UPC and Product Detection
The content script uses a multi-stage detection approach to maximize match rate across different retailer page structures:

- Stage 1 — Schema.org structured data: check window.__schema or JSON-LD script tags for Product schema with gtin13, gtin12, or sku fields. Most major retailers (Brownells, PSA, KyGunCo) include this. Most reliable source — parse this first.
- Stage 2 — Meta tags: check <meta property='product:upc'>, <meta name='upc'>, and <meta property='og:upc'>. Less common but present on some platforms.
- Stage 3 — DOM text scanning: scan page text for 12–13 digit sequences matching UPC-A format (\d{12,13}). Look near labels: 'UPC:', 'Item #:', 'Model #:', 'SKU:'. Pattern match only — no OCR.
- Stage 4 — Product title lookup: if no UPC found, extract the page <title> or h1 and send to background worker. Background worker queries /api/v1/extension/lookup?title={encoded_title} — server-side fuzzy match against catalog product names. Lower confidence than UPC match — popup shows 'Possible match' indicator.

- If a UPC match is found: background worker sets icon badge to the count of dealers found (e.g. '7'). Icon tint changes to platform primary color.
- If title match only: icon badge shows '?' — click to see possible match.
- If no match: icon remains neutral grey — no badge. User can click to manually enter a UPC or search by name.
- Content script sends only UPC strings and page title to background worker — never full page HTML, never personal data, never browsing history.


### 10.10.3 Supported Retailer Domains
The manifest declares host_permissions and content_script match patterns for a defined list of firearms retailer domains. Extension only activates on these domains — it does not run on every page the user visits.


- New domains added to the manifest list via extension update — no server-side changes needed
- Retailer domains are stored in the manifest host_permissions array — browser enforces that the extension only runs on listed domains
- User can optionally disable the extension on specific domains from the popup settings gear icon


### 10.10.4 Popup UI — Full Specification
The popup renders within the browser extension popup container (approximately 360px wide). It is a single-page HTML/CSS/JS file with no external framework dependencies — vanilla JS only for performance and store compliance.


Match Found — Popup Elements (top to bottom)
- Product thumbnail image (from platform catalog, 60x60px) + product name (max 2 lines, truncated with ellipsis)
- Current page price label: 'This page: $XXX.XX' — extracted from page DOM by content script and passed to popup
- Platform best price: 'GunRack best: $XXX.XX delivered — Free shipping · [Dealer Name]' — savings delta shown in green if platform is cheaper, red if current page is cheaper
- Heat badge: COLD / WARM / HOT / FIRE pill badge in appropriate color
- [ALL-TIME LOW] or [90-DAY LOW] badge shown below heat badge if applicable — green pill
- Dealer count line: 'X dealers have this in stock' — linked to product page
- Primary CTA button: 'See all X dealers on GunRack' — opens /products/{slug} in new tab with utm_source=extension&utm_medium=popup
- Secondary action row: 'Add to Wishlist' button (logged in) OR 'Log in to save' link (logged out) + 'Set price alert' link (logged in only)
- Footer: GunRack logo + 'Settings' gear icon (opens options page) + 'Report a problem' link


### 10.10.5 Extension API — Full Specification
The /api/v1/extension/lookup endpoint is the only server-side component the extension touches. It is read-only and public — no authentication required.


Response Format — Match Found

{
"matched": true,
"match_type": "upc",
// upc = exact UPC match, title = fuzzy title match
"product": {
"title": "Glock 19 Gen5 9mm Luger 4.02 Barrel 15+1",
"image_url": "https://gunrack.deals/catalog/images/764503030826.jpg",
"platform_url": "https://gunrack.deals/products/handguns/pistols/glock-19-gen5-764503030826",
"upc": "764503030826"
},
"pricing": {
"lowest_total": 449.00,
"lowest_price": 449.00,
"lowest_shipping": 0.00,
"lowest_dealer": "Palmetto State Armory",
"dealer_count": 8,
"in_stock_count": 6
},
"heat": {
"label": "FIRE",
"is_alltime_low": true,
"is_90day_low": true
}
}


Response Format — No Match

{
"matched": false,
"match_type": null,
"product": null,
"pricing": null,
"heat": null
}


- Rate limiting: 60 requests per minute per IP at Nginx level (see Appendix C Section C.6). Extension also implements client-side debouncing — minimum 2 seconds between API calls from the same install.
- Cache: background worker caches successful responses in chrome.storage.local with 24-hour TTL. Same UPC on same device does not make a new API call within 24 hours.
- Error handling: API errors and timeouts handled gracefully — popup shows cached data if available, error state if not. Never shows a blank popup or unhandled JS error.
- CORS: the endpoint returns appropriate CORS headers allowing requests from browser extension origins (chrome-extension:// and moz-extension://).


### 10.10.6 Manifest V3 Structure
The manifest.json defines the extension's permissions and behavior. Key fields:


{
"manifest_version": 3,
"name": "GunRack — Compare Firearm Prices",
"version": "1.0.0",
"description": "See competing prices from 40+ dealers instantly while browsing any gun store.",
"permissions": ["activeTab", "storage"],
"host_permissions": [
"https://gunrack.deals/*",
"https://*.palmettostatearmory.com/*",
"https://*.grabagun.com/*",
"https://*.kygunco.com/*",
"https://*.budsgunshop.com/*",
"... (all supported retailer domains)"
],
"background": { "service_worker": "background.js" },
"content_scripts": [{
"matches": ["<all supported retailer domains>"],
"js": ["content.js"],
"run_at": "document_idle"
}],
"action": {
"default_popup": "popup.html",
"default_icon": { "16": "icons/icon16.png", "48": "icons/icon48.png", "128": "icons/icon128.png" }
},
"icons": { "16": "icons/icon16.png", "48": "icons/icon48.png", "128": "icons/icon128.png" }
}


### 10.10.7 Extension Distribution and Privacy

- Source code published on GitHub — open source builds trust and allows community to verify the extension does not collect browsing history
- Privacy policy hosted at gunrack.deals/extension/privacy — required by all three stores. Key declaration: extension reads page content only to detect UPC numbers, sends only UPC strings to GunRack servers, never stores or transmits browsing history, page content, or personal data
- Extension does NOT use chrome.webRequest or any permission that could intercept network traffic — deliberately limited to activeTab and storage for minimal permission footprint
- Each store submission requires screenshots, a 128px icon, a short description (132 chars max for Chrome), and a long description. Prepare these assets before submitting.
- Store review timeline: build and submit to all three stores at minimum 2 weeks before planned launch date


## 10.11 Enhanced Price Drop Alerts
The existing watchlist price alert system (Plugin 3 Section 4.7) is extended with the following enhancements that were confirmed as part of this feature set:

- Category-level alerts: user subscribes to a category (e.g. '9mm Pistols under $400') — managed through Search Query Alerts (Section 10.3)
- Below MAP alert: fires when any watched or wishlisted product drops below MAP threshold — separate from regular price drop notification, higher priority
- All-time low alert: fires when any watched or wishlisted product hits its all-time lowest recorded price — labeled distinctly in notification
- Heat score milestone alert: fires when a watched product's heat score crosses from Warm to Hot or Hot to Fire
- Alert digest option: users can choose to receive a single daily email summary of all price drops across their watchlist and wishlists rather than individual notifications per event
- Notification preference center at /account/notifications — single page to manage all alert types, delivery methods (email / on-site / both), and digest vs instant settings


## 10.12 Acceptance Criteria — Plugin 7
Deal Heat Score
- Heat score calculated correctly: test listing with known price history, verify all four components score correctly and sum to expected total
- ALL-TIME LOW badge appears when dealer price equals historical minimum in gd_price_history
- 90-DAY LOW badge appears correctly — does not show when price is not the lowest in 90 days
- Heat score updates after every dealer feed import run — verified with a test price change

Search Query Alerts
- User saves a search alert, new matching product is added via dealer feed, alert notification fires
- Max price threshold respected — alert does not fire when matching product is above threshold
- Daily digest batches multiple matches into single email
- /alerts page shows accurate history and last triggered date

Wishlist
- User creates multiple named wishlists — all visible at /account/wishlists
- Public wishlist accessible at /wishlist/{username}/{slug} without login — appears in /wishlists browse
- Unlisted wishlist accessible via direct link only — does NOT appear in /wishlists browse or search
- New wishlist defaults to Unlisted on creation — verified
- All six notification triggers fire correctly when conditions are met
- goes_out_of_stock trigger fires only when ALL dealers go out of stock — not just one
- VIP member can add and edit notes on wishlist items — notes saved and displayed
- Free member sees notes field as locked/disabled with upgrade prompt — note cannot be saved
- Following a wishlist increments follower_count

Ammo Bulk Buy Optimizer
- Single-dealer solution calculates correct boxes needed and total cost
- Multi-dealer split correctly identifies when two dealers is cheaper than one
- Overage rounds displayed accurately when target not exactly divisible
- Embedded widget on ammo product page pre-fills caliber and grain weight correctly
- Budget filter correctly excludes solutions over budget

Compliance Notifications
- User auto-subscribed to their own state on registration — confirmed with test account
- Manual state subscription and removal working at /account/compliance-alerts
- Enacted law notification fires when Bill Tracker marks a bill as enacted
- Federal subscription receives federal-level notifications

MAP Tracker
- below_map flag set correctly when dealer_price is 10%+ below msrp
- below_map NOT set when product has no msrp in catalog
- [BELOW MAP] badge visible on listing rows and product cards
- Watcher notification fires when below_map flips from false to true
- /deals/below-map page listing all currently below-MAP listings sorted by discount
- RSS feed at /deals/below-map/feed returns valid RSS

Dealer Leaderboard
- All five metrics calculating correctly with test data
- Trusted Seller badge awarded only to dealers meeting all three criteria
- Ships Fast badge awarded to correct quartile
- /dealers/leaderboard page rendering with correct ranking
- Dealer badge embed URL returning valid image

Browser Extension
- Extension detects UPC on a test retailer product page and activates icon
- Popup shows correct product data from platform API
- Heat badge and historic low badge display correctly in popup
- 'See all dealers' link opens correct product page on platform
- Add to Wishlist works for logged-in users
- API rate limiting: verify 101st request within one hour is rejected
- Extension published and installable from Chrome Web Store, Firefox AMO, and Edge Add-ons

# 11. Plugin 8 — GD SEO Architecture
SEO is the most important infrastructure decision not related to the core data pipeline. Every product page, category page, review page, and rebate page needs to be built with search engine visibility as a first-class concern from day one. Retrofitting SEO after templates are built is expensive. This section defines all SEO requirements as explicit developer constraints that apply to every page rendered by the platform.

⚠  This section must be reviewed by the developer before any frontend template work begins. SEO requirements affect page structure, URL design, server-side rendering behavior, and OpenSearch index fields. They cannot be added after the fact without significant rework.


## 11.1 Confirmed Design Decisions


## 11.2 URL Structure
Clean, descriptive URLs are a ranking signal and improve click-through rates from search results. The following URL patterns are required across all page types — no query strings in public-facing URLs:


The UPC is appended to product and review URLs as the final segment. This ensures uniqueness when two products have identical brand and model slugs, and also means users who search for a UPC directly find the product page immediately.


## 11.3 Meta Tags
Every public page must output correctly structured meta tags in the HTML head. The following templates govern auto-generation. All templates support admin override at the page or category level via an ACP interface.

Product Detail Pages

<title>{Brand} {Model} {Caliber} — Compare Prices from {N} Dealers | GunDeals</title>
<!-- Example: Glock 19 Gen5 9mm — Compare Prices from 14 Dealers | GunDeals -->

<meta name="description" content="Compare {Brand} {Model} prices from {N} dealers.
Lowest price: ${lowest_total} including shipping.
{heat_label} deal — {pct_below_avg}% below average. Updated every 15 minutes.">

<meta name="robots" content="index, follow">
<link rel="canonical" href="https://gunrack.deals/products/{category}/{brand-model-upc}">


Category Browse Pages

<title>{Category} for Sale — Best Prices & Deals | GunDeals</title>
<!-- Example: 9mm Pistols for Sale — Best Prices & Deals | GunDeals -->

<meta name="description" content="Browse {N} {category} from {dealer_count} dealers.
Compare prices, read reviews, and find the best deals on {category}.
Prices updated every 15 minutes.">


Review Pages

<title>{Brand} {Model} Reviews — {avg_rating}/5 from {review_count} owners | GunDeals</title>

<meta name="description" content="{review_count} real owner reviews of the {Brand} {Model}.
Average rating: {avg_rating}/5. {recommend_pct}% of reviewers recommend it.
Read verified purchase reviews and see what owners say about reliability and value.">


Rebate Pages

<title>{Manufacturer} {Rebate_Title} — ${amount} Rebate | GunDeals</title>

<meta name="description" content="Get ${amount} back on qualifying {Manufacturer} purchases.
Rebate valid through {end_date}. Step-by-step submission instructions and
community success rate: {success_pct}% received their rebate.">


## 11.4 Structured Data — Schema.org JSON-LD
All public pages output Schema.org JSON-LD in the HTML head. JSON-LD is the Google-preferred format and does not require changes to visible HTML structure. The following schemas are required:

Product Detail Page Schema

{
"@context": "https://schema.org",
"@type": "Product",
"name": "{title}",
"brand": { "@type": "Brand", "name": "{brand}" },
"description": "{description}",
"image": "{image_url}",
"sku": "{upc}",
"mpn": "{upc}",
"offers": {
"@type": "AggregateOffer",
"lowPrice": "{lowest_dealer_price}",
"highPrice": "{highest_dealer_price}",
"offerCount": "{active_dealer_count}",
"priceCurrency": "USD",
"availability": "https://schema.org/InStock"
},
"aggregateRating": {
"@type": "AggregateRating",
"ratingValue": "{avg_rating}",
"reviewCount": "{review_count}",
"bestRating": "5",
"worstRating": "1"
}
}


Review Page Schema

-- Each individual review outputs a Review schema block:
{
"@type": "Review",
"author": { "@type": "Person", "name": "{username}" },
"datePublished": "{created_at}",
"reviewBody": "{body}",
"reviewRating": {
"@type": "Rating",
"ratingValue": "{overall_rating}",
"bestRating": "5"
}
}


BreadcrumbList Schema — All Pages

-- Output on every page with a breadcrumb trail:
{
"@type": "BreadcrumbList",
"itemListElement": [
{ "@type": "ListItem", "position": 1, "name": "Home", "item": "https://gunrack.deals" },
{ "@type": "ListItem", "position": 2, "name": "{category}", "item": "https://gunrack.deals/products/{category-slug}" },
{ "@type": "ListItem", "position": 3, "name": "{product_title}", "item": "https://gunrack.deals/products/{full-slug}" }
]
}


WebSite Schema — Homepage Only

{
"@type": "WebSite",
"name": "GunDeals",
"url": "https://gunrack.deals",
"potentialAction": {
"@type": "SearchAction",
"target": "https://gunrack.deals/search?q={search_term_string}",
"query-input": "required name=search_term_string"
}
}
-- SearchAction enables Google Sitelinks Search Box in search results.


## 11.5 Open Graph and Twitter Card Tags
Social sharing tags ensure the platform looks professional when links are shared on social media, forums like Reddit r/gundeals, and messaging apps. Required on all public pages:


<!-- Open Graph — required on all public pages -->
<meta property="og:title" content="{page_title}">
<meta property="og:description" content="{meta_description}">
<meta property="og:image" content="{product_image_url or category_image_url}">
<meta property="og:url" content="{canonical_url}">
<meta property="og:type" content="product">  <!-- or website for non-product pages -->
<meta property="og:site_name" content="GunDeals">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{page_title}">
<meta name="twitter:description" content="{meta_description}">
<meta name="twitter:image" content="{product_image_url}">


## 11.6 XML Sitemaps
Auto-generated XML sitemaps submitted to Google Search Console and Bing Webmaster Tools. Given the volume of pages (potentially 500k+ product pages, thousands of review and rebate pages), sitemaps are split into multiple files referenced by a sitemap index.


- Sitemap index file at /sitemap.xml references all individual sitemap files
- Each sitemap file split at 50,000 URLs per file — Google's recommended limit
- lastmod date on each URL reflects the last time that page's content meaningfully changed
- Products with no dealer listings still included in sitemap at lower priority (0.4) — they have catalog value even without pricing
- Sitemaps regenerated nightly as a scheduled background task
- Sitemap ping sent to Google and Bing on regeneration


## 11.7 Canonical URLs
Canonical URL enforcement prevents duplicate content penalties from filtered/sorted variations of the same page:

- Every page outputs a <link rel='canonical'> tag pointing to the clean base URL
- Category browse pages with active filters: canonical points to the unfiltered base category URL
- Paginated pages: canonical on page 2+ points to page 1 — also output rel='prev' and rel='next' for Google
- Product pages: canonical always uses the full slug including UPC — ensures uniqueness
- Dealer dashboard, account pages, /alerts, /account/* — output <meta name='robots' content='noindex, nofollow'> — these are private pages that must not be indexed
- Search results pages (/search?q=...): noindex — avoids thin content penalty from search crawls
- API endpoints (/api/*): noindex via robots.txt disallow


## 11.8 Page Speed Requirements
Page speed is a confirmed Google ranking factor. The following requirements must be met and verified before launch using Google PageSpeed Insights and Core Web Vitals:


- Product images: served via CDN (Cloudflare), WebP format where supported, lazy loaded below the fold
- IPS Redis cache configured for full-page caching on public pages — product pages, category pages, review pages
- Price comparison table data loaded via lightweight AJAX call after initial page render — prevents price data from blocking LCP
- Chart.js loaded only on pages that display charts — not globally
- Schema.org JSON-LD output in <head> — not blocking render


## 11.9 robots.txt

User-agent: *
Allow: /
Disallow: /account/
Disallow: /dealers/dashboard
Disallow: /api/
Disallow: /search
Disallow: /go/
Disallow: /admin/
Disallow: /?*
Disallow: /alerts

Sitemap: https://gunrack.deals/sitemap.xml

# Rate limit aggressive crawlers
User-agent: AhrefsBot
Crawl-delay: 10
User-agent: SemrushBot
Crawl-delay: 10


## 11.10 SEO Admin Dashboard
ACP section for monitoring and managing SEO health:
- Sitemap status: last generated, URL count per file, any generation errors
- Meta description override interface: search for any product, category, or page and set a custom meta description and title that overrides the auto-generated template
- Index coverage report: products with missing images (lower CTR from search), products with descriptions under 100 characters (thin content risk), products with no reviews (opportunity list)
- Canonical URL validation: tool to check any URL and confirm canonical tag is correct
- Robots.txt editor: edit robots.txt directly from ACP without server file access


## 11.11 SEO Acceptance Criteria
- Every product page outputs: correct <title>, <meta description>, canonical URL, BreadcrumbList schema, Product + AggregateOffer schema, AggregateRating schema (when reviews exist), Open Graph tags
- Category pages output: correct title, description, canonical, BreadcrumbList, Open Graph
- Review pages output: Review schema blocks for each displayed review
- Homepage outputs WebSite schema with SearchAction
- Sitemap index at /sitemap.xml references all sitemap files — validated with Google Search Console
- All sitemap files validate as valid XML — tested with sitemap validator tool
- Filtered category URLs canonicalize to base category URL — confirmed with curl
- Paginated pages output rel=prev/next — confirmed on page 2+
- Account pages, search pages, /go/ redirect URLs all return noindex — confirmed with crawl tool
- robots.txt disallows /account/, /api/, /go/, /search — confirmed
- Core Web Vitals: LCP under 2.5s, CLS under 0.1 on product pages — verified with PageSpeed Insights
- Mobile PageSpeed score 85+ on product and category pages — verified
- Admin meta description override saves and overrides template output correctly

# 12. Plugin 9 — GD Email and Digest System
The email system consolidates all transactional notifications and digest emails into a single infrastructure layer. It serves two distinct functions: transactional emails triggered by platform events (price alerts, watchlist notifications, dispute responses, etc.) and digest emails that aggregate deal intelligence into scheduled sends. Both are delivered via Amazon SES — the most cost-effective option at scale, typically under $1 per 10,000 emails.


## 12.1 Confirmed Design Decisions


## 12.2 Amazon SES Configuration
- Domain verification: platform sending domain verified in SES with DKIM and SPF records
- Dedicated IP address: request SES dedicated IP for sending reputation isolation — prevents shared IP reputation issues
- Bounce and complaint handling: SES SNS notifications for bounces and complaints → webhook handler → marks affected member email as invalid in IPS, suppresses future sends
- Sending rate: SES default 14 emails/second — sufficient for transactional. Batch digest sends use SES bulk sending with rate throttling to stay within limits
- Cost reference: approximately $0.10 per 1,000 emails. 100,000 members weekly digest = $10/week = $520/year. Negligible at scale.
- SES credentials stored in IPS ACP as encrypted config values — never hardcoded


## 12.3 Email Types and Triggers


## 12.4 Weekly and Daily Digest Content
The digest is the platform's primary retention tool outside the app. It must be worth opening. Content is auto-generated from platform data with optional admin curation.

Digest Content Sections — in order

Sections with no content are omitted entirely — a digest with only 2 or 3 relevant sections is better than a digest padded with irrelevant content. Minimum viable digest: Watchlist Highlights OR Fire Deals must have content. If neither has content for a member, the digest is skipped that week for that member.

VIP Daily Digest Differences
- Same section structure but covers the past 24 hours instead of 7 days
- Watchlist/wishlist section is the most prominent — daily cadence makes per-item changes more actionable
- Breaking deals section: items that hit Fire or All-Time Low in the last 24 hours
- No Expiring Rebates section in daily digest — included in weekly only


## 12.5 Email Preference Center
Simple, focused preference controls at /account/notifications. Users choose their digest frequency and which product categories to include. Transactional alerts (price drops, stock alerts, dispute responses) are not individually toggleable — they fire based on what the user has added to their watchlist and wishlist. Global unsubscribe is always available.

Digest Frequency
- Standard members: Weekly digest only — cannot change frequency, can only opt out entirely
- VIP members: Weekly or Daily — selector between the two options
- All members: Opt out of digest entirely without affecting transactional alerts

Category Filters — Digest Only

Category filters apply to digest emails only. Transactional alerts — price drops, stock alerts, dispute responses, review approvals — are not filterable by category. They fire based on what the user has explicitly added to their watchlist or wishlist, so by definition the user wants to hear about those items.

Global Controls
- Global unsubscribe link in every email footer — one click, no login required — CAN-SPAM compliant
- Unsubscribe sets a suppress flag on the member record — no emails of any type sent until re-enabled
- Re-subscribe option on the unsubscribe confirmation page and at /account/notifications
- 'Pause all emails for 30 days' option — temporary suppression without full unsubscribe


## 12.6 Email Template System
All emails use a consistent template system with the platform brand. Templates are maintained in code as IPS email templates — editable from ACP without developer involvement.

Template Requirements
- Mobile-responsive HTML emails — tested across Gmail, Outlook, Apple Mail, and mobile clients
- Plain text fallback for every HTML email — required for deliverability
- Single-column layout — highest compatibility across email clients
- Platform logo and brand colors in header
- Unsubscribe link and physical mailing address in every footer — CAN-SPAM compliance
- Preheader text configured on every email — the preview text shown in inbox before opening
- UTM parameters on all links: utm_source=email, utm_medium={email_type}, utm_campaign={digest_date or alert_type}

Email Template List


## 12.7 Digest Generation Process
- Scheduled task triggers Sunday at midnight UTC for weekly digest generation
- For each member with digest opted in: query all nine content sections using that member's watchlist, wishlist, saved alerts, and subscribed states
- Apply category toggles — if member has a category disabled (Firearms/Ammo/Accessories/Rebates/Compliance), filter out all content from that category
- Check minimum content threshold — if no Watchlist Highlights AND no Fire Deals: skip this member's digest
- Render HTML and plain text from weekly-digest template with member-specific data
- Queue rendered email in SES send queue
- Send in batches of 500 per minute to respect SES rate limits
- Log each send to gd_email_log with member_id, email_type, sent_at, SES message_id
- SES delivery callbacks update log with delivered/bounced/complained status


## 12.8 Email Log and Analytics

CREATE TABLE gd_email_log (
id              BIGINT AUTO_INCREMENT PRIMARY KEY,
member_id       INT           NOT NULL,
email_type      VARCHAR(50)   NOT NULL,
template        VARCHAR(100)  NOT NULL,
ses_message_id  VARCHAR(200)  NULL,
sent_at         DATETIME      NOT NULL,
status          ENUM('queued','sent','delivered','bounced','complained','failed') DEFAULT 'queued',
status_updated  DATETIME      NULL,
INDEX idx_member    (member_id),
INDEX idx_type      (email_type),
INDEX idx_sent      (sent_at)
);


Admin Email Dashboard — ACP
- Send volume by type and date — chart showing daily/weekly send counts per email type
- Delivery rate, bounce rate, complaint rate — updated from SES callbacks
- Member suppression list: members with bounce or complaint flags — admin can review and manually clear
- Digest preview: admin can generate and preview the digest email for any specific member without sending
- Manual send: admin can trigger a specific email type to a specific member for testing
- Template editor: edit any email template from ACP — changes apply to next send
- Admin deal pin interface: pin up to 3 deals to appear in the Fire Deals section of the next digest


## 12.9 Email Acceptance Criteria
- SES domain verification complete — DKIM and SPF records confirmed passing
- Dedicated IP requested and configured
- Price drop alert email sends within 5 minutes of feed import detecting a price change
- VIP price drop alert sends 30 minutes before equivalent free member alert — confirmed with timed test
- Weekly digest generates correctly for a test member with watchlist items, saved alerts, and subscribed states
- Member with no qualifying content receives no digest that week — confirmed
- Category toggle: member with Ammunition OFF receives no ammo content in digest — confirmed
- Standard member digest frequency cannot be changed from weekly — daily option not shown to non-VIP
- VIP member can switch between weekly and daily digest from /account/notifications
- Opt out of digest: member receives no digest emails but continues to receive transactional alerts
- Global unsubscribe link works without login — sets suppress flag, no emails of any type sent
- Bounce handler marks affected member email as invalid — subsequent sends suppressed
- All email templates render correctly in Gmail, Outlook, and Apple Mail — tested with Litmus or Email on Acid
- All templates render correctly on mobile viewport — confirmed
- Every email contains unsubscribe link and physical address — CAN-SPAM compliance confirmed
- UTM parameters present on all links in all templates
- Admin digest preview generates accurate member-specific content
- Admin deal pin appears in correct position in next digest send


# 13. Plugin 10 — GD Community Deal Posts
Community Deal Posts allow admin and registered members to manually post deals from any retailer — whether or not they are a registered platform dealer. This covers big-box retailers (Dunham's Sports, Academy Sports, Bass Pro Shops, Cabela's, Dick's Sporting Goods, Walmart), sporting goods chains, local store sales, in-store-only deals, bundle offers, and any retailer who will never register a feed subscription. This is the community engine that keeps the platform alive between feed updates and surfaces deals no automated system can find.


## 13.1 Confirmed Design Decisions


## 13.2 Deal Post Types


## 13.3 Deal Post Database Schema

CREATE TABLE gd_deal_posts (
id                  INT AUTO_INCREMENT PRIMARY KEY,
post_type           ENUM('product','bundle','storewide','clearance','coupon','price_error')
NOT NULL DEFAULT 'product',
upc                 VARCHAR(20)   NULL,
-- NULL for freeform posts. Indexed when present.
member_id           INT           NOT NULL,
-- Submitter member ID
title               VARCHAR(255)  NOT NULL,
description         TEXT          NULL,
retailer_name       VARCHAR(150)  NOT NULL,
retailer_type       ENUM('online','instore','both') NOT NULL DEFAULT 'online',
store_location      VARCHAR(200)  NULL,
-- City/State or full address for in-store deals
deal_price          DECIMAL(10,2) NULL,
-- NULL for percentage-off or freeform deals
original_price      DECIMAL(10,2) NULL,
-- For showing savings amount
discount_pct        DECIMAL(5,2)  NULL,
-- Calculated or manually entered
deal_url            VARCHAR(500)  NULL,
-- Link to retailer page. NULL for in-store only deals.
promo_code          VARCHAR(100)  NULL,
-- Coupon/promo code if applicable
shipping_cost       DECIMAL(10,2) NULL,
free_shipping       TINYINT(1)    DEFAULT 0,
status              ENUM('pending','active','expired','rejected','featured') DEFAULT 'pending',
source_badge        VARCHAR(50)   DEFAULT 'community',
-- 'community' / 'admin' / 'vip'
upvotes             INT           DEFAULT 0,
downvotes           INT           DEFAULT 0,
comment_count       INT           DEFAULT 0,
view_count          INT           DEFAULT 0,
click_count         INT           DEFAULT 0,
heat_score          TINYINT       DEFAULT 0,
-- Same heat score logic as dealer listings
heat_label          ENUM('cold','warm','hot','fire') DEFAULT 'cold',
posted_at           DATETIME      NOT NULL,
expires_at          DATETIME      NULL,
-- NULL = no auto-expiry. Set by submitter or admin when manually expiring.
manually_expired_by INT           NULL,
-- member_id who manually marked expired (submitter or admin)
manually_expired_at DATETIME      NULL,
flagged_expired     INT           DEFAULT 0,
-- Count of community expired flags
INDEX idx_upc       (upc),
INDEX idx_member    (member_id),
INDEX idx_status    (status),
INDEX idx_expires   (expires_at),
INDEX idx_posted    (posted_at)
);


CREATE TABLE gd_deal_votes (
id          INT AUTO_INCREMENT PRIMARY KEY,
deal_id     INT      NOT NULL,
member_id   INT      NOT NULL,
vote        TINYINT  NOT NULL,  -- 1 = upvote, -1 = downvote
voted_at    DATETIME NOT NULL,
UNIQUE KEY uq_deal_member (deal_id, member_id)
);


CREATE TABLE gd_deal_reputation (
id            INT AUTO_INCREMENT PRIMARY KEY,
member_id     INT          NOT NULL,
deal_id       INT          NOT NULL,
points        INT          NOT NULL,
reason        ENUM('deal_approved','upvote_received','deal_featured','price_error_verified')
NOT NULL,
awarded_at    DATETIME     NOT NULL,
INDEX idx_member (member_id)
);


## 13.4 Deal Submission Form
Accessible at /deals/submit for any registered member. All submissions show 'Pending review — typically approved within 2 hours'. Admin submissions at /deals/submit bypass this notice and go live immediately.


## 13.5 Moderation Flow
All Member Posts — Admin Moderation Required
- Any registered member (including VIP) submits a deal → status = pending → enters admin moderation queue
- Admin reviews: Approve / Reject with reason / Edit then Approve
- On approval: status = active, submitter notified, reputation points awarded (+5)
- On rejection: submitter notified with reason, can revise and resubmit
- Target moderation time: within 2 hours during active hours — shown to submitter at submission as expectation
- If a deal sits unreviewed for 4 hours: auto-notify admin via email
- Member deals show source_badge = 'community' regardless of tier

Admin Deals — No Moderation
- Admin-posted deals bypass moderation — set to status = active immediately
- Admin deals show source_badge = 'admin'
- Admin can set any deal as status = featured — featured deals get prominent placement on /deals and homepage widget


## 13.6 Display on Product Pages
When a product page loads (Plugin 3 Section 4.1.2), active community deal posts for that UPC are fetched and integrated into the price comparison table.

Integration Rules
- Community deal posts appear in the same price comparison table as dealer feed listings
- Source badge differentiates them: dealer feed listings show nothing (default) or a dealer tier badge. Community member posts show [COMMUNITY DEAL] in grey. Admin posts show [ADMIN PICK] in gold. Featured admin deals also show [ADMIN PICK] in gold. [PRICE ERROR] in red for verified price errors.
- Community deals sort by Total Cost alongside dealer listings — if a community deal has a lower total than all dealer listings it ranks first
- Community deals that are in-store only: shown with an [IN-STORE] badge and store location. No 'Buy' button — instead a 'View Deal' button that shows the location details
- Community deals without a URL: shown with location info and a 'In Store Only' note
- Expired community deals: immediately hidden from comparison table when expires_at passes or when flagged_expired reaches 3
- Deals with net downvote score below -5: automatically collapsed behind a 'Show low-rated deals' toggle


## 13.7 Community Voting and Comments
- Any registered member can upvote or downvote any deal post — one vote per member per deal
- Changing vote is allowed — updates the vote record
- Net score (upvotes - downvotes) displayed on deal card
- IPS native comment system attached to each deal post — same comment thread infrastructure as product pages
- Comments allow users to confirm 'Still valid as of [date]', report price errors, or share coupon stacking tips
- 'This deal is expired' button — increments flagged_expired counter. At 3 flags: deal automatically set to status = expired and hidden from all views. Submitter notified.
- Submitter can manually mark their own deal expired at any time from their deal history page at /account/deals
- Admin can manually expire any deal from the admin panel or directly from the deal card on /deals


## 13.8 Reputation System

- Reputation points stored in gd_deal_reputation for full audit trail
- Member total deal reputation points displayed on their profile
- Deal points feed into IPS native leaderboard via the IPS Points system — no separate deal leaderboard page. See Section 16 for consolidated leaderboard architecture.
- Top Deal Poster badge awarded to members in the top 10 on the Deal Hunters leaderboard tab
- Reputation score is a trust signal — admin moderation queue prioritizes reviewing deals from high-reputation members first


## 13.9 Dedicated Deals Feed — /deals
A dedicated page surfacing all active community deal posts across the platform, independent of the product catalog browsing experience. Also powers a homepage widget.

Feed Homepage — /deals
- Featured Deals: admin-pinned deals with [ADMIN PICK] badge — max 3 at any time
- Hottest Right Now: deals sorted by net upvote score in last 24 hours
- Freshest Deals: most recently posted active deals
- Price Errors: verified price error deals in their own section — these drive urgency
- In-Store Deals: deals flagged as in-store only — filterable by state

Feed Filters
- Category: Firearms / Ammo / Accessories / Optics / Bundles / Coupons / Store-Wide
- Retailer: multi-select from all retailers that have been used in deal posts
- Online / In-Store / Both
- Deal Type: Product / Bundle / Coupon / Price Error / Store-Wide
- Min Discount %: slider
- State: for in-store deal filtering

Sort Options
- Hottest (default) — net upvote score
- Newest
- Biggest Discount %
- Expiring Soonest

Homepage Widget
- Top 6 hottest deals from /deals surfaced on platform homepage
- Each card shows: product image, title, deal price, retailer, source badge, net upvote score, time remaining
- 'View All Deals' link to /deals


## 13.10 Retailer Registry
Over time, as deals are posted from the same retailers repeatedly, the platform builds an informal retailer registry. This powers autocomplete in the submission form and enables filtering by retailer across the /deals feed.


CREATE TABLE gd_deal_retailers (
id              INT AUTO_INCREMENT PRIMARY KEY,
name            VARCHAR(150)  NOT NULL,
slug            VARCHAR(160)  NOT NULL UNIQUE,
logo_url        VARCHAR(500)  NULL,
website_url     VARCHAR(500)  NULL,
retailer_type   ENUM('online','instore','both') DEFAULT 'both',
deal_count      INT           DEFAULT 0,
-- Denormalized: total active deals from this retailer
is_registered_dealer TINYINT(1) DEFAULT 0,
-- True if this retailer also has a dealer feed subscription
created_at      DATETIME      NOT NULL,
INDEX idx_slug  (slug)
);


- New retailer name auto-creates a registry entry on first deal post — admin can later add logo and website URL
- is_registered_dealer flag links to the dealer feed system — when a retailer eventually signs up as a dealer, their manual deal posts and their feed listings can be associated
- Retailer pages at /retailers/{slug} show all active deal posts from that retailer


## 13.11 Admin — Deal Post Management
- Moderation queue: pending community deals with Approve, Edit+Approve, Reject actions — sorted by submission time
- Priority queue: deals from high-reputation members surfaced first
- Active deals management: all live deals with extend, expire, feature, remove actions
- Flagged expired queue: deals with flagged_expired >= 3 waiting for admin confirmation
- Deal analytics: most upvoted deals, most active deal posters, most active retailers, deals per category
- Retailer registry management: add logos, website URLs, merge duplicate retailer entries
- Featured deal slots: set up to 3 deals as featured — drag-and-drop ordering


## 13.12 Interaction with Deal Heat Score (Plugin 7)
Community deal posts participate in the heat score system from Plugin 7 with modified rules:
- Heat score for deal posts uses: net upvote score (50% weight), deal price vs catalog MSRP (30% weight), time since posted decay factor (20% weight)
- Price history comparison is not available for community deals since there is no historical price data per retailer — MSRP comparison is used instead
- Fire badge community deals trigger the same early VIP notifications as dealer feed Fire deals
- Community deal heat scores recalculated hourly — not tied to feed import schedule


## 13.13 Acceptance Criteria — Plugin 10
- Admin can post a deal for a non-registered retailer (e.g. Dunham's) — deal appears immediately on the correct product page comparison table with [ADMIN PICK] badge
- Standard member submits a deal — deal enters moderation queue, not visible publicly until approved
- VIP member submits a deal — deal goes live immediately with [VIP DEAL] badge
- Community deal appears in price comparison table sorted correctly by total cost alongside dealer feed listings
- In-store deal shows [IN-STORE] badge and location — no Buy button, View Deal button instead
- Deal with no URL accepted and displayed correctly for in-store deals
- Deal with no expiry date stays live indefinitely — confirmed by checking status after 7+ days
- 3 expired flags auto-set deal status to expired — deal immediately disappears from comparison table and /deals feed
- Submitter can manually expire their own deal from /account/deals — deal disappears immediately
- Admin can expire any deal from admin panel — deal disappears immediately
- Upvote increments upvotes counter. Downvote increments downvotes counter. Changing vote updates correctly.
- Deal with net score below -5 collapses behind 'Show low-rated deals' toggle on product page
- Reputation points awarded correctly: +5 on approval, +1 per upvote, +10 on feature
- Reputation points deducted: -1 per downvote, -2 for deal flagged expired within 24 hours of posting
- /deals feed shows correct sections — Featured, Hottest, Freshest, Price Errors
- Retailer autocomplete in submission form shows existing retailers after 2 characters
- New retailer name creates a gd_deal_retailers entry — visible in admin retailer registry
- Homepage widget shows top 6 hottest deals — updates when deal vote scores change
- Freeform bundle deal with no UPC posts successfully and appears in /deals feed but not on any product page

# 14. Plugin 11 — GD Loadout Builder
The Loadout Builder lets users assemble complete firearms builds — combining a base firearm with optics, lights, suppressors, ammunition, parts, and accessories into a named, shareable loadout. Unlike ArmsAgora's static implementation, this builder shows real-time dealer pricing from live feeds for every item, calculates the true delivered cost of the entire build, and allows item swapping to compare total build costs. Public loadouts drive community engagement and long-tail SEO through unique shareable build pages.


## 14.1 Confirmed Design Decisions


## 14.2 Free vs VIP Feature Comparison

The key VIP differentiators for loadouts are private visibility (important for users planning builds they do not want to share publicly), per-item notes, and the ability to compare 3 alternatives simultaneously in swap mode rather than 1. These are meaningful quality-of-life upgrades without gate-keeping core functionality.


## 14.3 Loadout Slots

Slots are guidance not hard restrictions. A mismatch warning is shown if a product type does not match the slot convention, but the user can proceed. Goal is to guide, not gatekeep. The 10 core slots are always shown. Users can add more from the predefined library or create fully custom-labeled slots.


### 14.3.1 Expandable Slot System
Beyond the 10 core slots, users can add any number of additional slots from a predefined library or create custom-labeled slots for anything not covered. Added slots appear in an Added Slots group below the core slot grid with a dashed border to distinguish them.


- Add a slot button opens an inline slot picker — chips organized by category. Click chip to add. Already-added chips show checkmark and are disabled.
- Custom field at bottom of picker — type any name and click Add Slot
- Hover any slot to reveal a red X remove button
- No maximum on added slots
- Slot picker closes on outside click or Escape


### 14.3.2 Forum Post Rate Limiting
Rate limiting prevents spam and feed manipulation. Two abuse patterns are addressed: rapid-fire posting and near-duplicate builds flooding the feed.


The edit-vs-repost rule is the primary spam prevention mechanism. A loadout can only be formally shared once — all subsequent changes go through Update Forum Thread. Rate limits catch the secondary case of someone creating multiple different builds to flood the feed.

- gd_loadout_forum_posts table stores member_id and posted_at for each share — queried on every share attempt
- Cooldown timer: last_shared_at stored on loadout record — client shows countdown from this timestamp
- Duplicate check: compare item UPCs against all other shared loadouts by this member on share attempt
- All rate limit state is server-side — not client-side — to prevent bypass


CREATE TABLE gd_loadout_forum_posts (
id              INT AUTO_INCREMENT PRIMARY KEY,
loadout_id      INT      NOT NULL,
member_id       INT      NOT NULL,
forum_post_id   INT      NOT NULL,
forum_thread_url VARCHAR(500) NOT NULL,
posted_at       DATETIME NOT NULL,
-- Used for rolling 24-hour window rate limit queries
INDEX idx_member    (member_id),
INDEX idx_loadout   (loadout_id),
INDEX idx_posted_at (posted_at)
);


### 14.3.3 Builder UI Mockup — Tier 3 Layout
Two-column layout: left column has the base firearm hero image, core slot grid, added slots group, and slot picker. Right column is a persistent summary with itemized costs, compliance flags, and action buttons. Filled slots show a color-coded icon, item name, and live lowest price. Active slot shows a blue border. Added slots show a dashed border. Empty slots show an Add prompt.


## 14.4 Database Schema

CREATE TABLE gd_loadouts (
id                    INT AUTO_INCREMENT PRIMARY KEY,
member_id             INT           NOT NULL,
name                  VARCHAR(150)  NOT NULL,
slug                  VARCHAR(160)  NOT NULL,
description           TEXT          NULL,
visibility            ENUM('public','unlisted','private') DEFAULT 'unlisted',
use_case              VARCHAR(100)  NULL,
upvotes               INT           DEFAULT 0,
comment_count         INT           DEFAULT 0,
follow_count          INT           DEFAULT 0,
view_count            INT           DEFAULT 0,
total_min_price       DECIMAL(10,2) NULL,
-- Denormalized: sum of cheapest dealer total delivered cost per item.
-- Recalculated after each dealer feed import that affects any item in this loadout.
total_items           INT           DEFAULT 0,
has_nfa_item          TINYINT(1)    DEFAULT 0,
has_state_restriction TINYINT(1)    DEFAULT 0,
created_at            DATETIME      NOT NULL,
updated_at            DATETIME      NULL,
UNIQUE KEY uq_member_slug (member_id, slug),
INDEX idx_member     (member_id),
INDEX idx_visibility (visibility),
INDEX idx_upvotes    (upvotes)
);


CREATE TABLE gd_loadout_items (
id              INT AUTO_INCREMENT PRIMARY KEY,
loadout_id      INT           NOT NULL,
upc             VARCHAR(20)   NOT NULL,
slot_type       ENUM('base_firearm','optic','weapon_light','laser','suppressor',
'foregrip','sling','holster','ammo','cleaning','extra') DEFAULT 'extra',
custom_label    VARCHAR(100)  NULL,  -- for 'extra' slot items
sort_order      TINYINT       DEFAULT 0,
notes           VARCHAR(300)  NULL,  -- VIP only: visible to owner only
added_at        DATETIME      NOT NULL,
INDEX idx_loadout (loadout_id),
INDEX idx_upc     (upc)
);


CREATE TABLE gd_loadout_votes (
id INT AUTO_INCREMENT PRIMARY KEY, loadout_id INT NOT NULL, member_id INT NOT NULL,
voted_at DATETIME NOT NULL, UNIQUE KEY uq (loadout_id, member_id)
);
CREATE TABLE gd_loadout_follows (
id INT AUTO_INCREMENT PRIMARY KEY, loadout_id INT NOT NULL, member_id INT NOT NULL,
followed_at DATETIME NOT NULL, UNIQUE KEY uq (loadout_id, member_id)
);


## 14.5 Builder Interface
Canvas Layout
- Left panel: 10 slot cards — filled slots show product image, name, lowest price. Empty slots show Add prompt.
- Right panel: OpenSearch product search with category filter — results show lowest dealer price and heat badge
- Adding: click result, select target slot — product populates slot
- Replacing: click occupied slot → replace dialog with search → shows total build cost delta for each candidate
- Removing: X button on any slot item
- Extras section: unlimited freeform items with custom labels, drag-to-reorder

Build Summary Panel — always visible while building
- Total Items count
- Total Build Cost: sum of cheapest delivered price per item — updates live as items added or removed
- Per-item price breakdown: expandable table — item, cheapest dealer, dealer price, shipping, total delivered, heat badge
- Link to full price comparison page for each item
- Swap Mode: Free members compare 1 alternative at a time. VIP members compare up to 3 side-by-side. Each alternative shows resulting total build cost change — 'Swapping saves $47'
- Compliance summary banner: see Section 14.7

Loadout Metadata Fields
- Name: required
- Description: optional freeform
- Use Case: Home Defense / Concealed Carry / Hunting / Competition / Range / Tactical / Collection
- Visibility: Public / Unlisted / Private (Private option visible only to VIP members)


## 14.6 Pricing Integration
- Total Build Cost = sum of total_min_price per UPC across all loadout items — labeled 'Est. Minimum Build Cost (each item from cheapest dealer)'
- Recalculated on the loadout record after any dealer feed import that changes the lowest price for any item in that loadout
- Per-item table: cheapest dealer name, price, shipping, total delivered cost per item
- [ALL-TIME LOW] and [90-DAY LOW] badges shown per item where applicable — immediately communicates deal quality
- Item swap comparison shows the total build cost delta, not just per-item price — this is the key differentiation from ArmsAgora's static pricing


## 14.7 Compliance Integration
- Per-item badges: [NFA ITEM] red on suppressors/SBRs, [FFL REQUIRED] orange on all firearms, [STATE RESTRICTED] orange for user's state
- Build-level compliance summary banner at top of loadout page and builder: 'X NFA items (Form 4 + $200 tax stamp each), X items require FFL transfer, X items restricted in [state]'
- 'What does this mean?' expandable plain-English explanation per flag type
- FFL locator link embedded in summary for builds containing firearms — links to /tools/ffl-locator
- If no compliance issues: green 'No compliance issues detected for [state]' banner


## 14.8 Wishlist Integration
- 'Add All to Wishlist' button on every loadout — adds all UPCs to user's default wishlist (or chosen wishlist for VIP)
- Items already in watchlist/wishlist shown with checkmark in per-item breakdown
- Price drop notifications reference the loadout: 'Price drop on item in your [loadout name] build: [item] dropped to $X'
- Implementation: when a price alert fires, check if that UPC exists in any of the member's loadout items — if yes, append loadout name to the notification


## 14.9 Community Features
Public Loadout Page — /loadouts/{username}/{slug}
- Full build displayed with all items, images, live prices, compliance flags
- Total build cost prominently displayed
- Upvote button (one per member), Follow button, IPS comment thread, Share button
- Owner sees follower count and upvote count
- Private loadouts return 403 for any non-owner — not indexed, not in sitemap

Follow Notifications
- When a followed loadout is updated: followers notified — '[username] updated their [loadout name]: [item] was added/removed'
- Followers receive no price alerts — those belong to the loadout owner via wishlist integration


## 14.10 Loadouts Hub — /loadouts
- Sections: Featured Builds (admin-curated, up to 6), Trending (upvote velocity last 7 days), Top Rated (all-time, min 10 upvotes), Recently Updated, Budget Builds (total under $500)
- Use Case tabs: Home Defense / Concealed Carry / Hunting / Competition / Range
- Filters: Use Case, Budget range, Base Firearm Type, Base Firearm Brand, NFA toggle, State Compatible toggle
- Only Public loadouts appear in hub — Unlisted and Private never surface here
- Homepage widget: top 4 trending loadouts — name, use case, total cost, owner, upvote count


## 14.11 SEO
- Title: '[Loadout Name] — [Use Case] Build — [Base Firearm Brand] | GunRack'
- Meta description: '[Use Case] build featuring [Base Firearm] with [Optic] and [N] accessories. Est. total cost: $[total]. Prices from [N] dealers.'
- ItemList schema on each public loadout page — marks each item for Google
- sitemap-loadouts.xml added to sitemap index — public loadouts only, private and unlisted excluded
- Each item in a public loadout links to its product page — distributes internal link equity


## 14.12 Acceptance Criteria
- All members can create unlimited loadouts — no quantity limit shown for free or VIP
- Free member: only Public and Unlisted visibility options shown — Private option not visible
- VIP member: all three visibility options shown — Private loadout returns 403 for non-owner
- Free member: swap mode compares 1 alternative at a time — 'Compare more' prompt shown with upgrade CTA
- VIP member: swap mode shows up to 3 alternatives simultaneously
- Free member: notes field not shown in builder
- VIP member: notes field visible per item in builder — notes saved, visible only to owner on loadout page
- Total build cost sums correctly — verified against manual calculation
- total_min_price on loadout record updates after a feed import changes an item's lowest price
- Swap comparison shows correct total build delta, not just per-item delta
- [NFA ITEM] badge shown on suppressor. Compliance summary count accurate.
- Green 'no issues' banner when build has no restricted items for user's state
- Add All to Wishlist adds all items — confirmed in wishlist table
- Price drop notification on loadout item includes loadout name in notification text
- Public loadout at /loadouts/{username}/{slug} accessible without login
- Private loadout returns 403 for non-owner — confirmed
- Unlisted loadout: accessible via direct link, not in /loadouts browse, not in sitemap
- Follow triggers notification when owner adds an item to a public loadout
- Homepage widget shows top 4 trending — updates as upvote scores change
- Public loadout outputs correct title, meta description, and ItemList schema
- sitemap-loadouts.xml contains only public loadout URLs — private and unlisted absent

# 15. Plugin 12 — GD Loadout Forum Integration
Plugin 12 bridges the Loadout Builder (Plugin 11) with IPS Forums to create a community feedback loop. Users can share a loadout directly to the forums, where other members view the live build, suggest alternative items, vote on the build, and fork it into their own account. The loadout owner can accept suggestions with one click, updating their actual loadout. This creates something no firearms platform has — a structured community build review system where discussion directly improves the build.


## 15.1 Confirmed Design Decisions


## 15.2 Posting a Loadout to Forums
From any loadout page (owner's view) or from the builder, a 'Share to Forums' button initiates the forum post flow.

Share to Forums Flow
- Owner clicks 'Share to Forums' on their loadout
- Modal opens with: post title (pre-filled from loadout name, editable), post body (optional — owner can add context, story, or questions for the community). Category is fixed: Builds & Loadouts — not selectable.
- Owner submits — IPS forum post created in the Builds & Loadouts category with the loadout embed automatically inserted
- Loadout record updated: forum_post_id and forum_thread_url stored
- Owner notified when the first reply or upvote is received on the forum post

A loadout can only be shared to forums once. If the owner wants to re-share an updated version, they must create a new forum post — the old one remains as a historical record. The 'Share to Forums' button changes to 'View Forum Thread' after first share.


## 15.3 Loadout Embed in Forum Posts
The loadout renders as a custom IPS post component inside the forum thread. It uses a collapsed-by-default design to avoid overwhelming the forum thread view for users who are just browsing titles.

Collapsed State — Summary Card
- Loadout name and owner username
- Use case tag (Home Defense, Competition, etc.)
- Base firearm name and thumbnail image
- Total item count and total build cost — live from current dealer feeds
- Compliance badges: [NFA] [FFL REQUIRED] [STATE RESTRICTED] where applicable
- 'Expand Build' button
- Fork button — 'Save to My Loadouts' — visible in collapsed state. VIP members: one-click fork. Free members: button shows VIP upgrade prompt.
- Free members: 'View Full Build' link in collapsed state leads to expanded embed with manual recreate path

Expanded State — Full Inline Build
- All 10 slots rendered with product images, names, and current lowest dealer price per item
- Per-item: heat badge, [ALL-TIME LOW] / [90-DAY LOW] badge if applicable
- Total build cost recalculated from live feed data — 'Prices updated [timestamp]' shown below total
- Per-item link to full price comparison page — opens in new tab
- Compliance summary section if any flags exist
- 'Collapse Build' button to return to summary card
- Forking and suggestion actions always visible in expanded state


## 15.4 Fork — Save to My Loadouts
VIP members can fork any public or unlisted loadout from the forum embed in one click. Free members can view the full build and manually recreate it by adding each item individually from the product search — the embed shows each item with a direct link to its product page to make this straightforward. Guests see a login prompt.

The fork gate is a meaningful VIP differentiator — builds can contain 10+ items and manually recreating them is tedious. This creates genuine upgrade motivation without blocking free members from accessing build information or benefiting from community suggestions.

VIP Fork Flow — One Click
- VIP member clicks 'Save to My Loadouts' button on the forum embed (visible in both collapsed and expanded states)
- Rename prompt: 'Name this build:' with original name pre-filled — member can accept or change
- On confirm: copy of the loadout created with all items, slot assignments, use case, and visibility set to Unlisted
- Member taken directly into the builder with the forked loadout loaded and ready to customize
- Fork attribution stored: 'Forked from [original loadout name] by [username]'

Free Member — Manual Recreate Path
- Free member sees the full expanded build with all items visible
- 'Save to My Loadouts' button shows a VIP upgrade prompt: 'Fork this build in one click — upgrade to VIP'
- Below the upgrade prompt: 'Want to recreate this build manually? Each item below links to its product page.'
- Each item in the expanded embed shows a '+ Add to Loadout' button that links to the product page where they can add it to an open loadout from the product page
- Free member can still benefit from the build by manually adding items one at a time — it just requires more effort than a VIP fork

Fork Attribution
- Forked loadouts display 'Based on [original loadout name] by [username]' below the loadout title
- Link in attribution routes to the original loadout page if it still exists
- Original owner receives an IPS notification: '[username] forked your [loadout name] build'
- Fork count displayed on the original loadout page: 'Forked X times'

Fork Database Fields — added to gd_loadouts

-- Additional columns on gd_loadouts table:
ALTER TABLE gd_loadouts ADD COLUMN forked_from_id    INT  NULL;
ALTER TABLE gd_loadouts ADD COLUMN fork_count        INT  DEFAULT 0;
ALTER TABLE gd_loadouts ADD COLUMN forum_post_id     INT  NULL;
ALTER TABLE gd_loadouts ADD COLUMN forum_thread_url  VARCHAR(500) NULL;
-- forked_from_id: references gd_loadouts.id of the original. NULL if not a fork.
-- fork_count: denormalized count of how many times this loadout has been forked.


## 15.5 Structured Swap Suggestions
In addition to regular forum replies, members can submit structured item suggestions tied to specific slots in the build. These appear in a dedicated Suggestions tab on the forum thread — separate from general discussion — making it easy for the owner to review and act on them.

Submitting a Suggestion
- 'Suggest a Swap' button in the forum post below the expanded build
- Modal opens: select which slot to suggest for, then search the product catalog for the suggested replacement item
- Optional reason field (280 char max): 'This optic offers better clarity at half the price'
- Suggestion submitted — appears in the Suggestions tab on the forum post
- Owner notified of new suggestion via IPS notification

Suggestions Tab Display
- List of all suggestions for this forum post, sorted by upvotes
- Each suggestion card shows: slot targeted, suggested item image and name, current lowest price, suggester username, reason text, upvote count
- Price delta shown: 'Would save $X' or 'Costs $X more' compared to the current item in that slot
- Any member can upvote a suggestion — most upvoted suggestions surface first
- Owner sees Accept and Reject buttons on each suggestion

Accepting a Suggestion
- Owner clicks Accept on a suggestion
- Confirmation dialog: 'This will replace [current item] with [suggested item] in your [slot name] slot. Your loadout will be updated.'
- On confirm: gd_loadout_items record for that slot updated to the suggested UPC
- Loadout total_min_price recalculated
- Suggestion status set to 'Accepted' — green badge shown on suggestion card
- Suggester receives IPS notification: '[owner] accepted your suggestion for their [loadout name] build'
- Forum post embed updates to show the new item in that slot on next load

Rejecting a Suggestion
- Owner clicks Reject — optional reason field (shown to suggester)
- Suggestion status set to 'Rejected' — greyed out on Suggestions tab
- Suggester notified with rejection reason if provided

Suggestion Database Schema

CREATE TABLE gd_loadout_suggestions (
id              INT AUTO_INCREMENT PRIMARY KEY,
loadout_id      INT           NOT NULL,
forum_post_id   INT           NOT NULL,
suggester_id    INT           NOT NULL,
slot_type       VARCHAR(50)   NOT NULL,
current_upc     VARCHAR(20)   NULL,
suggested_upc   VARCHAR(20)   NOT NULL,
reason          VARCHAR(280)  NULL,
upvotes         INT           DEFAULT 0,
status          ENUM('pending','accepted','rejected') DEFAULT 'pending',
rejection_reason VARCHAR(280) NULL,
created_at      DATETIME      NOT NULL,
actioned_at     DATETIME      NULL,
INDEX idx_loadout (loadout_id),
INDEX idx_forum   (forum_post_id)
);


## 15.6 Forum Engagement — Loadout Popularity Score
Forum post activity on a loadout-linked thread feeds into the loadout's popularity score in the /loadouts hub, giving the hub a richer signal than upvotes alone.


- Popularity score used for Trending sort on /loadouts hub — calculated as weighted combination of upvotes, fork_count, comment_count, and suggestions_count
- Forum upvotes and reactions synchronized to loadout record via IPS hook — fires after every reaction event on a forum post that has a linked loadout


## 15.7 Builds & Loadouts Forum Category
A dedicated IPS forum category must be created before Plugin 12 is deployed:

- Category name: Builds & Loadouts
- Sub-forums: Home Defense Builds / Concealed Carry Builds / Hunting Builds / Competition Builds / Range Builds / General Builds
- Loadout posts are the primary content type — non-loadout posts allowed but the category is positioned as build-focused
- Category rules pinned post: explains how to use the Share to Forums feature, how suggestions work, and community guidelines for build feedback
- Moderators can merge duplicate build threads, lock outdated builds, or feature exceptional builds
- No cross-posting mechanism — all loadout forum posts live exclusively in Builds & Loadouts
- Category indexed by OpenSearch — build forum posts surfaceable via platform-wide search


## 15.8 Interaction with VIP Membership
- All members (Free and VIP) can share loadouts to forums, fork builds, and submit suggestions
- VIP members: when forking, their private visibility option is available for the forked copy
- VIP members: forked loadout opens in builder with per-item notes field active
- Free members: forked loadout opens in builder with public/unlisted visibility options only
- Suggestion acceptance: owner can accept suggestions regardless of tier


## 15.9 Acceptance Criteria — Plugin 12
- 'Share to Forums' button appears on loadout page for the owner only
- Forum post created in Builds & Loadouts category with embed on submission
- Cross-post option: selecting a category creates second post with cross-post attribution note
- 'Share to Forums' button changes to 'View Forum Thread' after first share — second share not possible
- Collapsed embed shows correct summary — loadout name, base firearm, total cost, fork button
- Expanded embed shows all items with current live prices — total reflects current dealer feed data
- 'Prices updated [timestamp]' note updates after a dealer feed import changes any item's price
- VIP member: fork button visible in collapsed and expanded embed states
- VIP member: rename prompt appears with original name pre-filled — fork completes and lands in builder
- Free member: fork button shows VIP upgrade prompt — no loadout created
- Free member: expanded embed shows '+ Add to Loadout' per item linking to product pages
- Guest: login prompt shown — no fork option visible
- After VIP fork: loadout shows fork attribution, original fork_count increments
- Cross-post category selector does NOT appear in Share to Forums modal — Builds & Loadouts is fixed destination
- Forum post created in Builds & Loadouts only — confirmed no duplicate post in other categories
- Forked loadout shows 'Based on [original] by [username]' attribution
- Original loadout fork_count increments on each fork
- Original owner receives fork notification
- 'Suggest a Swap' button opens modal with slot selector and product search
- Suggestion appears in Suggestions tab with price delta shown correctly
- Other members can upvote a suggestion — count increments
- Owner clicks Accept — loadout item updated, suggester notified, suggestion shows Accepted badge
- Owner clicks Reject with reason — suggester notified with reason, suggestion greyed out
- Forum post upvote increments loadout upvotes field via IPS hook
- Fork count feeds Trending sort on /loadouts hub — verified with test forks

# 16. Consolidated Leaderboard — IPS Native Integration
Rather than building multiple separate leaderboard pages across the platform, all community and dealer rankings are consolidated into a single leaderboard powered by the IPS native leaderboard and Points system. Users see one leaderboard with multiple tabs — each tab filters by activity type. This is simpler to build, simpler for users to navigate, and works with IPS architecture rather than around it.


## 16.1 Architecture Overview
IPS provides a native leaderboard and a Points system that tracks member reputation across all content types. Custom point types can be registered by plugins and fed into the IPS Points system via the IPS API. The leaderboard then surfaces these points with tab-based filtering.


## 16.2 Leaderboard Tab Structure

The Dealers tab uses a different data model than the other tabs — it pulls from dealer-specific metrics rather than IPS reputation points. It renders via a custom tab component but lives within the same consolidated leaderboard page. Users see one URL, one page, six tabs.


## 16.3 Point Types per Plugin

The gd_deal_reputation table specified in Plugin 10 Section 13.3 is retained as an audit log but points are also mirrored into the IPS Points system for leaderboard display. The audit table remains useful for admin reporting and reversals — the IPS Points system is the display layer.


## 16.4 Badges Triggered by Leaderboard Milestones
IPS native badge system configured in ACP to award badges when point thresholds are reached:


## 16.5 Member Profile — IPS Native Profile Extension
IPS Community Suite ships a full member profile system at /profile/{member_id}-{username}/. It includes avatar, cover photo, join date, post count, IPS reputation score, badges, an About tab, a Content tab (all posts, reviews, etc.), and an Activity tab. GunRack does not build a new member profile page. Instead it extends the existing IPS profile using IPS's native profile tab extension system, adding four custom tabs that surface GunRack-specific member activity alongside the built-in tabs.

The custom tabs registered here appear inside the existing IPS profile page — same URL, same header, same avatar and bio. Members see one profile page with their existing IPS tabs plus the GunRack tabs. No routing, no duplicate profile infrastructure, no maintaining a separate profile template.


### 16.5.1 IPS Profile Tab Extension — How It Works
IPS provides a profile tab extension point: any application can register a class that extends \IPS\core\Profile\Tab. IPS discovers and renders registered tabs automatically. Each tab class implements a getOutput() method that returns the tab's HTML content. Tab ordering is configurable in ACP → Customization → Profile Tabs.


- Tab registration happens in the plugin's setup.php install file — tabs appear automatically after install with no ACP configuration required
- Tab visibility is controlled by IPS's standard profile privacy settings — member can hide individual tabs from their profile in their account settings
- Tab ordering relative to built-in IPS tabs is set in ACP → Customization → Profile Tabs — no code change required to reorder
- Each tab only renders when active — no performance cost for inactive tabs


### 16.5.2 Custom Profile Tab — Deals
Shows the member's community deal post history. Only visible if the member has posted at least one deal.


### 16.5.3 Custom Profile Tab — Reviews
Shows the member's product review history. Only visible if member has posted at least one approved review.


### 16.5.4 Custom Profile Tab — Builds
Shows the member's loadout builds. Visibility per loadout respects the loadout's own visibility setting — Private loadouts never appear even on the member's own public profile (they are accessible only in the builder itself).


### 16.5.5 Custom Profile Tab — Wishlists
Shows the member's wishlists that are set to Public or Unlisted visibility. Private wishlists are never shown on the profile.


### 16.5.6 IPS Profile Fields — GunRack Additions
IPS supports custom member profile fields added via ACP → Members → Custom Profile Fields. GunRack adds the following fields to the standard IPS member profile:


- All custom profile fields are added via IPS ACP — no code changes required to add, remove, or rename fields
- Fields appear in the IPS 'Edit Profile' form automatically after creation in ACP
- Home state field value is read by Plugin 7 compliance notification system — no separate storage needed


### 16.5.7 What IPS Handles Natively — No Custom Work
The following profile elements are built into IPS and require zero custom development:


### 16.5.8 Acceptance Criteria — Member Profile Extensions
- Install GunRack application — four custom tabs appear on IPS member profile pages automatically
- Deals tab: member with approved deal posts sees their deals listed with vote scores and status badges
- Deals tab: member with no approved deals sees no Deals tab — tab is completely absent from profile
- Reviews tab: approved reviews visible with helpful vote counts and verified purchase badges where applicable
- Builds tab: Public and Unlisted loadouts visible — Private loadouts completely absent with no count shown
- Wishlists tab: Public and Unlisted wishlists visible — Private wishlists absent
- Home state profile field visible in Edit Profile form — value read correctly by Plugin 7 compliance system
- Preferred calibers field saves and retrieves multi-select values correctly
- Tab order configurable in ACP → Customization → Profile Tabs without code changes
- Tab visibility respects IPS member privacy settings — member can hide individual tabs
- All IPS native profile features (avatar, bio, follow, messages) continue working correctly after GunRack tab installation


## 16.6 Implementation Notes for Developer
- Register each custom point type during plugin install using IPS\Points\Api::addPoints() or equivalent IPS 4.7 method — consult IPS developer documentation for exact API
- Each point-awarding event in Plugin 4, 10, and 11 must call the IPS Points API in addition to writing to the plugin's own audit table
- The Dealers tab requires a custom IPS leaderboard extension that queries the dealer metrics tables rather than IPS Points — this is a custom tab component, not a standard points tab
- Time filters (This Week / This Month / All Time) apply to all tabs including Dealers — dealer scores should be calculable over each time window
- Leaderboard page URL: standard IPS leaderboard URL — no custom routing needed
- The /deals/leaderboard URL referenced in earlier spec drafts is deprecated — redirect to IPS native leaderboard Deals tab if that URL is ever visited
- The /dealers/leaderboard URL referenced in Plugin 7 is deprecated — redirect to IPS native leaderboard Dealers tab


## 16.7 Acceptance Criteria — Leaderboard
- IPS leaderboard page shows six tabs: Overall, Deal Hunters, Reviewers, Builders, Forum, Dealers
- Deal approved in Plugin 10 — member's deal_points increase, Deal Hunters tab ranking updates
- Review approved in Plugin 4 — member's review_points increase, Reviewers tab ranking updates
- Loadout upvoted in Plugin 11 — owner's loadout_points increase, Builders tab ranking updates
- Overall tab shows combined points across all three custom point types plus IPS native reputation
- Forum tab shows standard IPS post count and reaction score — no custom work, just native
- Dealers tab shows dealer metrics — response rate, resolution rate, dealer score — different from community tabs
- Time filters work across all tabs including Dealers
- Top Deal Poster badge awarded to correct member when they reach top 10 on Deal Hunters tab
- Member profile shows point breakdown per type — deal points, review points, loadout points
- /deals/leaderboard redirects to IPS leaderboard Deals tab
- /dealers/leaderboard redirects to IPS leaderboard Dealers tab

# 17. GunRack Deals Application — IPS Homepage Application
GunRack's homepage is built as a first-class IPS application — not a manually coded standalone page. IPS Community Suite allows any installed application to be designated as the site homepage via ACP → System → Site Promotion → Default Application. The GunRack Deals Application registers itself as an IPS application, sets its front controller as the default homepage route, and delivers its full widget-based layout through IPS's native template engine. This gives the homepage IPS's built-in page caching, theme inheritance, mobile responsiveness, member context, and language system for free — no custom routing or request handling required outside the IPS framework.

This approach means the homepage is not a separately maintained HTML file or a WordPress-style page builder. It is an IPS application front controller that renders IPS templates with data passed from PHP. Admin controls all layout and content through the GunRack ACP settings panel described in this section — not through template edits.


## 17.1 IPS Application Registration
The GunRack Deals Application is structured as a standard IPS application package with the following components:


## 17.2 Setting the Application as Homepage
After installation, admin sets the GunRack Deals Application as the site homepage in one step:

- In IPS ACP: navigate to System → Site Promotion → Default Application
- Select 'GunRack Deals' from the application list
- Save — the site root (gunrack.deals/) now routes to the GunRack Deals front controller

No .htaccess changes, no custom routing rules, no template hacks. IPS handles the routing entirely. The application registers the correct default module and controller in its Application.php so IPS knows what to render when the site root is requested.

The /deals URL route continues to work as a standalone deals feed page (Plugin 10) — the homepage application and the /deals page are separate routes. The homepage shows a curated widget layout; /deals shows the full community deal feed.


## 17.3 ACP Settings Panel — Organization
The GunRack ACP settings panel is the single place where admin controls everything about how the platform looks, behaves, and presents content — without touching code or templates. It lives at ACP → GunRack and is organized into tabbed sections:


## 17.4 Homepage Widget System
The homepage is built from a set of independently configurable widgets. Each widget maps to a PHP data-fetching method in the front controller and a corresponding .phtml template file. Admin controls which widgets are active, their order, their content configuration, and the overall page layout from the Homepage tab in ACP.


### 17.4.1 Layout Mode

- Layout mode switch applies immediately on save — no cache flush required
- Sidebar widgets are only rendered in two-column and three-section modes — they are completely absent from page output in full-width mode
- Mobile layout is always single-column regardless of desktop layout mode — sidebar widgets stack below main widgets on mobile


### 17.4.2 Widget Inventory and Settings
Each widget has a master on/off toggle and its own settings panel accessible by clicking the widget row in the Homepage tab. Widgets are reordered by drag-and-drop within their column (main vs sidebar).


- Widget settings are saved per-widget independently — changing Fire Deals settings does not require re-saving other widgets
- All widget section titles are editable text fields — admin can rename 'Fire Deals Strip' to 'This Week's Best Prices' or any custom label without code changes
- Disabled widgets are fully absent from page HTML output — not hidden with CSS, not rendered and display:none. Zero performance cost for disabled widgets.
- Preview mode: before publishing homepage changes, admin can preview the new layout in a full-screen preview iframe within ACP — see exactly what the homepage will look like before it goes live
- Save and publish vs save as draft: admin can save a homepage layout configuration as a draft and publish later — useful for pre-staging a seasonal layout change


### 17.4.3 Hero Banner — Extended Settings
The hero banner is the highest-visibility element on the homepage and has the most configuration options. It supports multiple display modes:


- Hero height: configurable in px — default 420px desktop, 280px mobile. Min 280px, max 720px.
- Text color override: if background image makes default text color unreadable, admin sets a text color override for that banner
- CTA buttons: primary button uses platform accent color by default. Secondary button uses outline style. Both colors independently overridable per banner.
- Mobile visibility: admin can independently show/hide the hero on mobile — some sites prefer a more compact mobile entry


### 17.4.4 Admin Pin System
Multiple widgets support admin pinning — the ability to manually select specific products, deals, or loadouts that always appear in that widget, overriding the algorithmic sort. This is the primary tool for promoting specific items without touching code.

- Fire Deals Strip pin: type a UPC or product name into a search field in ACP, click Add Pin. Pinned products fill the first N slots in the strip. Drag-and-drop to reorder pins. Remove button on each pin.
- Featured Deals Grid pin: same search-and-pin interface. Up to 12 pins. Remaining slots filled by fallback sort.
- Trending Loadouts pin: search by loadout name or owner username. Pin up to 6 loadouts to always appear in the widget.
- Admin pins never expire automatically — admin manually removes them. A pin indicator badge shows on pinned items in the admin list so they are visually distinct from algorithm-selected items.
- All pin configurations are independent per widget — pinning a product in Fire Deals Strip does not affect Featured Deals Grid


## 17.5 Appearance Settings


### 17.5.1 Branding


### 17.5.2 Color Scheme

- Selecting a pre-built theme (see 17.5.4) populates all color pickers with that theme's defaults — admin can then override individual colors
- 'Preview changes' button renders current color settings in a preview iframe before publishing
- 'Reset to theme defaults' restores all color pickers to the currently selected theme's defaults without affecting layout or content settings


### 17.5.3 Typography


### 17.5.4 Pre-Built Themes

- Switching themes applies all color and typography defaults for that theme — admin color overrides are cleared on theme switch (confirmation dialog shown)
- 'Save as custom theme' button: if admin has made significant customizations they want to preserve, save the current color/font configuration as a named custom theme — reapply later in one click
- Theme preview: apply theme in preview mode before committing — see the full homepage in the new theme without affecting the live site


### 17.5.5 Custom CSS and Advanced
- Custom CSS field: raw CSS injected into the page <head> on every front-end page — for minor overrides that don't justify a template edit. Changes apply immediately on save.
- IPS native theme editor: ACP → Customization → Themes provides full template and CSS editing with IPS's built-in override system — available out of the box, no custom development required
- GunRack-specific addition: 'Reset GunRack styles' button — restores all GunRack branding fields to current theme defaults without affecting IPS theme customizations or templates


## 17.6 Feature Toggles
Master on/off switches for every major feature. When a feature is disabled, all associated UI elements are completely removed from page output — no broken links, no empty pages, no 404s. The feature is as if it was never installed from the user's perspective.


## 17.7 Per-Plugin Settings — Summary
Each plugin's settings are documented in detail in their respective sections (Sections 2 through 16). The ACP panel surfaces those settings under the tabs listed in Section 17.3. For quick reference, the most commonly adjusted settings per plugin:


## 17.8 Acceptance Criteria — Admin Settings Panel and Homepage Application
- IPS application installs without errors — appears in ACP → System → Applications list
- Setting application as homepage via ACP → System → Site Promotion → Default Application routes gunrack.deals/ to GunRack Deals front controller — confirmed with browser
- Homepage renders all active widgets in correct order — verified against ACP drag-and-drop configuration
- Layout mode switch (full-width vs two-column vs three-section) applies immediately on save — sidebar widgets absent from HTML in full-width mode
- Disabled widget completely absent from page HTML — confirmed with browser inspector, no display:none anywhere
- Preview mode shows correct homepage before publishing — preview matches published result
- Save as draft: draft layout does not affect live homepage — publish button pushes draft to live
- Hero banner rotating mode: banners rotate on configured interval — verified with 3-second interval
- Hero banner video mode: video auto-plays muted and loops — falls back to poster image on mobile
- Admin pin: pinning a product to Fire Deals Strip places it in slot 1 — remainder filled by heat score sort
- Admin pin reorder: drag-and-drop reorder of pins changes their slot position immediately
- Widget section title override: custom title appears on live homepage — not the hardcoded default
- Color scheme change: primary color updates header, active nav, and primary buttons simultaneously — no partial updates
- Typography change: heading font change applies to all H1-H4 across homepage and browse pages
- Theme switch: all color pickers update to theme defaults — confirmation dialog shown before clearing custom colors
- 'Save as custom theme' saves current color/font config — re-applying it restores exact state
- Custom CSS field: CSS injects into page head — verified with browser inspector showing injected block
- Feature toggle OFF: associated nav items, buttons, and pages are fully absent — no 404 links
- Maintenance mode ON: public pages show maintenance message — ACP and /dealers/dashboard remain accessible
- All settings persist across IPS upgrades — stored in IPS Settings table, not in custom tables that could be dropped

# 18. Dealer Onboarding — Wizard and Help System
The dealer onboarding experience determines whether dealers successfully connect their feed, verify their listings, and become active paying participants on the platform. A dealer who completes onboarding correctly is a retained customer. A dealer who gets confused during feed setup churns before their first payment. This section specifies the 7-step onboarding wizard, the contextual help system, and the searchable help center.


## 18.1 Onboarding Wizard Overview
The wizard launches automatically on first dealer login after subscription purchase. It is skippable at any step — a 'Skip for now' link is present on every step — but the dashboard shows a persistent completion banner until all steps are marked complete. Dealers can restart the wizard at any time from their dashboard.


## 18.2 Step 1 — Account Setup Detail
Required Fields
- Business Name — shown on dealer profile and all listings
- Business Address — street, city, state, zip — used for FFL locator distance calculations
- Phone Number — shown on dealer profile
- Business Website URL
- FFL License Number — format validated against ATF FFL number format (XX-XXXXX-XX-XX-XXXXX)
- Primary Contact Name — internal, shown to admin only
- Primary Contact Email — for platform notifications

Optional Fields
- Business Logo — PNG/JPG, shown on dealer profile and listing source badge
- Business Description — rich text, shown on dealer profile page
- Social Media Links — Facebook, Instagram, X/Twitter — shown on dealer profile
- Shipping Policy — free text, shown on dealer profile
- Return Policy — free text, shown on dealer profile


## 18.3 Step 2 — FFL Verification Detail
- Dealer uploads FFL license document — PDF or image accepted
- Admin receives notification of pending verification — appears in ACP verification queue
- Admin reviews: verifies FFL number matches document, checks expiry date, confirms business name matches
- Admin approves or rejects with reason — dealer notified by email in both cases
- Target verification time: within 24 hours — shown to dealer as expectation
- While pending: dealer can complete wizard steps 3-6 but listings show 'Pending Verification' status and are not publicly visible
- On approval: all pending listings go live automatically — dealer notified
- FFL expiry tracking: platform alerts dealer 60 days before FFL expiry and requires updated document upload


## 18.4 Step 4 — Feed Submission Detail
Feed Endpoint Option
- URL field: paste the feed endpoint URL
- Format selector: XML / JSON / CSV — or Auto-detect
- Authentication: None / Basic Auth (username + password) / API Key (header name + value)
- Test connection button: fires a sample request and shows the first 200 chars of response — confirms URL is reachable before proceeding

Manual CSV Upload Option
- 'I don't have a feed URL' link expands alternative flow
- Download CSV template button — pre-formatted template with all required columns
- Upload CSV — imported immediately, treated as a manual feed snapshot
- Note shown: 'Manual CSV imports do not sync automatically. You will need to re-upload when your inventory changes. Consider upgrading to a live feed for automatic sync.'
- Dealer can switch to a live feed URL at any time after completing onboarding


## 18.5 Step 5 — Field Mapping Review Detail
Auto-detection maps common column names automatically. The dealer reviews and corrects the mapping before the full import runs.


- Live preview panel: shows first 5 rows of feed data mapped to platform fields — dealer can see exactly what will import before committing
- Save mapping button: stores the field mapping so it applies to all future syncs from this feed — dealer never has to remap
- Reset mapping: clears all mappings and re-runs auto-detection


## 18.6 Step 6 — Test Import Detail
Imports the first 25 records from the feed and shows the dealer a preview of their listings as they will appear on the platform.

Preview Display
- Product card for each imported record: product image (from master catalog), product name, dealer price, shipping cost, estimated heat badge, in-stock status
- Match status per record: Matched to catalog (green checkmark) / UPC not found in catalog (orange warning) / Import error (red X with error message)
- Summary bar: X of 25 records matched, X unmatched, X errors

Error Handling
- Missing required field (UPC or price): shown as error with specific field name — 'Row 7: UPC field is empty'
- Invalid price format: 'Row 12: Price value '$29.99' — remove currency symbol, use numeric value only'
- Unmatched UPC: 'Row 4: UPC 123456789012 not found in GunRack catalog — this listing will appear in your unmatched queue for review'
- Duplicate UPC: 'Rows 8 and 14: Duplicate UPC 987654321098 — only the first record will be imported'
- Error report downloadable as CSV — dealer can fix feed and re-run test import


## 18.7 Dealer Dashboard — Ongoing Help System

Contextual Tooltips
- Every setting field, metric, and action button in the dealer dashboard has a tooltip — triggered by hovering a ? icon
- Tooltip content: what the field does, what a good value looks like, and a link to the relevant help article
- Examples: Sync Frequency tooltip: 'How often your feed is pulled for new pricing and inventory. Pro tier: 15 minutes. Faster sync = more up-to-date prices = higher ranking in search results.'
- Unmatched UPCs tooltip: 'Products in your feed that did not match any item in the GunRack catalog. These listings are not visible to buyers. Review and either manually match them to catalog items or contact support to add missing products.'

Searchable Help Center — /dealers/help
- Searchable knowledge base with articles organized by topic
- Topics: Getting Started, Feed Setup, Field Mapping, Subscription and Billing, Understanding Your Dashboard, Price Comparison and Ranking, Dispute Resolution, FFL Verification, Shipping Settings, Contacting Support
- Each article: written explanation + annotated screenshot + short video walkthrough where relevant
- Search returns matching articles — powered by OpenSearch
- 'Was this helpful?' Yes/No feedback on each article — admin sees which articles have low helpfulness scores
- 'Contact Support' button on every help article — opens support ticket pre-filled with the article the dealer was reading

Dashboard Completion Tracker
- Persistent banner on dealer dashboard until wizard is 100% complete: 'Your setup is X% complete. Complete these steps to maximize your listing visibility.'
- Checklist items: Profile complete / FFL verified / Feed connected / First import successful / Shipping settings configured / Logo uploaded
- Each incomplete item links directly to the relevant wizard step or settings panel
- Banner dismisses permanently once all items are complete — replaced with a 'Your store is fully set up' confirmation shown for 7 days then removed

Feed Health Notifications
- Dealer receives email if feed fails 3 consecutive syncs: 'Your feed has not synced in X hours. Check your feed URL and credentials in your dashboard.'
- Dealer receives email if more than 50 unmatched UPCs accumulate: 'You have X products that are not visible to buyers. Review your unmatched queue.'
- Dealer receives email 60 days before FFL license expiry: 'Your FFL license expires in 60 days. Upload your renewed license to avoid listing suspension.'
- All notification emails include direct deep links into the relevant dashboard section — no hunting required


## 18.8 Dealer Dashboard — Key Sections
After onboarding the dealer dashboard provides full visibility into their platform performance:


## 18.9 Acceptance Criteria — Dealer Onboarding
- Wizard launches automatically on first dealer login after subscription purchase
- Each step shows a 'Skip for now' link — skipping records the step as incomplete in the completion tracker
- Wizard progress persists — dealer can close browser and return to same step
- FFL verification step cannot be skipped — 'Skip' link not shown on Step 2
- Subscription step cannot be skipped — 'Skip' link not shown on Step 3
- Listings with pending FFL verification show 'Pending Verification' status and are not publicly visible
- On FFL approval: all pending listings go live, dealer receives email notification
- Test connection button on Step 4 returns success/failure within 5 seconds
- CSV template download produces a correctly formatted file with all required columns and example data
- Field mapping auto-detect correctly identifies UPC, price, and stock columns for standard feed formats
- Mapping saved after Step 5 — next feed sync uses saved mapping without prompting dealer again
- Test import shows correct match/unmatch/error counts — verified against known test feed
- Error messages on test import are specific and actionable — not generic 'import failed' messages
- Every dashboard setting field has a tooltip — verified by clicking all ? icons
- Help center search returns relevant articles — tested with 10 common dealer queries
- 'Was this helpful?' feedback submits and admin sees low-score articles in ACP
- Dashboard completion tracker shows correct percentage and links to correct steps
- Banner dismisses permanently after all items complete — confirmed not returning on next login
- Feed failure email sends after 3 consecutive failed syncs — confirmed with test feed that returns errors
- FFL expiry email sends 60 days before expiry date — confirmed with test dealer with near-expiry date


# 19. Phase 2 Roadmap
Phase 2 features are intentionally excluded from the launch build to keep scope manageable and get a working platform live. Each item below is defined well enough that a developer can pick it up after launch without re-speccing from scratch. Phase 2 begins after Phase 1 has been live for at least 60 days and the core community and dealer base is established.

⚠  None of the items in this section are required for launch. Do not begin Phase 2 development until Phase 1 is fully live, stable, and generating revenue.


## 19.1 Mobile App — iOS and Android
A native mobile app for iOS and Android that surfaces the core price comparison, deal alerts, and loadout builder. Not a web view wrapper — a proper native app using React Native for code sharing across platforms.


- API foundation: the browser extension API (/api/v1/extension/lookup) is the starting point. Phase 2 API design should expand this into a proper versioned mobile API at /api/v2/ covering catalog, deals, loadouts, and member data.
- Authentication: IPS OAuth2 or API token system for authenticated app users — member context required for wishlists, alerts, and loadout saving.
- App store accounts: Apple Developer Program ($99/yr) and Google Play Developer ($25 one-time) must be set up before development begins.


## 19.2 Public API as a Product
A paid API tier for developers, data aggregators, and industry partners who want programmatic access to GunRack pricing data. Revenue model: monthly subscription tiers by request volume.


- Webhook events at Commercial tier: price.drop, price.alltime_low, stock.available, stock.unavailable, heat.fire — POST to customer-configured URL on trigger
- API key management self-service portal at /developers — generate/revoke keys, view usage, upgrade tier
- Documentation at docs.gunrack.deals — OpenAPI spec auto-generated from route definitions
- Rate limiting: per-API-key limits enforced server-side. Keys issued per account. Overage returns 429 with Retry-After header.


## 19.3 Dealer Inventory Widget
An embeddable JavaScript widget dealers can place on their own website that shows live pricing from their GunRack listings. Drives traffic back to the dealer's own site while keeping GunRack pricing data visible to the dealer's customers.

- Embed code: one script tag + one div with data attributes specifying dealer ID, product category filter, and display style
- Widget shows: product thumbnail, title, price, in-stock status, and 'See details' link back to the dealer's site
- Customizable: dealer can configure widget colors to match their site theme from their GunRack dealer dashboard
- Available to Pro and Enterprise tier dealers only
- Hosted on a CDN — widget script loaded from cdn.gunrack.deals to keep load time minimal


## 19.4 FFL Transfer Cost Tracker
A crowd-sourced database of FFL dealer transfer fees. Buyers enter the FFL, the fee they were charged, and the date — the platform aggregates this into a searchable directory of transfer costs near a given ZIP code. Solves the common problem of calling five local FFLs before ordering online.

- Data model: gd_ffl_transfer_costs (ffl_license_no, fee_amount, fee_date, submitted_by_member_id, verified)
- FFL locator (Plugin 3 Section 4.8) is extended to show average reported transfer fee alongside address and phone number
- Submission form on FFL locator page: 'Did you transfer here? Tell us what they charged'
- Moderation: fees that are extreme outliers (>3 standard deviations from local average) flagged for admin review before publishing
- Feeds into the ammo bulk buy optimizer — total cost calculation can optionally include estimated transfer fee


## 19.5 Sold / Unavailable Confirmation
A community mechanism to confirm that deal posts or catalog listings are sold out or unavailable, faster than waiting for the dealer feed to update.

- 'Mark as sold out' button on community deal posts — if 3+ members mark a deal as sold out, the deal is automatically moved to expired status and labeled 'Community confirmed sold'
- 'Price changed' button on deal posts — if members report the price is wrong, the deal flags for admin review
- For catalog listings: 'This item shows as out of stock on the dealer site' report button — logged but not automated. Admin uses the report queue to accelerate manual feed refresh for high-traffic products.


## 19.6 Social Proof Homepage Counter
A live counter on the homepage showing platform activity in real time to build credibility with new visitors.

- Stats displayed: total members, total deals found today, total money saved (sum of (avg_price - lowest_price) across all click events), total reviews, active dealers
- Updates every 60 seconds via lightweight AJAX call — no page refresh
- Stats Bar homepage widget (Section 17.4.2) is the Phase 1 placeholder — Phase 2 makes it live-updating and adds the money saved counter
- Money saved calculation: on each /go/{listing-id} click, record (competitor_avg_price - click_price) into gd_savings_log. Counter shows rolling 30-day sum.


## 19.7 Dealer Response Time Leaderboard Expansion
Expand the dealer leaderboard (Section 16.2 Dealers tab) with response time data from the dispute system and direct Q&A.

- Add avg_response_hours metric to dealer leaderboard — how fast dealers respond to reviews, disputes, and questions on their listings
- 'Ships Fast' badge refine: currently based on listing freshness. Phase 2 adds actual shipping speed data by correlating order date (from member report) with delivery confirmation.
- Q&A on product pages (Appendix A Section A.8): if implemented, dealer response time to product questions feeds into this metric


## 19.8 Development Timeline — Phase 2


# Appendix A — Forum Engagement Strategy
This appendix is a reference guide, not a buildable specification. It documents proven tactics for driving forum activity and should be consulted when planning content strategy, admin workflows, and Phase 2 feature prioritization. Forums live or die in the first 90 days — these ideas exist to prevent the cold-start problem where a new forum feels empty and users do not return.

⚠  None of the items in this appendix require developer work unless explicitly moved into a numbered plugin section. They are strategic reminders, not spec requirements.


## A.1 Automated Discussion Threads — High Priority
Scheduled forum posts created automatically by the platform from existing data. Zero admin effort after initial setup. Keep the forum feeling alive even before the community reaches critical mass.


## A.2 Ask the Community — Product Q&A
An 'Ask a Question' button on product pages creates a forum thread tagged to that product. Replies appear both in the forum and back on the product page in a Q&A tab. This mirrors what Amazon does with product questions — generates enormous long-tail SEO content organically and gives buyers a place to get real answers before purchasing. Each question thread is indexed by OpenSearch and Google. Implementation requires a link between product UPC and forum thread — a Phase 2 candidate given the SEO value.


## A.3 Weekly Build Challenge Details
Admin posts a challenge prompt in the Builds & Loadouts forum every Monday. Users respond by sharing a loadout as their entry. Community upvotes all week. Sunday: highest-voted entry wins.

- Winner receives: a Top Build badge on their profile, featured placement on /loadouts hub for one week, one free month of VIP membership
- Challenge prompts to rotate through: budget builds (under $X), specific use case builds, specific caliber builds, most unique build, best concealed carry setup, best competition build
- Admin manages challenge prompts from ACP — queue up 4 weeks in advance
- This mechanic drives loadout creation, forum participation, and repeat weekly visits simultaneously — one of the highest-ROI engagement features available


## A.4 FFL Dealer Reviews
Users post reviews of local FFL dealers they have used for firearm transfers — the brick-and-mortar dealers, not the platform dealer vendors. This fills a genuine gap: there is no good central place to review FFL transfer dealers and buyers desperately want this information before choosing where to do their transfer.

- Subforum: FFL Reviews — organized by state
- Review format: dealer name, city/state, transfer fee, wait time, staff friendliness, overall rating 1–5
- Hyper-local content that no national platform has — strong long-tail SEO for '[city] FFL dealer review' queries
- Ties naturally into the FFL locator feature — eventually reviews can surface on FFL locator results
- Phase 2 candidate: link reviews back to gd_ffl_dealers table for structured data


## A.5 Shooting Range Reviews
Same concept as FFL reviews but for shooting ranges. Gun owners visit ranges regularly and have no good central place to review them. Hyper-local, evergreen content.

- Subforum: Range Reviews — organized by state
- Review fields: range name, city/state, indoor/outdoor, lanes, rental guns available, overall rating
- Generates highly local content that ranks for '[city] shooting range review' queries — very low competition
- Strong Phase 2 candidate once community has enough members to seed initial reviews


## A.6 Compliance Discussion Subforum
A dedicated subforum for discussing firearms legislation organized by state — directly powered by the Firearms Bill Tracker integration already in the spec.

- Subforum: Laws & Compliance — with state sub-forums
- Bill Tracker auto-posts to the relevant state subforum when a bill is enacted or introduced
- Each auto-post seeded with the bill title, plain-English impact summary, and an open question: 'How does this affect you?'
- Drives serious, substantive discussion from the most engaged gun owners — the exact demographic you want
- Unique to this platform — no deal aggregator has this and it creates a reason to visit beyond deal hunting


## A.7 Trophy Case / Collection Log
A personal section on member profiles where they log firearms they own, have shot, or want to try. When someone adds a firearm to their owned list it creates a social activity event.

- 'I Own This' button on any product page — adds to member's collection on their profile
- 'I've Shot This' variant — for range rentals or friends' guns
- Collection tab on member profiles: public gallery of owned firearms with the member's brief notes
- Activity feed: '[username] added [product] to their collection' — visible in forum activity streams
- Ties to product pages: product pages show 'X members own this' count — social proof for buyers
- Phase 2 candidate — requires profile extension and activity feed infrastructure


## A.8 Price Prediction / Market Bet
Users predict whether a product's price will go up or down over the next 30 days. Platform tracks actual outcomes and scores member prediction accuracy over time.

- 'Bet on this price' button on any product page — user selects Up or Down
- After 30 days: outcome revealed, correct predictors score points
- 'Market Oracle' badge for members with top prediction accuracy (min 20 predictions)
- Leaderboard tab in IPS native leaderboard — top predictors ranked by accuracy score
- See Section 16 for consolidated leaderboard architecture
- Creates investment in price watching and drives return visits to check outcomes
- Phase 2 candidate — fun mechanic best added after core community is established


## A.9 Caliber Comparison Threads
Auto-generated or admin-seeded discussion threads per caliber, enriched with platform pricing and availability data that makes them uniquely valuable compared to generic forum discussions elsewhere.

- Examples: '9mm vs .45 ACP for home defense', 'Best budget .308 ammo right now', '5.56 vs .300 Blackout for home defense'
- Seed each thread with current CPR data from the platform, availability trends, and price history
- Platform data makes these threads data-rich in a way Reddit and traditional gun forums cannot match
- Strong SEO target: '[caliber] price comparison', '[caliber] best deal' queries have significant search volume
- Phase 1 candidate if admin is willing to seed 10–20 threads manually at launch


## A.10 Forum-to-Deal Pipeline
When a community member spots a deal and posts it informally in the forum before submitting it as a formal Deal Post, let them convert the forum thread into an official deal with one click from the post.

- 'Submit as Deal' button on forum posts in the Deals & Steals subforum
- Pre-fills the Deal Post submission form with the post title, any URL mentioned, and the price if mentioned in the post
- Rewards informal deal discussion by funneling it into structured platform data
- Submitter gets reputation points when the deal is approved — same as direct deal submission
- Phase 1 candidate — low implementation complexity since both systems already exist


## A.11 Launch Week Content Strategy
The first 7 days of forum activity set the tone for months. Recommended actions before public launch:

- Seed at least 20 forum threads across all categories before opening to the public — an empty forum repels new members
- Admin posts 5 build challenge entries as example loadouts in Builds & Loadouts
- Admin posts 10 FFL dealer reviews from real transfer experiences
- Post 5 caliber comparison threads seeded with platform CPR data
- Configure automated Deal of the Day and Weekly Price Watch threads to fire from day 1
- Invite 10–20 founding dealers to post introduction threads in a Vendor Introductions subforum — free engagement from people with an incentive to participate
- Post a Welcome thread explaining how the platform works with links to key features — pin it
- Set up 3 Weekly Build Challenge prompts in advance so they fire automatically for the first 3 weeks


END OF SPECIFICATION  —  Version 2.9.16.1  —  April 2026
GunRack  —  Confidential  —  12 Plugins + VIP Membership + Forum Engagement Appendix

# Appendix B — Server Setup Walkthrough
This appendix is a step-by-step guide for setting up the GunRack server environment on the Standard NVMe Server (4 Core vCPU, 10GB RAM, 140GB NVMe, 2x IPv4) using DirectAdmin as the control panel. Follow these steps in order before any application development begins. The guide covers DirectAdmin configuration, subdomain setup, OpenSearch installation, Nginx reverse proxy configuration, IPS Elasticsearch integration, firewall rules, and health verification.

⚠  Complete all steps in this appendix before beginning IPS installation or plugin development. Getting the infrastructure wrong after the application is built is significantly more painful than getting it right first.


## B.1 Server Overview and IP Allocation
Your server has two IPv4 addresses. Allocate them as follows before touching anything else:


Separating OpenSearch onto its own IP means search traffic is network-isolated from web traffic. It also means you can move OpenSearch to a dedicated VPS later by updating only the DNS A record for search.gunrack.deals — your application config never changes.


## B.2 DNS Setup — Before Anything Else
Set up all DNS records at your domain registrar before configuring the server. DNS propagation takes up to 48 hours and you want it done before you need it.

Required DNS Records

# At your domain registrar / DNS provider:
# Replace XX.XX.XX.XX with your actual IPv4 #1
# Replace YY.YY.YY.YY with your actual IPv4 #2

Type    Name                    Value           TTL
A       gunrack.deals           XX.XX.XX.XX     300
A       www.gunrack.deals       XX.XX.XX.XX     300
A       search.gunrack.deals    YY.YY.YY.YY     300

# Also set up email records for Amazon SES later:
# (SES will provide specific DKIM and SPF values during setup)
TXT     gunrack.deals           v=spf1 include:amazonses.com ~all


## B.3 DirectAdmin Initial Configuration

Step 1 — Log into DirectAdmin
- Open your browser and go to https://YOUR-SERVER-IP:2222
- Log in with the admin credentials provided by your hosting provider
- You should see the DirectAdmin admin panel — if you see a security warning about the SSL certificate, accept it (this is normal for the control panel URL)

Step 2 — Create the Main Domain Account
- In DirectAdmin Admin Panel → Account Manager → Create User Account
- Username: gunrack (or your preferred admin username)
- Email: your email address
- Domain: gunrack.deals
- Package: assign maximum resources (this is your own server, not a shared host)
- IP Address: select IPv4 #1 (your primary IP)
- Click Create — DirectAdmin creates the account and sets up the document root at /home/gunrack/domains/gunrack.deals/public_html/

Step 3 — Create the Search Subdomain
- Log into the user account you just created (or stay in admin and navigate to that account)
- Go to Domain Setup → Subdomains
- Click Create Subdomain
- Subdomain name: search
- Domain: gunrack.deals
- IP Address: select IPv4 #2 (your secondary IP)
- Click Create

DirectAdmin will create a document root for search.gunrack.deals but we will not use it for files — we will configure Nginx to proxy requests to OpenSearch instead. The document root just needs to exist.

Step 4 — Install SSL Certificates
- In DirectAdmin → SSL Certificates → Free & automatic certificate from Let's Encrypt
- Check both gunrack.deals and www.gunrack.deals — click Save
- Wait 2-3 minutes for certificate issuance — DirectAdmin handles this automatically
- Repeat for search.gunrack.deals — same process, select the subdomain
- Verify: visit https://gunrack.deals in a browser — should show a secure padlock


## B.4 Install IPS Community Suite

Step 1 — Set Up MySQL Database
- In DirectAdmin → MySQL Management → Create Database
- Database name: gunrack_ips
- Database user: gunrack_user
- Password: generate a strong password — save it somewhere secure
- Click Create

Step 2 — Configure PHP
- In DirectAdmin → PHP Configuration (or Custom PHP.ini)
- PHP version: 8.1 or 8.2 (IPS requires PHP 8.x — check IPS documentation for current minimum)
- Set the following PHP values:

memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 120
max_input_vars = 10000

- Save and restart PHP-FPM: in DirectAdmin → Service Monitor → PHP-FPM → Restart

Step 3 — Upload and Install IPS
- Download IPS Community Suite from your IPS client area (invisioncommunity.com)
- Upload the IPS zip file to /home/gunrack/domains/gunrack.deals/public_html/ via DirectAdmin File Manager or SFTP
- Extract the zip file — the public_html folder should now contain the IPS files
- Visit https://gunrack.deals/install/ in your browser
- Follow the IPS installation wizard — use the database credentials created above
- Complete IPS installation — note your admin username and password


## B.5 Install OpenSearch
OpenSearch is installed directly on the server via SSH — not through DirectAdmin. You will need SSH access to your server. Most VPS providers give you root SSH access.

Step 1 — Connect via SSH

# On your local machine (Mac/Linux terminal or Windows PuTTY):
ssh root@YY.YY.YY.YY
# Enter your root password when prompted
# You should see a command prompt like: root@hostname:~#


Step 2 — Install Java (OpenSearch Dependency)

# Update package list first
apt update && apt upgrade -y

# Install Java 17 (required by OpenSearch)
apt install -y openjdk-17-jdk

# Verify Java installed correctly
java -version
# Should output something like: openjdk version 17.x.x


Step 3 — Install OpenSearch

# Import OpenSearch GPG key
curl -o- https://artifacts.opensearch.org/publickeys/opensearch.pgp | gpg --dearmor --batch --yes -o /usr/share/keyrings/opensearch-keyring

# Add OpenSearch repository
echo 'deb [signed-by=/usr/share/keyrings/opensearch-keyring] https://artifacts.opensearch.org/releases/bundle/opensearch/2.x/apt stable main' | tee /etc/apt/sources.list.d/opensearch-2.x.list

# Install OpenSearch
apt update
OPENSEARCH_INITIAL_ADMIN_PASSWORD=YourStrongPassword123! apt install opensearch -y
# Replace YourStrongPassword123! with a real strong password — save it


Step 4 — Configure OpenSearch JVM Heap
OpenSearch needs 4GB of your 10GB RAM. This is set in the JVM config file:

# Edit the JVM options file
nano /etc/opensearch/jvm.options

# Find these two lines and change them:
-Xms1g    →    -Xms4g
-Xmx1g    →    -Xmx4g

# Save and exit: Ctrl+X, then Y, then Enter


Step 5 — Configure OpenSearch Settings

# Edit the main OpenSearch config
nano /etc/opensearch/opensearch.yml

# Add or update these settings:
cluster.name: gunrack-search
node.name: gunrack-node-1
network.host: 127.0.0.1
http.port: 9200
discovery.type: single-node

# Disable security plugin for internal use
# (Nginx will handle SSL and auth externally)
plugins.security.disabled: true

# Save and exit: Ctrl+X, then Y, then Enter


Setting network.host to 127.0.0.1 means OpenSearch only listens on localhost — it is NOT accessible from the internet directly. All external access goes through the Nginx reverse proxy which adds SSL. This is the correct security posture.

Step 6 — Start OpenSearch and Enable on Boot

# Reload systemd and start OpenSearch
systemctl daemon-reload
systemctl enable opensearch
systemctl start opensearch

# Check it started successfully
systemctl status opensearch
# Should show: Active: active (running)

# Verify OpenSearch is responding on localhost
curl -X GET http://localhost:9200
# Should return a JSON response with cluster info


## B.6 Configure Nginx Reverse Proxy for search.gunrack.deals
DirectAdmin uses Nginx (or Apache + Nginx) for web serving. We need to add a custom Nginx configuration that proxies search.gunrack.deals to OpenSearch on localhost:9200.

Step 1 — Find the Nginx Config Location

# DirectAdmin stores custom Nginx configs here:
ls /etc/nginx/conf.d/
# or
ls /usr/local/directadmin/data/users/gunrack/nginx.conf

# The exact path varies by DirectAdmin version.
# Check DirectAdmin → Advanced → Custom Nginx Configuration
# to see where custom configs are stored on your setup.


Step 2 — Create the Reverse Proxy Config

# Create a new config file for the search subdomain
nano /etc/nginx/conf.d/search.gunrack.deals.conf

# Paste this configuration:
server {
listen 443 ssl;
server_name search.gunrack.deals;

# SSL certificate paths — Let's Encrypt via DirectAdmin
ssl_certificate /etc/letsencrypt/live/search.gunrack.deals/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/search.gunrack.deals/privkey.pem;

# Only allow connections from this server itself
# Prevents external access to OpenSearch
allow 127.0.0.1;
allow XX.XX.XX.XX;  # Replace with your IPv4 #1
deny all;

location / {
proxy_pass http://127.0.0.1:9200;
proxy_set_header Host $host;
proxy_set_header X-Real-IP $remote_addr;
proxy_read_timeout 90;
}
}

server {
listen 80;
server_name search.gunrack.deals;
return 301 https://$host$request_uri;
}


Step 3 — Test and Reload Nginx

# Test the Nginx config for syntax errors
nginx -t
# Should output: configuration file /etc/nginx/nginx.conf syntax is ok

# Reload Nginx to apply the new config
systemctl reload nginx

# Verify the proxy is working
curl -X GET https://search.gunrack.deals
# Should return OpenSearch cluster JSON — same as the localhost test


## B.7 Connect IPS to OpenSearch

Step 1 — Install IPS Elasticsearch Plugin
- Log into IPS Admin Control Panel → System → Site Features → Search
- IPS supports Elasticsearch-compatible search engines — OpenSearch is fully compatible
- If IPS shows an Elasticsearch integration option: set the server URL to https://search.gunrack.deals
- Port: 443 (HTTPS through Nginx — not 9200 directly)
- No username/password required — Nginx IP restriction handles security
- Click Test Connection — should show green success
- Click Save and enable Elasticsearch as the search provider

Step 2 — Run Initial Index
- In IPS ACP → System → Search → Rebuild Search Index
- This indexes all existing IPS content into OpenSearch
- On a fresh install this is fast — on a large existing community it can take hours
- Check progress in the ACP task queue

Step 3 — Verify Search is Working
- Visit https://gunrack.deals and use the search bar
- Search for any term that exists in your content
- Results should appear — if they do, OpenSearch integration is working


## B.8 Configure MySQL for Performance
The default MySQL configuration is conservative. Update it for a dedicated server:


# Edit MySQL config
nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Add or update under [mysqld]:
innodb_buffer_pool_size = 2G
# Allocates 2GB RAM to MySQL — matches our allocation plan

innodb_log_file_size = 256M
query_cache_type = 0
query_cache_size = 0
max_connections = 150
innodb_flush_log_at_trx_commit = 2
# Value of 2 gives good performance with minimal data loss risk

# Save, exit, and restart MySQL
systemctl restart mysql


## B.9 Configure Redis for IPS Caching
Redis dramatically improves IPS performance by caching sessions, page fragments, and database query results in memory. IPS has native Redis support.

Install Redis

apt install -y redis-server

# Configure Redis to use 512MB max memory
nano /etc/redis/redis.conf

# Find and update:
maxmemory 512mb
maxmemory-policy allkeys-lru

# Enable and start
systemctl enable redis-server
systemctl restart redis-server

# Verify Redis is running
redis-cli ping
# Should return: PONG


Connect IPS to Redis
- In IPS ACP → System → Advanced Configuration → Redis
- Server: 127.0.0.1
- Port: 6379
- No password required (Redis is localhost only)
- Click Save — IPS will now use Redis for caching


## B.10 Firewall Rules
Lock down the server so only necessary ports are accessible from the internet:


# Install UFW firewall if not already installed
apt install -y ufw

# Set defaults
ufw default deny incoming
ufw default allow outgoing

# Allow SSH (do this first or you will lock yourself out)
ufw allow 22/tcp

# Allow web traffic
ufw allow 80/tcp
ufw allow 443/tcp

# Allow DirectAdmin control panel
ufw allow 2222/tcp

# Block direct access to OpenSearch from internet
# (it is already on localhost but this is defense in depth)
ufw deny 9200/tcp
ufw deny 9300/tcp

# Block MySQL from internet
ufw deny 3306/tcp

# Enable the firewall
ufw enable

# Verify rules
ufw status verbose


## B.11 Set Up Automated Backups
Configure daily backups before the platform goes live. DirectAdmin has a built-in backup system:

- In DirectAdmin Admin Panel → Admin Backup/Transfer → Schedule Backups
- Frequency: Daily
- Time: 4:00am (after nightly feed imports complete)
- What to back up: Databases + Home directories
- Backup destination: either a remote FTP/SFTP location or an external storage service — do NOT store backups only on the same VPS
- Retention: keep last 7 daily backups
- Enable email notification on backup completion/failure

Storing backups only on the same server is not a backup — it is a false sense of security. If the VPS fails, both your data and your backups are gone. Use an S3-compatible bucket (Backblaze B2 is cheapest at ~$0.006/GB/month) or a separate VPS for backup storage.


## B.12 Health Check Verification
Run through this checklist after completing all setup steps. Every item must pass before development begins:


## B.13 Maintenance Commands Reference
Quick reference for common server maintenance tasks after launch:


END OF SPECIFICATION  —  Version 2.9.16  —  April 2026
GunRack  —  Confidential  —  12 Plugins + VIP Membership + Appendices A, B, and C


# Appendix C — Security Requirements
This appendix defines mandatory security requirements for all GunRack plugins, the server stack, and infrastructure components. Every item in this appendix is a hard requirement — not a recommendation. Nothing goes live until every item in the Developer Checklist (Section C.8) is verified.


## C.1 Overall Verdict
The platform architecture is sound. The major risks are not in the spec design — they are in how the plugin code is written and in three specific conflict points between components identified in Section C.7. IPS Community Suite had zero published security vulnerabilities in 2025, so the core platform is a solid foundation. Security of this build depends primarily on custom plugin code quality and correct server configuration.


## C.2 IPS Plugin Code — Highest Priority
Every custom plugin is a potential entry point. The following classes of vulnerability must be explicitly prevented in all twelve plugins:


### SQL Injection
Every database query that uses user-supplied input must use IPS's parameterized query interface exclusively. Use \IPS\Db::i()->select() with bound parameters. Never concatenate user input into a query string directly. Highest-risk plugins: Plugin 3 (search filters and price range inputs), Plugin 10 (deal submission fields), Plugin 11 (loadout builder item search). The developer must perform a line-by-line audit of every DB call in all twelve plugins before launch.


### XSS — Cross-Site Scripting
All user-generated content that is rendered back to other users must be sanitized on output. Use \IPS\Text\Parser::parseStatic() for rich text fields or htmlspecialchars() for plain text fields. The following specific fields are high-risk and must be individually verified: community deal post title and body (Plugin 10), product review body (Plugin 4), loadout name and description (Plugin 11), swap suggestion reason text (Plugin 12), and dealer profile description (Plugin 2).


### CSRF — Cross-Site Request Forgery
Every state-changing action that originates from a front-end user interaction must validate IPS's CSRF token via \IPS\Session::i()->csrfCheck() before processing. State-changing actions include: deal voting, loadout forking, swap suggestion accept/reject, wishlist add/remove, watchlist add/remove, price alert set/delete, deal post submission, review submission, rebate submission, and loadout share to forum. This applies to all front-end AJAX endpoints across all twelve plugins.


### Privilege Escalation
The dealer onboarding wizard (Plugin 2) and VIP membership flow (Plugin 7) both use IPS Commerce group promotions. Custom code that touches member groups directly must validate that POST parameters cannot be manipulated to assign a higher member group than the user paid for. Never trust client-supplied group IDs. Always derive the correct target group server-side from the confirmed payment/subscription record.


## C.3 Feed Ingestion — Second Highest Priority
The feed ingestion system processes external XML, JSON, and CSV files from six distributors and dealer-submitted URLs. Treat all feed data as untrusted external input, identical to user-submitted form data.


### XML Entity Injection — XXE
If PHP's SimpleXML or DOMDocument is used to parse distributor XML feeds, external entity loading must be disabled before every parse. Add the following line before any XML parsing call:

libxml_disable_entity_loader(true);

Without this, a compromised distributor feed server could serve a malicious XML payload that reads arbitrary files from the GunRack server. This is a one-line fix that must be present in every XML parse call in Plugins 1 and 2.


### SSRF — Server-Side Request Forgery
The dealer feed submission flow allows dealers to enter a remote feed URL that the server fetches. Before making any outbound HTTP request to a dealer-supplied URL, the system must validate the URL against a denylist. Blocked targets: all private IP ranges (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16), localhost and 127.0.0.1, the OpenSearch internal address (search.gunrack.deals and port 9200), link-local range 169.254.0.0/16 (AWS/cloud metadata endpoints), and IPv6 loopback ::1. Resolve the URL to its final IP before making the request and re-validate the resolved IP — do not validate only the hostname, as DNS rebinding can bypass hostname-only checks.


### File Upload Validation — FFL Documents
Dealers upload FFL license documents during the onboarding wizard. The following validation rules are mandatory: validate MIME type server-side using PHP's finfo_file() — do not trust the Content-Type header or file extension alone; enforce a file size maximum of 10MB; store uploaded files outside the web root or in an S3-equivalent storage bucket — never in a publicly accessible directory; never serve uploaded files directly through PHP execution; generate a random filename on upload — do not use the original filename; accept only PDF, JPG, and PNG MIME types — reject all others regardless of extension.


### Feed Data Sanitization
Every field ingested from a distributor feed — product titles, descriptions, manufacturer names, category names, image URLs — must be sanitized before being written to the database and before being rendered on the front end. Distributor feeds are third-party data and must be treated as untrusted input identically to user-submitted form data.


## C.4 OpenSearch


### IP Allowlist — Specific IP Only
The Nginx reverse proxy on search.gunrack.deals must allowlist only the single specific IPv4 address of the gunrack.deals server. Not a /24 block, not a range — one IP. Any change to the server IP (e.g. after a migration) requires an immediate update to the Nginx allowlist before OpenSearch is accessible.


### OpenSearch Query Injection
Search queries built from user input in Plugin 3 (price comparison search) and Plugin 8 (SEO search) must use the OpenSearch PHP client's parameterized query builder. Never string-interpolate user input into a raw OpenSearch JSON query body. The injection risk for OpenSearch query DSL is equivalent to SQL injection and must be treated with the same discipline.


### Index Mapping — Dynamic Strict
The OpenSearch product index must include dynamic: strict in its mapping configuration. This prevents unexpected fields in a document from dynamically adding new mappings to the index, which can cause index bloat, query errors, and potential data leakage between documents. Add to the index mapping root:

{ "mappings": { "dynamic": "strict", "properties": { ... } } }


## C.5 Amazon SES and Email


### Email Authentication — SPF, DKIM, DMARC
All three email authentication DNS records are required on gunrack.deals before any transactional email is sent. SPF: publish a TXT record authorizing Amazon SES sending IPs. DKIM: configure DKIM signing in SES and publish the CNAME records SES provides. DMARC: publish a DMARC TXT record at _dmarc.gunrack.deals with p=quarantine at launch. Once delivery rates are stable and confirmed over 30 days, move DMARC policy to p=reject. Without DMARC, spoofed emails claiming to be from gunrack.deals are trivially possible and damage domain reputation.


### SES Credential Storage
The Amazon SES API key and secret must be stored in a server-side environment variable or a config file outside the web root. Never hardcode credentials in plugin source code. Never commit credentials to any Git repository — automated scanners find exposed AWS keys within minutes of a push to a public repo and immediately begin incurring charges. If credentials are ever accidentally committed: rotate them immediately in the AWS console before doing anything else.


### Email Enumeration Prevention
The password reset flow and the dealer registration 'email already exists' check must return identical response messages regardless of whether the email address has an account. Returning different messages for known vs unknown email addresses allows enumeration of all registered emails.


## C.6 Server and Infrastructure


### Redis — Bind and Authentication (Critical)
The current spec installs Redis and connects IPS to it without specifying authentication. This is the single highest-risk infrastructure gap. Two changes are required in redis.conf before Redis is started:

bind 127.0.0.1
requirepass YOUR_STRONG_REDIS_PASSWORD_HERE

Redis bound to all interfaces with no password — even behind UFW — is a single firewall misconfiguration away from full server compromise. Defense in depth requires Redis to be locked at both the network level (UFW) and the application level (bind + requirepass). Use a randomly generated password of at least 32 characters.


### DirectAdmin — IP Restriction
The DirectAdmin admin panel must be restricted to specific IP addresses only. Configure IP access restriction in DirectAdmin's security settings. The panel should not be accessible from the public internet. If remote admin access is needed from variable IPs, use a VPN rather than opening DirectAdmin publicly.


### MySQL — Minimum Necessary Permissions
The gunrack_ips MySQL user must be granted only the minimum permissions required for IPS to operate. Grant exactly: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER on the gunrack_ips database only. Do not grant GRANT OPTION, FILE, SUPER, PROCESS, or any global privileges. An over-privileged MySQL user turns a SQL injection into a full database server compromise.


### Backup Encryption
The daily backup job uploads to Backblaze B2. The backup must be encrypted before upload. An unencrypted database dump contains all member PII, hashed passwords, dealer FFL license data, and transaction records. If the B2 bucket is ever misconfigured as public, that data is fully exposed. Encrypt each backup archive with GPG using a strong key before upload. Store the GPG private key in a separate secure location — not on the same server as the backups.

# Example backup encryption before B2 upload
mysqldump -u gunrack_ips -p gunrack_ips | gzip | gpg --encrypt --recipient YOUR_KEY_ID > backup_$(date +%Y%m%d).sql.gz.gpg
b2 upload-file your-bucket backup_$(date +%Y%m%d).sql.gz.gpg


### Stripe Webhook Signature Validation
IPS Commerce handles Stripe integration natively which keeps raw card data off the server and out of PCI scope. Any custom code that responds to Stripe webhook events (e.g. VIP subscription confirmation, dealer subscription upgrade) must validate the Stripe webhook signature using the signing secret from the Stripe dashboard. Never process a Stripe webhook payload without signature validation — a fake 'payment succeeded' event can grant subscription access without payment.


### Browser Extension — Rate Limiting on Lookup API
The read-only API endpoint /api/v1/extension/lookup is public-facing. Without rate limiting it can be used to enumerate the entire product catalog by scraping UPCs sequentially. Implement per-IP rate limiting at the Nginx level: maximum 60 requests per minute per IP. This is generous for legitimate extension use and blocks systematic enumeration. Configure in Nginx using the limit_req_zone and limit_req directives.

# In nginx.conf http block:
limit_req_zone $binary_remote_addr zone=extension_api:10m rate=60r/m;

# In the location block for /api/v1/extension/:
limit_req zone=extension_api burst=10 nodelay;


## C.7 Component Conflict Points
Three specific points where two systems interact in ways that require explicit handling to avoid data corruption or incorrect behavior:


### Conflict 1 — Feed Ingestion Race Condition
Plugin 1 (distributor catalog sync) and Plugin 2 (dealer feed ingestion) can both run simultaneously on overlapping UPCs. If both write to the same catalog record at the same time without coordination, the result is corrupt records or compliance flags written halfway through a catalog update.


### Conflict 2 — IPS Commerce Group Promotion Delay
IPS Commerce processes group promotions (VIP membership, dealer subscription tiers) asynchronously via background tasks. There is a window — potentially several minutes — between a user completing payment and their member group actually being updated. During this window a user who just paid for VIP will still see the upgrade prompt, and their first attempt to use a VIP-only feature will fail silently.


### Conflict 3 — OpenSearch and MySQL Out of Sync
If a feed import writes records to MySQL and the OpenSearch indexing job fails partway through (memory pressure on the 10GB server, PHP timeout, network hiccup), some products will be searchable and some will not, with no visibility into which ones. This split state is silent — no error is surfaced to admin or users.


## C.8 Developer Security Checklist
Nothing goes live until every item below is checked and verified. This checklist is for the developer to complete and for Derrick to sign off on before launch.


Items 1 through 7 are the highest priority and must be verified first. Items 1 and 2 are the most critical — an open Redis instance and a missing XXE prevention line are both single-step full-server compromises that have no compensating controls if missed.


## C.9 What Is Already Well Handled
The following security considerations are already correctly addressed in the existing spec and need no changes — listed here so the developer knows these are intentional design decisions:

- /go/{listing-id} click tracking redirect: dealer URLs stay off the page source, preventing scraper bypass of click tracking
- OpenSearch Nginx reverse proxy with IP allowlist: correct architecture — OpenSearch never exposed directly
- SES bounce and complaint webhook handlers: specified in Plugin 9 — prevents SES account suspension
- Per-IP rate limiting on deal submissions: specified in Plugin 10 — prevents spam flooding
- Feed URL click tracking via redirect: all outbound dealer links routed through /go/ — consistent and correct
- IPS Commerce + Stripe iframe-based card collection: raw card data never touches GunRack server — out of PCI scope
- gd_compliance_flags source field: admin_manual entries distinguished from distributor entries at the data layer — prevents silent overwrite
- Browser extension distributed through official Chrome/Firefox stores only: extension signature verification enforced by store distribution

END OF SPECIFICATION  —  Version 2.9.16  —  April 2026
GunRack  —  Confidential  —  12 Plugins + VIP Membership + Appendices A, B, and C

| Confirmed Decision | Value |
| --- | --- |
| Distributors | Sports South  ‖  RSR Group  ‖  Zanders  ‖  Davidson's  ‖  Lipsey's  ‖  Bill Hicks |
| Conflict Resolution — Primary | RSR Group (highest priority — wins all field conflicts) |
| Dealer Feed Formats | XML, JSON, CSV — all three supported |
| Launch Priority | Dealer revenue + consumer experience simultaneously |
| Dealer Pricing | $149/mo Basic  ‖  $399/mo Pro  ‖  $799/mo Enterprise |
| Feed Sync — Basic | Every 30 minutes |
| Feed Sync — Pro | Every 15 minutes |
| Feed Sync — Enterprise | Every 5 minutes |
| Product Scope | Firearms, Ammunition, Accessories, Optics, Parts, Gear — everything |
| State Compliance | Flag restricted items per state (badge displayed, listing not suppressed) |
| FFL Locator | Phase 1 — standalone (no GunRack API tie-in at launch) |
| Launch Differentiators | Price history charts, ATF/NFA indicators, state compliance flags, total cost + shipping — ALL Phase 1 |
| Search Engine | OpenSearch — self-hosted on existing VPS. IPS Elasticsearch integration module pointed at OpenSearch endpoint. No Elastic Cloud cost. |
| Member Groups | Registered Members (default)  ‖  Dealers (secondary — IPS Commerce auto-assigns on subscription activation, removes on cancellation/expiry)  ‖  Administrators |
| Catalog Bootstrap | Six distributor feeds populate full product catalog at launch. 2-3 founding dealer contacts onboarded free for 90 days. No scraping of gun.deals. |
| Plugin 4 — Reviews | Single overall 1–5 star rating. Type-specific written prompts. Any registered member. Verified purchase badge. Image attachments. Dedicated /reviews hub. |
| Plugin 5 — Rebates | Fixed-schedule automated scraper (base) + community submissions (gaps). Admin fully manages scraper URL list in ACP. All scraped rebates auto-approve (admin controls the URL list). Source label shown to users. |
| Plugin 6 — Dealer Dispute | 14-day dealer response window, 14-day customer resolution window. Resolved reviews excluded from star average entirely. Both adjusted and raw averages displayed publicly. |
| Plugin 7 — Power Features | Deal Heat Score, Search Query Alerts, Wishlist (tiered), Ammo Bulk Buy Optimizer, Compliance Notifications, Dealer Leaderboard, MAP Tracker, Browser Extension, Price Drop Alert Enhancements |
| VIP Consumer Membership | $9.99/mo paid consumer tier. 10 wishlists vs 1 free, unlimited search alerts vs 3 free, ad-free, early deal notifications, priority alert delivery, full price history. |
| Plugin 8 — SEO Architecture | Full SEO suite: schema markup, auto-generated sitemaps (all page types), canonical URLs, Open Graph, template-based meta descriptions with admin override, structured data for products/reviews/deals/breadcrumbs. |
| Plugin 9 — Email & Digest | Amazon SES. Weekly digest + daily VIP. Automated base + admin pin. Full preference center. Breaking deal alerts for Fire/All-Time Low. Transactional emails consolidated. |
| Plugin 10 — Community Deal Posts | Manual deal posts by admin + all registered members (moderated before going live). UPC-linked or freeform. Integrated in price comparison table. No auto-expiry — stays live until manually expired or community flags as dead. Upvotes, downvotes, comments. Reputation points. Dedicated /deals feed + homepage widget. |
| Plugin 11 — Loadout Builder | All members. VIP: private visibility + per-item notes + 3-way swap compare. Free: public/unlisted + 1-way swap. Live pricing, compliance flags, wishlist integration, /loadouts hub. |
| Plugin 12 — Loadout Forum Integration | Post loadout to forums. Dedicated Builds category + optional cross-post. Collapsible inline embed with live prices. Fork into own loadout (logged-in members). Structured swap suggestions + accept/reject. Forum engagement feeds loadout popularity score. |
| Section 17 — Admin Settings Panel | Global Settings + per-plugin ACP sections. Full appearance control (colors, fonts, CSS), drag-and-drop homepage layout, widget content configuration, feature toggles per plugin, feed health dashboard, catalog conflict rules, email settings, SEO overrides. |
| Section 18 — Dealer Onboarding | 7-step wizard (mandatory, skippable per step). Account setup → subscription → feed submission → field mapping → test import → listing verification → go live. Contextual tooltips on every field. Searchable /help center. Wizard restartable from dashboard. |
| Section 19 — Phase 2 Roadmap | Mobile app (with barcode scanner), public API product, dealer inventory widget, FFL transfer cost tracker, sold/unavailable confirmation, social proof counter. Full skeleton spec with build estimates and revenue impact for each item. |
| Appendix B — Server Setup | Step-by-step DirectAdmin + OpenSearch setup guide for the Standard NVMe Server (10GB RAM, 140GB NVMe, 2x IPv4). Covers subdomain creation, OpenSearch install, Nginx reverse proxy, IPS Elasticsearch integration, firewall rules, and health checks. |


| Plugin | Name | Purpose | Depends On |
| --- | --- | --- | --- |
| 1 | GD Master Catalog | Six-distributor feed ingestion, conflict resolution, canonical product DB | Nothing — builds first |
| 2 | GD Dealer Manager | Dealer onboarding, XML/JSON/CSV feed ingestion, IPS Commerce billing, dashboards | Plugin 1 |
| 3 | GD Price Comparison | Consumer search, price tables, history charts, compliance flags, alerts, FFL locator | Plugin 1 + 2 |


| Phase | Weeks | Work |
| --- | --- | --- |
| Phase 1 | 1 – 2 | Plugin 1: All six distributor feeds configured, importing, conflict resolution running, OpenSearch indexed |
| Phase 1 | 3 – 4 | Plugin 1: Admin dashboard, manual override system, conflict log, full QA across all six feeds |
| Phase 2 | 5 – 6 | Plugin 2 + Plugin 3 in parallel: Dealer onboarding + Commerce billing  ‖‖  Product detail pages + price table |
| Phase 2 | 7 – 8 | Plugin 2 + Plugin 3 in parallel: Dealer feed ingestion engine + dashboard  ‖‖  Category browse + search + facets |
| Phase 3 | 9 – 10 | Plugin 2 + Plugin 3 in parallel: Dealer analytics + admin panel  ‖‖  Price alerts + watchlist + history charts |
| Phase 3 | 11 – 12 | All plugins: Compliance flags + NFA indicators + FFL locator + total cost calc + QA + launch readiness |


| Empty State | Message to Show |
| --- | --- |
| Product page — no dealer listings | 'No dealers are currently listing this product. Are you an FFL dealer? List your inventory and reach buyers actively searching for this item. Get started free for 90 days.'  — with a link to /dealers/join |
| Category browse — no results after filter | 'No products match your current filters. Try widening your search or clearing some filters.' — with a Clear Filters button |
| Search — no results | 'No products found for [query]. Try different keywords, or browse by category.' — with category links below |
| Watchlist — empty | 'You haven't watched any products yet. Browse products and click Watch to get price drop alerts.' — with a link to the homepage |
| Dealer dashboard — no listings yet | 'Your first feed import is in progress. Check back in a few minutes. If this persists, verify your feed URL is accessible.' — with a manual import trigger button |


| # | Action | Owner | Blocks | Target Date |
| --- | --- | --- | --- | --- |
| 1 | Contact RSR Group — apply for technology/data partner feed access. Explain you are building a price comparison platform that will drive traffic to RSR-stocked dealers. Use Gun Wise LLC FFL credentials and Ryan Fultz's industry relationships as leverage. Request XML product feed + pricing feed. | Derrick / Ryan | Plugin 1 development — without RSR feed the catalog has no data | Week 1 |
| 2 | Contact Sports South — same approach as RSR. Sports South is the #2 priority distributor. Their feed fills gaps left by RSR. | Derrick / Ryan | Plugin 1 — catalog completeness | Week 1 |
| 3 | Contact Zanders, Davidson's, Lipsey's, Bill Hicks — can follow RSR/Sports South by 1–2 weeks. Same pitch. | Derrick / Ryan | Plugin 1 — secondary distributors | Week 2 |
| 4 | Identify 2–3 founding FFL dealers from existing business relationships. Confirm their willingness to participate in the free 90-day trial before asking for feed details. | Derrick | Plugin 2 — dealer feed testing requires a real dealer | Week 1 |
| 5 | Set up Amazon SES account. Request dedicated sending IP. Verify gunrack.deals domain in SES. Set up SPF, DKIM, DMARC DNS records. | Derrick / developer | Plugin 9 — email cannot send without SES configured | Week 1–2 |
| 6 | Register on Chrome Web Store Developer program ($5), Mozilla Developer Account (free), and Microsoft Edge Developer program (free). These require review before the first extension publish. | Derrick | Browser extension distribution — review can take 1–7 days | Week 2 |
| 7 | Set up Backblaze B2 bucket for backups. Configure GPG key for backup encryption. Document key storage location separately from server. | Developer | Server setup — backups should run from day 1 of server config | Before server goes live |
| 8 | Purchase IPS Community Suite license. Download all required IPS plugins (Ads Pro referenced in Gunpliance work). Confirm IPS version compatibility with PHP version on server. | Derrick | Everything — IPS must be installed before any plugin development | Week 1 |
| 9 | Register gunrack.com inquiry (currently ~$35k). Not required for launch but worth initiating a watch/offer. gunrack.deals is the launch domain. | Derrick | Nothing blocks on this — future brand asset | When budget allows |
| 10 | Brief Ryan Fultz on the platform plan. His distributor relationships are the primary leverage for feed access — make sure he is aligned and available to make calls on your behalf if needed. | Derrick | Feed access outreach (items 1–3) | Week 1 |


| Priority | Distributor | Strengths | Role in Conflict Resolution |
| --- | --- | --- | --- |
| 1 — Primary | RSR Group | Broadest general catalog, reliable standardized data | Wins all field conflicts. If RSR has a value, it is used and cannot be overwritten by any lower source. |
| 2 | Sports South | Wide product range, good descriptions and images | Fills any field left empty by RSR. |
| 3 | Davidson's | Strong on handguns, rifles, high-quality imagery | Fills fields still empty after RSR and Sports South. |
| 4 | Lipsey's | Exclusive and limited-edition firearms | Fills fields still empty after top three. |
| 5 | Zanders Sporting Goods | Strong ammo catalog and accessories | Fills fields still empty after top four. |
| 6 | Bill Hicks | Regional strength, hunting and outdoor gear | Last resort — fills only what no other source populated. |


| Field | Special Rule | Rationale |
| --- | --- | --- |
| image_url | Use the highest-resolution image available across all six distributors regardless of priority rank. Resolution is determined by fetching image dimensions at import time or using pixel dimensions in the URL where available. | A small RSR thumbnail is worse than a large Davidson's image. Image quality matters for user experience. |
| additional_images | Merge all unique image URLs from all distributors into a single JSON array. Never discard an image from any source. | More images = better product pages. No reason to limit. |
| description | Use the longest non-empty description available across all distributors, regardless of priority rank. Character count determines winner. | Longer descriptions serve SEO. RSR descriptions are often shorter than Davidson's. |
| msrp | Use the highest MSRP value across all six distributors. | Some distributors carry outdated lower MSRPs. Highest is most likely current. |
| rounds_per_box | Cross-validate across all distributors. If all agree: use the value. If any conflict: do not auto-resolve. Set record_status = Admin Review and write a conflict log entry flagging this field for human review. | Incorrect rounds_per_box directly breaks price-per-round calculations. Conservative approach required. |
| nfa_item | If ANY distributor flags this UPC as an NFA item, the record is marked nfa_item = true. This flag cannot be set to false by a lower-priority source overriding a higher-priority source that flagged it. | Safety flag. Conservative is correct. Missing an NFA flag has legal implications. |
| requires_ffl | If ANY distributor flags this UPC as requiring FFL transfer, the record is marked requires_ffl = true. Same conservative logic as nfa_item. | Same rationale. FFL requirement must never be missed. |


| Field | Type | Req | Conflict Rule | Notes |
| --- | --- | --- | --- | --- |
| upc | Text(20) | Y | — | Primary key. Unique index. No duplicates. |
| title | Text(255) | Y | Priority | Standard priority hierarchy |
| brand | Text(100) | Y | Priority | Standard priority hierarchy |
| model | Text(100) | N | Priority | Standard priority hierarchy |
| category | IPS Category | Y | Priority | Mapped via category mapping config per distributor |
| subcategory | Text(100) | N | Priority | Secondary label |
| caliber | Text(50) | N | Priority | Caliber or gauge — firearms and ammo |
| action_type | Text(50) | N | Priority | Semi-auto, bolt, revolver, pump, lever, etc. |
| barrel_length | Decimal | N | Priority | Inches |
| capacity | Integer | N | Priority | Magazine or cylinder round count |
| finish | Text(100) | N | Priority | Blued, stainless, cerakote, parkerized, etc. |
| weight_oz | Decimal | N | Priority | Weight in ounces |
| overall_length | Decimal | N | Priority | Overall length in inches |
| msrp | Decimal | N | Highest value | Use highest MSRP across all distributors |
| description | Textarea | N | Longest text | Use longest description across all distributors |
| image_url | Text(500) | N | Highest res | Use highest resolution image across all distributors |
| additional_images | Textarea | N | Merge all | JSON array — merged from all distributors |
| nfa_item | Boolean | Y | Any = true | True if ANY distributor flags as NFA |
| requires_ffl | Boolean | Y | Any = true | True if ANY distributor flags FFL required |
| is_ammo | Boolean | Y | Priority | Drives per-round price logic in Plugin 3 |
| rounds_per_box | Integer | N | Cross-validate | Flag for Admin Review if sources conflict |
| distributor_sources | Text(255) | Y | Append all | Comma-separated list of all sources carrying this UPC |
| primary_source | Text(100) | Y | — | Distributor that supplied the most winning fields |
| locked_fields | Textarea | N | — | JSON array of admin-locked field names |
| record_status | Select | Y | — | Active / Discontinued / Admin Review / Pending |
| last_updated | DateTime | Y | — | Timestamp of most recent import change |
| total_min_price | Decimal | N | — | Denormalized: lowest (dealer_price + shipping_cost) across all active in-stock dealer listings. Recalculated after every dealer feed import. Powers price range filter and sort. |
| free_ship_avail | Boolean | N | — | Denormalized: true if any active in-stock dealer listing has free_shipping = true. Powers Free Shipping filter and badge on listing cards. |
| min_cpr | Decimal | N | — | Denormalized: lowest CPR (item price only, no shipping) across all active in-stock ammo listings. Null for non-ammo products. Powers CPR range filter. |
| min_cpr_shipped | Decimal | N | — | Denormalized: lowest CPR including shipping across active in-stock ammo listings. Powers CPR range filter when toggle is ON. |
| active_dealer_count | Integer | N | — | Denormalized: count of active in-stock dealer listings. Powers 'Most Dealers' sort and listing card display. |


| Field | Type | Notes |
| --- | --- | --- |
| Feed Name | Text | Internal label e.g. 'RSR Group Primary' |
| Distributor | Select | One of the six — determines priority rank automatically |
| Feed URL | Text | Full URL or FTP path to feed |
| Feed Format | Select | XML / JSON / CSV |
| Auth Type | Select | None / Basic Auth / API Key / FTP Credentials |
| Auth Credentials | Encrypted | Stored encrypted at rest — never displayed in plaintext after save |
| Field Mapping | JSON | Maps distributor field names to canonical schema field names |
| Category Mapping | JSON | Maps distributor category strings to our IPS category IDs |
| Import Schedule | Select | 15 min / 30 min / 1 hr / 6 hrs / Daily |
| Active | Toggle | Disable feed without deleting config |
| Last Run | DateTime | Read-only — populated by importer |
| Last Record Count | Integer | Read-only — records processed last run |
| Last Run Status | Select | Read-only: completed / failed / running |


| Field | ES Type | Searchable | Filterable | Facet |
| --- | --- | --- | --- | --- |
| upc | keyword | Y | Y | N |
| title | text (analyzed) | Y | N | N |
| brand | keyword | Y | Y | Y |
| model | text (analyzed) | Y | N | N |
| category | keyword | N | Y | Y |
| subcategory | keyword | N | Y | Y |
| caliber | keyword | Y | Y | Y |
| action_type | keyword | N | Y | Y |
| barrel_length | float | N | Y — range | N |
| capacity | integer | N | Y — range | N |
| msrp | float | N | Y — range | N |
| nfa_item | boolean | N | Y | N |
| requires_ffl | boolean | N | Y | N |
| is_ammo | boolean | N | Y | N |
| record_status | keyword | N | Y — active only shown to public | N |


| Decision | Selection |
| --- | --- |
| Restriction types captured | State-level restrictions only — e.g. not shippable to CA, NY, MA. Other restriction types (FFL-only, age verification, hazmat) remain manually managed by admin for now. |
| Trust model | All distributors require admin review before any state restriction is applied publicly. Distributor data may be wrong or outdated — admin approves or rejects every flag before it goes live. |
| Display behavior | Badge shown on listing — [STATE RESTRICTED] — but listing is always visible. Buyer sees the warning and can decide. Listing is never hidden automatically from distributor flag data. |
| Field mapping | Both: standard field names auto-detected on import, non-standard names mappable in ACP via the same field mapping interface used for price/stock fields. |
| Sync behavior | Restrictions update on every feed sync — not sticky. When a restriction is added or removed by the distributor, admin is notified. Distributor data drives the flag, not manual entry. |
| Restriction scope | Both: per-dealer listing restrictions (only that dealer's listing affected) and per-product catalog restrictions (whole product flagged). Flag type determines which level applies. |


| Restriction Type | Scope | Display Behavior | Auto-detect Field Names |
| --- | --- | --- | --- |
| State shipping restriction | Per-dealer listing — only that dealer's listing is affected. Other dealers listing the same product show without restriction. | [STATE RESTRICTED] badge shown on listing. Listing always visible — buyer sees the warning and decides. | restricted_states, no_ship_to, blocked_states, ship_restriction, state_restrictions |


| Step | What Happens |
| --- | --- |
| 1 — Feed sync detects restriction field | During import, the compliance flag field is detected in the feed (e.g. restricted_states = CA,NY). A record is written to gd_compliance_flags with status = pending_review. |
| 2 — Admin notified | Admin receives an email notification: 'New state restriction detected for [product name] from [distributor]. Review required before it goes live.' |
| 3 — Admin reviews in ACP | Compliance review panel in ACP shows the pending flag with: product name, UPC, distributor, states listed, first detected date. Admin clicks Approve or Reject. |
| 4a — Approved | Flag status set to active. [STATE RESTRICTED] badge appears on that dealer's listing in the price comparison table. |
| 4b — Rejected | Flag status set to rejected. No badge shown. Flag stays in log for audit trail but does not affect the listing. |
| 5 — Subsequent syncs | If distributor continues sending the flag: last_confirmed_at updates, status unchanged. If distributor stops sending: admin notified 'Distributor removed [restriction] — current status: active/rejected.' |


| Scenario | Resolution |
| --- | --- |
| Distributor adds a restriction admin has not set | Queued for admin review. Does not go live until approved. |
| Distributor removes a restriction admin manually set | Admin manual entry persists. Notification sent: 'Distributor removed [restriction] but admin-set entry remains active.' |
| Two distributors disagree — one says FFL required, other does not | Most restrictive wins: if any source flags FFL required, the flag applies platform-wide |
| Admin has manually overridden a flag and distributor subsequently changes it | Admin override takes priority. Admin notified of the conflict. |


| Level | Scope | When to Use | How to Set |
| --- | --- | --- | --- |
| Product level | Applies to ALL dealer listings for this UPC regardless of dealer | Product is legally restricted in certain states regardless of who ships it — e.g. magazine over state capacity limit | ACP → Catalog → [Product] → Restrictions tab → Add states → Save |
| Dealer listing level | Applies to that dealer's listing only — other dealers unaffected | A specific dealer cannot ship to certain states due to their own license limits, not a product issue | ACP → Dealers → [Dealer] → Listings → [Product] → Restrictions tab → Add states |


| Field | Default State | Conflict Trigger Condition | Can Be Disabled Per Distributor |
| --- | --- | --- | --- |
| restricted_states | ON for all | Incoming adds states not in current list OR removes flagged states | Yes |
| nfa_item | ON for all | Incoming changes true → false or false → true | Yes |
| requires_ffl | ON for all | Incoming changes true → false or false → true | Yes |
| caliber | ON for all | Incoming sends a different caliber than catalog record | Yes |
| rounds_per_box | ON for all | Incoming differs by more than 10% from current value | Yes |
| category | OFF by default | Incoming assigns a different product category | Yes — enable for distributors with reliable category data |
| manufacturer | OFF by default | Incoming sends a different manufacturer name | Yes |
| description | OFF by default | Incoming description differs from current | Yes — noisy, only enable if needed |


| Panel Tab | Contents | Actions |
| --- | --- | --- |
| New restrictions | State restriction flags from distributor feeds awaiting approval | Approve (applies badge) / Reject (discards) |
| Feed conflicts | Incoming values that disagree with current database on compliance fields. Shows: product, field, current value, incoming value, distributor. | Accept new value / Keep existing (locks field vs this distributor) / Set custom value (hard locks field) |
| Locked fields | All fields locked against feed overwrites. Shows: product, field, locked value, lock reason, locked by, lock date. | Unlock / Update lock reason |
| Admin restrictions | All manually set state restrictions. Shows: product or listing, states, set by, date, note. | Remove / Edit states |


| Resolution | What Happens | Field Status After |
| --- | --- | --- |
| Accept new value | Database updates to feed value. Future imports update this field normally. | Unlocked — feed updates freely |
| Keep existing value | Feed value discarded. Field locked against this distributor only — other distributors can still update it. Reason note required. | Distributor-specific lock |
| Set custom value | Admin enters own value. Field hard-locked against ALL distributors. Reason note required. | Hard lock — no distributor can overwrite |
| Auto-accept (48hrs) | If admin takes no action within 48 hours of the conflict being detected, the incoming feed value is automatically applied and the conflict is marked auto_accepted. No lock is created. Admin receives a digest email listing all auto-resolved conflicts from the past 24 hours. | Unlocked — field updates freely going forward |


| Tier | Monthly Price | Feed Sync | Features Included |
| --- | --- | --- | --- |
| Basic | $149/mo | Every 30 minutes | Feed ingestion, listings displayed, basic click analytics, email support |
| Pro | $399/mo | Every 15 minutes | All Basic + priority placement in price tables, full analytics dashboard, faster sync, phone support |
| Enterprise | $799/mo | Every 5 minutes | All Pro + API access for price queries, dedicated account manager, custom feed parsing assistance |


| Group | Type | How Assigned | Permissions |
| --- | --- | --- | --- |
| Registered Members | Primary group — default for all sign-ups | Automatic on registration | Browse products, search, set state, watchlist, rate dealers, comment, submit deal reports. Cannot access dealer dashboard, feed settings, or dealer analytics. |
| Dealers | Secondary group — added on top of Registered Members | IPS Commerce auto-assigns when subscription product is purchased. Auto-removes when subscription expires, is cancelled, or payment fails. | All Registered Member permissions PLUS: access to /dealers/dashboard, feed configuration, dealer analytics (Pro/Enterprise only), dealer profile management. |
| Administrators | Primary group | Manual assignment only | Full access to all ACP sections including GD Master Catalog, GD Dealer Manager admin panels, conflict log, unmatched UPCs, dealer suspension, and all plugin settings. |


| Subscription Product | Group Action on Purchase | Group Action on Expiry/Cancel/Failed Payment |
| --- | --- | --- |
| GD Basic Feed — $149/mo | Add member to Dealers secondary group | Remove member from Dealers secondary group. All their listings set to Suspended immediately. |
| GD Pro Feed — $399/mo | Add member to Dealers secondary group | Remove member from Dealers secondary group. All their listings set to Suspended immediately. |
| GD Enterprise Feed — $799/mo | Add member to Dealers secondary group | Remove member from Dealers secondary group. All their listings set to Suspended immediately. |


| Field | Type | Required | Validation Rules |
| --- | --- | --- | --- |
| Store Name | Text | Y | 2–100 characters |
| Store Website URL | URL | Y | Valid URL, must resolve |
| FFL Number | Text | Y | Format validated: X-XX-XXXXX — regex enforced |
| Business Street Address | Text | Y | Required for display on dealer profile page |
| City | Text | Y |  |
| State | Select | Y | US states only |
| ZIP Code | Text | Y | 5-digit US ZIP |
| Contact Name | Text | Y | Primary contact person at the dealership |
| Contact Email | Email | Y | Must match IPS account email |
| Contact Phone | Text | Y | 10-digit US format — stored as digits only |
| Product Feed URL | URL | Y | Direct URL to their product feed file |
| Feed Format | Select | Y | XML / JSON / CSV |
| Feed Update Frequency | Select | N | How often dealer updates their own feed — informational only |
| Dealer Agreement | Checkbox | Y | Must accept Dealer Terms of Service before proceeding |


| Field | Type | Req | Notes |
| --- | --- | --- | --- |
| upc | Text(20) | Y | Foreign key to Products DB — indexed |
| dealer_id | Integer | Y | IPS member_id of dealer — indexed |
| dealer_price | Decimal | Y | Current price from most recent feed |
| shipping_cost | Decimal | N | Flat shipping cost — null means 'varies' |
| free_shipping | Boolean | Y | True if dealer offers free shipping |
| free_ship_minimum | Decimal | N | Order minimum required for free shipping |
| in_stock | Boolean | Y | Current stock status |
| stock_quantity | Integer | N | Quantity if provided in feed |
| dealer_url | Text(500) | Y | Direct product page URL on dealer site — tracked via /go/ redirect |
| dealer_sku | Text(100) | N | Dealer internal SKU |
| condition | Select | Y | New / Used / Blemished / Refurbished |
| ships_to_restriction | Textarea | N | JSON array of 2-letter state codes dealer cannot ship to |
| last_seen_in_feed | DateTime | Y | Last import run that contained this UPC |
| listing_status | Select | Y | Active / Out of Stock / Discontinued / Suspended |
| subscription_tier | Select | Y | Basic / Pro / Enterprise — copied from dealer at import time |


| Column | Source | Notes |
| --- | --- | --- |
| Dealer | Dealer profile | Name linked to dealer profile page. Tier badge for Pro/Enterprise if dealer opts in. Star rating shown. |
| Condition | Dealer Listing | New / Used / Blemished / Refurbished |
| Price | Dealer Listing | Item price from latest feed import |
| Shipping | Dealer Listing | Flat rate shown, 'FREE' in green if free_shipping=true, 'Varies' if shipping_cost is null |
| Total | Calculated | dealer_price + shipping_cost. Default sort column. 'FREE' shipping dealers show price as total. |
| Ships To | Dealer Listing | State restriction flags — see Section 4.5. If user state is set and restricted: orange badge. |
| Stock | Dealer Listing | Green checkmark if in_stock. Red X if out of stock with 'Notify Me' link. |
| Buy | Dealer Listing | Button linking to /go/{listing-id} — tracked redirect to dealer URL |


| Filter | Type | Applies To |
| --- | --- | --- |
| Brand | Multi-select checkbox with counts | All categories |
| Caliber / Gauge | Multi-select checkbox with counts | Firearms and Ammo only |
| Action Type | Multi-select checkbox with counts | Firearms only |
| Condition | Multi-select checkbox | All |
| Price Range | Min/max range slider — labeled 'Total Price (inc. shipping)' | All — filters on total delivered cost (dealer_price + shipping_cost) of cheapest available dealer. See Section 4.3 for null shipping behavior. |
| Shipping Rate | Multi-select: Free Shipping Only ‖ Under $10 ‖ Under $20 ‖ Any | All — filters to products with at least one dealer meeting the selected shipping threshold. See Section 4.3. |
| CPR Range | Min/max range slider — labeled 'Cost Per Round' | Ammo only — filters on CPR. Toggle state (Section 4.2) determines whether shipping is included in CPR for filter purposes. |
| Barrel Length | Min/max range slider (inches) | Firearms only |
| Magazine Capacity | Min/max range slider | Firearms only |
| In Stock Only | Toggle | All — hides products with zero in-stock dealers |
| Free Shipping Only | Toggle — shortcut for Shipping Rate filter | All — equivalent to selecting 'Free Shipping Only' in the Shipping Rate filter. Duplicate for discoverability. |
| Ships to My State | Toggle | All — see Section 4.5 |
| FFL Required | Toggle — exclude or show only | All |
| NFA Items | Toggle — exclude or show only | All |
| Dealer | Multi-select checkbox | All — filter to specific dealers |
| Subscription Tier | Multi-select (Basic/Pro/Ent) | All — for users who prefer established dealers |


| Component | Minimum | Recommended |
| --- | --- | --- |
| PHP | 8.1 | 8.2+ |
| MySQL / MariaDB | 8.0 / 10.6 | Latest stable |
| OpenSearch (self-hosted) | 2.x | Latest stable |
| Redis | 6.x | 7.x |
| RAM | 8 GB | 16 GB |
| CPU | 4 cores | 8 cores |
| Storage | NVMe SSD | NVMe SSD (confirmed on existing VPS) |
| PHP Extensions | curl, json, mbstring, xml, zip | All listed |


| Item | Detail |
| --- | --- |
| Software | OpenSearch 2.x — open source, Apache 2.0 license |
| Hosting | Self-hosted on existing VPS alongside IPS — no separate server required at launch |
| Cost | $0 — no licensing cost, runs on existing VPS resources |
| IPS Integration | Use IPS built-in Elasticsearch integration. In ACP > Search > Elasticsearch, set the endpoint URL to http://localhost:9200 (or the VPS internal IP if on a separate node). IPS does not distinguish between Elasticsearch and OpenSearch at the API level. |
| RAM Allocation | Allocate 4–6 GB heap to OpenSearch on a 16 GB VPS. Remainder serves PHP/MySQL/Redis. |
| Index Strategy | Single index at launch. If index grows beyond 10M records, split into separate Products and Dealer Listings indexes. |
| Backup | OpenSearch snapshots to local disk on the same backup schedule as MySQL. Both must be backed up together to maintain consistency. |
| Scaling Path | If self-hosted becomes a bottleneck: migrate to AWS OpenSearch Service (starts ~$25–40/mo for a small domain) without any code changes — only the endpoint URL in IPS ACP changes. |


| Table | Created By | Purpose |
| --- | --- | --- |
| gd_import_log | Plugin 1 | Distributor feed import run history |
| gd_conflict_log | Plugin 1 | Field-level conflict resolution audit trail |
| gd_price_history | Plugin 2 | Price snapshots per dealer per UPC on change |
| gd_dealer_import_log | Plugin 2 | Dealer feed import run history |
| gd_unmatched_upcs | Plugin 2 | Dealer feed UPCs with no catalog match |
| gd_watchlist | Plugin 3 | User product watches with target prices |
| gd_click_log | Plugin 3 | Outbound click tracking per listing |
| gd_ffl_dealers | Plugin 3 | ATF FFL dealer data with geocoordinates |


| Task | Plugin | Trigger | Notes |
| --- | --- | --- | --- |
| Distributor feed import — each of 6 feeds | Plugin 1 | Configurable schedule per feed | Default 30 min. Runs as independent task per feed. |
| Dealer feed import — Enterprise tier | Plugin 2 | Every 5 minutes | Queue-based, multiple dealers run in parallel |
| Dealer feed import — Pro tier | Plugin 2 | Every 15 minutes | Queue-based |
| Dealer feed import — Basic tier | Plugin 2 | Every 30 minutes | Queue-based |
| Price alert check | Plugin 2/3 | After each dealer import | Triggered, not scheduled. Runs only when feed detected price decreases. |
| OpenSearch re-index | Plugin 1/2 | After each import | Incremental — only re-indexes changed records, never full rebuild during operation |
| Out-of-stock cleanup | Plugin 2 | Hourly | Marks listings not seen in last feed run as Out of Stock |
| Click log aggregation | Plugin 3 | Daily at 2am | Aggregates raw clicks to daily totals per listing |
| ATF FFL data refresh | Plugin 3 | Monthly | Downloads latest ATF FFL dataset, geocodes new entries |


| Decision | Selection |
| --- | --- |
| Who Can Review | Any registered member. Verified purchasers (member clicked a Buy link for that UPC in gd_click_log) receive a Verified Purchase badge on their review. |
| Rating Structure | Single overall 1–5 star rating. No sub-category ratings. Type-specific written prompt guidance in the review form body helps users describe what matters for that product type. |
| Review Form Prompts | Completely uniform form for all product types — same Pros, Cons, and Review Body prompts regardless of product category. Single star rating field. No type-specific guidance text. |
| Media Attachments | Images only — up to 5 images per review, max 5MB each, JPEG/PNG/WEBP. No video at launch. |
| Reviews Hub Location | Top-level navigation item — /reviews — with full hub navigation including Latest, Top Rated, By Brand, By Category, Most Helpful, Verified Only tabs. |
| Resolved Review Impact | Excluded from dealer star average entirely when resolved. Full review text, original rating, and complete dispute thread remain publicly visible with green [RESOLVED] badge. |


| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| Overall Rating | 1–5 stars | Y | Single rating — only star rating field on the form. No sub-categories. |
| Review Title | Text (150) | Y | Headline summarizing the review |
| Review Body | Textarea | Y | Minimum 50 characters. No maximum enforced but UI suggests 200–1000. |
| Pros | Text (500) | N | Freeform — What did you like? Same label for all product types. |
| Cons | Text (500) | N | Freeform — What could be better? Same label for all product types. |
| Would Recommend | Yes / No | Y | Simple binary — shown as a recommendation percentage on product page |
| Images | File upload | N | Up to 5 images — JPEG/PNG/WEBP, max 5MB each |
| Usage Context | Select | N | Home Defense / Concealed Carry / Range / Competition / Hunting / Collection / Other |
| Time Owned | Select | N | Less than 1 month / 1–6 months / 6–12 months / 1–3 years / 3+ years |


| Field | ES Type | Searchable | Filterable |
| --- | --- | --- | --- |
| upc | keyword | N | Y |
| overall_rating | integer | N | Y — range and facet |
| product_type | keyword | N | Y — facet |
| title | text (analyzed) | Y | N |
| body | text (analyzed) | Y | N |
| pros | text (analyzed) | Y | N |
| cons | text (analyzed) | Y | N |
| would_recommend | boolean | N | Y |
| usage_context | keyword | N | Y — facet |
| time_owned | keyword | N | Y — facet |
| usage_context | keyword | N | Y — facet |
| verified_purchase | boolean | N | Y — facet |
| created_at | date | N | Y — range |
| brand | keyword | N | Y — facet — denormalized from Product record at index time |
| category | keyword | N | Y — facet — denormalized from Product record at index time |
| status | keyword | N | Y — only approved shown publicly |
| dispute_status | keyword | N | Y — filter |


| Decision | Selection |
| --- | --- |
| Rebate Sourcing | Hybrid: automated fixed-schedule scraper as base layer + community submissions to fill gaps the scraper misses |
| Scraper Schedule | Fixed schedule only — configured per manufacturer target (nightly default). No on-demand triggers. |
| Scraper URL Management | Admin configures all manufacturer rebate URLs in ACP — fully manageable list. No community URL suggestions. |
| Scraper Auto-Approval | Known manufacturers (in admin URL list): scraped rebates auto-approve and go live immediately. This is the only scenario since community cannot add URLs. |
| Source Transparency | Source label shown to all users — auto-detected rebates show source label + manufacturer page link. Community submissions show 'Community Submitted' + submitter username. |
| User Depth | Full rebate management: form/instructions + user submission tracking (Saved/Submitted/Received/Rejected) + community success rate reporting |
| Expired Rebate Handling | Stays visible marked Expired for 30 days then archived. Archived rebates searchable permanently. |
| Flagging | Anyone logged in can flag. Admin reviews all flags. Auto-queue at 3 flags. |


| Config Field | Type | Notes |
| --- | --- | --- |
| Manufacturer Name | Text | Display name e.g. 'Glock', 'Hornady', 'Vortex Optics' |
| Rebate Page URL | URL | Direct URL to manufacturer's current rebates page |
| Extraction Config | JSON | CSS selectors or XPath to extract rebate fields — see 8.2.2 |
| Scrape Frequency | Select | Nightly / Every 6 hours / Weekly — nightly default |
| Known Manufacturer | Toggle | ON by default for all admin-configured URLs. Scraped rebates from this manufacturer auto-approve and go live immediately. Turn OFF temporarily if a manufacturer has been problematic. |
| Respect robots.txt | Toggle | Always ON — cannot be disabled. Scraper checks robots.txt before each crawl. |
| Rate Limit (seconds) | Integer | Minimum seconds between requests to this domain. Default 5. |
| Active | Toggle | Enable/disable without deleting config |
| Last Scraped | DateTime | Read-only — last successful crawl |
| Last Rebate Count | Integer | Read-only — rebates found on last crawl |


| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| Manufacturer | Text | Y | Brand/maker offering the rebate |
| Rebate Title | Text | Y | Descriptive title e.g. 'Glock Summer Rebate 2026 — $75 Back' |
| Description | Textarea | Y | Full rebate details — what qualifies, restrictions |
| Rebate Amount | Decimal | Y | Dollar value of rebate |
| Rebate Type | Select | Y | Mail-In / Instant / Online / Combination |
| Product Type | Select | Y | Firearm / Ammo / Optic / Accessory / Suppressor / Any |
| Eligible Models | Text | N | Which specific models qualify — freeform |
| Start Date | Date | Y | When rebate period begins |
| End Date | Date | Y | When rebate period ends |
| Submission Deadline | Date | N | Last date to submit rebate paperwork (often after end date) |
| Rebate Form URL | URL | N | Direct link to manufacturer's rebate form |
| Rebate PDF URL | URL | N | Link to printable PDF form if available |
| Manufacturer URL | URL | N | Main manufacturer rebate page for reference |
| Submission Steps | Textarea | N | Step-by-step instructions for submitting this rebate |
| Source Verification | URL | Y | Link to source proving this rebate exists — required for admin verification |


| Decision | Selection |
| --- | --- |
| Time Limits | Dealer has 14 days to respond to a review. After dealer responds, customer has 14 days to mark resolved. After both windows close with no action, review stands at full weight. |
| Resolved Review Impact | Resolved review is excluded from star average entirely. Review text, rating, and full dispute thread remain publicly visible with green [RESOLVED] badge. |
| Visibility | All dispute activity — dealer responses, customer replies, resolution — is fully public on the dealer profile and product page. |


| Stage | Actor | Action | Time Limit | Next Stage |
| --- | --- | --- | --- | --- |
| 1 — Review Posted | Customer | Posts dealer review with star rating | — | Dealer notified immediately |
| 2 — Dealer Response | Dealer | Posts official response to the review | 14 days from review posted | Customer notified of dealer response |
| 3 — Customer Reply | Customer | Replies to dealer response (optional) | 14 days from dealer response | Dealer can respond again — unlimited follow-ups |
| 4 — Follow-up Thread | Both | Additional back-and-forth replies | No limit on follow-ups | Either party can request resolution |
| 5 — Resolution | Customer | Marks dispute as Resolved — confirms issue addressed | Customer discretion | Review excluded from star average, Resolved badge applied |
| 6 — Expired (no action) | System | If dealer never responded within 14 days: dispute_status = no_response logged | 14 days | Review stands at full weight, dealer no_response count increments |


| Metric | Calculation | Displayed |
| --- | --- | --- |
| Response Rate | Disputes responded to within 14 days / Total disputes * 100 | Dealer profile, dealer comparison table |
| Resolution Rate | Disputes marked resolved by customer / Total disputes responded to * 100 | Dealer profile |
| Avg Response Time | Average hours from review posted to dealer first response (responded disputes only) | Dealer profile |
| No Response Count | Count of disputes where dealer never responded within 14 days | Dealer profile — prominently |
| Star Average (adjusted) | Average overall_rating of all approved reviews EXCLUDING resolved reviews | All dealer rating displays platform-wide |
| Star Average (raw) | Average overall_rating of all approved reviews INCLUDING resolved reviews | Shown in small text below adjusted average for transparency |


| Feature | Decision |
| --- | --- |
| Wishlist Visibility | Public and Unlisted (link only) — no private option. Public wishlists are followable by other users. |
| Wishlist Structure | Tiered: Free members get 1 wishlist. VIP members ($9.99/mo) get up to 10 named wishlists. |
| Wishlist Item Notes | VIP members only — free members cannot add notes to wishlist items |
| Wishlist Notifications | All six triggers for all members: price drop (any), price drop below target, back in stock, goes out of stock, new dealer lists product, heat/MAP milestones |
| VIP Membership | $9.99/mo consumer tier separate from dealer subs. Full feature set in Section 10.2. |
| Deal Heat Score Display | Badge label only (Cold/Warm/Hot/Fire) — no numeric score shown to users |
| Browser Extension | Chrome, Firefox, and Edge at launch |
| Extension Popup Content | Lowest price + heat badge + historic low badge + link to full comparison page |
| Search Query Alerts | Both — IPS notification system + dedicated /alerts page. Free: 3 max. VIP: unlimited. |
| MAP Tracker Depth | All three: badge on listings + watcher notifications + /deals/below-map page |
| Compliance Notifications | Auto-subscribed to own state on registration. Can add more states manually. |
| Dealer Leaderboard | Dealers tab within the consolidated IPS native leaderboard — not a separate page |
| Ammo Optimizer Location | Both — standalone /tools/ammo-calculator AND embedded on ammo product pages |


| Group | Type | Assigned By | Key Permissions |
| --- | --- | --- | --- |
| Registered Members | Primary — default for all sign-ups | Automatic on registration | 1 wishlist, 3 search alerts, standard notification priority, ads shown, 90-day price history, enacted-only compliance alerts |
| VIP Members | Secondary consumer tier | IPS Commerce on $9.99/mo purchase. Removed on cancellation/expiry/failed payment. | 10 wishlists, unlimited alerts, ad-free, early notifications, priority delivery, full price history, all compliance triggers, VIP badge |
| Dealers | Secondary dealer tier | IPS Commerce on dealer subscription | All dealer dashboard features per their subscription tier |
| Administrators | Primary | Manual only | Full ACP access |


| Feature | Free Member | VIP Member ($9.99/mo) |
| --- | --- | --- |
| Wishlists | 1 wishlist | Up to 10 named wishlists |
| Wishlist visibility | Public and Unlisted | Public and Unlisted (same — visibility is not gated) |
| Wishlist following | Can follow others | Can follow others + own public lists show follower count |
| Wishlist item notes | No — notes field locked, upgrade prompt shown | Yes — freeform note per wishlist item |
| Search query alerts | 3 active max | Unlimited active alerts |
| Alert delivery priority | Standard batch | Priority — processed first in every import run, before free members |
| Early deal notifications | No | 30-minute head start on below-MAP alerts and Fire badge alerts |
| Ad-free experience | Ads shown | All platform ads suppressed |
| Notification digest | Daily or weekly | Instant, daily, or weekly — full control |
| Price history chart | 90 days | Full all-time history — no date limit |
| Compliance alert triggers | Enacted laws only | All four triggers: enacted, committee, introduced, defeated |
| Ammo optimizer saved setups | No | Up to 10 saved optimizer configurations |
| VIP badge | No | Shown on profile, reviews, and forum posts |


| Component | Weight | Calculation |
| --- | --- | --- |
| Price vs 90-day average | 40% | (90_day_avg_price - current_price) / 90_day_avg_price * 100. Positive = below average = hotter. Capped at 40 points. |
| Price vs all-time low | 30% | If current_price equals or beats all-time low: 30 points. If within 5% of all-time low: 20 points. Otherwise scaled proportionally. |
| Click velocity | 20% | Clicks on this listing in last 24 hours vs 30-day daily average. Above average click rate = hotter. Capped at 20 points. |
| Community upvotes | 10% | Net upvotes on this product's most recent community deal post, if any. Capped at 10 points. |


| Internal Score | Badge Shown | Color | Meaning |
| --- | --- | --- | --- |
| 0–24 | Cold | Grey | At or above average price — no badge shown on listing cards, label only on product page |
| 25–49 | Warm | Blue | Somewhat below average — decent price |
| 50–74 | Hot | Orange | Significantly below average — good deal |
| 75–100 | Fire | Red | Historic low territory — exceptional deal. Triggers early notification for VIP members. |


| Trigger | When It Fires | Default |
| --- | --- | --- |
| Price drop (any) | Any dealer's price decreases for a wishlist item | ON |
| Price drop below target | Any dealer's price reaches or goes below the item's target price | ON when target price is set |
| Back in stock | Any dealer lists a wishlist item as in_stock after it was fully out of stock | ON |
| Goes out of stock | All dealers for a wishlist item go out of stock simultaneously | OFF — opt-in only |
| New dealer lists product | A new dealer adds a wishlist item to their feed for the first time | OFF — opt-in only |
| Heat score reaches Fire | A wishlist item's best dealer listing reaches Fire badge status | ON |
| All-time low hit | A wishlist item hits its all-time lowest price | ON |


| Input | Type | Notes |
| --- | --- | --- |
| Caliber | Select or search | Pulls from catalog caliber list — all available ammo calibers |
| Target Quantity | Integer | How many rounds the user wants to buy |
| Budget (optional) | Decimal | Maximum total spend including shipping |
| Condition | Select | New / Any — defaults to New |
| Grain Weight | Select | Optional — filter to specific grain weight |
| Bullet Type | Select | Optional — FMJ / HP / SP / etc. |
| Include Shipping | Toggle | ON by default — calculates true delivered cost |
| In Stock Only | Toggle | ON by default — only include dealers with in_stock = true |
| State | Auto-filled | User's saved state pre-filled — filters out restricted dealers |


| Metric | Source | How Calculated |
| --- | --- | --- |
| Overall Score | Composite | Weighted average of all metrics below — used for primary ranking |
| Response Rate | Dispute system | % of customer reviews responded to within 14 days — from Plugin 6 |
| Resolution Rate | Dispute system | % of disputes resolved by customer — from Plugin 6 |
| Avg Response Time | Dispute system | Average hours from review posted to dealer first response |
| Community Rating | Dealer reviews | Overall star average (adjusted, resolved excluded) — from Plugin 3 |
| Pricing Accuracy | Listing reports | Inverse of report rate — dealers with fewer pricing error reports rank higher |
| Listing Freshness | Feed imports | Average time between feed imports — fresher data ranks higher |


| Component | File | Responsibility |
| --- | --- | --- |
| Content script | content.js | Injected into all retailer page domains listed in the manifest. Scans the DOM for UPC and product data. Sends detected data to background worker. Does NOT make API calls directly — all network requests go through the background worker. |
| Background worker | background.js | Receives UPC from content script. Checks local cache first (24-hour TTL). If cache miss, calls /api/v1/extension/lookup. Updates extension icon and badge. Manages auth token if user is logged in. |
| Popup UI | popup.html + popup.js | Rendered when user clicks extension icon. Reads current product data from background worker storage. Renders price comparison card. Handles wishlist button and login prompt. |
| Manifest | manifest.json | Declares permissions (activeTab, storage, identity), host_permissions for platform API domain, content_script match patterns for supported retailer domains. |
| Platform API | /api/v1/extension/lookup | Server-side read-only endpoint. Accepts UPC. Returns product and pricing data. Rate limited at Nginx level — 60 requests per minute per IP. |


| Retailer Category | Example Domains Included at Launch | Notes |
| --- | --- | --- |
| Major online FFL retailers | palmettostatearmory.com, grabagun.com, kygunco.com, budsgunshop.com, gunprime.com, sportsmansoutdoorsuperstore.com, brownells.com, midwayusa.com | Primary target domains — highest traffic, most likely to have Schema.org UPC data |
| Ammo specialists | ammoseek.com, sgammo.com, targetsportsusa.com, luckygunner.com, bulkammo.com | Extension shows ammo pricing popup with CPR comparison |
| Marketplace / aggregators | guns.com, gun.deals, wikiarms.com | Shows GunRack competing prices — turns competitor sites into referral sources |
| Big box retailers | cabelas.com, basspro.com, academy.com | DOM scanning required — these don't always have clean UPC schema |


| Popup State | Trigger | Content Shown |
| --- | --- | --- |
| Loading | Icon clicked, API call in flight | Spinner + 'Checking GunRack prices...' text. Max 3 second timeout before error state. |
| Match found | API returned product data | Full price card — see elements below. |
| Title match only | Only fuzzy title match available | Price card with 'Possible match' yellow banner at top. 'Not the right product? Search manually' link. |
| No match | No UPC or title match in catalog | 'Product not found on GunRack' + manual search field + 'Submit this product' link. |
| API error / timeout | Network failure or 3s timeout | 'GunRack unavailable right now' with retry button. Cached data shown if available. |
| Not a retailer page | Extension clicked on non-product page | 'Browse to a firearm or ammo product page to compare prices.' |


| Parameter | Type | Description |
| --- | --- | --- |
| upc | string (optional) | 12–13 digit UPC. If provided, exact UPC match attempted first. |
| title | string (optional) | URL-encoded product title. Used if no UPC provided or UPC not found. Fuzzy match against catalog product names. |
| v | integer | API version — currently 1. Future-proofs the endpoint for breaking changes. |


| Store | Listing Name | Notes |
| --- | --- | --- |
| Chrome Web Store | GunRack — Compare Firearm Prices | Category: Shopping. Requires Google Developer Account ($5 one-time). Review typically 1–3 days. |
| Firefox Add-ons (AMO) | GunRack — Compare Firearm Prices | Category: Shopping. Free listing. Requires Mozilla Developer Account. Review typically 1–7 days. |
| Microsoft Edge Add-ons | GunRack — Compare Firearm Prices | Chrome Web Store extension can be submitted directly — Edge accepts Chrome MV3 extensions with minor adjustments. |


| Decision | Selection |
| --- | --- |
| SEO Scope | Full suite — schema markup, sitemaps, canonical URLs, Open Graph, structured data, meta tags |
| Meta Description Style | Template-based with dynamic variables + admin override per page or category |
| Sitemap Coverage | All page types: products, categories, reviews, rebates, dealer profiles, brand pages |
| Schema Types | Product, AggregateOffer, Review, AggregateRating, BreadcrumbList, WebSite, Organization, FAQPage |


| Page Type | URL Pattern | Example |
| --- | --- | --- |
| Product detail | /products/{category-slug}/{brand-slug}-{model-slug}-{upc} | /products/handguns/pistols/glock-19-gen5-9mm-764503037261 |
| Category | /products/{category-slug} | /products/handguns/pistols |
| Brand | /brands/{brand-slug} | /brands/glock |
| Review hub | /reviews | /reviews |
| Product reviews | /reviews/{category-slug}/{brand-slug}-{model-slug}-{upc} | /reviews/handguns/pistols/glock-19-gen5-9mm-764503037261 |
| Brand reviews | /reviews/brand/{brand-slug} | /reviews/brand/glock |
| Category reviews | /reviews/category/{category-slug} | /reviews/category/handguns |
| Rebate hub | /rebates | /rebates |
| Rebate detail | /rebates/{manufacturer-slug}-{title-slug}-{id} | /rebates/glock-summer-rebate-2026-142 |
| Dealer profile | /dealers/{dealer-slug} | /dealers/palmetto-state-armory |
| Dealer leaderboard | /dealers/leaderboard | /dealers/leaderboard |
| Below MAP deals | /deals/below-map | /deals/below-map |
| Ammo calculator | /tools/ammo-calculator | /tools/ammo-calculator |
| Alert management | /alerts | /alerts |
| Wishlist public | /wishlist/{username}/{wishlist-slug} | /wishlist/jdoe/home-defense-build |
| Account section | /account/{section} | /account/watchlist |


| Sitemap File | Pages Included | Update Frequency | Priority |
| --- | --- | --- | --- |
| sitemap-products.xml | All active product pages — one URL per UPC | Updated daily — new products added, discontinued removed | 0.8 |
| sitemap-categories.xml | All category and subcategory browse pages | Updated weekly | 1.0 |
| sitemap-reviews.xml | All product pages with at least one approved review | Updated daily | 0.7 |
| sitemap-rebates.xml | All active and recently expired rebate pages | Updated daily | 0.9 for active, 0.3 for expired |
| sitemap-brands.xml | All brand pages at /brands/{slug} and /reviews/brand/{slug} | Updated weekly | 0.6 |
| sitemap-dealers.xml | All active dealer profile pages | Updated weekly | 0.5 |
| sitemap-static.xml | Homepage, /deals/below-map, /tools/ammo-calculator, /reviews, /rebates, /dealers/leaderboard | Static | 1.0 |


| Metric | Target | Applies To |
| --- | --- | --- |
| Largest Contentful Paint (LCP) | Under 2.5 seconds | All public pages |
| First Input Delay (FID) | Under 100ms | All interactive pages |
| Cumulative Layout Shift (CLS) | Under 0.1 | All public pages — no layout shifts from late-loading images or ads |
| Time to First Byte (TTFB) | Under 200ms | Server response time — Redis caching required |
| Mobile PageSpeed Score | 85+ | All public pages — Google uses mobile-first indexing |


| Decision | Selection |
| --- | --- |
| Email Provider | Amazon SES — transactional and digest emails |
| Digest Frequency | Weekly for free members. Daily and weekly options for VIP members. Plus breaking deal alerts for Fire/All-Time Low events. |
| Content Selection | Automated base (heat score + price data) + admin can pin/feature specific deals |
| User Preferences | Simple preferences — choose digest frequency (weekly/daily VIP) and category toggles: Firearms, Ammo, Accessories, Optics. No granular per-alert-type controls. |
| Breaking Alerts | Separate from digest — fires immediately when Fire badge or All-Time Low is hit on watched/wishlisted products |
| Transactional Emails | All existing alert and notification emails (price drop, dispute response, rebate flagged, etc.) routed through SES |


| Email Type | Trigger | Audience | Priority |
| --- | --- | --- | --- |
| Price Drop Alert | Dealer feed import detects price decrease on watched/wishlisted UPC | Members with that UPC in watchlist or wishlist | Instant |
| Below MAP Alert | Dealer listing drops below MAP threshold | Members watching/wishlisting that UPC. VIP: 30-min head start. | Instant — VIP first |
| All-Time Low Alert | Dealer price equals historical minimum for UPC | Members watching/wishlisting that UPC. VIP: 30-min head start. | Instant — VIP first |
| Back in Stock Alert | in_stock flips from false to true on wishlisted item | Members with notify_back_in_stock = true for that wishlist | Instant |
| Fire Badge Alert | Dealer listing heat score crosses into Fire tier | Members with that UPC in wishlist with heat notifications on | Instant — VIP first |
| Search Alert Match | New product or price drop matches saved search query | Alert owner | Per alert frequency setting |
| Weekly Digest | Scheduled — Sunday 8am user local time | All members with digest opted in | Weekly |
| Daily Digest (VIP only) | Scheduled — daily 8am user local time | VIP members with daily digest opted in | Daily |
| Breaking Deal Alert | Fire badge or All-Time Low on any watched/wishlisted product | Members with that UPC in watchlist or wishlist only — not platform-wide. Fires as transactional alert, not configurable separately. | Instant — VIP first |
| Dispute Response | Dealer responds to member's review | Review author | Instant |
| Dispute Resolution | Customer marks dispute resolved | Dealer | Instant |
| Review Approved | Admin approves a pending review | Review author | Instant |
| Review Rejected | Admin rejects a pending review | Review author | Instant |
| Rebate Expiring Soon | Rebate end_date is 7 days away | Members who saved this rebate in tracker | Instant |
| Compliance Alert | Bill Tracker event matches user's subscribed state | Subscribed members | Instant |
| Wishlist Followed | Another user follows a public wishlist | Wishlist owner | Instant — batched if multiple follows same day |
| VIP Expiry Warning | VIP subscription expires in 7 days | VIP member | Instant |
| Dealer Subscription Expiry | Dealer subscription expires in 7 days | Dealer | Instant |
| Welcome Email | New member registration | New member | Instant |
| VIP Welcome Email | VIP subscription activated | New VIP member | Instant |


| Section | Content | Source |
| --- | --- | --- |
| Your Watchlist & Wishlist Highlights | Price changes on the member's watched/wishlisted items since last digest. Only included if there are changes — section omitted if nothing changed. | gd_price_history + watchlist/wishlist tables |
| This Week's Fire Deals | Top 10 listings that hit Fire badge status this week, sorted by discount percentage. Admin can pin up to 3 deals to the top of this section. | heat_score + admin pins |
| All-Time Lows This Week | Products that hit their all-time lowest price this week. Max 8 items. | gd_price_history all-time low flag |
| Below MAP Deals | Listings currently below MAP, sorted by discount amount. Max 6 items. | below_map flag |
| New Rebates | Rebates added or verified in the past 7 days with highest dollar values shown first. Max 5 rebates. | gd_rebates created_at |
| Personalized Picks | Products matching the member's saved search alerts that have dropped in price. Labeled 'Based on your saved searches'. Max 5 items. | gd_search_alerts |
| New Reviews This Week | Highest-rated new reviews (4+ stars, approved this week) for popular products. Max 3 reviews with excerpt. | gd_reviews |
| Expiring Rebates | Rebates expiring within 7 days — urgency section. Max 5. | gd_rebates end_date |
| Compliance Updates | Only included if any enacted laws match member's subscribed states this week. | Firearms Bill Tracker |


| Category Toggle | Default | Effect When OFF |
| --- | --- | --- |
| Firearms | ON | No firearm deals, listings, or reviews included in digest |
| Ammunition | ON | No ammo deals or CPR comparisons included in digest |
| Accessories | ON | No optics, parts, holsters, or gear included in digest |
| Rebates | ON | Rebate sections omitted from digest entirely |
| Compliance | ON | Compliance update section omitted from digest |


| Template Name | Variables |
| --- | --- |
| price-alert | product_name, product_image, previous_price, new_price, dealer_name, total_with_shipping, heat_badge, product_url |
| below-map-alert | product_name, product_image, dealer_price, msrp, discount_pct, dealer_name, product_url |
| alltime-low-alert | product_name, product_image, current_price, previous_low, dealer_name, product_url |
| back-in-stock | product_name, product_image, lowest_price, dealer_count, product_url |
| weekly-digest | member_name, watchlist_items[], fire_deals[], alltime_lows[], below_map_deals[], new_rebates[], personalized_picks[], expiring_rebates[], compliance_updates[], digest_date |
| daily-digest | member_name, watchlist_items[], fire_deals[], alltime_lows[], new_rebates[], digest_date |
| breaking-deal | product_name, product_image, deal_type (Fire/AllTimeLow), current_price, discount_pct, product_url |
| dispute-response | product_name, dealer_name, response_excerpt, dispute_url |
| review-approved | product_name, review_title, product_url |
| review-rejected | product_name, rejection_reason, resubmit_url |
| rebate-expiring | rebate_title, manufacturer, amount, days_remaining, rebate_url |
| compliance-update | state_name, bill_title, bill_summary, impact_summary, bill_url |
| welcome | member_name, getting_started_url |
| vip-welcome | member_name, features_list, account_url |
| vip-expiry-warning | member_name, expiry_date, renew_url |


| Decision | Selection |
| --- | --- |
| Who Can Post | Admin + any registered member. All member posts (including VIP) require admin moderation before going live. Admin posts go live immediately. |
| Display Location | Integrated in the price comparison table on the product page with a [COMMUNITY DEAL] source badge. Also appear in dedicated /deals feed and homepage widget. |
| UPC Requirement | UPC required for product-specific deals (links to master catalog). Freeform allowed for bundles, store-wide sales, or gift cards with no specific UPC. |
| Expiry | No auto-expiry. Deals stay live indefinitely until: (a) submitter manually marks expired, (b) admin manually expires, or (c) community flags reach the expired threshold (3 flags). |
| Voting | Upvotes, downvotes, and comments on all deal posts. |
| Retailer Info | Retailer name (required) + online vs in-store flag (required) + store location (optional, for in-store deals). |
| Reputation | Deal submitters earn reputation points. Points awarded when deal goes live and additional points per upvote received. |
| Deals Feed | Dedicated /deals page + homepage widget showing latest and hottest community deal posts. |


| Post Type | UPC | Example | Notes |
| --- | --- | --- | --- |
| Product Deal | Required | Glock 19 Gen5 at Dunham's for $449 in-store | Links to product page in catalog. Appears in product page comparison table. |
| Bundle Deal | Optional | AR-15 + 1000rds ammo bundle at Academy for $799 | Can link to a primary UPC if one product dominates. Otherwise freeform. |
| Store-Wide Sale | None | Bass Pro Shops — 20% off all handguns this weekend | Freeform. Appears in /deals feed only, not on any product page. |
| Clearance Alert | Optional | Walmart clearance — Ruger 10/22 $199 in-store | Location required. Links to UPC if available. |
| Coupon / Promo Code | None | Brownells code SAVE15 — 15% off all optics | Freeform. Promo code stored separately for one-click copy. |
| Price Error | Required | Obvious price error — Sig P320 listed $299 at PSA | Special badge. Admin can verify and feature these prominently. |


| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| Deal Type | Select | Y | Product / Bundle / Store-Wide Sale / Clearance / Coupon / Price Error |
| Product UPC or Search | Text/Search | Conditional | Required for Product and Price Error types. Search by title or scan UPC. Auto-fills product name and image from catalog. |
| Deal Title | Text(255) | Y | Concise description e.g. 'Glock 19 Gen5 — $449 at Dunham's' |
| Retailer Name | Text(150) | Y | Full retailer name. Auto-suggest from existing retailer list. |
| Online or In-Store | Select | Y | Online / In-Store / Both |
| Store Location | Text(200) | Conditional | Required if In-Store or Both selected |
| Deal Price | Decimal | Conditional | Required for Product and Clearance types |
| Original Price | Decimal | N | For showing savings calculation |
| Deal URL | URL | Conditional | Required for Online deals. Not required for In-Store only. |
| Promo Code | Text(100) | N | If a promo code is required to get this price |
| Shipping Cost | Decimal | N | Leave blank if unknown. Check Free Shipping if applicable. |
| Free Shipping | Checkbox | N |  |
| Description | Textarea | N | Additional context — restrictions, quantity limits, expiry if known |
| Expiry Date | Date | N | Optional — if the deal has a known end date (e.g. sale ends Sunday). Leave blank and deal stays live indefinitely until flagged or manually expired. |
| Source Verification | URL | N | Link proving the deal exists — not shown publicly, used by admin for moderation |


| Event | Points Awarded | Notes |
| --- | --- | --- |
| Deal approved by admin | +5 | Standard member deals only — VIP and admin get no points since no moderation |
| Deal receives upvote | +1 | Per upvote received. Uncapped. |
| Deal receives downvote | -1 | Per downvote received. Cannot go below 0 total reputation. |
| Deal featured by admin | +10 | One-time bonus when admin sets deal to featured status |
| Price error verified | +15 | Admin confirms a Price Error deal is genuine |
| Deal reaches 50 upvotes | +20 | Milestone bonus — one-time per deal |
| Deal manually expired by submitter | 0 | No penalty — submitter is being honest about deal ending |
| Deal flagged expired within 6 hours of posting | -3 | Heavy penalty — signals the deal was never real or grossly inaccurate at time of posting |


| Decision | Selection |
| --- | --- |
| Who Can Create | All registered members. Feature differential between Free and VIP — not a quantity limit. |
| Free Member Features | Unlimited loadouts. Public and Unlisted visibility only. No per-item notes. |
| VIP Member Features | Unlimited loadouts. Public, Unlisted, and Private visibility. Per-item notes (300 chars). Additional swap comparison slots (see 14.5). |
| Visibility Options | Public (discoverable, followable), Unlisted (link only), Private (VIP only — owner only). Default: Unlisted on creation. |
| Pricing Depth | All three: total build lowest price, per-item cheapest dealer breakdown, and item swap comparisons showing total build cost delta. |
| Community | Upvotes, comments, and follows on Public loadouts only. |
| Structure | 10 defined slots + unlimited freeform Extras. |
| Hub | /loadouts browse page with Featured, Trending, Top Rated sections. Featured widget on homepage. |
| Wishlist Integration | Add All to Wishlist button. Price drop alerts reference the loadout name. |
| Compliance | Per-item flags (NFA, state restrictions, FFL required) + build-level compliance summary banner. |


| Feature | Free Member | VIP Member |
| --- | --- | --- |
| Loadout quantity | Unlimited | Unlimited |
| Visibility options | Public and Unlisted | Public, Unlisted, and Private |
| Per-item notes | No | Yes — up to 300 chars per item, visible to owner only |
| Swap comparison slots | Compare 1 alternative at a time | Compare up to 3 alternatives side-by-side |
| Loadout duplication | Yes | Yes |
| One-click fork from forums | No — manual recreate only | Yes — one-click fork with rename prompt, lands in builder |
| Fork attribution | N/A | 'Forked from [original]' shown on forked loadout |
| Community features | Full — upvote, comment, follow | Full — upvote, comment, follow |
| Pricing integration | Full — all three pricing layers | Full — all three pricing layers |
| Compliance summary | Full | Full |
| Wishlist integration | Full | Full |


| Slot | Product Types Accepted | Notes |
| --- | --- | --- |
| Base Firearm | Handguns, Rifles, Shotguns, NFA Firearms | Primary slot — always shown first |
| Optic | Red Dots, Scopes, LPVOs, Prism, Night Vision, Thermal | One item per slot |
| Weapon Light | Weapon Lights | One item per slot |
| Laser / Sight | Lasers | One item per slot |
| Suppressor | Suppressors (NFA) | NFA warning shown prominently when filled |
| Foregrip / Grip | Grips, Foregrips, Vertical Grips | One item per slot |
| Sling | Slings | One item per slot |
| Holster | Holsters and Carry | One item per slot — primarily for handgun builds |
| Ammunition | Any ammunition | Caliber compatibility warning if caliber does not match base firearm |
| Cleaning Kit | Cleaning and Maintenance | One item per slot |
| Extras | Any product in catalog | Unlimited freeform items — user assigns a custom label to each |


| Category | Predefined Slots Available |
| --- | --- |
| Rifle / AR parts | Stock, Barrel, BCG, Handguard, Upper receiver, Lower receiver, Trigger, Buffer tube, Buffer & spring, Charging handle, Gas block, Gas tube, Pistol grip |
| Pistol parts | Slide, Frame, Slide cut / RMR, Compensator, Magazine release, Extended controls, Recoil spring |
| Universal parts | Magazine, Suppressor, Rail mount, Bipod, Rear sight, Front sight, Scope rings, Gun case, Safe storage |
| Shotgun parts | Choke, Magazine extension, Heat shield, Shell holder |
| Custom | User types any slot name — NVG mount, suppressor cover, range bag, etc. No restrictions. |


| Rule | Free Members | VIP Members |
| --- | --- | --- |
| Posts per 24 hours | 1 maximum | 3 maximum |
| Cooldown between posts | 20 minutes — button greyed with countdown timer | 5 minutes — button greyed with countdown timer |
| Minimum items to share | 3 items required | 3 items required |
| Duplicate detection | Warning if >60% item overlap with existing shared loadout from same user | Warning shown — not hard blocked |
| Edit vs repost | If already shared: Share blocked — only Update Forum Thread available | Same — Share blocked, Update allowed |
| 24-hour window | Rolling 24 hours from first post — not midnight reset | Same |


| Decision | Selection |
| --- | --- |
| Forum Destination | Dedicated 'Builds & Loadouts' forum category only. No cross-posting to other categories. |
| Embed Display | Collapsible widget inside the forum post — collapsed by default showing summary card, expands to full build inline |
| Fork Access | VIP members only can one-click fork. Free members see the build and can recreate it manually by adding items one-by-one — no automated copy. Guests see a login prompt. |
| Suggestion System | Both — regular replies AND structured swap suggestions with item search. Suggestions appear as a separate Suggestions tab on the forum post. |
| Forum → Loadout Score | Forum post upvotes and reply count feed into the loadout's platform-wide popularity score |
| Live Prices | Prices in the forum embed update live as dealer feeds change, with a 'prices last updated [timestamp]' note |
| Suggestion Acceptance | Owner can accept or reject each structured suggestion with a one-click response. Accepted suggestions update the actual loadout. |
| Fork Behavior | Fork creates a copy and drops user into the builder to modify it immediately. Prompted to rename before saving. |


| Signal | Weight | Notes |
| --- | --- | --- |
| Forum post upvotes/reactions | High — treated same as direct loadout upvote | IPS post reactions mapped to upvote increment on loadout record |
| Forum reply count | Medium — each reply adds to comment_count on loadout | Indicates discussion depth |
| Suggestion submissions | Medium — each suggestion increments a suggestions_count field | Shows community engagement beyond passive voting |
| Fork count | High — direct intent signal | Each fork increments fork_count which feeds popularity calculation |
| View count | Low — passive signal | Forum thread views increment loadout view_count |


| Component | IPS Mechanism | Custom Work Required |
| --- | --- | --- |
| Point registration | IPS Points system — each plugin registers its own point type | Each plugin (4, 10, 11) registers a named point type on install |
| Point awarding | Plugins call IPS Points API when triggering events | Each plugin awards points through IPS API instead of gd_deal_reputation table |
| Leaderboard display | IPS native leaderboard with tab extension | Custom tab component for each point type + Dealers tab with different data model |
| Time filters | IPS native — This Week / This Month / All Time | No custom work — native |
| Profile integration | IPS native — points shown on member profiles | No custom work — native |
| Badges | IPS native badges triggered by point thresholds | Configure badge triggers in ACP per point type |


| Tab | Label | Data Source | Sort Metric | Who Appears |
| --- | --- | --- | --- | --- |
| 1 | Overall | Sum of all IPS point types | Total points all-time / this month / this week | All members |
| 2 | Deal Hunters | Deal Points — awarded by Plugin 10 | Deal points earned | Members who have posted deals |
| 3 | Reviewers | Review Points — awarded by Plugin 4 | Review points earned | Members who have written reviews |
| 4 | Builders | Loadout Points — awarded by Plugin 11 | Loadout points earned | Members who have created public loadouts |
| 5 | Forum | IPS native post count + reaction score | IPS native reputation | All members |
| 6 | Dealers | Dealer metrics from Plugin 2 — response rate, resolution, freshness | Overall dealer score | Dealers only — different data model, custom tab render |


| Plugin | Point Type Name | Events That Award Points | Points per Event |
| --- | --- | --- | --- |
| Plugin 4 — Reviews | review_points | Review approved (+5), helpful vote received (+1), review featured (+10) | See Plugin 4 Section 7 for full table |
| Plugin 10 — Deals | deal_points | Deal approved (+5), upvote received (+1), deal featured (+10), price error verified (+15), 50-upvote milestone (+20) | See Plugin 10 Section 13 for full table |
| Plugin 11 — Loadouts | loadout_points | Loadout upvote received (+1), loadout forked (+3), loadout featured (+10), 10-upvote milestone (+5) | New — defined here |


| Badge | Trigger | Point Type |
| --- | --- | --- |
| First Deal | First deal_points earned | deal_points |
| Deal Hunter | 50 deal_points lifetime | deal_points |
| Top Deal Poster | Top 10 on Deal Hunters tab — recalculated weekly | deal_points |
| First Review | First review_points earned | review_points |
| Trusted Reviewer | 100 review_points lifetime | review_points |
| First Build | First loadout_points earned | loadout_points |
| Master Builder | Top 10 on Builders tab — recalculated weekly | loadout_points |
| Community Legend | Top 10 on Overall tab — recalculated weekly | All point types combined |
| Trusted Seller | Dealer response rate >90% + star rating >4.5 + min 20 reviews | Dealer metrics — Plugin 2 |
| Ships Fast | Top quartile of listing freshness among dealers | Dealer metrics — Plugin 2 |


| Tab Class File | IPS Extension Point | Registered As |
| --- | --- | --- |
| applications/gunrack/extensions/core/Profile/Deals.php | \IPS\core\Profile\Tab | Deals — shows member's deal post history |
| applications/gunrack/extensions/core/Profile/Reviews.php | \IPS\core\Profile\Tab | Reviews — shows member's product reviews |
| applications/gunrack/extensions/core/Profile/Builds.php | \IPS\core\Profile\Tab | Builds — shows member's loadouts |
| applications/gunrack/extensions/core/Profile/Wishlists.php | \IPS\core\Profile\Tab | Wishlists — shows public/unlisted wishlists |


| Element | Description |
| --- | --- |
| Deal cards | Each approved deal post shown as a card: deal title, retailer, posted date, vote score, comment count, deal type badge (Price Error / Coupon / etc.), current status (active/expired) |
| Sort options | Hottest (by vote score) / Newest / Most commented |
| Stat summary strip | Total deals posted, total upvotes received, total helpful votes, reputation points earned from deals |
| Empty state | If member has no deals: tab hidden entirely from profile — does not show as an empty tab |
| Pagination | IPS native pagination — 20 deals per page |
| Privacy | Only shows approved deals — pending and rejected deals never shown on public profile |


| Element | Description |
| --- | --- |
| Review cards | Product name (linked to price comparison page), star rating, review excerpt (first 200 chars), posted date, helpful vote count, verified purchase badge if applicable |
| Stat summary strip | Total reviews written, average rating given, total helpful votes received, review_points earned |
| Sort options | Newest / Highest rated / Most helpful |
| Empty state | Tab hidden if no approved reviews |
| Pagination | 20 reviews per page — IPS native |


| Element | Description |
| --- | --- |
| Loadout cards | Build name, use case tag, item count, estimated total cost (lowest current prices), upvote count, fork count, visibility badge (Public / Unlisted) |
| Filter | All / Public only — lets visitors filter to only publicly shared builds |
| Stat summary strip | Total builds created, total upvotes received, total forks, loadout_points earned |
| Sort options | Most upvoted / Newest / Lowest cost / Highest cost |
| Empty state | Tab hidden if no public or unlisted builds |
| Pagination | 12 builds per page in a 3-column grid |
| Private builds | Private builds never shown — not even the count. No indication they exist. |


| Element | Description |
| --- | --- |
| Wishlist cards | Wishlist name, item count, last updated date, visibility badge (Public / Unlisted). Clicking opens the wishlist page. |
| Empty state | Tab hidden if member has no public or unlisted wishlists |
| VIP note | Free members: max 1 wishlist. VIP: up to 10. The tab simply shows however many wishlists the member has made public — no tier information shown on the public profile. |
| Pagination | IPS native — 20 wishlists per page |


| Field | Type | Visibility | Purpose |
| --- | --- | --- | --- |
| Home state | Select — US states + DC | Public | Used for state compliance notifications (Plugin 7) and auto-subscribe to state bill alerts. Pre-populated from registration if state is collected. |
| Preferred calibers | Multi-select checklist | Public | Used to personalize deal alerts and the homepage ammo widget. Member selects from a configurable list of calibers in ACP. |
| FFL holder | Checkbox | Public | Member self-identifies as an FFL dealer or SOT — shown as a badge on their profile. Not verified — informational only. |
| GunRack member since | Date — auto-set | Public | Set to the date the member first posts a deal, review, or build — distinguishes active community members from lurkers. IPS join date remains for the full account age. |


| IPS Native Feature | Where It Appears |
| --- | --- |
| Avatar and cover photo upload | Profile header — IPS manages storage, resizing, and default avatars |
| Display name and username | Profile header |
| Join date | Profile header / About tab |
| IPS reputation score | Profile header — driven by IPS reactions system |
| Badges display | Profile header — GunRack badges configured in ACP feed into IPS native badge display |
| About tab — bio, location, website | IPS native About tab — standard profile fields |
| Content tab — all posts and content | IPS native Content tab — shows all forum posts, reactions, etc. |
| Activity tab — recent site activity | IPS native Activity tab |
| Follow / Ignore member | IPS native follow system |
| Private messages | IPS native messenger |
| Report member | IPS native report system |
| Online/offline indicator | IPS native — shown in profile header |
| Profile privacy settings | Member controls who can see each tab — IPS native privacy controls |


| Component | IPS Location | Purpose |
| --- | --- | --- |
| Application manifest | applications/gunrack/Application.php | Registers the application with IPS, declares version, hooks, and homepage capability |
| Front controller | applications/gunrack/modules/front/deals/index.php | Handles the homepage route — fetches widget data and passes to template |
| ACP controller | applications/gunrack/modules/admin/settings/index.php | Renders the full GunRack ACP settings panel described in this section |
| Homepage template | applications/gunrack/dev/html/front/deals/index.phtml | IPS template file — receives widget data from controller, renders layout |
| Widget templates | applications/gunrack/dev/html/front/deals/widgets/ | One .phtml per widget — included by the homepage template based on admin config |
| ACP settings templates | applications/gunrack/dev/html/admin/settings/ | ACP panel UI templates |
| Settings datastore | IPS Settings — IPSSettings::i()->gunrack_* | All GunRack settings stored in IPS's native settings table — no separate config table |
| Language strings | applications/gunrack/dev/lang.php | All user-facing strings — fully IPS-translatable |
| Hooks | applications/gunrack/dev/hooks/ | Any IPS hooks needed for theme integration or member group checks |


| ACP Tab | Scope | What Admin Controls Here |
| --- | --- | --- |
| Homepage | Deals application homepage | Widget layout, widget content, hero settings, layout mode (full-width vs two-column), homepage SEO title and description |
| Appearance | Platform-wide branding | Logo, favicon, color scheme, typography, pre-built theme selection, dark mode toggle, CSS injection field |
| Catalog | Plugin 1 — Master catalog | Distributor feeds, sync schedules, conflict resolution rules, field mapping, compliance flag configuration |
| Dealers | Plugin 2 — Dealer management | Subscription pricing, sync frequencies, approval settings, onboarding configuration |
| Price Comparison | Plugin 3 | Sort defaults, filter defaults, shipping threshold, FFL locator settings, click tracking |
| Reviews | Plugin 4 | Moderation settings, verified purchase rules, image limits, helpfulness voting |
| Rebates | Plugin 5 | Scrape schedule, manufacturer list, approval queue, success rate display |
| Disputes | Plugin 6 | Stage window durations, resolution rules, public thread toggle |
| Power Features | Plugin 7 — VIP | VIP pricing, feature toggles, heat score weights, MAP threshold, alert limits |
| SEO | Plugin 8 | Meta templates, robots.txt, sitemap schedule, schema toggles, canonical rules |
| Email | Plugin 9 | SES credentials, digest schedule, template editor, bounce handling |
| Deal Posts | Plugin 10 | Moderation queue, flag threshold, reputation point values, featured deal slots |
| Loadouts | Plugin 11 | Slot definitions, hub section toggles, swap comparison limits, compliance display |
| Forum Integration | Plugin 12 | Builds category, embed settings, fork gate, suggestion system toggle |
| Leaderboard | Section 16 | Tab configuration, point weights, badge thresholds, time filter defaults |
| Feature Toggles | Platform-wide | Master on/off switches for every major feature — disabled features fully removed from UI |
| Security | Platform-wide | API rate limit settings, extension API toggle, feed URL denylist management |


| Layout Option | Description | When to Use |
| --- | --- | --- |
| Full width — single column | All widgets stack in a single full-width column. No sidebar. Clean, deal-focused layout. | Default recommendation — puts deals front and center |
| Two column — main + sidebar | Main content column (approx 70%) + persistent sidebar (approx 30%). Sidebar widgets always visible. | When you want persistent stats, top dealers, or forum activity always visible |
| Three section — hero + two column below | Large hero/banner section at top, then splits into two columns below. Most visual impact. | When running a promotion or seasonal campaign that needs maximum hero real estate |


| Widget | Column | Default State | Configurable Settings |
| --- | --- | --- | --- |
| Hero Banner | Full width — always top | ON | Headline text (rich text, supports bold/links), subheadline text, primary CTA button label and URL, secondary CTA button label and URL (optional), background: solid color picker / gradient / uploaded image / video URL, overlay opacity slider (0–80%), min height (px), show/hide on mobile toggle |
| Stats Bar | Full width — below hero | ON | Toggle each individual stat: Active dealers / Total products / Total members / Money saved / Total reviews. Label text editable per stat. Show/hide entire bar toggle. |
| Fire Deals Strip | Full width | ON | Number of deal cards: 4 / 6 / 8 / 12. Sort: Heat score (default) / Newest / Manually pinned. Admin pin: drag up to 8 specific products into pinned slots — pinned slots fill first, remainder filled by sort. Card style: compact / standard / large. Show heat badge toggle. Show savings % toggle. |
| Featured Deals Grid | Main column | ON | Grid columns: 2 / 3 / 4. Admin pin up to 12 products as featured. Fallback sort when no pins: Heat score / Newest / Most reviewed. Show product image toggle. Show lowest price toggle. Show heat badge toggle. Section title (editable text). |
| Hot Ammo Deals | Main column | ON | Caliber filter: All / Handgun / Rifle / Shotgun / Rimfire / multi-select. Number of listings shown: 4 / 6 / 8. CPR display: default ON for ammo. In-stock only filter toggle. Section title editable. |
| Community Deals Feed | Main column | ON | Number of deals shown: 4 / 6 / 8. Filter by deal type: All / Price errors / Coupons / In-store. Sort: Hottest / Newest. Show vote count toggle. Show comment count toggle. Section title editable. 'View all deals' link URL (default: /deals). |
| New Rebates | Main column | ON | Number of rebates shown: 3 / 5 / 8. Filter by manufacturer: All or multi-select from registered manufacturers. Sort: Expiring soon / Highest value / Success rate. Show success rate bar toggle. Section title editable. |
| Trending Loadouts | Main column | ON | Number shown: 4 / 6. Time window: 24 hours / 7 days / 30 days. Filter by use case: All / Home defense / CCW / Competition / Hunting / Range. Show estimated cost toggle. Show fork count toggle. Section title editable. |
| Compliance Alerts | Main column | OFF | Number of recent state law alerts shown: 3 / 5. State filter: All states or admin selects specific states to highlight. Show enacted-only or all alerts toggle. Section title editable. |
| Top Dealers | Sidebar | ON | Number shown: 5 / 8 / 10. Sort: Dealer rating / Response time / Listing count / Newest. Show rating stars toggle. Show listing count toggle. Show 'Become a dealer' CTA below list toggle. Section title editable. |
| Forum Activity | Sidebar | ON | Number of recent threads shown: 5 / 8 / 10. Category filter: All forums / Builds & Loadouts only / Custom category ID. Show reply count toggle. Show last activity timestamp toggle. Section title editable. |


| Hero Mode | Description | Best For |
| --- | --- | --- |
| Static — color/image | Fixed background color or uploaded image. Headline, subheadline, and up to two CTA buttons overlaid. | Everyday default — clean and fast loading |
| Rotating banners | Admin uploads 2–5 banner configurations that rotate on a timer (3/5/8 second interval, configurable). Each banner has its own headline, CTA, and background. | Promotions — rotate between 'Fire Deals', 'New Rebates', 'Featured Build of the Week' |
| Video background | YouTube or self-hosted MP4 URL as background. Auto-plays muted, loops. Falls back to poster image on mobile. | High-impact seasonal campaigns — use sparingly |
| Minimal text-only | No background image. Headline and subheadline in large typography. Platform color as background. | When a promotion is purely text-driven — sale announcements, compliance alerts |


| Setting | Type | Description |
| --- | --- | --- |
| Platform Logo | Image upload | SVG or PNG. Shown in header, emails, and browser extension popup. Recommended 200x50px. Separate mobile logo upload (optional). |
| Favicon | Image upload | ICO or PNG. 32x32px. |
| Platform Name | Text | Shown in page titles, meta descriptions, email subjects. Default: GunRack |
| Platform Tagline | Text | Shown on hero banner and About page. Default: The Smarter Way to Buy Firearms |
| About Page Content | Rich text | Full About page — editable from ACP without any template changes |
| Footer Text | Rich text | Copyright line and any footer links — editable from ACP |


| Setting | Type | Description |
| --- | --- | --- |
| Primary Color | Color picker | Main brand color — header background, active nav, primary buttons. Default: #1A3C5E (navy) |
| Accent Color | Color picker | CTA buttons, badges, fire deal labels, heat badges. Default: #C8102E (red) |
| Secondary Accent | Color picker | VIP badges, featured labels, pinned items. Default: #E8A020 (gold) |
| Background Color | Color picker | Page background. Default: #F8F9FA |
| Card Background | Color picker | Product cards, listing rows, widget panels. Default: #FFFFFF |
| Text Primary | Color picker | Headings and body text. Default: #212529 |
| Text Secondary | Color picker | Labels, metadata, timestamps. Default: #6C757D |
| Border Color | Color picker | Card borders and dividers. Default: #DEE2E6 |
| Dark Mode Palette | Toggle + colors | Enable user-selectable dark mode. Separate color picker set for dark mode variants of each color above. |
| Heat Badge Colors | 4x color pickers | Individual color pickers for Cold / Warm / Hot / Fire badge backgrounds and text. Default palette pre-set. |


| Setting | Type | Description |
| --- | --- | --- |
| Heading Font | Google Fonts selector | Font for all H1-H4 headings across the platform. Default: Inter |
| Body Font | Google Fonts selector | Font for body text, labels, and UI elements. Default: Inter |
| Monospace Font | Google Fonts selector | Used for price displays and UPC/code fields. Default: JetBrains Mono |
| Base Font Size | Number (px) | Base size for body text — all other sizes scale from this. Default: 14px |
| Heading Scale | Select | Modular scale ratio for H1-H4 sizes. Options: 1.25 / 1.333 / 1.5. Default: 1.25 |
| Font Weight — Headings | Select | 400 / 500 / 600 / 700. Default: 600 |
| Font Weight — Body | Select | 300 / 400 / 500. Default: 400 |


| Theme | Visual Style | Color Defaults |
| --- | --- | --- |
| GunRack Dark | Dark navy background, high contrast, red accent — tactical feel | Primary: #0D1B2A, Accent: #C8102E, Background: #111827, Card: #1F2937 |
| GunRack Light | Clean white/grey, navy accent — professional marketplace feel | Primary: #1A3C5E, Accent: #C8102E, Background: #F8F9FA, Card: #FFFFFF |
| GunRack Minimal | Ultra-clean, monochrome, single accent — content-forward | Primary: #212529, Accent: #2563EB, Background: #FFFFFF, Card: #F9FAFB |


| Feature Toggle | Default | Effect When Disabled |
| --- | --- | --- |
| Rebates System | ON | /rebates returns 404. Rebates tab removed from nav and product pages. Rebate widget removed from homepage. |
| Dealer Review Disputes | ON | Dispute button removed from reviews. Existing dispute threads remain visible. |
| Heat Score Badges | ON | All heat badges hidden across product pages, homepage, and feeds. Score still calculated internally for sorting. |
| Community Deal Posts | ON | Deal submission disabled. /deals returns dealer-sourced feed only. Community deals widget removed from homepage. |
| Loadout Builder | ON | /loadouts returns 404. Builder hidden. Homepage trending loadouts widget removed. |
| Forum Loadout Integration | ON | Share to Forums button hidden. Existing forum embeds show static text fallback. |
| Browser Extension API | ON | /api/v1/extension/* endpoints return 503. Extension shows 'Service unavailable'. |
| VIP Membership | ON | VIP subscription product hidden in IPS Commerce. Existing VIP members retain access until expiry. |
| Compliance Notifications | ON | Bill tracker integration paused. Compliance alerts widget removed from homepage. |
| MAP Violation Tracker | ON | Below MAP badges hidden. /deals/below-map returns 404. MAP watcher removed from homepage widgets. |
| Dark Mode | OFF | Dark mode user toggle hidden. Platform renders in light mode only. |
| Maintenance Mode | OFF | All public pages show configurable maintenance message. ACP and dealer dashboard remain accessible. |


| Plugin | Most Commonly Adjusted Settings |
| --- | --- |
| Plugin 1 — Catalog | Distributor priority order, conflict resolution rules for images/descriptions/MSRP, compliance field detection per distributor |
| Plugin 2 — Dealers | Subscription tier pricing, founding dealer trial period, auto-approve toggle, sync frequency per tier |
| Plugin 3 — Price Compare | Default sort order, free shipping threshold, CPR default, FFL locator radius, price history days per tier |
| Plugin 4 — Reviews | Moderation queue on/off, verified purchase badge rules, review report threshold |
| Plugin 5 — Rebates | Scrape schedule, success rate display threshold, max featured rebates |
| Plugin 6 — Disputes | Stage window durations (14-day default), public thread toggle, no-response label threshold |
| Plugin 7 — VIP | VIP monthly and annual pricing, heat score weights (must total 100%), fire badge threshold, early alert delay minutes |
| Plugin 8 — SEO | Meta title and description templates per page type, sitemap regeneration schedule, schema type toggles |
| Plugin 9 — Email | SES credentials, weekly digest send day and time, admin pin slots, template editor |
| Plugin 10 — Deal Posts | Flag threshold for auto-expiry, moderation queue alert hours, reputation point values per action |
| Plugin 11 — Loadouts | Budget threshold for budget builds section, featured loadout slots, use case tag list |
| Plugin 12 — Forum | Builds & Loadouts IPS category ID, loadout embed display mode, fork rate limit per tier |
| Leaderboard | Point type weights for overall ranking, badge threshold values, time filter defaults |


| Step | Name | What Happens | Skippable |
| --- | --- | --- | --- |
| 1 | Account Setup | Dealer completes business profile: business name, address, phone, website, FFL license number, contact name. Profile photo/logo upload. These fields populate the public dealer profile page. | Yes |
| 2 | Business Verification | Dealer uploads FFL license document. Admin reviews and approves within 24 hours. Dealer proceeds to next steps while verification is pending — listings go live only after verification approved. | No — FFL verification is required before listings go live |
| 3 | Subscription Selection | Dealer selects tier: Basic ($149/mo), Pro ($399/mo), or Enterprise ($799/mo). Side-by-side feature comparison shown. IPS Commerce checkout flow. Annual discount shown. | No — subscription required to proceed |
| 4 | Feed Submission | Dealer enters feed URL and selects format (XML/JSON/CSV). Alternatively: manual CSV upload option for dealers without a live feed endpoint. Credential fields if feed requires authentication. | Yes — dealer can manually add listings later |
| 5 | Field Mapping Review | System runs a sample pull from the feed and maps detected fields to platform fields. Dealer reviews the mapping and confirms or corrects: UPC column, price column, stock column, description column. Side-by-side preview of first 5 feed records. | Yes |
| 6 | Test Import | System imports first 25 records from the feed. Dealer sees a preview of how their listings will appear on the platform: product name, image (from master catalog), price, shipping cost, estimated heat badge. Error report shown if any records failed — with specific error messages. | Yes |
| 7 | Go Live | Dealer reviews summary: X listings imported, X matched to catalog, X unmatched (review queue), sync schedule, first sync time. Clicks 'Go Live' to activate listings. Confetti moment — 'Your store is live on GunRack!' | No — this is the completion step |


| Platform Field | Required | Auto-detect Pattern | Dealer Action if Wrong |
| --- | --- | --- | --- |
| UPC | Yes | Columns named: upc, ean, barcode, product_id | Select correct column from dropdown |
| Price | Yes | Columns named: price, cost, retail_price, our_price | Select correct column |
| Stock Quantity | Yes | Columns named: qty, quantity, stock, inventory | Select correct column or toggle 'In stock flag only' |
| In Stock Flag | Alt to qty | Columns named: in_stock, available, status | Toggle between quantity or boolean flag |
| Shipping Cost | No | Columns named: shipping, ship_cost, freight | Select or mark as 'Not in feed' |
| Free Shipping Flag | No | Columns named: free_shipping, free_ship | Toggle |
| Description | No | Columns named: description, desc, product_desc | Select or mark as 'Not in feed — use catalog description' |
| Image URL | No | Columns named: image, image_url, photo | Select or mark as 'Not in feed — use catalog image' |
| MAP Price | No | Columns named: map, map_price, minimum_price | Select or mark as 'Not in feed' |
| MSRP | No | Columns named: msrp, retail, suggested_retail | Select or mark as 'Not in feed' |


| Dashboard Section | Key Metrics and Controls |
| --- | --- |
| Overview | Total active listings, total clicks (30 days), price competitiveness score, subscription tier and next billing date, wizard completion status |
| Listings | Full list of active/inactive listings with price, stock, heat badge, click count, last synced. Filter by status, search by UPC or product name. Manual price override per listing. |
| Feed Settings | Feed URL, format, auth credentials, sync schedule, last sync timestamp, sync now button, field mapping review link |
| Analytics | Click-through by product, clicks by day chart, most-clicked listings, price competitiveness vs other dealers on same UPCs |
| Unmatched Queue | UPCs from feed with no catalog match. For each: search catalog to manually match, flag as discontinued, or submit to admin for catalog addition |
| Reviews | All reviews received, star rating breakdown, dispute management, response interface |
| Subscription | Current tier, billing date, upgrade/downgrade options, payment method management, invoice history |
| Profile | All Step 1 fields editable. Logo, description, policies. Preview dealer profile page as buyers see it. |
| Notifications | Configure which email notifications to receive: feed errors, unmatched UPC alerts, review received, dispute updates, FFL expiry |
| Support | Link to /dealers/help, contact support form, current open tickets |


| Feature | Priority | Notes |
| --- | --- | --- |
| Price comparison and browse | P1 — core | Full product catalog, price table, dealer comparison, heat badges |
| Push notifications for alerts | P1 — core | Price drop alerts, fire deal notifications, back-in-stock alerts. Native push replaces email for mobile users. |
| Loadout builder — view and share | P1 — core | Browse and share builds. Full builder (slot editing) is P2 within Phase 2 — complex UI. |
| Barcode scanner | P1 — core | Point camera at firearm barcode in store — instantly shows online prices. Key differentiator vs web. |
| Deals feed | P1 — core | Community deals feed with voting. Deal submission P2 within Phase 2. |
| Watchlist and wishlists | P1 — core | View and manage existing watchlists and wishlists from phone. |
| Full loadout builder UI | P2 within Phase 2 | Slot picker, add items, swap suggestions — complex touch UI, spec separately before building. |
| Deal post submission | P2 within Phase 2 | Photo upload, deal type selection, UPC scanning pre-fills form. |
| Forum access | P3 — deprioritize | IPS has a mobile skin already. Native forum is a large build for modest gain. |


| API Tier | Monthly Price | Request Limit | Endpoints Included |
| --- | --- | --- | --- |
| Hobbyist | Free | 1,000 req/day | Product lookup by UPC, lowest price, basic product data |
| Developer | $49/mo | 50,000 req/day | All Hobbyist + price history, heat score, dealer list, in-stock status |
| Commercial | $299/mo | 500,000 req/day | All Developer + bulk UPC lookup, webhooks for price change events, compliance flags |
| Enterprise | Custom | Unlimited | All Commercial + dedicated support, SLA, raw feed access, custom endpoints |


| Item | Estimated Build Time | Revenue Impact | Priority |
| --- | --- | --- | --- |
| Mobile app — P1 features | 3–4 months | High — push notifications drive alert conversions and VIP upgrades | P1 |
| Barcode scanner | 1 week | High — key differentiator, drives app downloads | P1 — build with mobile app |
| Public API — free + paid | 3–5 weeks | Medium — recurring API subscription revenue, builds ecosystem | P2 |
| FFL transfer cost tracker | 2–3 weeks | Medium — unique data asset, improves product page completeness | P2 |
| Dealer inventory widget | 2–3 weeks | Medium — improves dealer retention, upsell to Pro/Enterprise | P3 |
| Sold/unavailable confirm | 1–2 weeks | Low — community quality of life, reduces stale listings | P3 |
| Social proof counter | 3–5 days | Low — conversion optimization, easy build | P3 — low effort |
| Dealer response time | 1–2 weeks | Low — leaderboard refinement | P4 |


| Thread Type | Frequency | Content Source | Suggested Subforum |
| --- | --- | --- | --- |
| Deal of the Day | Daily — 8am | Top Fire-badge deal from previous 24 hours. Include price history chart, dealer, and an open question: 'Anyone picked this up?' | Deals & Steals |
| Weekly Price Watch | Sunday — 9am | Top 10 biggest price drops of the past 7 days across all categories. Sorted by % drop. | Deals & Steals |
| New to the Catalog | As triggered | Auto-post when a new product is added to the master catalog from distributor feeds. Route to relevant subforum by category. | Relevant category |
| Weekly Build Challenge | Monday — 9am | Admin-configured challenge prompt e.g. 'Best budget AR-15 build under $600'. Community submits loadouts as replies. Community votes all week. Winner announced Sunday. | Builds & Loadouts |
| Compliance Alert | As triggered | Auto-post when Firearms Bill Tracker marks a bill as enacted or introduced. Route to state subforum. | Laws & Compliance |


| IP Address | Purpose | Domain | Services |
| --- | --- | --- | --- |
| IPv4 #1 (primary) | Web server — IPS application | gunrack.deals + www.gunrack.deals | Nginx → PHP-FPM → IPS + MySQL |
| IPv4 #2 (secondary) | Search server — OpenSearch | search.gunrack.deals | Nginx reverse proxy → OpenSearch port 9200 |


| Check | Command / Action | Expected Result |
| --- | --- | --- |
| gunrack.deals loads over HTTPS | Visit https://gunrack.deals in browser | IPS installation page or homepage — green padlock in browser |
| www redirect works | Visit http://www.gunrack.deals | Redirects to https://gunrack.deals |
| search subdomain resolves | Visit https://search.gunrack.deals | OpenSearch JSON response (not a connection error) |
| OpenSearch cluster health | curl https://search.gunrack.deals/_cluster/health | JSON with status: green or yellow |
| OpenSearch blocked externally | From a different computer: curl http://YY.YY.YY.YY:9200 | Connection refused — port 9200 not accessible from internet |
| MySQL running | systemctl status mysql | Active: active (running) |
| Redis running | redis-cli ping | PONG |
| IPS Redis connection | IPS ACP → System → Advanced → Redis → Test Connection | Connection successful |
| IPS OpenSearch connection | IPS ACP → System → Search → Test Connection | Connection successful |
| Firewall active | ufw status | Status: active — all rules listed |
| RAM usage at idle | free -h | Used should be 6-8GB at idle with all services running |
| Disk usage | df -h / | Used should be under 20GB at this point |
| Backups configured | DirectAdmin → Admin Backup → check schedule | Schedule showing next backup time |


| Task | Command |
| --- | --- |
| Restart Nginx | systemctl restart nginx |
| Restart PHP-FPM | systemctl restart php8.1-fpm |
| Restart MySQL | systemctl restart mysql |
| Restart OpenSearch | systemctl restart opensearch |
| Restart Redis | systemctl restart redis-server |
| Check OpenSearch logs | journalctl -u opensearch -n 100 --no-pager |
| Check Nginx error log | tail -100 /var/log/nginx/error.log |
| Check MySQL slow query log | tail -100 /var/log/mysql/mysql-slow.log |
| Check disk space | df -h |
| Check RAM usage | free -h |
| Check what is using RAM | ps aux --sort=-%mem ‖ head -20 |
| Check OpenSearch cluster health | curl http://localhost:9200/_cluster/health?pretty |
| Rebuild OpenSearch index (IPS) | IPS ACP → System → Search → Rebuild Search Index |
| Clear IPS cache | IPS ACP → Support → Clear System Cache |
| Check UFW firewall status | ufw status verbose |
| View scheduled cron jobs | crontab -l |


| Requirement | Implementation |
| --- | --- |
| Row-level locking | All feed ingestion jobs must use SELECT ... FOR UPDATE on the catalog row for the UPC being processed before writing any fields. This serializes concurrent writes to the same UPC. |
| OR — serial queue | Alternative: Plugin 1 and Plugin 2 jobs are queued through a single job queue and never allowed to process the same UPC simultaneously. Implement using IPS's built-in task queue with a UPC-keyed mutex. |
| Conflict log atomicity | The gd_feed_conflicts write and the field update it corresponds to must occur in the same database transaction. If the transaction fails, neither the conflict record nor the field update is written. |


| Requirement | Implementation |
| --- | --- |
| Optimistic access grant | On confirmed Stripe payment webhook: immediately write a pending_vip flag to the member record before the IPS background task runs. Plugin 7 feature gates check this flag in addition to the IPS member group. |
| Pending state UI | Show a 'VIP activating — refresh in a moment' message instead of the standard upgrade prompt if payment is confirmed but group not yet updated. |
| Flag cleanup | Once IPS background task runs and group is confirmed updated, remove the pending_vip flag — the member group becomes the single source of truth. |


| Requirement | Implementation |
| --- | --- |
| Reconciliation job | A nightly scheduled task compares MySQL catalog record count to OpenSearch document count per distributor. If they diverge by more than 1%, admin receives an alert email with the count discrepancy. |
| Indexing retry | Failed OpenSearch indexing jobs must be retried up to 3 times with exponential backoff before being marked as failed. A failed indexing job must write to an error log visible in ACP. |
| Manual reindex button | ACP → Plugin 8 Settings → OpenSearch → Rebuild Index triggers a full reindex from MySQL. Admin can use this to manually recover from a sync failure without server access. |


| # | Item | Verified By | Date |
| --- | --- | --- | --- |
| 1 | Redis bound to 127.0.0.1 and requirepass set in redis.conf |  |  |
| 2 | libxml_disable_entity_loader(true) present before every XML parse in Plugins 1 and 2 |  |  |
| 3 | SSRF prevention: dealer feed URL validated against private IP denylist before fetch |  |  |
| 4 | CSRF token validation on every state-changing front-end action across all 12 plugins |  |  |
| 5 | SQL injection audit: all DB queries use parameterized calls — no string concatenation |  |  |
| 6 | XSS audit: all user-generated output sanitized — deal posts, reviews, loadouts, swap suggestions |  |  |
| 7 | Nginx rate limit on /api/v1/extension/lookup: 60 req/min per IP |  |  |
| 8 | MySQL gunrack_ips user: SELECT/INSERT/UPDATE/DELETE only — no GRANT/FILE/SUPER |  |  |
| 9 | Backup encryption: GPG encrypt before B2 upload — test restore from encrypted backup |  |  |
| 10 | DMARC DNS record published at p=quarantine — verify with MXToolbox |  |  |
| 11 | SES credentials in environment variable — not in source code, not in Git |  |  |
| 12 | DirectAdmin access restricted to admin IP — not publicly accessible |  |  |
| 13 | OpenSearch index mapping includes dynamic: strict |  |  |
| 14 | Email enumeration: password reset and registration return identical messages for known/unknown emails |  |  |
| 15 | FFL document upload: MIME validation via finfo_file(), stored outside webroot, random filename |  |  |
| 16 | Stripe webhook signature validation in any custom Stripe event handlers |  |  |
| 17 | Feed ingestion race condition: UPC row locking or serial queue implemented and tested |  |  |
| 18 | VIP Commerce group delay: optimistic pending_vip flag implemented |  |  |
| 19 | OpenSearch reconciliation job scheduled nightly — test by manually creating a discrepancy |  |  |
| 20 | Privilege escalation test: attempt to POST invalid group ID during dealer onboarding — confirm rejected |  |  |
