<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueConstraintsFromPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE payments DROP INDEX IF EXISTS payments_transaction_id_unique');
        DB::statement('ALTER TABLE payments DROP INDEX IF EXISTS payments_payment_intent_id_unique');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE payments ADD UNIQUE INDEX payments_transaction_id_unique (transaction_id)');
        DB::statement('ALTER TABLE payments ADD UNIQUE INDEX payments_payment_intent_id_unique (payment_intent_id)');
    }
}
