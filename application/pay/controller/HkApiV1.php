<?php

namespace app\pay\controller;

use think\Controller;
use think\Db;

class HkApiV1 extends Controller
{
    public function getWeChatPay()
    {
        $orderID     = input('get.orderID/d');
        $requestTime = input('get.time/d');
        $sign        = input('get.sign/s');
        if (empty($orderID))
            return $this->fetch('/SystemMessage', ['msg' => '订单号码无效']);
        if (empty($requestTime))
            return $this->fetch('/SystemMessage', ['msg' => '参数无效']);
        if (strlen($sign) != 32)
            return $this->fetch('/SystemMessage', ['msg' => '签名无效']);
        $verifySign = md5($orderID . $requestTime . 'huaji233');
        if ($verifySign != $sign)
            return $this->fetch('/SystemMessage', ['msg' => '签名无效']);
        if ((time() - $requestTime) > 360)
            return $this->fetch('/SystemMessage', ['msg' => '订单超时请重新发起']);

        $orderInfo = Db::name('order')->where('id', $orderID)->field('tradeNoOut,money,payType,payAisle,status,createTime')->limit(1)->select();
        if (empty($orderInfo))
            return $this->fetch('/SystemMessage', ['msg' => '订单不存在，请联系客服处理']);
        if ($orderInfo[0]['status'])
            return redirect(buildReturnOrderUrl($orderID));
        if ($orderInfo[0]['payAisle'] != 7 || $orderInfo[0]['payType'] != 2)
            return $this->fetch('/SystemMessage', ['msg' => '订单类型不支持，请联系客服处理']);
        return $this->fetch('/WxPayPcTemplate', [
            'siteName'    => '易天聚合支付',
            'productName' => '商品支付-' . uniqid(),
            'money'       => $orderInfo[0]['money'] / 100,
            'tradeNo'     => $orderInfo[0]['tradeNoOut'],
            'addTime'     => $orderInfo[0]['createTime'],
            'orderID'     => $orderID,
            'time'        => $requestTime,
            'sign'        => $sign
        ]);
    }
}