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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_user_id');
            $table->unsignedBigInteger('address_id');
            $table->string('order_number')->unique();
            $table->integer('total_amount');
            $table->integer('shipping_cost');
            $table->enum('status', ['cancelled', 'awaiting_payment', 'pending', 'processed'])->default('awaiting_payment');
            $table->timestamps();
        
            $table->foreign('site_user_id')->references('id')->on('site_users')->onDelete('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
