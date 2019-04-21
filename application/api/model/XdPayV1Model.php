<?php

namespace app\api\model;

class XdPayV1Model
{
    public $appID;
    public $appKey;
    public $gateway;

    /**
     * XdPayV1Model constructor.
     */
    public function __construct()
    {
        $this->appID   = env('XD_APP_ID');
        $this->appKey  = env('XD_APP_KEY');
        $this->gateway = env('XD_GATEWAY');
    }

    /**
     * @param $tradeNo
     * @param $money //分为单位
     * @param $notifyUrl
     * @param $returnUrl
     * @return array
     */
    public function getPayUrlAliH5($tradeNo, $money, $notifyUrl, $returnUrl)
    {
        $requestData         = [
            'unique_id'    => $this->appID,
            'price'        => $money,
            'order_number' => $tradeNo,
            'notice_url'   => $notifyUrl,
            'return_url'   => $returnUrl,
        ];
        $requestData['sign'] = $this->buildSign($requestData);
        $requestResult       = curl($this->gateway . '/PayView/Index/alipayH5Pay.html', [], 'post', $requestData, '', false);
        if ($requestResult === false)
            return ['isSuccess' => false, 'msg' => '[epayCenter] Request Pay Url error 10001'];
        $requestResult = json_decode($requestResult, true);
        if ($requestResult['code'] != 200) {
            trace('[XD]' . $requestResult['msg'], 'info');
            return ['isSuccess' => false, 'msg' => '[epayCenter] get pay url error pls connect web admin'];
        }
        if (empty($requestResult['data']))
            return ['isSuccess' => false, 'msg' => '[epayCenter] Request result error,pls connect administrator'];

        return ['isSuccess' => true, 'url' => $requestResult['data']];
    }

    /**
     * @param string $tradeNo
     * @param string $price //分为单位
     * @return bool
     */
    public function isPay(string $tradeNo, $price)
    {
        $requestData         = [
            'unique_id' => $this->appID,
            'order_id'  => $tradeNo,
            'price'     => ($price / 100)
        ];
        $requestData['sign'] = $this->buildSign($requestData);

        $requestResult = curl($this->gateway . '/PayView/Index/OrderQuery.html', [], 'post', $requestData, '', false);
        if ($requestResult === false)
            return false;
        $jsonArr = json_decode($requestResult, true);
        if ($jsonArr['code'] != 200)
            return false;
        if (empty($jsonArr['data']['ispay']))
            return false;
        return $jsonArr['data']['ispay'] == '1';
    }

    /**
     * @param $data
     * @return string
     */
    public function buildSign($data)
    {
        ksort($data);
        $sign = urldecode(http_build_query($data)) . '&key=' . $this->appKey;
        return md5($sign . $this->appKey);
    }
}