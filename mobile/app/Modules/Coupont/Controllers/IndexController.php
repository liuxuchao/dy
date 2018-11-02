<?php

namespace App\Modules\Coupont\Controllers;

use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    protected $size = 10;

    public function __construct()
    {
        parent::__construct();
        $this->assign('lang', array_change_key_case(L()));
        $files = [
            'clips',
            'transaction',
            'main'
        ];
        $this->load_helper($files);
    }

    /**
     * 好券集市- 全场券、会员券、免邮券
     */
    public function actionIndex()
    {
        $page = I('page', 1, 'intval');
        $status = I('status', 0, 'intval');
        if (IS_AJAX) {
            $coupons_list = get_coupons_list($this->size, $page, $status);
            exit(json_encode(['coupons_list' => $coupons_list, 'totalPage' => $coupons_list['totalpage']]));
        }
        $this->assign('status', $status);
        $this->assign('page_title', '好券集市');
        $this->display();
    }

    /**
     * 任务集市- 购物券
     */
    public function actionCouponsGoods()
    {
        $page = I('page', 1, 'intval');
        if (IS_AJAX) {
            $coupons_list = get_coupons_goods_list($this->size, $page);
            exit(json_encode(['coupons_list' => $coupons_list, 'totalPage' => $coupons_list['totalpage']]));
        }

        $this->assign('page_title', '任务集市');
        $this->display();
    }

    /**
     * 领取优惠券， (coupons_user,coupons)
     * @param int $cou_id 优惠券id
     * //1.根据优惠券id查询，是否还有剩余优惠券，查coupon_user表默认只能领取一次，
     * //2.领取优惠券，（优惠券数量减少，coupons_user 添加一条数据记录用户获取优惠券，）
     */
    public function actiongetCoupon()
    {
        $cou_id = I('cou_id','', 'intval');
        $uid = $_SESSION['user_id'];
        $ticket = 1;      // 默认每次领取一张优惠券
        $time = gmtime();
        if (IS_AJAX) {
            if (empty($_SESSION['user_id'])) {
                die(json_encode(['msg' => "请登录", 'error' => '1']));
            }
            //会员等级判断
            $rank = $_SESSION['user_rank'];
            $sql_cou = "select cou_type,cou_ok_user from {pre}coupons where cou_id = '$cou_id'";
            $rest = $this->db->getRow($sql_cou);
            //等级
            $type = $rest['cou_type'];      //优惠券类型
            $cou_rank = $rest['cou_ok_user'];  //可以使用优惠券的rank
            $ranks = explode(",", $cou_rank);
            if ($type == 2 || $type == 4 && $ranks != 0) {
                if (in_array($rank, $ranks)) {
                    $this->getCoups($cou_id, $uid, $ticket);
                } else {
                    die(json_encode(['msg' => "非预定会员不可领取", 'error' => 5]));  //没有优惠券不能领取
                }
            } else {
                $this->getCoups($cou_id, $uid, $ticket);
            }
        }
    }

    /**
     *  获取优惠券
     * @param type $cou_id
     * @param type $uid
     */
    protected function getCoups($cou_id, $uid, $ticket)
    {
        $time = gmtime();
        $sql = "SELECT c.*,c.cou_total-COUNT(cu.cou_id) cou_surplus FROM {pre}coupons c LEFT JOIN {pre}coupons_user cu ON c.cou_id=cu.cou_id GROUP BY c.cou_id  HAVING cou_surplus>0 AND c.review_status = 3 AND c.cou_id='" . $cou_id . "' AND c.cou_end_time>$time limit 1";
        $total = $this->db->getRow($sql);
        if (!empty($total)) {
            $sql = "select count(cou_id) as num from {pre}coupons_user where user_id = '$uid' and  cou_id = '$cou_id'";
            $num = $this->db->getOne($sql);
            $sql = "select cou_user_num, cou_money from {pre}coupons where cou_id = '$cou_id' LIMIT 1";
            $res = $this->db->getRow($sql);
            //判断是否已经领取了,并且还没有使用(根据创建优惠券时设定的每人可以领取的总张数为准,防止超额领取)
            if ($res && $res['cou_user_num'] > $num) {
                //领取优惠券
                $sql3 = "INSERT INTO {pre}coupons_user (`user_id`,`cou_id`, `cou_money`,`uc_sn`) VALUES ($uid, $cou_id, '" .$res['cou_money']. "', $time ) ";
                if ($GLOBALS['db']->query($sql3)) {
                    die(json_encode(['msg' => "领取成功！感谢您的参与，祝您购物愉快", 'error' => 2]));  //领取成功！感谢您的参与，祝您购物愉快
                }
            } else {
                die(json_encode(['msg' => '领取失败,您已经领取过该券了!每人限领取' . $res['cou_user_num'] . '张', 'error' => 3]));
            }
        } else {
            die(json_encode(['msg' => "优惠券已领完", 'error' => 4]));  //没有优惠券不能领取
        }
    }

    /**
     * 验证是否登录
     */
    private function check_login()
    {
        $without = ['AddPackageToCart'];
        if (!$_SESSION['user_id'] && !in_array(ACTION_NAME, $without)) {
            ecs_header("Location: " . url('user/login/index'));
        }
    }
}
