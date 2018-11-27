<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;
use Log;

/**
 * 根据所加任务，修改magephp.yml配置项
 *
 * Class UtilsService
 * @package App\Services
 */
class UtilsService {
	/**
	 * 获取分支- 废弃
	 *
	 * @param $taskInfo
	 * @return bool|mixed
	 */
	public static function changeConfigFunc($taskInfo) {
		if (!is_array($taskInfo) || !array_key_exists('task_branch', $taskInfo) || !array_key_exists('task_env', $taskInfo) || !array_key_exists('task_id', $taskInfo)) {
			Log::error(json_encode($taskInfo));
			return false;
		}

		$taskBranchArr = config('deployment.task_branch');
		$taskEnvArr = config('deployment.task_env');

		// 获取选择分支、发布环境
		$selTaskBranch = $taskBranchArr[$taskInfo['task_branch']];
		$selTaskEnv = $taskEnvArr[$taskInfo['task_env']];
		$selTaskId = $taskInfo['task_id'];

		return $selTaskId;
	}

	/**
	 * 必填字段校验
	 * @param $allFields
	 * @param $filledFields
	 * @param $initFields
	 * @return bool
	 */
	public static function filterFields(&$allFields, $filledFields, $initFields) {
		if (empty($allFields) || empty($filledFields)) {
			return false;
		}

		foreach ($allFields as $field => $value) {
			if (in_array($field, $filledFields) && (strlen($value) <= 0)) {
				return false;
			}

			if (in_array($field, $initFields)) {
				if (is_array($value)) {
					$cnt = count($value);
					if ($cnt == 1 && ($value[0] === null)) {
						$value = '';
					}

					if ($cnt > 1) {
						foreach ($value as $k => $v) {
							if ($v === null) {
								unset($value[$k]);
							}
						}
					}
				} else if (is_string($value)) {
					if ($value === null) {
						$value = '';
					}
				}

			}
		}

		return true;
	}

