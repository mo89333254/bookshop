<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;
use think\Db;
use think\View;
use think\Request;
use think\Cache;
use think\Session;
use think\Exception;
use think\Log;
use think\Http;
use think\Cookie;
class Gzh
{
   	///获取网页授权
	

	
	public function GetOpenID($code = 0)
	{
		$http = new Http();

	
			$url = config('wpay.getoua2tokenurl');
			//定义传递的参数数组；
			$data['grant_type'] = 'authorization_code';
			$data['appid']=config('wpay.appid');
			$data['secret']=config('wpay.appsecret');
			$data['code'] = $code;
					
			//定义返回值接收变量；
			$httpstr = $http ->http($url, $data, 'POST', array("charset=utf-8"),false);
	
			$result = json_decode($httpstr);
			try
			{		
				cache($result->openid.'token',$result->access_token,7150);
			

				cache($result->openid.'refresh_token',$result->refresh_token,108000);

				session('openid',$result->openid);		
			
				return $result->openid;
			}
			catch(\Exception $e)
			{
				print $httpstr;          
				exit();   
			}
		
		
	}
	
	public function GetUserInfo($opid,$token)
	{		
		$http = new Http();
		$url = config('wpay.getuserinfourl');
		$data['access_token'] = $token;
		$data['openid']=$opid;
		$data['lang']='zh_CN';
		
		//定义返回值接收变量；
		$httpstr = $http ->http($url, $data, 'POST', array("charset=utf-8"),false);
					
		$result = json_decode($httpstr);
		try
		{
			
			return $result;
		}
		catch(\Exception $e)
		{
			print $httpstr;          
			exit();   
		}
	}

	public function RefreshOauthToken($openid)
	{
		$refresh_token = cache($openid.'token');
		$url = config('wpay.getrefreshtokenurl');
		$data['grant_type'] = 'refresh_token';
		$data['appid']=config('wpay.appid');
		$data['refresh_token']=cache('wpay.appsecret');
		
		//定义返回值接收变量；
		$httpstr = $http ->http($url, $data, 'POST', array("charset=utf-8"),false);
					
		$result = json_decode($httpstr);
		try
		{
			cache($result->openid.'token',$result->access_token,7150);
			return cache($result->openid.'token');
		}
		catch(\Exception $e)
		{
			print $httpstr;          
			exit();   
		}
	}
	

}
