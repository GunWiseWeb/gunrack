# GunRack.deals — Project CLAUDE.md

## What this project is
GunRack (gunrack.deals) is a firearms price comparison and community platform built on IPS Community Suite. It competes with gun.deals. Full technical specification is in `GunRack_Spec_v2.9.16.md` in this repo root — read that file first before writing any code.

## Critical rules — read before touching anything
1. **Never start on Plugin 2 or Plugin 3 before Plugin 1 has completed two full successful import cycles across all six distributors.** This is a hard gate in the spec.
2. **All SQL queries must use IPS parameterized queries — no string interpolation of user input into queries ever.**
3. **Redis must be bound to 127.0.0.1 with requirepass set before the server goes live.** See Appendix C.
4. **libxml_disable_entity_loader(true) must appear before every XML parse in feed ingestion.** See Appendix C.
5. **All 12 plugins must register CSRF token validation on every state-changing front-end action.** See Appendix C.
6. **Never store SES credentials or API keys in source code or commit them to Git.**

## Tech stack
- **Platform:** IPS Community Suite (self-hosted)
- **Server:** Standard NVMe — 4 Core, 10GB RAM, 140GB NVMe, 2x IPv4
- **Search:** OpenSearch self-hosted on search.gunrack.deals (Nginx reverse proxy, IP allowlisted)
- **Email:** Amazon SES with dedicated IP
- **Cache:** Redis (localhost only)
- **Payments:** IPS Commerce + Stripe
- **Domain:** gunrack.deals (IPv4 #1 → gunrack.deals, IPv4 #2 → search.gunrack.deals)

## Plugin build order (do not deviate)
| Phase | Weeks | What |
|---|---|---|
| 1 | 1–4 | Server setup → IPS install → Plugin 1 (Master Catalog) |
| 2 | 5–8 | Plugin 2 (Dealers) + Plugin 3 (Price Compare) in parallel |
| 3 | 9–10 | Plugin 4 (Reviews) + Plugin 5 (Rebates) |
| 4 | 11–12 | Plugin 6 (Disputes) + Plugin 7 (Power Features / VIP) |
| 5 | 13–14 | Plugin 8 (SEO) + Plugin 9 (Email) |
| 6 | 15–16 | Plugin 10 (Deal Posts) + Plugin 11 (Loadouts) |
| 7 | 17–18 | Plugin 12 (Forum Integration) + Section 16 (Leaderboard) |
| 8 | 19–20 | Section 17 (Homepage App) + Section 18 (Dealer Onboarding) |
| 9 | 21–22 | QA, security checklist (Appendix C), launch prep |

## Six distributors (Plugin 1)
Sports South · RSR Group (conflict resolution priority #1) · Zanders · Davidson's · Lipsey's · Bill Hicks

## Key database tables (quick reference)
- `gd_catalog` — master product catalog keyed by UPC
- `gd_dealer_listings` — per-dealer pricing and stock
- `gd_price_history` — price snapshots for heat scoring and history charts
- `gd_compliance_flags` — state restrictions and compliance data (source = distributor|admin_manual|admin_override)
- `gd_feed_conflicts` — incoming feed values that conflict with database (status = pending|accepted|kept|custom|auto_accepted)
- `gd_field_locks` — fields locked against distributor overwrites (lock_type = distributor_specific|hard)
- `gd_loadouts` — community loadout builds
- `gd_loadout_items` — items within a loadout (slot-based)
- `gd_loadout_forum_posts` — tracks forum shares for rate limiting
- `gd_feed_conflicts` — auto-resolves after 48 hours if admin doesn't act
- `gd_deal_posts` — community deal submissions
- `gd_rebates` — manufacturer rebates

## IPS extensions used (not custom pages)
- **Homepage:** GunRack Deals registered as IPS application, set as default homepage via ACP → System → Site Promotion → Default Application
- **Member profiles:** Four IPS profile tab extensions (Deals, Reviews, Builds, Wishlists) — NO new profile page
- **Leaderboard:** IPS native leaderboard + Points system + custom tab extensions for dealer metrics
- **Member groups:** Managed via IPS Commerce group promotions (not manually)

## Security checklist location
Full 20-item pre-launch security checklist is in **Appendix C Section C.8** of the spec. Nothing goes live until every item is checked.

## Pre-development actions (start these immediately, parallel with dev)
1. RSR Group — apply for technology/data partner feed access (blocks Plugin 1)
2. Sports South — same (blocks Plugin 1 catalog completeness)  
3. Amazon SES setup + SPF/DKIM/DMARC DNS records
4. IPS Community Suite license purchase
5. Chrome Web Store + Firefox AMO developer accounts (extension takes 1–7 days to review)
6. 2–3 founding FFL dealers confirmed for free 90-day trial (needed to test Plugin 2)

## IPS v5 third-party application requirements
These were learned by comparing against a working IPS v5 plugin. They apply to every application in this project.

1. **Application.php needs BOTH classes** — `class _Application extends \IPS\Application` AND `class Application extends _Application {}` below it. Both are required: the underscore class is what IPS resolves via its autoloader; the non-underscore alias is required for PHP to locate the class when instantiated normally.
2. **Controllers need BOTH classes** — e.g. `class _dashboard extends \IPS\Dispatcher\Controller` AND `class dashboard extends _dashboard {}` below it. Both are required for every controller in `modules/`.
3. **`execute()` MUST have `: void` return type** — the parent `\IPS\Dispatcher\Controller::execute()` signature requires it. Omitting it causes a fatal error.
4. **Templates are seeded via `setup/install.php`, not `data/theme.xml`** — IPS's XML import corrupts `{{-- comments --}}` to bare `-- comments --` and breaks template eval. Workaround: insert templates directly into `core_theme_templates` via `\IPS\Db::i()->insert()` in `setup/install.php`, using nowdoc heredocs (`<<<'TEMPLATE_EOT'`) to preserve comment syntax literally. Required fields: `template_set_id=1`, `template_app`, `template_location='admin'`, `template_group='catalog'`, `template_name`, `template_data` (parameter list), `template_content`. Do NOT use `data/theme.xml`. Controllers call `\IPS\Theme::i()->getTemplate( 'catalog', 'gdcatalog', 'admin' )->templateName(...)` once the install has seeded the DB.
5. **Language strings go in `data/lang.xml`, not `dev/lang.php`** — IPS installs language strings from this XML file. Format: `<language><app key="appdir"><word key="...">` with CDATA values. The `dev/lang.php` file is for IN_DEV mode only.
6. **Tar must be packaged with files at root level — no parent folder** — `Application.php` must be the first entry at the tar root, not inside `gdcatalog/`. Paths are `Application.php`, `data/theme.xml`, `modules/admin/catalog/dashboard.php`, etc. Use PharData `addFromString()` (not `addFile()` which produces 0-byte files). Every directory must contain a blank `index.html`.
7. **ActiveRecord property types — exact declarations, copy verbatim** — every model that extends `\IPS\Patterns\ActiveRecord` must declare these three static properties with EXACTLY these visibilities and types. Any deviation (adding `?` where it doesn't belong, dropping `?` where it does, wrong visibility) is a fatal type-variance error against the parent class and will white-screen the ACP on autoload:

    ```php
    public static ?string $databaseTable   = 'table_name';  // nullable — ?string
    public static string  $databaseColumnId = 'id';         // NOT nullable — string
    public static string  $databasePrefix   = '';           // NOT nullable — string (empty string for no prefix)
    ```

    Rules:
    - `$databaseTable` is the **only** one that is nullable (`?string`). Parent declares it nullable; omitting the `?` errors.
    - `$databaseColumnId` is **never** nullable. Every ActiveRecord has a primary key column, so `string` is the only correct type. Do not write `?string`.
    - `$databasePrefix` is **never** nullable. If there is no prefix, use `''` (empty string), not `null`. Do not write `?string`.
    - All three are `public static`. Never change visibility.
    - Copy the three lines above verbatim into each new model file and change only the string values.
8. **Dashboard controllers must NOT make live OpenSearch calls** — `OpenSearchIndexer::i()->getStats()` and `->indexExists()` perform synchronous HTTP requests that hang the ACP page indefinitely when the cluster is slow or unreachable. On the dashboard `manage()` method set `$osExists = FALSE` and `$osStats = []` as hardcoded values, and move all real index work into the dedicated `rebuildIndex` / `processQueue` actions the admin triggers explicitly. Every DB query on the dashboard must be wrapped in its own `try { ... } catch ( \Exception ) {}` block so one missing table cannot break the whole page.
9. **IPS templates have no comment syntax** — `{{-- comment --}}` is not parsed by the IPS template compiler and HTML `<!-- -->` gets rendered to the page. Do not put any comments inside templates seeded via `setup/install.php`. Use PHP nowdoc heredocs (`<<<'TEMPLATE_EOT'`) for the `template_content` column so real newlines/tabs are preserved; verify with `SELECT HEX(SUBSTRING(template_content,1,50))` that the first bytes include `0A`/`09`, not `5C6E`/`5C74`.
10. **`Application.php` must be the first entry in the tar file** — IPS's installer inspects the very first tar header to identify the application and will reject or misinstall the package if anything else precedes it (including `data/`, `dev/`, or a stray `index.html`). The build command must explicitly list `Application.php` first before any directories, e.g. `tar -cf gdcatalog-v1.0.0.tar Application.php data/ modules/ sources/ tasks/ setup/ dev/ index.html`. When building via `PharData::addFromString()`, add `Application.php` in the very first call before iterating directories. Verify with `tar -tf gdcatalog-v1.0.0.tar | head -1` — the first line must be `Application.php`.
11. **Application `get__icon()` must be `public` with `: string` return type** — the parent `\IPS\Application::get__icon()` is declared `public function get__icon(): string`. Child overrides must match exactly: `public function get__icon(): string { return 'database'; }`. A `protected` override with a `public` parent triggers a fatal LSP-visibility error that white-screens the ACP; omitting the return type triggers a fatal signature-mismatch error. IPS core apps (forums, blog, etc.) all use this exact pattern.
12. **IPS template syntax — only these proven safe patterns** — anything outside this list risks an `UnexpectedValueException` at compile time. Keep templates as dumb as possible; move all logic to the controller.
    1. `{$variable}` — simple variable output. Do not mix subscripts with arrow access (`{$ds['feed']->priority}` is illegal — flatten to scalars in the controller).
    2. `{expression="php_expression"}` — arbitrary PHP. Use this for `number_format(...)`, `htmlspecialchars(...)`, etc.
    3. `{{if condition}}...{{else}}...{{endif}}` — conditions must be simple (`$x`, `$x === 'foo'`, `count($x) > 0`). Avoid nested function calls inside the condition.
    4. `{{foreach $array as $item}}...{{endforeach}}` — the loop source must be a plain array variable, never an object-property chain.
    5. `{lang="key"}` — language strings.
    6. **Never** nest a `{url="..."}` tag inside an `{{if}}` block that depends on per-row data; the tokenizer evaluates tag arguments in a single pass and will break on the inner `{$...}`.
    7. **Never** use `->` object access inside a `{url=...}`, `{lang=...}`, or `{expression=...}` tag parameter where it sits next to an array subscript.
    8. For links that need dynamic IDs, build the full URL in the controller with `\IPS\Http\Url::internal(...)->csrf()` cast to string, and pass it as a scalar template variable (e.g. `$ds['run_import_url']`). The template then renders `<a href="{$ds['run_import_url']}">`.
    9. If you reach for any syntax not in this list, stop and push the logic back into the controller instead.
13. **Never use anonymous functions, closures, or `array_filter`/`array_map`/`array_walk`/`usort` with callables inside `{expression="..."}` template tags** — the IPS template compiler cannot tokenize PHP closures (`function( $f ) { ... }` or `fn( $f ) => ...`) inside expression tag arguments and throws `UnexpectedValueException` or silently emits broken PHP. Safe expressions are flat calls only: `number_format($x)`, `htmlspecialchars($x)`, `count($array)`, `strtoupper($x)`, `$x ? 'a' : 'b'`. Anything requiring a callback — counting filtered items, transforming a list, sorting — must be computed in the controller and passed as a pre-built scalar (e.g. `$activeFeedCount`, `$configuredUrlCount`) that the template prints directly via `{$activeFeedCount}`.
14. **ACP sidebar tab icons are set via a language key in `lang.xml`, NOT via `get__icon()`** — in IPS v5 the left-sidebar tab glyph is driven by the language string `menutab__{app_directory}_icon`; `Application::get__icon()` does not control it. The value is a FontAwesome icon name with no `fa-` prefix (e.g. `database`, `shield`, `tag`, `users`, `chart-bar`). Example for gdcatalog: `<word key="menutab__gdcatalog_icon"><![CDATA[database]]></word>`. Every future plugin must include this key in `lang.xml` or the tab will render with no icon. `get__icon()` must still exist on the Application class with the `: string` return type (see Rule #11) — other parts of IPS read it — but it does not determine the ACP sidebar icon.
15. **`data/tasks.json` uses ISO 8601 duration strings only — never cron syntax** — IPS v5's task scheduler parses task intervals as ISO 8601 durations (`PT15M`, `PT1H`, `PT24H`, `P7D`, `P30D`). A cron-style value like `"0 2 * * *"` or `"*/15 * * * *"` will not register — the task is silently dropped at install time and never fires. The keys of `tasks.json` are the task identifiers (matching the class name in `tasks/{name}.php`); the values are the interval strings. Reference values:
    - `PT5M` — every 5 minutes
    - `PT15M` — every 15 minutes
    - `PT1H` — hourly
    - `PT6H` — every 6 hours
    - `PT24H` — daily (use `PT24H`, not `P1D`, for consistency with IPS core)
    - `P7D` — weekly
    - `P30D` — monthly
    Example for a daily click aggregation plus a 15-minute alert dispatcher plus a monthly FFL refresh:

    ```json
    {
        "aggregateClicks": "PT24H",
        "dispatchWatchlistAlerts": "PT15M",
        "refreshFflData": "P30D"
    }
    ```

    IPS does not support cron expressions at any level of the task configuration — not in `tasks.json`, not in the ACP task editor. Scheduling specific times-of-day (e.g. "run at 2am daily") is not expressible in this format; either accept the first-run time as the daily anchor or compute time-of-day logic inside the task's `execute()` method and skip runs that fall outside the window.
16. **Every ACP controller must declare `public static bool $csrfProtected = TRUE;`** — IPS v5 requires this static property on `\IPS\Dispatcher\Controller` subclasses that run in the admin dispatcher. Omitting it causes `CSRF check failed` errors on every ACP page load (not just state-changing actions) because the dispatcher's pre-execute hook refuses to dispatch controllers that haven't opted into CSRF handling. The property declaration is the opt-in; it does NOT skip CSRF checks. Declared CSRF token verification still happens inside action methods via `\IPS\Session::i()->csrfCheck()` for POST bodies and via `->csrf()` on link URLs for GET actions. Place the property immediately after the `class _controllerName extends \IPS\Dispatcher\Controller {` line:

    ```php
    class _dashboard extends \IPS\Dispatcher\Controller
    {
        public static bool $csrfProtected = TRUE;

        public function execute(): void { ... }
    }
    ```

    This applies to every admin controller in every plugin — `gdcatalog`, `gddealer`, `gdpricecompare`, and the nine still-to-be-built plugins. Front-end controllers (`location=front`) do not need it because the front dispatcher handles CSRF differently. Audit command: `grep -L 'csrfProtected' applications/*/modules/admin/**/*.php` must return empty.
17. **Every plugin must ship `data/acprestrictions.json` declaring every ACP permission key referenced elsewhere** — IPS v5 resolves the `restriction` value from `data/acpmenu.json` and the argument to `\IPS\Dispatcher::i()->checkAcpPermission()` against the app's registered restrictions. If the key is not declared in `acprestrictions.json`, the dispatcher rejects the request with a generic "CSRF check failed" error on every ACP page load — even though the real problem is unknown-permission, not CSRF. The file must exist even when using a single unified permission across all admin controllers.

    Format — `{ module: { controller: { permission_key: permission_lang_key } } }`:

    ```json
    {
        "pricecompare": {
            "dashboard":   { "pricecompare_manage": "pricecompare_manage" },
            "settings":    { "pricecompare_manage": "pricecompare_manage" },
            "searchlog":   { "pricecompare_manage": "pricecompare_manage" },
            "ffldata":     { "pricecompare_manage": "pricecompare_manage" },
            "compliance":  { "pricecompare_manage": "pricecompare_manage" }
        }
    }
    ```

    The outer keys are module directory names (matching `modules/admin/{module}/`). Each inner key is a controller file name (without `.php`). Each innermost entry maps a permission key (used in `checkAcpPermission()` and the `restriction` value in `acpmenu.json`) to a language string key (which the ACP permission-editor shows as the human-readable label — typically `r__{permission_key}` in `lang.xml`).

    Consistency requirements across three files:
    - `data/acprestrictions.json` — declares the permission keys.
    - `data/acpmenu.json` — every entry's `"restriction"` value must appear as a permission key in `acprestrictions.json`.
    - Every admin controller's `checkAcpPermission( '...' )` argument must appear as a permission key in `acprestrictions.json`.
    - `data/lang.xml` — must define `r__{permission_key}` for every permission key (shown in the ACP permission editor).

    Audit command: `jq -r 'to_entries[].value | to_entries[].value | to_entries[].key' applications/*/data/acprestrictions.json | sort -u` lists every declared permission; every `checkAcpPermission(` string and every `"restriction"` value in `acpmenu.json` across the app must be present in that list.
18. **Every plugin MUST ship `data/application.json` AND `data/versions.json`** — without both files, the IPS installer rejects the uploaded tar with a generic and misleading error: *"The application you uploaded cannot be installed because it is not a valid application, the archive is corrupt or the file and directory permissions in /applications do not allow it."* The error says nothing about missing metadata — it is the same message IPS emits for permission problems, bad tar layout, or truncated archives — so it is easy to chase the wrong cause. Working IPS core apps and every correctly-built third-party app ship both files; any new plugin that omits them will fail at upload time even when Application.php, schema.json, lang.xml, and the tar layout are all otherwise correct.

    `data/application.json` — single JSON object describing the app:

    ```json
    {
        "app_directory": "gdrebates",
        "app_author": "GunRack",
        "app_version": "1.0.0",
        "app_long_version": 10000,
        "app_protected": false,
        "app_website": "https://gunrack.deals"
    }
    ```

    - `app_directory` — must match the folder name under `applications/` exactly.
    - `app_version` — human-readable semver (`"1.0.0"`).
    - `app_long_version` — integer used for upgrade comparisons. Convention: `MMmmpp` (major=1, minor=00, patch=00 → `10000`; `1.0.1` → `10001`; `1.1.0` → `10100`; `2.0.0` → `20000`).
    - `app_protected` — `false` for third-party apps; `true` blocks uninstall.
    - `app_website` — shown on the ACP Applications list.

    `data/versions.json` — maps every long-version integer to its semver label; IPS uses this to decide which `setup/upg_{long}/` upgrade steps to run:

    ```json
    {
        "10000": "1.0.0"
    }
    ```

    When bumping a plugin's version, add a new entry (e.g. `"10001": "1.0.1"`) AND update `app_version` + `app_long_version` in `application.json` to match. The two files must stay in sync.

    Audit command: `for d in applications/*/; do [ -f "$d/data/application.json" ] && [ -f "$d/data/versions.json" ] || echo "MISSING: $d"; done` must print nothing.
19. **`data/schema.json` index `length` array MUST have exactly `count(columns)` entries — count them explicitly before saving** — IPS's schema installer reads the `length` and `columns` arrays in lockstep: entry `length[i]` applies to column `columns[i]`. A mismatch in length count throws a schema installer error or silently produces a broken index definition. This applies to every index type: `primary`, `unique`, `key`, `fulltext`. The rule is absolute — there is no special case for PRIMARY keys or any other index type.

    ```json
    // One column → one length entry
    "PRIMARY":  { "type": "primary", "length": [ null ],        "columns": [ "id" ] }
    "idx_mfr":  { "type": "key",     "length": [ null ],        "columns": [ "manufacturer" ] }

    // Two columns → two length entries
    "uq_pair":  { "type": "unique",  "length": [ null, null ],  "columns": [ "rebate_id", "member_id" ] }

    // Three columns with a prefix length on the VARCHAR → three entries
    "idx_trio": { "type": "key",     "length": [ null, 32, null ], "columns": [ "rebate_id", "slug", "created_at" ] }
    ```

    Before saving `schema.json`, count the entries in every index's `columns` array and confirm the `length` array has the same count — PRIMARY with a single `id` column is `[ null ]`, never `[ null, null ]`. Use `null` when you want the default (full-column) index; use an integer when you want a prefix index on a VARCHAR/TEXT column.

    Audit command — run after any schema edit; it prints every index whose `length` and `columns` arrays disagree:

    ```sh
    php -r '
    foreach ( glob( "applications/*/data/schema.json" ) as $f ) {
        $s = json_decode( file_get_contents( $f ), true );
        foreach ( $s as $table => $def ) {
            foreach ( $def["indexes"] ?? [] as $name => $idx ) {
                if ( count( $idx["columns"] ) !== count( $idx["length"] ) ) {
                    printf( "MISMATCH: %s %s.%s cols=%d length=%d\n",
                        $f, $table, $name,
                        count( $idx["columns"] ), count( $idx["length"] ) );
                }
            }
        }
    }'
    ```

    Output must be empty.
20. **IPS v5 ACP button and page-wrapper classes use double-dash BEM syntax, not underscores** — the IPS v5 front-end CSS framework ships BEM-style modifier classes for buttons: `ipsButton--primary`, `ipsButton--normal`, `ipsButton--negative`, `ipsButton--small`. Underscore forms (`ipsButton_primary`, `ipsButton_medium`, `ipsButton_negative`, `ipsButton_small`) are legacy IPS v4 class names that no longer exist in the v5 stylesheet — using them renders buttons as bare unstyled anchor text, which is a frequent symptom of copy-pasting markup from older plugins or outdated docs. The base class `ipsButton` must always appear alongside the modifier (`class="ipsButton ipsButton--primary"` — never just `ipsButton--primary` on its own). The full mapping when porting from v4/underscore style:

    ```
    ipsButton_primary   → ipsButton ipsButton--primary
    ipsButton_medium    → ipsButton ipsButton--normal
    ipsButton_negative  → ipsButton ipsButton--negative
    ipsButton_small     → ipsButton ipsButton--small    (combines with a type modifier)
    ```

    Example: a small approve + reject pair inside a table row:

    ```html
    <a href="..." class="ipsButton ipsButton--primary ipsButton--small">Approve</a>
    <a href="..." class="ipsButton ipsButton--normal ipsButton--small">Reject</a>
    ```

    The ACP page wrapper must also use `<div class="ipsBox ipsPull">` (not bare `<div class="ipsBox">`) for every top-level panel in an admin template — `ipsPull` is the v5 layout helper that pulls the box to the full width of the content region and gives it the correct margin; omitting it renders panels as narrow floating cards rather than a proper admin page surface. This applies to every outer-wrapper `<div class="ipsBox">` in an admin template, including every sibling panel on a multi-section dashboard.

    Every admin template in every plugin (`gdcatalog`, `gddealer`, `gdpricecompare`, `gdrebates`, and the eight still-to-be-built plugins) must follow these conventions. Audit command — should return empty across all admin templates:

    ```sh
    grep -rEn 'ipsButton_(primary|medium|negative|small)\b' applications/*/setup/install.php applications/*/modules/admin
    ```

    Verify BEM forms are in use:

    ```sh
    grep -rEn 'ipsButton--(primary|normal|negative|small)' applications/*/setup/install.php | wc -l
    ```

    Front-end (location=front) templates follow the same convention — IPS v5 uses the same BEM classes across ACP and front-end. When in doubt, inspect a rendered IPS core ACP page (e.g. the Applications list) with browser devtools; every button there uses the double-dash form.

## Full specification
Read `GunRack_Spec_v2.9.16.md` for complete specs on all 12 plugins, database schemas, acceptance criteria, server setup (Appendix B), security requirements (Appendix C), and Phase 2 roadmap (Section 19).


## Server details
- Primary IP: 108.160.146.199
- Secondary IP: 162.255.160.38
- SSH port: 2200
- OS: AlmaLinux 9
- Control panel: DirectAdmin
- IPS path: /home/gunrack/domains/gunrack.deals/public_html/
- OpenSearch: http://localhost:9200 (internal) / https://search.gunrack.deals (external)
- IPS version: 5.0.18
- OpenSearch version: 2.1.0