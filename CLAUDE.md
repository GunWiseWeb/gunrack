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