<?php

namespace App\Modules\Api\Controllers\V3;

use App\Modules\Api\Foundation\Controller;
use App\Repositories\Brand\BrandRepository;
use App\Modules\Api\Transformers\BrandTransformer;

/**
 * Class BrandController
 * @package App\Api\Controllers\Brand
 */
class BrandController extends Controller
{
    /** @var  $brand */
    protected $brand;

    /** @var $brandTransformer */
    protected $brandTransformer;

    /**
     * Brand constructor.
     * @param BrandRepository $brand
     * @param BrandTransformer $brandTransformer
     */
    public function __construct(BrandRepository $brand, BrandTransformer $brandTransformer)
    {
        parent::__construct();
        $this->brand = $brand;
        $this->brandTransformer = $brandTransformer;
    }

    /**
     * ecapi.brand.list
     * @return mixed
     */
    public function actionList()
    {
        $data = $this->brand->getAllBrands();
        $this->apiReturn($data);
    }

    /**
     * ecapi.brand.get
     * @param $args
     * @return mixed
     */
    public function actionGet($id)
    {
        $data = $this->brand->getBrandDetail($id);
        $this->apiReturn($data);
    }
}
