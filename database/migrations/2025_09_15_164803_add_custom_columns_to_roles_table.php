<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomColumnsToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable()->after('guard_name');
            $table->boolean('is_default')->default(0)->after('business_id');
            $table->boolean('is_system_default')->default(0)->after('is_default');
            $table->boolean('is_default_for_business')->default(0)->after('is_system_default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['business_id', 'is_default', 'is_system_default', 'is_default_for_business']);
        });
    }
}
