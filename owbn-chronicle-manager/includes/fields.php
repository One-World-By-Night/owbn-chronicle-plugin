<?php
function owbn_get_chronicle_field_definitions() {
    return [
        'Chronicle Details' => [
            'chronicle_slug' => [
                'label' => __('Chronicle Slug', 'owbn-chronicle-manager'),
                'type' => 'slug',
                'min_length' => 3,
                'max_length' => 6,
                'required' => true,
            ],
            'genres' => [
                'label' => __('Genres', 'owbn-chronicle-manager'),
                'type' => 'multi_select',
                'source' => 'owbn_genre_list',
                'required' => true,
            ],
            'game_type' => [
                'label' => __('Game Type', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['In-Person', 'Virtual', 'Hybrid'],
                'required' => true,
            ],
            'premise' => ['label' => __('Premise', 'owbn-chronicle-manager'), 'type' => 'wysiwyg'],
            'game_theme' => ['label' => __('Game Theme', 'owbn-chronicle-manager'), 'type' => 'wysiwyg'],
            'game_mood' => ['label' => __('Game Mood', 'owbn-chronicle-manager'), 'type' => 'wysiwyg'],
            'traveler_info' => ['label' => __('Information for Travellers', 'owbn-chronicle-manager'), 'type' => 'wysiwyg'],
            'active_player_count' => [
                'label' => __('Active Players', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['1-10', '11-20', '21-30', '31-40', '41-50', '51+']
            ],
            'session_list' => [
                'label' => __('Session List', 'owbn-chronicle-manager'),
                'type'  => 'session_group',
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
                ],
            ],
            'web_url' => ['label' => __('Website URL', 'owbn-chronicle-manager'), 'type' => 'url'],
        ],
        'Staff Information' => [
            'hst_selection' => [
                'label' => __('HST Selection Method', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['--Select Option--','Player Vote', 'HST Appointed', 'Vote/Consensus of Staff', 'Other']
            ],
            'cm_selection' => [
                'label' => __('CM Selection Method', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['--Select Option--','Player Vote', 'HST Appointed', 'Vote/Consensus of Staff', 'Other']
            ],
            'ast_selection' => [
                'label' => __('AST Selection Method', 'owbn-chronicle-manager'),
                'type' => 'select',
                'options' => ['--Select Option--','Player Vote', 'HST Appointed', 'Vote/Consensus of Staff', 'Other']
            ],
            'hst_info' => [
                'label' => __('HST Info', 'owbn-chronicle-manager'),
                'type' => 'user_info',
                'required' => true,
            ],
            'cm_info' => [
                'label' => __('CM Info', 'owbn-chronicle-manager'),
                'type' => 'user_info',
                'conditional_required' => 'chronicle_satellite=0', // custom logic
            ],
            'ast_list' => [
                'label' => __('AST List', 'owbn-chronicle-manager'),
                'type' => 'ast_group',
                'fields' => [
                    'user' => [
                        'label' => __('User', 'owbn-chronicle-manager'),
                        'type' => 'user',
                    ],
                    'display_name' => [
                        'label' => __('Display Name', 'owbn-chronicle-manager'),
                        'type' => 'text',
                    ],
                    'email' => [
                        'label' => __('Email', 'owbn-chronicle-manager'),
                        'type' => 'email',
                    ],
                    'role' => [
                        'label' => __('Role', 'owbn-chronicle-manager'),
                        'type' => 'text',
                    ],
                ],
            ],
            'admin_contact' => [
                'label' => __('Admin Contact', 'owbn-chronicle-manager'),
                'type' => 'user_info',
            ],
        ],
        'Locations' => [
            'ooc_locations' => [
                'label' => __('OOC Locations', 'owbn-chronicle-manager'),
                'type'  => 'location_group',
                'fields' => [
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
                    'notes' => [
                        'label' => __('Location Notes', 'owbn-chronicle-manager'),
                        'type'  => 'wysiwyg',
                    ],
                ]
            ],
            'ic_location_list' => [
                'label' => __('IC Locations', 'owbn-chronicle-manager'),
                'type'  => 'location_group',
                'fields' => [
                    'name' => [
                        'label' => __('Site Name', 'owbn-chronicle-manager'),
                        'type' => 'text',
                        'required' => true,
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
            'game_site_list' => [
                'label' => __('Game Sites', 'owbn-chronicle-manager'),
                'type'  => 'location_group',
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
            ]
        ],
        'Links' => [
            'document_links' => [
                'label' => __('Document Links', 'owbn-chronicle-manager'),
                'type'  => 'document_links_group',
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
                ],
            ],
            'social_urls' => [
                'label'  => __('Social Media URLs', 'owbn-chronicle-manager'),
                'type'   => 'social_links_group',
                'fields' => [
                    'platform' => [
                        'label'   => __('Platform', 'owbn-chronicle-manager'),
                        'type'    => 'select',
                        'options' => [
                            'facebook'   => 'Facebook',
                            'twitter'    => 'Twitter (X)',
                            'instagram'  => 'Instagram',
                            'linkedin'   => 'LinkedIn',
                            'youtube'    => 'YouTube',
                            'tiktok'     => 'TikTok',
                            'discord'    => 'Discord',
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
            'email_lists' => [
                'label' => __('Email Lists', 'owbn-chronicle-manager'),
                'type' => 'email_lists_group',
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
        ],
        'Metadata' => [
            'chronicle_start_date' => ['label' => __('Start Date', 'owbn-chronicle-manager'), 'type' => 'date'],
            'chronicle_region' => [
                'label'   => __('Region', 'owbn-chronicle-manager'),
                'type'    => 'select',
                'options' => get_option('owbn_region_list', []),
            ],
            'chronicle_probationary' => ['label' => __('Probationary?', 'owbn-chronicle-manager'), 'type' => 'boolean'],
            'chronicle_satellite' => ['label' => __('Satellite?', 'owbn-chronicle-manager'), 'type' => 'boolean'],
            'chronicle_parent' => [
                'label' => __('Parent Chronicle', 'owbn-chronicle-manager'),
                'type'  => 'chronicle_select',
                'source' => 'owbn_chronicle_list',
            ],
        ],
    ];
}