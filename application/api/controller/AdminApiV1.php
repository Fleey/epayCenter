<?php

namespace app\api\controller;

use app\api\model\PayModel;
use think\App;
use think\Controller;
use think\Db;

class AdminApiV1 extends Controller
{
    public function __construct(App $app = null)
    {
        parent::__construct($app);

        dump($this->request);
    }

    /**
     * 获取token
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postToken()
    {
        $username = input('post.username/s');
        $password = input('post.password/s');

        if (empty($username) || empty($password))
            return json(['status' => 0, 'msg' => '用户名或者密码不能为空']);
        $selectResult = Db::name('user')->where('username=:username', ['username' => $username])
            ->field('id,password,salt')->limit(1)->select();
        if (empty($selectResult))
            return json(['status' => 0, 'msg' => '用户名或者密码错误']);
        $verifyPassword = hash('sha256', hash('sha256', $password) . $selectResult[0]['salt']);
        if ($verifyPassword != $selectResult[0]['password'])
            return json(['status' => 0, 'msg' => '用户名或者密码错误']);

        $token = hash('sha256', hash('sha256', time()) . uniqid());
        //build token
        $expireTime = 7200;
        //token过期时间 但是为秒
        cache('admin_token_' . $selectResult[0]['id'], $token, $expireTime);
        //缓存token有效期7200秒
        return json(['status' => 1, 'token' => $token, 'uid' => $selectResult[0]['id'], 'expire' => $expireTime]);
    }

    public function postDeleteToken()
    {
//        $uid = input()
    }

    /**
     * 校验token
     * @param int $uid
     * @param string $token
     * @return bool
     */
    private function verifyToken(int $uid, string $token)
    {
        if (empty($uid))
            return false;
        if (strlen($token) != 64)
            return false;
        $tokenData = cache('admin_token_' . $uid);
        if (empty($tokenData))
            return false;
        return $token == $tokenData;
    }
}