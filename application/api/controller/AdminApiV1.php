<?php

namespace app\api\controller;

use think\App;
use think\Controller;
use think\Db;

class AdminApiV1 extends Controller
{
    private $whiteList = [
        'api/v1/admin/token'
    ];

    private $uid = 0;
    private $token = '';

    public function __construct(App $app = null)
    {
        parent::__construct($app);

        $requestPath       = strtolower($this->request->path());
        $requestPathLength = strlen($requestPath);
        if ($requestPath[$requestPathLength - 1] == '/')
            $requestPath = substr($requestPath, 0, $requestPathLength - 1);
        //标准化请求路径
        if (!in_array($requestPath, $this->whiteList)) {
            $this->uid   = input('post.uid/d', 0);
            $this->token = input('post.token/s', '');
            if (!$this->verifyToken($this->uid, $this->token))
                exit(json([
                    'status' => 0,
                    'msg'    => 'token已经失效,请重新登录或获取'
                ])->send());
        }
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

    /**
     * 注销登录函数
     * @return \think\response\Json
     */
    public function postDeleteToken()
    {
        $uid   = input('post.uid/s');
        $token = input('post.token/s');

        $tokenData = cache('admin_token_' . $uid);

        do {
            if (empty($tokenData))
                break;
            if (strlen($token) != 64)
                break;
            if ($token != $tokenData)
                break;
            cache('admin_token_' . $uid, null);
        } while (false);
        //注销token
        return json(['status' => 1, 'msg' => '已经注销成功']);
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