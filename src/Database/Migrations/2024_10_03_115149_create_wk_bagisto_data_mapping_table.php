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
        Schema::create('wk_bagisto_data_mapping', function (Blueprint $table) {
            $table->increments('id');
            $table->string('related_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('code')->nullable();
            $table->unsignedInteger('job_instance_id')->nullable();
            $table->foreign('job_instance_id')->references('id')->on('job_track_batches')->onDelete('cascade');
            $table->unsignedInteger('credential_id')->nullable();
            $table->foreign('credential_id')->references('id')->on('wk_bagisto_credential')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_bagisto_data_mapping');
    }
};
