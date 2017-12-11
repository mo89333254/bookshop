<?php
namespace app\getdata\controller;
use think\Db;
use think\View;
use think\Request;
use think\Controller;
use think\Session;
class Getdata extends Controller
{



	
	public function GetHeroList()
	{	
		$haha = Db::table('HeroInfo')->column('Name');
		
		return json_encode($haha,JSON_UNESCAPED_UNICODE);
	}
	
	public function GetHeroInfo($name)
	{
		$haha = Db::table('HeroInfo')->where('Name',$name)->find();
		return json_encode($haha,JSON_UNESCAPED_UNICODE);
	}
	
	public function GetHeroDetail($heroid)
	{
		$herodetailinfo = Db::table('HeroDetail')->where('HeroID',$heroid)->find();
		return json_encode($herodetailinfo,JSON_UNESCAPED_UNICODE);
	}
	

	


	

}