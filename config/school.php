<?php

return [
    // timetable grid
    'days' => [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'],
    'periods' => [
        1 => ['08:00', '08:45'], 2 => ['08:50', '09:35'], 3 => ['09:40', '10:25'], 4 => ['10:45', '11:30'],
        5 => ['11:35', '12:20'], 6 => ['13:20', '14:05'], 7 => ['14:10', '14:55'],
    ],

    'attendance_statuses' => ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'excused' => 'Excused'],

    // library: members created automatically for students / teachers who get a login
    'student_max_books' => 3,
    'teacher_max_books' => 10,

    'student_statuses' => ['active' => 'Active', 'graduated' => 'Graduated', 'transferred' => 'Transferred', 'inactive' => 'Inactive'],
];
