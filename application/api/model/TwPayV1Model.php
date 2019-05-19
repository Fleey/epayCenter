<?php

namespace app\api\model;

class TwPayV1Model
{
    private $appID;
    private $appKey;
    private $gateway;

    public function __construct()
    {
        $this->appID   = env('TW_APP_ID');
        $this->appKey  = env('TW_APP_KEY');
        $this->gateway = env('TW_GATEWAY');
    }

    /**
     * @param string $tradeNo
     * @param string $money //注意单位为元 保留两位小数
     * @param $payType
     * @param string $notifyUrl
     * @param string $callBackUrl
     * @return array
     */
    public function getPayUrl(string $tradeNo, string $money, $payType, string $notifyUrl, string $callBackUrl = '')
    {
        $requestUrl    = $this->gateway . '/bank?';
        $param         = [
            'parter'      => $this->appID,
            'type'        => $this->converPayID($payType),
            'value'       => $money,
            'orderid'     => $tradeNo,
            'callbackurl' => $notifyUrl
        ];
        $param['sign'] = $this->buildSignMD5($param);
        if (!empty($callBackUrl))
            $param['hrefbackurl'] = $callBackUrl;
        return ['isSuccess' => true, 'url' => ($requestUrl . createLinkString($param, true))];
    }

    public function isPay(string $tradeNo)
    {
        if (empty($tradeNo))
            return false;
        $requestUrl    = $this->gateway . '/search.ashx';
        $param         = [
            'orderid' => $tradeNo,
            'parter'  => $this->appID
        ];
        $param['sign'] = $this->buildSignMD5($param);

        $requestResult = curl($requestUrl, [], 'get', $param, '', false);
        parse_str($requestResult, $requestResult);

        $verifySign = $this->buildSignMD5([
            'orderid' => $requestResult['orderid'],
            'opstate' => $requestResult['opstate'],
            'ovalue'  => $requestResult['ovalue']
        ]);

        if ($verifySign != $requestResult['sign'])
            return false;

        return $requestResult['opstate'] == '0';
    }

    /**
     * @param $type
     * @return int
     */
    private function converPayID($type)
    {
        $isMobile = request()->isMobile();
        if ($type == 'alipay') {
            if ($isMobile)
                return 1006;
            return 1003;
        }
        return 0000;
    }

    /**
     * 构建签名
     * @param array $param
     * @return string
     */
    public function buildSignMD5(array $param)
    {
        return md5(createLinkString($param, false) . $this->appKey);
    }
}