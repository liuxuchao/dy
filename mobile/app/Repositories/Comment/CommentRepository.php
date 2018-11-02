<?php

namespace App\Repositories\Comment;

use App\Models\Comment;

class CommentRepository
{
    /**
     * 添加评论
     * @param $args
     * @return boolean
     */
    public function orderAppraiseAdd($args)
    {
        $commemt = new Comment();
        foreach ($args as $k => $v) {
            $commemt->$k = $v;
        }
        return $commemt->save();
    }
}
