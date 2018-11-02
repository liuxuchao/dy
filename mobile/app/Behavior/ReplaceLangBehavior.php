<?php

namespace App\Behavior;

use App\Libraries\Mysql;

/**
 * 系统行为扩展：替换分销语言包
 * Class ReplaceLangBehavior
 * @package App\Behavior
 */
class ReplaceLangBehavior
{
    private $model;

    public function run()
    {
        if (is_dir(APP_DRP_PATH)) {
            $this->model = new Mysql();
            $condition['code'] = 'custom_distribution';
            $condition2['code'] = 'custom_distributor';
            $this->custom = $this->model->table('drp_config')->where($condition)->getField("value"); // 分销->代言
            $this->customs = $this->model->table('drp_config')->where($condition2)->getField("value"); // 分销商->代言人
            C('custom', $this->custom);
            C('customs', $this->customs);
            $coustomes = L();
            if (is_array($coustomes)) {
                foreach ($coustomes as $key => $val) {
                    L($key, str_replace("分销", $this->custom, str_replace("分销商", $this->customs, $val)));
                }
            }
        }
    }
}
