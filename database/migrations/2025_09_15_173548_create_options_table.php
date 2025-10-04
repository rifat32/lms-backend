<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('options', function (Blueprint $table) {
        $table->id();
        $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
        $table->string('option_text')->nullable();
        $table->boolean('is_correct')->default(false);
        $table->text('explanation')->nullable();
        $table->string('image')->nullable(); // for image-based options
        $table->string('matching_pair_text')->nullable(); // for matching
        $table->string('matching_pair_image')->nullable(); // for image matching
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
        Schema::dropIfExists('options');
    }
}
