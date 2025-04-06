<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use think\cache;
use think\Db;
/**
 * 开机流程所需接口
 */
class Certificate extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $machinecar = null;
    protected $student = null;
    protected $coach = null;
    protected $device = null;
    protected $intentstudent = null;
    protected $space = null;
    protected $order = null;
    protected $temporaryorder = null;
    protected $admin = null;
    protected $authgroup = null;
    protected $authgroupaccess = null;
    protected $common = null;
    protected $cooperation = null;

    public function _initialize()
    {
        parent ::_initialize();
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->student = new \app\admin\model\Student;
        $this->coach = new \app\admin\model\Coach;
        $this->device = new \app\admin\model\Device;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->space = new \app\admin\model\Space;
        $this->order = new \app\admin\model\Order;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->admin = new \app\admin\model\Admin;
        $this->authgroup = new \app\admin\model\AuthGroup;
        $this->authgroupaccess = new \app\admin\model\AuthGroupAccess;
        $this->common = new \app\api\controller\Common;
        $this->cooperation = new \app\admin\model\Cooperation;

    }

    public function index()
    {
        if(empty($_POST['image']) || empty($_POST['stu_id'])){
            $this->error('缺少参数');
        }

        $image= $_POST['image'];
        $stu_id = $_POST['stu_id'];
        
        $name = md5(time().rand(1111,9999).'jpg').'.jpg';

        $name2 = md5(time().rand(1111,9999).'docx').'.docx';
        if (strstr($image,",")){
            $image = explode(',',$image);
            $image = $image[1];
        }
        $username = "min_img";
        //我们给每个用户动态的创建一个文件夹
        $title = "/uploads/".$username.'/'.date("Y-m-d");
        $user_path= $_SERVER['DOCUMENT_ROOT'].$title;
        if(!file_exists($user_path)) {
            //mkdir($user_path); 
            mkdir($user_path,0777,true); 
        }
        //将签名保存下来
        $r = file_put_contents($user_path.'/'.$name, base64_decode($image));
        // var_dump($r);exit;

        // $user_path = '/www/wwwroot/aivipdriver/public/uploads/min_img/2023-04-03/714a3a06d6e99671ca913194668b9290.jpg';
        $where['stu_id'] = $stu_id;
        $update['sign_path'] = $title.'/'.$name;
        $update['contract_state'] = 1;
        $student = $this->student->where($where)->find();

        $rotate = $this->image_rotate($user_path.'/'.$name, 90);
        if(!$rotate){
            $this->error('旋转图片失败');
        }

        $tmp = new \PhpOffice\PhpWord\TemplateProcessor(ROOT_PATH.'public'.$student['contract_path']);

        $tmp->setImageValue('学员签名', ['path' => $user_path.'/'.$name,'width'=>80,'height'=>38]);
        $tmp->setValue('签署日期', date('Y-m-d',time()));
        // $tmp->setImageValue('学员签名',['path' => $user_path,'width'=>80,'height'=>38]);
        $title2 = "/uploads/".date("Ymd");
        $path2 = $_SERVER['DOCUMENT_ROOT'].$title2;


        if(!file_exists($path2)){
            mkdir($path2,0777,true);
        }

        
        $tmp->saveAs($path2.'/'.$name2);

        
        // var_dump(ROOT_PATH.'public'.$student['contract_path']);
        if(file_exists($path2.'/'.$name2)){
            $update['contract_path'] = $title2.'/'.$name2;
            $student->where( $where)->update($update);
            unlink(ROOT_PATH.'public'.$student['contract_path']);
            $this->success('返回成功',$update['contract_path']);
        }
    }


    /**
    * 图片旋转
    * 温馨提示：如果图片旋转非90的倍数，可能会出现黑色的填充区域(圆形图片则不会)
    * @param $sourcePath string 图片路径
    * @param $degrees int 旋转的角度 (以逆时针方向旋转)
    * @return bool
    */

    function image_rotate($sourcePath, $degrees)
    {
        if(!file_exists($sourcePath)) return false;
        $original = getimagesize($sourcePath);
        //创建图像资源
        switch($original[2]){
            case 1 : $source = imagecreatefromgif($sourcePath);
                break;
            case 2 : $source = imagecreatefromjpeg($sourcePath);
                break;
            case 3 : $source = imagecreatefrompng($sourcePath);
                break;
            default:
            return false; //不支持的类型
                break;
        }
        if(empty($source)) return false;
        //旋转图片
        $rotate = imagerotate($source, $degrees, 0);
        //旋转后的图片保存
        switch($original[2])
        {
            case 1 : $success = imagegif($rotate,$sourcePath);
            break;
            case 2 : $success = imagejpeg($rotate,$sourcePath);
            break;
            case 3 : $success = imagepng($rotate,$sourcePath);
            break;
            default:
            $success = false; //不支持的类型
            break;
        }
        return $success;
    }
}

           