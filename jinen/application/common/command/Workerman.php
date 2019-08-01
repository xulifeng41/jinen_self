<?php
/**
 * User: Tegic
 * Date: 2018/6/13
 * Time: 09:36
 */
namespace app\common\command;
use app\workerman\Events;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;
class Workerman extends Command
{
    protected function configure()
    {
        $this->setName('workerman')
            ->addArgument('action', Argument::OPTIONAL, "action  start|stop|restart")
            ->addArgument('type', Argument::OPTIONAL, "d -d")
            ->setDescription('workerman chat');
    }
    
    protected function execute(Input $input, Output $output)
    {
        global $argv;
        $action = trim($input->getArgument('action'));
        $type   = trim($input->getArgument('type')) ? '-d' : '';
        
        $argv[0] = 'chat';
        $argv[1] = $action;
        $argv[2] = $type ? '-d' : '';
        $this->start();
    }
    private function start()
    {

        $this->startGateWay();
        $this->startBusinessWorker();
        $this->startRegister();
        Worker::runAll();
    }
    
    private function startBusinessWorker()
    {
        $worker                  = new BusinessWorker();
        $worker->name            = 'mqtt_worker';
        $worker->count           = 4;
        $worker->registerAddress = '127.0.0.1:1238';
        $worker->eventHandler    = Events::class;
    }
    
    private function startGateWay()
    {
        $gateway = new Gateway("tcp://0.0.0.0:8282");
        $gateway->name                 = 'mqtt';
        $gateway->count                = 4;
        $gateway->lanIp                = '127.0.0.1';
        $gateway->startPort            = 2900;
        $gateway->pingInterval         = 45;
        $gateway->pingNotResponseLimit = 2;
        $gateway->pingData             = '{"type":"ping"}';
        $gateway->registerAddress      = '127.0.0.1:1238';
    }
    
    private function startRegister()
    {
        new Register('text://0.0.0.0:1238');
    }
}