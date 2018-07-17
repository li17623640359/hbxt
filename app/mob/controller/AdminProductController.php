<?php
/**
 * 商品管理主控制器
 *
 * @date: 2018年4月12日上午9:17:41
 * @author: Hhb
 */
namespace app\mob\controller;


use think\Db;
use think\Validate;
use app\mob\model\MobBrandModel;
use app\mob\model\MobProductModel;
use Three\BaiduImg;
use app\mob\model\MobBaiduLogModel;

class AdminProductController extends AdminComController
{
    /**
     * 商品管理首页
     * @date: 2018年4月12日上午9:26:09
     * @author: Hhb
     */
    public function index()
    {
        $data = Db::name('mobProduct')->field("COUNT(*) AS total,brand_type")->group("brand_type")->select()->toArray();
        $total = ['watch'=>0,'bag'=>0,'product'=>0];
        if(!empty($data)){
            foreach ($data as $v){
                if($v['brand_type'] == 1){
                    $total['watch'] = $v['total'];
                    $total['product'] += $total['watch'];
                }elseif($v['brand_type'] == 2){
                    $total['bag'] = $v['total'];
                    $total['product'] += $total['bag'];
                }
            }
        }
        $this->assign('total',$total);
        return $this->fetch();
    }
    /**
     * 品牌管理
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function brand(){
        if($this->request->isAjax()){
            $model = new MobBrandModel();
            $data = $model->getPageList();
            $data = $data->toArray();
            $brand = config('brand_type');
            if(!empty($data['data'])){
                foreach ($data['data'] as &$v){
                    $v['b_type_name'] = isset($brand[$v['b_type']]) ? $brand[$v['b_type']] : '未知';
                    unset($v);
                }
            }
            $this->success('ok','',['list' => $data]);
        }
        return $this->fetch();
    }
    /**
     * 添加品牌
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function brandAdd(){
        $brand = config('brand_type');
        $this->assign('brand',$brand);
        return $this->fetch();
    }
    /**
     * 添加品牌提交
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function brandAddPost(){
        if ($this->request->isPost()) {
            $validate = new Validate([
                'b_name'  => 'require',
                'b_type' => 'require',
            ]);
            $validate->message([
                'b_name.require' => '品牌分类名称不能为空',
                'b_type.require' => '请选择品牌分类',
            ]);
            
            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $model = new MobBrandModel();
            $re = $model->adminAddData($data);
            if($re){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->error('无效请求');
        }
    }
    /**
     * 编辑品牌
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function brandEdit(){
        $id = $this->request->param("id", 0, 'intval');
        if(empty($id)){
            $this->redirect('mob/AdminProduct/brand');
        }
        $brand = config('brand_type');
        $model = new MobBrandModel();
        $data = $model->getInfoById($id);
        $this->assign('data',$data);
        $this->assign('brand',$brand);
        return $this->fetch();
    }
    /**
     * 编辑品牌提交
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function brandEditPost(){
        if ($this->request->isPost()) {
            $id = $this->request->param('id',0,'intval');
            if(empty($id)){
                $this->error('非法请求');
            }
            $validate = new Validate([
                'b_name'  => 'require',
                'b_type' => 'require',
            ]);
            $validate->message([
                'b_name.require' => '品牌分类名称不能为空',
                'b_type.require' => '请选择品牌分类',
            ]);
        
            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $model = new MobBrandModel();
            $re = $model->adminEditData($data);
            if($re){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $this->error('无效请求');
        }
    }
    /**
     * 删除品牌
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function brandDelete(){
        $id = $this->request->param("id", 0, 'intval');
        if (empty($id)) {
            $this->error("参数异常");
        }
        $model = new MobBrandModel();
        $data = $model->getInfoById($id);
        if(empty($data)){
            $this->error("品牌分类不存在");
        }
        $count = 0;
        if($data['b_type']==1){
            $count = Db::name('mobProduct')->where(['pro_type'=>$id])->count();
        }elseif($data['b_type']==2){
            $count = Db::name('mobBag')->where(['pro_type'=>$id])->count();
        }
        if($count > 0){
            $this->error("该品牌分类下有商品！");
        }else{
            $status = Db::name('mobBrand')->delete($id);
            if (!empty($status)) {
                $this->success("删除成功！", url('mob/AdminProduct/brand'));
            } else {
                $this->error("删除失败！");
            }
        }
    }
    /**
     * 名表管理
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function watch(){
        if($this->request->isAjax()){
            $model = new MobProductModel();
            $data = $model->getPageList(['brand_type'=>1]);
            $data = $data->toArray();
            $this->success('ok','',['list' => $data]);
        }
        return $this->fetch();
    }
    /**
     * 添加名表
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function watchAdd(){
        $brand_model = new MobBrandModel();
        $brand = $brand_model->getAllList(['b_type'=>1]);
        $this->assign('brand',$brand);
        return $this->fetch();
    }
    /**
     * 添加名表提交
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function watchAddPost(){
        if($this->request->isPost()){
            $data = $this->request->param();
            $result = $this->validate($data, 'Product');
            if ($result !== true) {
                $this->error($result);
            }
            $data['brand_type'] = 1;
            $model = new MobProductModel();
            $re = $model->adminAddData($data);
            if($re){
                if(!empty($data['file_id']) && !empty($data['pro_img_url'])){
                    $options = [
                        'brief'=>array('file_id'=>$data['file_id'],'pro_id'=>$re),
                    ];
                    $rs = BaiduImg::similarAdd($data['pro_img_url'],$options);
                    $log = new MobBaiduLogModel();
                    $log->writeLog($rs, 1,$re);
                }
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->error("无效操作！");
        }
    }
    /**
     * 编辑名表
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function watchEdit(){
        $id = $this->request->param("id", 0, 'intval');
        if(empty($id)){
            $this->redirect('mob/AdminProduct/watch');
        }
        $model = new MobProductModel();
        $data = $model->getInfoById($id,['brand_type'=>1]);
        if(empty($data)){
            $this->redirect('mob/AdminProduct/watch');
        }
        $brand_model = new MobBrandModel();
        $brand = $brand_model->getAllList(['b_type'=>1]);
        $this->assign('data',$data);
        $this->assign('brand',$brand);
        return $this->fetch();
    }
    /**
     * 编辑名表提交
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function watchEditPost(){
        if($this->request->isPost()){
            $data = $this->request->param();
            if(!isset($data['more'])){
                $data['more'] = [];
            }
            $result = $this->validate($data, 'Product.edit');
            if ($result !== true) {
                $this->error($result);
            }
            $model = new MobProductModel();
            $re = $model->adminEditData($data);
            if($re){
                if($data['old_file_id'] != $data['file_id']){
                    $options = [
                        'brief'=>array('file_id'=>$data['file_id'],'pro_id'=>$data['id']),
                    ];
                    $rs = BaiduImg::similarAdd($data['pro_img_url'],$options);
                    $log = new MobBaiduLogModel();
                    $log->writeLog($rs, 1,$data['id']);
                }
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $this->error("无效操作！");
        }
    }
    /**
     * 删除名表
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function watchDelete(){
        $id = $this->request->param("id", 0, 'intval');
        if (empty($id)) {
            $this->error("参数异常");
        }
        $model = new MobProductModel();
        $data = $model->getInfoById($id,['brand_type'=>1]);
        if(empty($data)){
            $this->error("商品不存在");
        }
        $status = Db::name('mobProduct')->delete($id);
        if (!empty($status)) {
            if(!empty($data['file_id']) && !empty($data['pro_img_url'])){
                $ob = BaiduImg::similarDeleteByImage($data['pro_img_url']);
                $log = new MobBaiduLogModel();
                $log->writeLog($ob, 3, $data['id']);
            }
            $this->success("删除成功！", url('mob/AdminProduct/watch'));
        } else {
            $this->error("删除失败！");
        }
    }
    /**
     * 品包管理
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function bag(){
        if($this->request->isAjax()){
            $model = new MobProductModel();
            $data = $model->getPageList(['brand_type'=>2]);
            $data = $data->toArray();
            $this->success('ok','',['list' => $data]);
        }
        return $this->fetch();
    }
    /**
     * 添加名包
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function bagAdd(){
        $brand_model = new MobBrandModel();
        $brand = $brand_model->getAllList(['b_type'=>2]);
        $this->assign('brand',$brand);
        return $this->fetch();
    }
    /**
     * 添加名包提交
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function bagAddPost(){
        if($this->request->isPost()){
            $data = $this->request->param();
            $result = $this->validate($data, 'Product');
            if ($result !== true) {
                $this->error($result);
            }
            $data['brand_type'] = 2;
            $model = new MobProductModel();
            $re = $model->adminAddData($data);
            if($re){
                if(!empty($data['file_id']) && !empty($data['pro_img_url'])){
                    $options = [
                        'brief'=>array('file_id'=>$data['file_id'],'pro_id'=>$re),
                    ];
                    $rs = BaiduImg::similarAdd($data['pro_img_url'],$options);
                    $log = new MobBaiduLogModel();
                    $log->writeLog($rs, 1,$re);
                }
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->error("无效操作！");
        }
    }
    /**
     * 编辑名包
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function bagEdit(){
        $id = $this->request->param("id", 0, 'intval');
        if(empty($id)){
            $this->redirect('mob/AdminProduct/bag');
        }
        $model = new MobProductModel();
        $data = $model->getInfoById($id,['brand_type'=>2]);
        if(empty($data)){
            $this->redirect('mob/AdminProduct/bag');
        }
        $brand_model = new MobBrandModel();
        $brand = $brand_model->getAllList(['b_type'=>2]);
        $this->assign('data',$data);
        $this->assign('brand',$brand);
        return $this->fetch();
    }
    /**
     * 编辑名包提交
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function bagEditPost(){
        if($this->request->isPost()){
            $data = $this->request->param();
            $result = $this->validate($data, 'Product.edit');
            if ($result !== true) {
                $this->error($result);
            }
            $model = new MobProductModel();
            $re = $model->adminEditData($data);
            if($re){
                if($data['old_file_id'] != $data['file_id']){
                    $options = [
                        'brief'=>array('file_id'=>$data['file_id'],'pro_id'=>$data['id']),
                    ];
                    $rs = BaiduImg::similarAdd($data['pro_img_url'],$options);
                    $log = new MobBaiduLogModel();
                    $log->writeLog($rs, 1,$data['id']);
                }
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $this->error("无效操作！");
        }
    }
    /**
     * 删除名包
     * @date: 2018年4月17日下午5:51:08
     * @author: Hhb
     */
    public function bagDelete(){
        $id = $this->request->param("id", 0, 'intval');
        if (empty($id)) {
            $this->error("参数异常");
        }
        $model = new MobProductModel();
        $data = $model->getInfoById($id,['brand_type'=>2]);
        if(empty($data)){
            $this->error("商品不存在");
        }
        $status = Db::name('mobProduct')->delete($id);
        if (!empty($status)) {
            if(!empty($data['file_id']) && !empty($data['pro_img_url'])){
                $ob = BaiduImg::similarDeleteByImage($data['pro_img_url']);
                $log = new MobBaiduLogModel();
                $log->writeLog($ob, 3, $data['id']);
            }
            $this->success("删除成功！", url('mob/AdminProduct/bag'));
        } else {
            $this->error("删除失败！");
        }
    }
}
