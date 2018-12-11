<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNullToUmActionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('um_action', function (Blueprint $table) {
            $table->dateTime('invalid_start_time')->comment('失效开始时间')->nullable()->change();
            $table->dateTime('invalid_end_time')->comment('失效结束时间')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('um_action', function (Blueprint $table) {
            //
        });
    }
}
