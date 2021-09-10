<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogExceptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_exception', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('code')->comment('exception code');
            $table->string('class_name');
            $table->string('file')->comment('錯誤的檔案');
            $table->integer('line')->comment('錯誤的行數');
            $table->string('url');
            $table->string('ip');
            $table->text('message');
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_exception');
    }
}
