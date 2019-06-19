<?php

namespace app\api\model;

class KyxV1Model
{
    private $appID;
    private $appKey;
    private $gateway;
    private $sendEmail;

    public function __construct()
    {
        $this->appID   = env('KYX_APP_ID');
        $this->appKey  = env('KYX_APP_KET');
        $this->gateway = env('KYX_GATEWAY');
        $this->sendEmail = env('KYX_SEND_EMAIL');
    }

    /**
     * @param string $tradeNo
     * @param string $money //元为单位精确到两位小数
     * @param string $productName
     * @param string $notifyUrl
     * @param string $returnUrl
     * @return array
     */
    public function getPayUrl(string $tradeNo, string $money, string $productName, string $notifyUrl = '', string $returnUrl = ''){
        $requestUrl = $this->gateway.'/gateway.html';
        $requestDomain = request()->root(true);
        $param = [
            'partner'=>$this->appID,
            '_input_charset'=>'utf-8',
            'website_url'=>$requestDomain,
            'out_trade_no'=>$tradeNo,
            'subject'=>$productName,
            'seller_email'=>$this->sendEmail,
            'total_fee'=>$money,
            'body'=>'一个商品-'.md5(time()),
        ];
        if(!empty($notifyUrl))
            $param['notify_url'] = $notifyUrl;
        if(!empty($returnUrl))
            $param['return_url'] = $returnUrl;

        $param['sign'] = $this->buildSign($param);
        $param['sign_type'] = 'MD5';

        $requestResult = curl($requestUrl, [
            'Referer: '.$requestDomain,
            'User-Agent: '.Request::header('user-agent')
        ], 'post', $param, '', false);
        return ['isSuccess'=>true,'html'=>$requestResult];
    }

    /**
     * 查询是否支付
     * @param $tradeNo
     * @return bool
     */
    public function isPay($tradeNo)
    {
        $requestUrl         = $this->gateway . '/query.html';
        $param              = [
            'partner'        => $this->appID,
            '_input_charset' => 'utf-8',
            'out_trade_no'   => $tradeNo
        ];
        $param['sign']      = $this->buildSign($param);
        $param['sign_type'] = 'MD5';

        $requestResult = curl($requestUrl, [], 'post', $param, '', false);
        if(empty($requestResult))
            return false;
        $requestResult = json_decode(xmlToArray($requestResult)['body'],true);
        if(empty($requestResult))
            return false;
        if($requestResult['code'] != '0')
            return false;
        return $requestResult['result']['status'] == '0';
    }

    /**
     * 签名字符串
     * @param array
     * @return string
     */
    public function buildSign(array $param)
    {
        return md5(createLinkString(argSort($param), false) . $this->appKey);
    }


    /**
     * @param string $requestUrl
     * @param array $param
     * @param string $method
     * @param string $button_name
     * @return string
     */
    public function buildRequestForm(string $requestUrl, array $param, string $method, string $button_name = '') {
        //待请求参数数组
        $sHtml = '<form id=\'alipaysubmit\' name=\'alipaysubmit\' action=\''.$requestUrl.'\' method=\''.$method.'\'>';
        foreach ($param as $key =>$value){
            $sHtml.='<input type=\'hidden\' name=\''.$key.'\' value=\''.$value.'\'/>';
        }
        $sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";
        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
        return $sHtml;
    }
}