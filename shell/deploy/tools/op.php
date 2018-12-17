<?php
/**
 * Created by PhpStorm.
 * User: hongbin9@staff.weibo.com
 * Date: 2018/6/25
 * Time: 下午6:00
 */
$ret = opcache_reset();

if($ret) {
    echo "opcache reset OK!";
} else {
    echo "opcache reset Failed!";
}
