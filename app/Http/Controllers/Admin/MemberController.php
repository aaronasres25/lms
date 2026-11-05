<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::with('user')->withCount([
            'loans as issued_count'   => fn ($q) => $q->where('state', 'issued'),
            'loans as returned_count' => fn ($q) => $q->where('state', 'returned'),
            'loans as lost_count'     => fn ($q) => $q->where('state', 'lost'),
        ]);

        if ($s = trim((string) $request->query('q', ''))) $query->where('name', 'like', "%{$s}%");
        if ($request->query('status') === 'active') $query->where('membership_end', '>=', today()->toDateString());
        if ($request->query('status') === 'expired') $query->where('membership_end', '<', today()->toDateString());

        return view('admin.members.index', ['members' => $query->latest('id')->paginate($this->perPage($request))->withQueryString()]);
    }

    public function create()
    {
        return view('admin.members.form', [
            'member' => new Member(['membership_start' => today(), 'membership_end' => today()->addYear(), 'max_books' => config('lms.default_max_books', 5)]),
            'users'  => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $member = Member::create($this->validated($request));
        $member->logActivity("Member created by {$request->user()->name}.");
        return redirect()->route('admin.members.show', $member)->with('success', 'Member created.');
    }

    public function show(Member $member)
    {
        $member->load(['user', 'activities.user', 'loans' => fn ($q) => $q->with('book')->latest('id')]);
        return view('admin.members.show', ['member' => $member]);
    }

    public function edit(Member $member)
    {
        return view('admin.members.form', ['member' => $member, 'users' => User::orderBy('name')->get(['id', 'name', 'email'])]);
    }

    public function update(Request $request, Member $member)
    {
        $member->update($this->validated($request, $member));
        $member->logActivity("Member record updated by {$request->user()->name}.");
        return redirect()->route('admin.members.show', $member)->with('success', 'Member updated.');
    }

    public function destroy(Member $member)
    {
        if ($member->loans()->exists()) throw new LmsException('This member has loan records and cannot be deleted.');
        $member->delete();
        return redirect()->route('admin.members.index')->with('success', 'Member deleted.');
    }

    private function validated(Request $request, ?Member $member = null): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'user_id'          => ['nullable', 'exists:users,id'],
            'membership_start' => ['required', 'date'],
            'membership_end'   => ['required', 'date', 'after_or_equal:membership_start'],
            'max_books'        => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $dup = Member::where('name', $data['name'])->where('user_id', $data['user_id'] ?? null)
            ->when($member, fn ($q) => $q->where('id', '!=', $member->id))->exists();
        if ($dup) throw new LmsException("The member '{$data['name']}' is already registered for this user.");

        return $data;
    }
}
