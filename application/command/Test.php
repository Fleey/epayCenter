<?php

namespace app\command;

use app\api\model\OwPayV1Model;
use app\api\model\XdPayV1Model;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Test extends Command
{
    public $a = 66;
    public $c = 777;

    protected function configure()
    {
        // 指令配置
        $this->setName('test')->setDescription('user test');
        // 设置参数
    }

    protected function execute(Input $input, Output $output)
    {
        $owPayModel = new XdPayV1Model();
//        $requestData = $owPayModel->getPayUrlAliH5('1',
//            100,
//            url('/Pay/Xd/Notify', '', false, true),
//            url('/Pay/Xd/Return', '', false, true));
        $requestData = $owPayModel->isPay('1', '100');
        //支付宝支付
        dump($requestData);
        // 指令输出
    }
}
