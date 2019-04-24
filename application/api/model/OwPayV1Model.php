<?php

namespace app\api\model;

use think\Db;
use think\Exception;

class OwPayV1Model
{
    private $appIDWxH5;
    private $appKeyWxH5;
    private $appIDAliH5;
    private $appKeyAliH5;
    private $gateway;

    /**
     * XaPayV1Model constructor.
     */
    public function __construct()
    {
        $this->appIDAliH5  = env('OW_ALI_WAP_APP_ID');
        $this->appKeyAliH5 = env('OW_ALI_WAP_APP_KEY');
        $this->appIDWxH5   = env('OW_WX_H5_APP_ID');
        $this->appKeyWxH5  = env('OW_WX_H5_APP_KEY');
        $this->gateway     = env('OW_GATEWAY');
    }


    /**
     * @param string $payType
     * @param string $tradeNo
     * @param string $money
     * @param string $productName
     * @param string $notifyUrl
     * @param string $callBackUrl
     * @param string $productDesc
     * @return array
     * @throws Exception
     */
    public function getPayUrl(string $payType, string $tradeNo, string $money, string $productName,
                              string $notifyUrl, string $callBackUrl = '', string $productDesc = '默认商品描述')
    {
        $payTypeID = 0;
        $appID     = 'appID' . $payType;
        if (empty($this->$appID))
            return ['isSuccess' => false, 'msg' => '[OwPayV1Model] 参数为空或异常,请注意！！！'];

        switch ($payType) {
            case 'AliH5':
                $payTypeID = !request()->isMobile() ? 10 : 1;
                break;
            case 'WxH5':
                $payTypeID = 7;
                break;
        }
        $param = [
            'merchantid' => $this->$appID,
            'orderno'    => $tradeNo,
            'ordertime'  => date('yyyyMMddHHmmss'),
            'amount'     => $money,
            'notifyurl'  => $notifyUrl,
            'goodsname'  => $productName,
            'goodsdesc'  => $productDesc,
            'paytype'    => $payTypeID,
            'clientip'   => '127.0.0.1'
        ];
        if (!empty($callBackUrl))
            $param['pagebackurl'] = $callBackUrl;

        $param['sign'] = $this->buildSignMD5($param, $payType);

        return ['isSuccess' => true, 'url' => $this->gateway . '/DoPay.aspx?' . createLinkString($param, true)];

//        $requestResult = curl($this->gateway . '/DoPay.aspx', [], 'post', $param, '', false);
        if ($requestResult === false)
            return ['isSuccess' => false, 'msg' => '[epayCenter] Request Pay Url error 10001'];
        $requestResultJson = json_decode($requestResult, true);
        if ($requestResultJson === null)
            return ['isSuccess' => true, 'isHtml' => true, 'html' => $requestResult];
        if ($requestResultJson['Code'])
            return ['isSuccess' => false, 'msg' => '[epayCenter] OwPay error tips ' . htmlentities($requestResultJson['Message'])];
        return ['isSuccess' => true, 'url' => $requestResultJson['Order']['PayUrlOrCode']];
    }

    public function isPay(string $tradeNo)
    {
        $orderData = Db::name('order')->where([
            'id' => $tradeNo
        ])->field('payType,status,payAisle')->limit(1)->select();

        if (empty($orderData))
            return false;
        if ($orderData[0]['payAisle'] != 3)
            return false;
        if ($orderData[0]['status'])
            return true;

        $payType = 'none';
        if ($orderData[0]['payType'] == 1) {
            $payType = 'WxH5';
        } else if ($orderData[0]['payType'] == 3) {
            $payType = 'AliH5';
        }

        $appID = 'appID' . $payType;
        $param = [
            'orderno'    => $tradeNo,
            'merchantid' => $this->$appID
        ];

        $param['sign'] = $this->buildSignMD5($param, $payType);

        $requestResult = curl($this->gateway . '/Query.aspx', [], 'post', $param, '', false);
        if ($requestResult === false)
            return false;
        $requestResultJson = json_decode($requestResult, true);
        if ($requestResultJson === null)
            return false;
        if ($requestResultJson['Code'] != 0)
            return false;
        if ($requestResultJson['Order']['Status'] != 1)
            return false;
        return true;
    }

    /**
     * 构建签名
     * @param array $param
     * @param string $payType
     * @return string
     * @throws Exception
     */
    public function buildSignMD5(array $param, string $payType)
    {
        $tempArr = argSort(paraFilter($param, false));
        $str1    = '';
        foreach ($tempArr as $key => $value) {
            $str1 .= $key . $value;
        }
        $payType = 'appKey' . $payType;
        if (empty($this->$payType))
            throw new Exception('[OwPayV1Model] 参数为空或异常,请注意！！！');
        $str1 = md5($str1 . '' . $this->$payType);
        return $str1;
    }

}