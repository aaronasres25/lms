<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Str;

/** Creates the login (and the library membership) for a student or teacher. */
class PersonAccounts
{
    /** @return array{0:User,1:string} [user, plain password] */
    public static function create(string $role, string $name, string $email, ?string $password, int $maxBooks): array
    {
        $plain = $password ?: Str::password(10, true, true, false);

        $user = User::create([
            'name' => $name, 'email' => $email, 'password' => $plain, 'role' => $role,
            'active' => true, 'must_change_password' => true,
        ]);

        // library membership so they can borrow books and read e-books like everyone else
        $year = AcademicYear::current();
        $end = $year && $year->end_date->gte(today()) ? $year->end_date : today()->addYear();
        Member::create(['name' => $name, 'user_id' => $user->id, 'membership_start' => today(), 'membership_end' => $end, 'max_books' => $maxBooks]);

        return [$user, $plain];
    }
}
