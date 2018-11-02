<?php

namespace App\Repositories\Article;

use App\Models\Article;
use App\Models\ArticleCat;
use App\Models\ArticleExtend;

class ArticleRepository
{
    /**
     * @param $cat_id
     * @param array $columns
     * @param int $size
     * @param string $requirement
     * @return mixed
     */
    public function all($cat_id, $columns = ['*'], $page, $size)
    {
        $res = [];
        $current = ($page - 1) * $size;
        $article = Article::where('is_open', '=', 1);

        // 取出所有非0的文章
        if ($cat_id == '-1') {
            $article = $article->where('cat_id', '>', 0);
        } else {
            $cat_str = $this->get_article_children($cat_id);
            if ($cat_str) {
                array_unshift($cat_str, $cat_id);
                $article = $article->whereIn('cat_id', $cat_str);
            } else {
                $article = $article->where('cat_id', $cat_id);
            }
        }

        $res['article'] = $article
            ->offset($current)->limit($size)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('add_time', 'DESC')
            ->get()
            ->toArray();

        $res['num'] = $article
            ->count('article_id');
        return $res;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        if (is_array($id)) {
            $field = key($id);
            $value = $id[$field];
            $model = Article::where($field, '=', $value)->first();
        } else {
            $model = Article::find($id);
        }

        if (is_null($model)) {
            return false;
        }

        $article = $model->toArray();

        // 关联扩展字段
        if (is_null($model->extend)) {
            $data = [
                'article_id' => $model->article_id,
                'click' => 1,
                'likenum' => 0,
                'hatenum' => 0,
            ];
            ArticleExtend::create($data);
        } else {
            $data = $model->extend->toArray();
            unset($data['id']);
        }
        // 合并扩展字段
        $article = array_merge($article, $data);

        // 关联评论
        foreach ($model->comment as $vo) {
            $model->comment->push($vo->user);
        }
        $article['comment'] = $model->comment->where('id_value', '=', $id)->where('status', '=', 1)->toArray();
        // 关联商品
        $article['goods'] = $model->goods->toArray();
        return $article;
    }

    /**
     * 获得文章分类信息
     * @param  $id 文章ID
     * @return
     */
    public function articleCatInfo($id)
    {
        $where = [
            'article_id' => $id
        ];
        $article_cat = Article::join('article_cat', 'article.cat_id', '=', 'article_cat.cat_id')
            ->where($where)
            ->select('article_cat.cat_id', 'article_cat.cat_name')
            ->first();

        if ($article_cat === null) {
            return [];
        }

        return $article_cat->toArray();
    }

    /**
     * 获得文章分类下子分类ID
     * @param  $id 文章分类ID
     * @return
     */
    public function get_article_children($id)
    {
        $res = ArticleCat::select('cat_id')
                        ->where('parent_id', $id)
                        ->get()
                        ->toArray();

        foreach ($res as $k => $v) {
            $cat[$k] = $v['id'];
        }
        foreach ($cat as $key => $val) {
            $tree = $this->get_article_children($val);
            if (!empty($tree)) {
                $result = array_merge($cat, $tree);
            } else {
                $result = $cat;
            }
        }
        return $result;
    }
}
