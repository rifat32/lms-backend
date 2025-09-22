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
            $table->string("name");
            $table->string("email")->nullable()->unique();
            $table->string("phone")->nullable();
            $table->date("registration_date");
            $table->date("trail_end_date")->nullable();
            $table->text("about")->nullable();
            $table->string("web_page")->nullable();
            $table->string("address_line_1")->nullable();
            $table->string("country");
            $table->string("city");
            $table->string("postcode")->nullable();
            $table->string("currency")->nullable();
            $table->foreignId("service_plan_id")->nullable()->constrained('service_plans')->restrictOnDelete();
            $table->enum('status', ['pending', 'active',  'suspended', 'cancelled', 'expired', 'trail_ended', 'inactive'])->default("pending");
            $table->boolean('is_active')->default(1);
            $table->string("logo")->nullable();
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
