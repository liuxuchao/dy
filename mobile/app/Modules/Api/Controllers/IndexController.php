<?php

namespace App\Modules\Api\Controllers;

use App\Modules\Api\Foundation\Controller;

class IndexController extends Controller
{
    public function actionIndex()
    {
        $this->resp(['foo' => 'bar']);
    }

}