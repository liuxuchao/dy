<?php


class flat
{

    /**
     * 配置信息
     */
    public $configure;

    /**
     * 构造函数
     *
     * @param: $configure[array]    配送方式的参数的数组
     *
     * @return null
     */
    public function __construct($cfg = [])
    {
        foreach ($cfg as $key => $val) {
            $this->configure[$val['name']] = $val['value'];
        }
    }

    /**
     * 计算订单的配送费用的函数
     *
     * @param   float $goods_weight 商品重量
     * @param   float $goods_amount 商品金额
     * @return  decimal
     */
    public function calculate($goods_weight, $goods_amount)
    {
        if ($this->configure['free_money'] > 0 && $goods_amount >= $this->configure['free_money']) {
            return 0;
        } else {
            return isset($this->configure['base_fee']) ? $this->configure['base_fee'] : 0;
        }
    }

    /**
     * 查询发货状态
     * 该配送方式不支持查询发货状态
     *
     * @access  public
     * @param   string $invoice_sn 发货单号
     * @return  string
     */
    public function query($invoice_sn)
    {
        $str = '<a class="btn-default-new tracking-btn" href="https://m.kuaidi100.com/result.jsp?nu=' . $invoice_sn . '">订单跟踪</a>';
        return $str;
    }

    /**
     * 查询快递API
     *
     * @param string $invoice_sn
     * @return mixed
     */
    public function api($invoice_sn = '')
    {
        return false;
    }
}
