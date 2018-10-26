<?php
/**
 * Created by PhpStorm.
 * User: zhenpeng8
 * Date: 2018/10/18
 * Time: 下午6:29
 */

namespace App\Http\Controllers;

use Symfony\Component\Yaml\Yaml;

class TestController extends Controller
{
    public function test()
    {
        $arr = [
            'magephp' => [
                'logdir' => '/data/log',
                'environment' => [
                    'foo' => 'bar',
                    'bar' => ['foo', 'bar', 'bar', 'baz'],
                    'string' => "- Multiple\n- Line\n- String",
                ],
            ],
        ];

//        $yaml = Yaml::dump($arr);
        $yaml = Yaml::dump($arr, 2, 3, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        file_put_contents(config_path() . '/mage/tt.yml', $yaml);

        $yaml = <<<YAML
magephp:
    #log_dir: /path/to/my/logs
    environments:
        production:
            tar_create: cfh
            tar_create_path: tar
            tar_extract: xf
            tar_extract_path: tar
            user: zhenpeng8
#            branch: master
            from: /data0/task
            host_path: /var/www/tmp
            releases: 4
            exclude:
                - ./var/cache/*
                - ./var/log/*
                - ./web/app_dev.php
            hosts:
                - 127.0.0.1
            pre-deploy:
                - deploy/tar/prepare
#                - git/update
#                - composer/install
#                - composer/dump-autoload
            on-deploy:
                - deploy/release/prepare
                - deploy/tar/copy
            on-release:
                - deploy/release
            post-release:
                - deploy/release/cleanup
            post-deploy:
                - deploy/tar/cleanup

YAML;
        $data = Yaml::parse($yaml);
//        echo "<pre>";
//        var_dump($data, json_encode($data));
//        print_r($data);
//        die;
        $arr = [
            'magephp' => [
                'environments' => [
                    'production' => [
                        'tar_create' => 'cfh',
                        'tar_create_path' => 'tar',
                        'tar_extract' => 'xf',
                        'tar_extract_path' => 'tar',
                        'user' => 'zhenpeng8',
                        'from' => '/data0/task',
                        'host_path' => '/var/www/tmp',
                        'releases' => 4,
                        'exclude' => [
                            0 => './var/cache/*',
                            1 => './var/log/*',
                            2 => './web/app_dev.php',
                        ],
                        'hosts' => [
                            0 => '127.0.0.1'
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
                        'test' => '{$test}',

                    ],

                ],
            ],
        ];



        $test = <<<EOF
        return 'magephp' => [
            'environments' => [
                "{$config_env}" => [
                    'tar_create' => 'cfh',
                    'tar_create_path' => 'tar',
                    'tar_extract' => 'xf',
                    'tar_extract_path' => 'tar',
                    'user' => '{$user}',
                    'from' => '{$config_from}',
                    'host_path' => '{$config_host_path}',
                    'releases' => '{$config_releases}',
                    'exclude' => '{$config_exlude}', 
                    'hosts' => '{$config_hosts}',
                    'pre-deploy' => '{$config_pre_deploy}',
                    'on-deploy' => '{$config_on_deploy}',
                    'on-release' => '{$config_on_release}',
                    'post-release' => '{$config_post_release},
                    'post-deploy' => '{$config_post_deploy}',
                    'test' => '{$test}',
                ],
            ],
        ]
EOF;
var_dump($test);
//        $yaml = yaml_emit($arr);

        $yaml = Yaml::dump($arr, 5, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        file_put_contents(config_path() . '/mage/tt1.yml', $yaml);

        extract($arr, EXTR_PREFIX_SAME, 'wddx');
        echo "<pre>";
        var_dump(json_encode($arr));
        die;
    }
}
