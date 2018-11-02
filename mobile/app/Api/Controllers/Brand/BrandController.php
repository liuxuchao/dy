<?php

namespace App\Api\Controllers\Brand;

use App\Api\Controllers\Controller;
use App\Api\Transformers\BrandTransformer;
use App\Repositories\Brand\BrandRepository;

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
    public function __construct(BrandRepository $brand)
    {
        //parent::__construct();
        $this->brand = $brand;
        //$this->brandTransformer = $brandTransformer;
    }


    /**
     * ecapi.brand.list
     * @return mixed
     */
    public function index()
    {
        $data = $this->brand->getAllBrands();

        return $this->apiReturn($data);
    }

    /**
     * ecapi.brand.get
     * @param $args
     * @return mixed
     */
    public function get($id)
    {
        $data = $this->brand->getBrandDetail($id);

        return $this->apiReturn($data);
    }
}
