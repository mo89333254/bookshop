<?php
namespace app\order\controller;
use think\Db;
use think\View;
use think\Request;
use think\Cache;
use think\Session;
use think\Exception;
use think\Log;

class Order extends \think\Controller
{

	public function GetOrderReport()
	{
		$date = date("Y-m-d",time());
		$orderreport = Db::table('orderinfo')
							->where('CreateTime','between',$date.','.date('Y-m-d',strtotime("$date +1 day")))	
							->where('SaleType','1')
							->field('*,sum(Amount) as amountsum,count(OrderID) as ordercount')
							->group('CreateDate')							
							->select();
		dump($orderreport);
	}
	
	public function bookorder($status=2 ,$type=2)
	{
		$view = new View();
		$userid = session('userid');
		$orderlist= Db::table('orderinfo')->where('UserID',$userid)->where('IsDel',0);

		$orderlist = $orderlist ->where('Status',$status);		

	
		
		$orderlist = $orderlist ->where('SaleType',$type)->order('CreateTime desc')->select();

		$kk1 = array();
		
		foreach($orderlist as $k1 => $k2)
		{
			$kk1 = array_merge($kk1,json_decode($k2['SaleID']));
		}
	
		
		$neworder = array();
		
		foreach($orderlist as $n1 => $n2)
		{

			$neworder[$n1]['count'] = $n2['Count'];
			$neworder[$n1]['price'] = $n2['Amount'];
			$neworder[$n1]['date'] = $n2['CreateTime'];
			$neworder[$n1]['orderid'] = $n2['OrderID'];
			$neworder[$n1]['status'] = $n2['Status'];
			$neworder[$n1]['row'] = json_decode($n2['SaleID']);			
			$neworder[$n1]['orderinfo'] = json_decode($n2['UserInfo']);
			$neworder[$n1]['IsRecart'] = $n2['IsRecart'];
		}
		
		$view -> userid = $userid;
		$view -> status = $status;
		$view -> type = $type;
		$view -> orderlist = $neworder;
		return $view -> fetch();
	}
	

	public function orderdetail()
	{
		$view = new View();

		return $view -> fetch();
	}	
	public function pay($cart_value = 0)
	{
		
		$view = new View();
		


		$userinfo = Db::table('userinfo')
						->field('a.*,b.SchoolName,b.SchoolID')
						->alias('a')->join('schoolinfo b','a.SchoolID = b.SchoolID','LEFT')
						->where('ID',session('userid'))->find();

		$shopcartlist = array();
		$typelist = Db::table('shopcartinfo') -> alias('a')
							->field('a.*,w.*,x.*,y.*,a.Count as scount,a.ID as sid')
							->join('saleinfo y','y.ID = a.SaleID','LEFT')
							->join('bookinfo w','y.BookID = w.ID','LEFT')	
														
							->join('userinfo x','y.SuserID = x.ID','LEFT');
							
		$typelist =	$typelist ->where('a.ID','IN',$cart_value)->select();		

	

		foreach($typelist as $k=>$v)
		{
		    $shopcartlist[$v['NickName']]['name']    =   $v['NickName'];

		    $shopcartlist[$v['NickName']]['row'][]    =   $v;
		}			
		

						
		$stotal = 0;
		$scount = 0;		
		foreach($shopcartlist as $value) 
			{ 

				foreach($value['row'] as $value1)
				{
					$stotal = $stotal + ($value1['scount'] * 0.1 * $value1['Amount'] * $value1['Discount']);
					$scount = $scount + $value1['scount'];
				}
			} 
		

		$service = round(floatval($stotal) * config('service.rate'),2); 
		$result = $service + $stotal;
		$view -> orderlist = $shopcartlist;
		$view -> countlist = count($shopcartlist);
		$view -> stotal = $stotal;
		$view -> userinfo = $userinfo;
		$view -> scount = $scount;
		$view -> service = $service;
		$view -> result = $result;
		$view -> cart_value =$cart_value;
		$view -> userid = 4;

		return $view -> fetch();
	}

	public function orderresult()
	{
		$view = new view();
		return $view -> fetch();
	}
	
	public function changepayment()
	{
		return 1;
	}
	
	///下订单
	public function GetOrder()
	{
		$cart_value = $_POST['cart_value'];
		return $cart_value;
	}
	
