<?php

namespace app\api\model;

class YbPayV1Model
{
    private $appID;
    private $appKey;
    private $username;
    private $gateway;

    /**
     * YbPayV1Model constructor.
     */
    public function __construct()
    {
        $this->appID    = env('YB_APP_ID');
        $this->appKey   = env('YB_APP_KEY');
        $this->username = env('YB_USER_NAME');
        $this->gateway  = env('YB_GATEWAY');
    }

    /**
     * 获取聚合支付链接
     * @param string $tradeNo
     * @param string $money
     * @return string|null
     */
    public function getQrCode(string $tradeNo, string $money)
    {
        if (empty($tradeNo) || empty($money))
            return null;
        $url           = $this->gateway . 'api/index/qrcode';
        $requestResult = $this->requestApi($url, ['order_num' => $tradeNo, 'money' => $money]);
        if ($requestResult === false)
            return null;
        $jsonArr = json_decode($requestResult, true);
        if (empty($jsonArr['code']))
            return null;
        if ($jsonArr['code'] != 200)
            return null;
        if (empty($jsonArr['data'])) {
            trace('[YB]' . $jsonArr['msg'], 'info');
            return null;
        }
        return $jsonArr['data']['url'];
    }

    /**
     * 查询订单是否支付
     * @param string $tradeNo
     * @return bool
     */
    public function isPay(string $tradeNo)
    {
        if (empty($tradeNo))
            return false;
        $url           = $this->gateway . 'api/index/getorderstate';
        $requestResult = $this->requestApi($url, ['order_num' => $tradeNo]);
        if ($requestResult === false)
            return false;
        $jsonArr = json_decode($requestResult, true);
        if (empty($jsonArr['code']) || empty($jsonArr['msg']))
            return false;
        if ($jsonArr['code'] != 200)
            return false;
        return true;
    }

    /**
     * 获取token
     * @param $createTime
     * @return string
     */
    public function getToken($createTime)
    {
        $param = [
            'appid'    => $this->appID,
            'username' => $this->username,
            'time'     => $createTime
        ];
        $sign  = md5(createLinkString($param) . $this->appKey);
        return $sign;
    }

    /**
     * 包装请求函数
     * @param string $url
     * @param array $param
     * @return bool|string
     */
    private function requestApi(string $url, array $param)
    {
        $time              = time();
        $param['sign']     = $this->getToken($time);
        $param['time']     = $time;
        $param['username'] = $this->username;
        $param['APPID']    = $this->appID;
        return curl($url, [], 'post', $param);
    }

}
