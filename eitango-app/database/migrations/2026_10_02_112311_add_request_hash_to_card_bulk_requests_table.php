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
        Schema::table('card_bulk_requests', function (Blueprint $table) {
            $table->char('request_hash', 64)->nullable()->after('request_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_bulk_requests', function (Blueprint $table) {
            $table->dropColumn('request_hash');
        });
    }
};
