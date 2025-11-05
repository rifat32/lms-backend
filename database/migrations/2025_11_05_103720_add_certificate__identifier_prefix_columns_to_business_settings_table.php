<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCertificateIdentifierPrefixColumnsToBusinessSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('certificate__identifier_prefix')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('certificate__identifier_prefix');
        });
    }
}
