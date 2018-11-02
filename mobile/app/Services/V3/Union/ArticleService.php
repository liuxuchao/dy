<?php

namespace App\Services\V3\Union;

use App\Contracts\Services\Article\ArticleServiceInterface;
use App\Contracts\Services\Article\CategoryServiceInterface;
use App\Repositories\Article\ArticleRepository;
use App\Repositories\Article\CategoryRepository;

/**
 * Class ArticleService
 * @package App\Services\V3\Union
 */
class ArticleService implements ArticleServiceInterface, CategoryServiceInterface
{
    /**
     * @var ArticleRepository
     */
    private $article;

    /**
     * @var CategoryRepository
     */
    private $category;

    /**
     * ArticleService constructor.
     * @param CategoryRepository $categoryRepository
     * @param ArticleRepository $articleRepository
     */
    public function __construct(CategoryRepository $categoryRepository, ArticleRepository $articleRepository)
    {
        $this->category = $categoryRepository;
        $this->article = $articleRepository;
    }

    /**
     * 类别列表
     * @return mixed
     */
    public function category($id)
    {
        return 'category';
    }

    /**
     * 类别详情
     * @return mixed
     */
    public function detail($id)
    {
        return $this->category->detail($id);
    }

    /**
     * 文章列表
     * @return mixed
     */
    public function all($id)
    {
        return 'all';
    }

    /**
     * 文章详情
     * @return mixed
     */
    public function show($id)
    {
        return 'show';
    }

    /**
     * 用户协议
     * @return mixed
     */
    public function agreement()
    {
        return 'agreement';
    }

    /**
     * 帮助中心
     * @return mixed
     */
    public function help()
    {
        return 'help';
    }
}