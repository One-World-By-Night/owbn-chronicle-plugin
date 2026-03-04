# Plugin Status

## v2 Refactor — Complete

All phases shipped. See git history for details.

### Files Added

| File | Purpose |
| ---- | ------- |
| `includes/core/entity-registry.php` | Entity type registration and lookup |
| `includes/core/entity-init.php` | CPT/meta registration, permissions, templates |
| `includes/core/entity-save.php` | Generic save handler with field sanitization |
| `includes/core/entity-validate.php` | Generic validation with error handling |
| `includes/core/entity-api.php` | REST API handlers (list, detail, routes, CORS) |
| `includes/entities/chronicle-config.php` | Chronicle entity config |
| `includes/entities/coordinator-config.php` | Coordinator entity config |
| `includes/entities/exec-config.php` | Executive entity config |

### v2 Test Results (studiodev — 168 chronicles, 45 coordinators)

| Area | Result |
|------|--------|
| Plugin load, 3 entity types registered | PASS |
| Chronicle/coordinator meta reads correctly | PASS |
| `POST /entities/{type}/list` and `/detail` | PASS |
| Wrong API key returns 403 | PASS |
| Missing slug returns 400, invalid type returns 404 | PASS |
| Personnel fields filtered (no user IDs or emails) | PASS |
| Legacy routes (`/chronicles`, `/coordinator-detail`) | PASS |
| CORS OPTIONS preflight returns correct headers | PASS |
| Invalid/duplicate slug rejected | PASS |
| Required fields flagged when empty | PASS |

## Remaining Work

- [ ] Manual browser test: admin UI metabox rendering
- [ ] Manual browser test: save handler via admin UI (create + edit)
- [ ] Manual test: AccessSchema permission checks, self-promotion, restricted fields
- [ ] Manual test: exclusive field rules (satellite toggle clears cm\_info)
- [ ] Refactor: make `cc-settings.php` data-driven from entity registry
- [ ] Refactor: owbn-client registry pattern (currently requires ~9 file changes per new entity type)
