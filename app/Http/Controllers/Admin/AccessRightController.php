<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\AccessRight;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin screen: change a user's role / status and grant or revoke Read-Create-Update-Delete per module. */
class AccessRightController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when(trim((string) $request->query('q', '')), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->orderByRaw("case role when 'admin' then 0 when 'manager' then 1 when 'qa' then 2 when 'user' then 3 else 4 end")
            ->orderBy('name')->get();

        $selected = $request->filled('user') ? User::find($request->query('user')) : $users->first();

        return view('admin.access.index', [
            'users'      => $users,
            'selected'   => $selected,
            'customIds'  => AccessRight::distinct()->pluck('user_id')->all(),
            'resources'  => config('lms.resources'),
            'roles'      => array_keys(User::ROLES),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', Rule::in(array_keys(User::ROLES))],
        ]);
        $user = User::create($data);
        return redirect()->route('admin.access.index', ['user' => $user->id])->with('success', "User {$user->name} created.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(User::ROLES))]]);
        $active = $request->boolean('active');

        if ($user->id === $request->user()->id && ($data['role'] !== 'admin' || ! $active)) {
            throw new LmsException('You cannot demote or deactivate your own account.');
        }

        $user->update(['role' => $data['role'], 'active' => $active]);

        if ($user->role === 'admin') {
            $user->accessRights()->delete();          // admins are never restricted
        } elseif ($request->boolean('save_rights')) {
            foreach (config('lms.resources') as $resource => $meta) {
                $flag = fn (string $a) => in_array($a, $meta['actions'], true) && $request->boolean("rights.{$resource}.{$a}");
                $c = $flag('create'); $u = $flag('update'); $d = $flag('delete');
                $r = $flag('read') || $c || $u || $d;  // can't create/edit/delete what you can't see
                AccessRight::updateOrCreate(['user_id' => $user->id, 'resource' => $resource],
                    ['can_read' => $r, 'can_create' => $c, 'can_update' => $u, 'can_delete' => $d]);
            }
        }

        return redirect()->route('admin.access.index', ['user' => $user->id])->with('success', "Access updated for {$user->name}.");
    }

    public function reset(User $user)
    {
        $user->accessRights()->delete();
        return redirect()->route('admin.access.index', ['user' => $user->id])->with('success', "{$user->name} is back on the default rights of the “{$user->role}” role.");
    }
}
