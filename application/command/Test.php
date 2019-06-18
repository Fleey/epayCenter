<?php

namespace app\command;

use app\api\model\EebPayV1Model;
use app\api\model\KyxV1Model;
use app\api\model\OwPayV1Model;
use app\api\model\TwPayV1Model;
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
        $model = new KyxV1Model();
        //支付宝支付
        dump($model->getPayUrl('1','1','defaultProductName','http://center.zmz999.com/nofity','http://center.zmz999.com/return'));
//        dump($model->isPay('300000000001'));
        // 指令输出
    }
}
