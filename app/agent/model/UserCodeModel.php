<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/9 0009
 * Time: 上午 8:55
 */

namespace app\agent\model;


use think\Model;

class UserCodeModel extends Model
{
    /**
     *
     * @Author: Ldm
     * @FunctionName:查询多个未分配的红包码段
     * @Date:2018/6/9 0009
     */
  public function getCodeMany($where=''){
   $where['status']=1;
   $where['user_id']=0;
   $data=$this->where($where)->select()->toArray();
   if($data){
       return $data;
   }
   return false;
  }

    /**
     *
     * @Author: Ldm
     * @FunctionName:分配红包
     * @Date:2018/6/9 0009
     */
  public function setAllotCode($where='',$arrayData){
   $status=$this->isUpdate(true,$where)->save($arrayData);
   if($status){
       return $status;
   }
   return false;
  }
}