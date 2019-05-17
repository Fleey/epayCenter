<?php

namespace app\api\model;

use PDOStatement;
use think\Collection;
use think\Db;
use think\Exception;

class UserModel
{
    /**
     * @param string $username
     * @param string $password
     * @return int|string
     */
    public static function create(string $username, string $password)
    {
        if (empty($username) || empty($password))
            return 0;
        $salt     = getRandChar(6);
        $key      = hash('sha256', time() . uniqid());
        $password = hash('sha256', hash('sha256', $password) . $salt);

        $insertResult = Db::name('user')->insertGetId([
            'username'   => $username,
            'password'   => $password,
            'salt'       => $salt,
            'key'        => $key,
            'createTime' => getDateTime()
        ]);
        return $insertResult;
    }

    /**
     * @param int $uid
     * @param string $keyName
     * @param string $value
     * @return int|string
     * @throws Exception
     */
    public static function setAttr(int $uid, string $keyName, string $value)
    {
        $isExist = Db::name('user_attr')->field('id')->limit(1)->where([
            'uid'     => $uid,
            'attrKey' => $keyName
        ])->select();
        if (empty($isExist)) {
            return Db::name('user_attr')->insertGetId([
                'uid'     => $uid,
                'attrKey' => $keyName,
                'value'   => $value
            ]);
        } else {
            return Db::name('user_attr')->where([
                'uid'     => $uid,
                'attrKey' => $keyName
            ])->limit(1)->update([
                'value' => $value
            ]);
        }
    }

    /**
     * @param int $uid
     * @param string $keyName
     * @return array|PDOStatement|string|Collection
     */
    public static function getAttr(int $uid, string $keyName)
    {
        try {
            $result = Db::name('user_attr')->field('value')->limit(1)->where([
                'uid'     => $uid,
                'attrKey' => $keyName
            ])->select();
            if (empty($result))
                $result = '';
            else
                $result = $result[0]['value'];
        } catch (Exception $exception) {
            $result = '';
        }
        return $result;
    }
}