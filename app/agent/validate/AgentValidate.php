<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/2 0002
 * Time: 17:20
 */

namespace app\agent\validate;


use think\Validate;

class AgentValidate extends Validate
{
    protected $rule = [
         'user_nickname'  => 'require',
        'avatar'  => 'require',
        'address' => 'require',
        'user_email' => 'email',
            ];
            protected $message = [
        'user_nickname.require' => '代理商姓名不能为空',
        'avatar.require' => '头像不能为空',
        'user_email' => '邮箱格式错误',
        'address.require'  => '地址不能为空',
    ];
}