<?php

namespace app\api\controller;

use app\api\model\PayModel;
use think\App;
use think\Controller;
use think\Db;

class PayApiV1 extends Controller
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
        if (empty($this->requestData['time']))
            $this->returnJson(['status' => 0, 'msg' => '[10005]签名校验失败,时间参数不能为空']);
        if ((time() - intval($this->requestData['time'])) > 120)
            $this->returnJson(['status' => 0, 'msg' => '[10006]签名校验失败,时间超时 或者 将时区调整为 Asia/Shanghai']);
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
     * 获取支付接口列表
     */
    public function postPayApiListAll()
    {
        $this->returnJson([
            'status' => 1,
            'msg'    => '查询接口列表成功',
            'data'   => json_encode(PayModel::apiList)
        ]);
    }

    /**
     * 获取银行代付列表
     */
    public function postBankListAll()
    {
        $this->returnJson([
            'status' => 1,
            'msg'    => '查询接口列表成功',
            'data'   => json_encode(PayModel::bankList)
        ]);
    }

    /**
     * 获取接口列表
     */
    public function postPayApiList()
    {
        if (!isset($this->requestData['type']))
            $this->returnJson([
                'status' => 0,
                'msg'    => '支付类型不能为空'
            ]);
        $payType = $this->requestData['type'] - 1;

        $this->returnJson([
            'status' => 1,
            'msg'    => '查询接口列表成功',
            'data'   => json_encode(empty(PayModel::apiList[$payType]) ? [] : PayModel::apiList[$payType])
        ]);
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

        if (empty($this->requestData['payAisle']))
            $this->requestData['payAisle'] = 0;

        $payAisle = intval($this->requestData['payAisle']);

        $payType = self::converPayName($payType);
        if (!$payType)
            $this->returnJson(['status' => 0, 'msg' => '[EpayCenter] 支付类型有误']);
        if (empty($payAisle))
            $this->returnJson(['status' => 0, 'msg' => '[EpayCenter] 支付接口不能为空']);
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

        if (!PayModel::isExistPayApi($payType, $payAisle))
            $this->returnJson(['status' => 0, 'msg' => '[EpayCenter] 2支付类型接口有误,请联系管理员处理']);
        Db::name('order')->where([
            'tradeNoOut' => $tradeNo,
            'uid'        => $this->uid
        ])->limit(1)->delete();


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

        $requestData = PayModel::buildPayData($result, number_format($money / 100, 2, '.', ''), $payType, $payAisle);
        //核心业务
        if (!$requestData['isSuccess']) {
            Db::name('order')->where('id', $result)->limit(1)->delete();
            $this->returnJson(['status' => -1, 'msg' => '[EpayCenter]' . $requestData['msg']]);
            //订单创建失败回滚
        }

        $isHtml = empty($requestData['url']);

        $returnData = [
            'isHtml' => $isHtml
        ];
        if ($isHtml)
            $returnData['html'] = $requestData['html'];
        else
            $returnData['url'] = $requestData['url'];
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