<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhpVersionToDeploymentConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deployment_config', function (Blueprint $table) {
            $table->unsignedInteger('config_php_version')->default(0)->comment('项目使用php版本')->after('config_ssh_addr');
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
