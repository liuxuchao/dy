<?php

namespace App\Modules\Console\Controllers;

use App\Modules\Base\Controllers\BackendController;

class IndexController extends BackendController
{
    /**
     * 编辑控制台
     */
    public function actionIndex()
    {
        $this->display();
    }
}
