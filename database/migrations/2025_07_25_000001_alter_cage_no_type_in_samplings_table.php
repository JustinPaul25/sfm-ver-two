<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Clean non-numeric data
        if ($driver === 'mysql') {
            DB::statement("UPDATE samplings SET cage_no = NULL WHERE cage_no NOT REGEXP '^[0-9]+$'");
        } elseif ($driver === 'pgsql') {
            DB::statement("UPDATE samplings SET cage_no = NULL WHERE cage_no !~ '^[0-9]+$'");
        }

        // Change column type
        if ($driver === 'mysql') {
            Schema::table('samplings', function (Blueprint $table) {
                $table->unsignedBigInteger('cage_no')->change();
            });
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE samplings ALTER COLUMN cage_no TYPE bigint USING cage_no::bigint");
        }

        // Add foreign key
        Schema::table('samplings', function (Blueprint $table) {
            $table->foreign('cage_no')->references('id')->on('cages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('samplings', function (Blueprint $table) {
            $table->dropForeign(['cage_no']);
            $table->string('cage_no')->change();
        });
    }
}; 