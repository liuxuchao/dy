<?php

namespace App\Modules\Api\Controllers\V3;

use App\Modules\Api\Foundation\Controller;
use App\Repositories\Article\ArticleRepository;
use App\Repositories\Article\CategoryRepository;
use App\Modules\Api\Transformers\ArticleTransformer;

/**
 * Class ArticleController
 * @package App\Api\Controllers\Article
 */
class ArticleController extends Controller
{

    /**
     * @var ArticleRepository
     */
    protected $article;

    /**
     * @var CategoryRepository
     */
    protected $category;

    /**
     * @var ArticleTransformer
     */
    protected $articleTransformer;

    /**
     * ArticleController constructor.
     * @param ArticleRepository $article
     * @param CategoryRepository $category
     * @param ArticleTransformer $articleTransformer
     */
    public function __construct(ArticleRepository $article, CategoryRepository $category, ArticleTransformer $articleTransformer)
    {
        parent::__construct();
        $this->article = $article;
        $this->category = $category;
        $this->articleTransformer = $articleTransformer;
    }

    /**
     * 文章类别
     * @param null $id
     */
    public function actionCategory($id = null)
    {
        if (is_null($id)) {
            $data = $this->category->all();
        } else {
            $data = $this->category->detail($id);
        }

        $this->resp($data);
    }

    /**
     * 文章列表
     * @param array $args
     * @return mixed
     */
    public function actionList(array $args)
    {
        $result = $this->article->all($args['id']);
        $data = $this->articleTransformer->transformCollection($result['data']);

        $this->apiReturn($data);
    }

    /**
     * 文章详情
     * @param array $args
     * @return array|mixed
     */
    public function actionGet(array $args)
    {
        $result = $this->article->detail($args['id']);
        $result = $this->articleTransformer->transform($result);
        return $result;
    }

    public function actionAbout()
    {
        $data = $this->article->all(2);

        return $this->resp($data);
    }

    /**
     * 帮助中心
     * @return array|mixed
     */
    public function actionHelp()
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

    /**
     * 用户协议
     */
    public function actionAgreement()
    {
        $data = $this->article->detail(['cat_id' => '-1']);

        return $this->resp($data);
    }
}
