<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();


Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    //$router->get('/', 'HomeController@index');
    $router->get('/', 'DefaultController@index');
    $router->resource('dp/ts', DeploymentTaskController::class);
    $router->resource('config', DeploymentConfigController::class);
    $router->post('dp/deploy', 'DeployController@deploy');
    $router->post('dp/rollback', 'DeployController@rollback');
    $router->get('dp/log', 'DeployController@showLog');
    $router->get('dp/env', 'DeployController@selectEnv');
    $router->get('dp/branch', 'DeployController@selectBranch');
    $router->get('dp/config', 'DeployController@selectConfigName');
    $router->post('test', 'DeployController@test');

    // 审核工单
    $router->get('dp/review', 'ReviewController@index');
    $router->get('dp/members', 'ReviewController@getReviewers');
    $router->post('change_review_status', 'ReviewController@changeReviewStatus');
    $router->get('dp/custom_config', 'CustomConfigController@index');
    $router->get('dp/add_config', 'CustomConfigController@add');
});