	public function bookorder1($orderid=1494147048812)
	{
		
		$view = new View();
		
		$orderinfo = Db::table('orderinfo')->alias('a')
						->field('a.*,b.SchoolName,c.NickName,c.Phone,c.SchoolID')
						->join('userinfo c','a.UserID = c.ID','LEFT')
						->join('schoolinfo b','b.SchoolID = c.SchoolID','LEFT')
						
						->where('a.OrderID',$orderid)->find();
						
		$orderdetail = json_decode($orderinfo['SaleID']);

		
		if($orderdetail)
		{
			$userinfo = 
			$userinfo = ['name'=>$orderdetail[0]->nickname,'school'=>$orderdetail[0]->schoolname,'schooladd'=>$orderdetail[0]->schooladd,'phone'=>$orderdetail[0]->phone];

			
		}
		$saleids = '';
		$stotal = 0;
		$scount = 0;
		foreach($orderdetail as $k1 => $k2)
		{
			$saleids = ','.$k2->saleid.$saleids;
			$stotal = $stotal + ($k2->price * $k2->count);
			$scount = $scount + $k2->count;
			$neworder[$k2->suserid]['name']    =   $k2->snickname;

		    $neworder[$k2->suserid]['row'][]    =   $k2;
		}
		$saleids = substr($saleids,1);
	
		

		


	
		
		$service = round(floatval($stotal) * 0.05,2); 
		$result = $service + $stotal;
		$view -> orderlist = $neworder;
		$view -> userid = session('userid');
		$view -> orderinfo = $orderinfo;
		$view -> stotal = $stotal;
		$view -> scount = $scount;
		$view -> service = $service;
		$view -> result = $result;
		$view -> userinfo = $userinfo;
		return $view -> fetch();
	}
	
	///买家取消订单
	public function CancelOrder($orderid)
	{
		$orderinfo = Db::table('orderinfo')->where('BindOrder',$orderid)->find();
		$shopinfo = json_decode($orderinfo['SaleID']);
		$userinfo = json_decode($orderinfo['UserInfo']);

		
		Db::table('orderinfo')->where('BindOrder',$orderid)->update(['Status' => 5] );
		$result = Db::table('orderinfo')->where('OrderID',$orderid)->update(['Status' => 6]);
		
		$tuiqian = $orderinfo['Amount'];  ///退款金额
		$maijiaid = $orderinfo['Suserid']; ///买家ID
		$paytype1 = $orderinfo['PayType']; ///支付方式

		Db::table('userinfo')->where('ID',$maijiaid)->setInc('Property',$tuiqian);
		
		$tuiqianparam = [
			'Type' => 3,
			'UserID' => $maijiaid,
			'PayType' => $paytype1,
			'OrderID' => $orderid,
			'Suserid' => 0,
			'Income' => $tuiqian,
			'CreateTime' => date("Y-m-d H:i:s")
			];
		
		Db::table('incomeinfo')->insert($tuiqianparam);
		
		$bookname = '';
		foreach($shopinfo as $s1 => $s2)
		{
			$bn = '《'.$s2 -> bookname.'》';
			$bc = $s2 -> count;
			$bookname = ','.$bn.'--数量:'.$bc.$bookname;
		}
		$bookname = substr($bookname,1);
		$this -> cancelordermessage($userinfo->sphone,$bookname);
		return $result;
	}

	///卖家取消订单
	public function SCancelOrder($sorderid)
	{
		$sorderinfo = Db::table('orderinfo')->where('OrderID',$sorderid)->find();
		$orderid = $sorderinfo['BindOrder'];
		$shopinfo = json_decode($sorderinfo['SaleID']);
		$userinfo = json_decode($sorderinfo['UserInfo']);

		$sprice = $sorderinfo['Amount']; ///退款金额
		$suserid = $sorderinfo['UserID']; ///买家ID
		$paytype1 = $sorderinfo['PayType']; ///支付方式
		
		
		Db::table('orderinfo')->where('OrderID',$sorderid)->update(['Status' => 5] );
		$result = Db::table('orderinfo')->where('OrderID',$orderid)->update(['Status' => 6]);
		Db::table('userinfo')->where('ID',$suserid)->setInc('Property',$sprice);
		
		$tuiqianparam = [
			'Type' => 3,
			'UserID' => $sprice,
			'PayType' => $paytype1,
			'OrderID' => $orderid,
			'Suserid' => 0,
			'Income' => $sprice,
			'CreateTime' => date("Y-m-d H:i:s")
			];
		
		Db::table('incomeinfo')->insert($tuiqianparam);
		
		$bookname = '';
		foreach($shopinfo as $s1 => $s2)
		{
			$bn = '《'.$s2 -> bookname.'》';
			$bc = $s2 -> count;
			$bookname = ','.$bn.'--数量:'.$bc.$bookname;
		}
		$bookname = substr($bookname,1);
		$this -> scancelordermessage($userinfo->phone,$bookname);
		return $result;
	}	
	
	public function DelOrder($orderid)
	{
		return Db::table('orderinfo')->where('OrderID',$orderid)->update(['IsDel' => 1]);
	}
	
