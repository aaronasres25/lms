<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Admin\MasterController;
use App\Models\Announcement;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnnouncementController extends MasterController
{
    protected function modelClass(): string { return Announcement::class; }
    protected function title(): string { return 'Announcement'; }
    protected function routePrefix(): string { return 'admin.school.announcements'; }
    protected function permResource(): string { return 'announcements'; }
    protected function searchColumn(): string { return 'title'; }
    protected function orderColumn(): string { return 'id'; }
    protected function orderDirection(): string { return 'desc'; }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'audience', 'label' => 'Audience', 'type' => 'select', 'required' => true, 'options' => ['all' => 'Everyone', 'students' => 'Students', 'teachers' => 'Teachers']],
            ['name' => 'body', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
            ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $record): array
    {
        return ['title' => ['required', 'string', 'max:150'], 'audience' => ['required', 'in:all,students,teachers'],
                'body' => ['required', 'string', 'max:5000'], 'is_published' => ['nullable', 'boolean']];
    }

    protected function prepare(array &$data, Request $request): void
    {
        $data['is_published'] = $request->boolean('is_published');
        $data['published_by'] = $request->user()->id;
    }

    /** New published notice -> in-app notification for its audience. */
    protected function afterSave(Model $record, bool $isNew): void
    {
        if (! $isNew || ! $record->is_published) return;

        $roles = match ($record->audience) {
            'students' => ['student'], 'teachers' => ['teacher'], default => ['student', 'teacher', 'user', 'qa', 'manager', 'admin'],
        };
        Notify::send(User::where('active', true)->whereIn('role', $roles)->get(), 'Announcement: ' . $record->title,
            Str::limit($record->body, 100), route('home', [], false), 'bell', 'amber');
    }

    protected function extraColumns(): array
    {
        return ['Audience' => fn ($r) => ucfirst($r->audience), 'Status' => fn ($r) => $r->is_published ? 'published' : 'draft',
                'Posted' => fn ($r) => $r->created_at->format('Y-m-d')];
    }
}
