<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
      $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('course_id');
    $table->decimal('amount', 10, 2);
    $table->string('method');
    $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
    $table->string('transaction_id')->unique();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
