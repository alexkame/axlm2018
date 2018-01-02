<%@LANGUAGE="VBSCRIPT" CODEPAGE="65001"%>
<%
' 功能：批量付款到支付宝账户有密接口接入页
' 版本：3.3
' 日期：2012-07-17
' 说明：
' 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
' 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
	
' /////////////////注意/////////////////
' 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
' 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
' 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
' 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
' /////////////////////////////////////

%>
<html>
<head>
	<META http-equiv=Content-Type content="text/html; charset=utf-8">
<title>支付宝批量付款到支付宝账户有密接口</title>
</head>
<body>

<!--#include file="class/alipay_submit.asp"-->

<%
'/////////////////////请求参数/////////////////////

        '服务器异步通知页面路径
        notify_url = "http://商户网关地址/batch_trans_notify-ASP-UTF-8/notify_url.asp"
        '需http://格式的完整路径，不允许加?id=123这类自定义参数
        '付款账号
        email = Request.Form("WIDemail")
        '必填
        '付款账户名
        account_name = Request.Form("WIDaccount_name")
        '必填，个人支付宝账号是真实姓名公司支付宝账号是公司名称
        '付款当天日期
        pay_date = Request.Form("WIDpay_date")
        '必填，格式：年[4位]月[2位]日[2位]，如：20100801
        '批次号
        batch_no = Request.Form("WIDbatch_no")
        '必填，格式：当天日期[8位]+序列号[3至16位]，如：201008010000001
        '付款总金额
        batch_fee = Request.Form("WIDbatch_fee")
        '必填，即参数detail_data的值中所有金额的总和
        '付款笔数
        batch_num = Request.Form("WIDbatch_num")
        '必填，即参数detail_data的值中，“|”字符出现的数量加1，最大支持1000笔（即“|”字符出现的数量999个）
        '付款详细数据
        detail_data = Request.Form("WIDdetail_data")
        '必填，格式：流水号1^收款方帐号1^真实姓名^付款金额1^备注说明1|流水号2^收款方帐号2^真实姓名^付款金额2^备注说明2....

'/////////////////////请求参数/////////////////////

'构造请求参数数组
sParaTemp = Array("service=batch_trans_notify","partner="&partner,"_input_charset="&input_charset  ,"notify_url="&notify_url   ,"email="&email   ,"account_name="&account_name   ,"pay_date="&pay_date   ,"batch_no="&batch_no   ,"batch_fee="&batch_fee   ,"batch_num="&batch_num   ,"detail_data="&detail_data  )

'建立请求
Set objSubmit = New AlipaySubmit
sHtml = objSubmit.BuildRequestForm(sParaTemp, "get", "确认")
response.Write sHtml


%>
</body>
</html>
