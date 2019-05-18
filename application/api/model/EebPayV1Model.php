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
            'UserIP'    => '127.0.0.1',
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
//        return ['isSuccess' => true, 'url' => urldecode($requestResult['PayUrl'])];
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
     * @param $settleNo //结算ID
     * @param $money //结算金额为元 保留两位小数
     * @param $bankCardName //收款人
     * @param $bankCardNo //收款卡号
     * @param $bankType //开户行
     * @param $bankAddress //开户分行
     * @param $bankBranchName //开户支行
     * @param $bankProvince //开户省份
     * @param $bankCity //开户城市
     * @param $notifyUrl //回调地址
     * @return array //[处理结果,失败描述]
     */
    public function applySettle($settleNo, $money, $bankCardName, $bankCardNo, $bankType, $bankAddress, $bankBranchName,
                                $bankProvince, $bankCity, $notifyUrl)
    {
        $requestUrl = $this->gateway;

        $param = [
            'ApiMethod'      => 'SettOrderPay',
            'Version'        => 'V2.0',
            'MerID'          => $this->appID,
            'TradeNum'       => $settleNo,
            'Amount'         => $money,
            'BankCardName'   => $bankCardName,
            'BankCardNo'     => $bankCardNo,
            'BankType'       => $bankType,
            'BankAddress'    => $bankAddress,
            'BankBranchName' => $bankBranchName,
            'BankProvince'   => $bankProvince,
            'BankCity'       => $bankCity,
            'NotifyUrl'      => $notifyUrl,
            'TransTime'      => date('YmdHis', time()),
            'SignType'       => 'MD5'
        ];

        $param['Sign'] = $this->buildSignMD5($param);
        $requestResult = curl($requestUrl, [], 'post', $param);
        if ($requestResult === false)
            return [false, '请求数据失败'];
        if ($requestResult['RespCode'] != '1111')
            return [false, $requestResult['Message']];
        return [true, '处理成功'];
    }

    /**
     * @param $settleID
     * @return int //结算状态 0 查询失败 1 处理中 2 处理成功 3 处理失败
     */
    public function getSettleStatus($settleID)
    {
        $requestUrl = $this->gateway;

        $param         = [
            'ApiMethod' => 'QuerySettOrder',
            'Version'   => 'V2.0',
            'MerID'     => $this->appID,
            'TradeNum'  => $settleID,
            'TransTime' => date('YmdHis', time()),
            'SignType'  => 'MD5'
        ];
        $param['Sign'] = $this->buildSignMD5($param);
        $requestResult = curl($requestUrl, [], 'post', $param);
        if ($requestResult === false)
            return 0;
        $requestResult = json_decode($requestResult, true);
        if (empty($requestResult))
            return 0;
        if ($requestResult['RespCode'] != '1111')
            return 0;
        $verifySign = $this->buildSignMD5($requestResult);
        if ($verifySign != $requestResult['Sign'])
            return 0;
        //check sign
        if ($requestResult['Status'] == '01')
            return 2;
        if ($requestResult['Status'] == '02')
            return 1;
        if ($requestResult['Status'] == '03')
            return 3;
        return 0;
    }

    /**
     * 获取账号余额
     * @return array //返回单位为分 [用户余额,可结算金额]
     */
    public function getBalance()
    {
        $requestUrl = $this->gateway;

        $param         = [
            'ApiMethod' => 'QueryBalance',
            'Version'   => 'V2.0',
            'MerID'     => $this->appID,
            'TransTime' => date('YmdHis', time()),
            'SignType'  => 'MD5'
        ];
        $param['Sign'] = $this->buildSignMD5($param);

        $requestResult = curl($requestUrl, [], 'post', $param);
        if ($requestResult === false)
            return [0, 0];
        $requestResult = json_decode($requestResult, true);
        if (empty($requestResult))
            return [0, 0];
        if ($requestResult['RespCode'] != '1111')
            return [0, 0];
        $verifySign = $this->buildSignMD5($requestResult);
        if ($verifySign != $requestResult['Sign'])
            return [0, 0];
        //check sign
        return [decimalsToInt($requestResult['Balance'], 2), decimalsToInt($requestResult['CanPayMoney'], 2)];
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