<?php
namespace app\wallet\controller;
use think\Db;
use think\View;
use think\Request;
use think\Controller;
use think\Session;
use think\Http;
use think\Exception;
use think\Cache;

class Wallet extends Controller
{
	
	public function pickmoney()
	{	
		$view = new View();
		$cardlist = Db::table('bankcardinfo')->where('UserID',session('userid'))->select();
		$userinfo = Db::table('userinfo')->where('ID',session('userid'))->find();
		$cardcount = count($cardlist);
		$view -> cardlist = $cardlist;
		$view -> cardcount = $cardcount;
		$view -> userinfo = $userinfo;
		return $view -> fetch();
	}
		

	public function addbank()
	{	
		$view = new View();

		return $view -> fetch();
	}
	
	public function bankcardlist()
	{
		$view = new View();
		$cardlist = Db::table('bankcardinfo')->where('UserID',session('userid'))->select();
		$view -> cardlist = $cardlist;
		return $view -> fetch();		
	}
	
	public function AddBankMode()
	{
		$s_userid =session('userid');
		$s_userid = session('userid');
		$request = Request::instance();
		$info = $request->param();
		$IsExit = Db::table('bankcardinfo')->where('CardID',$info['bank_card'])->find();
		if($IsExit)
		{
			return $this -> redirect('failinfo');
		}
		$param = [
			'CardID' => $info['bank_card'],
			'BankName' => $info['bank_name'],
			'BankArea' => $info['bank_region'],
			'UserID' => session('userid'),
			'UserName' => $info['bank_user_name']
		];
		$result = Db::table('bankcardinfo')->insert($param);
		if($result > 0)
		{
			return $this -> redirect('successinfo');
		}
		return $this -> redirect('failinfo',array('type'=> 'bankcardlist'));
	}
	
	public function DelBankMode($id)
	{
		$result = Db::table('bankcardinfo')->where('ID',$id)->delete();
		return $result;
	}
	
	public function successinfo($type="")
	{
		$view = new View();
		$url="";
		if($type = 'userinfo')
		{
			$url = HOSTPATH.'/index.php/index/index/userinfo';
		}
		else if($type == 'bankcardlist')
		{
			$url = HOSTPATH.'/index.php/wallet/wallet/bankcardlist';
		}
        $view -> type = $type;	
		$view -> url = $url;	
		return $view -> fetch();			
	}
	public function failinfo($type="")
	{
		$view = new View();
		$url="";
		if($type == 'userinfo')
		{
			$url = HOSTPATH.'/index.php/index/index/userinfo';
		}
		else if($type == 'bankcardlist')
		{
			$url = HOSTPATH.'/index.php/wallet/wallet/bankcardlist';
		}
		$view -> url = $url;	
        $view -> type = $type;	
		return $view -> fetch();	
	}
	
	public function errorindex()
	{
		$view = new View();

		return $view -> fetch();			
	}
	
	public function GetMoney($money,$cardid,$bankname,$bankadd,$bankcard,$username)
	{	
		$userid = session('userid');
		$param = ['Money' => $money,'UserID' => $userid,
		'CardID' => $cardid, 'BankName' => $bankname, 'BankAdd' => $bankadd, 'BankCard' => $bankcard,
		'UserName' => $username,		
		'Status' => 0, 'CreateTime' => date("Y-m-d H:i:s")];
		
		$result = Db::table('pickmoneyinfo')->insert($param);
		if($result > 0)
		{
			$result = Db::table('userinfo')->where('ID',$userid)->setDec('Property',$money);
		}
		$url = 'data=/index/index/userinfo';
		return $this -> redirect('successinfo',array('type'=> 'userinfo'));
	}
}