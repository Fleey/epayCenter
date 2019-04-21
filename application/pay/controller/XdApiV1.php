<?php

namespace app\pay\controller;

use app\api\model\XdPayV1Model;
use think\Db;

class XdApiV1
{
    public function postNotify()
    {
        $tradeNoOut = input('post.orderNum/s');
        $status     = input('post.status/s');
        $totalMoney = input('post.price/d');
        $sign       = input('post.sign/s');

        if (empty($tradeNoOut) || empty($status) || empty($totalMoney))
            return 'FAIL param is empty';
        if ($status != '1')
            return 'FAIL status error';

        $XdPayV1Model = new XdPayV1Model();
        $signVerify   = $XdPayV1Model->appKey . $totalMoney . $tradeNoOut;
        $signVerify   = md5($signVerify);
        if ($sign != $signVerify)
            return 'FAIL sign error';

        $orderData = Db::name('order')->where([
            'id' => $tradeNoOut
        ])->limit(1)->field('payAisle,money,status')->select();

        if (empty($orderData))
            return 'FAIL order not found';
        if ($orderData[0]['status'])
            return 'SUCCESS';
        if ($orderData[0]['payAisle'] != 4)
            return 'FAIL order pay type error';

        if (!$XdPayV1Model->isPay($tradeNoOut, $orderData[0]['money']))
            return 'FAIL order is Unpaid';
        Db::name('order')->where('id', $tradeNoOut)->limit(1)->update([
            'status'  => 1,
            'endTime' => getDateTime()
        ]);
        processOrder($tradeNoOut);
        return 'SUCCESS';
    }

    public function getReturn()
    {
        return '<h1 style="text-align: center;padding-top: 10rem;">请稍后，正在为您转跳中。。。</h1>';
    }
}