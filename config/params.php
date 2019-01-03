<?php

return [
    'wx_params' => [
        'url' => 'http://10.77.136.158:10086/messager/send_wx/',
    ],

    'notify_way' => [
        1 => '短信',
        2 => '微信',
        4 => '邮件',
    ],

    'alert_level' => [
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
    ],

    'aggregate' => [
        0 => 'sum',
        1 => 'max',
        2 => 'min',
        3 => 'average',
    ],

    'operator' => [
        0 => '>=',
        1 => '=',
        2 => '>',
        3 => '<',
        4 => '<=',
    ],
];
