<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookApproval;
use Illuminate\Http\Request;

/** "My approvals": documents whose CURRENT step is assigned to me. */
class ApprovalQueueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('admin.approvals.queue', [
            'rows' => BookApproval::queueFor($user)->latest('updated_at')->paginate($this->perPage($request))->withQueryString(),
            'override' => $user->role === 'admin' && config('lms.approval_admin_override', true),
        ]);
    }
}
