<?php

namespace app\pay\controller;

use app\api\model\EebPayV1Model;
use think\Controller;
use think\Db;

class EebApiV1 extends Controller
{

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function getNotify()
    {
        $responseCode = input('get.RespCode/s');
        $status       = input('get.Status/s');
        $tradeNoOut   = input('get.TradeNum/s');
        $tradeNo      = input('get.OrderNum/s');
        $payType      = input('get.PayType/s');
        $money        = input('get.Amount/s');
        $signType     = input('get.SignType/s');
        $sign         = input('get.Sign/s');

        if (empty($responseCode) || empty($status) || empty($tradeNo) || empty($tradeNoOut) || empty($sign))
            return 'FAIL 请求参数有误';

        if ($signType != 'MD5')
            return 'FAIL 签名方式有误';

        $ebbPayModel = new EebPayV1Model();
        $verifySign  = $ebbPayModel->buildSignMD5(input('get.'));
        if ($verifySign != $sign)
            return 'FAIL 签名有误';

        if ($status != '01')
            return 'FAIL 订单尚未支付[1]';

        if (!$ebbPayModel->isPay($tradeNoOut))
            return 'FAIL 订单尚未支付[2]';

        $orderData = Db::name('order')->where('id', $tradeNoOut)->field('status,payAisle,money')->limit(1)->select();
        if (empty($orderData))
            return 'FAIL 数据不存在';
        if ($orderData[0]['status'])
            return 'SUCCESS';
        if ($orderData[0]['payAisle'] != 5)
            return 'FAIL 您在操作什么呢？';
        if (decimalsToInt($money, 2) != $orderData[0]['money'])
            return 'FAIL 订单金额有误';
        $updateOrder = Db::name('order')->where('id', $tradeNoOut)->limit(1)->update([
            'endTime' => getDateTime(),
            'status'  => 1
        ]);
        if (!$updateOrder) {
            trace('[EebApiV1] 更新订单失败 tradeNoOut => ' . $tradeNoOut, 'error');
            return 'FAIL 更新订单状态有误';
        }
        processOrder($tradeNoOut);

        return 'SUCCESS';
    }

    public function getSettleNotify()
    {
        $responseCode = input('get.RespCode/s');
        $status       = input('get.Status/s');
        $settleNoOut  = input('get.TradeNum/s');
        $settleNo     = input('get.OrderNum/s');
        $signType     = input('get.SignType/s');
        $sign         = input('get.Sign/s');

        if (empty($responseCode) || empty($status) || empty($settleNo) || empty($settleNoOut) || empty($signType) || empty($sign))
            return 'FAIL 请求参数有误';
        if ($signType != 'MD5')
            return 'FAIL 签名方式有误';
        if ($responseCode != '1111')
            return 'FAIL 请求状态有误';
        $ebbPayModel = new EebPayV1Model();
        $verifySign  = $ebbPayModel->buildSignMD5(input('get.'));
        if ($verifySign != $sign)
            return 'FAIL 签名有误';

        switch ($status) {
            case '01':
                $status = 2;
                break;
            case '02':
                $status = 1;
                break;
            case '03':
                $status = 3;
                break;
            default:
                $status = 1;
                break;
        }

        $updateSettle = Db::name('settle')
            ->where('settleNo=:settleNo', ['settleNo' => $settleNo])
            ->where('settleAisle', 5)->limit(1)->update([
            'updateTime' => getDateTime(),
            'status'     => $status
        ]);

        if ($updateSettle)
            return 'SUCCESS';
        return 'FAIL 更新状态异常';
    }
}