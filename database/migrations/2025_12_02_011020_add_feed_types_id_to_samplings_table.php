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
        Schema::table('samplings', function (Blueprint $table) {
            $table->foreignId('feed_types_id')->nullable()->after('cage_no')->constrained('feed_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('samplings', function (Blueprint $table) {
            $table->dropForeign(['feed_types_id']);
            $table->dropColumn('feed_types_id');
        });
    }
};
