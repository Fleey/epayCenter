<?php

namespace app\api\model;

class EebPayV1Model
{
    private $appID;
    private $appKey;
    private $gateway;

    public function __construct()
    {
        $this->appID   = env('EBB_APP_ID');
        $this->appKey  = env('EBB_APP_KEY');
        $this->gateway = env('EBB_GATEWAY') . '/GateWay/ApiInterFace.aspx';
    }

    /**
     * @param string $tradeNo
     * @param string $money //注意金额为RMB 元 精确到2位小数
     * @param $payType
     * @param string $notifyUrl
     * @param string $callBackUrl
     * @param string $productName
     * @return array
     */
    public function getPayUrl(string $tradeNo, string $money, $payType, string $notifyUrl, string $callBackUrl = '', string $productName = '')
    {
        if (empty($productName))
            $productName = '商品支付-' . uniqid();

        $requestUrl = $this->gateway;

        $param = [
            'ApiMethod' => 'OnLinePay',
            'Version'   => 'V2.0',
            'MerID'     => $this->appID,
            'TradeNum'  => $tradeNo,
            'Amount'    => $money,
            'GoodsName' => $productName,
            'NotifyUrl' => $notifyUrl,
            'TransTime' => date('YmdHis', time()),
            'PayType'   => $payType,
//            'IsImgCode' => '1',
            'SignType'  => 'MD5'
        ];

        if (!empty($callBackUrl))
            $param['ReturnUrl'] = $callBackUrl;

        $param['Sign'] = $this->buildSignMD5($param);
        //build sign
        return ['isSuccess' => true, 'url' => $requestUrl . '?' . createLinkString($param, true)];

//        $requestResult = curl($requestUrl, [], 'post', $param, '', false);
//        if ($requestResult === false)
//            return ['isSuccess' => false, 'msg' => '[EebPay] Request Pay Url error 10001'];
//        $requestResult = json_decode($requestResult, true);
//        if ($requestResult['RespCode'] != '1111') {
//            if (!empty($requestResult['Message']))
//                trace('[EebPay]' . $requestResult['Message'], 'info');
//            return ['isSuccess' => false, 'msg' => '[EebPay] get pay url error pls connect web admin'];
//        }
//
//        $verifySign = $this->buildSignMD5($requestResult);
//        if ($verifySign != $requestResult['Sign'])
//            return ['isSuccess' => false, 'msg' => '[EebPay] Return param verify sign error'];
//        //verify return data
//        if (empty($requestResult['PayUrl']))
//            return ['isSuccess' => false, 'msg' => '[EebPay] PayUrl is empty ,pls connect web admin'];
//
//        return ['isSuccess' => true, 'url' => $requestResult['PayUrl']];
    }

    /**
     * @param $tradeNo
     * @return bool
     */
    public function isPay($tradeNo)
    {
        $requestUrl = $this->gateway;

        $param         = [
            'ApiMethod' => 'QueryPayOrder',
            'Version'   => 'V2.0',
            'MerID'     => $this->appID,
            'TradeNum'  => $tradeNo,
            'TransTime' => date('YmdHis', time()),
            'SignType'  => 'MD5'
        ];
        $param['Sign'] = $this->buildSignMD5($param);
        $requestResult = curl($requestUrl, [], 'post', $param);
        if ($requestResult === false)
            return false;
        $requestResult = json_decode($requestResult, true);
        if ($requestResult['RespCode'] != '1111')
            return false;
        $verifySign = $this->buildSignMD5($requestResult);
        if ($verifySign != $requestResult['Sign'])
            return false;
        return $requestResult['Status'] == '01';
    }

    /**
     * 构建签名
     * @param array $param
     * @return string
     */
    public function buildSignMD5(array $param)
    {
        $tempArr = argSort(paraFilter($param, false));
        $str1    = '';
        foreach ($tempArr as $key => $value) {
            if ($key == 'Sign')
                continue;
            $str1 .= $key . '=' . $value . '&';
        }
        $str1 = md5($str1 . 'key=' . $this->appKey);
        return $str1;
    }
}