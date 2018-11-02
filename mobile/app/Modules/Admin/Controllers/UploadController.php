<?php

namespace App\Modules\Admin\Controllers;

use Think\Upload;
use Think\Upload\Driver\Alioss;
use App\Extensions\Uploader;
use App\Modules\Base\Controllers\BackendController;

class UploadController extends BackendController
{
    private $conf = [];

    public function __construct()
    {
        parent::__construct();
        C('SHOW_PAGE_TRACE', false);
        L(require(LANG_PATH . C('shop.lang') . '/user.php'));
        $this->content = file_get_contents(ROOT_PATH . "public/vendor/editor/config.json");
        $this->conf = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", str_replace('__ROOT__', ltrim(dirname(__ROOT__), '/'), $this->content)), true);
    }

    // 上传方法
    public function actionIndex()
    {
        $action = I('get.action');

        switch ($action) {
            case 'config':
                $result = json_encode($this->conf);
                break;

            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = $this->uploads();
                break;

            /* 列出图片 */
            case 'listimage':
                $result = $this->lists();
                break;
            /* 列出文件 */
            case 'listfile':
                $result = $this->lists();
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $result = $this->crawler();
                break;

            default:
                $result = json_encode([
                    'state' => L('request_url_error'),
                ]);
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode([
                    'state' => L('parameter_error'),
                ]);
            }
        } else {
            echo $result;
        }
    }

    // 上传附件和上传视频
    private function uploads()
    {
        /* 上传配置 */
        $base64 = "upload";
        switch (htmlspecialchars($_GET['action'])) {
            case 'uploadimage':
                $config = [
                    "pathFormat" => $this->conf['imagePathFormat'],
                    "maxSize" => $this->conf['imageMaxSize'],
                    "allowFiles" => $this->conf['imageAllowFiles']
                ];
                $fieldName = $this->conf['imageFieldName'];
                break;
            case 'uploadscrawl':
                $config = [
                    "pathFormat" => $this->conf['scrawlPathFormat'],
                    "maxSize" => $this->conf['scrawlMaxSize'],
                    "allowFiles" => $this->conf['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                ];
                $fieldName = $this->conf['scrawlFieldName'];
                $base64 = "base64";
                break;
            case 'uploadvideo':
                $config = [
                    "pathFormat" => $this->conf['videoPathFormat'],
                    "maxSize" => $this->conf['videoMaxSize'],
                    "allowFiles" => $this->conf['videoAllowFiles']
                ];
                $fieldName = $this->conf['videoFieldName'];
                break;
            case 'uploadfile':
            default:
                $config = [
                    "pathFormat" => $this->conf['filePathFormat'],
                    "maxSize" => $this->conf['fileMaxSize'],
                    "allowFiles" => $this->conf['fileAllowFiles']
                ];
                $fieldName = $this->conf['fileFieldName'];
                break;
        }

        /* 生成上传实例对象并完成上传 */
        $aliossConfig = get_bucket_info(true); // 获取oss配置
        if (C('shop.open_oss') == 1 && $aliossConfig !== false) {
            $res = $this->oss_upload('data/attached/image/', true, 2, false, true, $aliossConfig);
            return json_encode($res);
        } else {
            $up = new Uploader($fieldName, $config, $base64);

            /**
             * 得到上传文件所对应的各个参数,数组结构
             * array(
             * "state" => "", //上传状态，上传成功时必须返回"SUCCESS"
             * "url" => "", //返回的地址
             * "title" => "", //新文件名
             * "original" => "", //原始文件名
             * "type" => "" //文件类型 .jpg
             * "size" => "", //文件大小
             * )
             */
            /* 返回数据 */
            return json_encode($up->getFileInfo());
        }
    }

    /**
     * 上传文件 oss
     * @param string $savePath 保存目录
     * @param bool $hasOne 返回一维数组
     * @param int $size 文件上传大小限制
     * @param bool $thumb 是否生成缩略图
     * @param bool $autoSub 是否生成子目录
     * @param array $subName 生成子目录名称规则  Ymd = 20171014
     * @param array $aliossConfig oss配置参数
     * @return array
     */
    public function oss_upload($savePath = '', $hasOne = false, $size = 2, $thumb = false, $autoSub = false, $aliossConfig = [])
    {
        $oss_config = [
            'maxSize' => $size * 1024 * 1024, // 2MB
            'rootPath' => C('UPLOAD_PATH'),
            'savePath' => rtrim($savePath, '/') . '/', //保存路径
            'exts' => ['jpg', 'gif', 'png', 'jpeg', 'bmp', 'mp3', 'amr', 'mp4'],
            'autoSub' => $autoSub,
            'thumb' => $thumb,
            'subName' => ['date', 'Ymd']
        ];
        $oss_up = new Upload($oss_config, 'Alioss', $aliossConfig);
        // 上传文件
        $result = $oss_up->upload();
        if (!$result) {
            // 上传错误提示错误信息
            $res = [
                'state' => $oss_up->getError()
            ];
        } else {
            // 上传成功 获取上传文件信息
            $res = [
                'state' => 'SUCCESS'
            ];
            if ($hasOne) {
                $info = reset($result);
                $res['url'] = $info['savepath'] . $info['savename'];
                $res['title'] = $info['savename'];
                $res['original'] = $info['name'];
                $res['type'] = $info['type'];
                $res['size'] = $info['size'];
                $res['url'] = get_image_path($res['url']);
            } else {
                foreach ($result as $k => $v) {
                    $result[$k]['url'] = $v['savepath'] . $v['savename'];
                    $result[$k]['url'] = get_image_path($result[$k]['url']);
                }
                $res['url'] = $result;
            }
        }
        return $res;
    }

    // 显示文件列表
    private function lists()
    {
        /* 判断类型 */
        switch ($_GET['action']) {
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $this->conf['fileManagerAllowFiles'];
                $listSize = $this->conf['fileManagerListSize'];
                $path = $this->conf['fileManagerListPath'];
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $this->conf['imageManagerAllowFiles'];
                $listSize = $this->conf['imageManagerListSize'];
                $path = $this->conf['imageManagerListPath'];
        }
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = $start + $size;

        /* 获取文件列表 */
        $path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "" : "/") . $path;
        $files = $this->getfiles($path, $allowFiles);
        if (!count($files)) {
            return json_encode([
                "state" => "no match file",
                "list" => [],
                "start" => $start,
                "total" => count($files)
            ]);
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = []; $i < $len && $i >= 0 && $i >= $start; $i--) {
            $list[] = $files [$i];
        }
        // 倒序
        // for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
        // $list[] = $files[$i];
        // }

        /* 返回数据 */
        $result = json_encode([
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ]);

        return $result;
    }

    // 抓取远程文件
    private function crawler()
    {
        set_time_limit(0);

        /* 上传配置 */
        $config = [
            "pathFormat" => $this->conf['catcherPathFormat'],
            "maxSize" => $this->conf['catcherMaxSize'],
            "allowFiles" => $this->conf['catcherAllowFiles'],
            "oriName" => "remote.png"
        ];
        $fieldName = $this->conf['catcherFieldName'];

        /* 抓取远程图片 */
        $list = [];
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote");
            $info = $item->getFileInfo();
            array_push($list, [
                "state" => $info["state"],
                "url" => $info["url"],
                "size" => $info["size"],
                "title" => htmlspecialchars($info["title"]),
                "original" => htmlspecialchars($info["original"]),
                "source" => htmlspecialchars($imgUrl)
            ]);
        }

        /* 返回抓取数据 */
        return json_encode([
            'state' => count($list) ? 'SUCCESS' : 'ERROR',
            'list' => $list
        ]);
    }

    /**
     * 遍历获取目录下的指定类型的文件
     *
     * @param
     *            $path
     * @param array $files
     * @return array
     */
    private function getfiles($path, $allowFiles, &$files = [])
    {
        if (!is_dir($path)) {
            return null;
        }
        if (substr($path, strlen($path) - 1) != '/') {
            $path .= '/';
        }
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(" . $allowFiles . ")$/i", $file)) {
                        $files [] = [
                            'url' => substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                            'mtime' => filemtime($path2)
                        ];
                    }
                }
            }
        }
        return $files;
    }
}
