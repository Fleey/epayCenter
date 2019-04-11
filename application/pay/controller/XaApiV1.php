<?php

namespace app\pay\controller;

use app\api\model\XaPayV1Model;
use think\Controller;
use think\Db;

class XaApiV1 extends Controller
{
    /**
     * 订单回调事件
     */
    public function postNotify()
    {
        $status     = input('post.status/s');
        $tradeNoOut = input('post.out_trade_no/s');
        $tradeNo    = input('post.trade_no/s');
        $totalMoney = input('post.total_fee/d');
        //注意 这里请求的订单金额为int 并且单位为分位
        $tradeStatus  = input('post.trade_state/s');
        $tradeMessage = input('post.message/s');
        $nonceStr     = input('post.nonce_str/s');
        $sign         = input('post.sign/s');

        if (empty($status) || empty($tradeNoOut) || empty($tradeNo) || empty($totalMoney) || empty($tradeStatus))
            return 'Param empty';
        //check param
        $XaPayV1Model = new XaPayV1Model();
        if ($XaPayV1Model->buildSignMD5(input('post.')) != $sign)
            return 'Sign error';
        //check sign
        $result = Db::name('order')->where('id', $tradeNoOut)->limit(1)->field('money,payAisle,status')->select();
        if (empty($result))
            return 'SUCCESS';
        //这个订单为空 可能是漏单了 也有可能无效单号
        if ($result[0]['status'])
            return 'SUCCESS';
        if ($result[0]['payAisle'] != 2)
            return 'Notify Status Error';
        if (!$XaPayV1Model->isPay($tradeNoOut))
            return 'Check Pay Status Error';
        //查询远程支付状态有误
        if ($totalMoney != $result[0]['money'])
            return 'Pay Money Error';

        $updateResult = Db::name('order')->where('id', $tradeNoOut)->limit(1)->update([
            'status'  => 1,
            'endTime' => getDateTime()
        ]);
        if (!$updateResult)
            trace('订单更新失败 tradeNo => ' . $tradeNoOut, 'error');
        processOrder($tradeNoOut);
        return 'SUCCESS';
    }

    public function getReturn()
    {
        $tradeNoOut = input('get.tradeNoOut/s');
        if (empty($tradeNoOut))
            return '<h1 style="text-align: center;padding-top: 10rem;">无效订单ID</h1>';
        $returnUrl = buildReturnOrderUrl($tradeNoOut);
        if (empty($returnUrl))
            return '<h1 style="text-align: center;padding-top: 10rem;">订单尚未支付</h1>';
        return redirect($returnUrl, [], 302);
    }
}