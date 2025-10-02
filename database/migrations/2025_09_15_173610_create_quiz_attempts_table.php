<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizAttemptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            
    $table->id();

    $table->unsignedBigInteger('quiz_id');
    $table->unsignedBigInteger('user_id');
    $table->integer('score')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->integer('time_spent')->default(0); // in seconds
    $table->boolean('is_expired')->default(false);


    $table->timestamps();

    $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quiz_attempts');
    }
}
