<?php

namespace App\Services\V3\ThirdParty;

use App\Extensions\Http;

/**
 * Class ShippingService
 * @package App\Services\V3\ThirdParty
 */
class ShippingService
{
    /**
     * @var string
     */
    private $queryExpressUrl = 'https://m.kuaidi100.com/query?type=%s&postid=%s';

    /**
     * @param string $com
     * @param string $num
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getExpress($com = '', $num = '')
    {
        $url = sprintf($this->queryExpressUrl, $com, $num);

        $response = Http::doGet($url, 5, $this->defaultHeader($com, $num));
        $result = json_decode($response, true);

        if ($result['message'] === 'ok') {
            return ['error' => 0, 'data' => $result['data']];
        } else {
            return ['error' => 403, 'data' => $result['message']];
        }
    }

    /**
     * +     * 默认HTTP头
     * +     *
     * +     * Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.79 Safari/537.36
     * +     * Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1
     * +     *
     * +     * @return string
     * +     */
    public function defaultHeader($com = '', $nu = '')
    {
        $header = "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.%d Safari/537.%d\r\n";
        $header .= "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
        $header .= "Accept-Language: zh-cn,zh;q=0.5\r\n";
        $header .= "Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n";
        $header .= "Host: m.kuaidi100.com\r\n";
        $header .= "Referer: https://m.kuaidi100.com/result.jsp?com=" . $com . "&nu=" . $nu . "\r\n";
        $header .= "X-Requested-With: XMLHttpRequest\r\n";
        return sprintf($header, time(), time() + rand(1000, 9999));
    }

}