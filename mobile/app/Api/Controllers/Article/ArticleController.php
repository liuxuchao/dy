<?php

namespace App\Api\Controllers\Article;

use App\Api\Controllers\Controller;
use App\Api\Transformers\ArticleTransformer;
use App\Repositories\Article\ArticleRepository;

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
     * @var ArticleTransformer
     */
    protected $articleTransformer;

    /**
     * Article constructor.
     * @param ArticleRepository $article
     * @param ArticleTransformer $articleTransformer
     */
    public function __construct(ArticleRepository $article, ArticleTransformer $articleTransformer)
    {
        $this->article = $article;
        $this->articleTransformer = $articleTransformer;
    }

    /**
     * 文章列表
     * @param array $args
     * @return mixed
     */
    public function actionList(array $args)
    {
        $result = $this->article->all($args['id']);
        $result['data'] = $this->articleTransformer->transformCollection($result['data']);
        return $result;
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

    /**
     * 用户协议
     * @param array $args
     * @return mixed
     */
    public function actionAgreement(array $args)
    {
        return $this->article->detail(['cat_id' => '-1']);
    }
}
