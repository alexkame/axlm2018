<?php
header("Content-Type:text/html;charset=utf-8");

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
    //$mobiles="15865943529";					//目标手机号码，多个用半角“,”分隔
    $mobiles=$mobile;					//目标手机号码，多个用半角“,”分隔
    //$content="php版soap接口发送测试,您的验证码：8888【华信】";
    $extnumber="";
    
    //定时短信发送时间,格式 2016-12-06T08:09:10+08:00，null或空串表示为非定时短信(即时发送)
    $plansendtime=$time;						    
    //$plansendtime='2016-12-06T08:09:10+08:00'
    $result=WsMessageSend::send($username, $password, $mobiles, $content,$extnumber,$plansendtime);

$fp = fopen(ROOT_PATH . 'smslog/smslog.txt' , "a+");
fwrite($fp, "[mobile]=================:Date:".date("Y-m-d H:i",time())."================\r\n");
fwrite($fp, "mobiles: ".$mobiles."\r\n");
fwrite($fp, "content: ".$content."\r\n");
fwrite($fp, "=================================\r\n");

fclose($fp);

/*
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
*/
	
	if(trim($result->StatusCode) == 'Success')
	
	{
		return "发送成功!";
	}
	else 
	{
		return "发送失败! 状态：".$result->Description."--".$result->StatusCode;
	}
}

/*--------------------------------
功能:HTTP接口 发送短信
修改日期:	2009-04-08
说明:		http://http.yunsms.cn/tx/?uid=用户账号&pwd=MD5位32密码&mobile=号码&content=内容
状态:
	100 发送成功
	101 验证失败
	102 短信不足
	103 操作失败
	104 非法字符
	105 内容过多
	106 号码过多
	107 频率过快
	108 号码内容空
	109 账号冻结
	110 禁止频繁单条发送
	111 系统暂定发送
	112	有错误号码
	113	定时时间不对
	114	账号被锁，10分钟后登录
	115	连接失败
	116 禁止接口发送
	117	绑定IP不正确
	120 系统升级
--------------------------------*/
//$uid = '9999';		//用户账号
//$pwd = '9999';		//密码
//$mobile	 = '13912341234,13312341234,13512341234,02122334444';	//号码
//$content = '你好，验证码：1019【云信】';		//内容
//即时发送
//$res = sendSMS($uid,$pwd,$mobile,$content);
//echo $res;

//定时发送
/*
$time = '2010-05-27 12:11';
$res = sendSMS($uid,$pwd,$mobile,$content,$time);
echo $res;
*/

/*
function sendSMS($mobile,$content,$time='',$mid='')
{
	$content = iconv('utf-8','gbk',$content);
	$http = 'http://http.yunsms.cn/tx/';
	$uid = '2105111304'; // 用户账号
	$pwd = '3ed0b3'; // 密码
	$data = array
		(
		'uid'=>$uid,					//用户账号
		'pwd'=>strtolower(md5($pwd)),	//MD5位32密码
		'mobile'=>$mobile,				//号码
		'content'=>$content,			//内容 如果对方是utf-8编码，则需转码iconv('gbk','utf-8',$content); 如果是gbk则无需转码
		'time'=>$time,		//定时发送
		'mid'=>$mid						//子扩展号
		);
	$re= postSMS($http,$data);			//POST方式提交
	if( trim($re) == '100' )
	{
		return "发送成功!";
	}
	else
	{
		return "发送失败! 状态：".$re;
	}
}

function postSMS($url,$data='')
{
	$port = $post = '';
	$row = parse_url($url);
	$host = $row['host'];
	$port = isset($row['port']) ? $row['port']:80;
	$file = $row['path'];
	while (list($k,$v) = each($data))
	{
		$post .= rawurlencode($k)."=".rawurlencode($v)."&";	//转URL标准码
	}
	$post = substr( $post , 0 , -1 );
	$len = strlen($post);
	$fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
	if (!$fp) {
		return "$errstr ($errno)\n";
	} else {
		$receive = '';
		$out = "POST $file HTTP/1.1\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n\r\n";
		$out .= $post;
		fwrite($fp, $out);
		while (!feof($fp)) {
			$receive .= fgets($fp, 128);
		}
		fclose($fp);
		$receive = explode("\r\n\r\n",$receive);
		unset($receive[0]);
		return implode("",$receive);
	}
}
*/
?>