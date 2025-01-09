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
            $table->id(); // For Order ID
            $table->integer('user_id'); // For Restaurant
            $table->integer('restaurant_id'); // For Restaurant
            $table->string('items'); // For Items
            $table->integer('total'); // For Total
            $table->string('status'); // For Status
            $table->timestamp('order_date'); // For Date
            $table->timestamps(); // Created at and Updated at
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
