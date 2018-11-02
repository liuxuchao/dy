<?php

namespace App\Contracts\Services\Article;

/**
 * Interface CategoryServiceInterface
 * @package App\Contracts\Services\Article
 */
interface CategoryServiceInterface
{
    /**
     * 类别列表
     * @param $id
     * @return mixed
     */
    public function category($id);

    /**
     * 类别详情
     * @param $id
     * @return mixed
     */
    public function detail($id);
}
