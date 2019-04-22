<?php

namespace app\api\controller;

use app\api\model\OwPayV1Model;
use app\api\model\XaPayV1Model;
use app\api\model\XdPayV1Model;
use think\App;
use think\Controller;
use think\Db;

class ApiV1 extends Controller
{
    private $requestData = [];
    private $uid = 0;
    private $userKey = '';

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->requestData = input('post.');
        if (empty($this->requestData))
            $this->returnJson(['status' => 0, 'msg' => '请求数据不能为空，仅支持POST方式传递参数']);
        if (empty($this->requestData['uid']))
            $this->returnJson(['status' => 0, 'msg' => '商户号不能为空']);
        if (empty($this->requestData['sign_type']))
            $this->returnJson(['status' => 0, 'msg' => '[10001]签名效验失败，仅支持MD5签名']);
        if ($this->requestData['sign_type'] != 'MD5')
            $this->returnJson(['status' => 0, 'msg' => '[10002]签名效验失败，仅支持MD5签名']);
        $userKey = Db::name('user')->where('id', $this->requestData['uid'])->field('key')->limit(1)->select();
        if (empty($userKey))
            $this->returnJson(['status' => 0, 'msg' => '[10003]签名效验失败，仅支持MD5签名']);
        $userKey = $userKey[0]['key'];
        if (!$this->checkSign($this->requestData, $userKey, $this->requestData['sign']))
            $this->returnJson(['status' => 0, 'msg' => '[10004]签名效验失败，仅支持MD5签名']);
        //check sign
        $this->uid         = $this->requestData['uid'];
        $this->requestData = json_decode(urldecode($this->requestData['data']), true);
        $this->userKey     = $userKey;
    }

    /**
     * 查询订单支付状态
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postPayStatus()
    {
        $tradeNo = $this->requestData['tradeNo'];
        if (empty($tradeNo))
            $this->returnJson(['status' => 0, 'msg' => '订单号码不能为空']);
        $result = Db::name('order')->where([
            'tradeNoOut' => $tradeNo,
            'uid'        => $this->uid
        ])->limit(1)->field('status')->select();
        if (empty($result))
            $this->returnJson([
                'status' => 1,
                'msg'    => '[EpayCenter] 查询订单成功',
                'data'   => json_encode([
                    'payStatus' => 0
                ])
            ]);
        $this->returnJson([
            'status' => 1,
            'msg'    => '[EpayCenter] 查询订单成功',
            'data'   => json_encode([
                'payStatus' => $result[0]['status']
            ])
        ]);
    }

    /**
     * 获取QR支付码
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function postPayUrl()
    {
        if (empty($this->requestData['tradeNo']) || empty($this->requestData['payType']) ||
            empty($this->requestData['money']) || empty($this->requestData['notifyUrl']) ||
            empty($this->requestData['returnUrl']))
            $this->returnJson(['status' => 0, 'msg' => '[EpayCenter] 请求参数不能为空']);

        $tradeNo   = $this->requestData['tradeNo'];
        $payType   = $this->requestData['payType'];
        $money     = $this->requestData['money'];
        $notifyUrl = $this->requestData['notifyUrl'];
        $returnUrl = $this->requestData['returnUrl'];

        $payType = self::converPayName($payType);
        if (!$payType)
            $this->returnJson(['status' => 0, 'msg' => '[EpayCenter] 支付类型有误']);
        $money = decimalsToInt($money, 2);
        if ($money <= 0)
            $this->returnJson(['status' => 0, 'msg' => '[EpayCenter] 请求金额异常']);

        $result = Db::name('order')->where([
            'tradeNoOut' => $tradeNo,
            'uid'        => $this->uid
        ])->limit(1)->field('id,status')->select();
        if (!empty($result))
            if ($result[0]['status'])
                $this->returnJson(['status' => 2, 'msg' => '[EpayCenter] 订单已经付款,无法再次支付']);

        Db::name('order')->where([
            'tradeNoOut' => $tradeNo,
            'uid'        => $this->uid
        ])->limit(1)->delete();

        $payAisle = 0;
        if ($payType == 4) {
            $payAisle = 2;
        } else if ($payType == 1) {
            $payAisle = 3;
        } else if ($payType == 3) {
            $payAisle = 4;
        }

        $result = Db::name('order')->insertGetId([
            'uid'        => $this->uid,
            'tradeNoOut' => $tradeNo,
            'money'      => $money,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'payType'    => $payType,
            'payAisle'   => $payAisle,
            'status'     => 0,
            'createTime' => getDateTime()
        ]);
        if (!$result)
            $this->returnJson(['status' => -1, 'msg' => '[EpayCenter]数据库新增数据异常，请刷新重试。']);

        if ($payType == 4) {
            $xaPayModel  = new XaPayV1Model();
            $requestData = $xaPayModel->getPayUrl($result,
                number_format($money / 100, 2),
                env('DEFAULT_PRODUCT_NAME'),
                url('/Pay/Xa/Notify', '', false, true),
                url('/Pay/Xa/Return', '', false, true)
            );
            //银联支付
        } else if ($payType == 1) {
            $owPayModel  = new OwPayV1Model();
            $requestData = $owPayModel->getPayUrl('WxH5',
                $result, number_format($money / 100, 2),
                env('DEFAULT_PRODUCT_NAME'),
                url('/Pay/Ow/Notify', '', false, true),
                url('/Pay/Ow/Return', '', false, true));
            //微信支付
        } else if ($payType == 3) {
            if ($money < 100)
                $this->returnJson(['status' => -1, 'msg' => '[EpayCenter] 最低金额不能小于1RMB' . $money]);
            $xdPayModel  = new XdPayV1Model();
            $requestData = $xdPayModel->getPayUrlAliH5($result, $money, url('/Pay/Xd/Notify', '', false, true), url('/Pay/Xd/Return', '', false, true));
            //支付宝支付
        } else {
            $this->returnJson(['status' => -1, 'msg' => '[EpayCenter] 暂无更多的支付方式']);
        }
//        else {
//            $ybPayModel  = new YbPayV1Model();
//            $requestData = $ybPayModel->getQrCode($result, number_format($money / 100, 2));
//        }
        //核心业务

        if (!$requestData['isSuccess']) {
            Db::name('order')->where('id', $result)->limit(1)->delete();
            $this->returnJson(['status' => -1, 'msg' => '[EpayCenter]' . $requestData['msg']]);
            //订单创建失败回滚
        }

        $isHtml = false;
        if (!empty($requestData['isHtml']))
            if ($requestData['isHtml'])
                $isHtml = true;

        $returnData = [
            'isHtml' => $isHtml
        ];
        if (!$isHtml)
            $returnData['url'] = $requestData['url'];
        else
            $returnData['html'] = $requestData['html'];
        //为了解决那些sb玩意 居然tm直接返回html操蛋
        $this->returnJson(['status' => 1, 'msg' => '[EpayCenter]创建订单成功', 'data' => json_encode($returnData)]);
    }

    /**
     * 效验签名
     * @param array $data
     * @param $key
     * @param $sign
     * @return bool
     */
    private function checkSign(array $data, string $key, string $sign)
    {
        $str1 = createLinkString(argSort(paraFilter($data, true)));
        $str1 = md5($str1 . $key);
        return $str1 == $sign;
    }

    /**
     * 签名返回数据
     * @param array $data
     */
    private function returnJson(array $data)
    {
        if (empty($this->userKey))
            exit(json_encode($data));
        //key is empty
        $data['time']      = time();
        $args              = argSort(paraFilter($data, false));
        $sign              = md5(createLinkString($args) . $this->userKey);
        $data['sign']      = $sign;
        $data['sign_type'] = 'MD5';
        exit(json_encode($data));
    }

    /**
     * 转换支付名称 主要为了兼容老接口 和 优化数据库
     * @param $payName
     * @param bool $isReversal
     * @return int
     */
    public static function converPayName($payName, $isReversal = false)
    {
        if ($isReversal) {
            switch ($payName) {
                case 1:
                    $payName = 'wxpay';
                    break;
                case 3:
                    $payName = 'alipay';
                    break;
                case 2:
                    $payName = 'tenpay';
                    break;
                case 4:
                    $payName = 'bankpay';
                    break;
                default:
                    $payName = 'null';
                    break;
            }
        } else {
            switch ($payName) {
                case 'wxpay':
                    $payName = 1;
                    break;
                case 'alipay':
                    $payName = 3;
                    break;
                case 'qqpay':
                    $payName = 2;
                    break;
                case 'tenpay':
                    $payName = 2;
                    break;
                case 'bankpay':
                    $payName = 4;
                    break;
                default:
                    $payName = 0;
                    break;
            }
        }
        return $payName;
    }
}