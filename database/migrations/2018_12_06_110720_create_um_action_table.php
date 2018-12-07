<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUmActionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('um_action', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 32)->default('')->comment('动作名');
            $table->string('explain')->default('')->comment('动作说明');
            $table->dateTime('invalid_start_time')->comment('失效开始时间');
            $table->dateTime('invalid_end_time')->comment('失效结束时间');
            $table->unsignedTinyInteger('notify_type_bit')->comment('通知方式：1(001)-短信 2(010)-微信 4(100)-邮件');
            $table->unsignedInteger('group_id')->comment('绑定的组ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('um_action');
    }
}
