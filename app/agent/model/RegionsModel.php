<?php
/**
 * Created by PhpStorm.
 * User: ldm
 * Date: 2018/6/4 0004
 * Time: 12:37
 */

namespace app\agent\model;


use think\Model;

class RegionsModel extends Model
{
    /**
     *
     * @param string $where
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @Author: Ldm
     * @FunctionName:查询地址表的数据
     * @Date:2018/7/9 0009
     */
   public function regions($where=''){
       $data=$this->where($where)->select()->toArray();
       if($data){
       return $data;
       }
       return false;
   }
}