<?php

namespace app\pay\controller;


use app\api\model\TwPayV1Model;
use think\Controller;
use think\Db;

class TwApiV1 extends Controller
{
    public function getNotify()
    {
        $tradeNoOut = input('get.orderid/s');
        $status     = input('get.opstate/s');
        $money      = input('get.ovalue/s');
        $sign       = input('get.sign/s');

        $twPayV1Model = new TwPayV1Model();
        $verifySign   = $twPayV1Model->buildSignMD5([
            'orderid' => $tradeNoOut,
            'opstate' => $status,
            'ovalue'  => $money
        ]);
        if ($verifySign != $sign)
            return 'opstate=-2';
        //签名有误
        if ($status != '0')
            return '订单尚未支付1';
        //尚未支付
        if (!$twPayV1Model->isPay($tradeNoOut))
            return '订单尚未支付2';
        //尚未支付
        $orderData = Db::name('order')->where('id', $tradeNoOut)->field('status,payAisle,money')->limit(1)->select();
        if (empty($orderData))
            return '订单数据不存在';
        if ($orderData[0]['status'])
            return 'opstate=0';
        if ($orderData[0]['payAisle'] != 6)
            return '您在操作什么呢？';
        if (decimalsToInt($money, 2) != $orderData[0]['money'])
            return '订单金额有误';
        $updateOrder = Db::name('order')->where('id', $tradeNoOut)->limit(1)->update([
            'endTime' => getDateTime(),
            'status'  => 1
        ]);
        if (!$updateOrder) {
            trace('[TwApiV1] 更新订单失败 tradeNoOut => ' . $tradeNoOut, 'error');
            return '订单更新有误';
        }
        processOrder($tradeNoOut);

        return 'opstate=0';
    }

    public function getReturn()
    {
        $tradeNoOut = input('get.orderid/s');
        if (empty($tradeNoOut))
            return '<h1 style="text-align: center;padding-top: 10rem;">无效订单ID</h1>';
        $returnUrl = buildReturnOrderUrl($tradeNoOut);
        if (empty($returnUrl))
            return '<h1 style="text-align: center;padding-top: 10rem;">订单尚未支付</h1>';
        return redirect($returnUrl, [], 302);
    }
}