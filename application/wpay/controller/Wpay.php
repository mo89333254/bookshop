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
use think\model\shopcart\GetUserInfoParam;


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
		$sumprice = 0;
		$paytype = $_POST['paytype'];
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
			->join('saleinfo y','y.ID = a.SaleID','LEFT')							
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

			$stotal = round(floatval($total) * config('service.rate'),2) + $stotal;
			$linkid_in = time().rand(10,99).'2';
			$linkid_out = time().rand(10,99).'1';
			
			///卖家参数
			$param1 = [
				'OrderID' => $linkid_out,
				'GoodsID' => '',
				'PayType' => $paytype,
				'Count' => 0,
				'UserID' => $userid,
				'Status' => 0,
				'ExtendInfo' => $content,
				'Amount' => 0,
				'CreateTime' => date("Y-m-d H:i:s"),
				'CreateDate' =>date("Y-m-d H:i:s"),
				'SaleType' => 1,
				'SaleID' => '',
				'BindOrder' => '',
				'Suserid' => $userid,
				'UserInfo' => '',
			];
			
			$servicetotal = round(floatval($total) * config('service.rate'),2) + $total; 
			
			///买家参数
			$param2 = [
				'OrderID' => $linkid_in,
				'GoodsID' => $cart_value,
				'PayType' => $paytype,
				'Count' => 0,
				'UserID' => $userid,
				'Status' => 0,
				'ExtendInfo' => $content,
				'Amount' => 0,
				'CreateTime' => date("Y-m-d H:i:s"),
				'CreateDate' => date("Y-m-d H:i:s"),
				'SaleType' => 2,
				'Suserid' => 0,
				'UserInfo' => '',
			];
			
		
			
			foreach($shopcartlist as $k=>$v){
				$new[$v['Suserid']][]    =   $v;

			}
		
			
			$orderids = "";
			
			///买家订单
			$param2['OrderID'] = strtotime('+'.count($new).' second').rand(10,99).'1';
			
			
			
		
			foreach($new as $k => $v)
			{
				
				//卖家参数
				$hha = '';
				$salearray_1 = array();
				$salearray_2 = array();
				
				//书本数量
				$salecount = 0;
				
				//不带服务金额
				$nototal = 0;
			
		
				//带服务费金额
				$sertotal = 0;
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
					
					

					
					
					$salecount = $salecount + $k2['scount'];
					$gettotal = (($saleinfo ->price) * $k2['scount']);
					$nototal = $gettotal + $nototal;
					$service = round(floatval($gettotal) * config('service.rate'),2);
					$sertotal = $service + $gettotal + $sertotal; 
					
					
					
					array_push($salearray_1,$saleinfo);
					array_push($salearray_2,$saleinfo);
					//Db::table('saleinfo')->where('BookID',$k2['BookID'])->where('Suserid',$k2['Suserid'])->setInc('Count',$k2['scount']);
					$param1['UserID'] = $k2['Suserid'];
					
					$param2['Suserid'] = $k2['Suserid'];
					
				}
				$goodsid = substr($hha,1);
				

				$userinfo = new GetUserInfoParam();
				$userinfo ->username = $name;
				$userinfo ->phone = $phone;
				$userinfo ->userid = $userid;
				
				$userinfo -> susername = $v[0]['NickName'];
				$userinfo -> sphone = $v[0]['Phone'];
				
				$param2['UserInfo'] = json_encode($userinfo,JSON_UNESCAPED_UNICODE);
				//买家生成订单
				$param2['GoodsID'] = $goodsid;
				$param2['OrderID'] = strtotime('+'.($k1+1).' second').rand(10,99).'2';
				$param2['Amount'] = $nototal;
				$param2['Count'] = $salecount;
				$param2['SaleID'] = json_encode($salearray_2,JSON_UNESCAPED_UNICODE);
				Db::table('orderinfo')->insert($param2);
				
				//卖家订单绑定买家orderid
				$param1['BindOrder'] = $param2['OrderID'];
				
				
				//卖家生成订单
				$param1['UserInfo'] = json_encode($userinfo,JSON_UNESCAPED_UNICODE);
				$param1['GoodsID'] = $goodsid;
				$param1['Amount'] = $nototal;
				$param1['Count'] = $salecount;
				$param1['SaleID'] = json_encode($salearray_1,JSON_UNESCAPED_UNICODE);
				$param1['OrderID'] = strtotime('+'.$k1.' second').rand(10,99).'2';
				//$orderids = ','.$param1['OrderID'].$orderids;
				Db::table('orderinfo')->insert($param1);

				
				$orderids = ','.$param2['OrderID'].$orderids;
			}

			
	
			//Db::table('shopcartinfo')->where('ID','IN',$cart_value)->delete();
			
			$orderids = substr($orderids,1);
		}
					
		


		
		
		
		
		

		else
		{
			$nototal = $_GET['price'];
			$orderids = $_GET['orderid'];
		}	
		
		$view -> stotal = $nototal; 
		

		$result = $this -> getpay($nototal,$orderids,config('wpay.notify_url'));
		
		$view->assign('appid',$result['appId']);
        $view->assign('noncestr',$result['nonceStr']);
		$view->assign('time',$result['timeStamp']);
		$view->assign('sign',$result['signature']);
		$view -> assign('signtype',$result['signType']);
		 
		 
		$view -> assign('prepayid',$result['prepayid']);
		$view -> assign('paysign',$result['paysign']);	 
		$view -> orderid = $orderids;
		$view -> paytype = $paytype;
		
		return $view ->fetch();
	}
	
	///type  1 - 体验劵 0 - 学生折
	public function merpay($info='1,8',$type=1,$fee=0.1)
	{
		$view = new View();
		$result = $this -> getpay($fee,$info,config('wpay.student_notify'));
		$userid = session('userid');
		$userinfo = Db::table('userinfo')->field('UserType,Property')->where('ID',$userid)->find();
		$orderid = time().rand(10,99).'3';
		$time = date("Y-m-d H:i:s");
		
		///生成待支付订单
		$orderparam = [
				'OrderID' => $orderid,
				'UserID' => $userid,
				'CreateTime' => $time,		
				'Status' => 1
			];
		$order = Db::table('studentorder')->insert($orderparam);
		
		
		if($type == 1)
		{
			$infoarray = explode(',',$info);
			$shopid = $infoarray[0];
			$shopinfo = Db::table('shopinfo')->where('ID',$shopid)->find();
			
		}
		else if($type == 0)
		{
			$price = $_GET['fee'];
			$infoarray = explode(',',$info);
			$merid = $infoarray[2];
			$shopinfo = ['ShopName' => '学生折买单','Price' => $price,'MerID' => $merid,'ID' => 0];
			
			
			
		}
		
		$view->assign('appid',$result['appId']);
        $view->assign('noncestr',$result['nonceStr']);
		$view->assign('time',$result['timeStamp']);
		$view->assign('sign',$result['signature']);
		$view -> assign('signtype',$result['signType']);
		
		 $view -> shopinfo =$shopinfo;
		 $view -> orderid = $orderid;
		 $view -> userinfo = $userinfo;
		$view -> assign('prepayid',$result['prepayid']);
		$view -> assign('paysign',$result['paysign']);	 
		return $view -> fetch();
		
	}
	
	public function getpay($fee=0.1,$attach,$notifyurl)
	{
				///请求微信支付
		$request = Request::instance();		
		$ip= request()->ip();	

		
		$url = urldecode($request->url(true));
		
		
		$openid = session('openid');
		
		$orderresult = $this -> GetOrder($openid,$fee,$attach,$notifyurl);

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
		
		$result = array_merge($paydata,['paysign' => $paysign,'prepayid' => $orderresult['prepay_id'],'signature' => $signature]);
		
		return $result;
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
		/*if(session('openid'))
		{
			return session('openid');
		}
		else
		{	*/
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
		/*}	*/
		
	}
	
	///生成支付订单ID
	public function GetOrder($opid,$fee,$attach = "test",$notifyurl)
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
		$notify_url = $notifyurl;
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
			'Status' => 3,
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
	
	///书本交易微信支付回调接口
	public function notify()
	{
		$xml = file_get_contents("php://input");

		$data = $this -> FromXml($xml);


		//$data = ['LinkID' => $data['out_trade_no'], 'Price' => $data['total_fee'],'UserType' => 1,'UpdateTime' => date("Y-m-d H:i:s"),'SubMchID' => $data['sub_mch_id'],'Content' => $data['attach']];
		$orderids = $data['attach'];
		$weixinorder = $data['transaction_id'];
		if($orderids == 'test')
		{
			return 'SUCCESS';
		}
		
		$orderlist = Db::table('orderinfo')->alias('a')->field('a.*,b.NickName,b.Phone')->join('userinfo b','a.Suserid = b.ID','LEFT')
						->where('OrderID','IN',$orderids)
						->whereOr('BindOrder','IN',$orderids)
						->select();
		foreach($orderlist as $ord1 => $ord2)
		{
			$sinfo = json_decode($ord2['SaleID']);
			$userinfo = json_decode($ord2['UserInfo']);
			$bookname = '';
			foreach($sinfo as $s1 => $s2)
			{
				$bn = '《'.$s2 -> bookname.'》';
				$bc = $s2 -> count;
				$bookname = ','.$bn.'--数量:'.$bc.$bookname;
			
			}
			$bookname = substr($bookname,1);
			$count = $ord2['Count'];
			$name = $userinfo -> username;
			$phone = $userinfo -> phone;
			$sname = $userinfo -> susername;
			$sphone = $userinfo -> sphone;
			if($ord2['SaleType'] == 2)
			{
				$this -> sendusermessage($phone,$bookname,$sname,$sphone);
				//Db::table('saleinfo')->where('ID',$s2 ->saleid)->setDec('Count',$s2->count);
				Db::table('shopcartinfo')->where('ID','IN',$ord2['GoodsID'])->delete();
				
			}
			else if($ord2['SaleType'] == 1)
			{
				$this -> sendsusermessage($sphone,$bookname,$name,$phone);
			}
		}
		
		$param2 = ['Status' => 2,'WeixinOrder' => $weixinorder];
		$param1 = ['Status' => 1];
		Db::table("orderinfo")->where('OrderID','IN',$orderids)->update($param2);
		Db::table("orderinfo")->where('BindOrder','IN',$orderids)->update($param1);
		Log::write('支付成功:'.$xml,'notice');
		
		echo 'SUCCESS';
	}
	
	///学生折微信交易支付回调
	public function studentnotify($info = '4,8')
	{
		$xml = file_get_contents("php://input");

		$data = $this -> FromXml($xml);


		
		$info = $data['attach'];
		$weixinorder = $data['transaction_id'];
		$price = $data['total_fee'] * 0.01;
		$info = explode(',',$info);
		$shopid = $info[0];
		$userid = $info[1];
		$merid = $info[2];
		$shopinfo_json = '';
		
		$type = 0;
		if($shopid != 0)
		{
			$type = 1;
			$shopinfo = Db::table('shopinfo')->alias('a')->field('a.MerID,a.ID,a.ShopName,a.SImg,a.Price,a.Type,b.Merchant')
							->join('merchantinfo b','a.MerID = b.ID')
							->where('a.ID',$shopid)->find();
			$shopinfo_json = json_encode($shopinfo,JSON_UNESCAPED_UNICODE);
			//统计是否消费过此体验劵
			$this -> merisexit($shopid,$userid);
		}
		else
		{
			$merinfo = Db::table('merchantinfo')->field('Merchant')->where('ID',$merid)->find();
			$mername = $merinfo['Merchant'];
			$shopname = '学生折消费';
			$jsondata = [
				'Merchant' => $mername,
				'ShopName' => $shopname
			];
			$shopinfo_json = json_encode($jsondata,JSON_UNESCAPED_UNICODE);
			
		}		

		$noncestr = $this ->CreateNoncestr(8);
		$time = date("Y-m-d H:i:s");
		$param = ['ShopID' => $shopid,
			'UserID' => $userid,
			'Type' => 0,
			'Content' => $shopinfo_json,
			'CreateTime' => $time,
			'EndTime' => date('Y-m-d',strtotime("$time +1 month")),
			'Code' => $noncestr
		];
		$orderparam = [
			'OrderID' => time().rand(10,99).'3',
			'UserID' => $userid,
			'CreateTime' => $time,
			'ShopID' => $shopid,	
			'MerID' => $merid,
			'Content' => $shopinfo_json,
			'Price' => $price,
			'Type' => $type,
			'WeixinOrder' => $weixinorder,
			'PayType' => 0
		];
		Db::table('studentorder')->insert($orderparam);
		Db::table('couponinfo')->insert($param);
		Db::table('merchantinfo')->where('ID',$merid)->setInc('Redu');

		Log::write('支付成功:'.$xml,'notice');
		
		echo 'SUCCESS';
	}
	
	public function sendusermessage($num,$bookname,$username,$phone)
	{
		try {
			// 请根据实际 appid 和 appkey 进行开发，以下只作为演示 sdk 使用
			$appid = config('sms1.appid');
			$appkey = config('sms1.appkey');
			$phoneNumber1 = "12345678901";
			$phoneNumber2 = $num;
			$phoneNumber3 = "12345678903";
			$templId = config('sms1.getid');

			$singleSender = new \phpsms\SmsSingleSender($appid, $appkey);
			$util = new \phpsms\SmsSenderUtil();


			
			// 指定模板单发
			// 假设模板内容为：测试短信，{1}，{2}，{3}，上学。
			$random = $util -> getRandom();
			$params = array($bookname, $username,$phone);
			$result = $singleSender->sendWithParam("86", $phoneNumber2, $templId, $params, "", "", "");
			$rsp = json_decode($result);
			//echo $result;
			Log::write('买家信息发送成功:'.$result);


		} 
		catch (\Exception $e) 
		{
			echo var_dump($e);
		}
	}
	
	public function sendsusermessage($num,$bookname,$username,$phone)
	{
		try {
			// 请根据实际 appid 和 appkey 进行开发，以下只作为演示 sdk 使用
			$appid = config('sms1.appid');
			$appkey = config('sms1.appkey');
			$phoneNumber1 = "12345678901";
			$phoneNumber2 = $num;
			$phoneNumber3 = "12345678903";
			$templId = config('sms1.saleid');

			$singleSender = new \phpsms\SmsSingleSender($appid, $appkey);
			$util = new \phpsms\SmsSenderUtil();


			
			// 指定模板单发
			// 假设模板内容为：测试短信，{1}，{2}，{3}，上学。
			$random = $util -> getRandom();
			$params = array($bookname, $username,$phone);
			$result = $singleSender->sendWithParam("86", $phoneNumber2, $templId, $params, "", "", "");
			$rsp = json_decode($result);
			//echo $result;
			Log::write('卖家家信息发送成功:'.$result);


		} 
		catch (\Exception $e) 
		{
			echo var_dump($e);
		}
	}
	
	//学生折零钱支付回调
	//code 1 - 成功 0 - 失败 2 - 余额不足 3 - 密码错误
	public function linqianzhifu($price,$merid,$shopid=0,$orderid)
	{
		$userid = session('userid');
		$userinfo  =Db::table('userinfo')->where('ID',$userid)->find();
		$chorder = Db::table('studentorder')->where('OrderID',$orderid)->find();
		if($chorder['Status'] < 1)
		{
			return 0;

		}		
		if($userinfo['Property'] < $price)
		{
			return 2;

		}

		/*if($pass != $userinfo['PayPass'])
		{
			return 3;
		}*/

		$shopinfo_json = '';
		
		$type = 0;
		if($shopid != 0)
		{
			$type = 1;
		
			$shopinfo = Db::table('shopinfo')->alias('a')->field('a.MerID,a.ID,a.ShopName,a.SImg,a.Price,a.Type,b.')
				->join('merchantinfo b','a.MerID = b.ID')
				->where('a.ID',$shopid)->find();
			$shopinfo_json = json_encode($shopinfo,JSON_UNESCAPED_UNICODE);
			//统计是否消费过此体验劵
			$this -> merisexit($shopid,$userid);
		}
		else
		{
			$merinfo = Db::table('merchantinfo')->field('Merchant')->where('ID',$merid)->find();
			$mername = $merinfo['Merchant'];
			$shopname = '学生折消费';
			$jsondata = [
				'Merchant' => $mername,
				'ShopName' => $shopname
			];
			$shopinfo_json = json_encode($jsondata,JSON_UNESCAPED_UNICODE);
			
		}
		

		$noncestr = $this ->CreateNoncestr(8);
		$time = date("Y-m-d H:i:s");
		$param = ['ShopID' => $shopid,
			'UserID' => $userid,
			'Type' => 0,
			'Content' => $shopinfo_json,
			'CreateTime' => $time,
			'EndTime' => date('Y-m-d',strtotime("$time +1 month")),
			'Code' => $noncestr
		];
		$orderparam = [
			'OrderID' => time().rand(10,99).'3',
			'UserID' => $userid,
			'CreateTime' => $time,
			'ShopID' => $shopid,	
			'MerID' => $merid,
			'Content' => $shopinfo_json,
			'Price' => $price,
			'Type' => $type,
			'WeixinOrder' => '',
			'PayType' => 1,
			'Status' => 0
		];
		$result = Db::table('studentorder')->where('OrderID',$orderid)->update($orderparam);
		
		
		if($result > 0)
		{
			Db::table('couponinfo')->insert($param);
			Db::table('merchantinfo')->where('ID',$merid)->setInc('Redu');
			Db::table('userinfo')->where('ID',$userid)->setDec('Property',$price);
		}

		
		return $result;
	}
	
		///书本零钱支付回调接口
	public function lingqiannotify($orderids,$price)
	{

		$userid = session('userid');
		$userinfo  =Db::table('userinfo')->where('ID',$userid)->find();
		$chorder = Db::table('orderinfo')->where('OrderID','IN',$orderids)->find();
		if($chorder['Status'] > 0)
		{
			return 0;

		}
		if($userinfo['Property'] < $price)
		{
			return 2;

		}

		
		
		
		$orderlist = Db::table('orderinfo')->alias('a')->field('a.*,b.NickName,b.Phone')->join('userinfo b','a.Suserid = b.ID','LEFT')
						->where('OrderID','IN',$orderids)	
						
						->whereOr('BindOrder','IN',$orderids)
						->select();
		foreach($orderlist as $ord1 => $ord2)
		{
			$sinfo = json_decode($ord2['SaleID']);
			$userinfo = json_decode($ord2['UserInfo']);
			$bookname = '';
			foreach($sinfo as $s1 => $s2)
			{
				$bn = '《'.$s2 -> bookname.'》';
				$bc = $s2 -> count;
				$bookname = ','.$bn.'--数量:'.$bc.$bookname;
			
			}
			$bookname = substr($bookname,1);
			$count = $ord2['Count'];
			$name = $userinfo -> username;
			$phone = $userinfo -> phone;
			$sname = $userinfo -> susername;
			$sphone = $userinfo -> sphone;
			if($ord2['SaleType'] == 2)
			{
				if($ord2['Status'] == 0)
				{
					$this -> sendusermessage($phone,$bookname,$sname,$sphone);
					//Db::table('saleinfo')->where('ID',$s2 ->saleid)->setDec('Count',$s2->count);
					Db::table('shopcartinfo')->where('ID','IN',$ord2['GoodsID'])->delete();
				}
				
			}
			else if($ord2['SaleType'] == 1)
			{
				if($ord2['Status'] == 0)
				{
					$this -> sendsusermessage($sphone,$bookname,$name,$phone);
				}
			}
		}
		
		$param2 = ['Status' => 2,'PayType' => 1];
		$param1 = ['Status' => 1,'PayType' => 1];
		
		$result = Db::table("orderinfo")->where('OrderID','IN',$orderids)->update($param2);
		Db::table("orderinfo")->where('BindOrder','IN',$orderids)->update($param1);
	
		if($result > 0)
		{			
			Db::table('userinfo')->where('ID',$userid)->setDec('Property',$price);
		}

		
		return $result;
		
	}
	
	public function tuiqian()
	{
		$request = Request::instance();		
		$ip = request()->ip();
		$url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
		$appid = config('wpay.appid');
		$mch_id = config('wpay.mchid');
		$nonce_str = $this -> CreateNoncestr();
		$body = 'test11111';
		$out_trade_no = time();
	

		
		$data = [
		'appid' => $appid,		
		'mch_id' => $mch_id,
		'nonce_str' => $nonce_str,
		
		'op_user_id' => $mch_id,
		'out_refund_no' => 778546123456789,
	
		'refund_fee' => 100,
		
		'sub_mch_id' => '1441772202',
		'total_fee' => 100,
		
		'transaction_id' => 4001722001201705211953287679
		
		];
		$params = urldecode(http_build_query($data)).'&key='.config('wpay.api_key');
		
	
		
		$md5sign = ['sign' => strtoupper(md5($params))];
		
		$result = array_merge($data,$md5sign);
		$kk1 = $this -> ToXml($result);

		$httpstr = $this ->http($url, $kk1, 'POST', array("charset=utf-8"),true);		
		dump($httpstr);
		$repayid = $this -> FromXml($httpstr);
	}
	
	///判断是否已消费学生折 2 - 已存在
	public function merisexit($shopid,$userid)
	{
		
		$daohuo = Db::table('userinfo')->where('ID',$userid)->find();
		

		$merlist = json_decode($daohuo['MerList']);
		if(!$merlist)
		{
			$new = array($shopid);
			$merjson = json_encode($new);
			$result = Db::table('userinfo')->where('ID',$userid)->update(['MerList'=>$merjson]);
			return $result;
		}
		else
		{
			if(in_array($shopid,$merlist))
			{
				return 2;
			}
			else
			{
				array_push($merlist,$shopid);
				$merjson = json_encode($merlist);
				$result = Db::table('userinfo')->where('ID',$userid)->update(['MerList'=>$merjson]);
				return $result;
			}
		}

	}
	
	public function paysuccess()
	{
		$view = new View();
		return $view->fetch();
	}
	
	public function studentpaysuccess()
	{
		$view = new View();
		return $view->fetch();
	}	
}
