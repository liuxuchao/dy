<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class ProjectRelease
 * @package App\Console\Commands
 */
class ProjectRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:lite {type=free}'; // type = [free, basic, advanced]

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'release the project';

    /**
     * root path.
     *
     * @var string
     */
    private $base_path = '';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->argument('type');
        $this->base_path = base_path();

        // 必删文件列表
        $free = [
            'app/Modules/Touchim/*',
            'app/Plugins/connect/facebook.php',
            'app/Plugins/payment/paypal.php',
            'resources/docs/*',
            'resources/client/*',
            'resources/views/touchim/*',
            'scripts/*',
            'tests/*',
            '.bowerrc',
            '.gitattributes',
            '.gitignore',
            '.php_cs.dist',
            'bower.json',
            'CHANGELOG.md',
            'composer.json',
            'package.json',
            'README.md',
            'webpack.mix.js',
        ];

        // 基础版文件列表
        $basic = [
            'app/Console/Commands/CustomerService.php',
            'app/Modules/Chat/Controllers/AdminController.php',
            'app/Modules/Chat/Controllers/AdminpController.php',
            'app/Modules/Chat/Controllers/IndexController.php',
            'app/Modules/Chat/Controllers/LoginController.php',
            'app/Modules/Chat/Models/Kefu.php',
            'app/Modules/Chat/Views/*',
            'app/Modules/Purchase/*',
            'app/Modules/Qrpay/*',
            'app/Modules/Wechat/*',
            'app/Plugins/payment/wxpay.php',
            'app/Extensions/Wechat.php',
            'app/Extensions/Wxapp.php',
            'app/Extensions/WorkerEvent.php',
            'database/*',
            'public/css/console_wechat.css',
            'public/css/console_wechat_seller.css',
            'public/assets/wechat/*',
            'public/assets/qrpay/*',
            'public/fonts/wechat/*',
            'public/css/wechat/*',
            'public/css/wechat.css',
            'public/css/wechat.min.css',
            'artisan',
            'respond_wxh5.php',
            'resources/views/purchase/*',
            'resources/views/respond/index.wxh5.html',
        ];

        // 高级版文件列表
        $advanced = [
            'app/Modules/Bargain/*',
            'app/Modules/Drp/*',
            'app/Modules/Team/*',
            'public/css/console_team.css',
            'public/css/team.css',
            'public/css/team.min.css',
        ];

        if ($type == 'free') {
            $allfile = array_merge($free, $basic, $advanced);
        } elseif ($type == 'basic') {
            $allfile = array_merge($free, $advanced);
        } else {
            $allfile = $free;
        }

        /**
         * 删除文件
         */
        foreach ($allfile as $vo) {
            $this->delete($vo);
        }

        /**
         * 删除文档
         */
        $docs_file = glob($this->base_path . '/app/Modules/*/Docs');
        foreach ($docs_file as $vo) {
            $this->del_dir($vo);
        }
    }

    /**
     * @param string $file
     */
    private function delete($file = '')
    {
        $suffix = substr($file, -2);
        if ($suffix == '/*') {
            $this->del_dir($this->base_path . '/' . substr($file, 0, -1));
        } elseif ($suffix == '_*') {
            $this->del_pre($this->base_path . '/' . substr($file, 0, -1));
        } else {
            @unlink($this->base_path . '/' . $file);
        }
    }

    /**
     * @param $dir
     * @return bool
     */
    private function del_dir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                is_dir("$dir/$file") ? $this->del_dir("$dir/$file") : @unlink("$dir/$file");
            }
        }
        if (readdir($handle) == false) {
            closedir($handle);
            @rmdir($dir);
        }
    }

    /**
     * @param $files
     */
    private function del_pre($files)
    {
        $dir = dirname($files);
        //打开目录
        $handle = opendir($dir);
        //列出目录中的文件
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                $prefix = basename($files);
                $FP = stripos($file, $prefix);
                if ($FP === 0) {
                    @unlink($dir . '/' . $file);
                }
            }
        }
        closedir($handle);
    }
}
