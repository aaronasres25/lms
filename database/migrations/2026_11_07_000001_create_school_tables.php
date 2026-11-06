<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $t) {
            $t->id();
            $t->string('name');                       // 2026/2027
            $t->date('start_date');
            $t->date('end_date');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });

        Schema::create('terms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->string('name');                       // Term 1
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->timestamps();
        });

        Schema::create('grade_levels', function (Blueprint $t) {
            $t->id();
            $t->string('name');                       // Grade 5
            $t->unsignedSmallInteger('level_order')->default(0);
            $t->timestamps();
        });

        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 20)->nullable();
            $t->timestamps();
        });

        Schema::create('teachers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_no')->unique();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('qualification')->nullable();
            $t->date('joined_on')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });

        // a "section" is one class group: Grade 5 - A in a given academic year
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->string('name');                       // A
            $t->foreignId('class_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $t->unsignedSmallInteger('capacity')->default(40);
            $t->string('room')->nullable();
            $t->timestamps();
            $t->unique(['grade_level_id', 'academic_year_id', 'name']);
        });

        // which subject is taught in which section, and by whom
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $t->timestamps();
            $t->unique(['section_id', 'subject_id']);
        });

        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('admission_no')->unique();
            $t->string('name');
            $t->string('gender', 10)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->string('guardian_name')->nullable();
            $t->string('guardian_phone')->nullable();
            $t->string('guardian_email')->nullable();
            $t->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status', 20)->default('active');      // active | graduated | transferred | inactive
            $t->date('admitted_on')->nullable();
            $t->timestamps();
        });

        // class history (written on admission / promotion)
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->string('result', 30)->nullable();
            $t->timestamps();
            $t->unique(['student_id', 'academic_year_id']);
        });

        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->string('status', 10);                 // present | absent | late | excused
            $t->string('remark')->nullable();
            $t->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['student_id', 'date']);
            $t->index(['section_id', 'date']);
        });

        Schema::create('timetable_slots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('day_of_week');   // 1 = Monday
            $t->unsignedTinyInteger('period_no');
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $t->string('room')->nullable();
            $t->timestamps();
            $t->unique(['section_id', 'day_of_week', 'period_no']);
        });

        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $t->string('name');
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->unsignedSmallInteger('max_marks')->default(100);
            $t->boolean('is_published')->default(false);     // students see results only when published
            $t->timestamps();
        });

        Schema::create('exam_marks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->decimal('marks', 6, 2)->nullable();
            $t->string('remark')->nullable();
            $t->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['exam_id', 'student_id', 'subject_id']);
        });

        Schema::create('grade_scales', function (Blueprint $t) {
            $t->id();
            $t->string('name');                       // A, B, C ...
            $t->decimal('min_percent', 5, 2);
            $t->string('remark')->nullable();
            $t->timestamps();
        });

        Schema::create('fee_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->decimal('amount', 10, 2)->default(0);
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('fee_invoices', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $t->string('description')->nullable();
            $t->decimal('total', 10, 2)->default(0);
            $t->date('due_date')->nullable();
            $t->string('status', 10)->default('unpaid');      // unpaid | partial | paid | void
            $t->timestamps();
            $t->index('status');
        });

        Schema::create('fee_invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fee_invoice_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fee_type_id')->nullable()->constrained()->nullOnDelete();
            $t->string('description');
            $t->decimal('amount', 10, 2);
            $t->timestamps();
        });

        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fee_invoice_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 10, 2);
            $t->date('paid_on');
            $t->string('method', 20)->default('cash');
            $t->string('reference')->nullable();
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->string('audience', 20)->default('all');       // all | students | teachers
            $t->boolean('is_published')->default(true);
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['announcements', 'fee_payments', 'fee_invoice_items', 'fee_invoices', 'fee_types', 'grade_scales', 'exam_marks', 'exams',
                  'timetable_slots', 'attendances', 'enrollments', 'students', 'section_subjects', 'sections', 'teachers', 'subjects',
                  'grade_levels', 'terms', 'academic_years'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
