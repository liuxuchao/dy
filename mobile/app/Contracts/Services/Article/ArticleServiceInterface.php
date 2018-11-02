<?php

namespace App\Contracts\Services\Article;

/**
 * Interface ArticleServiceInterface
 * @package App\Contracts\Services\Article
 */
interface ArticleServiceInterface
{
    /**
     * 文章列表
     * @param $id
     * @return mixed
     */
    public function all($id);

    /**
     * 文章详情
     * @param $id
     * @return mixed
     */
    public function show($id);

    /**
     * 用户协议
     * @return mixed
     */
    public function agreement();

    /**
     * 帮助中心
     * @return mixed
     */
    public function help();
}
