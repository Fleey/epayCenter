<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class SyncOrder extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('syncOrder')->setDescription('user balance');
        // 设置参数

    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->info(' start call back order');
        $this->supplyOrder(5);
        $output->info('end call back order');
    }

    /**
     * 补发订单
     * @param int $callBackCount //从0开始 1 则为两次 2 则为三次
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function supplyOrder($callBackCount = 1)
    {
        for ($i = $callBackCount; $i >= 0; $i--) {
            $callbackList = Db::name('cron')->where('status', $i)->cursor();
            foreach ($callbackList as $value) {
                $result = curl($value['url'], [], $value['method'], json_decode($value['data'], true), '', strtolower($value['method']) == 'get');

                $isCron = false;

                if ($result === false)
                    $isCron = true;
                else {
                    $requestResult = json_decode($result, true);
                    if ($requestResult == null)
                        $isCron = true;
                    else {
                        if ($requestResult['status'] != 1)
                            $isCron = true;
                    }
                }
                if ($isCron) {
                    Db::name('cron')->where('id', $value['id'])->limit(1)->update([
                        'status'     => ($i + 1),
                        'createTime' => getDateTime()
                    ]);
                } else {
                    Db::name('cron')->where('id', $value['id'])->limit(1)->delete();
                }
            }
        }
    }
}
