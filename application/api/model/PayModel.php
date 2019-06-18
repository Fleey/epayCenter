<?php

namespace app\api\model;

use think\Db;

class PayModel
{
    const apiList = [
        [
            ['name' => 'ow51pay', 'aisle' => 3],
            ['name' => '元宝聚合支付', 'aisle' => 1],
            ['name' => 'Hook支付', 'aisle' => 7]
            //微信支付
        ], [
            //QQ钱包支付
        ], [
            ['name' => 'ow51pay', 'aisle' => 3],
            ['name' => '现代聚合支付', 'aisle' => 4],
            ['name' => 'EEB原声聚合支付', 'aisle' => 5],
            ['name' => 'Tw天网聚合支付', 'aisle' => 6],
            ['name'=>'KYX卡易信聚合支付','aisle'=>8]
            //支付宝支付
        ], [
            ['name' => 'XTXA快捷支付', 'aisle' => 2],
            ['name' => 'Tw天网聚合支付', 'aisle' => 6]
            //银联支付
        ]
    ];

    const bankList = [
        ['name' => '工商银行', 'code' => 'ICBC'],
        ['name' => '农业银行', 'code' => 'ABC'],
        ['name' => '建设银行', 'code' => 'CCB'],
        ['name' => '交通银行', 'code' => 'BOCO'],
        ['name' => '中国银行', 'code' => 'BOC'],
        ['name' => '招商银行', 'code' => 'CMBCHINA'],
        ['name' => '兴业银行', 'code' => 'CIB'],
        ['name' => '光大银行', 'code' => 'CEB'],
        ['name' => '民生银行', 'code' => 'CMBC'],
        ['name' => '浦发银行', 'code' => 'SPDB'],
        ['name' => '广发银行', 'code' => 'GDB'],
        ['name' => '邮政储蓄银行', 'code' => 'POST'],
        ['name' => '中信银行', 'code' => 'ECITIC'],
        ['name' => '华夏银行', 'code' => 'HXB'],
        ['name' => '平安银行', 'code' => 'PINGANBANK'],
        ['name' => '杭州银行', 'code' => 'HCCB'],
        ['name' => '上海银行', 'code' => 'SHRCB'],
        ['name' => '北京银行', 'code' => 'BOB']
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
        if (empty(self::apiList[$payType]))
            return false;
        foreach (self::apiList[$payType] as $data) {
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
            'isSuccess' => false,
            'msg'       => '不知道什么地方错误了，请联系管理员'
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
                if (intval($money) < 100) {
                    $requestResult['msg'] = '[XD] 订单金额不能低于 100 RMB';
                    return $requestResult;
                }
                $requestResult = $xdPayModel->getPayUrlAliH5($tradeNo, $money * 100,
                    url('/Pay/Xd/Notify', '', false, true),
                    url('/Pay/Xd/Return', '', false, true));
            }
        } else if ($payAisle == 5) {
            $eebPayModel = new EebPayV1Model();
            if ($payType == 3) {
                if (intval($money) < 100) {
                    $requestResult['msg'] = '[Eeb] 订单金额不能低于 100 RMB';
                    return $requestResult;
                }
                $payType       = 'alipay' . (request()->isMobile() ? 'wap' : '');
                $requestResult = $eebPayModel->getPayUrl($tradeNo, $money, $payType,
                    url('/Pay/Eeb/Notify', '', false, true),
                    url('/Pay/Eeb/Return', '', false, true));
            }
        } else if ($payAisle == 6) {
            $twPayModel = new TwPayV1Model();
            $payName    = 'none';
            if (intval($money) < 200) {
                $requestResult['msg'] = '[Tw] 订单金额不能低于 200 RMB';
                return $requestResult;
            }
            if ($payType == 3) {
                $payName = 'alipay';
            } else if ($payType == 4) {
                $payName = 'bankpay';
            }

            $requestResult = $twPayModel->getPayUrl($tradeNo, $money, $payName,
                url('/Pay/Tw/Notify', '', false, true),
                url('/Pay/Tw/Return', '', false, true));
        } else if ($payAisle == 7) {
            $time       = time();
            $sign       = md5($tradeNo . $time . 'huaji233');
            $insertData = Db::name('hook_order')->insert([
                'hid'        => 1,
                'tradeNoOut' => $tradeNo,
                'money'      => $money * 100,
                'randStr'    => md5($time . uniqid()),
                'type'       => 1,
                'status'     => 0,
                'createTime' => getDateTime()
            ]);
            if (!$insertData)
                return ['isSuccess' => false, 'msg' => '[HookPay] 生成订单失败 请联系管理员'];
            return ['isSuccess' => true, 'url' => url('/Pay/Hk/WeChatPay?orderID=' . $tradeNo . '&time=' . $time . '&sign=' . $sign, '', false, true)];
        } else if($payAisle == 8){
            $kyxPayModel = new KyxV1Model();
            $requestResult = $kyxPayModel->getPayUrl($tradeNo,$money,$productName,
                url('/Pay/Kyx/Notify','',false,true),
                url('/Pay/Kyx/Return','',false,true)
                );
        }else {
            $requestResult['msg'] = '[EpayCenter] 支付类型接口不存在';
        }
        return $requestResult;
    }
}