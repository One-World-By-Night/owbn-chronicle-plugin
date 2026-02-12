# Refactor owbn-chronicle-plugin: Dynamic Entity Type System

## Context

owbn-chronicle-plugin manages two entity types (chronicles, coordinators) with duplicated code paths. The goal is to create a generic entity registration system so new entity types can be added via configuration + field definitions.

**Constraints:**

- Zero data migration — post types, meta keys, and stored data are unchanged
- Clean migration, no backward compat wrappers (only 2-3 sites, all controlled)
- Delete old duplicate code, don't deprecate
- Staff change logic is generic (self-promotion detection applies to all entities with staff)
- Entity types managed as PHP config files (version-controlled, developer-added)
- REST API moves to generic routes: `/entities/{type}/list` and `/entities/{type}/detail`

**Scope:** owbn-chronicle-plugin changes + owbn-client API route updates (since it's the only consumer)

## Architecture

### Entity Type Registry

`owbn_register_entity_type($config)` accepts a configuration array. Chronicles and coordinators are two configs.

```php
owbn_register_entity_type([
    // Identity (matches existing data)
    'post_type'      => 'owbn_chronicle',
    'slug_meta_key'  => 'chronicle_slug',
    'url_slug'       => 'chronicles',
    'entity_key'     => 'chronicle',

    // Labels
    'singular'       => 'Chronicle',
    'plural'         => 'Chronicles',
    'menu_icon'      => 'dashicons-location-alt',

    // Feature toggle
    'option_enabled' => 'owbn_enable_chronicles',

    // Fields
    'field_definitions' => 'owbn_get_chronicle_field_definitions',

    // Save behavior
    'immutable_fields'  => ['chronicle_slug'],
    'restricted_fields' => ['record_type', 'chronicle_slug', 'chronicle_start_date', ...],
    'staff_fields'      => ['hst_info', 'cm_info'],

    // Staff-specific rules
    'exclusive_fields'  => [
        // When chronicle_satellite=1, cm_info gets cleared (and vice versa for chronicle_parent)
        ['condition' => ['chronicle_satellite', '1'], 'clear' => ['cm_info']],
        ['condition' => ['chronicle_satellite', '0'], 'clear' => ['chronicle_parent']],
    ],

    // Permissions (AccessSchema patterns)
    'access_patterns' => [
        'chronicle/{slug}/cm',
        'chronicle/{slug}/hst',
    ],

    // API
    'api_key_option'    => 'owbn_chronicles_api_key',
    'list_fields'       => [...],
    'detail_fields'     => [...],
    'personnel_fields'  => ['hst_info', 'cm_info', 'ast_list', 'admin_contact'],

    // Capabilities
    'capabilities' => [
        'edit_post'     => 'edit_owbn_chronicle',
        'read_post'     => 'read_owbn_chronicle',
        'delete_post'   => 'delete_owbn_chronicle',
        'edit_posts'    => 'ocm_view_list',
        'publish_posts' => 'ocm_create_chronicle',
    ],
]);
```

### What Stays The Same (Data Layer)

- Post type names: `owbn_chronicle`, `owbn_coordinator`
- All meta keys: `chronicle_slug`, `hst_info`, `coordinator_slug`, `coord_info`, etc.
- Option names: `owbn_enable_chronicles`, `owbn_chronicles_api_key`, etc.
- Capability names: `edit_owbn_chronicle`, `ocm_create_chronicle`, etc.
- Database tables and stored data: untouched

### What Changes

- Duplicate init/save/validate/API code → generic functions driven by config
- REST API routes → generic `/entities/{type}/list` and `/entities/{type}/detail`
- Old hardcoded files deleted (not deprecated)
- owbn-client updated to use new API routes

### New Entity Type Convention

New entities follow the pattern `owbn_{entity_key}`:

- `owbn_exec` for executive positions
- `owbn_committee` for committees
- etc.

Each is a PHP config file + field definitions function.

## Implementation Plan

### Phase 1: Entity Registry Core

**New file:** `includes/core/entity-registry.php`

```php
function owbn_register_entity_type(array $config): void
function owbn_get_entity_types(): array
function owbn_get_entity_config(string $post_type): ?array
function owbn_get_entity_config_by_key(string $entity_key): ?array
function owbn_is_entity_enabled(string $post_type): bool
```

Static array storage. All downstream functions look up config by post type or entity key.

### Phase 2: Generic CPT Registration

**New file:** `includes/core/entity-init.php`

```php
function owbn_register_entity_cpt(array $config): void
function owbn_register_entity_meta(array $config): void
function owbn_add_entity_meta_box(array $config): void
function owbn_entity_map_meta_cap($caps, $cap, $user_id, $args): array
function owbn_user_can_edit_entity(int $user_id, int $post_id): bool
```

Permission mapping uses config's `access_patterns` — iterates patterns, substitutes `{slug}` with the entity's slug meta value, checks against AccessSchema client.

### Phase 3: Generic Metabox Rendering

**Modify:** `includes/render/render-metabox-fields.php`

- Rename `owbn_render_chronicle_fields_metabox()` → `owbn_render_entity_metabox($post)`
- Looks up entity config from `$post->post_type`
- Gets field definitions from config's `field_definitions` callable
- Nonce name derived from config: `owbn_{entity_key}_nonce`

**Fix hardcoded spots:**

- `owbn_render_chronicle_select_field()` → `owbn_render_entity_select_field($key, $value, $meta, $label, $error_class, $disabled_attr, $post_type)`
- `render_social_link_block()` → accept field meta as parameter
- `owbn_render_user_info()` → look up staff_fields from entity config instead of hardcoded `cm_info` check

### Phase 4: Generic Save Handler

**New file:** `includes/core/entity-save.php`

```php
function owbn_save_entity_meta(int $post_id, WP_Post $post): void
```

Flow:

1. Look up entity config from `$post->post_type`
2. Get field definitions, immutable/restricted lists from config
3. Iterate field definitions with type-based sanitization (reuse existing sanitizers from helpers.php)
4. Track staff changes using config's `staff_fields`
5. Apply `exclusive_fields` rules from config
6. Self-promotion detection: if current user assigns themselves to any staff field, revert to draft
7. Existing sanitizer functions in `helpers.php` stay as-is

**Delete:** `chronicle-save.php` (except `owbn_handle_chronicle_staff_change()` logic, which moves into the generic handler + config rules) and `coordinator-save.php`

### Phase 5: Generic Validation

**New file:** `includes/core/entity-validate.php`

```php
function owbn_validate_entity_submission(array $postarr): array
function owbn_force_draft_on_error(array $data, array $postarr): array
```

Generic required field checks, slug uniqueness via config's `slug_meta_key`. Post type and transient keys derived from config.

**Delete:** `chronicle-validate.php`, `coordinator-validate.php`

### Phase 6: Generic API Handlers

**New file:** `includes/core/entity-api.php`

```php
function owbn_api_get_entity_list(WP_REST_Request $request): array
function owbn_api_get_entity_detail(WP_REST_Request $request): array
function owbn_format_entity_data(int $post_id, string $post_type, bool $full = false): array
```

Routes are generic — entity type comes from URL parameter:

```
POST /wp-json/owbn-cc/v1/entities/{type}/list
POST /wp-json/owbn-cc/v1/entities/{type}/detail
```

Route registration loops over `owbn_get_entity_types()`. API key checked per entity type via config's `api_key_option`.

Response format uses config's `list_fields`, `detail_fields`, and `personnel_fields`. Existing helper functions (`owbn_filter_personnel_list()`, `owbn_strip_wysiwyg_subfields()`) stay as-is.

**Delete:** `api-chronicles.php`, `api-coordinators.php`

**Modify:** `webhooks.php` — loop over registered types for route registration

### Phase 7: Chronicle + Coordinator Configs

**New file:** `includes/entities/chronicle-config.php`

Calls `owbn_register_entity_type()` with the chronicle config. References existing `owbn_get_chronicle_field_definitions()` from `fields.php`.

**New file:** `includes/entities/coordinator-config.php`

Same pattern for coordinators.

### Phase 8: owbn-client API Route Updates

**Modify:** `owbn-client/owbn-client/includes/core/client-api.php`

Update remote request URLs from:

```
{base_url}/chronicles       → {base_url}/entities/chronicle/list
{base_url}/chronicle-detail → {base_url}/entities/chronicle/detail
{base_url}/coordinators     → {base_url}/entities/coordinator/list
{base_url}/coordinator-detail → {base_url}/entities/coordinator/detail
```

This is a targeted change to the URL construction in `owc_get_chronicles()`, `owc_get_coordinators()`, `owc_get_chronicle_detail()`, `owc_get_coordinator_detail()`.

### Phase 9: Upgrade Routine

**Add to:** `owbn-chronicle-manager.php` or `includes/core/entity-registry.php`

```php
function owbn_check_upgrade(): void
    $current = get_option('owbn_cm_version', '0');
    if (version_compare($current, OWBN_CM_VERSION, '<')) {
        owbn_run_upgrade($current);
        update_option('owbn_cm_version', OWBN_CM_VERSION);
    }

function owbn_run_upgrade(string $from): void
    // Flush rewrite rules (new API routes)
    flush_rewrite_rules();
    // Set defaults for any new options
    // Data normalization if needed (e.g., slug casing)
```

Runs on `init` hook. Version stored in `owbn_cm_version` option. Future upgrades add version-gated blocks to `owbn_run_upgrade()`.

### Phase 10: Wire It Up

**Modify:** `owbn-chronicle-manager.php`

```php
// Core
require_once 'includes/core/entity-registry.php';
require_once 'includes/core/entity-init.php';
require_once 'includes/core/entity-save.php';
require_once 'includes/core/entity-validate.php';
require_once 'includes/core/entity-api.php';

// Field definitions (unchanged)
require_once 'includes/fields.php';

// Entity configs
require_once 'includes/entities/chronicle-config.php';
require_once 'includes/entities/coordinator-config.php';

// Shared helpers (unchanged)
require_once 'includes/hooks/helpers.php';

// Rendering (modified but same files)
require_once 'includes/render/render-metabox-fields.php';
// ... other render files
```

## Files Summary

| File                                          | Action                                              |
| --------------------------------------------- | --------------------------------------------------- |
| `includes/core/entity-registry.php`           | NEW                                                 |
| `includes/core/entity-init.php`               | NEW                                                 |
| `includes/core/entity-save.php`               | NEW                                                 |
| `includes/core/entity-validate.php`           | NEW                                                 |
| `includes/core/entity-api.php`                | NEW                                                 |
| `includes/entities/chronicle-config.php`       | NEW                                                 |
| `includes/entities/coordinator-config.php`     | NEW                                                 |
| `includes/fields.php`                         | UNCHANGED                                           |
| `includes/hooks/helpers.php`                  | UNCHANGED                                           |
| `includes/render/render-metabox-fields.php`   | MODIFY (generic render, fix 3 hardcoded spots)      |
| `includes/render/render-user-fields.php`      | MODIFY (parameterize cm_info check)                 |
| `includes/render/render-links-uploads-fields.php` | MODIFY (fix social_link_block)                  |
| `includes/hooks/webhooks.php`                 | MODIFY (generic route registration)                 |
| `includes/hooks/admin-init.php`               | MODIFY (use registry for cap grants)                |
| `owbn-chronicle-manager.php`                  | MODIFY (load new files, remove old requires)        |
| `includes/hooks/chronicle-init.php`           | DELETE                                              |
| `includes/hooks/coordinator-init.php`         | DELETE                                              |
| `includes/hooks/chronicle-save.php`           | DELETE                                              |
| `includes/hooks/coordinator-save.php`         | DELETE                                              |
| `includes/hooks/chronicle-validate.php`       | DELETE                                              |
| `includes/hooks/coordinator-validate.php`     | DELETE                                              |
| `includes/hooks/api-chronicles.php`           | DELETE                                              |
| `includes/hooks/api-coordinators.php`         | DELETE                                              |
| `owbn-client/.../client-api.php`              | MODIFY (update API route URLs)                      |

## Data & Upgrade Guarantees

1. **Post types unchanged**: `owbn_chronicle` and `owbn_coordinator` — same CPT names
2. **Meta keys unchanged**: All stored meta keys identical — existing post meta just works
3. **Options unchanged**: Same option names for feature toggles, API keys, etc.
4. **Capabilities unchanged**: Same capability names
5. **Rendering output unchanged**: Same HTML structure, same CSS classes
6. **API data format unchanged**: Same JSON response structure, just different URL paths
7. **Upgrade routine available**: Version-gated migration runs on upgrade — flushes rewrites, sets defaults, normalizes data if needed. Future upgrades add version checks to the same routine.

## Verification

1. Chronicle and coordinator admin UI must render identically
2. Saving chronicles/coordinators produces same post meta
3. API responses match current format (test with owbn-client after route update)
4. AccessSchema permission checks work identically
5. Staff self-promotion detection works for both entity types
6. Exclusive field rules work (satellite/parent for chronicles)
7. Adding a minimal new entity type (e.g., "exec" with slug + title + staff) requires only a new config file + field definitions function

---

## Implementation Status

All 10 phases implemented in `owbn-chronicle-manager-v2/`. See [status.md](status.md) for detailed tracking.

### Post-Review Fixes Applied

Issues discovered during code review of the v2 implementation and fixed:

#### 1. Nonce Action Mismatch (Critical)

**Problem:** `entity-init.php` generated nonce with action `owbn_{key}_save` but `entity-save.php` verified against `owbn_{key}_meta_nonce`. All saves would silently fail.

**Fix:** Changed `entity-save.php:38` to use `owbn_{key}_save` to match the generated nonce.

#### 2. Missing API Helper Functions (Critical)

**Problem:** `owbn_filter_personnel_list()` and `owbn_strip_wysiwyg_subfields()` were called in `entity-api.php` but only existed in v1's `api-chronicles.php` — never ported to v2's `helpers.php`.

**Fix:** Added both functions to `includes/hooks/helpers.php` in the API Response Helpers section.

#### 3. CORS Preflight Broken (High)

**Problem:** CORS headers were set unconditionally for all REST API requests via `rest_api_init` action. OPTIONS callbacks returned empty 204 without explicit CORS headers — browsers could reject preflight.

**Fix:**
- Moved CORS header injection to `rest_pre_serve_request` filter, scoped to `/owbn-cc/` routes only.
- Added `owbn_api_cors_preflight()` callback that returns 204 with explicit CORS headers.
- All OPTIONS routes now use this callback instead of anonymous empty responses.

#### 4. Slug Uniqueness Not Validated (High)

**Problem:** `entity-validate.php` checked slug format but not uniqueness. Multiple entities could share the same slug, breaking URL routing and API detail lookups.

**Fix:** Added `WP_Query` uniqueness check after format validation — queries for existing posts with the same slug meta value, excluding the current post ID.

#### 5. Hook Priority Inverted (Medium)

**Problem:** `wp_insert_post_data` filters ran slug sync (priority 5) before validation (priority 10). If slug failed validation, it was already synced to `post_name`.

**Fix:** Changed slug sync priority from 5 to 15, so validation (priority 10) runs first.

#### 6. Admin Capabilities Hardcoded (Medium)

**Problem:** `admin-init.php` had hardcoded capability lists per entity type. Adding a new entity type required manually updating the function.

**Fix:** Refactored `owbn_grant_admin_chronicle_caps()` to loop over `owbn_get_entity_types()` and derive capabilities from each config's `capabilities` array plus WordPress-standard CPT cap names.

### Phase 8 Note

The owbn-client (`/One-World-by-Night/owbn-client/`) was already updated to use the new generic routes (`entities/chronicle/list`, `entities/chronicle/detail`, etc.) prior to this review. Legacy backward-compat routes remain registered in the v2 API for any other consumers.
