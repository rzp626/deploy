<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSshAddrToDeploymentConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deployment_config', function (Blueprint $table) {
            $table->string('config_ssh_addr', 255)->default('')->comment('git项目仓库地址')->after('config_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deployment_config', function (Blueprint $table) {
            //
        });
    }
}
