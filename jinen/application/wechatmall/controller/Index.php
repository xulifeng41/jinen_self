<?php
namespace app\wechatmall\controller;
use think\Controller;
use think\Db;
use app\wechatmall\model\MallWechatUser;
use app\wechatmall\model\MallWechatQrcode;
define("TOKEN", "xulifeng");

class Index extends Controller
{
    public function index(){
        if (!isset($_GET['echostr'])) {
            $this->responseMsg();
        }else{
            $this->valid();
        }
    }

    //验证签名
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            echo $echoStr;
            exit;
        }
    }

    //响应
    public function responseMsg()
    {
        $postStr=file_get_contents('php://input');
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            if (($postObj->MsgType == "event") && ($postObj->Event == "subscribe" || $postObj->Event == "unsubscribe" || $postObj->Event == "TEMPLATESENDJOBFINISH")){
                //过滤关注和取消关注事件
            }else{
                //更新互动记录
//                Db::name('user')->where('openid',strval($postObj->FromUserName))->setField('heartbeat', time());
            }

            //消息类型分离
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
                case "image":
                    $result = $this->receiveImage($postObj);
                    break;
                case "location":
                    $result = $this->receiveLocation($postObj);
                    break;
                case "voice":
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video":
                    $result = $this->receiveVideo($postObj);
                    break;
                case "link":
                    $result = $this->receiveLink($postObj);
                    break;
                default:
                    $result = "unknown msg type: ".$RX_TYPE;
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }


    //接收事件消息
    private function receiveEvent($object)
    {
        $weixin = new \weixin1\Wxapi();
        $openid = strval($object->FromUserName);
        $content = "";

        switch ($object->Event)
        {
            case "subscribe":
                $wuser=new MallWechatUser;
                $info = $weixin->get_user_info($openid);
                $is_exist=$wuser->where('openid',$openid)->find();
                $municipalities = array("北京", "上海", "天津", "重庆", "香港", "澳门");
                $data = array();
                $data['openid'] = $openid;
                $data['sex'] = $info['sex'];
                $data['province'] = $info['province'];
                $data['subscribe'] = $info['subscribe'];
                $data['nickname'] = str_replace("'", "", $info['nickname']);
                $data['city'] = (in_array($info['province'], $municipalities))?$info['province'] : $info['city'];
                $data['subscribe_time'] = date('Y-m-d H:i:s',$info['subscribe_time']);
                $data['scene'] = (isset($object->EventKey) && (str_replace("qrscene_","",$object->EventKey)))?1:0;
                $data['headimgurl'] = $info['headimgurl'];
                if($data['scene'])
                {
                    $scene = str_replace("qrscene_","",$object->EventKey);
                    $userinfo = $wuser->where('openid', $scene)->find();
                    $data['second']=$userinfo->id;
                    $data['first']=$userinfo->second;
                }
                if($is_exist)
                {
                    $result=MallWechatUser::get($is_exist->id);
                    $data['update_time'] = date('Y-m-d H:i:s');
                    $result->save($data);
                }else
                {
                    $data['create_time'] = date('Y-m-d H:i:s');
                    $wuser->save($data);
                }
                // $User->add($data, array(), true); // 根据条件更新记录
                $content = "欢迎关注，".$info['nickname'];

                break;
            case "unsubscribe":
//                MallWechatUser::where('openid','=',$openid)->delete();
                // $data['heartbeat'] = 0;
                // $User->where("`openid` = '".$openid."'")->save($data); // 根据条件更新记录
                break;
            case "CLICK":
                switch ($object->EventKey)
                {

                    case "SINGLENEWS":
                        $content = array();
                        $content[] = array("Title"=>"单图文标题",  "Description"=>"单图文内容", "PicUrl"=>"", "Url" =>"");
                        break;
                    case "MULTINEWS":
                        $content = array();
                        $content[] = array("Title"=>"多图文1标题", "Description"=>"", "PicUrl"=>"", "Url" =>"");
                        $content[] = array("Title"=>"多图文2标题", "Description"=>"", "PicUrl"=>"", "Url" =>"");
                        $content[] = array("Title"=>"多图文3标题", "Description"=>"", "PicUrl"=>"", "Url" =>"");
                        break;
                    case "MUSIC":
                        $content = array();
                        $content = array("Title"=>"", "Description"=>"", "MusicUrl"=>"", "HQMusicUrl"=>"");
                        break;
                    case "MYCODE":
                        $content = array();
                        $wuser=new MallWechatUser();
                        $result=$wuser->is_buy($openid);
                        if($result==1)
                        {
                            $res=MallWechatQrcode::where('ticket', $openid)->find();
                            if($res)
                            {
                                if($res['expiration_time']<=date('Y-m-d H:i:s',time()))
                                {
                                    //过期，生成
                                    $weixin = new \weixin1\Wxapi();
                                    $data=$weixin->get_qrcode($res['ticket']);
                                    //没记录，更新

                                    $params['id']=$res['id'];
                                    $params['update_time']=date('Y-m-d H:i:s');
                                    $params['expiration_time']=date('Y-m-d H:i:s',strtotime('+3 day'));
                                    $params['media_id']=$data['media_id'];
                                    MallWechatQrcode::update($params);
                                    $content = array("MediaId"=>$data['media_id']);
                                }else
                                    {
                                        $content = array("MediaId"=>$res['media_id']);
                                    }
                            }else
                            {
                                //没记录，生成
                                $weixin = new \weixin1\Wxapi();
                                $data=$weixin->get_qrcode($openid);
                                $info = $wuser->where('openid', $openid)->find();
                                $params['wuser_id']=$info->id;
                                $params['ticket']=$openid;
                                $params['qrcode_url']=$data['qrcode_url'];
                                $params['create_time']=date('Y-m-d H:i:s');
                                $params['expiration_time']=date('Y-m-d H:i:s',strtotime('+3 day'));
                                $params['media_id']=$data['media_id'];
                                MallWechatQrcode::create($params);
                                $content = array("MediaId"=>$data['media_id']);
                            }
                        }elseif($result==2)
                        {
                            $content = '当前用户暂无此功能';
                        }
                        break;
                    default:
                        $content = "点击菜单：".$object->EventKey;
                        break;
                }
                break;
//            case "VIEW":
//                $content = "跳转链接 ".$object->EventKey;
//                break;
            case "SCAN":
//                $content = "扫描参数二维码，场景ID：".$object->EventKey;
                $wuser=new MallWechatUser();
                $scene = (isset($object->EventKey) && (str_replace("qrscene_","",$object->EventKey)))?str_replace("qrscene_","",$object->EventKey):0;
                if($scene)
                {
                    $info = $wuser->where('openid', $openid)->find();
                    MallWechatQrcode::where('wuser_id','=',$info->id)->setInc('scan');
                }
                break;
//            case "LOCATION":
//
//                $content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
//                $content = "";
//                break;
//            case "scancode_waitmsg":
//                if ($object->ScanCodeInfo->ScanType == "qrcode"){
//                    $content = "扫码带提示：类型 二维码 结果：".$object->ScanCodeInfo->ScanResult;
//                }else if ($object->ScanCodeInfo->ScanType == "barcode"){
//                    $codeinfo = explode(",",strval($object->ScanCodeInfo->ScanResult));
//                    $codeValue = $codeinfo[1];
//                    $content = "扫码带提示：类型 条形码 结果：".$codeValue;
//                }else{
//                    $content = "扫码带提示：类型 ".$object->ScanCodeInfo->ScanType." 结果：".$object->ScanCodeInfo->ScanResult;
//                }
//                break;
//            case "scancode_push":
//                $content = "扫码推事件";
//                break;
//            case "pic_sysphoto":
//                $content = "系统拍照1";
//                break;
//            case "pic_weixin":
//                $content = "相册发图：数量 ".$object->SendPicsInfo->Count;
//                break;
//            case "pic_photo_or_album":
//                $content = "拍照或者相册：数量 ".$object->SendPicsInfo->Count;
//                break;
//            case "location_select":
//                $content = "发送位置：标签 ".$object->SendLocationInfo->Label;
//                break;
            default:
                $content = "receive a new event: ".$object->Event;
                break;
        }
        if(is_array($content)){
            if (isset($content[0])){
                $result = $this->transmitNews($object, $content);
            }else if (isset($content['MusicUrl'])){
                $result = $this->transmitMusic($object, $content);
            }else if (isset($content['MediaId']))
            {
                $result = $this->transmitImage($object, $content);
            }
        }else{
            $result = $this->transmitText($object, $content);
        }

        return $result;
    }

    //接收文本消息
    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        $openid = strval($object->FromUserName);
        $content = "";
        //多客服人工回复模式
        if (strstr($keyword, "在线客服_") || strstr($keyword, "你好_")){
            $result = $this->transmitService($object);
        }
        //自动回复模式
        else{
            if (strstr($keyword, "文本")){
//                $result=$wuser->is_buy($openid);
//                file_put_contents('obj', '{"obj": "'.$result.'"}');
//                $content = $result;
            }else if (strstr($keyword, "单图文")){
                $content = array();
                $content[] = array("Title"=>"单图文标题",  "Description"=>"单图文内容", "PicUrl"=>"", "Url" =>"");
                $weixin = new \weixin1\Wxapi();
                $template = array('touser' => $openid,
                    'template_id' => "_yFpVtfHd0pSWy6ffApi6isjY8HmmWC8aKW-Uqz8viU",
                    'url' => "",
                    'topcolor' => "#0000C6",
                    'data' => array('content'    => array('value' => "123",
                        'color' => "#743A3A",
                    ),
                    )
                );
                $weixin->send_template_message($template);
            }else if (strstr($keyword, "图文") || strstr($keyword, "多图文")){
                $content = array();
                $content[] = array("Title"=>"多图文1标题", "Description"=>"", "PicUrl"=>"", "Url" =>"");
                $content[] = array("Title"=>"多图文2标题", "Description"=>"", "PicUrl"=>"", "Url" =>"");
                $content[] = array("Title"=>"多图文3标题", "Description"=>"", "PicUrl"=>"", "Url" =>"");

            }else if (strstr($keyword, "音乐")){
                $content = array();
                $content = array("Title"=>"", "Description"=>"", "MusicUrl"=>"", "HQMusicUrl"=>"");
            }else{
                // 可以接入机器人
                $content = '你好,未识别关键字';
            }

            if(is_array($content)){
                if (isset($content[0]['PicUrl'])){
                    $result = $this->transmitNews($object, $content);
                }else if (isset($content['MusicUrl'])){
                    $result = $this->transmitMusic($object, $content);
                }
            }else{
                $result = $this->transmitText($object, $content);
            }
        }

        return $result;
    }

    //接收图片消息
    private function receiveImage($object)
    {
        $content = array("MediaId"=>$object->MediaId);
        $result = $this->transmitImage($object, $content);
        return $result;
    }

    //接收位置消息
    private function receiveLocation($object)
    {

        $content = "你发送的是位置，经度为：".$object->Location_Y."；纬度为：".$object->Location_X."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收语音消息
    private function receiveVoice($object)
    {
        if (isset($object->Recognition) && !empty($object->Recognition)){
            $content = "你刚才说的是：".$object->Recognition;
            $result = $this->transmitText($object, $content);
        }else{
            $content = array("MediaId"=>$object->MediaId);
            $result = $this->transmitVoice($object, $content);
        }
        return $result;
    }

    //接收视频消息
    private function receiveVideo($object)
    {
        $content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");
        $result = $this->transmitVideo($object, $content);
        return $result;
    }

    //接收链接消息
    private function receiveLink($object)
    {
        $content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //回复文本消息
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>
$item_str</Articles>
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    //回复音乐消息
    private function transmitMusic($object, $musicArray)
    {
        $itemTpl = "<Music>
    <Title><![CDATA[%s]]></Title>
    <Description><![CDATA[%s]]></Description>
    <MusicUrl><![CDATA[%s]]></MusicUrl>
    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
</Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
$item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复图片消息
    private function transmitImage($object, $imageArray)
    {
        $itemTpl = "<Image>
        <MediaId><![CDATA[%s]]></MediaId>
    </Image>";

        $item_str = sprintf($itemTpl, $imageArray['MediaId']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[image]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复语音消息
    private function transmitVoice($object, $voiceArray)
    {
        $itemTpl = "<Voice>
        <MediaId><![CDATA[%s]]></MediaId>
    </Voice>";

        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[voice]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复视频消息
    private function transmitVideo($object, $videoArray)
    {
        $itemTpl = "<Video>
        <MediaId><![CDATA[%s]]></MediaId>
        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
    </Video>";

        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[video]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复多客服消息
    private function transmitService($object)
    {
        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[transfer_customer_service]]></MsgType>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }



    //字节转Emoji表情
    function bytes_to_emoji($cp)
    {
        if ($cp > 0x10000){       # 4 bytes
            return chr(0xF0 | (($cp & 0x1C0000) >> 18)).chr(0x80 | (($cp & 0x3F000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
        }else if ($cp > 0x800){   # 3 bytes
            return chr(0xE0 | (($cp & 0xF000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
        }else if ($cp > 0x80){    # 2 bytes
            return chr(0xC0 | (($cp & 0x7C0) >> 6)).chr(0x80 | ($cp & 0x3F));
        }else{                    # 1 byte
            return chr($cp);
        }
    }

    //日志记录
    private function logger($log_content)
    {
        if(isset($_SERVER['HTTP_APPNAME'])){   //SAE
            sae_set_display_errors(false);
            sae_debug($log_content);
            sae_set_display_errors(true);
        }else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
            $max_size = 1000000;
            $log_filename = "log.xml";
            if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
            file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);
        }
    }
}
