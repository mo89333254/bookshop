<?php
namespace app\wpay\controller;
use think\Db;
use think\View;
use think\Request;
use think\Cache;
use think\Session;
use think\Exception;
use think\Log;
use think\Gzh;
use index\Getgoodsparam;
use think\model\shopcart\GetOrderBookInfo;

class Wpay extends \think\Controller
{
	public function test1()
	{
		$kk = new Gzh();
		$request = Request::instance();
		if(isset($_GET["code"]))
		{
			$code = input('get.code');
			$opid = $kk->GetOpenID($code);

		}
		else
		{
			$opid = session('openid');
		}
		
		
		$token = cache($opid.'token');
		$userinfo = $kk->GetUserInfo($opid,$token);
		$this -> SaveUser($opid,$userinfo);

	}
	
	public function wpay($type=0)
	{
		
		$view = new View();
		$request = Request::instance();
		$stotal = 0;
		if($type <1)
		{
			$cart_value = $_POST['cart_value'];
			$paytype = $_POST['paytype'];
			$userid = session('userid');
			$count = $_POST['count'];		
			$total = $_POST['total'];
			$content = $_POST['content'];
			$name = $_POST['name'];
			$phone = $_POST['phone'];
			$schoolname =$_POST['schoolname'];
			$schooladd = $_POST['schooladd'];
															
			$shopcartlist = Db::table('shopcartinfo') -> alias('a')
			->field('a.*,w.*,x.*,y.*,a.Count as scount,a.ID as sid,y.ID as saleid,w.Amount as price,x.ID as uid,x.SchoolID as schoolid,b.*')
			->join('bookinfo w','a.BookID = w.ID','LEFT')	
			->join('saleinfo y','y.BookID = a.BookID','LEFT')							
			->join('userinfo x','y.SuserID = x.ID','LEFT')	
			->join('schoolinfo b','x.SchoolID = b.SchoolID','LEFT')			
			->where('a.ID','IN',$cart_value)														
			->select();
			
			if(!$shopcartlist)
			{
				return $this->redirect('wallet/wallet/errorindex');
			}

			foreach($shopcartlist as $value1)
			{
				$stotal = $stotal + ($value1['scount'] * 0.1 * $value1['price'] * $value1['Discount']);

			}
			///生成订单

		
			$linkid_in = time().rand(10,99).'2';
			$linkid_out = time().rand(10,99).'1';
			
			///卖家参数
			$param1 = [
				'OrderID' => $linkid_out,
				'GoodsID' => '',
				'PayType' => $paytype,
				'Count' => $count,
				'UserID' => $userid,
				'Status' => 1,
				'ExtendInfo' => $content,
				'Amount' => $total,
				'CreateTime' => date("Y-m-d H:i:s"),
				'CreateDate' =>date("Y-m-d H:i:s"),
				'SaleType' => 1,
				'SaleID' => '',
				'BindOrder' => ''
			];
			
			$servicetotal = round(floatval($total) * config('service.rate'),2) + $total; 
			
			///买家参数
			$param2 = [
				'OrderID' => $linkid_in,
				'GoodsID' => $cart_value,
				'PayType' => $paytype,
				'Count' => $count,
				'UserID' => $userid,
				'Status' => 1,
				'ExtendInfo' => $content,
				'Amount' => $servicetotal,
				'CreateTime' => date("Y-m-d H:i:s"),
				'CreateDate' => date("Y-m-d H:i:s"),
				'SaleType' => 2
			];
			
		
			
			foreach($shopcartlist as $k=>$v){
				$new[$v['Suserid']][]    =   $v;
			}
			
			$salearray_2 = array();
			$orderids = "";
			
			///买家订单
			$param2['OrderID'] = strtotime('+'.count($new).' second').rand(10,99).'1';
			
			
			$param1['BindOrder'] = $param2['OrderID'];
		
			foreach($new as $k => $v)
			{
				$hha = '';
				$salearray_1 = array();
				foreach($v as $k1 => $k2)
				{
					$hha = ','.$k2['sid'].$hha;
					$saleinfo = new GetOrderBookInfo();
					$saleinfo -> saleid = $k2['saleid'];
					$saleinfo -> bookid = $k2['BookID'];
					$saleinfo -> count = $k2['scount'];
					$saleinfo ->suserid = $k2['Suserid'];
					$saleinfo -> price =  (0.1 * $k2['price'] * $k2['Discount']);
					$saleinfo -> bookname = $k2['BookName'];
					$saleinfo -> snickname = $k2['NickName'];
					$saleinfo -> goodimg = $k2['BookSimg'];
					$saleinfo -> userid = $k2['uid'];
				
					$saleinfo -> nickname = $name;
				
					$saleinfo -> schoolname = $schoolname;
					$saleinfo -> schooladd = $schooladd;
					$saleinfo -> phone = $phone;
					
					array_push($salearray_1,$saleinfo);
					array_push($salearray_2,$saleinfo);
					Db::table('saleinfo')->where('BookID',$k2['BookID'])->where('Suserid',$k2['Suserid'])->setDec('Count',$k2['scount']);
					$param1['UserID'] = $k2['Suserid'];
					
					
				}
				$goodsid = substr($hha,1);

				$param1['GoodsID'] = $goodsid;
				$param1['SaleID'] = json_encode($salearray_1,JSON_UNESCAPED_UNICODE);
				$param1['OrderID'] = strtotime('+'.$k1.' second').rand(10,99).'2';
				//$orderids = ','.$param1['OrderID'].$orderids;
				Db::table('orderinfo')->insert($param1);

				
			}

			//$orderids = ','.$param2['OrderID'].$orderids;
			$param2['SaleID'] = json_encode($salearray_2,JSON_UNESCAPED_UNICODE);
			Db::table('orderinfo')->insert($param2);
			Db::table('shopcartinfo')->where('ID','IN',$cart_value)->delete();
			
			$orderids = $param2['OrderID'];
		}
					
		


		
		
		
		
		

		else
		{
			$stotal = $_GET['price'];
			$orderids = $_GET['orderid'];
		}	
		
		$view -> stotal = $stotal; 
		
		///请求微信支付
		$ip= request()->ip();	

		
		$url = urldecode($request->url(true));
		
		
		$openid = session('openid');
		
		$orderresult = $this -> GetOrder($openid,0.1,$orderids);
		
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
		
		$params = urldecode(http_build_query($paydata)).'&key='.config('wpay.api_key');
		

		$signature = $this -> ToSign($data,true);
		$paysign = strtoupper(md5($params));

		
		$view->assign('appid',$appid);
        $view->assign('noncestr',$noncestr);
		$view->assign('time',$time);
		$view->assign('sign',$signature);
		$view -> assign('signtype',$signtype);
		 
		 
		$view -> assign('prepayid',$orderresult['prepay_id']);
		$view -> assign('paysign',$paysign);	 
		$view -> orderid = $orderids;
		
		return $view ->fetch();
	}
	
