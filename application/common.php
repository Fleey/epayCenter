<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function curl($url = '', $addHeaders = [], $requestType = 'get', $requestData = '', $postType = '', $urlencode = true, $isProxy = false)
{
    if (empty($url))
        return '';
    //容错处理
    $headers  = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36'
    ];
    $postType = strtolower($postType);
    if ($requestType == 'get' && is_array($requestData)) {
        $tempBuff = '';
        foreach ($requestData as $key => $value) {
            if ($urlencode)
                $tempBuff .= rawurlencode(rawurlencode($key)) . '=' . rawurlencode(rawurlencode($value)) . '&';
            else
                $tempBuff .= $key . '=' . $value . '&';
        }
        $tempBuff = trim($tempBuff, '&');
        $url      .= '?' . $tempBuff;
    }
    //手动build get请求参数
    if (!empty($addHeaders))
        $headers = array_merge($headers, $addHeaders);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    //设置允许302转跳

//    $isProxy = true;
    if ($isProxy) {
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1'); //代理服务器地址
        curl_setopt($ch, CURLOPT_PROXYPORT, 1080); //代理服务器端口
        //set proxy
    }
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    //gzip

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //add ssl
    if ($requestType == 'get') {
        curl_setopt($ch, CURLOPT_HEADER, false);
    } else if ($requestType == 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($requestType));
    }
    //处理类型
    if ($requestType != 'get') {
        if (is_array($requestData) && !empty($requestData)) {
            $temp = '';
            foreach ($requestData as $key => $value) {
                if ($urlencode) {
//                    $temp .= urlencode($key) . '=' . urlencode($value) . '&';
                    $temp .= rawurlencode(rawurlencode($key)) . '=' . rawurlencode(rawurlencode($value)) . '&';
                } else {
                    $temp .= $key . '=' . $value . '&';
                }
            }
            $requestData = substr($temp, 0, strlen($temp) - 1);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
    }
    //只要不是get姿势都塞东西给他post
    if ($requestType != 'get') {
        if ($postType == 'json') {
            $headers[]   = 'Content-Type: application/json; charset=utf-8';
            $requestData = is_array($requestData) ? json_encode($requestData) : $requestData;
        } else if ($postType == 'xml') {
            $headers[] = 'Content-Type:text/xml; charset=utf-8';
        }
        $headers[] = 'Content-Length: ' . strlen($requestData);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);

    curl_close($ch);
    return $result;
}

/**
 * string 的小数 转int类型
 * @param string $decimals
 * @param int $decimalPlace
 * @return float|int
 */
function decimalsToInt(string $decimals, int $decimalPlace)
{
    $str = explode('.', $decimals);
    if (count($str) == 1) {
        $decimalMultiple = 1;
        for ($i = 1; $i <= $decimalPlace; $i++)
            $decimalMultiple *= 10;
        return intval($str[0]) * $decimalMultiple;
    }
    //保证数位
    if (count($str) != 2)
        return 0;

    $decimalMultiple = 1;
    for ($i = 1; $i <= $decimalPlace; $i++) {
        $decimalMultiple *= 10;
    }
    $decimalsLength = strlen($str[1]);
    $temp1          = intval($str[0]) * $decimalMultiple;
    if ($decimalPlace > $decimalsLength) {
        $temp2           = $decimalPlace - $decimalsLength;
        $decimalMultiple = 1;
        for ($i = 1; $i <= $temp2; $i++) {
            $decimalMultiple *= 10;
        }
        $temp1 += intval($str[1]) * $decimalMultiple;
    }
    if ($decimalPlace == $decimalsLength) {
        $temp1 += intval($str[1]);
    }
    //需求小数位符合
    if ($decimalPlace < $decimalsLength) {
        $str[1] = substr($str[1], 0, $decimalPlace);
        $temp1  += intval($str[1]);
    }
    //需求小数位大于
    return $temp1;
}

/**
 * 对数组排序
 * @param $para array//排序前的数组
 * @return array //排序后的数组
 */
