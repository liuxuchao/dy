<?php

namespace App\Services;

use App\Repositories\Location\LocationRepository;

/**
 * Class LocationService
 * @package App\Services
 */
class LocationService
{
    private $locationRepository;
    private $goodsRepository;
    private $authService;
    private $goodsAttrRepository;

    /**
     * CartService constructor.
     * @param LocationRepository $locationRepository
     */
    public function __construct(
        LocationRepository $locationRepository
    ) {
        $this->locationRepository = $locationRepository;
    }

    /**
     * 地区列表
     * @return mixed
     */
    public function index($region_id = 0)
    {
        if ($region_id > 0) {
            $city = $this->locationRepository->index($region_id);
            return $city;
        }
        $url = '../../data/sc_file/pin_regions.php';
        $list = file_get_contents($url);
        $arr = explode("\r\n", $list);
        $area = $arr['1'];
        if (empty($area)) {
            $city = $this->locationRepository->index();
            foreach ($city as $key => $sett) {
                $sname = $sett['region_name'];
                $sett['region_name'] = $sname;
                $licity = $this->pinyin($sname);
                $area[$licity][$key] = $sett;
            }
            ksort($area);
        }
        return $area;
    }

    /**
     * 地区详情
     * @return mixed
     */
    public function specific($name)
    {
        $name = mb_substr($name, 0, 2);
        $region_name = $this->locationRepository->contrast($name);
        return $region_name;
    }

    public function pinyin($city)
    {
        $fchar = ord($city{0});
        if ($fchar >= ord('A') && $fchar <= ord('Z')) {
            return strtoupper($city{0});
        }
        $s1 = iconv('UTF-8', 'gb2312', $city);
        $s2 = iconv('gb2312', 'UTF-8', $s1);
        $s = $s2 == $city ? $s1 : $city;
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if ($asc >= -20319 && $asc <= -20284) {
            return 'A';
        }
        if ($asc >= -20283 && $asc <= -19776 || $asc >= -9743 && $asc <= -9743) {
            return 'B';
        }
        if ($asc >= -19775 && $asc <= -19219) {
            return 'C';
        }
        if ($asc >= -19218 && $asc <= -18711 || $asc >= -9767 && $asc <= -9767) {
            return 'D';
        }
        if ($asc >= -18710 && $asc <= -18527) {
            return 'E';
        }
        if ($asc >= -18526 && $asc <= -18240) {
            return 'F';
        }
        if ($asc >= -18239 && $asc <= -17923) {
            return 'G';
        }
        if ($asc >= -17922 && $asc <= -17418) {
            return 'H';
        }
        if ($asc >= -17417 && $asc <= -16475) {
            return 'J';
        }
        if ($asc >= -16474 && $asc <= -16213) {
            return 'K';
        }
        if ($asc >= -16212 && $asc <= -15641 || $asc >= -7182 && $asc <= -7182 || $asc >= -6928 && $asc <= -6928) {
            return 'L';
        }
        if ($asc >= -15640 && $asc <= -15166) {
            return 'M';
        }
        if ($asc >= -15165 && $asc <= -14923) {
            return 'N';
        }
        if ($asc >= -14922 && $asc <= -14915) {
            return 'O';
        }
        if ($asc >= -14914 && $asc <= -14631 || $asc >= -6745 && $asc <= -6745) {
            return 'P';
        }
        if ($asc >= -14630 && $asc <= -14150 || $asc >= -7703 && $asc <= -7703) {
            return 'Q';
        }
        if ($asc >= -14149 && $asc <= -14091) {
            return 'R';
        }
        if ($asc >= -14090 && $asc <= -13319) {
            return 'S';
        }
        if ($asc >= -13318 && $asc <= -12839) {
            return 'T';
        }
        if ($asc >= -12838 && $asc <= -12557) {
            return 'W';
        }
        if ($asc >= -12556 && $asc <= -11848) {
            return 'X';
        }
        if ($asc >= -11847 && $asc <= -11056) {
            return 'Y';
        }
        if ($asc >= -11055 && $asc <= -10247) {
            return 'Z';
        }
        return $asc;
    }
}
