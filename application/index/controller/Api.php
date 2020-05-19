<?php
namespace app\index\controller;
use think\Controller;
use app\index\model\Rules;
use think\Db;

/**
 * 统一api类
 * Class Api
 * @package app\index\controller
 */
class Api extends Controller{
    /**
     * 文件上传
     * @return \think\response\Json
     */
    public function upload(){
        try{
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            if($file){
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                if($info){
                    // 成功上传后 获取上传信息

                    $json = [
                        'ext' => $info->getExtension(),
                        'path' => DS.'uploads'.DS.$info->getSaveName(),
                        'fileName' => $info->getFilename()
                    ];

                    return json(['code'=>0,'msg'=>'ok','data'=>$json]);
                }else{
                    // 上传失败获取错误信息
                    return json(['code'=>-1,'msg'=>$file->getError()]);
                }
            }else{
                return json(['code'=>-1,'msg'=>'未找到上传的文件']);
            }
        }catch (\Exception $e){
            return json(['code'=>-1,'msg'=>$e->getMessage()]);
        }
    }

    /**
     * tree二叉树数组
     */
    public function treeData(){
        if(request()->isPost()){
            $params = request()->post();
            $menu = (new Rules)->getMenu('All',$params['group_rules_old'],true);
            return json(['code'=>0,'msg'=>'获取成功','data'=>$menu]);
        }else{
            return json(['code'=>-1,'msg'=>'访问错误']);
        }
    }

    /**
     * 过滤角色权限中的顶级菜单编号
     */
    public function notParentRules(){
        if(request()->isPost()){
            $params = request()->post();
            if(!empty($params['nodeStr'])){
                $rules = new Rules();
                $result = $rules->where('id in('.$params['nodeStr'].')')->select();
                $menus = $rules->nodeParentMenu($result);
            }else{
                $menus = [];
            }
            return json(['code'=>0,'msg'=>'获取成功','data'=>$menus]);
        }else{
            return json(['code'=>-1,'msg'=>'访问错误']);
        }
    }

    /**
     * 用户ajax列表数据
     */
    public function admin_list(){
        $param = request()->get();
        $where = ' 1=1 and oil_admin.id != 1 ';
        if(!empty($param['name'])){
            $where = $where.' and (oil_admin.user_name like "%'.$param['name'].'%")';
        }
        //数据总数
        $count = Db::table('oil_admin')
            ->field(['oil_admin.*','oil_group.group_name'])
            ->join('oil_group_rules', 'oil_admin.id=oil_group_rules.uid','LEFT')
            ->join('oil_group', 'oil_group_rules.role_id=oil_group.id','LEFT')
            ->where($where)
            ->count();
        //总页数
        $totalPage = ceil($count/8);
        //分页数据
        $admin = Db::table('oil_admin')
            ->field(['oil_admin.*','oil_group.group_name'])
            ->join('oil_group_rules', 'oil_admin.id=oil_group_rules.uid','LEFT')
            ->join('oil_group', 'oil_group_rules.role_id=oil_group.id','LEFT')
            ->where($where)
            ->order('oil_admin.id','desc')->paginate(8);
        //返回值
        return json(['code'=>0,'msg'=>'','pages'=>$totalPage,'data'=>$admin]);
    }
}