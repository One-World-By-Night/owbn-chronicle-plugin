=== OWBN Chronicle Manager ===
Contributors: gregwhacke, owbnwebteam
Donate link: https://www.owbn.net/
Tags: chronicle, nested content, information, custom post types
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.80
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Purpose

This plugin aims to:

- Centralize all OWbN Chronicle data using a native WordPress custom post type (`owbn_chronicle`)
- Allow Chronicle owners to manage and update their chronicle profiles
- Provide admins with oversight and editorial control
- Offer shortcode-based filtering and rendering for public-facing displays
- Store all data internally without external form builders or plugins

---

## Architecture Overview

### Custom Post Type

Each `owbn_chronicle` post stores the full record for a Chronicle. Metadata is attached to the post, including structured objects (e.g., staff, locations) and simple scalar values (e.g., region, slug).

### Repeatable Sub-records

Chronicles can have multiple nested elements stored as JSON objects:

- OOC and IC Locations
- Game Sites (virtual and physical)
- Sessions
- Assistant Storytellers (ASTs)
- Documents
- Email Lists
- Social URLs

These are saved under single meta keys and parsed/rendered accordingly.

---

## Field Definitions

- **Chronicle Name**: Human-readable title of the chronicle  
- **Chronicle Slug**: URL-safe identifier (e.g., `kony` for Kings of New York)  

**Nested Fields (stored as objects):**

- **ooc_locations**: One or more locations with city/state/country and geocode  
- **ic_location_list**: In-character locations (multi-record)  
- **game_site_list**: Combined physical and virtual game sites  
- **session_list**: Repeatable session entries with type, date, frequency, genres  
- **ast_list**: Assistant Storytellers (ASTs) with name, email, role, user ID  
- **document_links**: Label + URL pairs for internal or external documentation  
- **email_lists**: Named group mailing lists  
- **social_urls**: Discord, Facebook, etc.  
- **genres**: Array of genre and subgenre combinations  

**Scalar Fields:**

- **premise**, **game_theme**, **game_mood**, **traveler_info**: WYSIWYG text  
- **active_player_count**: Integer estimate  
- **web_url**: Website  
- **hst_user**, **hst_display_name**, **hst_email**: HST data  
- **cm_user**, **cm_display_name**, **cm_email**: CM data (if not a satellite)  
- **hst_selection**, **cm_selection**, **ast_selection**: How each was selected  
- **chronicle_start_date**: ISO date  
- **chronicle_region**: Region name  
- **chronicle_probationary**, **chronicle_satellite**: Boolean (yes/no)  
- **chronicle_parent**: Slug of parent chronicle (if satellite)  

---

## Shortcodes

Chronicle data can be rendered anywhere using the following shortcodes:

### `[owbn-chronicles]`

Renders a listing of approved chronicles.

Example:

`[owbn-chronicles]` – show all  
`[owbn-chronicles region="great lakes" genre="sabbat"]` – filter

**Supported filters:**

- `plug`
- `region`
- `genre`
- `country`
- `state`
- `game_type` (virtual, in-person)
- `probationary` (yes/no)
- `satellite` (yes/no)

### `[owbn-chronicle plug="kony" view="box|full"]`

Render a single chronicle:

- `view="full"` (default): Full profile view  
- `view="box"`: Compact card/summary view with title and key info  

---

## Installation

1. Copy this plugin folder into `/wp-content/plugins/`
2. Activate via the WordPress dashboard
3. Add `[owbn-chronicles]` or `[owbn-chronicle plug="kony"]` to any page or post
4. Begin managing chronicles using WordPress post editing and structured metadata

---

## Developer Notes

- Data is stored using `register_post_meta()` for GraphQL/REST access
- All fields are editable via internal admin tools or custom interfaces
- Repeatables are saved as JSON under single meta keys (e.g., `ast_list`)
- Plugin includes metabox rendering for internal admin review
- Designed to support full static form processing without ACF or Gravity Forms

---

## License

This plugin is licensed under the [GNU General Public License v2.0](http://www.gnu.org/licenses/gpl-2.0.html)

---

## Example Output

View a formatted sample:

https://www.owbn.net/chronicles/new-york-city-ny-usa-kings-of-new-york

---

## Updates

- 1.1.2: Multi-Site control  
- 1.1.5: Validation and noitce of approval requirements for staff changes.

---

## Contributing

Fork the repository, open issues, or submit pull requests via:

[https://github.com/One-World-By-Night/owbn-chronicle-plugin](https://github.com/One-World-By-Night/owbn-chronicle-plugin)

---

## Maintainers

This plugin is maintained by the OWBN.org Web Coordination team.