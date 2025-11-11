<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            // Custom morphs - made nullable for system notifications
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->text('data')->nullable(); // Keep for Laravel compatibility, but nullable
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Additional custom fields
            $table->unsignedBigInteger("entity_id");
            $table->json("entity_ids")->nullable();
            $table->string("entity_name");

            $table->text("notification_title");
            $table->text("notification_description");
            $table->text("notification_link")->nullable();

            $table->unsignedBigInteger("sender_id")->nullable();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger("receiver_id")->nullable();
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger("business_id")->nullable();
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            $table->boolean("is_system_generated")->default(false);

            // $table->unsignedBigInteger("notification_template_id")->nullable();
            // $table->foreign('notification_template_id')->references('id')->on('notification_templates')->onDelete('cascade');

            $table->string("notification_type")->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Indexes for better performance
            $table->index(['notifiable_type', 'notifiable_id']); // Polymorphic index
            $table->index(['notifiable_id', 'read_at']); // For user notification queries
            $table->index(['sender_id']);
            $table->index(['receiver_id']);
            $table->index(['business_id']);
            $table->index(['notification_type']);
            $table->index(['entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
