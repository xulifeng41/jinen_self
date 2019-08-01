<?php


namespace app\mall\job;
use getui\GeTui;
use think\queue\Job;

class Jobs
{
    public function action1(Job $job,$data){
//        sleep(10);
        echo $data;
        $getui = new GeTui();
        $getui->pushMessageToApp();

        if ($job->attempts() > 3) {
            //通过这个方法可以检查这个任务已经重试了几次了
        }

        //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
        $job->delete();

        // 也可以重新发布这个任务
//        $job->release($delay); //$delay为延迟时间
    }

    public function action2(Job $job,$data){
//        sleep(5);
        echo $data;

        if ($job->attempts() > 3) {
            //通过这个方法可以检查这个任务已经重试了几次了
        }

        //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
        $job->delete();
    }

    public function failed($data){
        echo 'failed';
    }

}