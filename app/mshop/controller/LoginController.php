<?php
/**
 *
 * @Author: Lcs
 * @Date: 2018/6/2 15:53
 */

namespace app\mshop\controller;


use app\user\model\UserModel;
use cmf\controller\HomeBaseController;
use think\Validate;
use Three\EasyWechat;

class LoginController extends HomeBaseController
{
//    public function _initialize(){
//        parent::_initialize();
//        if(!cmf_is_wechat()){
//            if($this->request->isAjax()){
//                $this->error('请在微信内访问');
//            }else{
//                echo '请在微信内访问!';
//                exit();
//            }
//        }
//        $open_id = session('self_openid');
//        if(empty($open_id) || empty(json_decode($open_id,true)['openid'])){
//            $this->setOpenId();
//        }
//    }
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
            session('self_openid', json_encode($temp, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     *商户登陆
     * @Author: Lcs
     * @Date: 2018/6/2 15:54
     */
    public function login()
    {

        if (cmf_is_mobile()) {
            if (cmf_is_user_login()) {
                if (session('user')['user_type']==3){
                    return $this->redirect(url('mshop/Index/index'));
                }else if (session('user')['user_type']==2){
                    return $this->redirect(url('magent/Index/index'));
                }
            }
            return $this->fetch();
        } else {
            $this->redirect(url('shop/Login/login'));
        }
    }

    /**
     *用户登陆提交
     * @Author: Lcs
     * @Date: 2018/6/2 15:57
     */
    public function loginPost()
    {
        $param = $this->request->param();
        $validate = new Validate([
            'username|帐号' => 'require',
            'pwd|密码' => 'require',
//            'code|验证码'=>'require',
        ]);
        if (!$validate->check($param)) {
            $this->error($validate->getError());
        }
        $user = new UserModel();
        $where = array(
            'mobile' => $param['username'],
            'user_pass' => $param['pwd']
        );
        $log = $user->doMobile($where);
        if ($log['code'] == 1) {
            cmf_user_action('login');
            $user = cmf_get_current_user();
            if ($user['user_type'] == 3) {
                //商户
                $this->success('登录成功', url('mshop/Index/index'));
            } elseif ($user['user_type'] == 2) {
                //代理
                $this->success('登录成功', url('magent/Index/index'));
            }
        } elseif ($log['code'] == 0) {
            $this->error($log['msg']);
        }
    }

    /**
     *忘记密码
     * @return mixed
     * @Author: Lcs
     * @Date: 2018/6/2 16:48
     */
    public function forget()
    {
        return $this->fetch();
    }

    /**
     *忘记密码提交
     * @Author: Lcs
     * @Date: 2018/6/2 16:49
     */
    public function forgetPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username' => 'require',
                'password' => 'require|min:6|max:32',
                'repassword' => 'require|min:6|max:32',
//                'verification_code' => 'require',

            ]);
            $validate->message([
                'username.require' => '请输入手机号码',
                'password.require' => '密码不能为空',
                'password.max' => '密码不能超过32个字符',
                'password.min' => '密码不能小于6个字符',
                'repassword.require' => '重复密码不能为空',
                'repassword.max' => '重复密码不能超过32个字符',
                'repassword.min' => '重复密码不能小于6个字符',
                'verification_code.require' => '手机验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            if ($data['repassword'] != $data['password']) {
                $this->error('两次密码不一致');
            }


            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }


            $userModel = new UserModel();
            if ($validate::is($data['username'], 'email')) {

                $log = $userModel->emailPasswordReset($data['username'], $data['password']);

            } else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['username'])) {
                $user['mobile'] = $data['username'];
                $log = $userModel->mobilePasswordReset($data['username'], $data['password']);
            } else {
                $log = 2;
            }
            switch ($log) {
                case 0:
                    $this->success('密码重置成功', $this->request->root());
                    break;
                case 1:
                    $this->error("您的账户尚未注册");
                    break;
                case 2:
                    $this->error("您输入的账号格式错误");
                    break;
                default :
                    $this->error('未受理的请求');
            }

        } else {
            $this->error("请求错误");
        }
    }

    /**
     *退出登录
     * @Author: Lcs
     * @Date: 2018/6/4 17:58
     */
    public function logoOut()
    {
        session("user", null);
        session('self_openid', null);
        $this->redirect(url('mshop/Login/login'));
    }
}