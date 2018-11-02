<?php

namespace App\Services;

use App\Repositories\Region\RegionRepository;

class RegionService
{
    private $regionRepository;

    public function __construct(RegionRepository $regionRepository)
    {
        $this->regionRepository = $regionRepository;
    }

    /**
     * 地区下级列表
     * @param $args
     * @return mixed
     */
    public function regionList($args)
    {
        // $regionId = empty($args['id']) ? 0 : $args['id'];
        // if (empty($args['id'])) {
        //     $list = $this->regionRepository->regionListByType(0);
        // } else {
        //     $type = $this->regionRepository->getRegionTypeById($regionId);
        //     $list = $this->regionRepository->regionListByType($type + 1);
        // }

        // foreach ($list as $k => $v) {
        //     if ($v['parent_id'] != $regionId) {
        //         unset($list[$k]);
        //     }
        // }

        // sort($list);

        $list = $this->regionRepository->getRegionAll($args);
        return $list;
    }
}