	public function wpay1()
	{
		
		$view = new View();
		$request = Request::instance();
		$Gzh = new Gzh();
		$code = $Gzh -> GetCode(session('userid'));

		
		$ip= request()->ip();
			

		
		$url = urldecode($request->url(true));
		
		
		$openid = $this->GetOpenID($code);
		
		$orderresult = $this -> GetOrder($openid,2);
		
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
		
		$params = urldecode(http_build_query($paydata)).'&key='.config('wpay.api_key');
		

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
	
	///生成支付订单ID
	public function GetOrder($opid,$fee,$attach = "test")
	{
		$request = Request::instance();		
		$ip = request()->ip();
		$url = config('wpay.unifiedorderurl');
		$appid = config('wpay.appid');
		$mch_id = config('wpay.mchid');
		$nonce_str = $this -> CreateNoncestr();
		$body = 'test11111';
		$out_trade_no = time();
		$total_fee = $fee*100;
		$spbill_create_ip = $ip;
		$notify_url = config('wpay.notify_url');
		$trade_type = 'JSAPI';
		
		$data = [
		'appid' => $appid,
		'attach' => $attach,
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
		$params = urldecode(http_build_query($data)).'&key='.config('wpay.api_key');
		
	
		
		$md5sign = ['sign' => strtoupper(md5($params))];
		
		$result = array_merge($data,$md5sign);
		$kk1 = $this -> ToXml($result);

		$httpstr = $this ->http($url, $kk1, 'POST', array("charset=utf-8"),true);		

		$repayid = $this -> FromXml($httpstr);
		return $repayid;
	}
	
	///obj转XML
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
	
	///From转OBJ
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
	

	
	public function GetPrintSign($apikey,$machine_code,$partner,$key)
	{
		$sign = $apikey.'machine_code'.$machine_code.'partner'.$partner.'time'.time().$key;
		
		$md5sign = strtoupper(md5($sign));
		return $md5sign;
	}
	
	public function ReqPrintApi($url,$apikey,$machine_code,$partner,$key,$content)
	{

		$printsign = $this -> GetPrintSign($apikey,$machine_code,$partner,$key);
		$printdata = [
		
		'machine_code' => $machine_code,
		'time' => time(),
		'partner' => $partner,		
		'sign' => $printsign,
		'content' => urlencode($content),		
		];	
		
		$data = $this -> getStr($printdata);
		
		$curl = curl_init(); // 启动一个CURL会话      
		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址                  
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检测    
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在      
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:')); //解决数据包大不能提交     
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转      
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer      
		curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求      
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包      
		curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循     
		curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容      
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回 
			   
		$tmpInfo = curl_exec($curl); // 执行操作      
		if (curl_errno($curl)) {      
		   echo 'Errno'.curl_error($curl);      
		}      
		curl_close($curl); // 关键CURL会话      
		return $tmpInfo; // 返回数据    	
	
	}
	
	public function getStr($param)
	{
		$str = '';
		foreach ($param as $key => $value) {
			$str=$str.$key.'='.$value.'&';
		}
		$str = rtrim($str,'&');
		return $str;
	}
	
	public function SaveUser($opid,$userinfo)
	{
		$IsExit = Db::table('userinfo')->where('OpenID',$opid)->find();
		if(!$IsExit)
		{
			$params = [
				'OpenID' => $userinfo -> openid,
				'NickName' => $userinfo -> nickname,
				'CreateTime' => time(),
				'UserType' => 1,
				'Sex' => $userinfo -> sex
			];
			$result = Db::table('userinfo')->insert($params);
		}
		return 0;
	}
	///2 - 买家 1 - 卖家 
	public function test111()
	{
		$cart_value = $_POST['cart_value'];
		$paytype = $_POST['paytype'];
		$userid = 4;
		$count = $_POST['count'];		
		$total = $_POST['total'];
		$content = $_POST['content'];
		
		$linkid_in = time().rand(10,99).'2';
		$linkid_out = time().rand(10,99).'1';
		
		///卖家参数
		$param1 = [
			'OrderID' => $linkid_out,
			'GoodsID' => '',
			'PayType' => $paytype,
			'Count' => $count,
			'UserID' => $userid,
			'Status' => 1,
			'ExtendInfo' => $content,
			'Amount' => $total,
			'SaleType' => 1
		];
		
		///买家参数
		$param2 = [
			'OrderID' => $linkid_in,
			'GoodsID' => '',
			'PayType' => $paytype,
			'Count' => $count,
			'UserID' => $userid,
			'Status' => 1,
			'ExtendInfo' => $content,
			'Amount' => $total,
			'SaleType' => 2
		];
		
		$shocartlist = Db::table('shopcartinfo')->where('ID','IN',$cart_value)->select();
		
		foreach($shocartlist as $k=>$v){
			$new[$v['Suserid']][]    =   $v;
		}
		foreach($new as $k => $v)
		{
			$hha = '';
			foreach($v as $k1 => $k2)
			{
				$hha = ','.$k2['BookID'].$hha;
				Db::table('saleinfo')->where('BookID',$k2['BookID'])->where('Suserid',$k2['Suserid'])->setInc('Count',$k2['Count']);
				
			}
			$goodsid = substr($hha,1);
			$param1['GoodsID'] = $goodsid;
			$param2['GoodsID'] = $goodsid;
			$param1['OrderID'] = strtotime('+'.$k1.' second').rand(10,99).'2';
			Db::table('orderinfo')->insert($param1);
			$param2['OrderID'] = strtotime('+'.$k1.' second').rand(10,99).'1';
			Db::table('orderinfo')->insert($param2);
			
		}
		Db::table('shopcartinfo')->where('ID','IN',$cart_value)->delete();
	}
	
	public function woshishui()
	{
		$k = 1;
		for($x=0; $x<=10; $x++)
		{
			echo strtotime('+'.$x.' second').'<br>';
		}
			
		
	}
	
	///支付回调接口
	public function notify()
	{
		$xml = file_get_contents("php://input");

		$data = $this -> FromXml($xml);


		//$data = ['LinkID' => $data['out_trade_no'], 'Price' => $data['total_fee'],'UserType' => 1,'UpdateTime' => date("Y-m-d H:i:s"),'SubMchID' => $data['sub_mch_id'],'Content' => $data['attach']];
		$orderid = $data['attach'];
		if($orderid == 'test')
		{
			return 'SUCCESS';
		}
		$param = ['Status' => 2];
		Db::table("orderinfo")->where('OrderID',$orderid)->update($param);
		Db::table("orderinfo")->where('BindOrder',$orderid)->update($param);
		Log::write('支付成功:'.$xml,'notice');
		
		echo 'SUCCESS';
	}
}
