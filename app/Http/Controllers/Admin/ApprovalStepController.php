<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\LmsException;
use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Admin: define WHO approves WHICH department's books, and in WHAT order. */
class ApprovalStepController extends Controller
{
    private function scope(Request $request): ?int
    {
        $d = $request->input('department', $request->query('department'));
        return ($d && $d !== 'default' && ctype_digit((string) $d)) ? (int) $d : null;
    }

    public function index(Request $request)
    {
        $dept = $this->scope($request);
        $counts = ApprovalStep::selectRaw('department_id, count(*) as c')->groupBy('department_id')->pluck('c', 'department_id');

        return view('admin.approvals.flows', [
            'deptId'      => $dept,
            'departments' => Department::options(),
            'counts'      => $counts,
            'defaultCount'=> (int) ($counts[''] ?? $counts[null] ?? ApprovalStep::whereNull('department_id')->count()),
            'steps'       => ApprovalStep::with('user')->where('department_id', $dept)->orderBy('sequence')->get(),
            'users'       => User::staff()->orderBy('name')->get(['id', 'name', 'role']),
            'roles'       => ApprovalStep::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $dept = $this->scope($request);

        ApprovalStep::create($data + [
            'department_id' => $dept,
            'sequence' => ((int) ApprovalStep::where('department_id', $dept)->max('sequence')) + 1,
        ]);
        return back()->with('success', 'Approval step added.');
    }

    public function update(Request $request, ApprovalStep $step)
    {
        $step->update($this->validated($request));
        return back()->with('success', 'Approval step updated.');
    }

    public function destroy(ApprovalStep $step)
    {
        $dept = $step->department_id;
        $step->delete();
        ApprovalStep::resequence($dept);
        return back()->with('success', 'Approval step removed.');
    }

    public function move(Request $request, ApprovalStep $step)
    {
        $up = $request->input('direction') === 'up';
        $other = ApprovalStep::where('department_id', $step->department_id)
            ->where('sequence', $up ? '<' : '>', $step->sequence)->orderBy('sequence', $up ? 'desc' : 'asc')->first();

        if ($other) {
            DB::transaction(function () use ($step, $other) {
                [$a, $b] = [$step->sequence, $other->sequence];
                $step->update(['sequence' => $b]);
                $other->update(['sequence' => $a]);
            });
        }
        return back();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'max:100'],
            'role'    => ['nullable', Rule::in(ApprovalStep::ROLES)],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        if (empty($data['role']) && empty($data['user_id'])) {
            throw new LmsException('Choose who approves this step: a role, or a specific person.');
        }
        // a named person wins over the role
        if (! empty($data['user_id'])) $data['role'] = null;
        $data['user_id'] = $data['user_id'] ?? null;
        $data['role'] = $data['role'] ?? null;

        return $data;
    }
}
