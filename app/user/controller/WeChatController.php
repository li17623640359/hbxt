<?php
namespace app\user\controller;

use cmf\controller\HomeBaseController;

class WeChatController extends HomeBaseController{
    public function _initialize()
    {
        parent::_initialize();
        $url = config("WEB_SITE.HTTP_KIND") . '://' . config("WEB_SITE.WEB_URL") . $_SERVER['REQUEST_URI'];
        if (!Wechat::isWeChat()) {
            if (session('userId')) {
                $user_info = M('user_info')->where(['USER_ID' => session('userId')])->find();
                if ($user_info) {
                    $kind = $user_info['user_kind'];
                    $account_state = $user_info['account_state'];
                    if ($kind == 'buyer') {
                        $usercenter = U('member/index');
                    } elseif ($kind == 'seller' && $account_state == 'pass') {
                        $usercenter = U('seller/index');
                    } elseif ($kind == 'server' && $account_state == 'pass') {
                        $usercenter = U('server/index');
                    } else {
                        $usercenter = U('member/index');
                    }
                }
            }
            if (!$usercenter) {
                D('UserServer')->logOut();
                $usercenter = U('user/login');
            }
            header("location:" . $usercenter);
            exit();
        }

        if (session('openid') == null && session('userId')) {
            $code = $_GET['code'];
            Vendor('WxPay.WxPay#Config');
            $appid = \WxPayConfig::APPID;
            $secret = \WxPayConfig::APPSECRET;

            if (!empty($code)) {

                $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
                $at = json_decode(Utils::serverHttpGet($get_token_url), true);
                $openId = $at['openid'];
                session('openid', $openId);
                $Value = serialize($openId);
                $Str = md5($Value . C('COOK_KEY'));
                setcookie('openid', $Str . $Value, time() + 60 * 60 * 24 * 365, '/');
                $userId = Wechat::syncWxUser($openId);
//                session('userId',$userId);
                return;
            }
            $redirectUri = urlencode($url);

            $wechatUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $redirectUri . '&response_type=code&scope=snsapi_base&state=1#wechat_redirect';
            header("Location:" . $wechatUrl);
        }
    }

    public function index()
    {

    }

    public function isOnline()
    {
        if (session('userId') == null) {
            $Value = $_COOKIE['Login'];
            // 去掉魔术引号
            if (get_magic_quotes_gpc()) {
                $Value = stripslashes($Value);
            }
            $Str = substr($Value, 0, 32);
            $Value = substr($Value, 32);
            //校验
            if (md5($Value . C('COOK_KEY')) == $Str) {
                $User = unserialize($Value);
                session('userId', $User);
            }
            if (session('userId') == null) header("Location:" . U('User/login'));
        }
    }
}