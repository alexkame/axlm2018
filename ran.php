<?php
error_reporting(1);

 class WsMessageSend
    {
    	const wsdl="https://dx.ipyy.net/webservice.asmx?wsdl";
    	
    	static function send($username,$password,$mobiles,$content,$extnumber,$plansendtime=null)
    	{
    		$client=new SoapClient(self::wsdl);
    		$sms=array(
    				'Msisdns'=>$mobiles,
    				'SMSContent'=>$content,
    				'ExtNumber'=>$extnumber,
    		);
    		if($plansendtime!=null && $plansendtime!=''){
    			$sms['PlanSendTime']=$plansendtime;
    		}
    		//print_r($sms);
    		$body=array(
    				'userName'=>$username,
    				'password'=>$password,
					'sms'=>$sms
    		);
     		$result=$client->__call("SendSms", array($body));
    		//$client->__soapCall("SendSms", array($body));
    		if(is_soap_fault($result))
    		{
    			echo "faultcode:",$result->faultcode,"faultstring:",$result->faultstring;
    			return null;
    		}
    		else 
    		{
    			$data=$result->SendSmsResult;
    			return $data;
    		}
    	}
    }
	
	function sendSMS($mobile,$content,$time='',$mid='')
{
	//$content = iconv('utf-8','gbk',$content);	    
    $username="axlm_sms";							//改为实际账户名
    $password="Aa123123";							//改为实际短信发送密码
    $mobiles="15865943529";					//目标手机号码，多个用半角“,”分隔
    //$content="php版soap接口发送测试,您的验证码：8888【华信】";
    $extnumber="";
    
    //定时短信发送时间,格式 2016-12-06T08:09:10+08:00，null或空串表示为非定时短信(即时发送)
    $plansendtime=$time;						    
    //$plansendtime='2016-12-06T08:09:10+08:00'
    $result=WsMessageSend::send($username, $password, $mobiles, $content,$extnumber,$plansendtime);

    if($result==null)
    {
    	echo "接口调用失败";
    }
    else
    {
    	//print_r($result);
        echo "返回信息提示：",$result->Description,"\n";
        echo "返回状态为：",$result->StatusCode,"\n";
        echo "返回余额：",$result->Amount,"\n";
        //echo "返回本次任务ID：",$result->MsgId,"\n";
        echo "返回成功短信数：",$result->SuccessCounts,"\n";
    }

}

$mobile_code = 123543;
	// 短信内容
	//$content = sprintf($_LANG['mobile_code_template'], $GLOBALS['_CFG']['shop_name'], $mobile_code, $GLOBALS['_CFG']['shop_name']);
	$content = "【品瑞嘉】您的验证码为：".$mobile_code;
	/* 发送激活验证邮件 */
	// $result = true;
	$result = sendSMS($mobile_phone, $content);
	
?>

?>