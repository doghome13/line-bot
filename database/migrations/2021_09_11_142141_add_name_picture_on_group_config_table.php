<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNamePictureOnGroupConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_config', function (Blueprint $table) {
            $table->string('picture_url')->nullable()->comment("群組 icon URL")->after('group_id');
            $table->string('name')->nullable()->comment("群組名稱")->after('group_id');
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
            $table->dropColumn(['name', 'picture_url']);
        });
    }
}
