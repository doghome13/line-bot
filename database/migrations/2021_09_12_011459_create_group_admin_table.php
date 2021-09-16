<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupAdminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_admin', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_id')->unsigned()->comment('所屬群組 (group_config.id)');
            $table->string('user_id')->comment('用戶 id');
            $table->boolean('is_sidekick')->default(true)->comment('管理者層級，是否為小幫手');
            $table->dateTime('applied_at');

            $table->index(['group_id', 'user_id']);
            $table->foreign('group_id')
                ->references('id')->on('group_config')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_admin');
    }
}
