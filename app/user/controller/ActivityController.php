<?php
/**
 * 活动展示前端主控制器
 *
 * @date: 2018年6月13日下午4:11:06
 * @author: Hhb
 */

namespace app\user\controller;

use cmf\controller\HomeBaseController;
use Three\EasyWechat;
use app\user\model\UserActivityModel;
use app\user\service\ActivityService;

class ActivityController extends HomeBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        if (!cmf_is_wechat()) {
            if ($this->request->isAjax()) {
                $this->error('请在微信内访问');
            } else {
                echo '请在微信内访问!';
                exit();
            }
        }
        $open_id = session('openid');
        if (empty($open_id) || empty(json_decode($open_id, true)['openid'])) {
            $this->setOpenId();
        }
    }

    protected function setOpenId()
    {
        $wechat = config('wechat');
        $web_site = config('web_site');
        $options = array(
            'token' => $wechat['token'], //填写您设定的key
            'encodingaeskey' => $wechat['encodingaeskey'], //填写加密用的EncodingAESKey
            'appid' => $wechat['appid'],//填写高级调用功能的app id
            'appsecret' => $wechat['appsecret'], //填写高级调用功能的密钥
        );
        $wx = new EasyWechat($options);
        $code = $this->request->param('code');
        if (empty($code)) {
            $scope = 'snsapi_base';
            $callback = $web_site['http_kind'] . '://' . $web_site['web_url'] . $_SERVER['REQUEST_URI'];
            $url = $wx->getOauthRedirect($callback, 'state', $scope);
            $this->redirect($url);
        } else {
            $data = $wx->getOauthAccessToken();
            $open_id = $data['openid'];
            $user = $wx->getUserInfo($open_id);//getOauthUserinfo($data['access_token'],$data['openid']);//
            $temp = array(
                'openid' => $open_id,
            );
            if (!empty($user)) {
                $temp['name'] = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $user['nickname']);
            } else {
                $temp['name'] = '';
            }
            session('openid', json_encode($temp, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 红包扫码活动前置页面（校验活动，错误提示页面）
     * @date: 2018年7月10日下午3:08:17
     * @author: Hhb
     */
    public function index()
    {
        $code = $this->request->param('c', '');
        $msg = $this->request->param('msg', '');
        if (empty($msg)) {
            $service = new ActivityService();
            $re = $service->checkActivityByCode($code);
            if ($re['status'] == 1) {
                session('activity', json_encode($re['value'], JSON_UNESCAPED_UNICODE));
                $this->redirect(url('user/activity/act'));
            }
            $msg = $re['msg'];
        }
        $this->assign('is_examine', config('is_examine'));
        $this->assign('site',config('site'));
        $this->assign('msg', $msg);
        return $this->fetch();
    }

    /**
     * 领红包主页面
     * @date: 2018年7月10日下午3:09:10
     * @author: Hhb
     */
    public function act()
    {
        $activity = session('activity');
        if (empty($activity)) {
            $this->redirect(url('user/activity/index', ['msg' => '无效请求！']));
        }
        $activity = json_decode($activity, true);
        $aid = $activity['aid'];
        $act_model = new UserActivityModel();
        $act = $act_model->getInfoById($aid);
        if (empty($act)) {
            $this->redirect(url('user/activity/index', ['msg' => '活动不存在！']));
        }
        $wechat = config('wechat');
        $web_site = config('web_site');
        $options = array(
            'token' => $wechat['token'], //填写您设定的key
            'encodingaeskey' => $wechat['encodingaeskey'], //填写加密用的EncodingAESKey
            'appid' => $wechat['appid'],//填写高级调用功能的app id
            'appsecret' => $wechat['appsecret'], //填写高级调用功能的密钥
        );
        $wx = new EasyWechat($options);
        $signPackage = $wx->getJsSign();
        $this->assign('act', $act);
        $this->assign('signPackage', $signPackage);
        return $this->fetch();
    }

    /**
     * 拆红包
     * @date: 2018年7月10日下午3:11:23
     * @author: Hhb
     */
    public function receive()
    {
        $activity = session('activity');
        if (empty($activity)) {
            $this->error('无效请求！');
        }
        $activity = json_decode($activity, true);
        $aid = $activity['aid'];
        $act_model = new UserActivityModel();
        $act = $act_model->getInfoById($aid);
        if (empty($act)) {
            $this->error('活动不存在！');
        }
        $service = new ActivityService();
        $re = $service->receivePacket($activity);
        if ($re['status'] != 1) {
            $this->error($re['msg']);
        }
        $money = $activity['money'];
        $this->success('ok', '', $money);
    }

    /**
     * 活动页面
     * @date: 2018年7月10日下午3:11:56
     * @author: Hhb
     */
    public function show()
    {
        $aid = $this->request->param('aid', 0, 'intval');
        $service = new ActivityService();
        $re = $service->setActivityClickNum($aid);
        if ($re['status'] != 1) {
            $this->redirect(url('user/activity/index', ['msg' => $re['msg']]));
        }
        $this->assign('act', $re['value']);
        return $this->fetch();
    }
}
