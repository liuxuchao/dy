<?php

namespace App\Api\Foundation;

/**
 * Class Transformer
 * @package App\Api\Foundation
 */
abstract class Transformer
{

    /**
     * @param array $map
     * @return array
     */
    public function transformCollection(array $map)
    {
        return array_map([$this, 'transform'], $map);
    }

    /**
     * @param array $map
     * @return mixed
     */
    abstract public function transform(array $map);
}
