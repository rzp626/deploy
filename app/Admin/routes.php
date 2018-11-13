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
    $router->post('deploy', 'DeployController@deploy');
    $router->post('rollback', 'DeployController@rollback');
    $router->get('log', 'DeployController@showLog');
    $router->get('env', 'DeployController@selectEnv');
    $router->get('branch', 'DeployController@selectBranch');
    $router->get('config', 'DeployController@selectConfigName');
    $router->post('test', 'DeployController@test');

    // 审核工单
    $router->get('review', 'ReviewController@index');
    $router->get('members', 'ReviewController@getReviewers');
    $router->post('change_review_status', 'ReviewController@changeReviewStatus');
});


