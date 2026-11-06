<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // admin-reset passwords force a change at next login
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('must_change_password')->default(false)->after('active');
        });

        // Laravel database notifications (in-app bell) - read_at makes "mark as read" persistent
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        // Approval flow templates configured by the admin: per department (or default when department is null)
        Schema::create('approval_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $t->unsignedTinyInteger('sequence');
            $t->string('title');
            $t->string('role', 20)->nullable();                       // any active user with this role...
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();   // ...or one specific person
            $t->timestamps();
        });

        // Snapshot of the chain for every submission round of a book
        Schema::create('book_approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('cycle')->default(1);
            $t->unsignedTinyInteger('sequence');
            $t->string('title');
            $t->string('role', 20)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('pending');             // pending | approved | rejected | skipped
            $t->boolean('is_current')->default(false);                // the step whose turn it is
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('acted_at')->nullable();
            $t->text('comment')->nullable();
            $t->timestamps();
            $t->index(['book_id', 'cycle']);
            $t->index(['is_current', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_approvals');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('notifications');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('must_change_password'));
    }
};
