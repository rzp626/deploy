<?php

/**
 * 环境与分支关系： 1：n(n>=1)的关系,待修改
 */
return [
    'deploy_config' => [
        'task_branch' => [
            0 => 'production',
            1 => 'test',
            2 => 'develop',
            3 => 'sendbox',
        ],
        'task_env' => [
            0 => 'production',
            1 => 'test',
            2 => 'develop',
            3 => 'sendbox',
        ],
        'pre-deploy' => [
            0 => 'deploy/tar/prepare',
        ],
        'on-deploy' => [
            0 => 'deploy/release/prepare',
            1 => 'deploy/tar/copy',
        ],
        'on-release' => [
            0 => 'deploy/release',
        ],
        'post-release' => [
            0 => 'deploy/release/cleanup',
        ],
        'post-deploy' => [
            0 => 'deploy/tar/cleanup',
        ],
    ],

    'filled_fields' => [
        'config_name',
        'config_env',
        'config_from',
        'config_host_path',
        'config_release',
        'config_hosts',
    ],

    'init_fields' => [
        'config_pre_deploy',
        'customize_pre_deploy',
        'config_on_deploy',
        'customize_on_deploy',
        'config_on_release',
        'customize_on_release',
        'config_post_release',
        'customize_post_release',
        'config_post_deploy',
        'customize_post_deploy',
    ],

    'common_mage_yml' => [
        'tar_create' => 'cfh',
        'tar_create_path' => 'tar',
        'tar_extract' => 'xf',
        'tar_extract_path' => 'tar',
    ],

    'yml_map_fields' => [
        'user' => 'config_user|normal',
        'from' => 'config_from|normal',
        'host_path' => 'config_host_path|normal',
        'releases' => 'config_releases|int',
        'exclude' => 'config_exlude|array',
        'hosts' => 'config_hosts|array',
        'pre-deploy' => 'config_pre_deploy|array',
        'on-deploy' => 'config_on_deploy|array',
        'on-release' => 'config_on_release|array',
        'post-release' => 'config_post_release|array',
        'post-deploy' => 'config_post_deploy|array',
    ],

    'yml_template' => [
        'tar_create' => 'cfh',
        'tar_create_path' => 'tar',
        'tar_extract' => 'xf',
        'tar_extract_path' => 'tar',
        'user' => '',
        'from' => '',
        'host_path' => '',
        'releases' => '',
        'exclude' => '',
        'hosts' => '',
        'pre-deploy' => '',
        'on-deploy' => '',
        'on-release' => '',
        'post-release' => '',
        'post-deploy' => '',
    ],
];

