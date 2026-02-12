# OWBN Chronicle Plugin — Refactor Status

## Migration Phase Tracker

| Phase | Name | Status | Notes |
|-------|------|--------|-------|
| 1 | Entity Registry Core | DONE | `includes/core/entity-registry.php` (118 lines) |
| 2 | Generic CPT Registration | DONE | `includes/core/entity-init.php` (536 lines) |
| 3 | Generic Metabox Rendering | DONE | Integrated into entity-init.php + updated render files |
| 4 | Generic Save Handler | DONE | `includes/core/entity-save.php` (518 lines) |
| 5 | Generic Validation | DONE | `includes/core/entity-validate.php` — slug uniqueness added |
| 6 | Generic API Handlers | DONE | `includes/core/entity-api.php` — CORS fixed, preflight added |
| 7 | Chronicle + Coordinator Configs | DONE | `includes/entities/chronicle-config.php`, `coordinator-config.php`, `exec-config.php` |
| 8 | owbn-client API Route Updates | DONE | Already using `entities/{type}/list` routes |
| 9 | Upgrade Routine | DONE | Version-gated in `owbn-chronicle-manager.php` |
| 10 | Wire It Up | DONE | Main plugin file loads all new files in correct order |

## Post-Review Bug Fixes

| Issue | Severity | File(s) Changed | Fix |
|-------|----------|-----------------|-----|
| Nonce action mismatch | CRITICAL | `entity-save.php:38` | Changed action from `owbn_{key}_meta_nonce` to `owbn_{key}_save` |
| Missing API helpers | CRITICAL | `helpers.php` | Added `owbn_filter_personnel_list()` and `owbn_strip_wysiwyg_subfields()` |
| CORS preflight broken | HIGH | `entity-api.php` | Scoped CORS to `/owbn-cc/` routes, added `owbn_api_cors_preflight()` callback |
| Slug uniqueness missing | HIGH | `entity-validate.php` | Added WP_Query uniqueness check after format validation |
| Hook priority inverted | MEDIUM | `entity-save.php:518` | Slug sync priority 5→15 (now runs after validation at 10) |
| Admin caps hardcoded | MEDIUM | `admin-init.php` | Refactored to derive caps from entity registry |

## Files Created (v2)

| File | Lines | Purpose |
|------|-------|---------|
| `includes/core/entity-registry.php` | 118 | Entity type registration and lookup |
| `includes/core/entity-init.php` | 536 | CPT/meta registration, permissions, templates |
| `includes/core/entity-save.php` | 518 | Generic save handler with field sanitization |
| `includes/core/entity-validate.php` | 205 | Generic validation with error handling |
| `includes/core/entity-api.php` | 503 | REST API handlers (list, detail, routes, CORS) |
| `includes/entities/chronicle-config.php` | 71 | Chronicle entity configuration |
| `includes/entities/coordinator-config.php` | 67 | Coordinator entity configuration |
| `includes/entities/exec-config.php` | 67 | Executive entity configuration |

## Files Deleted (from v1)

- `includes/hooks/chronicle-init.php` → merged into entity-init.php
- `includes/hooks/coordinator-init.php` → merged into entity-init.php
- `includes/hooks/chronicle-save.php` → merged into entity-save.php
- `includes/hooks/coordinator-save.php` → merged into entity-save.php
- `includes/hooks/chronicle-validate.php` → merged into entity-validate.php
- `includes/hooks/coordinator-validate.php` → merged into entity-validate.php
- `includes/hooks/api-chronicles.php` → merged into entity-api.php
- `includes/hooks/api-coordinators.php` → merged into entity-api.php

## Files Modified (v2)

- `owbn-chronicle-manager.php` — new require order, upgrade routine, activation hook
- `includes/render/render-metabox-fields.php` — parameterized field helpers
- `includes/render/render-user-fields.php` — uses entity config for visibility rules
- `includes/render/render-links-uploads-fields.php` — generic field handling
- `includes/hooks/admin-init.php` — caps derived from entity registry
- `includes/hooks/helpers.php` — added API response helpers (filter_personnel_list, strip_wysiwyg_subfields)
- `includes/admin/cc-settings.php` — added Executive settings section (enable, mode, API key, remote URL/key)

## Post-Phase Enhancements

