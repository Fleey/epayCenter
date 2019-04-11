<?php

namespace app\command;

use app\api\model\OwPayV1Model;
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
        $owPayModel  = new OwPayV1Model();
        $requestData = $owPayModel->getPayUrl('AliH5',
            17, number_format(100 / 100, 2),
            env('DEFAULT_PRODUCT_NAME'),
            url('/Pay/Ow/Notify', '', false, true),
            url('/Pay/Ow/Return', '', false, true));
        //支付宝支付
        dump($requestData);
        // 指令输出
    }
}