	public function ConfirmOrder($orderid)
	{
		$sorderinfo = Db::table('orderinfo')->where('BindOrder',$orderid)->find();
		$shopinfo = json_decode($sorderinfo['SaleID']);
		$userinfo = json_decode($sorderinfo['UserInfo']);
		$status = $sorderinfo['Status'];
		if($status == 3)
		{
			return 0;
		}
		$sprice = round(floatval($sorderinfo['Amount']) * config('service.rate'),2)
		$suserid = $sorderinfo['UserID'];
		$sorderid = $sorderinfo['OrderID'];
		Db::table('userinfo')->where('ID',$suserid)->setInc('Property',$sprice);
		Db::table('orderinfo')->where('BindOrder',$orderid)->update(['Status' => 3] );
		$insertparam = [
			'OrderID' => $sorderid,
			'Income' => $sprice,
			'CreateTime' => date("Y-m-d H:i:s"),
			'Type' => 1,
			'Suserid' => $suserid,
			'UserID' => $sorderinfo['Suserid'],
			'PayType' => $sorderinfo['PayType']
		];
		Db::table('incomeinfo')->insert($insertparam);
		$result = Db::table('orderinfo')->where('OrderID',$orderid)->update(['Status' => 4]);
		$bookname = '';
		foreach($shopinfo as $s1 => $s2)
		{
			$bn = '《'.$s2 -> bookname.'》';
			$bc = $s2 -> count;
			$bookname = ','.$bn.'--数量:'.$bc.$bookname;
		}
		$bookname = substr($bookname,1);
		$this -> confirmordermessage($userinfo->sphone,$userinfo->username,$bookname,$sorderinfo['Amount']);
		return $result;
	}
	
	public function recart($orderid)
	{
		$isrecart = Db::table('orderinfo')->where('OrderID',$orderid)->find();
		if($isrecart['IsRecart'] == 1)
		{
			return 0;
		}
		$result = Db::table('orderinfo')->where('OrderID',$orderid)->update(['IsRecart' => 1,'Status' => 0]);
		$saleinfo = json_decode($isrecart['SaleID']);
		foreach($saleinfo as $k1 => $k2)
		{
			Db::table('saleinfo')->where('ID',$k2 -> saleid)->setInc('Count',$k2 -> count);
		}
		
		return $result;
	}
	
	//买家取消订单信息
	public function cancelordermessage($num,$bookname)
	{
		try {
			// 请根据实际 appid 和 appkey 进行开发，以下只作为演示 sdk 使用
			$appid = config('sms1.appid');
			$appkey = config('sms1.appkey');
			$phoneNumber1 = "12345678901";
			$phoneNumber2 = $num;
			$phoneNumber3 = "12345678903";
			$templId = config('sms1.cancelorder');

			$singleSender = new \phpsms\SmsSingleSender($appid, $appkey);
			$util = new \phpsms\SmsSenderUtil();


			
			// 指定模板单发
			// 假设模板内容为：测试短信，{1}，{2}，{3}，上学。
			$random = $util -> getRandom();
			$params = array($bookname);
			$result = $singleSender->sendWithParam("86", $phoneNumber2, $templId, $params, "", "", "");
			$rsp = json_decode($result);
			//echo $result;
			Log::write('买家取消订单信息发送成功:'.$result);


		} 
		catch (\Exception $e) 
		{
			echo var_dump($e);
		}
	}
	
	//卖家取消信息订单
	public function scancelordermessage($num,$bookname)
	{
		try {
			// 请根据实际 appid 和 appkey 进行开发，以下只作为演示 sdk 使用
			$appid = config('sms1.appid');
			$appkey = config('sms1.appkey');
			$phoneNumber1 = "12345678901";
			$phoneNumber2 = $num;
			$phoneNumber3 = "12345678903";
			$templId = config('sms1.scancelorder');

			$singleSender = new \phpsms\SmsSingleSender($appid, $appkey);
			$util = new \phpsms\SmsSenderUtil();


			
			// 指定模板单发
			// 假设模板内容为：测试短信，{1}，{2}，{3}，上学。
			$random = $util -> getRandom();
			$params = array($bookname);
			$result = $singleSender->sendWithParam("86", $phoneNumber2, $templId, $params, "", "", "");
			$rsp = json_decode($result);
			//echo $result;
			Log::write('卖家取消订单信息发送成功:'.$result);


		} 
		catch (\Exception $e) 
		{
			echo var_dump($e);
		}
	}	
	
	public function confirmordermessage($num,$user,$bookname,$amount)
	{
		try {
			// 请根据实际 appid 和 appkey 进行开发，以下只作为演示 sdk 使用
			$appid = config('sms1.appid');
			$appkey = config('sms1.appkey');
			$phoneNumber1 = "12345678901";
			$phoneNumber2 = $num;
			$phoneNumber3 = "12345678903";
			$templId = config('sms1.confirmorder');

			$singleSender = new \phpsms\SmsSingleSender($appid, $appkey);
			$util = new \phpsms\SmsSenderUtil();


			
			// 指定模板单发
			// 假设模板内容为：测试短信，{1}，{2}，{3}，上学。
			$random = $util -> getRandom();
			$params = array($user,$bookname,$amount);
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
}
