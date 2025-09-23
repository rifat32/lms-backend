<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_times', function (Blueprint $table) {
            $table->id();
            $table->integer("day");
            $table->time('start_at')->nullable();
            $table->time('end_at')->nullable();
            $table->boolean("is_weekend");
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
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
        Schema::dropIfExists('business_times');
    }
}
