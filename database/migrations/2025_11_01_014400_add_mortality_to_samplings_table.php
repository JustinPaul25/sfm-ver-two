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
            $table->integer('mortality')->default(0)->after('cage_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('samplings', function (Blueprint $table) {
            $table->dropColumn('mortality');
        });
    }
};
