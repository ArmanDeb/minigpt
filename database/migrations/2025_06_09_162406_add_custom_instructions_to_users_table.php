<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('about_you')->nullable()->after('preferred_model');
            $table->text('assistant_behavior')->nullable()->after('about_you');
            $table->json('custom_commands')->nullable()->after('assistant_behavior');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['about_you', 'assistant_behavior', 'custom_commands']);
        });
    }
};