function argSort($para)
{
    ksort($para);
    reset($para);
    return $para;
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para array//需要拼接的数组
 * @param bool $isUrlEncode
 * @return string//拼接完成以后的字符串
 */
function createLinkString($para, $isUrlEncode = false)
{
    $arg = '';
    foreach ($para as $key => $val) {
        if ($isUrlEncode)
            $arg .= $key . '=' . urlencode($val) . '&';
        else
            $arg .= $key . '=' . $val . '&';
    }
    //去掉最后一个&字符
    $arg = substr($arg, 0, strlen($arg) - 1);

    //如果存在转义字符，那么去掉转义
    if (get_magic_quotes_gpc()) {
        $arg = stripslashes($arg);
    }
    return $arg;
}

/**
 * 整数转小数 string 类型
 * @param $int
 * @param int $decimalPlace
 * @return string
 */
function intToDecimals($int, int $decimalPlace)
{
    $str       = strval($int);
    $strLength = strlen($str);
    $str       = substr($str, 0, $strLength - $decimalPlace) . '.' . substr($str, $strLength - $decimalPlace, $strLength);
    return $str;
}

/**
 * 除去数组中的空值和签名参数
 * @param $para array//签名参数组
 * @param bool $isUrlDecode
 * @return array//去掉空值与签名参数后的新签名参数组
 */
function paraFilter($para, $isUrlDecode = true)
{
    $para_filter = array();
    foreach ($para as $key => $val) {
        if ($key == 'sign' || $key == 'sign_type' || empty($val))
            continue;
        else
            if ($isUrlDecode)
                $para_filter[$key] = urldecode($val);
            else
                $para_filter[$key] = $val;
    }
    return $para_filter;
}

/**
 * 获取随机字符串
 * @param int $length
 * @return null|string
 */
function getRandChar($length = 8)
{
    $str    = null;
    $strPol = "ABCDEFGHIJKMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz";
    $max    = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

/**
 * 回调数据
 * @param $id
 * @throws \think\Exception
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 * @throws \think\exception\PDOException
 */
function processOrder($id)
{
    if (empty($id))
        return;
    $orderInfo = \think\Db::name('order')->where('id', $id)->field('uid,tradeNoOut,money,notify_url,payType,status,createTime,endTime')->limit(1)->select();
    if (empty($orderInfo))
        return;
    if (!$orderInfo[0]['status'])
        return;
    //订单无效
    $userInfo = \think\Db::name('user')->where('id', $orderInfo[0]['uid'])->field('key')->limit(1)->select();
    if (empty($userInfo))
        return;

    $tempData = [
        'status' => 1,
        'time'   => time(),
        'msg'    => '订单信息回调',
        'data'   => json_encode($orderInfo[0])
    ];

    $args              = argSort(paraFilter($tempData));
    $sign              = md5(createLinkString($args) . $userInfo[0]['key']);
    $data              = $tempData;
    $data['sign']      = $sign;
    $data['sign_type'] = 'MD5';

    $requestResult = curl($orderInfo[0]['notify_url'], [], 'post', $data, '', false);
    if ($requestResult === false)
        trace('日志信息: 请求结果 => ' . $requestResult . ' 请求id =>' . $id . ' 请求数据 => ' . json_encode($data), 'info');
    //回调事件
}

function buildReturnOrderUrl($id)
{
    if (empty($id))
        return '';
    $orderInfo = \think\Db::name('order')->where('id', $id)->field('uid,tradeNoOut,money,status,payType,endTime,return_url')->limit(1)->select();
    if (empty($orderInfo))
        return '';
    $userInfo = \think\Db::name('user')->where('id', $orderInfo[0]['uid'])->field('key')->limit(1)->select();
    if (empty($userInfo))
        return '';

    $args = [
        'tradeNoOut'  => $orderInfo[0]['tradeNoOut'],
        'money'       => $orderInfo[0]['money'] / 100,
        'tradeStatus' => $orderInfo[0]['status'] ? 'SUCCESS' : 'FAIL',
        'payType'     => \app\api\controller\ApiV1::converPayName($orderInfo[0]['payType'], true),
        'endTime'     => $orderInfo[0]['endTime']
    ];

    $sign = md5(createLinkString(argSort(paraFilter($args))) . $userInfo[0]['key']);

    $callBackUrl = $orderInfo[0]['return_url'] . (strpos($orderInfo[0]['return_url'], '?') ? '&' : '?') . createLinkString($args, true) . '&sign=' . $sign . '&sign_type=MD5';
    return $callBackUrl;
}

/**
 * 返回当前时间格式 存储数据库专用
 * @return false|string
 */
function getDateTime()
{
    return date('Y-m-d H:i:s', time());
}