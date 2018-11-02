<?php

/**
 * 在线升级
 */

define('IN_ECS', true);

require __DIR__ . '/includes/init.php';

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('CACHE_PATH') or define('CACHE_PATH', ROOT_PATH . 'temp/upgrade/');

// 当前版本
$sql = "SELECT `value` FROM " . $ecs->table("shop_config") . " WHERE `code` = 'dsc_version'";
$current_version = $db->getOne($sql, true);

$patch_url = 'http://download.dscmall.cn/metadata.json?v=' . date('YmdH'); // 补丁地址
$patch = patch_list($patch_url, $current_version);

$fs = new \Illuminate\Filesystem\Filesystem();

/**
 * 在线升级列表
 */
if ($_REQUEST['act'] == 'index') {
    // 检查权限
    check_authz_json('upgrade_manage');

    if (empty($patch)) {
        $last_version = $_LANG['already_new'];
    } else {
        $last_version = end($patch);
    }

    $smarty->assign('ur_here', $_LANG['list_link']);
    $smarty->assign('full_page', 1);
    $smarty->assign('ecs_version', $current_version);
    $smarty->assign('ecs_release', RELEASE);
    $smarty->assign('last_version', $last_version);
    $smarty->assign('is_writable', $fs->isWritable(ROOT_PATH));
    $smarty->assign('patch', $patch);

    $smarty->display('upgrade_index.dwt');
}

/**
 * 在线升级功能
 */
if ($_REQUEST['act'] == 'init') {
    // 检查权限
    check_authz_json('upgrade_manage');

    // 确认是否升级
    $cover = !empty($_REQUEST['cover']) ? intval($_REQUEST['cover']) : 0;
    if (empty($cover)) {
        sys_msg($_LANG['covertemplate'], 1);
    }

    // 获取补丁列表
    if (empty($patch)) {
        sys_msg($_LANG['already_new'] . $msg, 2);
    }

    // 创建缓存文件夹
    $upgrade_path = ROOT_PATH . 'temp/upgrade';
    if (!$fs->isDirectory($upgrade_path)) {
        $fs->makeDirectory($upgrade_path);
    }

    // 更新补丁包
    $message = upgrade($patch[0]);

    // 生成队列url
    if (isset($patch[1])) {
        $url = 'upgrade.php?act=init&cover=' . $cover . '&t=' . time();
    } else {
        $url = 'upgrade.php?act=index';
		
		/* 清除缓存 */
		clear_all_files();
    }

    // 升级成功
    $links = [
        [
            'text' => $patch[0] . $GLOBALS['_LANG']['upgrade_success'],
            'href' => $url,
        ]
    ];

    sys_msg($GLOBALS['_LANG']['upgradeing'], 2, $links);
}

/**
 * 获取补丁列表
 * @param $patch_url
 * @param $current_version
 * @return array
 */
function patch_list($patch_url, $current_version)
{
    $metadata_str = Http::doGet($patch_url);
    $metadata = json_decode($metadata_str, true);

    // 获取可供当前版本升级的压缩包
    $patch = [];
    foreach ($metadata['version'] as $k => $v) {
        if (version_compare($v, $current_version, '>')) {
            $patch[] = $v;
        }
    }

    return $patch;
}

/**
 * 更新补丁包
 * @param $patch
 * @return string
 */
function upgrade($patch)
{
    global $patch_url, $upgrade_path, $fs;

    // 远程压缩包地址
    $upgradezip_url = dirname($patch_url) . '/' . substr($patch, 0, 2) . '/patch_' . $patch . '.zip?v=' . date('Ymd');

    // 保存到本地地址
    $upgradezip_path = $upgrade_path . '/' . $patch . '.zip';

    // 补丁包解压路径
    $upgradezip_source_path = $upgrade_path . '/' . $patch;

    // 下载补丁压缩包
    $fs->put($upgradezip_path, Http::doGet($upgradezip_url));

    // 备份数据库配置
    $fs->move(ROOT_PATH . 'data/config.php', ROOT_PATH . 'data/config.bak.php');

    // 解压缩补丁包
    if (unzip($upgradezip_path, ROOT_PATH) === false) {
        die("Error : unpack the failure.");
    }

    // 恢复数据库配置
    $fs->move(ROOT_PATH . 'data/config.bak.php', ROOT_PATH . 'data/config.php');

    // 删除文件
    $fs->delete($upgradezip_path);

    // 删除文件夹
    $fs->deleteDirectory($upgradezip_source_path);
	
	// 更新数据库
	$migration = ROOT_PATH . 'data/migrations/' . $patch . '.php';
	if(file_exists($migration)){
		require $migration;		
	}

	// 更新版本到数据库
    $sql = "UPDATE " . $GLOBALS['ecs']->table("shop_config") . " SET `value` = '" . $patch . "' WHERE `code` = 'dsc_version'";
    $GLOBALS['db']->query($sql);
}

/**
 * 解压文件到指定目录
 *
 * @param   string   zip压缩文件的路径
 * @param   string   解压文件的目的路径
 * @param   boolean  是否以压缩文件的名字创建目标文件夹
 * @param   boolean  是否重写已经存在的文件
 *
 * @return  boolean  返回成功 或失败
 */
function unzip($src_file, $dest_dir = false, $create_zip_name_dir = true, $overwrite = true)
{
    global $fs;

    if ($zip = zip_open($src_file)) {
        if ($zip) {
            $splitter = ($create_zip_name_dir === true) ? "." : "/";
            if ($dest_dir === false) {
                $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter)) . "/";
            }

            // 如果不存在 创建目标解压目录
            if (!$fs->isDirectory($dest_dir)) {
                $fs->makeDirectory($dest_dir);
            }

            // 对每个文件进行解压
            while ($zip_entry = zip_read($zip)) {
                // 文件不在根目录
                $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
                if ($pos_last_slash !== false) {
                    // 创建目录 在末尾带 /
                    $path = $dest_dir . substr(zip_entry_name($zip_entry), 0, $pos_last_slash + 1);
                    if (!$fs->isDirectory($path)) {
                        $fs->makeDirectory($path);
                    }
                }

                // 打开包
                if (zip_entry_open($zip, $zip_entry, "r")) {
                    // 文件名保存在磁盘上
                    $file_name = $dest_dir . zip_entry_name($zip_entry);

                    // 检查文件是否需要重写
                    if ($overwrite === true || $overwrite === false && !is_file($file_name)) {
                        // 读取压缩文件的内容
                        $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                        if (!$fs->isDirectory($file_name)) {
                            $fs->put($file_name, $fstream);
                        }
                        // 设置权限
                        chmod($file_name, 0755);
                    }

                    // 关闭入口
                    zip_entry_close($zip_entry);
                }
            }
            // 关闭压缩包
            zip_close($zip);
        }
    } else {
        return false;
    }
    return true;
}