<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUmGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('um_group', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 32)->default('')->comment('组名');
            $table->string('explain', 100)->default('')->comment('组说明');
            $table->text('members')->comment('组员列表逗号分隔：hongbin9,shiqiang3');
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
        Schema::dropIfExists('um_group');
    }
}
