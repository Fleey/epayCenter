<?php

namespace app\pay\controller;

use app\api\model\YbPayV1Model;
use think\Db;

class YbApiV1
{
    public function postNotify()
    {
        $tradeNo         = input('post.order_num/s');
        $money           = input('post.money/s');
        $status          = input('post.status/d');
        $orderCreateTime = input('post.create_time/s');
        $user_order      = input('post.user_order/s');
        $time            = input('post.time/s');
        $sign            = input('post.sign/s');

        if (empty($tradeNo) || empty($money) || empty($status) || empty($orderCreateTime) || empty($user_order) || empty($time) || empty($sign))
            return json(['status' => 0, 'msg' => '参数不能为空']);

        $YbPayV1Model = new YbPayV1Model();
        if (!$YbPayV1Model->getToken($time) != $sign)
            return json(['status' => 0, 'msg' => '签名验证失败']);

        $tradeNoOut = explode('-', $user_order);
        if (count($tradeNoOut) != 2)
            return json(['status' => 0, 'msg' => '参数有误']);
        $tradeNoOut = $tradeNoOut[1];
        $result     = Db::name('order')->where('id', $tradeNoOut)->limit(1)->field('money,status')->select();
        if (empty($result))
            return json(['status' => 0, 'msg' => '订单不存在']);
        if ($result[0]['status'])
            return json(['status' => 0, 'msg' => '订单已经支付']);

        if (!$YbPayV1Model->isPay($tradeNo))
            return json(['status' => 0, 'msg' => '校验支付状态有误']);

        if (decimalsToInt($money, 2) != $result[0]['money'])
            return json(['status' => 0, 'msg' => '订单金额不一致']);
        Db::name('order')->where('id', $tradeNoOut)->limit(1)->update([
            'status'  => 1,
            'endTime' => getDateTime()
        ]);
        processOrder($tradeNoOut);
        return json(['status' => 1, 'msg' => '更新订单状态成功']);
    }
    
}