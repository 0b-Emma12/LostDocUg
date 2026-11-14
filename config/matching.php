<?php

return [
    'thresholds' => [
        'auto_notify' => 85,
        'manual_review' => 60,
        'ignore_below' => 40,
    ],
    'document_types' => [
        'nira_id' => [
            'fields' => [
                'nin' => ['weight' => 50, 'match_type' => 'exact', 'required' => true],
                'name' => ['weight' => 30, 'match_type' => 'fuzzy', 'required' => false],
                'date_of_birth' => ['weight' => 20, 'match_type' => 'exact', 'required' => false],
            ],
            'rules' => [
                'nin_mismatch_blocks_high_confidence' => true,
                'min_fields_for_match' => 2,
            ],
        ],
        'passport' => [
            'fields' => [
                'passport_number' => ['weight' => 50, 'match_type' => 'exact', 'required' => true],
                'name' => ['weight' => 30, 'match_type' => 'fuzzy', 'required' => false],
                'date_of_birth' => ['weight' => 20, 'match_type' => 'exact', 'required' => false],
            ],
            'rules' => [
                'passport_number_mismatch_blocks_high_confidence' => true,
                'min_fields_for_match' => 2,
            ],
        ],
        'land_title' => [
            'fields' => [
                'title_reference' => ['weight' => 60, 'match_type' => 'exact', 'required' => true],
                'owner_name' => ['weight' => 30, 'match_type' => 'fuzzy', 'required' => false],
                'location' => ['weight' => 10, 'match_type' => 'fuzzy', 'required' => false],
            ],
            'rules' => [
                'always_require_manual_review' => true,
                'min_fields_for_match' => 2,
            ],
        ],
        'bank_card' => [
            'fields' => [],
            'rules' => [
                'use_institutional_referral' => true,
                'disable_ocr_matching' => true,
            ],
        ],
    ],
];
