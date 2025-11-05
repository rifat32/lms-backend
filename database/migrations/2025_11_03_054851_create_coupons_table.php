<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->unique();
            $table->enum('discount_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('min_total', 10, 2)->nullable();
            $table->decimal('max_total', 10, 2)->nullable();
            $table->integer('redemptions')->default(0);
            $table->date('coupon_start_date')->nullable();
            $table->date('coupon_end_date')->nullable();
            $table->boolean('is_auto_apply')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupons');
    }
}
