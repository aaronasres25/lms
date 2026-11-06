<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Change my own password (also the forced screen after an admin reset). */
class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();

        return redirect($user->homeUrl())->with('success', 'Password updated.');
    }
}
