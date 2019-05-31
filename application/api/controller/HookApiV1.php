<?php

namespace app\api\controller;

use think\App;
use think\Controller;
use think\Db;

class HookApiV1 extends Controller
{
    private $requestData = [];
    private $hid = 0;
    private $userKey = '';

    /**
     * HookApiV1 constructor.
     * @param App|null $app
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->requestData = input('post.');
        if (empty($this->requestData))
            $this->returnJson(['status' => 0, 'msg' => '请求数据不能为空，仅支持POST方式传递参数']);
        if (empty($this->requestData['hid']))
            $this->returnJson(['status' => 0, 'msg' => 'Hook id is empty']);
        if (empty($this->requestData['sign_type']))
            $this->returnJson(['status' => 0, 'msg' => '[10001]签名效验失败，仅支持MD5签名']);
        if (empty($this->requestData['time']))
            $this->returnJson(['status' => 0, 'msg' => '[10005]签名校验失败,时间参数不能为空']);
        if ((time() - intval($this->requestData['time'])) > 120)
            $this->returnJson(['status' => 0, 'msg' => '[10006]签名校验失败,时间超时 或者 将时区调整为 Asia/Shanghai']);
        if ($this->requestData['sign_type'] != 'MD5')
            $this->returnJson(['status' => 0, 'msg' => '[10002]签名效验失败，仅支持MD5签名']);
        $userKey = Db::name('hook_user')->where('id=:hid', ['hid' => $this->requestData['hid']])->field('key')->limit(1)->select();
        if (empty($userKey))
            $this->returnJson(['status' => 0, 'msg' => '[10003]签名效验失败，仅支持MD5签名']);
        $userKey = $userKey[0]['key'];
        if (!$this->checkSign($this->requestData, $userKey, $this->requestData['sign']))
            $this->returnJson(['status' => 0, 'msg' => '[10004]签名效验失败，仅支持MD5签名']);
        //check sign
        $this->hid         = $this->requestData['hid'];
        $this->requestData = json_decode(urldecode($this->requestData['data']), true);
        $this->userKey     = $userKey;
    }

    /**
     * 异步回调订单二维码
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function postQrNotify()
    {
        $hid     = $this->hid;
        $qrURL   = $this->requestData['url'];
        $randStr = $this->requestData['randStr'];

        if (empty($qrURL) || empty($randStr))
            $this->returnJson(['status' => 0, 'msg' => 'request param is empty']);

        $selectData = Db::name('hook_order')->where([
            'hid'     => $hid,
            'randStr' => $randStr
        ])->field('id,status')->limit(1)->select();

        if (empty($selectData))
            $this->returnJson(['status' => 0, 'msg' => 'empty notify data']);
        //check notify order
        if ($selectData[0]['status'] != 1)
            $this->returnJson(['status' => 0, 'msg' => 'order status error']);
        //check notify order status
        $updateResult = Db::name('hook_order')->where([
            'hid'     => $hid,
            'randStr' => $randStr
        ])->limit(1)->update([
            'codeUrl' => $qrURL,
            'status'  => 2
        ]);

        if ($updateResult)
            $this->returnJson(['status' => 1, 'msg' => 'notify success']);

        $this->returnJson(['status' => 0, 'msg' => 'change order data fail']);
    }

    public function postErrorNotify()
    {
        $hid = $this->hid;
        $msg = $this->requestData['msg'];
        if (empty($msg))
            $this->returnJson(['status' => 0, 'msg' => 'data error']);
        trace('[Error]', 'hid => ' . $hid . ' msg =>' . $msg);
        $this->returnJson(['status' => 1]);
    }

    /**
     * 支付回调
     */
    public function postPayNotify()
    {
        $hid     = $this->hid;
        $money   = $this->requestData['money'];
        $randStr = $this->requestData['randStr'];

        if (empty($money) || empty($randStr))
            $this->returnJson(['status' => 0, 'msg' => 'request param is empty']);

        $selectData = Db::name('hook_order')->where([
            'hid'     => $hid,
            'randStr' => $randStr
        ])->field('id,status')->limit(1)->select();

        if (empty($selectData))
            $this->returnJson([
                'status' => 0,
                'msg'    => 'empty notify data'
            ]);
        //check notify order
        if ($selectData[0]['status'] == 3)
            $this->returnJson([
                'status' => 1,
                'msg'    => 'notify success'
            ]);

        $updateResult = Db::name('hook_order')->where([
            'hid'     => $hid,
            'randStr' => $randStr
        ])->limit(1)->update([
            'status' => 3
        ]);

        if ($updateResult)
            $this->returnJson([
                'status' => 1,
                'msg'    => 'notify success'
            ]);
        //change order status
        $this->returnJson([
            'status' => 0,
            'msg'    => 'change order status fail'
        ]);
    }

    /**
     * 心跳获取订单列表，并且改变状态
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function postGetOrderInfo()
    {
        $hid          = $this->hid;
        $selectResult = Db::name('hook_order')->where([
            'type'   => 1,
            'status' => 0,
            'hid'    => $hid
        ])->field('id,money,randStr')->order('id')->limit(10)->select();

        if (empty($selectResult))
            $this->returnJson(['status' => 1, 'data' => [], 'total' => 0]);

        $ids = [];
        foreach ($selectResult as $key => $value) {
            $ids[]                       = $value['id'];
            $selectResult[$key]['money'] = number_format($value['money'] / 100, 2, '.', '');
        }
        //获取需要更新的id列表

        Db::name('hook_order')->where('id', 'in', $ids)->update([
            'status' => 1
        ]);

        $this->returnJson([
            'status' => 1,
            'data'   => json_encode($selectResult),
            'total'  => count($selectResult)
        ]);
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
}