<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('group_id')->unique()->comment('群組 id');
            $table->boolean('silent_mode')->default(false)->comment('靜音模式');
            $table->timestamps();
            $table->index('group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_config');
    }
}
