<?php

namespace App\Modules\Merchants\Controllers;

use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    protected $user_id = 0;
    protected $step_id = 0;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        L(require(LANG_PATH . C('shop.lang') . '/merchants.php'));
        $this->assign('lang', array_change_key_case(L()));
        $this->user_id = $_SESSION['user_id'];
        //验证登录
        $this->actionchecklogin();
        $files = [
            'clips',
            'transaction',
            'main'
        ];
        $this->load_helper($files);
    }

    /**
     * 入驻商家信息
     */
    public function actionIndex()
    {
        //验证商家是否申请
        $shop = $this->model->table('merchants_shop_information')->where(['user_id' => $this->user_id])->find();
        if ($shop) {
            $this->redirect('merchants/index/audit');
        }

        if (IS_POST) {
            if (input('agree') == 1) {
                $data['agreement'] = input('agree');
            } else {
                show_message('请同意用户协议', '', '', 'error');
            }
            $data['fid'] = input('fid', 0, 'intval');
            $data['contactXinbie'] = input('contactXinbie', '', ['trim', 'html_in']);
            $data['contactName'] = input('contactName', '', ['trim', 'html_in']);
            $data['contactPhone'] = input('contactPhone', '', ['trim', 'html_in']);
            $data['license_adress'] = input('license_adress', '', ['trim', 'html_in']);

            $province_region_id = input('province_region_id', 0, 'intval');
            $city_region_id = input('city_region_id', 0, 'intval');
            $district_region_id = input('district_region_id', 0, 'intval');
            if (!empty($province_region_id)) {
                $data['company_located'] =  $province_region_id . ',' . $city_region_id . ',' . $district_region_id;
            }
            if (empty($data['contactName'])) {
                show_message(L('msg_shop_owner_notnull'), '', '', 'warning');
            }
            if (empty($data['contactPhone'])) {
                show_message(L('mobile_not_null'), '', '', 'warning');
            }
            if ($data['contactPhone'] && !is_mobile($data['contactPhone'])) {
                show_message(L('mobile_phone_invalid'), '', '', 'warning');
            }
            $data['user_id'] = $this->user_id;
            if (!empty($data['fid'])) {
                // 编辑
                dao('merchants_steps_fields')->where(['fid' => $data['fid'], 'user_id' => $this->user_id])->data($data)->save();
            } else {
                dao('merchants_steps_fields')->data($data)->add();
            }
            $this->redirect('merchants/index/shop');
        }
        $this->step_id = 2;
        // 验证PC商家入驻申请流程 - 公司信息认证
        $steps = dao('merchants_steps_fields')->where(['user_id' => $this->user_id])->find();
        $this->assign('steps', $steps);
        $this->assign('page_title', L('business_information'));
        $this->display();
    }

    /**
     * 入驻店铺信息
     */
    public function actionShop()
    {
        if (IS_POST) {
            $data = input('', ['trim', 'html_in']);
            if (empty($data['rz_shopName'])) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_shop_name_notnull')]));
            }
            if (empty($data['hopeLoginName'])) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_login_shop_name_notnull')]));
            }
            if (empty($data['shoprz_type'])) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_shoprz_type_notnull')]));
            }
            // 验证 旗舰店 子类型
            if ($data['shoprz_type'] && $data['shoprz_type'] == 1) {
                if (empty($data['subShoprz_type'])) {
                    exit(json_encode(['status' => 'n', 'info' => L('msg_sub_shoprz_type_notnull')]));
                }
            }
            if (empty($data['shop_categoryMain'])) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_shop_category_main_notnull')]));
            }
            $data['user_id'] = $this->user_id;
            $data['add_time'] = gmtime();
            $res = dao('merchants_shop_information')->data($data)->add();
            if ($res == true) {
                // 更新临时类目表
                dao('merchants_category_temporarydate')->data(['is_add' => 1])->where(['user_id' => $this->user_id, 'is_add' => 0])->save();
                exit(json_encode(['status' => 'y', 'info' => L('add_success').L('wait_audit'), 'url' => url('merchants/index/audit')]));
                // $this->redirect('merchants/index/audit');
            } else {
                exit(json_encode(['status' => 'n', 'info' => L('add_error')]));
            }
        }

        $this->step_id = 3;
        //验证商家是否申请
        $shop = $this->model->table('merchants_shop_information')->where(['user_id' => $this->user_id])->find();
        if ($shop) {
            $this->redirect('merchants/index/audit');
        }

        if ($this->step_id > 1 && $this->step_id < 4){
            //删除临时表数据
            dao('merchants_category_temporarydate')->where(['user_id' => $this->user_id, 'is_add' => 0])->delete();
        }
        // 顶级分类
        $category = get_first_cate_list(0, 0);
        foreach ($category as $key => $value) {
            $category[$key]['cat_name'] = !empty($value['cat_alias_name']) ? $value['cat_alias_name'] : $value['cat_name'];
        }
        $this->assign('category', $category);
        $this->assign('page_title', L('store_information'));
        $this->display();
    }

    /**
     * 获取下级分类
     * @return
     */
    public function actionGetChildCate()
    {
        if (IS_POST) {
            $cat_id = input('cat_id', 0, 'intval');
            if ($cat_id) {
                $childCate = get_first_cate_list($cat_id, 0);
                foreach ($childCate as $key => $value) {
                    $childCate[$key]['cat_name'] = !empty($value['cat_alias_name']) ? $value['cat_alias_name'] : $value['cat_name'];
                }
                exit(json_encode(['status' => 0, 'cat_id' => $cat_id, 'childCate' => $childCate]));
            }
            exit(json_encode(['status' => 1]));
        }
    }

    /**
     * 添加详细类目 is_add = 0
     * @return
     */
    public function actionAddChildCate()
    {
        if (IS_POST) {
            $cat_id = input('cat_id', 0, 'intval');
            $child_cate_id = input('child_cate_id', '', ['trim', 'html_in']);

            $category_info = [];
            if ($cat_id && $child_cate_id) {
                $category_info = get_fine_category_info($child_cate_id, $this->user_id);
                exit(json_encode(['status' => 0, $cat_id => $cat_id, 'category_info' => $category_info]));
            }
            exit(json_encode(['status' => 1]));
        }
    }

    /**
     * 删除详细类目 is_add = 0
     * @return
     */
    public function actionDeleteChildCate()
    {
        if (IS_AJAX) {
            $ct_id = input('ct_id', 0, 'intval');

            if ($ct_id) {
                $catParent = get_temporarydate_ctId_catParent($ct_id);
                if ($catParent && $catParent['num'] == 1) {
                    dao('merchants_dt_file')->where(['cat_id' => $catParent['parent_id']])->delete();
                }

                dao('merchants_category_temporarydate')->where(['ct_id' => $ct_id, 'is_add' => 0])->delete();
                exit(json_encode(['status' => 0, $ct_id => $ct_id]));
            }
            exit(json_encode(['status' => 1]));
        }
    }

    /**
     * 等待审核
     */
    public function actionAudit()
    {
        //店铺状态
        $shop = $this->model->table('merchants_shop_information')->field('merchants_audit,merchants_message')->where(['user_id' => $this->user_id])->find();
        $this->assign('shop', $shop);
        $this->assign('img', elixir('img/shenqing-loding.gif'));

        $this->assign('page_title', L('review_the_status'));
        $this->display();
    }

    /**
     * 入驻须知
     */
    public function actionGuide()
    {
        $this->step_id = input('step', 1, 'intval');
        $sql = "SELECT process_title, process_article FROM {pre}merchants_steps_process WHERE process_steps = ".$this->step_id;
        $row = $this->db->getRow($sql);
        if ($row['process_article'] > 0) {
            $row['article_centent'] = $this->db->getOne("SELECT content FROM {pre}article WHERE article_id = '" . $row['process_article'] . "'");
        }
        if (IS_AJAX) {
            exit(json_encode(['status' => 0, 'row' => $row['article_centent']]));
        }
        $this->assign('row', $row);
        $this->assign('page_title', L('instructions'));
        $this->display();
    }

    /**
     * 验证是否登录
     */
    public function actionchecklogin()
    {
        if (!$this->user_id) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect('user/login/index', ['back_act' => urlencode($back_act)]);
        }
    }
}
