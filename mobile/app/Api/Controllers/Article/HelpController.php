<?php

namespace App\Api\Controllers\Article;

use App\Api\Controllers\Controller;
use App\Repositories\Article\ArticleRepository;
use App\Repositories\Article\CategoryRepository;

/**
 * Class HelpController
 * @package App\Api\Controllers\Article
 */
class HelpController extends Controller
{
    /**
     * @var CategoryRepository
     */
    protected $category;

    /**
     * @var ArticleRepository
     */
    protected $article;

    /**
     * Help constructor.
     * @param $category
     * @param $article
     */
    public function __construct(CategoryRepository $category, ArticleRepository $article)
    {
        $this->category = $category;
        $this->article = $article;
    }

    /**
     * 帮助中心
     * @param array $args
     * @return array|mixed
     */
    public function actionList(array $args)
    {
        $help = S('shop_help');
        if (!$help) {
            $help = [];

            // 网店信息
            $intro = $this->category->detail(['cat_type' => INFO_CAT], ['cat_id', 'cat_name']);
            $intro['list'] = $this->article->all($intro['id'], ['title']);
            $help[] = $intro;

            // 网店帮助
            $list = $this->category->all(['cat_type' => HELP_CAT], ['cat_id', 'cat_name']);
            foreach ($list['data'] as $key => $item) {
                $item['list'] = $this->article->all($item['id'], ['title']);
                $help[] = $item;
            }

            S('shop_help', $help);
        }

        return $help;
    }
}
