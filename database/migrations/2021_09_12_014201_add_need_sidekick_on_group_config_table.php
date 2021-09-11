<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNeedSidekickOnGroupConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_config', function (Blueprint $table) {
            $table->boolean('need_sidekick')
                ->default(false)
                ->comment("可設定小幫手")
                ->after('group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_config', function (Blueprint $table) {
            $table->dropColumn('need_sidekick');
        });
    }
}
