<?php

namespace App\Api\Controllers\Article;

use App\Api\Controllers\Controller;
use App\Repositories\Article\CategoryRepository;

/**
 * Class CategoryController
 * @package App\Api\Controllers\Article
 */
class CategoryController extends Controller
{
    protected $category;

    /**
     * Category constructor.
     * @param CategoryRepository $category
     */
    public function __construct(CategoryRepository $category)
    {
        $this->category = $category;
    }

    /**
     * 类别列表
     * @return mixed
     */
    public function index()
    {
        return $this->category->all();
    }

    /**
     * 类别详情
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        return $this->category->detail($id);
    }
}
