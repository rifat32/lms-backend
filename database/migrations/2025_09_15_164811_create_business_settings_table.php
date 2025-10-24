<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_settings', function (Blueprint $table) {

            $table->id();
            $table->integer('business_start_day')->default(0);
            $table->string('business_time_zone')->nullable();
            $table->string('identifier_prefix')->nullable();
            $table->boolean('delete_read_notifications_after_30_days')->nullable();

            $table->string('STRIPE_KEY')->nullable();
            $table->string('STRIPE_SECRET')->nullable();
            $table->boolean('stripe_enabled')->default(false);

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
        Schema::dropIfExists('business_settings');
    }
}
