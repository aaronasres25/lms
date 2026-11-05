<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Per-user CRUD overrides. No row for a resource = the user's role defaults apply.
        Schema::create('access_rights', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('resource', 40);
            $t->boolean('can_read')->default(false);
            $t->boolean('can_create')->default(false);
            $t->boolean('can_update')->default(false);
            $t->boolean('can_delete')->default(false);
            $t->timestamps();
            $t->unique(['user_id', 'resource']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_rights');
    }
};
