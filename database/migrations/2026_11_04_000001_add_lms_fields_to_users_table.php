<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // reader < user < qa < manager < admin   (Odoo: portal/public < User < QA < Manager < Administrator)
            $table->string('role', 20)->default('reader')->after('password');
            $table->boolean('active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'active', 'last_login_at']);
        });
    }
};
