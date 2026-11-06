<?php

return [
    'company_name'         => env('LMS_COMPANY', 'Miller Academic Library'),
    'loan_period_days'     => 14,   // max loan period
    'lost_fine_multiplier' => 5,    // policy: 5x daily fine when a book is lost
    'approval_valid_years' => 2,    // approved documents expire after N years
    'default_max_books'    => 5,

    // Approval chain: administrators may approve/reject any pending step (prevents a stuck document when an assignee is away)
    'approval_admin_override' => true,

    // Shown on the public Contact page
    'contact' => [
        'email'   => env('LMS_CONTACT_EMAIL', 'library@example.com'),
        'phone'   => env('LMS_CONTACT_PHONE', '+251 000 000 000'),
        'address' => env('LMS_CONTACT_ADDRESS', 'Head Office Library, Addis Ababa, Ethiopia'),
        'hours'   => env('LMS_CONTACT_HOURS', 'Mon - Fri, 8:30 AM - 5:30 PM'),
    ],
    'enable_activity_check' => env('LMS_ACTIVITY_CHECK', false),

    // Production bootstrap: DatabaseSeeder creates this admin when APP_ENV=production (read via config, not env())
    'admin_name'     => env('ADMIN_NAME', 'Administrator'),
    'admin_email'    => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),

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

        // ---- school management ----
        'students'      => ['label' => 'Students',          'actions' => ['read', 'create', 'update', 'delete']],
        'teachers'      => ['label' => 'Teachers',          'actions' => ['read', 'create', 'update', 'delete']],
        'academics'     => ['label' => 'Classes & setup',   'actions' => ['read', 'create', 'update', 'delete']],
        'attendance'    => ['label' => 'Attendance',        'actions' => ['read', 'update']],
        'timetable'     => ['label' => 'Timetable',         'actions' => ['read', 'update']],
        'exams'         => ['label' => 'Exams & marks',     'actions' => ['read', 'create', 'update', 'delete']],
        'fees'          => ['label' => 'Fees',              'actions' => ['read', 'create', 'update', 'delete']],
        'announcements' => ['label' => 'Announcements',     'actions' => ['read', 'create', 'update', 'delete']],
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

        // school modules: administrators by default - delegate to staff from Access rights
        'students'      => ['read' => 'admin', 'create' => 'admin', 'update' => 'admin', 'delete' => 'admin'],
        'teachers'      => ['read' => 'admin', 'create' => 'admin', 'update' => 'admin', 'delete' => 'admin'],
        'academics'     => ['read' => 'admin', 'create' => 'admin', 'update' => 'admin', 'delete' => 'admin'],
        'attendance'    => ['read' => 'admin', 'update' => 'admin'],
        'timetable'     => ['read' => 'admin', 'update' => 'admin'],
        'exams'         => ['read' => 'admin', 'create' => 'admin', 'update' => 'admin', 'delete' => 'admin'],
        'fees'          => ['read' => 'admin', 'create' => 'admin', 'update' => 'admin', 'delete' => 'admin'],
        'announcements' => ['read' => 'admin', 'create' => 'admin', 'update' => 'admin', 'delete' => 'admin'],
    ],
];
