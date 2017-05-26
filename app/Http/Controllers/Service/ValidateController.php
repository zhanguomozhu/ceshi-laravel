<?php

namespace App\Http\Controllers\Service;

use App\Tool\Validate\ValidateCode;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Tool\SMS\SendTemplateSMS;//融联云短信类
use App\Tool\SMS\Ucpaas;//云之讯短信类
use App\Entity\TempPhone;
use App\Models\M3Result;
use App\Entity\TempEmail;
use App\Entity\Member;

class ValidateController extends Controller
{
  //验证码
  public function create(Request $request)
  {
    $validateCode = new ValidateCode;
    $request->session()->put('validate_code', $validateCode->getCode());
    return $validateCode->doimg();
  }

  public function sendSMS(Request $request)
  {
    //初始化返回接口模型
    $m3_result = new M3Result;
    //前台获取手机号
    $phone = $request->input('phone', '');
    //如果为空
    if($phone == '') {
      $m3_result->status = 1;
      $m3_result->message = '手机号不能为空';
      return $m3_result->toJson();
    }
    //格式不对
    if(strlen($phone) != 11 || $phone[0] != '1') {
      $m3_result->status = 2;
      $m3_result->message = '手机格式不正确';
      return $m3_result->toJson();
    }


  //************************************************融云连短信接口start*************************************************
  /*$sendTemplateSMS = new SendTemplateSMS;
    $code = '';
    $charset = '1234567890';
    $_len = strlen($charset) - 1;
    for ($i = 0;$i < 6;++$i) {
        $code .= $charset[mt_rand(0, $_len)];
    }
    $m3_result = $sendTemplateSMS->sendTemplateSMS($phone, array($code, 60), 1);
    */
    
    //*************************************************************end******************************************
  
    //**********************************************云之讯短信接口start*****************************************************
      //初始化必填
      $options['accountsid']='17fef39499570514068fb9ea4724f95c'; //填写自己的accountsid
      $options['token']='a53dcd9c5d676d018b9bb8b74b87251f'; //填写自己的token
      //初始化 $options必填
      $ucpass = new Ucpaas($options);         
      //随机生成6位验证码
      $authnum='';
      srand((double)microtime()*1000000);//create a random number feed.
      $ychar="0,1,2,3,4,5,6,7,8,9";
      $list=explode(",",$ychar);
      for($i=0;$i<6;$i++){
        $randnum=rand(0,9); // 10+26;
        $authnum.=$list[$randnum];//生成的6位验证码
      }
      //短信验证码（模板短信）,默认以65个汉字（同65个英文）为一条（可容纳字数受您应用名称占用字符影响），超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
      $appId = "64b405fd15e44b5590cf17d52f2002e0";  //填写自己的appId
      $templateId = "43083";//短信模板编号
      //把生成的验证码拼接手机号生成md5码存储到session***用于ajax验证***********************************【重要】
      //$_SESSION['msgcode']=md5($authnum.$phone);//这里使用验证码存储到数据库
      $arr=$ucpass->templateSMS($appId,$phone,$templateId,$authnum);
      //print_r($arr);$arr返回json数据{"resp":{"respCode":"000000","templateSMS":{"createDate":"20170525134149","smsId":"916846453603c64b95338de0792b7f68"}}}
      if (substr($arr,21,6) == 000000) {
          //如果成功就，这里只是测试样式，可根据自己的需求进行调节
          //echo "短信验证码已发送成功，请注意查收短信"; 
          $m3_result->status = 0;
          $m3_result->message = '短信验证码已发送成功，请注意查收短信'; 
      }else{
          //如果不成功
          //echo "短信验证码发送失败，请联系客服"; 
          $m3_result->status = 3;
          $m3_result->message = '短信验证码发送失败，请联系客服';  
      }
      //*************************************************************end******************************************
     if($m3_result->status == 0) {
      $tempPhone = TempPhone::where('phone', $phone)->first();
      if($tempPhone == null) {
        $tempPhone = new TempPhone;
      }
      $tempPhone->phone = $phone;//手机号
      $tempPhone->code = $authnum;//验证码
      $tempPhone->deadline = date('Y-m-d H-i-s', time() + 60*60);//1小时过期
      $tempPhone->save();

      //修改会员表active状态吗
      $member = Member::find($phone);
      $member->active = 1;
      $member->save();
    }

   return $m3_result->toJson();
  }

  public function validateEmail(Request $request)
  {
    $member_id = $request->input('member_id', '');
    $code = $request->input('code', '');
    if($member_id == '' || $code == '') {
      return '验证异常';
    }

    $tempEmail = TempEmail::where('member_id', $member_id)->first();
    if($tempEmail == null) {
      return '验证异常';
    }

    if($tempEmail->code == $code) {
      if(time() > strtotime($tempEmail->deadline)) {
        return '该链接已失效';
      }

      $member = Member::find($member_id);
      $member->active = 1;
      $member->save();

      return redirect('/login');
    } else {
      return '该链接已失效';
    }
  }
}
