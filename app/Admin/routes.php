<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    $router->resource('deployments', DeploymentTaskController::class);
    $router->resource('deploymentConfig', DeploymentConfigController::class);

    $router->get('deploy/{id}', 'DeployController@deploy');
    $router->get('rollback/{releaseId}', 'DeployController@rollback');
    $router->get('log/{releaseId}', 'DeployController@showLog');
    $router->get('env', 'DeployController@selectEnv');
    $router->get('branch', 'DeployController@selectBranch');
    $router->get('config', 'DeployController@selectConfigName');
});
