<?php

namespace app\pay\controller;

use app\api\model\KyxV1Model;
use think\Controller;
use think\Db;

class KyxApiV1 extends Controller
{
    public function postReturn()
    {
        $signType = input('post.sign_type/s');
        $sign     = input('post.sign/s');

        $tradeNoOut  = input('post.out_trade_no/s');
        $tradeStatus = input('post.trade_status/s');
        $money       = input('post.total_fee/s');

        if (empty($sign) || empty($signType) || empty($tradeNoOut) || empty($tradeStatus) || empty($money))
            return '<h1 style="text-align: center;padding-top: 10rem;">参数无效</h1>';

        if ($tradeStatus != 'TRADE_SUCCESS')
            return '<h1 style="text-align: center;padding-top: 10rem;">订单尚未支付</h1>';

        $signParam = input('post.');
        unset($signParam['sign']);
        unset($signParam['sign_type']);
        $kyxModel = new KyxV1Model();
        if ($kyxModel->buildSign($signParam) != $sign)
            return '<h1 style="text-align: center;padding-top: 10rem;">签名无效</h1>';

        $returnUrl = buildReturnOrderUrl($tradeNoOut);
        if (empty($returnUrl))
            return '<h1 style="text-align: center;padding-top: 10rem;">订单尚未支付</h1>';
        return redirect($returnUrl, [], 302);
    }

    public function postNotify()
    {
        $signType = input('post.sign_type/s');
        $sign     = input('post.sign/s');

        $tradeNoOut  = input('post.out_trade_no/s');
        $tradeStatus = input('post.trade_status/s');
        $money       = input('post.total_fee/s');

        if (empty($sign) || empty($signType) || empty($tradeNoOut) || empty($tradeStatus) || empty($money))
            return 'fail';

        if ($tradeStatus != 'TRADE_SUCCESS')
            return 'fail';

        $signParam = input('post.');
        unset($signParam['sign']);
        unset($signParam['sign_type']);
        $kyxModel = new KyxV1Model();
        if ($kyxModel->buildSign($signParam) != $sign)
            return 'sign fail';

        $orderData = Db::name('order')->where('id', $tradeNoOut)->field('status,payAisle,money')->limit(1)->select();
        if (empty($orderData))
            return 'out order fail';
        if(!$kyxModel->isPay($tradeNoOut))
            return 'trade unpaid fail';
        if ($orderData[0]['status'])
            return 'success';
        if ($orderData[0]['payAisle'] != 8)
            return 'aisle fail';
        if (decimalsToInt($money, 2) != $orderData[0]['money'])
            return 'money fail';

        $updateOrder = Db::name('order')->where('id', $tradeNoOut)->limit(1)->update([
            'endTime' => getDateTime(),
            'status'  => 1
        ]);

        //更新订单状态
        if (!$updateOrder) {
            trace('[KyxApiV1] 更新订单失败 tradeNoOut => ' . $tradeNoOut, 'error');
            return 'update order fail';
        }
        processOrder($tradeNoOut);
        //判断订单更新状态是否失败
        return 'success';
    }
}