<?php

return [
    'company_name'         => env('LMS_COMPANY', 'AMG Holdings'),
    'loan_period_days'     => 14,   // max loan period
    'lost_fine_multiplier' => 5,    // policy: 5x daily fine when a book is lost
    'approval_valid_years' => 2,    // approved documents expire after N years
    'default_max_books'    => 5,
    'enable_activity_check' => env('LMS_ACTIVITY_CHECK', false),

    /* ---------------------------------------------------------------
     * Access rights
     * 'resources'   : what appears on the Access Rights screen and which CRUD actions exist
     * 'permissions' : DEFAULT minimum role per action (used when a user has no custom override)
     * Administrators always have full access and cannot be restricted.
     * --------------------------------------------------------------- */
    'resources' => [
        'books'       => ['label' => 'Books',       'actions' => ['read', 'create', 'update', 'delete']],
        'loans'       => ['label' => 'Loans',       'actions' => ['read', 'create', 'update', 'delete']],
        'members'     => ['label' => 'Members',     'actions' => ['read', 'create', 'update', 'delete']],
        'authors'     => ['label' => 'Authors',     'actions' => ['read', 'create', 'update', 'delete']],
        'categories'  => ['label' => 'Categories',  'actions' => ['read', 'create', 'update', 'delete']],
        'departments' => ['label' => 'Departments', 'actions' => ['read', 'create', 'update', 'delete']],
        'reports'     => ['label' => 'Reports',     'actions' => ['read']],
        'feedback'    => ['label' => 'Feedback',    'actions' => ['read', 'delete']],
    ],

    'permissions' => [
        'books'       => ['read' => 'user', 'create' => 'qa',      'update' => 'user',    'delete' => 'admin'],
        'loans'       => ['read' => 'user', 'create' => 'user',    'update' => 'user',    'delete' => 'admin'],
        'members'     => ['read' => 'user', 'create' => 'manager', 'update' => 'manager', 'delete' => 'admin'],
        'authors'     => ['read' => 'user', 'create' => 'admin',   'update' => 'admin',   'delete' => 'admin'],
        'categories'  => ['read' => 'user', 'create' => 'admin',   'update' => 'admin',   'delete' => 'admin'],
        'departments' => ['read' => 'user', 'create' => 'admin',   'update' => 'admin',   'delete' => 'admin'],
        'reports'     => ['read' => 'user'],
        'feedback'    => ['read' => 'admin', 'delete' => 'admin'],
    ],
];
