# OWbN Chronicle Manager

The OWbN Chronicle Manager is a custom WordPress plugin designed to store, manage, and display detailed information about OWbN Chronicles in a structured, searchable, and filterable way. It provides a clean interface for public users, Chronicle owners, and administrators to interact with chronicle data, leveraging custom post types and shortcodes, without relying on third-party form field plugins like ACF.

## Purpose

This plugin aims to:

- Centralize all OWbN Chronicle data using a native WordPress custom post type (`owbn_chronicle`)
- Allow Chronicle owners to edit and propose updates to their chronicle information
- Provide admins with tools to approve sensitive changes
- Offer shortcode-based listing and filtering for public display of chronicles

## Architecture Overview

### Custom Post Type

All Chronicle data is stored in a custom post type: `owbn_chronicle`. Each post represents a unique Chronicle entry within the OWbN network. This structure enables robust, extensible, and permission-aware data storage using WordPress's built-in systems for revisions, metadata, and user relationships.

#### Sub-Records and Multiplicity

Several Chronicle fields support **multiple entries** to reflect the complex nature of OWbN chronicles:

- OOC and IC Locations
- Game Sites (physical and virtual)
- Sessions (repeatable entries per genre)
- ASTs (Assistant Storytellers)
- Documents, Email Lists, and External Links

#### Field Definitions

- **OOC Location**:
  - One or more entries
  - Each includes a location selector, geocode, and structured address (City/State/Country)

- **IC Location**:
  - Supports multiple entries (multi-select)
  - Each includes a name, geocode, and address
  - Stored as child entries or sub-records

- **Game Site**:
  - Can include both Virtual and Physical entries
  - **Virtual Site**: Type (select dropdown) and URL
  - **Physical Address**: Full address and structured location fields

- **Chronicle Data**:
  - Premise (WYSIWYG)
  - Game Theme (WYSIWYG)
  - Game Mood (WYSIWYG)
  - Information for Travellers (WYSIWYG)

- **Genre Information**:
  - Multi-select field
  - Genres include: Vampire (Anarch, Camarilla, Sabbat, Independent, Giovanni, Clan-Specific), Changing Breeds (Garou, Other), Changeling, Demon, Hunter, Kuei-Jin, Mage, Wraith, Other

- **Active Players / Session Count**:
  - Numeric estimate of currently active players per game session

- **Web URL**:
  - Chronicle website or primary presence

- **Social Media Addresses**:
  - One or more entries, including platforms like Discord, Facebook, etc.

- **Sessions**:
  - Repeatable
  - Fields include:
    - Type (Game/Meetup)
    - Frequency (1st–5th, Every, Every Other, Random, Other)
    - Day or Range (Monday–Sunday, Week, Other)
    - Time (local)
    - Game Date Notes
    - Genre (Multi-select)

- **Staff Information**:
  - HST: Name, Email, and linked to a WordPress user account
  - CM: Name, Email, and linked to a WordPress user account
  - AST(s): Multiple entries, each with Name, Email, optional user account, and custom title
  - Admin: Name and Email (optional, for backend management)

- **Staff Selection Processes**:
  - Defined per role (HST, CM, AST)
  - Options include: Player Vote, By HST, By Staff, Other

- **Document Links**:
  - Multiple entries allowed
  - Each includes a descriptive label and document URL

- **Chronicle Email Lists**:
  - Multiple named lists, each with a contact email

- **Behind the Scenes**:
  - Chronicle Start Date
  - Region (selectable)
  - Probationary Status (Yes/No)
  - Satellite Status (Yes/No)
    - If yes, also includes linked Parent Chronicle

This structured model ensures each Chronicle entry captures comprehensive details while maintaining flexibility and editability for owners, visibility for users, and approval control for administrators.

- **OOC Locations**: Allows one or more OOC locations to be listed, each with its own geocode and address metadata. For example, a chronicle may operate in multiple counties.
- **IC Locations**: Supports multiple in-character locations. Each entry includes a name, geocode, and structured address. These are treated as repeatable sub-records.
- **Game Sites**: Chronicles may run both physical and virtual games. Each game site is stored independently and may include:
  - **Virtual**: Type (e.g., Discord, Zoom), URL
  - **Physical**: Full address and city/state/country fields
- **Chronicle Data**: Each data point (Premise, Game Theme, Game Mood, Information for Travellers) is stored as a WYSIWYG/HTML field to allow for full formatting and links.
- **Genres**: Multi-select field. A single chronicle may list support for multiple genres (e.g., Vampire, Wraith, Mage, etc.) and sub-genres (e.g., Sabbat, Camarilla).
- **Sessions**: Repeatable entries that can vary by genre. Each session entry includes:
  - Session type (Game/Meetup)
  - Frequency (e.g., 1st Saturday, Every Other Week, etc.)
  - Day and Time
  - Game Date Notes
  - Genres covered
