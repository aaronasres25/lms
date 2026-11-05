<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.feedback.index', ['items' => Feedback::latest('id')->paginate($this->perPage($request, 10))->withQueryString()]);
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();
        return back()->with('success', 'Feedback deleted.');
    }
}
