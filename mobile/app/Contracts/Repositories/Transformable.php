<?php

namespace App\Contracts\Repositories;

/**
 * Interface Transformable
 * @package App\Contracts\Repositories
 */
interface Transformable
{
    /**
     * @return array
     */
    public function transform();
}
