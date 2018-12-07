<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUmStrategyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('um_strategy', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 32)->default('')->comment('策略名');
            $table->string('explain', 100)->default('')->comment('策略说明');
            $table->string('rule', 500)->default('')->comment('报警规则格式：{"es_index":"TYPE", "index_value":"displayErr", "aggregate":"max/min/average/sum", "operator":">=", "threshold":"1400"}');
            $table->unsignedInteger('cycle_times_x')->default(0)->comment('周期次数x值 (x周期内发生y次)');
            $table->unsignedInteger('cycle_times_y')->default(0)->comment('周期次数y值 (x周期内发生y次)');
            $table->unsignedTinyInteger('level')->default(0)->comment('报警级别0-2');
            $table->unsignedTinyInteger('action_id')->default(0)->comment('报警动作ID');
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
        Schema::dropIfExists('um_strategy');
    }
}
