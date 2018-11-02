<?php

namespace App\Modules\Goods\Controllers;

use App\Services\Erp\JigonService;
use Think\Image;
use App\Extensions\Http;
use Endroid\QrCode\QrCode;
use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    protected $user_id = 0;
    protected $goods_id = 0;
    protected $region_id = 0;
    protected $area_id = 0;
    protected $area_city = 0;
    protected $size = 10;

    public function __construct()
    {
        parent::__construct();
        L(require(LANG_PATH . C('shop.lang') . '/goods.php'));
        L(require(LANG_PATH . C('shop.lang') . '/user.php'));
        $this->assign('lang', array_change_key_case(L()));
        $this->goods_id = I('id', 0, 'intval');
        if ($this->goods_id == 0) {
            /* 如果没有传入id则跳回到首页 */
            ecs_header("Location: ./\n");
            exit;
        }
        $this->user_id = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $this->assign('goods_id', $this->goods_id); //商品ID
        //初始化位置信息
        $this->init_params();
        $this->area_id = !empty($this->area_info['region_id']) ? $this->area_info['region_id'] : 0;
        $this->assign('region_id', $this->region_id); //商品仓库region_id
        $this->assign('area_id', $this->area_id); //地区ID
        $this->assign('area_city', $this->area_city); // 城市ID
        $this->assign('custom', C('custom')); //原分销
    }

    public function actionIndex()
    {
        //ecmoban模板堂 --zhuo start 仓库
        $pid = I('request.pid', 0, 'intval');
        //ecmoban模板堂 --zhuo end 仓库

        $goods = get_goods_info($this->goods_id, $this->region_id, $this->area_id, $this->area_city);
        if ($goods === false || !isset($goods['goods_name'])) {
            /* 如果没有找到任何记录则跳回到首页 */
            ecs_header("Location: ./\n");
            exit;
        }

        /* 查看是否秒杀商品 */
        $sec_goods_id = $this->get_is_seckill($this->goods_id);

        if ($sec_goods_id) {
            $this->redirect(url('seckill/index/detail', ['id' => $sec_goods_id, 'tmr' => 0]));
            exit;
        }

        //分销跳转
        /*if (empty($goods['user_id']) && !empty($this->user_id) && isset($_GET['u']) === false) {
            $this->redirect('goods/index/index', array('id'=>$this->goods_id, 'u'=>$this->user_id));
        }*/
        if (is_dir(APP_DRP_PATH)) {
            $isdrp = $this->model->table('drp_config')->field('value')->where(['code' => 'isdrp'])->find();
            $sql = "SELECT id FROM {pre}drp_shop WHERE audit=1 AND status=1 AND user_id=" . $this->user_id;
            $drp = $this->db->getOne($sql);
            $this->assign('drp', $drp);
            $this->assign('isdrp', $isdrp['value']);
            if ($goods['dis_commission'] > 0 && $goods['is_distribution'] == 1 && !empty($this->user_id) && $_GET['d'] != $this->user_id && $drp > 0) {
                $this->redirect('goods/index/index', ['id' => $this->goods_id, 'd' => $this->user_id]);
            }
        }
        $share_type = 0;
        if (isset($isdrp) && $isdrp['value'] == 1 && isset($drp) && $drp > 0 && $goods['is_distribution'] == 1) {
            $share_type = 1;
        }
        $this->assign('share_type', $share_type); // 分享类型

        //商品服务
        $is_reality = get_goods_extends($this->goods_id);
        $this->assign('is_reality', $is_reality);
        $this->assign('id', $this->goods_id);
        $this->assign('type', 0);
        $this->assign('cfg', C('shop'));

        //ecmoban模板堂 --zhuo start 限购
        $start_date = $goods['xiangou_start_date'];
        $end_date = $goods['xiangou_end_date'];
        $nowTime = gmtime();
        if ($nowTime > $start_date && $nowTime < $end_date) {
            $xiangou = 1;
        } else {
            $xiangou = 0;
        }
        $order_goods = get_for_purchasing_goods($start_date, $end_date, $this->goods_id, $this->user_id);
        $this->assign('xiangou', $xiangou);
        $this->assign('orderG_number', $order_goods['goods_number']);
        //ecmoban模板堂 --zhuo end 限购

        //ecmoban模板堂 --zhuo start
        $shop_info = get_merchants_shop_info('merchants_steps_fields', $goods['user_id']);
        $adress = get_license_comp_adress($shop_info['license_comp_adress']);
        $this->assign('shop_info', $shop_info);
        $this->assign('adress', $adress);
        //ecmoban模板堂 --zhuo end
        $volume_price_list = get_volume_price_list($goods['goods_id'], '1');
        $this->assign('volume_price_list', $volume_price_list);    // 商品优惠价格区间

        //ecmoban模板堂 --zhuo start 仓库
        $province_list = get_warehouse_province();
        $this->assign('province_list', $province_list); //省、直辖市

        $city_list = get_region_city_county($this->province_id);
        if ($city_list) {
            foreach ($city_list as $k => $v) {
                $city_list[$k]['district_list'] = get_region_city_county($v['region_id']);
            }
        }
        $this->assign('city_list', $city_list); //省下级市

        $district_list = get_region_city_county($this->city_id);
        $this->assign('district_list', $district_list);//市下级县

        $warehouse_list = get_warehouse_list_goods();
        $this->assign('warehouse_list', $warehouse_list); //仓库列

        $warehouse_name = get_warehouse_name_id($this->region_id);
        $this->assign('warehouse_name', $warehouse_name); //仓库名称

        $this->assign('user_id', $_SESSION['user_id']);
        $this->assign('shop_price_type', $goods['model_price']); //商品价格运营模式 0代表统一价格（默认） 1、代表仓库价格 2、代表地区价格
        //ecmoban模板堂 --zhuo start 仓库

        $shop_price = $goods['shop_price'] ? $goods['shop_price'] : 0;
        $goods['goods_style_name'] = add_style($goods['goods_name'], $goods['goods_name_style']);
        /* 购买该商品可以得到多少钱的红包 */
        if ($goods['bonus_type_id'] > 0) {
            $time = gmtime();
            $sql = "SELECT type_money FROM {pre}bonus_type" .
                " WHERE type_id = '$goods[bonus_type_id]' " .
                " AND send_type = '" . SEND_BY_GOODS . "' " .
                " AND send_start_date <= '$time'" .
                " AND send_end_date >= '$time'";
            $goods['bonus_money'] = floatval($this->db->getOne($sql));
            if ($goods['bonus_money'] > 0) {
                $goods['bonus_money'] = price_format($goods['bonus_money']);
            }
        }

        /*获取可用门店数量*/
        $goods['store_count'] = 0;
        $sql = "SELECT COUNT(*) FROM {pre}offline_store AS o LEFT JOIN {pre}store_goods AS s ON o.id = s.store_id WHERE s.goods_id = '" . $this->goods_id . "' AND o.is_confirm = 1 ";
        $store_goods = $this->db->getOne($sql);
        if ($store_goods > 0) {
            $goods['store_count'] = 1;
            $store_id = getStoreIdByGoodsId($this->goods_id); // 商品所在门店ID
            $store = dao('offline_store')->field('id, stores_name')->where(['id' => $store_id])->find();
            $store['take_time'] = local_date('Y-m-d H:i:s', gmtime() + 3600 * 24);
            $store['store_mobile'] = dao('users')->where(['user_id' => $_SESSION['user_id']])->getField('mobile_phone');
            $this->assign('store', $store);
        }
        // 促销信息
        // 商品满减阶梯
        $consumption = '';
        if ($goods['consumption']) {
            foreach ($goods['consumption'] as $key => $v) {
                $consumption .= "满" . $v['cfull'] . "减" . $v['creduce'] . ",";
            }
            $consumption = ['act_name' => rtrim($consumption, ','), 'url' => '#', 'type' => 'consumption'];
        }

        $promotion = get_promotion_info($this->goods_id, $goods['user_id']);
        if (!empty($consumption)) {
            $promotion[] = $consumption; // 将商品满减阶梯与促销信息合并
        }
        $this->assign('promote_end_time', $goods['gmt_end_time']);
        $this->assign('promotion', $promotion);
        $this->assign('promotion_info', get_promotion_info('', $goods['user_id']));
        //$this->assign('categories', get_categories_tree($goods['cat_id']));  // 分类树

        $position = assign_ur_here($goods['cat_id'], $goods['goods_name']);

        // SEO标题
        $seo = get_seo_words('goods');
        foreach ($seo as $key => $value) {
            $seo[$key] = html_in(str_replace(['{sitename}', '{key}', '{name}', '{description}'], [C('shop.shop_name'), $goods['keywords'], $goods['goods_name'], $goods['goods_brief']], $value));
        }

        $page_title = !empty($seo['title']) ? $seo['title'] : $position['title'];
        $keywords = !empty($seo['keywords']) ? $seo['keywords'] : (!empty($goods['keywords']) ? $goods['keywords'] : C('shop.shop_keywords'));
        $description = !empty($seo['description']) ? $seo['description'] : (!empty($goods['goods_brief']) ? $goods['goods_brief'] : C('shop.shop_desc'));

        // 微信JSSDK分享
        $share_data = [
            'title' => $page_title,
            'desc' => $description,
            'link' => '',
            'img' => $goods['goods_img'],
        ];
        $this->assign('share_data', $this->get_wechat_share_content($share_data));
        $this->assign('page_title', $page_title);
        $this->assign('keywords', $keywords);
        $this->assign('description', $description);

        $properties = get_goods_properties($this->goods_id, $this->region_id, $this->area_id);  // 获得商品的规格和属性
        $this->assign('properties', $properties['pro']);                              // 商品属性
        //默认选中的商品规格 by wanglu
        $default_spe = '';
        $default_attr_id = 0; // 默认属性id
        if ($properties['spe']) {
            foreach ($properties['spe'] as $k => $v) {
                if ($v['attr_type'] == 1) {
                    if ($v['is_checked'] > 0) {
                        foreach ($v['values'] as $key => $val) {
                            $default_spe .= $val['checked'] == 1 ? $val['label'] . '、' : '';
                            if ($val['checked'] == 1) {
                                $default_attr_id = $val['id'];
                            }
                        }
                    } else {
                        foreach ($v['values'] as $key => $val) {
                            if ($key == 0) {
                                $default_spe .= $val['label'] . '、';
                                $default_attr_id = $val['id'];
                            }
                        }
                    }
                }
            }
        }
        if (!empty($default_attr_id)) {
            $default_attr_id = !empty($default_attr_id) ? explode(',', $default_attr_id) : [];
            if ($GLOBALS['_CFG']['add_shop_price'] == 1) {
                $add_tocart = 1;
            } else {
                $add_tocart = 0;
            }
            // 最终价格
            $shop_price = get_final_price($this->goods_id, 1, true, $default_attr_id, $this->region_id, $this->area_id, $this->area_city, 0, 0, $add_tocart);
            $goods['shop_price'] = $shop_price;
            $goods['shop_price_formated'] = price_format($goods['shop_price']);
        }
        if (!empty($goods['goods_video'])) {
            $goods['goods_video'] = get_image_path($goods['goods_video']);
        }
        $this->assign('goods', $goods);
        $this->assign('goods_id', $goods['goods_id']);
        //加入购物车的模式
        if (empty($properties['spe'])) {
            $add_type = 0;
        } else {
            $add_type = 1;
        }
        $this->assign('add_type', $add_type);
        $this->assign('default_spe', $default_spe);                              // 默认商品规格
        $this->assign('specification', $properties['spe']);                              // 商品规格
        $this->assign('attribute_linked', get_same_attribute_goods($properties));           // 相同属性的关联商品
        $this->assign('rank_prices', get_user_rank_prices($this->goods_id, $shop_price));    // 会员等级价格
        $this->assign('pictures', get_goods_gallery($this->goods_id));                    // 商品相册
        $this->assign('bought_goods', get_also_bought($this->goods_id));                      // 购买了该商品的用户还购买了哪些商品
        $this->assign('goods_rank', get_goods_rank($this->goods_id));                       // 商品的销售排名
        // 组合套餐
        $group_count = get_group_goods_count($this->goods_id);
        if ($group_count > 0) {
            $comboTabIndex = get_cfg_group_goods(); // 配件类型
            $this->assign('comboTab', $comboTabIndex);

            $fittings_list = get_goods_fittings([$this->goods_id], $this->region_id, $this->area_id);
            if (is_array($fittings_list)) {
                foreach ($fittings_list as $vo) {
                    $fittings_index[$vo['group_id']] = $vo['group_id'];//关联数组
                }
            }
            ksort($fittings_index); //重新排序
            $this->assign('fittings_tab_index', $fittings_index); //套餐数量

            // 节省金额最高 排序最前
            $fittings_list = get_array_sort($fittings_list, 'spare_price_ori', 'DESC');
            $fittings_list = array_values($fittings_list); // 重新对数组键排序 值不变
            $this->assign('fittings', $fittings_list); // 配件列表
        }

        //获取关联礼包
        $package_goods_list = get_package_goods_list($goods['goods_id']);
        $this->assign('package_goods_list', $package_goods_list);    // 获取关联礼包

        assign_dynamic('goods');
        $volume_price_list = get_volume_price_list($goods['goods_id'], '1');
        $this->assign('volume_price_list', $volume_price_list);    // 商品优惠价格区间

        $this->assign('sales_count', get_goods_sales($this->goods_id)); // 销量

        //商品运费
        $region = [1, $this->province_id, $this->city_id, $this->district_id, $this->town_region_id];
        $shippingFee = goodsShippingFee($this->goods_id, $this->region_id, $this->area_id, $region);
        $this->assign('shippingFee', $shippingFee);
        $this->assign('is_shipping', $goods['is_shipping']); // 是否免运费商品

        // 检查是否已经存在于用户的收藏夹
        if ($_SESSION ['user_id']) {
            $where['user_id'] = $_SESSION ['user_id'];
            $where['goods_id'] = $this->goods_id;
            $rs = $this->db->table('collect_goods')->where($where)->count();
            if ($rs > 0) {
                $this->assign('goods_collect', 1);
            }
        }
        /* 更新点击次数 */
        $this->db->query('UPDATE ' . $this->ecs->table('goods') . " SET click_count = click_count + 1 WHERE goods_id = '$this->goods_id'");
        /* 记录浏览历史 */
        if (!empty($_COOKIE['ECS']['history_goods'])) {
            $history = explode(',', $_COOKIE['ECS']['history_goods']);
            foreach ($history as $key => $val) {
                $arry[$key] = explode('_', $val);
                if ($arry[$key][0] == $this->goods_id) {
                    unset($arry[$key]);
                }
            }
            $arry = array_merge($arry);
            foreach ($arry as $key => $val) {
                $his[$key] = $val[0] . '_' . $val[1];
            }
            array_unshift($his, $this->goods_id . '_' . $nowTime);
            $his = array_unique($his);
            while (count($his) > C('shop.history_number')) {
                array_pop($his);
            }
            cookie('ECS[history_goods]', implode(',', $his));
        } else {
            cookie('ECS[history_goods]', $this->goods_id . '_' . $nowTime);
        }
        //ecmoban模板堂 --zhuo 仓库 start
        $this->assign('province_row', get_region_name($this->province_id));
        $this->assign('city_row', get_region_name($this->city_id));
        $this->assign('district_row', get_region_name($this->district_id));
        $this->assign('town_row', get_region_name($this->town_region_id));

        $goods_region = [
            'country' => 1,
            'province' => $this->province_id,
            'city' => $this->city_id,
            'district' => $this->district_id,
            'town_region_id' => $this->town_region_id,
        ];
        $this->assign('goods_region', $goods_region);

        $date = ['shipping_code'];
        $where = "shipping_id = '" . $goods['default_shipping'] . "'";
        $shipping_code = get_table_date('shipping', $where, $date, 2);

        $cart_num = cart_number();
        $this->assign('cart_num', $cart_num); // 购物车数量

        // 选择所在地区下的门店列表
        $area_position_list = get_goods_user_area_position($goods['user_id'], $this->city_id);
        $this->assign('area_position_list', $area_position_list);

        //评分 start
        $mc_all = ments_count_all($this->goods_id);       //总条数
        $mc_one = ments_count_rank_num($this->goods_id, 1);        //一颗星
        $mc_two = ments_count_rank_num($this->goods_id, 2);        //两颗星
        $mc_three = ments_count_rank_num($this->goods_id, 3);    //三颗星
        $mc_four = ments_count_rank_num($this->goods_id, 4);        //四颗星
        $mc_five = ments_count_rank_num($this->goods_id, 5);        //五颗星
        $comment_all = get_conments_stars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);
        if ($goods['user_id'] > 0) {
            //商家所有商品评分类型汇总
            $merchants_goods_comment = get_merchants_goods_comment($goods['user_id']);
            $merchants_goods_comment['commentRank'] = comment_font($merchants_goods_comment['cmt']['commentRank']['zconments']['goodReview']);//时效评分背景
            $merchants_goods_comment['commentServer'] = comment_font($merchants_goods_comment['cmt']['commentServer']['zconments']['goodReview']);
            $merchants_goods_comment['commentDelivery'] = comment_font($merchants_goods_comment['cmt']['commentDelivery']['zconments']['goodReview']);
            $this->assign('merch_cmt', $merchants_goods_comment);
        }
        $this->assign('comment_all', $comment_all);
        //查询一条好评
        $good_comment = get_good_comment($this->goods_id, 4, 1, 0, 1);
        $this->assign('good_comment', $good_comment);

        //店铺关注人数 by wanglu
        $sql = "SELECT count(*) FROM " . $this->ecs->table('collect_store') . " WHERE ru_id = " . $goods['user_id'];
        $collect_number = $this->db->getOne($sql);
        $this->assign('collect_number', $collect_number ? $collect_number : 0);
        //评分 end
        $sql = "select a.kf_im_switch, b.is_IM,a.ru_id,a.province, a.city, a.kf_type, a.kf_ww, a.kf_qq, a.meiqia, a.shop_name, a.kf_appkey,kf_secretkey from {pre}seller_shopinfo as a left join {pre}merchants_shop_information as b on a.ru_id=b.user_id where a.ru_id='" . $goods['user_id'] . "' ";
        $basic_info = $this->db->getRow($sql);

        $info_ww = $basic_info['kf_ww'] ? explode("\r\n", $basic_info['kf_ww']) : '';
        $info_qq = $basic_info['kf_qq'] ? explode("\r\n", $basic_info['kf_qq']) : '';
        $kf_ww = $info_ww ? $info_ww[0] : '';
        $kf_qq = $info_qq ? $info_qq[0] : '';
        $basic_ww = $kf_ww ? explode('|', $kf_ww) : '';
        $basic_qq = $kf_qq ? explode('|', $kf_qq) : '';
        $basic_info['kf_ww'] = $basic_ww ? $basic_ww[1] : '';
        $basic_info['kf_qq'] = $basic_qq ? $basic_qq[1] : '';
        $customer_service = dao('shop_config')->where(['code' => 'customer_service'])->getField('value');
        $zkf = dao('seller_shopinfo')->field('kf_type, kf_qq, kf_ww')->where(['ru_id' => '0'])->find();
        $this->assign('customer_service', $customer_service);
        if ($customer_service == 0) {
            $basic_info['kf_ww'] = preg_replace('/^[^\-]*\|/is', '', $zkf['kf_ww']);
            $basic_info['kf_qq'] = preg_replace('/^[^\-]*\|/is', '', $zkf['kf_qq']);
        }
        if ($goods['user_id'] == 0) {
            //判断平台是否开启了IM在线客服
            if ($this->db->getOne("SELECT kf_im_switch FROM {pre}seller_shopinfo WHERE ru_id = 0")) {
                $basic_info['is_dsc'] = true;
            } else {
                $basic_info['is_dsc'] = false;
            }
        } else {
            $basic_info['is_dsc'] = false;
        }

        $basic_date = ['region_name'];
        $basic_info['province'] = !empty($basic_info['province']) ? get_table_date('region', "region_id = '" . $basic_info['province'] . "'", $basic_date, 2) : '';
        $basic_info['city'] = !empty($basic_info['city']) ? get_table_date('region', "region_id= '" . $basic_info['city'] . "'", $basic_date, 2) . "市" : '';
        // 美洽传递客户信息
        if (!empty($basic_info['meiqia'])) {
            $userinfo = get_user_default($_SESSION['user_id']);
            $this->assign('userinfo', $userinfo);
            $this->assign('meiqia_id', $basic_info['meiqia']);
        }

        $this->assign('basic_info', $basic_info);
        $shipping_list = warehouse_shipping_list($goods, $this->region_id, 1, $goods_region);
        $this->assign('shipping_list', $shipping_list);

        $_SESSION['goods_equal'] = '';
        /* 清空配件购物车 */
        if (!empty($_SESSION['user_id'])) {
            $sess_id = " user_id = '" . $_SESSION['user_id'] . "' ";
        } else {
            $sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
        }
        $this->db->query('DELETE FROM ' . $this->ecs->table('cart_combo') . " WHERE (parent_id = 0 AND goods_id = '$this->goods_id' OR parent_id = '$this->goods_id') AND " . $sess_id);

        //新品
        $new_goods = get_recommend_goods('new', '', $this->region_id, $this->area_id, $this->area_city, $goods['user_id']);
        $this->assign('new_goods', $new_goods);
        // 关联商品
        $link_goods = get_linked_goods($this->goods_id, $this->region_id, $this->area_id, $this->area_city);
        $this->assign('link_goods', $link_goods);
        $history_goods = get_history_goods($this->goods_id, $this->region_id, $this->area_id, $this->area_city);
        $this->assign('history_goods', $history_goods);

        //店铺优惠券 by wanglu
        $time = gmtime();
        $sql = "SELECT * FROM {pre}coupons WHERE (cou_type = 3 OR cou_type = 4 ) AND cou_end_time >$time AND (( instr(cou_goods, $this->goods_id) ) or (cou_goods = 0)) AND review_status = 3 AND ru_id = " . $goods['user_id'] . " ORDER BY cou_id DESC LIMIT 0, 10 ";
        $coupont = $this->db->getAll($sql);
        foreach ($coupont as $key => $value) {
            $coupont[$key]['cou_end_time'] = local_date('Y.m.d', $value['cou_end_time']);
            $coupont[$key]['cou_start_time'] = local_date('Y.m.d', $value['cou_start_time']);
            // 是否领取
            if ($_SESSION['user_id'] > 0) {
                $user_num = dao('coupons_user')->where(['cou_id' => $value['cou_id'], 'user_id' => $_SESSION['user_id']])->count();
                if ($user_num > 0 && $value['cou_user_num'] <= $user_num) {
                    $coupont[$key]['cou_is_receive'] = 1;
                } else {
                    $coupont[$key]['cou_is_receive'] = 0;
                }
            }
            // 能否领取 优惠劵总张数 1 不能 0 可以领取
            $cou_num = dao('coupons_user')->where(['cou_id' => $value['cou_id']])->count();
            $coupont[$key]['enable_ling'] = (!empty($cou_num) && $cou_num >= $value['cou_total']) ? 1 : 0;
        }
        $this->assign('coupons_list', $coupont);

        /** 判断客服目录 */
        $kf_im_switch = dao('seller_shopinfo')->where(['ru_id' => '0'])->getField('kf_im_switch');
        $im_dialog = M()->query('SHOW TABLES LIKE "{pre}im_dialog"');

        if ($kf_im_switch == 1 && $im_dialog) {
            $this->assign('kefu', $kf_im_switch);
        }

        if (!empty($goods['desc_mobile'])) {
            // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $goods['desc_mobile'], 'desc_mobile');
                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $desc_preg['desc_mobile']);
            } else {
                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $goods['desc_mobile']);
            }
        }
        if (empty($goods['desc_mobile']) && !empty($goods['goods_desc'])) {
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $bucket_info['endpoint'] . 'images/upload', $goods['goods_desc']);

                // $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $goods['goods_desc']);
                // $goods_desc = $desc_preg['goods_desc'];
            } else {
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . __STATIC__ . '/images/upload', $goods['goods_desc']);
            }
        }
        if (empty($goods['desc_mobile']) && empty($goods['goods_desc'])) {
            // 查询关联商品描述
            $sql = "SELECT ld.goods_desc FROM {pre}link_desc_goodsid AS dg, {pre}link_goods_desc AS ld WHERE dg.goods_id = {$this->goods_id}  AND dg.d_id = ld.id AND ld.review_status > 2";
            $goods_desc = $this->db->getOne($sql);
        }
        if (!empty($goods_desc)) {
            /* 如果样式错位，直接去添加手机端内容。
            $goods_desc = preg_replace("/height\=\"[0-9]+?\"/", "", $goods_desc);
            $goods_desc = preg_replace("/width\=\"[0-9]+?\"/", "", $goods_desc);
            $goods_desc = preg_replace("/style=.+?[*|\"]/i", "", $goods_desc);
            */
            // Img lazyload
            $goods_desc = preg_replace("/<img(.*?)src=/i", '<img${1}class="lazy" src=', $goods_desc);
        }

        $this->assign('goods_desc', $goods_desc);
        $this->display();
    }

    public function actionNewGoods()
    {
        $goods = get_goods_info($this->goods_id, $this->region_id, $this->area_id, $this->area_city);
        $goods['pic'] = dao('goods_gallery')->field('img_url')->where(['goods_id' => $this->goods_id])->select();
        foreach ($goods['pic'] as $key => $value) {
            $goods['pic'][$key]['img_url'] = get_image_path($value['img_url']);
        }
        $properties = get_goods_properties($this->goods_id, $this->region_id, $this->area_info['region_id']);
        foreach ($properties['pro'][''] as $k => $v) {
            $spec[$k]['name'] = $v['name'];
            $spec[$k]['value'] = $v['value'];
        }
        $spec = array_values($spec);
        foreach ($properties['spe'] as $key => $val) {
            $attr = $val['attr_type'];
        }
        // 微信JSSDK分享
        $share_data = [
            'title' => $goods['goods_name'],
            'desc' => $goods['goods_brief'],
            'link' => '',
            'img' => $goods['goods_img'],
        ];
        $cart_num = cart_number();
        $this->response(['error' => 0, 'goods' => $goods, 'cart_num' => $cart_num, 'spec' => $spec, 'properties' => $properties['spe'], 'share_data' => $this->get_wechat_share_content($share_data), 'type' => $attr]);
    }

    /**
     * 商品详情
     */
    public function actionInfo()
    {
        $info = $this->db->table('goods')->field('goods_desc,desc_mobile')->where(['goods_id' => $this->goods_id])->find();
        $properties = get_goods_properties($this->goods_id, $this->region_id, $this->area_id);  // 获得商品的规格和属性

        if (!empty($info['desc_mobile'])) {
            // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $info['desc_mobile'], 'desc_mobile');
                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $desc_preg['desc_mobile']);
            } else {
                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $info['desc_mobile']);
            }
        }

        if (empty($info['desc_mobile']) && !empty($info['goods_desc'])) {
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $bucket_info['endpoint'] . 'images/upload', $info['goods_desc']);

                // $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $info['goods_desc']);
                // $goods_desc = $desc_preg['goods_desc'];
            } else {
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . __STATIC__ . '/images/upload', $info['goods_desc']);
            }
        }
        if (empty($info['desc_mobile']) && empty($info['goods_desc'])) {
            // 查询关联商品描述
            $sql = "SELECT ld.goods_desc FROM {pre}link_desc_goodsid AS dg, {pre}link_goods_desc AS ld WHERE dg.goods_id = {$this->goods_id}  AND dg.d_id = ld.id AND ld.review_status > 2";
            $link_desc = $this->db->getOne($sql);
            $goods_desc = $link_desc;
        }
        if (!empty($goods_desc)) {
            $goods_desc = preg_replace("/height\=\"[0-9]+?\"/", "", $goods_desc);
            $goods_desc = preg_replace("/width\=\"[0-9]+?\"/", "", $goods_desc);
            $goods_desc = preg_replace("/style=.+?[*|\"]/i", "", $goods_desc);
            // Img lazyload
            $goods_desc = preg_replace("/<img(.*?)src=/i", '<img${1}class="lazy" src=', $goods_desc);
        }

        $this->assign('goods_desc', $goods_desc);
        // 商品属性
        $this->assign('properties', $properties['pro']);
        $this->assign('page_title', L('goods_detail'));
        $this->display();
    }


    /**
     * 商品评论
     */
    public function actionComment($rank = '')
    {
        if (IS_AJAX) {
            $rank = I('rank', 'all', ['htmlspecialchars', 'trim']);
            $page = I('page', 0, 'intval');
            $start = $page > 0 ? ($page - 1) * $this->size : 1;

            $arr = get_good_comment_as($this->goods_id, $rank, 1, $start, $this->size);
            $comments = $arr['arr'];
            $totalPage = $arr['max'];
            if ($rank == 'img') {
                foreach ($comments as $key => $val) {
                    if ($val['comment_img'] == '') {
                        unset($comments[$key]);
                    }
                }
                $totalPage = $arr['img_max'];
            }
            // dd($comments);
            $reset = $start > 0 ? 0 : 1;
            die(json_encode(['comments' => $comments, 'rank' => $rank, 'reset' => $reset, 'totalPage' => $totalPage, 'top' => 1]));
        }
        // 兼容原有图评论
        if ($rank == 'img') {
            $rank = $rank;
        } else {
            $rank = I('rank', 'all', ['htmlspecialchars', 'trim']);
        }
        // 查询商品分享类型
        $is_distribution = 0;
        if (is_dir(APP_DRP_PATH)) {
            $is_distribution = dao('goods')->where(['goods_id' => $this->goods_id])->getField('is_distribution');
            $isdrp = $this->model->table('drp_config')->field('value')->where(['code' => 'isdrp'])->find();
            $sql = "SELECT id FROM {pre}drp_shop WHERE audit=1 AND status=1 AND user_id=" . $this->user_id;
            $drp = $this->db->getOne($sql);
        }
        $share_type = 0;
        if (isset($isdrp) && $isdrp['value'] == 1 && isset($drp) && $drp > 0 && $is_distribution == 1) {
            $share_type = 1;
        }
        $this->assign('share_type', $share_type); // 分享类型
        $this->assign('rank', $rank); // 评论
        $this->assign('comment_count', commentCol($this->goods_id)); // 评论数量
        $this->assign('goods_id', $this->goods_id);
        $this->assign('page_title', L('goods_comment'));
        $this->display('comment');
    }

    /**
     * 有图评论
     * @return
     */
    public function actionInfoimg()
    {
        $rank = 'img';
        $this->actionComment($rank);
    }

    /**
     * 商品分享（分成/代言）
     */
    public function actionSharing()
    {
        if (IS_AJAX) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            if (empty($_SESSION['user_id'])) {
                exit(json_encode(['error' => 2, 'back_act' => urlencode($back_act)]));
            }

            $share_type = input('type', 0, 'intval'); // 分享类型 0 分享、1 分销
            $user_id = $_SESSION['user_id'];
            $goods_price = input('price', '', ['trim', 'html_in']);

            if ($share_type == 1) {
                $goods_url = url('goods/index/index', ['id' => $this->goods_id, 'd' => $user_id], false, true);
            } else {
                $goods_url = url('goods/index/index', ['id' => $this->goods_id, 'u' => $user_id], false, true);
            }

            $goods = get_goods_info($this->goods_id, $this->region_id, $this->area_id, $this->area_city);
            if (empty($goods)) {
                exit(json_encode(['error' => 1]));
            }

            $users = get_user_default($user_id);
            if (empty($users)) {
                exit(json_encode(['error' => 1]));
            }

            $file = dirname(ROOT_PATH) . '/data/attached/goods_share/';
            if (!file_exists($file)) {
                make_dir($file, 0777);
            }
            $avatar_file = dirname(ROOT_PATH) . '/data/attached/avatar/';
            if (!file_exists($avatar_file)) {
                make_dir($avatar_file, 0777);
            }
            $goods_thumb_file = $file . 'goods_thumb/';
            if (!file_exists($goods_thumb_file)) {
                make_dir($goods_thumb_file, 0777);
            }

            // 背景图片
            $bgImg = $file . 'goods_bg.png';
            // 商品图 默认相册图第一张
            $pictures_one = get_one_goods_gallery($this->goods_id);
            $goods_img = isset($pictures_one['img_url']) ? $pictures_one['img_url'] : $goods['goods_img'];
            $goods_thumb = $goods_thumb_file . $this->goods_id . '_' . basename($goods_img);
            // 头像
            $user_picture = basename($users['user_picture']);
            $avatar_name = (isset($user_picture) && !empty($user_picture)) ? $user_picture : md5($users['user_picture']) . '.png';
            $avatar = $avatar_file . 'avatar_' . $user_id . '_' . $avatar_name;
            // 二维码
            $qrcode = $file . 'goods_qrcode_' . $share_type . '_' . $this->goods_id . '_' . $user_id . '.png';
            // 输出图片
            $outImg = $file . 'goods_share_' . $share_type . '_' . $this->goods_id . '_' . $user_id . '.png';

            if ($goods_url && $goods_img) {
                // 生成二维码
                $qrCode = new QrCode($goods_url);
                $img = new Image();

                // 生成二维码+微信头像
                if (!file_exists($avatar)) {
                    // 生成微信头像缩略图
                    $headimg = Http::doGet($users['user_picture']);
                    $avatar_open = $avatar;
                    file_put_contents($avatar_open, $headimg);
                    if ($headimg === false) {
                        if (strtolower(substr($users['user_picture'], 0, 4)) == 'http' && strpos($users['user_picture'], __STATIC__) !== false) {
                            $avatar_open = str_replace(__STATIC__, dirname(ROOT_PATH), $users['user_picture']);
                        } else {
                            $avatar_open = dirname(ROOT_PATH) . $users['user_picture'];
                        }
                    }
                    $img->open($avatar_open)->thumb(60, 60, Image::IMAGE_THUMB_FILLED)->save($avatar);
                }
                $qrCode->setSize(257)->setMargin(15);
                $qrCode->setLogoPath($avatar)->setLogoWidth(60); // 头像默认居中
                $qrCode->writeFile($qrcode); // 保存二维码

                // 默认
                $bg_width = $img->open($bgImg)->width(); // 背景图宽
                $bg_height = $img->open($bgImg)->height(); // 背景图高

                // 生成商品图+文字
                $goods_title = strip_tags(html_out($goods['goods_name']));
                $goods_title_first = sub_str($goods_title, 20, true, 0);
                $len = goods_name_strlen($goods_title_first);
                if (!empty($len) && $len > 0) {
                    $sub_len = 20 + $len / 2;
                    $goods_title_first = sub_str($goods_title, $sub_len, true, 0);
                }
                // 处理价格
                if (!empty($goods_price)) {
                    $currency_format = sub_str(strip_tags(C('shop.currency_format')), 1, false);
                    $goods['shop_price'] = str_replace($currency_format, '', $goods_price);
                }
                $goods_price = price_format($goods['shop_price']);
                if (!file_exists($goods_thumb)) {
                    // 生成商品图缩略图
                    // 1 开启oss, 2. 采集图片的远程地址（非本网站）
                    if (C('shop.open_oss') == 1 || (strtolower(substr($goods_img, 0, 4)) == 'http' && strpos($goods_img, __STATIC__) === false)) {
                        $goodsimg = Http::doGet($goods_img);
                        $goodsthumb = $goods_thumb;
                        file_put_contents($goodsthumb, $goodsimg);
                    } else {
                        $goodsthumb = str_replace(__STATIC__, dirname(ROOT_PATH), $goods_img);
                    }
                    $img->open($goodsthumb)->thumb($bg_width, $bg_width, Image::IMAGE_THUMB_FILLED)->save($goods_thumb);
                }

                $fonts_path = dirname(ROOT_PATH) . '/data/attached/fonts/msyh.ttf';
                $font_color = "#555555";
                $img->open($bgImg)->water($goods_thumb, [0, 0], 100)->text($goods_title_first, $fonts_path, 20, $font_color, [40, $bg_width + 20])->save($outImg);
                // 生成价格
                $img->open($outImg)->text($goods_price, $fonts_path, 28, '#EC5151', [40, $bg_width + 70])->save($outImg);

                // 默认昵称
                $nickname = sub_str($users['nick_name'], 10, false);
                // 分销文案
                if ($share_type == 1) {
                    $text_description = sprintf(L('share_drp_desc'), $nickname, C('shop.shop_name'));
                } else {
                    // 推荐分成文案
                    $text_description = sprintf(L('share_desc'), $nickname);
                }
                // 二维码坐标
                $qr_left = 50; // 左50
                $qr_top = $bg_width + 130;
                $logo_width = $img->open($qrcode)->width(); // logo图宽
                $text_left = $bg_width / 8; // 背景图宽度的1/8位置 使文字居中显示
                $text_top = $qr_top + $logo_width + 45;

                // 最后生成 商品图+二维码
                $img->open($outImg)->water($qrcode, [$qr_left, $qr_top], 100)->text($text_description, $fonts_path, 18, '#999999', [$text_left, $text_top])->save($outImg);

                // 同步OSS数据
                if (C('shop.open_oss') == 1) {
                    $image_name = $this->ossMirror($outImg, 'data/attached/goods_share/');
                }
            }

            $image_name = 'data/attached/goods_share/' . basename($outImg);
            $outImg = get_image_path($image_name);

            exit(json_encode(['error' => 0, 'share_img' => $outImg . '?v=' . time()]));
        }
    }

    /**
     * 改变属性、数量时重新计算商品价格
     */
    public function actionPrice()
    {
        $res = ['err_msg' => '', 'result' => '', 'qty' => 1];
        $attr = I('attr', '', ['htmlspecialchars', 'trim']);
        $number = I('number', 1, 'intval');
        $attr_id = !empty($attr) ? explode(',', $attr) : [];
        $warehouse_id = I('request.warehouse_id', 0, 'intval');
        $area_id = I('request.area_id', 0, 'intval'); //仓库管理的地区ID
        $area_city = I('area_city', 0, 'intval'); // 城市ID
        $onload = I('request.onload', '', ['htmlspecialchars', 'trim']); // 首次加载

        $goods_attr = isset($_REQUEST['goods_attr']) ? explode(',', $_REQUEST['goods_attr']) : [];
        $attr_ajax = get_goods_attr_ajax($this->goods_id, $goods_attr, $attr_id);

        $goods = get_goods_info($this->goods_id, $warehouse_id, $area_id, $area_city);

        if ($this->goods_id == 0) {
            $res['err_msg'] = L('err_change_attr');
            $res['err_no'] = 1;
        } else {
            if ($number == 0) {
                $res['qty'] = $number = 1;
            } else {
                $res['qty'] = $number;
            }
            //ecmoban模板堂 --zhuo start
            $products = get_warehouse_id_attr_number($this->goods_id, $attr, $goods['user_id'], $warehouse_id, $area_id, $area_city);
            $attr_number = isset($products['product_number']) ? $products['product_number'] : 0;
            $product_promote_price = isset($products['product_promote_price']) ? $products['product_promote_price'] : 0;

            if ($goods['model_attr'] == 1) {
                $table_products = "products_warehouse";
                $type_files = " and warehouse_id = '$warehouse_id'";
            } elseif ($goods['model_attr'] == 2) {
                $table_products = "products_area";
                $type_files = " and area_id = '$area_id'";
                if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                    $type_files .= " AND city_id = '$area_city'";
                }
            } else {
                $table_products = "products";
                $type_files = "";
            }

            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '$this->goods_id'" . $type_files . " LIMIT 0, 1";
            $prod = $GLOBALS['db']->getRow($sql);

            //贡云商品 获取库存
            if ($goods['cloud_id'] > 0) {
                $sql = "SELECT product_number,cloud_product_id FROM" . $GLOBALS['ecs']->table('products') . "WHERE product_id = '" . $products['product_id'] . "'";
                $product = $GLOBALS['db']->getRow($sql);
                $attr_number = get_jigon_products_stock($product);
            } else {
                if ($goods['goods_type'] == 0) {
                    $attr_number = $goods['goods_number'];
                } else {
                    if (empty($prod)) { //当商品没有属性库存时
                        $attr_number = $goods['goods_number'];
                    }
                    if (!empty($prod) && $GLOBALS['_CFG']['add_shop_price'] == 0 && $onload == 'onload') {
                        if (empty($attr_number)) {
                            $attr_number = $goods['goods_number'];
                        }
                    }
                }
            }

            $attr_number = !empty($attr_number) ? $attr_number : 0;
            $res['attr_number'] = $attr_number;

            //限制用户购买的数量 bywanglu
            $res['limit_number'] = $attr_number < $number ? ($attr_number ? $attr_number : 1) : $number;
            $shop_price = get_final_price($this->goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city);
            //ecmoban模板堂 --zhuo end

            $res['shop_price'] = price_format($shop_price);
            $res['market_price'] = $goods['market_price'];

            $res['show_goods'] = 0;
            if ($goods_attr && $GLOBALS['_CFG']['add_shop_price'] == 0) {
                if (count($goods_attr) == count($attr_ajax['attr_id'])) {
                    $res['show_goods'] = 1;
                }
            }

            //属性价格
            $spec_price = get_final_price($this->goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city, 1, 0, 0, $res['show_goods'], $product_promote_price);
            if ($GLOBALS['_CFG']['add_shop_price'] == 0 && empty($spec_price) && empty($prod)) {
                $spec_price = $shop_price;
            }

            if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                $shop_price = $spec_price;
                $res['result'] = price_format($spec_price);
            } else {
                $res['result'] = price_format($shop_price);
            }

            $res['spec_price'] = price_format($spec_price);

            $martetprice_amount = $spec_price + $goods['marketPrice'];
            $res['marketPrice_amount'] = price_format($spec_price + $goods['marketPrice']);

            //切换属性后的价格折扣 by wanglu
            $res['discount'] = round(($shop_price / $martetprice_amount) * 10, 1);

            $res['result'] = price_format($shop_price); //商品价格不跟随增加数量而增加，之前代码  $res['result'] = price_format($shop_price * $number);
            if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                $goods['marketPrice'] = isset($products['product_market_price']) && !empty($products['product_market_price']) ? $products['product_market_price'] : $goods['marketPrice'];
                $res['result_market'] = price_format($goods['marketPrice']); //商品价格不跟随增加数量而增加，之前代码    $res['result_market'] = price_format($goods['marketPrice'] * $number);
            } else {
                $res['result_market'] = price_format($goods['marketPrice'] + $spec_price); //商品价格不跟随增加数量而增加，之前代码    $res['result_market'] = price_format($martetprice_amount * $number);
            }
        }

        if ($GLOBALS['_CFG']['open_area_goods'] == 1) {
            $area_list = get_goods_link_area_list($this->goods_id, $goods['user_id']);
            if ($area_list['goods_area']) {
                if (!in_array($area_id, $area_list['goods_area'])) {
                    $res['err_no'] = 2;
                }
            } else {
                $res['err_no'] = 2;
            }
        }
        // 属性图片
        $attr_info = get_attr_value($this->goods_id, $attr_id[0]);
        if (!empty($attr_info['attr_img_flie'])) {
            $res['attr_img'] = get_image_path($attr_info['attr_img_flie']);
        }
        // 是否显示门店 查询门店商品库存
        $area_position_list = get_goods_user_area_position($goods['user_id'], $this->city_id, $attr, $this->goods_id, 0, 0, 1, 0, '');
        if (count($area_position_list) > 0) {
            $res['store_type'] = 1;
        } else {
            $res['store_type'] = 0;
        }
        $res['onload'] = $onload;
        die(json_encode($res));
    }

    /**
     * 切换仓库
     */
    public function actionInWarehouse()
    {
        if (IS_AJAX) {
            $res = ['err_msg' => '', 'result' => '', 'qty' => 1, 'goods_id' => 0];
            $pid = I('get.pid', 0, 'intval');
            $goods_id = I('get.id', 0, 'intval');
            if (empty($pid) || empty($goods_id)) {
                die(json_encode($res));
            }
            cookie('region_id', $pid);
            cookie('regionid', $pid);

            cookie('area_region', 0);

            $res['goods_id'] = $goods_id;
            die(json_encode($res));
        }
    }

    /**
     * 商品详情所在地区切换
     */
    public function actionInstock()
    {
        if (IS_AJAX) {
            $res = ['err_msg' => '', 'result' => '', 'qty' => 1];
            clear_cache_files();
            $goods_id = $this->goods_id;
            $province = I('get.province', 1, 'intval');
            $city = I('get.city', 0, 'intval');
            $district = I('get.district', 0, 'intval');
            $street = I('get.street', 0, 'intval');
            $d_null = I('get.d_null', 0, 'intval');
            $user_id = I('get.user_id', 0, 'intval');
            $user_address = get_user_address_region($user_id);
            $user_address = explode(",", $user_address['region_address']);

            cookie('province', $province, gmtime() + 3600 * 24 * 30);
            cookie('city', $city, gmtime() + 3600 * 24 * 30);
            cookie('district', $district, gmtime() + 3600 * 24 * 30);
            cookie('street', $street, gmtime() + 3600 * 24 * 30);

            //清空
            cookie('regionId', 0, gmtime() + 3600 * 24 * 30);
            cookie('type_province', 0, gmtime() + 3600 * 24 * 30);
            cookie('type_city', 0, gmtime() + 3600 * 24 * 30);
            cookie('type_district', 0, gmtime() + 3600 * 24 * 30);
            cookie('type_street', 0, gmtime() + 3600 * 24 * 30);

            $res['d_null'] = $d_null;
            if ($d_null == 0) {
                if (in_array($district, $user_address)) {
                    $res['isRegion'] = 1;
                } else {
                    $res['message'] = L('write_address');
                    $res['isRegion'] = 88; //原为0
                }
            } else {
                cookie('district', '', gmtime() + 3600 * 24 * 30);
            }
            $res['goods_id'] = $goods_id;
            die(json_encode($res));
        }
    }


    /**
     * 商品收藏
     */
    public function actionAddCollection()
    {
        $result = [
            'error' => 0,
            'message' => ''
        ];

        if (!isset($this->user_id) || $this->user_id == 0) {
            $result['error'] = 2;
            $result['message'] = L('login_please');
            die(json_encode($result));
        } else {
            // 检查是否已经存在于用户的收藏夹
            $where['user_id'] = $this->user_id;
            $where['goods_id'] = $this->goods_id;
            $rs = $this->db->table('collect_goods')->where($where)->count();
            if ($rs > 0) {
                $rs = $this->db->table('collect_goods')->where($where)->delete();
                if (!$rs) {
                    $result['error'] = 1;
                    $result['message'] = M()->errorMsg();
                    die(json_encode($result));
                } else {
                    $result['error'] = 0;
                    $result['message'] = L('collect_success');
                    die(json_encode($result));
                }
            } else {
                $data['user_id'] = $this->user_id;
                $data['goods_id'] = $this->goods_id;
                $data['add_time'] = gmtime();
                if ($this->db->table('collect_goods')->data($data)->add() === false) {
                    $result['error'] = 1;
                    $result['message'] = M()->errorMsg();
                    die(json_encode($result));
                } else {
                    $result['error'] = 0;
                    $result['message'] = L('collect_success');
                    die(json_encode($result));
                }
            }
        }
    }

    /**
     * 初始化参数
     */
    private function init_params()
    {
        #需要查询的IP start
        if (!isset($_COOKIE['province'])) {
            $area_array = get_ip_area_name();
            if ($area_array['county_level'] == 2) {
                $date = ['region_id', 'parent_id', 'region_name'];
                $where = "region_name = '" . $area_array['area_name'] . "' AND region_type = 2";
                $city_info = get_table_date('region', $where, $date, 1);

                $date = ['region_id', 'region_name'];
                $where = "region_id = '" . $city_info[0]['parent_id'] . "'";
                $province_info = get_table_date('region', $where, $date);

                $where = "parent_id = '" . $city_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
                $district_info = get_table_date('region', $where, $date, 1);

                $where = "parent_id = '" . $district_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
                $town_info = get_table_date('region', $where, $date, 1);
            } elseif ($area_array['county_level'] == 1) {
                $area_name = $area_array['area_name'];

                $date = ['region_id', 'region_name'];
                $where = "region_name = '$area_name'";
                $province_info = get_table_date('region', $where, $date);

                $where = "parent_id = '" . $province_info['region_id'] . "' order by region_id asc limit 0, 1";
                $city_info = get_table_date('region', $where, $date, 1);

                $where = "parent_id = '" . $city_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
                $district_info = get_table_date('region', $where, $date, 1);

                $where = "parent_id = '" . $district_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
                $town_info = get_table_date('region', $where, $date, 1);
            }
        }
        #需要查询的IP end
        $order_area = get_user_order_area($this->user_id);
        $user_area = get_user_area_reg($this->user_id); //2014-02-25

        if ($order_area['province'] && $this->user_id > 0) {
            $this->province_id = $order_area['province'];
            $this->city_id = $order_area['city'];
            $this->district_id = $order_area['district'];
            $this->town_region_id = $order_area['street'];
        } else {
            //省
            if ($user_area['province'] > 0) {
                $this->province_id = $user_area['province'];
                cookie('province', $user_area['province']);
                $this->region_id = get_province_id_warehouse($this->province_id);
            } else {
                $sql = "select region_name from " . $this->ecs->table('region_warehouse') . " where regionId = '" . $province_info['region_id'] . "'";
                $warehouse_name = $this->db->getOne($sql);

                $this->province_id = $province_info['region_id'];
                $cangku_name = $warehouse_name;
                $this->region_id = get_warehouse_name_id(0, $cangku_name);
            }
            //市
            if ($user_area['city'] > 0) {
                $this->city_id = $user_area['city'];
                cookie('city', $user_area['city']);
            } else {
                $this->city_id = $city_info[0]['region_id'];
            }
            //区
            if ($user_area['district'] > 0) {
                $this->district_id = $user_area['district'];
                cookie('district', $user_area['district']);
            } else {
                $this->district_id = $district_info[0]['region_id'];
            }
            //街道
            if ($user_area['street'] > 0) {
                $this->town_region_id = $user_area['street'];
                cookie('town_region_id', $user_area['street']);
            } else {
                $this->town_region_id = $town_info[0]['region_id'];
            }
        }

        $this->province_id = isset($_COOKIE['province']) ? $_COOKIE['province'] : $this->province_id;

        $child_num = get_region_child_num($this->province_id);
        if ($child_num > 0) {
            $this->city_id = isset($_COOKIE['city']) ? $_COOKIE['city'] : $this->city_id;
        } else {
            $this->city_id = '';
        }

        $child_num = get_region_child_num($this->city_id);
        if ($child_num > 0) {
            $this->district_id = isset($_COOKIE['district']) ? $_COOKIE['district'] : $this->district_id;
        } else {
            $this->district_id = '';
        }

        $child_num = get_region_child_num($this->district_id);
        if ($child_num > 0) {
            $this->town_region_id = isset($_COOKIE['street']) ? $_COOKIE['street'] : $this->town_region_id;
        } else {
            $this->town_region_id = '';
        }
        $this->region_id = !isset($_COOKIE['region_id']) ? $this->region_id : $_COOKIE['region_id'];
        $goods_warehouse = get_warehouse_goods_region($this->province_id); //查询用户选择的配送地址所属仓库
        if ($goods_warehouse) {
            $this->regionId = $goods_warehouse['region_id'];
            if ($_COOKIE['region_id'] && $_COOKIE['regionid']) {
                $gw = 0;
            } else {
                $gw = 1;
            }
        }
        if ($gw) {
            $this->region_id = $this->regionId;
            cookie('area_region', $this->region_id);
        }

        cookie('goodsId', $this->goods_id);

        $sellerInfo = get_seller_info_area();
        if (empty($this->province_id)) {
            $this->province_id = $sellerInfo['province'];
            $this->city_id = $sellerInfo['city'];
            $this->district_id = 0;
            cookie('province', $this->province_id);
            cookie('city', $this->city_id);
            cookie('district', $this->district_id);
            cookie('stree', $this->town_region_id);
            $this->region_id = get_warehouse_goods_region($this->province_id);
        }

        $other = [
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
        ];
        $warehouse_area_info = get_warehouse_area_info($other);
        $this->area_city = $warehouse_area_info['city_id'];
        cookie('area_city ', $this->area_city);
        //ecmoban模板堂 --zhuo end 仓库
        $this->area_info = get_area_info($this->province_id);
    }

    /**
     * 判断是否是分销商
     */
    public function actionCheckDrp()
    {
        if (IS_AJAX) {
            $shop_num = $this->model->table('drp_shop')->where(['user_id' => $this->user_id])->count();
            if ($shop_num == 1) {
                exit(json_encode(['code' => 1]));
            } else {
                exit(json_encode(['code' => 0]));
            }
        }
    }

    /**
     * 组合套餐 配件
     * @return
     */
    public function actionFittings()
    {
        /* 清空配件购物车 */
        if (!empty($_SESSION['user_id'])) {
            $sess_id = " user_id = '" . $_SESSION['user_id'] . "' ";
        } else {
            $sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
        }
        $this->db->query('DELETE FROM ' . $this->ecs->table('cart_combo') . " WHERE (parent_id = 0 AND goods_id = '$this->goods_id' OR parent_id = '$this->goods_id') AND " . $sess_id);

        $this->goods_id = input('id', 0, 'intval');
        // 主商品信息
        $goods = get_goods_info($this->goods_id, $this->region_id, $this->area_id, $this->area_city);
        if ($goods === false || !isset($goods['goods_name'])) {
            /* 如果没有找到任何记录则跳回到首页 */
            ecs_header("Location: ./\n");
            exit;
        }

        $properties = get_goods_properties($this->goods_id, $this->region_id, $this->area_id);  // 获得商品的规格和属性
        $this->assign('properties', $properties['pro']);                              // 商品属性
        //默认选中的商品规格 by wanglu
        $default_spe = '';
        if ($properties['spe']) {
            foreach ($properties['spe'] as $k => $v) {
                if ($v['attr_type'] == 1) {
                    if ($v['is_checked'] > 0) {
                        foreach ($v['values'] as $key => $val) {
                            $default_spe .= $val['checked'] == 1 ? $val['label'] . '、' : '';
                        }
                    } else {
                        foreach ($v['values'] as $key => $val) {
                            if ($key == 0) {
                                $default_spe .= $val['label'] . '、';
                            }
                        }
                    }
                }
            }
        }
        $this->assign('default_spe', $default_spe);          // 默认商品规格
        $this->assign('specification', $properties['spe']);  // 商品规格

        $this->assign('goods', $goods);

        $group_count = get_group_goods_count($this->goods_id);
        $fittings_list = [];
        if ($group_count > 0) {
            $comboTabIndex = get_cfg_group_goods(); // 配件类型
            $this->assign('comboTab', $comboTabIndex);

            // 缓存

            $fittings_list = get_goods_fittings([$this->goods_id], $this->region_id, $this->area_id);
            if (is_array($fittings_list)) {
                foreach ($fittings_list as $vo) {
                    $fittings_index[$vo['group_id']] = $vo['group_id'];//关联数组
                }
            }
            ksort($fittings_index); //重新排序
            $this->assign('fittings_tab_index', $fittings_index); //套餐数量

            // 节省金额最高 排序最前
            $fittings_list = get_array_sort($fittings_list, 'spare_price_ori', 'DESC');
            $fittings_list = array_values($fittings_list); // 重新对数组键排序 值不变
        }

        $this->assign('fittings', $fittings_list); // 配件列表
        $this->assign('page_title', L('fittings_list'));
        $this->display();
    }

    /**
     * 查看是否秒杀
     */
    public function get_is_seckill($goods_id = 0)
    {
        $date_begin = local_strtotime(local_date('Ymd'));
        $stb_time = local_date('H:i:s');

        $sql = "SELECT sg.id AS sec_goods_id FROM " . $GLOBALS['ecs']->table('seckill_goods') . ' AS sg ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('seckill_time_bucket') . ' AS stb ON sg.tb_id = stb.id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('seckill') . ' AS s ON s.sec_id = sg.sec_id ' .
            " WHERE goods_id = '$goods_id' AND s.begin_time <= '$date_begin' AND s.acti_time > '$date_begin' AND s.is_putaway = 1 " .
            " AND s.review_status = 3 AND stb.begin_time <= '$stb_time' AND stb.end_time > '$stb_time' ORDER BY stb.begin_time ASC";
        $sec_goods_id = $GLOBALS['db']->getOne($sql, true);

        return $sec_goods_id;
    }

    /**
     * 组合套餐 改变属性、数量时重新计算商品价格
     */
    public function actionFittingPrice()
    {
        if (IS_AJAX) {
            $res = ['err_msg' => '', 'result' => '', 'number' => 1];

            $goods_id = input('id', 0, 'intval');
            $type = input('type', 0, 'intval'); // type 1 主件 0 配件
            $onload = input('onload', '', ['htmlspecialchars', 'trim']); // 首次加载

            $group = input('group', '', 'stripcslashes');
            if (empty($group)) {
                $res['err_msg'] = 1;
                exit(json_encode($res));
            }

            $group = json_decode($group);

            $attr = trim($group->attr);
            $attr_id = !empty($attr) ? explode(',', $attr) : [];
            $goods_attr = trim($group->goods_attr);
            $goods_attr = isset($goods_attr) ? explode(',', $goods_attr) : [];

            $warehouse_id = intval($group->warehouse_id);
            $area_id = intval($group->area_id);
            $area_city = intval($group->area_city);

            $number = intval($group->number);
            $group_name = trim($group->group_name);
            $group_id = trim($group->group_id);
            $parent_id = intval($group->fittings_goods); // 主商品id

            // $attr_ajax = get_goods_attr_ajax($goods_id, $goods_attr, $attr_id);

            $goods = get_goods_info($goods_id, $warehouse_id, $area_id, $area_city);

            if ($goods_id == 0) {
                $res['err_msg'] = L('err_change_attr');
                $res['err_no'] = 1;
            } else {
                if ($number == 0) {
                    $res['number'] = $number = 1;
                } else {
                    $res['number'] = $number;
                }
                //ecmoban模板堂 --zhuo start
                $products = get_warehouse_id_attr_number($goods_id, $attr, $goods['user_id'], $warehouse_id, $area_id, $area_city);
                $attr_number = isset($products['product_number']) ? $products['product_number'] : 0;
                $product_promote_price = isset($products['product_promote_price']) ? $products['product_promote_price'] : 0;

                if ($goods['model_attr'] == 1) {
                    $table_products = "products_warehouse";
                    $type_files = " and warehouse_id = '$warehouse_id'";
                } elseif ($goods['model_attr'] == 2) {
                    $table_products = "products_area";
                    $type_files = " and area_id = '$area_id'";
                    if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                        $type_files .= " AND city_id = '$area_city'";
                    }
                } else {
                    $table_products = "products";
                    $type_files = "";
                }

                $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '$goods_id'" . $type_files . " LIMIT 0, 1";
                $prod = $GLOBALS['db']->getRow($sql);

                //贡云商品 获取库存
                if ($goods['cloud_id'] > 0) {
                    $sql = "SELECT product_number,cloud_product_id FROM" . $GLOBALS['ecs']->table('products') . "WHERE product_id = '" . $products['product_id'] . "'";
                    $product = $GLOBALS['db']->getRow($sql);
                    $attr_number = get_jigon_products_stock($product);
                } else {
                    if ($goods['goods_type'] == 0) {
                        $attr_number = $goods['goods_number'];
                    } else {
                        if (empty($prod)) { //当商品没有属性库存时
                            $attr_number = $goods['goods_number'];
                        }
                        if (!empty($prod) && $GLOBALS['_CFG']['add_shop_price'] == 0 && $onload == 'onload') {
                            if (empty($attr_number)) {
                                $attr_number = $goods['goods_number'];
                            }
                        }
                    }
                }

                $attr_number = !empty($attr_number) ? $attr_number : 0;
                $res['attr_number'] = $attr_number;

                //限制用户购买的数量 bywanglu
                $res['limit_number'] = $attr_number < $number ? ($attr_number ? $attr_number : 1) : $number;

                $res['shop_price'] = $goods['shop_price'];
                $res['market_price'] = $goods['market_price'];

                if ($GLOBALS['_CFG']['add_shop_price'] == 1) {
                    $add_tocart = 1;
                } else {
                    $add_tocart = 0;
                }

                //属性价格
                $warehouse_area = array(
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city,
                );
                $spec_price = spec_price($attr_id, $goods_id, $warehouse_area);

                // 最终价格
                $shop_price = get_final_price($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city, 0, 0, $add_tocart, 0, $product_promote_price);
                if ($GLOBALS['_CFG']['add_shop_price'] == 0 && empty($spec_price) && empty($prod)) {
                    $spec_price = $shop_price;
                }

                if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                    $shop_price = $spec_price;

                    $goods['market_price'] = isset($products['product_market_price']) && !empty($products['product_market_price']) ? $products['product_market_price'] : $goods['market_price'];
                    $res['result_market'] = price_format($goods['market_price']);
                } else {
                    $res['result_market'] = price_format($goods['market_price'] + $spec_price);
                }

                $res['result'] = price_format($shop_price);

                $res['spec_price'] = $spec_price;
                $res['spec_price_format'] = price_format($spec_price);

                //切换属性后的价格折扣 by wanglu
                // $res['discount'] = round(($shop_price / $goods['market_price']) * 10, 1);
            }

            // 组合套餐 返回区间价格
            // 1 首次加载
            if ($type == 1 && $onload == 'onload') {
                $group_count = get_group_goods_count($goods_id);
                if ($group_count > 0) {

                    $fittings_list = get_goods_fittings([$goods_id], $warehouse_id, $area_id, $area_city);
                    if ($fittings_list) {
                        $fittings_attr = $attr_id;
                        $goods_fittings = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, $area_city, '', 1, '', $fittings_attr);

                        if (is_array($fittings_list)) {
                            foreach ($fittings_list as $vo) {
                                $fittings_index[$vo['group_id']] = $vo['group_id'];//关联数组
                            }
                        }
                        ksort($fittings_index);//重新排序

                        $merge_fittings = get_merge_fittings_array($fittings_index, $fittings_list); //配件商品重新分组
                        $fitts = get_fittings_array_list($merge_fittings, $goods_fittings);
                        for ($i = 0; $i < count($fitts); $i++) {
                            $fittings_interval = $fitts[$i]['fittings_interval'];

                            $res['fittings_interval'][$i]['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                            $res['fittings_interval'][$i]['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');

                            if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                                $res['fittings_interval'][$i]['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                            } else {
                                $res['fittings_interval'][$i]['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                            }

                            $res['fittings_interval'][$i]['groupId'] = $fittings_interval['groupId'];
                        }
                    }
                }
            } else {
                // 切换属性
                $combo_goods = get_cart_combo_goods_list($goods_id, $parent_id, $group_id);

                if ($combo_goods['combo_number'] > 0) {
                    $goods_info = get_goods_fittings_info($parent_id, $warehouse_id, $area_id, $area_city, $group_id);
                    $fittings = get_goods_fittings([$parent_id], $warehouse_id, $area_id, $area_city, $group_id, 1);
                } else {
                    $goods_info = get_goods_fittings_info($parent_id, $warehouse_id, $area_id, $area_city, '', 1);
                    $fittings = get_goods_fittings([$parent_id], $warehouse_id, $area_id, $area_city);
                }

                $fittings = array_merge($goods_info, $fittings);
                $fittings = array_values($fittings);

                $fittings_interval = get_choose_goods_combo_cart($fittings);

                if ($combo_goods['combo_number'] > 0) {

                    // if ($fittings_interval['return_attr'] < 1) {
                    //配件商品没有属性时
                    $res['fittings_minMax'] = price_format($fittings_interval['all_price_ori']);
                    $res['market_minMax'] = price_format($fittings_interval['all_market_price']);
                    $res['save_minMaxPrice'] = price_format($fittings_interval['save_price_amount']);
                    // }
                    // else {
                    //     $res['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                    //     $res['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');

                    //     if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                    //         $res['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                    //     } else {
                    //         $res['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                    //     }
                    // }

                } else {
                    $res['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                    $res['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');

                    if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                        $res['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                    } else {
                        $res['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                    }
                }
            }

            $goodsGroup = explode('_', $group_id);
            $res['groupId'] = $goodsGroup[2];
            //查询组合购买商品区间价格 end

            if ($GLOBALS['_CFG']['open_area_goods'] == 1) {
                $area_list = get_goods_link_area_list($goods_id, $goods['user_id']);
                if ($area_list['goods_area']) {
                    if (!in_array($area_id, $area_list['goods_area'])) {
                        $res['err_no'] = 2;
                    }
                } else {
                    $res['err_no'] = 2;
                }
            }
            // 属性图片
            $attr_info = get_attr_value($goods_id, $attr_id[0]);
            if (!empty($attr_info['attr_img_flie'])) {
                $res['attr_img'] = get_image_path($attr_info['attr_img_flie']);
            }
            $res['goods_id'] = $goods_id;
            $res['parent_id'] = $parent_id;
            $res['group_name'] = $group_name;
            $res['onload'] = $onload;
            $res['region_id'] = $warehouse_id;
            $res['area_id'] = $area_id;
            $res['area_city'] = $area_city;

            // 点击切换属性 保存到 临时表
            if (($type == 1 && $onload != 'onload') || $type == 0) {
                $prod_attr = [];
                if (!empty($prod['goods_attr'])) {
                    $prod_attr = explode('|', $prod['goods_attr']);
                }

                if (count($prod_attr) <= 1) {
                    if (empty($result['attr_number'])) {
                        $result['message'] = L('stock_goods_null');
                    }
                } elseif (count($prod_attr) > 1) {
                    if (count($prod_attr) == count($attr_id)) {
                        if (empty($result['attr_number'])) {
                            $result['message'] = L('stock_goods_null');
                        }
                    } else {
                        unset($result['attr_number']);
                    }
                }

                if (is_spec($prod_attr) && !empty($prod)) {
                    $product_info = get_products_info($goods_id, $prod_attr, $warehouse_id, $area_id, $area_city);
                }

                $goods_attr = get_goods_attr_info_new($attr_id, 'pice', $warehouse_id, $area_id);

                // 主件商品
                if ($type == 1) {
                    $goods_price = $shop_price;
                } else {
                    // 配件商品
                    $sql = "SELECT goods_price FROM " . $GLOBALS['ecs']->table('group_goods') . " WHERE parent_id = '" . $parent_id . "' AND goods_id = '$goods_id' ";
                    $goods_price = $GLOBALS['db']->getOne($sql);

                    if ($GLOBALS['_CFG']['add_shop_price'] == 1) {
                        $goods_price = $goods_price + $spec_price;
                    }
                }

                $cart_combo_data = array(
                    'goods_attr_id' => implode(',', $attr_id),
                    'product_id' => $product_info['product_id'],
                    'goods_attr' => addslashes($goods_attr),
                    'goods_price' => $goods_price
                );
                if (!empty($_SESSION['user_id'])) {
                    $sess_id = " user_id = '" . $_SESSION['user_id'] . "' ";
                } else {
                    $sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
                }
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart_combo'), $cart_combo_data, 'UPDATE', "group_id = '$group_id' AND goods_id = '$goods_id' AND " . $sess_id);
            }

            exit(json_encode($res));
        }
    }


}