<?php
/**
 * 微信接口控制器
 *
 * @date: 2018年7月2日下午3:09:33
 * @author: Hhb
 */
namespace  app\user\controller;
use cmf\controller\HomeBaseController;
use Three\EasyWechat;
use Three\Wechat;

class WeixinController extends HomeBaseController{

    protected $web_site = array();
    protected $weObj = null;
	public function _initialize(){
	    $wechat = config('wechat');
	    $web_site = config('web_site');
		$options = array(
 			'token'=>$wechat['token'], //填写您设定的key
 			'encodingaeskey'=>$wechat['encodingaeskey'], //填写加密用的EncodingAESKey
 			'appid'=>$wechat['appid'],//填写高级调用功能的app id
 			'appsecret'=>$wechat['appsecret'], //填写高级调用功能的密钥
 		);
		$weObj = new EasyWechat($options);
    	$this->weObj = $weObj;
        $this->web_site = $web_site;
	}

	/**
	 * 微信被动访问首页
	 */
    public function index(){

    	$weObj = $this->weObj;
    	$weObj->valid();


    	/*获取服务器信息 返回接收消息类型*/
       	$type = $weObj->getRev()->getRevType();
		switch($type) {
			//处理字符串请求
		    case Wechat::MSGTYPE_TEXT:
		    		$text = $weObj->getRevContent();
		    		//$this->tuling($text,$weObj);//调用图灵机器人
		            $weObj->text($text)->reply();

		            break;
		    //处理语音请求
		    case Wechat::MSGTYPE_VOICE:
		    		
		    		$text = $weObj->getRevContent();
		    		if(isset($text)){
		    			$weObj->text($text)->reply();
		    			//$this->tuling($text,$weObj);//调用图灵机器人
		    		}else{
		    			$data = $weObj->getRevVoice();
		    			$weObj->voice($data['mediaid'])->reply();
		    		}

		            break;
		    //处理事件请求
		    case Wechat::MSGTYPE_EVENT:
		    		$data = $weObj->getRevEvent();
		    		if(!empty($data)){
		    		    $data['event'] = strtolower($data['event']);
		    		    switch($data['event']) {
		    		        //事件关注
		    		        case 'subscribe':
		    		            $text = "欢迎关注【{$this->web_site['web_title']}】";
		    		            $weObj->text($text)->reply();
		    		            break;
		    		        case 'click':
		    		            if($data['key'] == 'image_center'){
		    		                $weObj->image()->reply();
		    		            }elseif ($data['key'] == 'image_pay'){
		    		                $weObj->text($data['key'])->reply();
		    		            }
		    		            break;
	    		            case 'view':
	    		                $weObj->text($data['key'])->reply();
	    		                break;
		    		        default:
		    		            $weObj->text(json_encode($data))->reply();
		    		            break;
		    		    }
		    		}
		    		break;
		    //处理图片请求
		    case Wechat::MSGTYPE_IMAGE:
		            break;
		    default:
		            $weObj->text("我读书少，您所说的还不懂！")->reply();
		            break;
		}
    }


    /**
     * 获取自定义菜单
     */
    public function getMenu(){

    	$data = $this->weObj->getMenu();
    	return $data;
    }

