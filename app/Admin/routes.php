<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();


Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->resource('deployment', DeploymentTaskController::class);
    $router->resource('config', DeploymentConfigController::class);
    $router->post('dp/deploy', 'DeployController@deploy');
    $router->post('dp/rollback', 'DeployController@rollback');
    $router->get('dp/log', 'DeployController@showLog');
    $router->get('dp/env', 'DeployController@selectEnv');
    $router->get('dp/branch', 'DeployController@selectBranch');
    $router->get('dp/config', 'DeployController@selectConfigName');
    $router->post('test', 'DeployController@test');

    // 审核工单
    $router->get('review', 'ReviewController@index');
    $router->get('members', 'ReviewController@getReviewers');
    $router->post('change_review_status', 'ReviewController@changeReviewStatus');
    $router->get('custom_config', 'CustomConfigController@index');
});
