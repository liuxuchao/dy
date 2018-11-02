<?php

namespace App\Api\Controllers\Wx;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\ShareService;
use App\Api\Controllers\Controller;

/**
 * Class ShareController
 * @package App\Api\Controllers\Wx
 */
class ShareController extends Controller
{
    private $shareService;
    private $authService;

    public function __construct(ShareService $shareService, AuthService $authService)
    {
        $this->shareService = $shareService;
        $this->authService = $authService;
    }

    /**
     * 分享
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',
            'path' => 'required|string'
        ]);

        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }
        $share = $this->shareService->Share($uid, $request->get('path'));

        return $this->apiReturn($share);
    }
}
