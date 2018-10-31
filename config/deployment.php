<?php

/**
 * 环境与分支关系： 1：n(n>=1)的关系,待修改
 */
return [
    'deploy_config' => [
        'task_branch' => [
            0 => 'master',
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
//            0 => 'git/change-branch',
            1 => 'composer/install',
            2 => 'composer/dump-autoload',
            3 => 'deploy/tar/prepare',
        ],
        'on-deploy' => [
            1 => 'deploy/release/prepare',
            2 => 'deploy/tar/copy',
        ],
        'on-release' => [
            1 => 'deploy/release',
        ],
        'post-release' => [
            1 => 'deploy/release/cleanup',
        ],
        'post-deploy' => [
            1 => 'deploy/tar/cleanup',
        ],
        'log_dir' => ' /data0/deploy/logs',
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
        'branch' => 'config_branch|map',
        'from' => 'config_from|const',
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
        'user' => '',
        'branch' => '',
        'from' => './',
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

    'custom_field' => [
        'custom_pre_deploy' => 'config_pre_deploy',
        'custom_on_deploy' => 'config_on_deploy',
        'custom_on_release' => 'config_on_release',
        'custom_post_release' => 'config_post_release',
        'custom_post_deploy' => 'config_post_deploy',
    ],

    'init_map_field' => [
        'config_pre_deploy' => 'pre-deploy',
        'config_on_deploy' => 'on-deploy',
        'config_on_release' => 'on-release',
        'config_post_release' => 'post-release',
        'config_post_deploy' => 'post-deploy',
        'config_hosts' => 'config_hosts',
    ],
];

