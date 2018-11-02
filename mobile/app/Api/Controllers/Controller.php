<?php

namespace App\Api\Controllers;

use Dingo\Api\Routing\Helpers;
use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * Class Controller
 * @package App\Api\Controllers
 */
class Controller extends BaseController
{
    use Helpers;

    /**
     * API返回访问
     * @param $data
     * @param $code
     * @return array
     */
    protected function apiReturn($data, $code = 0)
    {
        return (['code' => $code, 'data' => $data]);
    }
}
