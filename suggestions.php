<?php

/*
 * 搜索框提示功能
 * 
 * =============================================================================
 * 
 * $Author guan $
 * 
 * $Id suggestions.php 2013-10-08 14:14:40
 * =============================================================================
 */

define('IN_ECS', true);

if (!function_exists("htmlspecialchars_decode")) {

    function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT) {
        return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
    }

}
require(dirname(__FILE__) . '/includes/init.php');
require_once (dirname(__FILE__) . '/includes/cls_json.php');
$json = new JSON();

$keyword = empty($_POST['keyword']) ? '' : trim($_POST['keyword']);
$keyword = !empty($keyword) ? dsc_addslashes($keyword) : '';
$category = empty($_POST['category']) ? 0 : trim($_POST['category']);

if ($category == $_LANG['all_attribute'] ) {
    $children = "";
    $parent = "";
} elseif ($category == $_LANG['Template']) {
    $children = get_children(9);
    $children = str_replace('g.', ' AND ', $children);
    $parent = " AND parent_id = 9";
} elseif ($category == $_LANG['plugins']) {
    $children = get_children(23);
    $children = str_replace('g.', ' AND ', $children);
    $parent = " AND parent_id = 23";
} else {
    $children = "";
    $parent = "";
}

if (empty($keyword)) {
    echo '';
    exit;
} else {
    
    $goods_where = ' AND g.is_show = 1 ';
    //ecmoban模板堂 --zhuo start
    if ($GLOBALS['_CFG']['review_goods'] == 1) {
        $goods_where .= ' AND g.review_status > 2 ';
    }
    //ecmoban模板堂 --zhuo end	
    //
    //$keyword = iconv("UTF-8","GB2312",$keyword);
    $sql = "SELECT distinct keyword FROM " . $ecs->table('search_keyword') . "WHERE keyword LIKE '%" . mysql_like_quote($keyword) . "%' OR pinyin_keyword LIKE '%" . mysql_like_quote($keyword) . "%' ORDER BY count DESC";
    $result = $db->selectLimit($sql, 10);

    //查询分类
    $sql = "SELECT cat_id, cat_name, parent_id FROM " . $ecs->table('category') . " WHERE cat_name LIKE '%" . mysql_like_quote($keyword) . "%' OR pinyin_keyword LIKE '%" . mysql_like_quote($keyword) . "%' $children limit 0,4";
    $cate_res = $db->getAll($sql);

    $cat_html = '';

    foreach ($cate_res as $key => $row) {
        if ($row['parent_id'] > 0) {
            $sql_1 = "SELECT cat_name FROM " . $ecs->table('category') . "WHERE cat_id=" . $row['parent_id'];
            $parent_res = $db->getRow($sql_1);
            $url = build_uri('category', array('cid' => $row['cat_id']));
            if ($url == "") {
                $url = '#';
            }
            $cat_html .= '<li onmouseover="_over(this);" onmouseout="_out(this);">' . "&nbsp;&nbsp;&nbsp;在<a class='cate_user' href=" . $url . " style='color:#ec5151;'>" . $parent_res['cat_name'] . ">" . $row['cat_name'] . "</a>" . $_LANG['cat_search'].'</li>';
        }
    }

    $html = '<ul id="suggestions_list_id"><input type="hidden" value="1" name="selectKeyOne" id="keyOne" />';
    $res_num = 0;
    $exist_keyword = array();
    while ($row = $db->FetchRow($result)) {
        $scws_res = scws($row['keyword']); //这里可以把关键词分词：诺基亚，耳机
        $arr = explode(',', $scws_res);
        $operator = " AND ";

		/*补充搜索条件 by wu start*/
		$insert_keyword = trim($row['keyword']);
		if(empty($arr[0]))
		{
			$arr[0] = $insert_keyword;
		}
		/*补充搜索条件 by wu end*/
		
        $keywords = 'AND (';
        $goods_ids = array();
        foreach ($arr AS $key => $val) {
            
            $val = !empty($val) ? dsc_addslashes($val) : '';
            
            if ($key > 0 && $key < count($arr) && count($arr) > 1) {
                $keywords .= $operator;
            }
            $val = mysql_like_quote(trim($val));
            $keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%' $sc_dsad)";
            
            $sql = 'SELECT DISTINCT goods_id FROM ' . $ecs->table('tag') . " WHERE tag_words LIKE '%$val%' ";
            $res = $db->query($sql);
            while ($rows = $db->FetchRow($res)) {
                $goods_ids[] = $rows['goods_id'];
            }
        }
        $keywords .= ')';
		
		/*补充搜索条件 by wu start*/
		//ecmoban模板堂 --zhuo start
		require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo
        $warehouse_other = [
            'province_id' => $province_id,
            'city_id' => $city_id
        ];
        $warehouse_area_info = get_warehouse_area_info($warehouse_other);

        $region_id = $warehouse_area_info['region_id'];
        $area_id = $warehouse_area_info['area_id'];
        $area_city = $warehouse_area_info['city_id'];

		$where = "regionId = '$province_id'";
		$date = array('parent_id');
		$region_id = get_table_date('region_warehouse', $where, $date, 2);
		//ecmoban模板堂 --zhuo end
		$categories = '';
		$brand = '';
		$price_min = '';
		$price_max = '';
		$intro = '';
		$outstock = '';	
		$tag_where = '';
		$leftJoin = '';	  

        $where_area = '';
        if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
            $where_area = " AND wag.city_id = '$area_city'";
        }                        
		
		$shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
		$leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_goods'). " as wg on g.goods_id = wg.goods_id and wg.region_id = '$region_id' ";
		$leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_area_goods'). " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' $where_area ";
		
		$area_where = '';
		if($GLOBALS['_CFG']['open_area_goods'] == 1){
			$leftJoin .= " left join " .$GLOBALS['ecs']->table('link_area_goods'). " as lag on g.goods_id = lag.goods_id ";
			$area_where = " and lag.region_id = '$area_id' ";
		}
		
		if($GLOBALS['_CFG']['review_goods'] == 1){
			$tag_where .= ' AND g.review_status > 2 ';
		}		
		/* 获得符合条件的商品总数 */
		$sql   = "SELECT COUNT(*) FROM " .$ecs->table('goods'). " AS g ".
			$leftJoin . 
			"WHERE g.is_delete = 0 AND g.is_on_sale = 1 " .$area_where. " AND g.is_alone_sale = 1 $attr_in " . $goods_where .
			"AND (( 1 " . $categories . $keywords . $brand . $price_min . $price_max . $intro . $outstock ." ) ".$tag_where." )";
			
		$count = $db->getOne($sql);
		/*补充搜索条件 by wu end*/		
		
        //如果查询的数量为空则不显示此关键词
        if ($count <= 0) {
            continue;
        }

        $keyword = preg_quote($keyword); //特殊字符自动添加转义符\
        //$keyword_style = preg_replace("/($keyword_style)/","<font style='color:red; font-weight:blod;'>$1</font>",$row['keyword']);
        $keyword_style = preg_replace("/($keyword)/i", "<font style='font-weight:normal;color:#ec5151;'>$1</font>", $row['keyword']);
        $keyword_string = "<font style='font-weight:;'>" . $keyword . "</font>";
        $keyword_name = str_replace($keyword, $keyword_string, $weight_keyword);
        $html .= '<li onmouseover="_over(this);" title="' . $row['keyword'] . '" onmouseout="_out(this);" onClick="javascript:fill(\'' . $row['keyword'] . '\');"><div class="left-span">&nbsp;' . $keyword_style . '</div><div class="suggest_span">约' . $count . '个商品</div></li>';
        $res_num++;
        $exist_keyword[] = $row['keyword'];
    }

    if (isset($cat_html) && $cat_html != "") {
        $html .= $cat_html;
        $html .= '<li style="height:1px; overflow:hidden; border-bottom:1px #eee solid; margin-top:-1px;"></li>';
        unset($cat_html);
    }
    
    //查询商品关键字
    if ($res_num < 10) {
        $sql = "SELECT distinct g.goods_name FROM " . $ecs->table('goods') . " AS g " .
                " WHERE g.goods_name like '%$keyword%' OR g.pinyin_keyword LIKE '%$keyword%' AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1" . $goods_where;
        
        $keyword_res = $db->getAll($sql);

        $res_count = count($keyword_res);
        if ($res_count <= 0) {
            $html .= '</ul>';

            if ($html == '<ul id="suggestions_list_id"><input type="hidden" value="1" name="selectKeyOne" id="keyOne" /></ul>') {
                $html = '';
            }

            echo $html;
            exit;
        }
        $len = 10 - $res_num;
        for ($i = 0; $i < $len; $i++) {
            if ($res_count == $i) {
                break;
            }
            $scws_res = scws($keyword_res[$i]['goods_name']); //这里可以把关键词分词：诺基亚，耳机
            $arr = explode(',', $scws_res);
            $operator = " AND ";
            //@author guan end

            $keywords = 'AND (';
            $goods_ids = array();
            foreach ($arr AS $key => $val) {
                
                $val = !empty($val) ? dsc_addslashes($val) : '';

                if ($key > 0 && $key < count($arr) && count($arr) > 1) {
                    $keywords .= $operator;
                }
                $val = mysql_like_quote(trim($val));
                $keywords .= "(g.goods_name LIKE '%$val%' OR g.goods_sn LIKE '%$val%' OR g.keywords LIKE '%$val%' $sc_dsad)";

                $sql = 'SELECT DISTINCT goods_id FROM ' . $ecs->table('tag') . " WHERE tag_words LIKE '%$val%' ";
                $res = $db->query($sql);
                while ($rows = $db->FetchRow($res)) {
                    $goods_ids[] = $rows['goods_id'];
                }
            }
            $keywords .= ')';
            $count = $db->getOne("SELECT count(*) FROM " . $ecs->table('goods') . " AS g " . 
                    " WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 $keywords $goods_where");

            if ($count <= 0) {
                continue;
            }

            if (in_array($keyword_res[$i]['goods_name'], $exist_keyword)) {
                continue;
            }

            $keyword_new_name = $keyword_res[$i]['goods_name'];
            cut_str($keyword_new_name, 25);

            $keyword_style = preg_replace("/($keyword)/i", "<font style='font-weight:normal;color:#ec5151;'>$1</font>", $keyword_new_name);
            $html .= '<li onmouseover="_over(this);" onmouseout="_out(this);" title="' . $keyword_new_name . '" onClick="javascript:fill(\'' . $keyword_new_name . '\');"><div class="left-span">&nbsp;' . $keyword_style . '</div>&nbsp;<b>' . '</b>' . '<div class="suggest_span">约' . $count . '个商品</div></li>';
        }
    }

    $html .= '</ul>';

    if ($html == '<ul id="suggestions_list_id"><input type="hidden" value="1" name="selectKeyOne" id="keyOne" /></ul>') {
        $html = '';
    }

    echo $html;
    exit;
}

/**
 *  author : lihui870920@163.com
 *  date  : 2012-05-12
 *  截取指定的中英文字符的长度
 *
 * 	指定字符串

 * 	保留长度

 * 	开始位置

 * 	编码
 */
function cut_str($string, $sublen, $start = 0, $code = 'gbk') {
    if ($code == 'utf-8') {
        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string);
        if (count($t_string[0]) - $start > $sublen)
            return join('', array_slice($t_string[0], $start, $sublen)) . "...";
        return join('', array_slice($t_string[0], $start, $sublen));
    }
    else {
        $start = $start * 2;
        $sublen = $sublen * 2;
        $strlen = strlen($string);
        $tmpstr = '';

        for ($i = 0; $i < $strlen; $i++) {
            if ($i >= $start && $i < ($start + $sublen)) {
                if (ord(substr($string, $i, 1)) > 129) {
                    $tmpstr.= substr($string, $i, 2);
                } else {
                    $tmpstr.= substr($string, $i, 1);
                }
            }

            if (ord(substr($string, $i, 1)) > 129)
                $i++;
        }
        //超出多余的字段就显示...

        if (strlen($tmpstr) < $strlen)
            $tmpstr.= "";

        return $tmpstr;
    }
}

?>
