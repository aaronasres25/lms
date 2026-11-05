<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ir.sequence replacement (loan numbers, ISBN generator)
        Schema::create('sequences', function (Blueprint $t) {
            $t->string('code')->primary();
            $t->unsignedBigInteger('next_number')->default(1);
        });

        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->foreignId('parent_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['name', 'parent_id']);
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('authors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('bio')->nullable();
            $t->timestamps();
        });

        Schema::create('books', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('isbn', 13)->unique();
            $t->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $t->unsignedSmallInteger('publication_year');
            $t->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $t->text('description')->nullable();
            $t->unsignedInteger('copies_total')->default(1);
            $t->decimal('fine_per_day', 10, 2)->default(0);
            $t->boolean('active')->default(true);

            // document lifecycle: draft -> review -> approved -> released -> obsolete
            $t->string('state', 20)->default('draft');
            $t->date('approval_date')->nullable();
            $t->date('expiry_date')->nullable();

            // versioning
            $t->string('version', 20)->default('1.0');
            $t->text('change_reason')->nullable();

            // e-book
            $t->boolean('is_ebook')->default(false);
            $t->string('ebook_path')->nullable();
            $t->string('ebook_filename')->nullable();
            $t->string('cover_path')->nullable();
            $t->boolean('is_secure_ebook')->default(false);
            $t->unsignedInteger('ebook_read_count')->default(0);
            $t->unsignedInteger('total_pages')->default(1);
            $t->timestamp('last_opened')->nullable();

            $t->timestamps();

            $t->unique(['name', 'publication_year', 'version', 'department_id', 'category_id'], 'book_identity_unique');
            $t->index('state');
        });

        Schema::create('book_version_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->string('version', 20);
            $t->text('change_reason');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('members', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->date('membership_start');
            $t->date('membership_end');
            $t->unsignedSmallInteger('max_books')->default(5);
            $t->timestamps();
            $t->unique(['name', 'user_id']);
        });

        Schema::create('loans', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();                       // LN00001
            $t->foreignId('book_id')->constrained()->restrictOnDelete();
            $t->foreignId('member_id')->constrained()->restrictOnDelete();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->date('loan_date');
            $t->date('date_expected')->nullable();
            $t->date('date_returned')->nullable();
            $t->string('state', 20)->default('draft');          // draft, issued, returned, lost
            $t->unsignedInteger('overdue_days')->default(0);
            $t->decimal('fine_amount', 10, 2)->default(0);
            $t->timestamps();
            $t->index('state');
        });

        // lms.viewer - per-user reading progress
        Schema::create('viewers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('current_page')->default(1);
            $t->timestamp('last_read_at')->useCurrent();
            $t->timestamps();
            $t->unique(['user_id', 'book_id']);
        });

        // lms.book.read.history - every open (guests allowed)
        Schema::create('read_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('category_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->timestamps();
            $t->index('created_at');
        });

        Schema::create('feedback', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->text('comment');
            $t->timestamps();
        });

        // chatter replacement (audit trail on books / loans / members)
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->morphs('loggable');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->text('body');
            $t->timestamps();
        });

        // res.users.log replacement - feeds the login report on the dashboard
        Schema::create('login_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
            $t->index('created_at');
        });

        // "Notification Tracking" tab on the book form
        Schema::create('book_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['book_notifications', 'login_logs', 'activity_logs', 'feedback', 'read_histories', 'viewers', 'loans',
                  'members', 'book_version_histories', 'books', 'authors', 'categories', 'departments', 'sequences'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
