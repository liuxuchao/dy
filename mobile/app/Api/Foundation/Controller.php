<?php

namespace App\Api\Foundation;

use App\Modules\Base\Controllers\Frontend;

/**
 * Class Controller
 * @package App\Api\Foundation
 */
class Controller extends Frontend
{

    /**
     * API返回访问
     * @param $data
     * @return array
     */
    protected function apiReturn($data, $code = 0)
    {
        return (['code' => $code, 'data' => $data]);
    }

    /**
     * 接口参数校验
     * @param $args
     * @param $pattern
     * @return array
     */
    protected function validate($args, $pattern)
    {
        $validator = Validation::createValidation();

        $rules = Validation::transPattern($pattern);

        if ($validator->validate($rules)->create($args) === false) {
            return $validator->getError();
        } else {
            return true;
        }
    }
}
