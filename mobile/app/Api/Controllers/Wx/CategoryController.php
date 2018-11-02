<?php

namespace App\Api\Controllers\Wx;

use Illuminate\Http\Request;
use App\Services\Category\CategoryService;
use App\Api\Controllers\Controller;
use App\Services\AuthService;

/**
 * Class CategoryController
 * @package App\Api\Controllers\Wx
 */
class CategoryController extends Controller
{
    private $categoryService;
    private $authService;

    /**
     * CategoryController constructor.
     * @param CategoryService $categoryService
     */
    public function __construct(CategoryService $categoryService, AuthService $authService)
    {
        $this->categoryService = $categoryService;
        $this->authService = $authService;
    }

    /**
     * 分类列表
     * @return array
     */
    public function index(Request $request)
    {
        $this->validate($request, []);
        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }
        $list = $this->categoryService->categoryList($uid);

        return $this->apiReturn($list);
    }
}
