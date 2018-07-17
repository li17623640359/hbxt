<?php
/**
 *
 * @Author: Lcs
 * @Date: 2018/7/4 16:32
 */

namespace app\mob\controller;

use app\user\model\UserActivityModel;
use app\user\model\UserCodeExtModel;
use app\user\model\UserCodeModel;
use app\user\model\UserModel;
use app\user\service\ActivityService;

class MobActivityController extends AdminComController
{
    /**
     * 活动列表
     * @date: 2018年6月7日上午11:27:26
     * @author: Hhb
     * @return mixed|string
     */
    public function index()
    {
        $param = $this->request->param();
        $where = array();
        if ($this->request->isAjax()) {
            if (!empty($param['activity_name'])) {
                $where['activity_name'] = $param['activity_name'];
            }
            $model = new UserActivityModel();
            $res = $model->getPageList($where);
            $res->appends($where);
            $this->assign('res', $res);
            $this->success('ok', '', array('list' => $res->toArray()));
        }
        $this->assign('param', $param);
        return $this->fetch();
    }

    /**
     * 活动详情
     * @date: 2018年6月11日下午2:57:07
     * @author: Hhb
     */
    public function detals()
    {
        $id = $this->request->param('id', 0, 'intval');
        $model = new UserActivityModel();
        $post = $model->getInfoById($id);
        $userModel = new UserModel();
        $user = $userModel->getInfoById($post['user_id']);
        $this->assign('user', $user);
        $this->assign('res', $post);
        $post['packet_ext']=json_decode($post['packet_ext'],true);
        $post['fixed_ext']=json_decode($post['fixed_ext'],true);
        return $this->fetch();
    }

    /**
     * 删除活动
     * @date: 2018年6月7日上午11:30:04
     * @author: Hhb
     */
    public function delete()
    {
        $param = $this->request->param();
        $post_model = new UserCodeModel();
        if (isset($param['id'])) {
            $result = $post_model->where(['id' => $param['id']])->delete();
            $this->success("删除成功！", '');
        }
        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $result = $post_model->where(['id' => ['in', $ids]])->delete();
            $this->success("删除成功！", '');
        }
    }

    /**
     * 查看红包详情
     * @date: 2018年6月13日下午12:08:16
     * @author: Hhb
     */
    public function details()
    {
        $id = $this->request->param('id', 0, 'intval');
        $code_num = $this->request->param('code_num', '');
        $activity_model = new UserActivityModel();
        $one = $activity_model->getDetailsById($id);
        $code_model = new UserCodeModel();
        $this->assign('one', $one);
        $activity_type = config('activity_type');
        $packet_type = config('packet_type');
        $this->assign('activity_type', $activity_type);
        $this->assign('packet_type', $packet_type);
        if ($this->request->isAjax() && $this->request->isPost()) {
            if ($one['type'] == 1) {
                $code = array();
                $res = $code_model->getActivityCodePageListById($id, $one['type'], 20, $code_num);
            } else {
                $res = $code_model->getActivityCodePageListById($id, $one['type']);
                if (!empty($res)) {
                    $code = $res;
                    $code_ext_model = new UserCodeExtModel();
                    $res = $code_ext_model->getActivityCodePageListById($id, $code['id']);
                } else {
                    $code = array();
                }
            }
            $this->success('ok', '', ['list' => $res->toArray()]);
        }
        $this->assign('code_num', $code_num);
        return $this->fetch();
    }

    /**
     * 获取用户余额以及二维码余量
     * @date: 2018年6月12日上午11:22:39
     * @author: Hhb
     */
    public function getUserData()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数异常');
        }
        $model = new UserModel();
        $user = $model->getInfoById($id);
        if (empty($user)) {
            $this->error('数据异常');
        }
        $code_model = new UserCodeModel();
        $user_code = $code_model->getUserCodeNums($id, 2);
        if (empty($user_code) || empty($user_code[0]['status'])) {
            $num = 0;
        } else {
            $num = $user_code[0]['total'];
        }

        $data = array(
            'code_num' => $num,
            'user_money' => $user['balance'] - $user['frozen_total']
        );
        $this->success('ok', '', $data);
    }

    /**
     *
     * @Author: Lcs
     * @Date: 2018/7/16 10:21
     */
    public function exportData()
    {
        $status = $this->request->param('status', 0, 'intval');
        $mobile = $this->request->param('mobile', '');
        $start = $this->request->param('start', '');
        $end = $this->request->param('end', '');
        $param = array(
            'status' => $status,
            'mobile' => $mobile,
            'start' => $start,
            'end' => $end
        );
        $where = array();
        if (!empty($param['status'])) {
            $where['status'] = $param['status'];
        }
        if (!empty($param['mobile'])) {
            $user_model = new UserModel();
            $user = $user_model->getInfoByMobile($param['mobile']);
            $where['user_id'] = $user['id'];
        }
        if (!empty($param['start'])) {
            $where['code_num'] = ['egt', $param['start']];
        }
        if (!empty($param['end'])) {
            $where['code_num'] = [['egt', empty($param['start']) ? 0 : $param['start']], ['elt', $param['end']]];
        }
        $model = new UserCodeModel();
        $res = $model->getDataList($where);
        if (empty($res)) {
            echo '<script>alert("暂时没有数据!");history.back();</script>';
            exit();
        }
        $root = CMF_ROOT . '/upload/';
        $fileList = array();
        foreach ($res as $v) {
            $fileList[] = $root . $v['code_img'];
        }
        $zip_url = cmf_zip_files('code', $fileList, '', true);
    }

    /**
     *删除活动
     * @Author: Ldm
     * @FunctionName:活动审核
     * @Date:2018/6/14 0014
     */
    public function examine()
    {
        $param = $this->request->param();
        $activity_model = new UserActivityModel();
        $status = $activity_model->isUpdate(true, ['id' => $param['id']])->save(['is_examine' => 1, 'update_time' => time()]);
        if (empty($status)) {
            $this->error("审核失败！", '');
        }
        return $this->success("审核成功！");
    }

    /**
     *开启/关闭活动
     * @Author: Lcs
     * @Date: 2018/7/16 10:20
     */
    public function editSite()
    {
        $param = $this->request->param();
        if (!isset($param['site']) || empty($param['id'])) {
            $this->error('参数异常');
        }
        $activityModel = new UserActivityModel();
        $result = $activityModel->editSiteById($param['id'], $param['site']);
        if ($result) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

}