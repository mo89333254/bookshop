<?php
namespace app\order\controller;
use think\Db;
use think\View;
use think\Request;
use think\Cache;
use think\Session;
use think\Exception;
class Order extends \think\Controller
{

	
	public function index()
	{
		$view = new View();
		$request = Request::instance();
		$code = input('get.code');
		$tlist = Db::table('foodtype')->where('UserType','1')->select();
		
		foreach($tlist as $n=> $val){  
			$tlist[$n]['voo'] = Db::table('fooddetail') ->where('FoodType',$val['ID'])->select();  
		}  
		

		
		
		
		$view ->tlist = $tlist;
		$view ->code = $code;
		return $view -> fetch();
	}
	
	public function pay($code)
	{
		$view = new View();		
		
		$request = Request::instance();
		
		
		$ip= request()->ip();		
		$url = urldecode($request->url(true));
		$openid = $this->GetOpenID($code);		
		$orderresult = $this -> GetOrder($openid);		
		$appid = config('wpay.appid');
		$time = time();
		$noncestr = $this -> CreateNoncestr();
		$jsapi_ticket = $this -> GetTicket();
		$token = $this -> GetToken();
		
		$signtype = 'MD5';
		$data = [
			'jsapi_ticket' =>$jsapi_ticket,
			'noncestr' => $noncestr,
			'timestamp' => $time,
			'url' => $url,
		];
		

		$paydata = [
			'appId' =>$appid,
			'nonceStr' => $noncestr,
			'package' => 'prepay_id='.$orderresult['prepay_id'],
			'signType' => $signtype,
			'timeStamp' => $time,
		];	
		
		$params = urldecode(http_build_query($paydata)).'&key=1E36A15161292BE7CA18C3F2C5FDAB58';
		

		$signature = $this -> ToSign($data,true);
		$paysign = strtoupper(md5($params));			
		
		$view->assign('appid',$appid);
        $view->assign('noncestr',$noncestr);
		$view->assign('time',$time);
		$view->assign('sign',$signature);
		$view -> assign('signtype',$signtype);
		 
		 
		$view -> assign('prepayid',$orderresult['prepay_id']);
		$view -> assign('paysign',$paysign);
		
		return $view -> fetch();
	}
	
	public function wpay()
	{

		
		$view = new View();
		$request = Request::instance();
		$code = input('get.code');
		
		$ip= request()->ip();
		
		


		
		$url = urldecode($request->url(true));
		
		
		$openid = $this->GetOpenID($code);
		
		$orderresult = $this -> GetOrder($openid);
		
		$appid = config('wpay.appid');
		
		$time = time();
		$noncestr = $this -> CreateNoncestr();
		$jsapi_ticket = $this -> GetTicket();
		$token = $this -> GetToken();

		$signtype = 'MD5';
		$data = [
			'jsapi_ticket' =>$jsapi_ticket,
			'noncestr' => $noncestr,
			'timestamp' => $time,
			'url' => $url,
		];
		

		$paydata = [
			'appId' =>$appid,
			'nonceStr' => $noncestr,
			'package' => 'prepay_id='.$orderresult['prepay_id'],
			'signType' => $signtype,
			'timeStamp' => $time,
		];	
		
		$params = urldecode(http_build_query($paydata)).'&key=1E36A15161292BE7CA18C3F2C5FDAB58';
		

		$signature = $this -> ToSign($data,true);
		$paysign = strtoupper(md5($params));

		
		$view->assign('appid',$appid);
        $view->assign('noncestr',$noncestr);
		$view->assign('time',$time);
		$view->assign('sign',$signature);
		$view -> assign('signtype',$signtype);
		 
		 
		$view -> assign('prepayid',$orderresult['prepay_id']);
		$view -> assign('paysign',$paysign);
		 
		return $view -> fetch();
	}
	
	///http请求类
	public function http($url, $params, $method = 'GET', $header = array(), $multi = false)
	{

		$opts = array(
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HTTPHEADER     => $header
		);
		/* 根据请求类型设置特定参数 */
		switch(strtoupper($method)){
			case 'GET':
				$opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
				break;
			case 'POST':
				//判断是否传输文件
				$params = $multi ? $params : http_build_query($params);
				$opts[CURLOPT_URL] = $url;
				$opts[CURLOPT_POST] = 1;
				$opts[CURLOPT_POSTFIELDS] = $params;
				$opts[CURLOPT_SSLCERTTYPE] = 'PEM';
				$opts[CURLOPT_SSLCERT] = '/var/www/html/thinkphp/public/apiclient_cert.pem';
				$opts[CURLOPT_SSLKEYTYPE] = 'PEM';
				$opts[CURLOPT_SSLKEY] = '/var/www/html/thinkphp/public/apiclient_key.pem';
				break;
			default:
				throw new Exception('不支持的请求方式！');
		}
		/* 初始化并执行curl请求 */
		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		$data  = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if($error) throw new Exception('请求发生错误：' . $error);
		return  $data;
	}
	
