<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAppliedOnGroupAdminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_admin', function (Blueprint $table) {
            $table->boolean('applied')
                ->default(true)
                ->comment("申請小幫手，申請不通過則會整筆刪除")
                ->after('need_sidekick');
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
            $table->dropColumn('applied');
        });
    }
}
