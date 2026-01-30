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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_name', 50);
            $table->string('phone_number', 15);
            $table->string('address_line1', 100);
            $table->string('address_line2', 50)->nullable();
            $table->string('province', 50);
            $table->string('city', 50);
            $table->string('postal_code', 10);
            $table->unsignedBigInteger('site_user_id');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->foreign('site_user_id')->references('id')->on('site_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
