<?php


namespace app\admin\controller;


use app\admin\model\Company;
use app\admin\model\ManagerPc;
use think\Request;
use think\Db;

class Corporation extends Admin
{
    public function index(Request $request,Company $company){
        if($request->isAjax()){
            $params = $request->param();
            $where = array();
            $page = isset($params['page']) ? $params['page'] : 1;
            $limit = isset($params['limit']) ? $params['limit'] : 1;
            $guanlis = $company->with(['legaler'=>function($query){
                $query->field('id,real_name');
            }])->where($where)->page($page,$limit)->all();
            foreach($guanlis as $key => $guanli){
                $guanlis[$key]['legaler1'] = $guanli->legaler->real_name;
            }
            $zong = $company->where($where)->count();
            return array('code'=> 0, 'msg' => '', 'count' => $zong, 'data' => $guanlis);
        }else{
            return $this->fetch();
        }
    }

    public function add_corporation(Request $request,Company $company,ManagerPc $managerPc){
        if ($request->isAjax()){
            $params = $request->param();
            $params['menu_link'] = 'wu';
            Db::startTrans();
            try {
                //储存公司信息
                $company->save($params);
                //修改制定法人的所属公司
                $managerPc->save(['company_id' =>$company->id,['id' => $params['legal_person']]]);
                return ['code' => 200, 'msg' => lang('add_ok')];
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
//                // 回滚事务
                Db::rollback();
                return ['code' => 201, 'msg' => lang('add_fail')];
            }
        }else{
            $dailis = $managerPc->where(['company_id' => 1  , 'is_delete' => 1])
                                ->field('id,real_name')->all();
            $this->assign('dailis',$dailis);
            return $this->fetch();
        }
    }

    public function edit_company(Request $request,Company $company,ManagerPc $managerPc){
        $id = $request->param('id');
        if ($request->isAjax()){
            $params = $request->param();
            unset($params['id']);
            Db::startTrans();
            try {
                //储存公司信息
                $company->save($params,['id' =>$id]);
                //修改制定法人的所属公司
                $managerPc->save(['company_id' =>$id,['id' => $params['legal_person']]]);
                return ['code' => 200, 'msg' => lang('edit_ok')];
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return ['code' => 201, 'msg' => lang('eidt_fail')];
            }
        }else{
            $dailis = $managerPc->where(['company_id' => 1, 'role' => 2, 'is_delete' =>1])
                                ->field('id,real_name')->all();
            $this->assign('dailis',$dailis);
            $corporation = $company->get($id);
            $this->assign('corporation',$corporation);
            return $this->fetch();
        }
    }

    public function del_company(){

    }

}