<?php


namespace app\mall\controller;
use think\Queue;
use think\Controller;

class QueTest extends Controller
{
    public function test(){
        $job = 'app\mall\job\Jobs@action1';
        Queue::push($job, $data = '545465455', $queue = 'getui');
//        $getui = new GeTui();
//        $res = $getui->pushMessageToApp();
//        if ($res){
            echo ' dsfsdf3645646f';
//        }

    }

    public function test1(){
        $job = 'app\mall\job\Jobs@action2';
        Queue::push($job, $data = '545465455', $queue = 'getui');
//        echo 'jkjfs';
    }


}