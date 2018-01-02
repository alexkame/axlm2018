<?php
	/*
	 * 通过webservice接口发送短信
	 * 开发环境：php7.0.9,windows10 专业版,Eclipse for PHP Developers
	 * 联系方式 ：346910917@qq.com,18611729367
	 * 版本：1.0
	 * 最近修订：2016-12-28
	 */

    include "MessageSend.class.php";
    
    $username="axlm_sms";							//改为实际账户名
    $password="Aa123123";							//改为实际短信发送密码
    $mobiles="15865943529";					//目标手机号码，多个用半角“,”分隔
    $content="php版soap接口发送测试,您的验证码：8888【华信】";
    $extnumber="";
    
    //定时短信发送时间,格式 2016-12-06T08:09:10+08:00，null或空串表示为非定时短信(即时发送)
    $plansendtime='';						    
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
?>