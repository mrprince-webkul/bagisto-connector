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
        Schema::table('wk_bagisto_attribute_config_mapping', function (Blueprint $table) {
            $table->json('additional_info')->nullable()->after('fixed_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wk_bagisto_attribute_config_mapping', function (Blueprint $table) {
            $table->dropColumn('additional_info');
        });
    }
};