| Enhancement | Files Changed | Description |
|-------------|---------------|-------------|
| `menu_name` config support | `entity-init.php`, all configs | Sidebar labels: OWBN Chronicles, OWBN Coordinators, OWBN Executives |
| `menu_position` config support | `entity-init.php`, all configs | Entity types grouped at positions 30-32 in admin sidebar |
| Executive entity type | `exec-config.php`, `fields.php`, `owbn-chronicle-manager.php`, `cc-settings.php` | New entity type matching coordinator structure |

## Test Results (studiodev — WordPress Studio)

**Test environment:** WordPress Studio, `studiodev` site (`studiodev.ihp.local`), SQLite backend
**Test data:** 168 chronicles (2 published, 166 draft), 45 coordinators (all published)
**Source:** XML exports from `chroniclesofowbn` and `councilowbn` production sites

### Plugin Load

| Test | Result |
|------|--------|
| v2.0.0 plugin activates without fatal errors | PASS |
| Entity registry loads 3 types (chronicle, coordinator, exec) | PASS |
| All entity types report enabled | PASS |
| Upgrade routine runs (version stored as 2.0.0) | PASS |
| Menu positions grouped: 30 (Chronicles), 31 (Coordinators), 32 (Executives) | PASS |

### Data Integrity

| Test | Result |
|------|--------|
| Chronicle meta reads correctly (slug, region, hst_info, genres, ooc_locations) | PASS |
| Coordinator meta reads correctly (slug, title, type, coord_info, subcoord_list) | PASS |
| Imported data preserved without modification | PASS |

### REST API — Generic Routes

| Test | Result |
|------|--------|
| `POST /entities/chronicle/list` — returns formatted chronicles | PASS |
| `POST /entities/chronicle/detail` — returns full chronicle by slug | PASS |
| `POST /entities/coordinator/list` — returns formatted coordinators | PASS |
| `POST /entities/coordinator/detail` — returns full coordinator by slug | PASS |
| `POST /entities/exec/list` — returns empty list (no data yet) | PASS |
| `POST /entities/exec/list` — wrong API key returns 403 | PASS |
| Detail with missing slug returns 400 error | PASS |
| Invalid entity type returns 404 error | PASS |
| Wrong API key returns 403 error | PASS |
| Personnel fields filtered (no user IDs or actual_email) | PASS |

### REST API — Legacy Routes

| Test | Result |
|------|--------|
| `POST /chronicles` — works as backward compat | PASS |
| `POST /coordinator-detail` — works as backward compat | PASS |

### CORS

| Test | Result |
|------|--------|
| OPTIONS preflight returns Access-Control headers | PASS |
| Allow-Origin, Allow-Methods, Allow-Headers all present | PASS |

### Validation

| Test | Result |
|------|--------|
| Invalid slug format (spaces, special chars) rejected | PASS |
| Valid unique slug passes format check | PASS |
| Duplicate slug (existing "mckn") rejected | PASS |
| Required fields (genres, game_type, hst_info, cm_info) flagged when empty | PASS |

## owbn-client Integration Status

The owbn-client plugin (`/One-World-by-Night/owbn-client/`) is the **consumer** of entity data. It fetches from the manager's REST API and renders front-end pages.

**Current state:** Heavily hardcoded per entity type. Each new entity requires ~10 file changes.

**Files that need changes per new entity:**
1. `client-api.php` — fetch functions (list + detail, local + remote)
2. `client-register.php` — enable/mode helper functions
3. `settings.php` — settings registration + HTML section
4. `shortcodes.php` — shortcode type cases
5. `data-fetch.php` — fetch wrapper cases
6. `rewrites.php` — URL rewrite rules
7. `activation.php` — default page creation
8. New `render-{type}-list.php` — list rendering
9. New `render-{type}-detail.php` — detail rendering

**Recommended next step:** Refactor owbn-client to use a registry pattern (like the manager) so new entity types only need a config + render templates.

## Remaining Work

- [ ] Testing: Admin UI metabox rendering (manual browser test in Studio)
- [ ] Testing: Save handler via admin UI (create + edit chronicle)
- [ ] Testing: Permission checks (AccessSchema, self-promotion, restricted fields)
- [ ] Testing: Exclusive field rules (satellite toggle clears cm_info)
- [ ] Refactor: Make cc-settings.php data-driven from entity registry
- [ ] Refactor: owbn-client registry pattern (reduce per-entity code changes)
- [ ] Deployment: Production rollout plan
