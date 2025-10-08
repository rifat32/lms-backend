<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCertificateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
             $table->string('name');
    $table->longText('html_content'); // full HTML with placeholders
    $table->boolean('is_active')->default(false);
            $table->timestamps();
        });


         // Insert a default certificate template
        DB::table('certificate_templates')->insert([
            'name' => 'Default Certificate Template',
            'html_content' => '
                <div style="border: 10px solid #1E90FF; padding: 50px; text-align: center; font-family: Arial, sans-serif;">
                    <h1 style="color: #1E90FF;">Certificate of Completion</h1>
                    <p>This is to certify that</p>
                    <h2 style="margin: 10px 0;">{user_name}</h2>
                    <p>has successfully completed the course</p>
                    <h3 style="margin: 10px 0;">{course_name}</h3>
                    <p>on {issued_date}</p>
                    <hr style="margin: 40px 0;">
                    <p>Certificate Code: <strong>{certificate_code}</strong></p>
                </div>
            ',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('certificate_templates');
    }
}
