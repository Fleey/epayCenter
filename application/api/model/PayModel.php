<?php

namespace app\api\model;

class PayModel
{
    public static $apiList = [
        [
            [
                'name'  => 'ow51pay',
                'aisle' => 3
            ],
            [
                'name'  => '元宝聚合支付',
                'aisle' => 1
            ]
            //微信支付
        ], [

            //QQ钱包支付
        ], [
            [
                'name'  => 'ow51pay',
                'aisle' => 3
            ],
            [
                'name'  => '现代聚合支付',
                'aisle' => 4
            ]
            //支付宝支付
        ], [
            [
                'name'  => 'XTXA快捷支付',
                'aisle' => 2
            ]
            //银联支付
        ]
    ];

    /**
     * 判断是否存在支付类型接口 存在true 不存在 false
     * @param int $payType
     * @param int $payAisle
     * @return bool
     */
    public static function isExistPayApi(int $payType, int $payAisle)
    {
        $payType = $payType - 1;
        if (empty(self::$apiList[$payType]))
            return false;
        foreach (self::$apiList[$payType] as $data) {
            if ($data['aisle'] == $payAisle)
                return true;
        }
        return false;
    }

    /**
     * @param string $tradeNo
     * @param string $money //单位 为 元 小数
     * @param int $payType
     * @param int $payAisle
     * @param string $productName
     * @return array|string|null
     * @throws \think\Exception
     */
    public static function buildPayData(string $tradeNo, string $money, int $payType, int $payAisle, string $productName = '')
    {
        if (!self::isExistPayApi($payType, $payAisle))
            return ['isSuccess' => false, 'msg' => '支付类型接口不存在'];

        if (empty($productName))
            $productName = env('DEFAULT_PRODUCT_NAME');

        $requestResult = [
            'isSuccess' => false
        ];

        if ($payAisle == 1) {
            $ybPayModel = new YbPayV1Model();
            if ($payType == 1) {
                $requestResult = $ybPayModel->getPayUrl($tradeNo, $money);
            }
        } else if ($payAisle == 2) {
            $xaPayModel = new XaPayV1Model();
            if ($payType == 4) {
                $requestResult = $xaPayModel->getPayUrl($tradeNo, $money, $productName,
                    url('/Pay/Xa/Notify', '', false, true),
                    url('/Pay/Xa/Return', '', false, true));
            }
        } else if ($payAisle == 3) {
            $owPayModel  = new OwPayV1Model();
            $payTypeName = 'none';
            if ($payType == 1) {
                $payTypeName = 'WxH5';
            } else if ($payType == 3) {
                $payTypeName = 'AliH5';
            }
            $requestResult = $owPayModel->getPayUrl($payTypeName, $tradeNo, $money, $productName,
                url('/Pay/Ow/Notify', '', false, true),
                url('/Pay/Ow/Return', '', false, true));
        } else if ($payAisle == 4) {
            $xdPayModel = new XdPayV1Model();
            if ($payType == 3) {
                if (intval($money < 100)) {
                    $requestResult['msg'] = '[XD] 订单金额不能低于 100 RMB';
                    return $requestResult;
                }
                $requestResult = $xdPayModel->getPayUrlAliH5($tradeNo, $money * 100,
                    url('/Pay/Xd/Notify', '', false, true),
                    url('/Pay/Xd/Return', '', false, true));
            }
        } else {
            $requestResult['msg'] = '[EpayCenter] 支付类型接口不存在';
        }
        return $requestResult;
    }
}