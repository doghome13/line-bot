<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProfileGroupAdminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_admin', function (Blueprint $table) {
            $table->string('picture_url')->nullable()->comment("用戶 icon URL")->after('user_id');
            $table->string('name')->nullable()->comment("用戶名稱")->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_admin', function (Blueprint $table) {
            $table->dropColumn(['name', 'picture_url']);
        });
    }
}
