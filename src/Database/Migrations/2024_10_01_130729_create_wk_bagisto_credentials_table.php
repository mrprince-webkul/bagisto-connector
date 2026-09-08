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
        Schema::create('wk_bagisto_credential', function (Blueprint $table) {
            $table->increments('id');
            $table->string('shop_url');
            $table->string('email');
            $table->string('password');
            $table->json('store_info')->nullable();
            $table->json('additional_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_bagisto_credential');
    }
};
