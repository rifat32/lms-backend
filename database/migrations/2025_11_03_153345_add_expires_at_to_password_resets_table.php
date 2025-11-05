<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpiresAtToPasswordResetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('password_resets', function (Blueprint $table) {
            if (!Schema::hasColumn('password_resets', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index()->after('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('password_resets', function (Blueprint $table) {
            if (Schema::hasColumn('password_resets', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
}
