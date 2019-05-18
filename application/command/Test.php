<?php

namespace app\command;

use app\api\model\EebPayV1Model;
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
        $model = new EebPayV1Model();
        //支付宝支付
        dump($model->getPayUrl('400000000001','100','alipay','http://192.168.1.204:9001/OnLinePayNotifyUrl.php'));
        // 指令输出
    }
}
