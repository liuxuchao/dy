<?php

namespace App\Modules\Api\Controllers;

use App\Modules\Api\Foundation\Controller;
use App\Services\AuthService;
use App\Services\RegionService;

/**
 * Class RegionController
 * @package App\Api\Controllers\Wx
 */
class RegionController extends Controller
{
    private $authService;
    private $regionService;

    public function __construct(AuthService $authService, RegionService $regionService)
    {
        $this->authService = $authService;
        $this->regionService = $regionService;
    }

    /**
     * 获取下级地区列表
     * @param Request $request
     * @return mixed
     */
    public function regionList(Request $request)
    {
        $this->validate($request, [
            'id' => 'integer'
        ]);


        //
        $args = $request->all();
        $list = $this->regionService->regionList($args);

        return $this->apiReturn($list);
    }
}
