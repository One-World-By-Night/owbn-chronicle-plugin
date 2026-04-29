<?php
if (!defined('ABSPATH')) exit;

function owbn_get_chronicle_field_definitions()
{
    return [
        // ── Header (rendered ABOVE the tab nav, not as a tab) ──────────
        // The renderer detects the `__header__` group key and renders its
        // fields in a dedicated block above the tab navigation.
        '__header__' => [
            'chronicle_slug' => [
                'label' => __('Chronicle Slug', 'owbn-chronicle-manager'),
                'type' => 'slug',
                'min_length' => 3,
                'max_length' => 6,
                'required' => true,
                'description' => __('3–6 character ID used in URLs and as a stable handle (e.g. "mckn"). Locked once saved.', 'owbn-chronicle-manager'),
            ],
            'chronicle_probationary' => [
                'label' => __('Probationary?', 'owbn-chronicle-manager'),
                'type' => 'boolean',
                'description' => __('Set by admin, the Web Coord, or the Membership Coord. Chronicles can\'t unflag themselves.', 'owbn-chronicle-manager'),
            ],
            'chronicle_satellite' => [
                'label' => __('Satellite?', 'owbn-chronicle-manager'),
                'type' => 'boolean',
                'description' => __('Check if this is a satellite of another chronicle. Pick the parent below.', 'owbn-chronicle-manager'),
            ],
            'chronicle_parent' => [
                'label' => __('Parent Chronicle', 'owbn-chronicle-manager'),
                'type'  => 'chronicle_select',
                'source' => 'owbn_chronicle_list',
                'description' => __('Only used when Satellite is checked. Parents must be full (non-probationary) chronicles — only admin, the Web Coord, or the Membership Coord can promote a chronicle to full.', 'owbn-chronicle-manager'),
            ],
        ],
        // ── Tab 1: Description ─────────────────────────────────────────
        // Intentionally (mostly) empty. The renderer detects empty groups and
        // renders WP's post_content editor inside that tab panel via wp_editor().
        // The __description__ key is shown as a hint above the editor.
        'Description' => [
            '__description__' => __('Tell players what your chronicle is about — setting, themes, what makes it unique. Shown on the public chronicle page.', 'owbn-chronicle-manager'),
        ],
        // ── Tab 2 ──────────────────────────────────────────────────────
        'Game, Schedule & Staff' => [
            'genres' => [
                'label' => __('Genres', 'owbn-chronicle-manager'),
                'type' => 'multi_select',
                'source' => 'owbn_genre_list',
                'required' => true,
                'description' => __('All genres run at this chronicle. Pick as many as apply.', 'owbn-chronicle-manager'),
            ],
            'game_type' => [
                'label' => __('Game Type', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['In-Person', 'Virtual', 'Hybrid'],
                'required' => true,
                'description' => __('Where games happen — physical site, online (Discord/Zoom/etc.), or both.', 'owbn-chronicle-manager'),
            ],
            'timezone' => [
                'label' => __('Timezone', 'owbn-chronicle-manager'),
                'type'  => 'select',
                'source' => 'owbn_timezone_list',
                'description' => __('Used to convert session times to each viewer\'s local time on the dashboard calendar.', 'owbn-chronicle-manager'),
            ],
            'session_list' => [
                'label' => __('Session List', 'owbn-chronicle-manager'),
                'type'  => 'session_group',
                'description' => __('Recurring game nights. Add one row per regular session. One-time events go in One-Off Events below.', 'owbn-chronicle-manager'),
                'fields' => [
                    'session_type' => [
                        'label' => __('Session Type', 'owbn-chronicle-manager'),
                        'type' => 'select',
                        'options' => ['Game', 'OOC Social Meetup', 'Other'],
                        'required' => true,
                    ],
                    'frequency' => [
                        'label' => __('Frequency', 'owbn-chronicle-manager'),
                        'type' => 'select',
                        'options' => ['1st', '2nd', '3rd', '4th', '5th', 'Every', 'Every Other', 'Random', 'Other'],
                    ],
                    'day' => [
                        'label' => __('Day', 'owbn-chronicle-manager'),
                        'type' => 'select',
                        'options' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Week', 'Other'],
                        'required' => true,
                    ],
                    'checkin_time' => [
                        'label' => __('Check-in Time', 'owbn-chronicle-manager'),
                        'type' => 'time',
                    ],
                    'start_time' => [
                        'label' => __('Start Time', 'owbn-chronicle-manager'),
                        'type' => 'time',
                        'required' => true,
                    ],
                    'notes' => [
                        'label' => __('Game Date Notes', 'owbn-chronicle-manager'),
                        'type' => 'wysiwyg',
                    ],
                    'genres' => [
                        'label' => __('Genres', 'owbn-chronicle-manager'),
                        'type' => 'multi_select',
                        'source' => 'owbn_genre_list',
                    ],
                    'anchor_date' => [
                        'label' => __('Start Date (for "Every Other")', 'owbn-chronicle-manager'),
                        'type' => 'date',
                        'description' => __('Required when frequency is "Every Other". Anchors the bi-weekly schedule.', 'owbn-chronicle-manager'),
                    ],
                ],
            ],
            'session_one_offs' => [
                'label' => __('One-Off Events', 'owbn-chronicle-manager'),
                'type'  => 'one_off_group',
                'description' => __('Special events that don\'t fit the normal schedule — single-night games, conventions, crossovers.', 'owbn-chronicle-manager'),
                'fields' => [
                    'event_date'  => ['label' => __('Date', 'owbn-chronicle-manager'), 'type' => 'date', 'required' => true],
                    'start_time'  => ['label' => __('Start Time', 'owbn-chronicle-manager'), 'type' => 'time', 'required' => true],
                    'event_title' => ['label' => __('Event Title', 'owbn-chronicle-manager'), 'type' => 'text'],
                    'genres'      => ['label' => __('Genres', 'owbn-chronicle-manager'), 'type' => 'multi_select', 'source' => 'owbn_genre_list'],
                    'notes'       => ['label' => __('Notes', 'owbn-chronicle-manager'), 'type' => 'wysiwyg'],
                ],
            ],
            'hst_info' => [
                'label' => __('HST Info', 'owbn-chronicle-manager'),
                'type' => 'user_info',
                'required' => true,
                'description' => __('Head Storyteller. Pick the WP user — name and email pull from their profile.', 'owbn-chronicle-manager'),
            ],
            'cm_info' => [
                'label' => __('CM Info', 'owbn-chronicle-manager'),
                'type' => 'user_info',
                'conditional_required' => 'chronicle_satellite=0', // custom logic
                'description' => __('Chronicle Manager. Required unless this is a satellite chronicle (which inherits the parent\'s CM).', 'owbn-chronicle-manager'),
            ],
            'ast_list' => [
                'label' => __('AST Info', 'owbn-chronicle-manager'),
                'type' => 'ast_group',
                'description' => __('Assistant Storytellers. Add one row per AST. Granting roles here also grants them in the system.', 'owbn-chronicle-manager'),
                'fields' => [
                    'user' => [
                        'label' => __('User', 'owbn-chronicle-manager'),
                        'type' => 'user',
                    ],
                    'display_name' => [
                        'label' => __('Display Name', 'owbn-chronicle-manager'),
                        'type' => 'text',
                    ],
                    'actual_email' => [
                        'label' => __('Actual Email', 'owbn-chronicle-manager'),
                        'type' => 'email',
                    ],
                    'display_email' => [
                        'label' => __('Display Email', 'owbn-chronicle-manager'),
                        'type' => 'email',
                    ],
                    'role' => [
                        'label' => __('Role', 'owbn-chronicle-manager'),
                        'type' => 'text',
                    ],
                ],
            ],
        ],
        // ── Tab Two ────────────────────────────────────────────────────
        'Documents, Players & Sites' => [
            'document_links' => [
                'label' => __('Document Links', 'owbn-chronicle-manager'),
                'type'  => 'document_links_group',
                'description' => __('Public docs for the chronicle: house rules, character apps, FAQs. Link out or upload a file.', 'owbn-chronicle-manager'),
                'fields' => [
                    'title' => [
                        'label'    => __('Title', 'owbn-chronicle-manager'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'link' => [
                        'label' => __('External URL (if no upload)', 'owbn-chronicle-manager'),
                        'type'  => 'url',
                    ],
                    'upload' => [
                        'label' => __('Upload File (optional)', 'owbn-chronicle-manager'),
                        'type'  => 'upload',
                    ],
                    'last_updated' => [
                        'label' => __('Last Updated', 'owbn-chronicle-manager'),
                        'type'  => 'date',
                    ],
                ],
            ],
            'active_player_count' => [
                'label' => __('Active Players', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['1-10', '11-20', '21-30', '31-40', '41-50', '51+'],
                'description' => __('Rough size of the active player base. Self-reported.', 'owbn-chronicle-manager'),
            ],
            'game_site_list' => [
                'label' => __('Game Sites', 'owbn-chronicle-manager'),
                'type'  => 'location_group',
                'description' => __('Where games happen — physical venue address or the online room. Add one row per site.', 'owbn-chronicle-manager'),
                'fields' => [
                    'name' => [
                        'label' => __('Site Name', 'owbn-chronicle-manager'),
                        'type' => 'text',
                        'required' => true,
                    ],
                    'online' => [
                        'label' => __('Online?', 'owbn-chronicle-manager'),
                        'type'  => 'boolean',
                    ],
                    'url' => [
                        'label' => __('Online URL', 'owbn-chronicle-manager'),
                        'type'  => 'url',
                    ],
                    'country' => [
                        'label' => __('Country', 'owbn-chronicle-manager'),
                        'type'  => 'select',
                        'options' => owbn_get_country_list(),
                        'search'  => true, // enable Select2 search
                    ],
                    'region' => [
                        'label' => __('State / Province / Region', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'city' => [
                        'label' => __('City / Municipality', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'address' => [
                        'label' => __('Street Address (optional)', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'notes' => [
                        'label' => __('Game Site Notes', 'owbn-chronicle-manager'),
                        'type'  => 'wysiwyg',
                    ],
                ]
            ],
            'web_url' => [
                'label' => __('Website URL', 'owbn-chronicle-manager'),
                'type' => 'url',
                'description' => __('Main public website for the chronicle, if there is one.', 'owbn-chronicle-manager'),
            ],
            'social_urls' => [
                'label'  => __('Social Media URLs', 'owbn-chronicle-manager'),
                'type'   => 'social_links_group',
                'description' => __('Discord servers, Facebook groups, etc. Add one entry per platform.', 'owbn-chronicle-manager'),
                'fields' => [
                    'platform' => [
                        'label'   => __('Platform', 'owbn-chronicle-manager'),
                        'type'    => 'select',
                        // Discord first — many chronicles run everything on Discord.
                        'options' => [
                            'discord'    => 'Discord',
                            'facebook'   => 'Facebook',
                            'twitter'    => 'Twitter (X)',
                            'instagram'  => 'Instagram',
                            'linkedin'   => 'LinkedIn',
                            'youtube'    => 'YouTube',
                            'tiktok'     => 'TikTok',
                            'twitch'     => 'Twitch',
                            'reddit'     => 'Reddit',
                            'threads'    => 'Threads',
                            'mastodon'   => 'Mastodon',
                            'bluesky'    => 'Bluesky',
                            'custom'     => 'Other',
                        ],
                        'required' => true,
                    ],
                    'url' => [
                        'label'    => __('Profile URL', 'owbn-chronicle-manager'),
                        'type'     => 'url',
                        'required' => true,
                    ],
                ],
            ],
        ],
        // ── Tab Three ──────────────────────────────────────────────────
        'Premise, Travel & Lists' => [
            'premise' => [
                'label' => __('Premise', 'owbn-chronicle-manager'),
                'type' => 'wysiwyg',
                'description' => __('The chronicle\'s setting and storyline pitch — what new players read first.', 'owbn-chronicle-manager'),
            ],
            'traveler_info' => [
                'label' => __('Information for Travellers', 'owbn-chronicle-manager'),
                'type' => 'wysiwyg',
                'description' => __('General info for visiting players — site details, how to submit characters, anything they need to know.', 'owbn-chronicle-manager'),
            ],
            'email_lists' => [
                'label' => __('Staff Lists', 'owbn-chronicle-manager'),
                'type' => 'email_lists_group',
                'description' => __('Public-facing staff distribution lists — hst@, staff@, etc.', 'owbn-chronicle-manager'),
                'fields' => [
                    'list_name' => [
                        'label' => __('List Name', 'owbn-chronicle-manager'),
                        'type' => 'text',
                        'required' => true,
                    ],
                    'email_address' => [
                        'label' => __('Email Address', 'owbn-chronicle-manager'),
                        'type' => 'email',
                    ],
                    'description' => [
                        'label' => __('Description', 'owbn-chronicle-manager'),
                        'type' => 'wysiwyg',
                    ],
                ],
            ],
            'player_lists' => [
                'label' => __('Player Lists', 'owbn-chronicle-manager'),
                'type'  => 'player_lists_group',
                'description' => __('Player communication lists or channels. Mark each IC vs OOC, and set Access — Public means anyone can join, Private means a moderator has to approve them.', 'owbn-chronicle-manager'),
                'fields' => [
                    'list_name' => [
                        'label' => __('List Name', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'access' => [
                        'label'   => __('Access', 'owbn-chronicle-manager'),
                        'type'    => 'select',
                        'options' => ['Public' => 'Public', 'Private' => 'Private'],
                    ],
                    'address' => [
                        'label' => __('Address', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'ic_ooc' => [
                        'label'   => __('IC/OOC', 'owbn-chronicle-manager'),
                        'type'    => 'select',
                        'options' => ['IC' => 'In Character', 'OOC' => 'Out of Character'],
                    ],
                    'moderate_address' => [
                        'label' => __('Moderator Email', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'signup_url' => [
                        'label' => __('Sign Up URL', 'owbn-chronicle-manager'),
                        'type'  => 'url',
                    ],
                ],
            ],
        ],
        // ── Tab 5: Administrative ──────────────────────────────────────
        'Administrative' => [
            'hst_selection' => [
                'label' => __('HST Selection Method', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['--Select Option--', 'Player Vote', 'HST Appointed', 'Vote/Consensus of Staff', 'Other'],
                'description' => __('How the HST is chosen at this chronicle.', 'owbn-chronicle-manager'),
            ],
            'cm_selection' => [
                'label' => __('CM Selection Method', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['--Select Option--', 'Player Vote', 'HST Appointed', 'Vote/Consensus of Staff', 'Other'],
                'description' => __('How the CM is chosen.', 'owbn-chronicle-manager'),
            ],
            'ast_selection' => [
                'label' => __('AST Selection Method', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['--Select Option--', 'Player Vote', 'HST Appointed', 'Vote/Consensus of Staff', 'Other'],
                'description' => __('How ASTs are chosen.', 'owbn-chronicle-manager'),
            ],
            'chronicle_start_date' => [
                'label' => __('Start Date', 'owbn-chronicle-manager'),
                'type' => 'date',
                'description' => __('Date the chronicle first opened.', 'owbn-chronicle-manager'),
            ],
            'chronicle_region' => [
                'label'   => __('Region', 'owbn-chronicle-manager'),
                'type'    => 'select',
                'options' => get_option('owbn_region_list', []),
                'description' => __('Geographic region — bookkeeping only.', 'owbn-chronicle-manager'),
            ],
            'staff_history' => [
                'label' => __('Previous Staff', 'owbn-chronicle-manager'),
                'type'  => 'readonly_history',
                'description' => __('Read-only log of past staff. Updated automatically when staff change.', 'owbn-chronicle-manager'),
                'columns' => [
                    'role'         => __('Role', 'owbn-chronicle-manager'),
                    'display_name' => __('Name', 'owbn-chronicle-manager'),
                    'actual_email' => __('Email', 'owbn-chronicle-manager'),
                    'start_date'   => __('Start', 'owbn-chronicle-manager'),
                    'end_date'     => __('End', 'owbn-chronicle-manager'),
                ],
            ],
        ],
    ];
}

function owbn_get_coordinator_field_definitions()
{
    return [
        'Basic Info' => [
            'coordinator_slug' => [
                'label' => __('Coordinator Slug', 'owbn-chronicle-manager'),
                'type'  => 'text',
                'description' => __('Short ID for this office, used in URLs (e.g. "sabbat", "treasurer"). Lowercase letters, numbers, dashes only. Locked once saved.', 'owbn-chronicle-manager'),
            ],
            'coordinator_title' => [
                'label' => __('Office Title', 'owbn-chronicle-manager'),
                'type'  => 'text',
                'description' => __('Display name of the office, e.g. "Sabbat Coordinator" or "Coordinator of Operations".', 'owbn-chronicle-manager'),
            ],
            'term_start_date' => [
                'label' => __('Term Start Date', 'owbn-chronicle-manager'),
                'type'  => 'date',
                'description' => __('When the current coordinator\'s term started.', 'owbn-chronicle-manager'),
            ],
            'term_end_date' => [
                'label' => __('Term End Date', 'owbn-chronicle-manager'),
                'type'  => 'date',
                'description' => __('When the current term ends. Update on re-election or appointment.', 'owbn-chronicle-manager'),
            ],
            'web_url' => [
                'label' => __('Website URL', 'owbn-chronicle-manager'),
                'type'  => 'text',
                'description' => __('Office website, if any.', 'owbn-chronicle-manager'),
            ],
            'coordinator_appointment' => [
                'label'   => __('Appointment', 'owbn-chronicle-manager'),
                'type'    => 'select',
                'options' => ['' => '-- Select --', 'Elected' => 'Elected', 'Appointed' => 'Appointed'],
                'description' => __('How the coordinator gets the seat — elected by chronicles, or appointed by the Head Coordinator.', 'owbn-chronicle-manager'),
            ],
            'coordinator_type' => [
                'label'   => __('Type', 'owbn-chronicle-manager'),
                'type'    => 'select',
                'options' => ['' => '-- Select --', 'Administrative' => 'Administrative', 'Genre' => 'Genre', 'Clan' => 'Clan'],
                'description' => __('Set by admin or the Web Coord. Determines how the office is categorized.', 'owbn-chronicle-manager'),
            ],
            'hosting_chronicle' => [
                'label' => __('Hosting Chronicle', 'owbn-chronicle-manager'),
                'type'  => 'chronicle_select',
                'description' => __('Required for Genre and Clan coordinators — the chronicle hosting this office. Leave blank for Administrative coordinators.', 'owbn-chronicle-manager'),
            ],
        ],
        // ── Description tab ────────────────────────────────────────────
        // Empty group → renderer hosts wp_editor() for post_content here.
        // Replaces the former office_description WYSIWYG; clients that read
        // `office_description` keep working via the save_post mirror hook.
        'Description' => [
            '__description__' => __('Describe the office — what it does, scope, current focus. Shown on the public coordinator page.', 'owbn-chronicle-manager'),
        ],
        'Coordinator' => [
            'coord_info' => [
                'label' => __('Coordinator', 'owbn-chronicle-manager'),
                'type'  => 'user_info',
                'description' => __('The current coordinator. Pick the WP user — name and email pull from their profile.', 'owbn-chronicle-manager'),
            ],
        ],
        'Staff' => [
            'subcoord_list' => [
                'label' => __('Sub-Coordinators', 'owbn-chronicle-manager'),
                'type'  => 'ast_group',
                'description' => __('Sub-coordinators or office staff. Add one row per person. Granting roles here also grants them in the system.', 'owbn-chronicle-manager'),
                'fields' => [
                    'user' => [
                        'label' => __('User', 'owbn-chronicle-manager'),
                        'type'  => 'user',
                    ],
                    'display_name' => [
                        'label' => __('Display Name', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'role' => [
                        'label' => __('Role/Title', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'actual_email' => [
                        'label' => __('Actual Email', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'display_email' => [
                        'label' => __('Display Email', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                ],
            ],
        ],
        'History' => [
            'coordinator_history' => [
                'label' => __('Previous Coordinators', 'owbn-chronicle-manager'),
                'type'  => 'readonly_history',
                'columns' => [
                    'display_name'    => __('Name', 'owbn-chronicle-manager'),
                    'actual_email'    => __('Email', 'owbn-chronicle-manager'),
                    'term_start_date' => __('Term Start', 'owbn-chronicle-manager'),
                    'term_end_date'   => __('Term End', 'owbn-chronicle-manager'),
                ],
            ],
        ],
        'Links' => [
            'document_links' => [
                'label' => __('Document Links', 'owbn-chronicle-manager'),
                'type'  => 'document_links_group',
                'fields' => [
                    'title' => [
                        'label' => __('Title', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'link' => [
                        'label' => __('External URL', 'owbn-chronicle-manager'),
                        'type'  => 'url',
                    ],
                    'upload' => [
                        'label' => __('Upload File', 'owbn-chronicle-manager'),
                        'type'  => 'file',
                    ],
                    'last_updated' => [
                        'label' => __('Last Updated', 'owbn-chronicle-manager'),
                        'type'  => 'date',
                    ],
                ],
            ],
            'email_lists' => [
                'label' => __('Staff Lists', 'owbn-chronicle-manager'),
                'type'  => 'email_lists_group',
                'fields' => [
                    'list_name' => [
                        'label' => __('List Name', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'email_address' => [
                        'label' => __('Email Address', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'description' => [
                        'label' => __('Description', 'owbn-chronicle-manager'),
                        'type'  => 'textarea',
                    ],
                ],
            ],
            'player_lists' => [
                'label' => __('Player Lists', 'owbn-chronicle-manager'),
                'type'  => 'player_lists_group',
                'description' => __('Player communication lists or channels. Mark each IC vs OOC, and set Access — Public means anyone can join, Private means a moderator has to approve them.', 'owbn-chronicle-manager'),
                'fields' => [
                    'list_name' => [
                        'label' => __('List Name', 'owbn-chronicle-manager'),
                        'type'  => 'text',
                    ],
                    'access' => [
                        'label'   => __('Access', 'owbn-chronicle-manager'),
                        'type'    => 'select',
                        'options' => ['Public' => 'Public', 'Private' => 'Private'],
                    ],
                    'address' => [
                        'label' => __('Address', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'ic_ooc' => [
                        'label'   => __('IC/OOC', 'owbn-chronicle-manager'),
                        'type'    => 'select',
                        'options' => ['IC' => 'In Character', 'OOC' => 'Out of Character'],
                    ],
                    'moderate_address' => [
                        'label' => __('Moderator Email', 'owbn-chronicle-manager'),
                        'type'  => 'email',
                    ],
                    'signup_url' => [
                        'label' => __('Sign Up URL', 'owbn-chronicle-manager'),
                        'type'  => 'url',
                    ],
                ],
            ],
        ],
    ];
}
