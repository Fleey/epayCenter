<?php

namespace app\api\model;

class XaPayV1Model
{
    private $appID;
    private $appKey;
    private $dfAppID;
    private $dfAppKey;
    private $gateway;

    /**
     * XaPayV1Model constructor.
     */
    public function __construct()
    {
        $this->appID    = env('XA_APP_ID');
        $this->appKey   = env('XA_APP_KEY');
        $this->dfAppID  = env('XA_DF_APP_ID');
        $this->dfAppKey = env('XA_DF_APP_KEY');
        $this->gateway  = env('XA_GATEWAY');
    }


    /**
     * 获取支付链接
     * @param string $tradeNo
     * @param string $money
     * @param string $productName
     * @param string $notifyUrl
     * @param string $callBackUrl
     * @return array
     */
    public function getPayUrl(string $tradeNo, string $money, string $productName, string $notifyUrl, string $callBackUrl = '')
    {
        $param = [
            'sp_id'        => substr($this->appID, 0, 4),
            'mch_id'       => $this->appID,
            'out_trade_no' => $tradeNo,
            'total_fee'    => decimalsToInt($money, 2),
            'body'         => $productName,
            'notify_url'   => $notifyUrl,
            'nonce_str'    => getRandChar(16)
        ];
//        if (!empty($callBackUrl))
//            $param['callback_url'] = $callBackUrl;

        $param['sign'] = $this->buildSignMD5($param);
        $requestResult = curl($this->gateway . '/kakaloan/quick/cashierOrder', [], 'post', $param, '', false);
        if ($requestResult === false)
            return ['isSuccess' => false, 'msg' => '[epayCenter] Request Pay Url error 10001'];
        $requestResult = json_decode($requestResult, true);
        if ($requestResult['status'] == 'SUCCESS') {
            if (strtoupper($this->buildSignMD5($requestResult)) != $requestResult['sign'])
                return ['isSuccess' => false, 'msg' => '[epayCenter] Result sign verify error 10002'];
        }
        if (empty($requestResult['ret_url']))
            return ['isSuccess' => false, 'msg' => '[epayCenter] Request result error,pls connect administrator'];

        return ['isSuccess' => true, 'url' => $requestResult['ret_url']];
    }

    /**
     * 查询订单状态
     * @param string $tradeNo
     * @return bool
     */
    public function isPay(string $tradeNo)
    {
        $param         = [
            'sp_id'        => substr($this->appID, 0, 4),
            'mch_id'       => $this->appID,
            'out_trade_no' => $tradeNo,
            'nonce_str'    => getRandChar(16)
        ];
        $param['sign'] = $this->buildSignMD5($param);

        $requestResult = curl($this->gateway . '/kakaloan/api/quickpay/ordertype', [], 'post', $param, '', false);
        if ($requestResult === false)
            return false;
        $requestResult = json_decode($requestResult, true);
        if ($requestResult['status'] != 'SUCCESS') {
            if (strtoupper($this->buildSignMD5($requestResult)) != $requestResult['sign'])
                return false;
        }
        return $requestResult['trade_state'] == 'SUCCESS';
    }


    /**
     * 签名参数
     * @param array $param
     * @return string
     */
    public function buildSignMD5(array $param)
    {
        $str1 = createLinkString(argSort(paraFilter($param, false)));
        $str1 = md5($str1 . '&key=' . $this->appKey);
        $str1 = strtoupper($str1);
        return $str1;
    }
}