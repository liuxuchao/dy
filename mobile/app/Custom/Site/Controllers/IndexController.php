<?php

namespace App\Custom\Site\Controllers;

use App\Modules\Site\Controllers\IndexController as FoundationController;

class IndexController extends FoundationController
{
    /**
     * URL路由访问地址: mobile/index.php?m=site&c=index&a=about
     */
    public function actionAbout()
    {
        $this->display();
    }

    public function actionPhpinfo()
    {
        // phpinfo();
    }
}