	///获取token
	public function GetToken()
	{

		
		if(cache('token'))
		{
			return cache('token');
		}
		else
		{
		$url = config('wpay.gettokenurl');
		//定义传递的参数数组；
		$data['grant_type'] = 'client_credential';
		$data['appid']=config('wpay.appid');
		$data['secret']=config('wpay.appsecret');
		
		
		//定义返回值接收变量；
		$httpstr = $this ->http($url, $data, 'POST', array("charset=utf-8"),false);
		$result = json_decode($httpstr);
		
		$token = $result->access_token;
		if($token){
			
			cache('token',$result->access_token,7150);
			return $result->access_token;
		}
		else
		{
			return $this ->getMessage();
		}
		}
	}
	
	public function GetTicket()
	{

		
		$ticket = cache('secretticket');
		if($ticket)
		{
			return $ticket;
		}
		else
		{
			$url = config('wpay.getsecreturl');
			//定义传递的参数数组；
			$data['access_token'] = $this ->GetToken();
			$data['type'] = 'jsapi';
			
			
			
			//定义返回值接收变量；
			$httpstr = $this ->http($url, $data, 'POST', array("charset=utf-8"),false);
			$result = json_decode($httpstr);
			
			$code = $result -> errcode;
		
			if($code == 0)
			{
				cache('secretticket',$result->ticket,7150);
				return $result->ticket;
			}
			else
			{
				return $this -> getMessage();
			}
		}
	}
	
	///签名（sha1）
	public function ToSign($params,$decode = false)
	{
		$bparams = http_build_query($params);
		if($decode)
		{
			$bparams = urldecode($bparams);
		}
		
		$sign = sha1($bparams);
		return $sign;
		
	}
	
	
	///随机生成字符串 默认32位
	public function CreateNoncestr( $length = 32 )
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ ) {
		$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}
	
	///获取网页授权token
	public function GetOpenID($code = 0)
	{
		if(session('openid'))
		{
			return session('openid');
		}
		else
		{	
			$url = config('wpay.getoua2tokenurl');
			//定义传递的参数数组；
			$data['grant_type'] = 'authorization_code';
			$data['appid']=config('wpay.appid');
			$data['secret']=config('wpay.appsecret');
			$data['code'] = $code;
					
			//定义返回值接收变量；
			$httpstr = $this ->http($url, $data, 'POST', array("charset=utf-8"),false);
					
			$result = json_decode($httpstr);
			try
			{		
			//cache('oua2_token',$result->access_token,7150);
			//cache('refresh_token',$result->refresh_token,108000);
			session('openid',$result->openid);			
			return $result->openid;
			}
			catch(\Exception $e)
			{
				print $httpstr;          
				exit();   
			}
		}	
		
	}
	
	public function GetOrder($opid)
	{
		$request = Request::instance();		
		$ip = request()->ip();
		$url = config('wpay.unifiedorderurl');
		$appid = config('wpay.appid');
		$mch_id = config('wpay.mchid');
		$nonce_str = $this -> CreateNoncestr();
		$body = '测试商品';
		$out_trade_no = time();
		$total_fee = 20;
		$spbill_create_ip = $ip;
		$notify_url = 'http://www.ok-life.com/test.html';
		$trade_type = 'JSAPI';
		
		$data = [
		'appid' => $appid,
		'body'  => $body,
		'mch_id' => $mch_id,
		'nonce_str' => $nonce_str,
		'notify_url' => $notify_url,
		'openid' => $opid,
		'out_trade_no' => $out_trade_no,
		'spbill_create_ip' => $spbill_create_ip,
		'sub_mch_id' => '1441772202',
		'total_fee' => $total_fee,
		'trade_type' => $trade_type,
		
		];
		$params = urldecode(http_build_query($data)).'&key=1E36A15161292BE7CA18C3F2C5FDAB58';
		
		
		$md5sign = ['sign' => strtoupper(md5($params))];
		
		$result = array_merge($data,$md5sign);
		$kk1 = $this -> ToXml($result);
	
		$httpstr = $this ->http($url, $kk1, 'POST', array("charset=utf-8"),true);		
		
		$repayid = $this -> FromXml($httpstr);
		return $repayid;
	}
	
	public function ToXml($data)
	{
		if(!is_array($data) 
			|| count($data) <= 0)
		{
    		echo '数组数据异常';
    	}
    	
    	$xml = "<xml>";
    	foreach ($data as $key=>$val)
    	{
    		if (is_numeric($val)){
    			$xml.="<".$key.">".$val."</".$key.">";
    		}else{
    			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
    		}
        }
        $xml.="</xml>";
        return $xml; 
	}
	
	public function FromXml($xml)
	{	
		if(!$xml){
			echo 'xml数据异常!';
		}
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $this->values;
	}
}