- **Staff**:
  - **HST** (Head Storyteller): Single required entry. Should be linkable to an existing user account.
  - **CM** (Chronicle Manager): Single required entry. Should be linkable to an existing user account.
  - **ASTs** (Assistant Storytellers): One or more entries. Each AST can include a unique title and contact information. User linkage optional.
- **Staff Selection Processes**: Stores how each staff member is selected. Includes options like Player Vote, Appointed by HST, or Other. Stored per role (HST, CM, AST).
- **Document Links**: Supports multiple document entries (PDFs, Google Docs, etc.). Each entry includes a label and link.
- **Chronicle Email Lists**: Allows multiple entries, each with a name and address.
- **Web and Social Media URLs**: Includes standard Chronicle website, Discord invite URLs, Facebook pages, and other external links.

### User Roles and Views

| Role      | Access Level                      | Purpose                               |
|-----------|-----------------------------------|---------------------------------------|
| Public    | Read-only                         | Browse Chronicle data                 |
| Owner     | Edit (own chronicle only)         | Propose updates via form interface    |
| Admin     | Edit + approve submitted changes  | Moderate restricted fields            |

### Approval Workflow

The OWbN Chronicle Manager plugin uses a structured workflow that ensures chronicle data is verified, versioned, and subject to admin approval when necessary. This is achieved through the integration of Gravity Forms for data submission and Gravity Flow for approval routing.

#### 1. Initial Submission

- A Chronicle Owner (typically the HST or CM) submits a new chronicle via the Chronicle Information Form (built using Gravity Forms).
- All chronicle data is captured, including OOC/IC locations, game site details, session schedules, genres, staff, and behind-the-scenes metadata.

#### 2. Initial Approval (Gravity Flow)

- The submission is routed through Gravity Flow for administrative approval.
- Admins review all fields, particularly those requiring approval (e.g., genre declarations, staff roles, region/probation status).
- Upon approval, a new `owbn_chronicle` post is created and marked as published.

#### 3. Chronicle Appears Publicly

- Once approved, the chronicle will be included in:
  - Shortcode-rendered listings
  - Public displays on the site
  - Any federated views or REST API endpoints

#### 4. Editing an Existing Chronicle

- Chronicle Owners can initiate edits via a pre-populated Gravity Form, launched via a webhook or admin/editor link.
- The form auto-fills with the current values from the live chronicle post.
- The user makes updates and submits the form.

#### 5. Post-Edit Processing

- Upon form submission:
  - A new version of the existing chronicle post is created.
  - If no changes were made to any fields that require approval, the new version is published immediately.
  - If changes include any workflow-managed fields, the update is routed to Gravity Flow for admin approval.

#### 6. Approval of Updated Version

- Admins compare the submitted version to the current published version.
- Upon approval:
  - The updated version is published and becomes the new active chronicle.
  - The previous version is retained in a version history log.
- If rejected:
  - The current version remains unchanged.
  - The Owner is notified and may revise and resubmit.

This workflow balances editorial freedom with administrative oversight and ensures that all chronicle data displayed to users is accurate, consistent, and accountable.

## Shortcodes

Chronicle lists can be embedded anywhere using shortcode filters.

### Basic Usage

[owbn-chronicles]

Renders a complete listing of all approved chronicles.

### Filtered Listings

Shortcodes accept attributes for field-based filtering:

[owbn-chronicles region="great lakes" genre="sabbat"]

Supported filters (case-insensitive):

- region
- genre
- country
- state
- game_type (virtual, in-person)
- active_players_min, active_players_max
- probationary (yes/no)
- satellite (yes/no)

Multiple filters can be combined to narrow down the results.

## Installation

1. Upload the plugin to `/wp-content/plugins/owbn-chronicle-manager/`
2. Activate via the WordPress admin panel
3. Use `[owbn-chronicles]` in pages or posts to render lists
4. Assign proper roles to Chronicle owners and admins

## Developer Notes

- All chronicle metadata is stored as post meta attached to the `owbn_chronicle` post type.
- Shortcode rendering is handled via template parts or inline rendering hooks.
- Future enhancements may include JSON API support, external data sync, and Gravity Flow integration for approvals.

## License

This plugin is licensed under the GNU General Public License v2.0.

See LICENSE for full text.

## Example Output

To see a visual example, visit:

https://www.owbn.net/chronicles/new-york-city-ny-usa-kings-of-new-york

## Roadmap

- Custom post type scaffolding
- User/admin roles & edit views
- Field storage and validation
- Admin change approval UI
- Gravity Flow integration for approval routing
- Custom REST API for federation and external tools

## Contributing

Want to help? Open a GitHub issue, fork the repo, or submit a pull request.

## Maintainers

This plugin is maintained by OWbN.org development volunteers.
