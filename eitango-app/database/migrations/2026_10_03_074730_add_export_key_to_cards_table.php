<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->uuid('export_key')->nullable()->after('deck_id');
        });

        DB::table('cards')->whereNull('export_key')->orderBy('id')->each(function ($card) {
            DB::table('cards')
                ->where('id', $card->id)
                ->update(['export_key' => (string) Str::uuid()]);
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->uuid('export_key')->nullable(false)->change();
            $table->unique('export_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropUnique(['export_key']);
            $table->dropColumn('export_key');
        });
    }
};
