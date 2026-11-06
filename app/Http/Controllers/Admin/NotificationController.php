<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->user()->notifications();
        if ($request->query('filter') === 'unread') $q->whereNull('read_at');

        return view('admin.notifications.index', [
            'layout' => $request->user()->isStaff() ? 'layouts.admin' : 'layouts.portal',
            'np'     => $request->user()->isStaff() ? 'admin.notifications' : ($request->user()->isTeacher() ? 'teacher.notifications' : 'student.notifications'),
            'items'  => $q->paginate($this->perPage($request, 20))->withQueryString(),
            'unread' => $request->user()->notifications()->whereNull('read_at')->count(),
        ]);
    }

    /** Clicking a notification marks it as read, then goes to the record. */
    public function open(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        return redirect($n->data['url'] ?? route('admin.dashboard'));
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();
        return back();
    }

    public function readAll(Request $request)
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'All notifications marked as read.');
    }
}
