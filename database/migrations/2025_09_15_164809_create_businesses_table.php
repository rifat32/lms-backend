<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string("name"); // required|string|max:255
            $table->string("email")->nullable()->unique(); // nullable|string|unique
            $table->string("phone")->nullable(); // nullable|string
            $table->date("registration_date"); // required in migration, can be auto-set

            $table->text("about")->nullable(); // nullable|string
            $table->string("web_page")->nullable(); // nullable|string
            $table->string("address_line_1"); // required|string
            $table->string("address_line_2")->nullable(); // nullable|string
            $table->string("country"); // required|string
            $table->string("city"); // required|string
            $table->string("postcode")->nullable(); // nullable|string
            $table->string("currency")->nullable(); // nullable|string
            $table->string("latitude")->nullable(); // nullable|string
            $table->string("longitude")->nullable(); // nullable|string
            $table->string("logo")->nullable(); // nullable|string
            $table->string("image")->nullable(); // nullable|string
            $table->string("background_image")->nullable(); // nullable|string
            $table->string("theme")->nullable(); // nullable|string
            $table->json("images")->nullable(); // nullable|array of strings
            $table->string("additional_information")->nullable(); // nullable|string
            $table->string('status')->default("pending");
            $table->boolean('is_active')->default(1);
            $table->foreignId("owner_id")->constrained('users')->cascadeOnDelete();
            $table->foreignId("created_by")->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
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
        Schema::dropIfExists('businesses');
    }
}