	/**
	 * 配置文件添加成功，执行的任务
	 *
	 * @param $configItem 配置项
	 * @return bool
	 */
	public static function generateConfigForMage($configItem) {
		if (!is_array($configItem) || empty($configItem)) {
			Log::info('the config params is wrong.check it.');
			return false;
		}
		Log::info('the ret ' . print_r($configItem, true));
		$customFieldArr = config('deployment.custom_field');
		$initMapFields = config('deployment.init_map_field');
		$deployConfigArr = config('deployment');
		$tmplFields = $deployConfigArr['yml_map_fields'];
		$tmpl = $deployConfigArr['yml_template'];
		$deployConfig = $deployConfigArr['deploy_config'];

		unset($configItem['operator']);
		foreach ($customFieldArr as $k => $v) {
			if (isset($initMapFields[$v])) {
				// 可选值存在，map真实值
				foreach ($configItem[$v] as $f1 => $v1) {
					if (isset($deployConfig[$initMapFields[$v]][$v1])) {
						$configItem[$v][$f1] = $deployConfig[$initMapFields[$v]][$v1];
					}
				}
			}

			if (isset($configItem[$k]) && !empty($configItem[$k])) {
				foreach ($configItem[$k] as $key => $value) {
					$arr = explode('|', $value);
					foreach ($arr as $vv) {
						$configItem[$v][] = $vv;
					}
				}
			}
			unset($configItem[$k]);
		}

		extract($configItem);

		$config_env = $deployConfig['task_env'][$config_env];
		$common_items = $deployConfigArr['common_mage_yml'];
		$log_dir = $deployConfig['log_dir'];
		$mageYmlFile = [
			'magephp' => [
				'environments' => [],
			],
		];
		Log::info('tmpFields' . print_r($tmplFields, true));
		Log::info('tmp:' . print_r($tmpl, true));
		$split = '|';
		$customStr = 'custom_';
//        $mageYmlFile['magephp']['environments'][$config_env] = $common_items;
		$mageYmlFile['magephp']['environments'][$config_env] = [];
		$mageYmlFile['magephp']['log_dir'] = $log_dir;

		$ymlFile = rtrim($config_from, '/') . '/.mage.yml';
		if (!file_exists($ymlFile)) {
			foreach ($tmpl as $key => $value) {
				Log::info('magephp' . print_r($mageYmlFile, true));
				if (isset($tmplFields[$key])) {
					$tmpArr = explode('|', $tmplFields[$key]);
					if ($tmpArr[1] == 'normal') {
						$mageYmlFile['magephp']['environments'][$config_env][$key] = ${$tmpArr[0]};
					} else if ($tmpArr[1] == 'const') {
						$mageYmlFile['magephp']['environments'][$config_env][$key] = $value;
					} else if ($tmpArr[1] == 'map') {
						$mageYmlFile['magephp']['environments'][$config_env][$key] = $deployConfig['task_branch'][${$tmpArr[0]}];
					} else if ($tmpArr[1] == 'int') {
						$mageYmlFile['magephp']['environments'][$config_env][$key] = (int) ${$tmpArr[0]};
					} else if ($tmpArr[1] == 'array') {
						if (${$tmpArr[0]} === null || ${$tmpArr[0]} == '') {
							$mageYmlFile['magephp']['environments'][$config_env][$key] = '';
						} else if (is_array(${$tmpArr[0]})) {
							if (empty(${$tmpArr[0]})) {
								$mageYmlFile['magephp']['environments'][$config_env][$key] = '';
							} else {
								$mageYmlFile['magephp']['environments'][$config_env][$key] = ${$tmpArr[0]};
							}
						}
					}
				}
				Log::info('magephp' . print_r($mageYmlFile, true));
			}
			$yaml = preg_replace("/'/", '', Yaml::dump($mageYmlFile, 5, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
			file_put_contents($ymlFile, $yaml); // 写如到对应的文件中的image中
			if (!chmod($ymlFile, 0777)) {
				Log::info('修改mage.yml文件权限失败，请检查，手动修改777。');
			}
			return true;
		} else {
			$info = Yaml::parseFile($ymlFile);
			if (isset($info) && !empty($info)) {
				foreach ($info as $rootMage => $configArr) {
					// 一位大数组
					foreach ($configArr as $item => $v) {
						// 二级配置项
						if ($item == 'environments') {
							// 实际的配置项目
							foreach ($tmpl as $key => $value) {
								if (isset($tmplFields[$key])) {
									$tmpArr = explode('|', $tmplFields[$key]);
									if ($tmpArr[1] == 'normal') {
										$info[$rootMage][$item][$config_env][$key] = ${$tmpArr[0]};
									} else if ($tmpArr[1] == 'const') {
										$info[$rootMage][$item][$config_env][$key] = $value;
									} else if ($tmpArr[1] == 'map') {
										$info[$rootMage][$item][$config_env][$key] = $deployConfig['task_branch'][${$tmpArr[0]}];
									} else if ($tmpArr[1] == 'int') {
										$info[$rootMage][$item][$config_env][$key] = (int) ${$tmpArr[0]};
									} else if ($tmpArr[1] == 'array') {
										if (${$tmpArr[0]} === null || ${$tmpArr[0]} == '') {
											$info[$rootMage][$item][$config_env][$key] = '';
										} else if (is_array(${$tmpArr[0]})) {
//                                            $info[$rootMage][$item][$config_env][$key] = array_merge($builInArr, $customArr);
											if (empty(${$tmpArr[0]})) {
												$info[$rootMage][$item][$config_env][$key] = '';
											} else {
												$info[$rootMage][$item][$config_env][$key] = ${$tmpArr[0]};
											}
										}
									}
								}
							}
						}
					}
				}
			}
			$yaml = preg_replace("/'/", '', Yaml::dump($info, 5, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
			file_put_contents($ymlFile, $yaml); // 写如到对应的文件中的image中
			if (!chmod($ymlFile, 0777)) {
				Log::info('修改mage.yml文件权限失败，请检查，手动修改777。');
			}
			return true;
		}
	}

	/**
	 * 获取目录下文件函数
	 *
	 * @param $dir
	 * @return array
	 */
	public static function getFile($dir) {
		$dp = opendir($dir);
		$fileArr = array();
		while (!false == $curFile = readdir($dp)) {
			if ($curFile != "." && $curFile != ".." && $curFile != "") {
				if (is_dir($curFile)) {
					$fileArr = self::getFile($dir . "/" . $curFile);
				} else {
					$fileArr[] = $dir . "/" . $curFile;
				}
			}
		}
		return $fileArr;

	}

	/**
	 * 获取文件内容
	 *
	 * @param $file
	 * @return string
	 */
	public static function getFileContent($file) {
		$fileContent = '';
		if (!$fp = fopen($file, "r")) {
			die("Cannot open file $file");
		}
		while ($text = fread($fp, 4096)) {
			$fileContent .= $text;
		}
		return $fileContent;
	}

	/**
	 * 获取文件大小(KB)
	 *
	 * @param $file
	 * @return string
	 */
	public static function getFileSize($file) {
		$filesize = intval(filesize($file) / 1024) . "K";
		return $filesize;
	}

	/**
	 *获取文件最后修改的时间
	 *
	 * @param $file
	 * @return false|string
	 */
	public static function getFileTime($file) {
		$filetime = date("Y-m-d", filemtime($file));
		return $filetime;
	}

	/**
	 * 搜索指定文件
	 *
	 * @param $file
	 * @param $keyword
	 * @return bool
	 */
	public static function searchText($file, $keyword) {
		$text = self::getFileContent($file);
		if (preg_match("/$keyword/i", $text)) {
			return true;
		}
		return false;
	}

	/**
	 * 搜索目录下所有文件
	 *
	 * @param $dir
	 * @param $keyword
	 * @return array|bool
	 */
	public static function searchFile($dir, $keyword) {
		$sFile = self::getFile($dir);
		if (count($sFile) <= 0) {
			return false;
		}
		$sResult = array();
		foreach ($sFile as $file) {
			if (self::searchText($file, $keyword)) {
				$sResult[] = $file;
			}
		}
		if (count($sResult) <= 0) {
			return false;
		} else {
			return $sResult;
		}
	}

	/**
	 * 保存配置文件
	 *
	 * @param string $filename
	 * @param string $content
	 * @return void
	 */
	public static function saveConfig($filename, $content) {
		file_put_contents($filename, "<?php\n\nreturn " . var_export($content, true) . ';');
	}

    /**
     * @param $url
     * @param null $uid
     * @param int $timeout
     * @return bool|mixed
     */
    public static function requestGet($url, $uid=null, $timeout=3) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        // 获取tauth token
        if (! is_null($uid)) {
            $tauth = self::_getTAuth($uid);
            if ($tauth === false) return false;
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($tauth));
        }

        $result = curl_exec($ch);
        if(false === $result)
        {
            Log::warning("curl_get_error", ['url' => $url, 'uid' => $uid, 'return' => $result, 'curl_errno' => curl_errno($ch), 'curl_error' => curl_error($ch)]);
        } else {
            Log::info("curl_get_info", ['url' => $url, 'uid' => $uid, 'return' => $result]);

        }
        curl_close($ch);
        return $result;
    }

    /**
     * @param $url
     * @param $data
     * @param null $uid
     * @param int $timeout
     * @param array $headers
     * @return bool|mixed
     */
    public static function requestPost($url, $data, $uid=null, $timeout=3, $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(! empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        // 获取tauth token
        if (! is_null($uid)) {
            $tauth = self::_getTAuth($uid);
            if ($tauth === false) return false;
            array_merge($headers, array($tauth));
        }
        if(!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($ch);
        if(false === $result)
        {
            Log::warning("curl_post_error", ['url' => $url, 'uid' => $uid, 'params' => $data, 'return' => $result, 'curl_errno' => curl_errno($ch), 'curl_error' => curl_error($ch)]);
        } else {
            Log::info("curl_post_info", ['url' => $url, 'uid' => $uid, 'params' => $data, 'return' => $result]);

        }
        curl_close($ch);
        return $result;
    }

    public static function curlGet($user, $msg)
    {
        $urlPrefix = config('params.wx_params');
        $url = $urlPrefix['url'].$user.'/'.$msg;
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //函数中加入下面这条语句
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

        //执行命令
        $data = curl_exec($curl);
        //显示获得的数据
        if(false === $data)
        {
            Log::warning("curl_get_error", ['url' => $url, 'return' => $data, 'curl_errno' => curl_errno($curl), 'curl_error' => curl_error($curl)]);
        } else {
            Log::info("curl_get_info", ['url' => $url, 'params' => $data, 'return' => $data]);

        }
        //关闭URL请求
        curl_close($curl);
        return $data;
    }
}

