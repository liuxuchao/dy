<?php

use App\Extensions\Pinyin;

/**
 * 获取首字母
 * @param unknown $str
 * @return string
 */
function getLetter($str)
{
    $i = 0;
    while ($i < strlen($str)) {
        $tmp = bin2hex(substr($str, $i, 1));
        if ($tmp >= 'B0') { //汉字
            $object = new Pinyin();
            $pyobj = $object->output($str);
            $pinyin = isset($pyobj[0]) ? $pyobj[0] : '';
            return strtoupper(substr($pinyin, 0, 1));
            $i += 2;
        } else {
            return strtoupper(substr($str, $i, 1));
            $i++;
        }
    }
}

/**
 * 将内容进行UNICODE编码，编码后的内容格式：\u56fe\u7247 （原始：图片）
 */
function unicode_encode($name)
{
    $name = iconv('UTF-8', 'UCS-2', $name);
    $len = strlen($name);
    $str = '';
    for ($i = 0; $i < $len - 1; $i = $i + 2) {
        $c = $name[$i];
        $c2 = $name[$i + 1];
        if (ord($c) > 0) {    // 两个字节的文字
            $str .= '\u' . base_convert(ord($c), 10, 16) . base_convert(ord($c2), 10, 16);
        } else {
            $str .= $c2;
        }
    }
    return $str;
}

/**
 * 将UNICODE编码后的内容进行解码，编码后的内容格式：\u56fe\u7247 （原始：图片）
 */
function unicode_decode($name)
{
    // 转换编码，将Unicode编码转换成可以浏览的utf-8编码
    $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
    preg_match_all($pattern, $name, $matches);
    if (!empty($matches)) {
        $name = '';
        for ($j = 0; $j < count($matches[0]); $j++) {
            $str = $matches[0][$j];
            if (strpos($str, '\\u') === 0) {
                $code = base_convert(substr($str, 2, 2), 16, 10);
                $code2 = base_convert(substr($str, 4), 16, 10);
                $c = chr($code) . chr($code2);
                $c = iconv('UCS-2', 'UTF-8', $c);
                $name .= $c;
            } else {
                $name .= $str;
            }
        }
    }
    return $name;
}

/**
 * js escape php 实现
 * @param $string
 * @param string $in_encoding
 * @param string $out_encoding
 * @return string
 */
function escape($string, $in_encoding = 'UTF-8', $out_encoding = 'UCS-2')
{
    $return = '';
    if (function_exists('mb_get_info')) {
        for ($x = 0; $x < mb_strlen($string, $in_encoding); $x++) {
            $str = mb_substr($string, $x, 1, $in_encoding);
            if (strlen($str) > 1) { // 多字节字符
                $return .= '%u' . strtoupper(bin2hex(mb_convert_encoding($str, $out_encoding, $in_encoding)));
            } else {
                $return .= '%' . strtoupper(bin2hex($str));
            }
        }
    }
    return $return;
}

function unescape($str)
{
    $ret = '';
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        if ($str[$i] == '%' && $str[$i + 1] == 'u') {
            $val = hexdec(substr($str, $i + 2, 4));
            if ($val < 0x7f) {
                $ret .= chr($val);
            } else {
                if ($val < 0x800) {
                    $ret .= chr(0xc0 | ($val >> 6)) .
                        chr(0x80 | ($val & 0x3f));
                } else {
                    $ret .= chr(0xe0 | ($val >> 12)) .
                        chr(0x80 | (($val >> 6) & 0x3f)) .
                        chr(0x80 | ($val & 0x3f));
                }
            }
            $i += 5;
        } else {
            if ($str[$i] == '%') {
                $ret .= urldecode(substr($str, $i, 3));
                $i += 2;
            } else {
                $ret .= $str[$i];
            }
        }
    }
    return $ret;
}
