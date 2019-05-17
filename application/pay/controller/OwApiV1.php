<?php

namespace app\pay\controller;

use app\api\model\OwPayV1Model;
use think\Controller;
use think\Db;

class OwApiV1 extends Controller
{
    /**
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function getNotify()
    {
        $merchantid = input('get.merchantid/s');
        $tradeNoOut = input('get.merchantorderno/s');
        $tradeNo    = input('get.orderno/s');
        $payStatus  = input('get.paystatus/d');
        $payType    = input('get.paytype/d');
        $totalMoney = input('get.amount/s');
        $sign       = input('get.sign/s');

        if ($payType == 1 || $payType == 10) {
            $payType = 'AliH5';
        } else if ($payType == 7) {
            $payType = 'WxH5';
        } else {
            $payType = 'none';
        }

        $OwPayV1Model = new OwPayV1Model();
        $buildSign    = $OwPayV1Model->buildSignMD5(input('get.'), $payType);
        if ($buildSign != $sign)
            return json(['Status' => 'F', 'BackTime' => getDateTime()]);
        //签名不正确
        if ($payStatus != 1)
            return json(['Status' => 'F', 'BackTime' => getDateTime()]);
        //回调的支付状态不为成功
        $orderData = Db::name('order')->where('id', $tradeNoOut)->field('status,payAisle,money')->limit(1)->select();
        if (empty($orderData))
            return json(['Status' => 'F', 'BackTime' => getDateTime()]);
        //查无订单
        if ($orderData[0]['status'])
            return json(['Status' => 'T', 'BackTime' => getDateTime()]);
        //订单状态已经为支付
        if ($orderData[0]['payAisle'] != 3)
            return json(['Status' => 'F', 'BackTime' => getDateTime()]);
        //订单类型不一致 无法回调改变状态
        if (decimalsToInt($totalMoney, 2) != $orderData[0]['money'])
            return json(['Status' => 'F', 'BackTime' => getDateTime()]);
        //订单金额不一致

        $updateOrder = Db::name('order')->where('id', $tradeNoOut)->limit(1)->update([
            'endTime' => getDateTime(),
            'status'  => 1
        ]);
        //更新订单状态
        if (!$updateOrder) {
            trace('[OwApiV1] 更新订单失败 tradeNoOut => ' . $tradeNoOut, 'error');
            return json(['Status' => 'F', 'BackTime' => getDateTime()]);
        }
        processOrder($tradeNoOut);
        //判断订单更新状态是否失败
        return json(['Status' => 'T', 'BackTime' => getDateTime()]);
    }

    /**
     * @return string|\think\response\Redirect
     */
    public function getReturn()
    {
        $tradeNoOut = input('get.merchantorderno/s');
        if (empty($tradeNoOut))
            return '<h1 style="text-align: center;padding-top: 10rem;">无效订单ID</h1>';
        $returnUrl = buildReturnOrderUrl($tradeNoOut);
        if (empty($returnUrl))
            return '<h1 style="text-align: center;padding-top: 10rem;">订单尚未支付</h1>';
        return redirect($returnUrl, [], 302);
    }
}