    /**
     * 创建自定义菜单
     * @param array $data 菜单数据
     */
   	public function createMenu(){
        $data = array (
             'button' => array (
               0 => array (
                 'name' => '商务中心',
                 'sub_button' => array (
                     0 => array (
                       'type' => 'click',
                       'name' => '业务服务',
                       'key' => 'image_center',
                     ),
                     1 => array (
                       'type' => 'view',
                       'name' => '商务咨询',
                       'url' => 'https://mp.weixin.qq.com/s/7DazHMw-YKmJw9HIktsghw',
                     ),
                 ),
               ),
               1 => array (
                 'name' => '资讯中心',
                 'sub_button' => array (
                     0 => array (
                       'type' => 'view',
                       'name' => '视频',
                       'url' => 'http://mp.weixin.qq.com/mp/video?__biz=MzU2OTU0NDM1MA==&mid=100000015&sn=c59efe285e51e0c4e463c5914619f488&vid=p1339alvyiz&idx=1&vidsn=ed280f885680b9e54aa2116c8a603421&fromid=1&scene=18#wechat_redirect',
                     ),
                     1 => array (
                       'type' => 'pic_photo_or_album',
                       'name' => '拍照或者相册发图',
                       'key' => 'rselfmenu_1_1',
                     )
                 ),
               ),
               1 => array (
                 'name' => '付款码',
                 'sub_button' => array (
                     0 => array (
                       'type' => 'view',
                       'name' => '微信小店',
                       'url' => 'http://mp.weixin.qq.com/bizmall/malldetail?id=&pid=pnuzC1IuiooDUPx9NIpEqw2DM2Ns&biz=MzU2OTU0NDM1MA==&scene=&action=show_detail&showwxpaytitle=1#wechat_redirect',
                     ),
                     1 => array (
                       'type' => 'click',
                       'name' => '拍照或者相册发图',
                       'key' => 'image_pay',
                     )
                 ),
               ),
             ),
         );
   		$data = $this->weObj->createMenu($data); //创建

   		return $data;
   	}

   	/**
   	 * 清空自定义菜单
   	 * @param int $menuid 个性菜单ID 没有清空所有菜单
   	 */
   	public function deleteMenu($menuid=null){

   		$data = $this->weObj->deleteMenu($menuid);
   		dump($data);
   	}


    /**
     * 图灵机器人接口
     * @param string $text 文本信息
     * @param obj $weObj 微信对象
     */
   	public function tuling($text,$weObj){
   		$key = 'ea6277bcc896e33de5f771b14e5079de';
   		$url = 'http://www.tuling123.com/openapi/api?key='.$key.'&info='.$text.'&loc=重庆市渝中区&userid=1';
   		$data = $this->getRemoteUrl($url);
    	$data = json_decode($data,true);

    	//接收返回值 分类处理
    	switch ($data['code']) {
    		case '100000':
    			//纯文本数据
    			$weObj->text($data['text'])->reply();
    			break;
    		case '200000':
    			//超链接数据
    			$weObj->text('<a href="'.$data['url'].'">'.$data['text'].'</a>')->reply();
    			break;
    		case '302000':
    			//新闻数据
    			$list = array();$i = 0;
				foreach ($data['list'] as $k => $v) {
					$row = array();
					$row['Title'] = $v['article'];
					$row['Description'] = $v['source'];
					$row['PicUrl'] = $v['icon'];
					$row['Url'] = $v['detailurl'];
					$list[] = $row;
					$i++;
					if($i>=10){
						break;
					}
				}

    			$weObj->news($list)->reply();
    			break;
    		case '308000':
    			//菜谱数据
				$list = array();$i = 0;
				foreach ($data['list'] as $k => $v) {
					$row = array();
					$row['Title'] = $v['name'];
					$row['Description'] = $v['info'];
					$row['PicUrl'] = $v['icon'];
					$row['Url'] = $v['detailurl'];
					$list[] = $row;
					$i++;
					if($i>=10){
						break;
					}
				}

    			$weObj->news($list)->reply();
    			break;
    		
    		default:
    			$weObj->text('我读书少，您不要说得这么深奥！')->reply();
    			break;
    	}
   	}

   	/**
	 * 获取远程数据
	 * @param string $url 要请求的URL地址
	 */
    private function getRemoteUrl($url = '', $method = '', $param = ''){
		$opts = array(
			CURLOPT_TIMEOUT        => 20,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL            => $url
		);
		if($method === 'post'){
			$opts[CURLOPT_POST] = 1;
			$opts[CURLOPT_POSTFIELDS] = $param;
		}

		/* 初始化并执行curl请求 */
		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		$data  = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		return $data;
	}
}
