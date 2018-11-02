<?php

namespace App\Api\Transformers;

use App\Models\Users as User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id' => $user->user_id,
            'name' => $user->user_name,
        ];
    }
}
