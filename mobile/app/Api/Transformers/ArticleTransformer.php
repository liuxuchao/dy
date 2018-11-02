<?php

namespace App\Api\Transformers;

use App\Models\Article;
use League\Fractal\TransformerAbstract;

class ArticleTransformer extends TransformerAbstract
{
    public function transform(Article $article)
    {
        return [
            'id' => $article->article_id,
            'title' => $article->title,
        ];
    }
}
