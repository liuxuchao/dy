<?php

namespace App\Repositories\Category;

use App\Models\Goods;
use App\Models\Category;
use App\Models\DrpType;
use Illuminate\Support\Facades\Cache;

class CategoryRepository
{

    /**
     * 获取所有分类列表
     * @return array
     */
    public function getAllCategorys($uid)
    {
        $category_list = Cache::get('category_list');
        //缓存一小时
        if (empty($category_list)) {
            $category_list = $this->getTree($uid, 0);
            Cache::put('category_list', $category_list, 60);
        }

        return $category_list;
    }

    /**
     * 获取所有分类列表
     * @return array
     */
    public function getDrpCategorys($uid)
    {
        //$category_list = Cache::get('category_list');
        //缓存一小时
        //if (empty($category_list)) {
            $category_list = $this->getTree($uid, 0);
           // Cache::put('category_list', $category_list, 60);
        //}

        return $category_list;
    }

    /**
     * 获得分类下的商品
     * @param $id
     * @return array
     */
    public function getCategoryGetGoods($id)
    {
        $goods = Goods::select('goods_id', 'goods_sn', 'goods_name')
            ->where('cat_id', $id)
            ->get()
            ->toArray();

        return $goods;
    }

    /**
     * 获取商品分类树
     * @param int $tree_id
     * @param int $top
     * @return array
     */
    private function getTree($uid, $tree_id = 0, $top = 0)
    {
        $three_arr = [];
        $count = Category::where('parent_id', $tree_id)
            ->where('is_show', 1)
            ->count();
        if ($count > 0 || $tree_id == 0) {
            $res = Category::select('cat_id', 'cat_name', 'touch_icon', 'parent_id', 'cat_alias_name', 'is_show')
                ->where('parent_id', $tree_id)
                ->where('is_show', 1)
                ->with(['goods'=>function ($query) {
                    $query->select('goods_id', 'cat_id', 'goods_thumb')->where('is_on_sale', 1)->where('is_delete', 0)->orderby('sort_order', 'ASC')->orderby('goods_id', 'DESC');
                }])
                ->orderby('sort_order', 'ASC')
                ->orderby('cat_id', 'ASC')
                ->get()
                ->toArray();
            foreach ($res as $k => $row) {
                if ($row['is_show']) {
                    $type = DrpType::select('id')
                            ->where('cat_id', $row['cat_id'])
                            ->where('user_id', $uid)
                            ->first();
                    if (!empty($type)) {
                        $three_arr[$k]['drp_type'] = true;
                    } else {
                        $three_arr[$k]['drp_type'] = false;
                    }
                    $three_arr[$k]['id'] = $row['cat_id'];
                    $three_arr[$k]['name'] = $row['cat_alias_name'] ? $row['cat_alias_name'] : $row['cat_name'];
                    if (isset($row['goods'][0]['goods_thumb'])) {
                        $three_arr[$k]['cat_img'] = !empty($row['touch_icon']) ? get_image_path($row['touch_icon']) : get_image_path($row['goods'][0]['goods_thumb']);
                    } else {
                        $three_arr[$k]['cat_img'] = !empty($row['touch_icon']) ? get_image_path($row['touch_icon']) : get_image_path();
                    }

                    $three_arr[$k]['haschild'] = 0;
                }
                if (isset($row['cat_id'])) {
                    $child_tree = $this->getTree($uid, $row['cat_id']);
                    if ($child_tree) {
                        $three_arr[$k]['cat_id'] = $child_tree;
                        $three_arr[$k]['haschild'] = 1;
                    }
                }
            }
        }
        return $three_arr;
    }

    /**
     * 获取商品分类树
     * @param int $cat_id
     * @return array
     */
    public function catList($cat_id = 0)
    {

        $arr = [];
        $count = Category::where('parent_id', $cat_id)
            ->where('is_show', 1)
            ->count();
        if($count > 0){
            $res = Category::select('cat_id', 'cat_name', 'touch_icon', 'parent_id', 'cat_alias_name', 'is_show')
                ->where('parent_id', $cat_id)
                ->where('is_show', 1)
                ->orderby('sort_order', 'ASC')
                ->orderby('cat_id', 'ASC')
                ->get()
                ->toArray();

            if ($res === null) {
                return [];
            }

            foreach ($res as $key => $row) {
                $arr[$row['cat_id']]['cat_id'] = $row['cat_id'];
                if (isset($row['cat_id'])) {
                    $child_tree = $this->catList($row['cat_id']);
                    if ($child_tree) {
                        $arr[$row['cat_id']]['child_tree'] = $child_tree;
                    }
                }
            }

        }
        return $arr;
    }


    /**
     * 多维数组转为一维数组
     */
    public function arr_foreach($arr)
    {
        $tmp = [];
        if (!is_array($arr)) {
            return false;
        }
        foreach ($arr as $val) {
            if (is_array($val)) {
                $tmp = array_merge($tmp, $this->arr_foreach($val));
            } else {
                $tmp[] = $val;
            }
        }
        return $tmp;
    }






}




