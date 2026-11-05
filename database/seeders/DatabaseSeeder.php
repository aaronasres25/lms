<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo accounts - one per role (CHANGE THESE PASSWORDS in production!)
        $users = [];
        foreach (['admin' => 'Administrator', 'manager' => 'Library Manager', 'qa' => 'Library QA', 'user' => 'Library Clerk', 'reader' => 'Demo Reader'] as $role => $name) {
            $users[$role] = User::firstOrCreate(['email' => "{$role}@lms.test"], [
                'name' => $name, 'password' => 'password', 'role' => $role,
            ]);
        }

        // Departments (library_department_data.xml)
        $tree = [
            'Production' => [], 'Fabrication' => [],
            'ICT' => ['System Development', 'Infrastructure', 'Customer Support', 'CCTV Operator'],
            'Finance' => ['General Finance' => ['Registry'], 'Closing Finance'],
            'Commerce' => [], 'Coffee' => [], 'Purchase' => [], 'Documentation' => [], 'Digital Marketing' => [],
            'HR' => [], 'Management' => [], 'Scrap' => [], 'Security' => [], 'Drivers' => [],
        ];
        $this->seedDepartments($tree, null);

        foreach (['Policy', 'Procedure', 'Manual', 'Technical Guide', 'Report', 'Training Material'] as $c) {
            Category::firstOrCreate(['name' => $c]);
        }

        $author = Author::firstOrCreate(['name' => 'AMG Documentation Team']);

        // One released demo book so the catalogue/loans are usable straight away
        $book = Book::firstOrCreate(
            ['name' => 'Employee Handbook', 'publication_year' => 2025, 'version' => '1.0',
             'department_id' => Department::where('name', 'HR')->value('id'), 'category_id' => Category::where('name', 'Policy')->value('id')],
            ['author_id' => $author->id, 'copies_total' => 3, 'fine_per_day' => 2.50, 'state' => 'released',
             'approval_date' => today(), 'expiry_date' => today()->addYears(2), 'description' => 'Demo physical copy of the employee handbook.']
        );

        Member::firstOrCreate(['name' => 'Demo Reader', 'user_id' => $users['reader']->id], [
            'membership_start' => today(), 'membership_end' => today()->addYear(), 'max_books' => 5,
        ]);
    }

    private function seedDepartments(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $key => $value) {
            $name = is_int($key) ? $value : $key;
            $dept = Department::firstOrCreate(['name' => $name, 'parent_id' => $parentId]);
            if (! is_int($key) && is_array($value)) {
                $this->seedDepartments($value, $dept->id);
            }
        }
    }
}
