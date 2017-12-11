<?php
namespace app\index\controller;
use think\Db;
use think\View;
use think\Request;
use think\Cache;
use think\Session;
use think\Exception;
use think\Controller;
use think\Log;
use think\Gzh;
use think\Http;
class Index extends Controller
{
    public function index()
    {
		$view = new View();
		
		
        return $view -> fetch();
    }
	
	public function index1($search = "",$sex=0,$ordertype=0,$sc=0,$stype=1,$fenleitype=0,$zhufenlei=0,$fenlei = 0)
    {
		$view = new View();
		$getGzh = new Gzh();
		$userinfo = "";
		
		
		$zflname="";
		$flname="";
		
		if(!session('userid'))
		{
			if(isset($_GET["code"]))
			{
				$code = input('get.code');
				$opid = $getGzh->GetOpenID($code);
				
							
				$token = cache($opid.'token');
				$userinfo = $getGzh->GetUserInfo($opid,$token);
				$this -> SaveUser($opid,$userinfo);
				
			}
			else
			{
				$opid = session('openid');
				
					
			}
		
		}
		
		$scinfo = 'desc';
		
		
		
		if($sc > 0)
		{
			$scinfo = 'asc';
		}
		
		$firstload = Db::table('userinfo')->alias('a')->field('a.FirstLoad,a.SchoolID')->where('a.ID',session('userid'))->find();
		if($firstload['FirstLoad'] == 0)
		{
			return $this -> redirect('index/index/firstload?userid='.session('userid'));
		}
	
		
		$schoolid = 0;
		$schoolname = '定位';
		if(!isset($_COOKIE['schoolhistory']))
		{
			$schoolid = $firstload['SchoolID'];
			$schoolinfo = Db::table('schoolinfo')->field('SchoolName')->where('SchoolID',$schoolid)->find();
			$schoolname = $schoolinfo['SchoolName'];
			$schoolhistory = [
				'schoolid' => $schoolid,
				'schoolname' => $schoolname
				
			];
			cookie('schoolhistory', $schoolhistory, 7200);
		}
		else
		{
			$schoolhistory = cookie('schoolhistory');
			
			$schoolid = $schoolhistory['schoolid'];
			$schoolname = $schoolhistory['schoolname'];
			
		}
		
		$bannerlist = Db::table('bannerinfo')->order(['Order'=>'desc'])->select();
		$schoollist = Db::table('schoolinfo')->field('SchoolID,SchoolName')->select();
		
		///saleinfo先按折扣,库存排序
		$subQuery = '(SELECT * FROM saleinfo ORDER BY if (Count=0,0,1)desc,Discount asc,Count Desc)';
		
		$salebooklist = Db::table($subQuery)->field('sum(a.Count) as sumc,w.*,a.*,a.Status as S_Status,a.ID as sid,c.Sex,a.Discount * w.Amount *0.01 as rprice,r.UserList')->alias('a')
				->where('a.Status',1)
				->where('a.SchoolID',$schoolid)
				->join('bookinfo w','a.BookID = w.ID','LEFT')
				->join('userinfo c','a.Suserid = c.ID','LEFT')		
				->join('daohuoinfo r','r.BookID = a.BookID','LEFT');
		if($search != "")
		{
			if($stype == 1)
			{
				$salebooklist = $salebooklist -> where('w.BookName','like','%'.$search.'%');
			}
			else if($stype == 2)
			{
				$salebooklist = $salebooklist -> where('w.Author','like','%'.$search.'%');
			}
			else if($stype == 3)
			{
				$salebooklist = $salebooklist -> where('w.PublishName','like','%'.$search.'%');
			}
			
		}
		if($fenleitype > 0)
		{
			
			$salebooklist = $salebooklist -> where('a.BookType',$fenleitype);
		}
		if($zhufenlei > 0)
		{
			$zhufenleilist =Db('fenleiinfo')->field('ID')->where('ParentID',$zhufenlei)->order(['Order'=>'desc','Name'=>'asc'])->select();
			$zhufenleiinfo = Db('fenleiinfo')->field('Name')->where('ID',$zhufenlei)->find();
			
			$zflname = $zhufenleiinfo['Name'];
			$zhufenleiarray = array(-1);
			foreach($zhufenleilist as $k1 => $k2)
			{
				array_push($zhufenleiarray,$k2['ID']);
			}
		
			if(count($zhufenleiarray) > 0)
			{
				$salebooklist = $salebooklist -> where('a.CataID','IN',$zhufenleiarray);
			}

		}
		if($fenlei > 0)
		{
			$salebooklist = $salebooklist -> where('a.CataID',$fenlei);
		}
		if($sex > 0)
		{
			$salebooklist = $salebooklist -> where('c.Sex',$sex);
		}			
		if($ordertype > 0)
		{
			if($ordertype == 1)
			{
				$salebooklist = $salebooklist -> order('a.Discount '.$scinfo);
			}
			else if($ordertype == 2)
			{
				$salebooklist = $salebooklist -> order('rprice '.$scinfo);
			}
			else if($ordertype == 3)
			{
				$salebooklist = $salebooklist -> order('a.CreateTime '.$scinfo);
			}
			else if($ordertype == 4)
			{
				$salebooklist = $salebooklist -> order('w.PublishDate '.$scinfo);
			}			
			
		}
		else{
			$salebooklist = $salebooklist -> order('a.CreateTime '.$scinfo);
		}		
		
		$salebooklist = $salebooklist->group('a.BookID')->select();
		$fenleiresult = array();
		$fenleilist = Db::table('fenleiinfo')
						
						->order(['Order'=>'desc','Name'=>'asc'])->select();
		
		$fenleiinfo =Db::table('fenleiinfo a')->field('a.Name,b.Name as zflname,a.ParentID')->alias('a')
						->join('fenleiinfo b','a.ParentID = b.ID','LEFT')
						->where('a.ID',$fenlei)->find();
						
		$fenleiresult[1]['row'][] = null;
		$fenleiresult[1]['ftype'] = 1;	
		$fenleiresult[2]['row'][] = null;
		$fenleiresult[2]['ftype'] = 2;	
		$fenleiresult[3]['row'][] = null;
		$fenleiresult[3]['ftype'] = 3;			
		foreach($fenleilist as $k1 => $k2)
		{
			if($k2['Type']==1 && $k2['SchoolID'] == $schoolid)
			{

				$fenleiresult[$k2['Type']]['row'][] = $k2;
				$fenleiresult[$k2['Type']]['ftype'] = $k2['Type'];

			}
			else if($k2['Type'] > 1)
			{
				$fenleiresult[$k2['Type']]['row'][] = $k2;
				$fenleiresult[$k2['Type']]['ftype'] = $k2['Type'];
			}
			
		}
	

		
		$view -> userid = session('userid');
		$view -> salebooklist = $salebooklist;
		$view -> bannerlist = $bannerlist;
		$view -> schoolid = $schoolid;
		$view ->schoolname = $schoolname;
		$view -> search = $search;
		$view -> sex = $sex;
		$view -> ordertype = $ordertype;
		$view -> fenleilist = $fenleiresult;
		$view -> sc = $sc;
		$view -> stype = $stype;
		$view -> fenlei = $fenlei;
		$view -> fenleitype = $fenleitype;
		$view -> zhufenlei = $zhufenlei;
		$view -> zflname = $zflname;
		$view -> fenleiinfo = $fenleiinfo;
		$view -> schoollist = $schoollist;
		return $view -> fetch();
    }
	
	public function shopcart($user_id=4)
	{
		$view = new View();
			
		$user_id = session('userid');

		$typelist = Db::table('shopcartinfo') -> alias('a')
							->field('a.*,a.Count as scount,a.ID as sid,b.*,c.*,d.*')
							->join('bookinfo b','a.BookID = b.ID','LEFT')
							->join('saleinfo d','d.ID = a.SaleID','LEFT')		
							->join('userinfo c','d.Suserid = c.ID','LEFT')		
							->where('a.UserID',$user_id)													
							->select();

		$shopcartlist = array();

		foreach($typelist as $k=>$v)
		{
		    $shopcartlist[$v['Suserid']]['name']    =   $v['NickName'];
		    $shopcartlist[$v['Suserid']]['sex']    =   $v['Sex'];
		    $shopcartlist[$v['Suserid']]['suserid']    =   $v['Suserid'];
		    $shopcartlist[$v['Suserid']]['userimg']    =   $v['UserImg'];
		    $shopcartlist[$v['Suserid']]['row'][]    =   $v;
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
		
		

		$view -> shopcartlist = $shopcartlist;
		$view -> countlist = count($shopcartlist);
		$view -> stotal = $stotal;
		$view -> scount = $scount;
		$view -> userid = $user_id;
        return $view -> fetch();
	}
	
	public function bookinfo($userid = 4, $bookid = 0,$type="",$cartid = 0)
	{
		$view = new View();
		$userid = session('userid');
		
		$schoolid = 0;
		$schoolname = '定位';
		if(!isset($_COOKIE['schoolhistory']))
		{
			$firstload = Db::table('userinfo')->alias('a')->field('a.FirstLoad,a.SchoolID,b.SchoolName')->join('schoolinfo b','a.SchoolID = b.SchoolID')->where('a.ID',session('userid'))->find();
			$schoolid = $firstload['SchoolID'];
			$schoolname = $firstload['SchoolName'];
			$schoolhistory = [
				'schoolid' => $schoolid,
				'schoolname' => $schoolname
				
			];
			cookie('schoolhistory', $schoolhistory, 7200);
		}
		else
		{
			$schoolhistory = cookie('schoolhistory');
			
			$schoolid = $schoolhistory['schoolid'];
			$schoolname = $schoolhistory['schoolname'];
			
		}
		$userinfo = Db::table('userinfo')->where('ID',$userid )->find();
		
		$bookinfo = Db::table('saleinfo')->field('sum(a.Count) as sumc,w.*,a.*,a.Status as S_Status,x.*,a.ID as saleid,z.UserList')->alias('a')
				->join('bookinfo w','a.BookID = w.ID','LEFT')	
				->join('userinfo x','a.SuserID = x.ID','LEFT')
				->join('daohuoinfo z','a.BookID = z.BookID','LEFT')
				
				->where('a.BookID',$bookid)	
				->where('a.SchoolID',$schoolid)
				->find();
		if(!$bookinfo)
		{
			return redirect('index/index/fail');
		}			
		$view -> userid = $userid;
		$sbookimg = "";
		if($bookinfo['Img'] != "" && $bookinfo['Img']!=null)
		{
			$sbookimg = explode(",",$bookinfo['Img']);
			array_pop($sbookimg);
		}

	
		
		
		$shopcartsum = Db::table('shopcartinfo')->field('sum(Count) as shopcartsum')->where('Userid',$userid)->find();		
		
		$salebooklist = Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status,a.ID as sid,x.NickName,x.ID as uid,x.Sex,x.UserImg,r.UserList')->alias('a')
							->join('bookinfo w','a.BookID = w.ID','LEFT')	
							->join('userinfo x','a.Suserid = x.ID','LEFT')
							->join('daohuoinfo r','a.BookID = r.BookID','LEFT')
							->where('a.BookID',$bookinfo['BookID'])		
							->where('a.SchoolID',$schoolid)
							->order('Discount asc')
							->select();
		
		$view -> salebooklist = $salebooklist;	
		
		$view -> bookinfo = $bookinfo;
		
		$view -> sbookimg = $sbookimg;
		$view -> changetype = $type;
		$view -> userinfo = $userinfo;
		$view ->collect = iscollect($userinfo['Collect'],$bookinfo['saleid']);
		$view -> cartid = $cartid;
		$view -> sum = $shopcartsum['shopcartsum']?$shopcartsum['shopcartsum']:0;

        return $view -> fetch();
	}	
	
	public function kk1()
	{
		$view = new View();
		

        return $view -> fetch();
	}
	
	public function test()
	{
		
		return 'ok';
	}
	
	public function SaveUserInfo($opid,$userimg=0,$nickname,$sex=1,$school,$area,$class,$department)
	{
		$params=[
			'NickName' => $nickname, 'Sex' => $sex , 'School' => $school, 'Area' => $area,
			'UserImg' => $userimg, 'CreateTime' => date("Y-m-d H:i:s"), 'Department' => $department,
			'OpenID' => $opid			
		];
		$result = Db::table('userinfo')->insert($params);
		return $result;
	}
	
	public function test1()
	{
		$kk2 = Db::table("bookinfo")->select();
		$kk = ['total' => '20','rows' =>$kk2];
		return json_encode($kk,JSON_UNESCAPED_UNICODE);
	}
	
	public function userinfo()
	{
		$view = new View();
		
		$userinfo = Db::table('userinfo')->where('ID',session('userid'))->find();
		$view -> userinfo = $userinfo;
		
		return $view -> fetch();
	}
	public function kk2($bookid)
		{
			
			Cache::set('name',222,3600);
			Cache::get('name');
			echo Cache::get('name');
		}
		
			public function GetShopCartTotal($scid=0,$type=0)
	{
		$juery = 'call test('.$scid.','.$type.')';
		echo $juery;
		$result = Db::query('call test(0,0)');
		dump($result);
	}
	
	public function footer()
	{
		$view = new View();
		

        return $view -> fetch();
	}
	
	public function nav()
	{
		$view = new View();
		

        return $view -> fetch();
	}	
	
	public function browserecords()
	{
		$view = new View();
		

        return $view -> fetch();		
	}
	
	public function yonghushujia($suserid = 4)
	{
		
		$view = new View();		
		$salebooklist = Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status')->alias('a')
				->join('bookinfo w','a.BookID = w.ID','LEFT')	
				
				->where('a.Suserid',$suserid)				
				->select();
		$suserinfo = Db::table('userinfo')->alias('a')
						->field('a.*,b.*')
						->join('schoolinfo b','a.SchoolID = b.SchoolID','LEFT')
						->where('ID',$suserid)->find();	
								
		$view -> salebooklist = $salebooklist;	
		$view -> suserinfo = $suserinfo;			
		$view -> suserid = $suserid;
		$view -> userid = session('userid');
        return $view -> fetch();			
	}
	
	public function SaveUser($opid,$userinfo)
	{
		$IsExit = Db::table('userinfo')->where('OpenID',$opid)->find();
		$userid = 0;
		if(!$IsExit)
		{
			$params = [
				'OpenID' => $userinfo -> openid,
				'NickName' => $userinfo -> nickname,
				'CreateTime' => time(),
				'UserType' => 1,
				'Sex' => $userinfo -> sex,
				'UserImg' => $userinfo -> headimgurl
			];
			$userid = Db::table('userinfo')->insertGetId($params);
			
		}
		else
		{
			$userid = $IsExit['ID'];
		}

		session('userid',$userid);

		

		return 0;
	}
	
	public function change($bookid,$cartid = 0)
	{
		$this -> isorschoolhistory();
		$schoolhistory = cookie('schoolhistory');			
		$schoolid = $schoolhistory['schoolid'];
			
	
		$userid = session('userid');
		$view = new View();
		$salebooklist = Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status,x.NickName,x.ID as uid,x.Sex,x.UserImg')->alias('a')
							->join('bookinfo w','a.BookID = w.ID','LEFT')	
							->join('userinfo x','a.Suserid = x.ID','LEFT')
							->where('a.BookID',$bookid)		
							->where('a.SchoolID',$schoolid)
							->select();
		
		$view -> salebooklist = $salebooklist;	
		$view -> cartid = $cartid;
		$view -> userid = session('userid');
		return $view -> fetch();
	}
	
	public function gettest()
	{
		$http = new Http();
		$isbn = '9787807315858';
		$url = 'https://api.douban.com/v2/book/isbn/'.$isbn;
		echo $url;
		
		$data = $http->http($url,array());
		
		$bookinfo = json_decode($data);
		$booklimg = $bookinfo->images->small;
		$booksimg = $bookinfo->images->large;
		$booktitle = $bookinfo->title;
		$bookpublish = $bookinfo->publisher;
		$bookisbn10 = $bookinfo->isbn10;
		$bookisbn13 = $bookinfo->isbn13;
		$amount = $this -> findNum($bookinfo->price);
		$author = '';
		if(count($bookinfo->author) > 0)
		{
			$author = $bookinfo->author[0];
		}
		
		$publishdate = $bookinfo->pubdate;
		$summary = $bookinfo->summary;
		$origintitle = $bookinfo->origin_title;
		//$levelnum = $bookinfo->levelNum;
		$pages = $bookinfo->pages;
		$subtitle = $bookinfo->subtitle;
		$inserparam = ['BookName' => $booktitle, 'PublishName' => $bookpublish, 'BookLimg' => $booklimg,
				'BookSimg' => $booksimg, 'Summary' => $summary, 'Author' => $author, 'ISBN10' => $bookisbn10,
				'ISBN13' => $bookisbn13, 'OriginTitle' => $origintitle, 'PublishDate' => $publishdate,
				'Amount' => $amount, 'CreateTime' => date("Y-m-d H:i:s"), 'Pages' => $pages, 'SubTitle' => $subtitle
				
		];
	
		
	
	}
	
	public function findNum($str='')
	{
		$str=trim($str);
		if(empty($str)){return '';}
		$temp=array('1','2','3','4','5','6','7','8','9','0','.');
		$result='';
		for($i=0;$i<strlen($str);$i++){
		if(in_array($str[$i],$temp)){
		$result.=$str[$i];
		}
		}
		return $result;
	}
	
	public function collect()
	{
		$view = new View();
		$salebooklist = array();
		$userid = session('userid');
		$userinfo = Db::table('userinfo')->where('ID',$userid)->find();
		$collect = $userinfo['Collect'];
		if($collect != "" && $collect != null)
		{
			$collect = substr($collect,0,-1);
		}
		$salebooklist = Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status')->alias('a')
				->join('bookinfo w','a.BookID = w.ID','LEFT')	
				
				->where('a.ID','IN',$collect)				
				->select();
		$view -> salebooklist = $salebooklist;
		$view -> userid = $userid;
		return $view -> fetch();
	}
	
	public function student($type=0,$redu=0)
	{
		$view = new View();
		$mercount = $this->getmertypecount();
		$isorschoolhistory = $this->isorschoolhistory(); ///判断学区cookies是否存在
		
		$schoolhistory = cookie('schoolhistory');
				
		$baidukey = config('baidu.key');
		$schoolid = $schoolhistory['schoolid'];
		$schoolinfo = Db::table('schoolinfo')->where('SchoolID',$schoolid)->find();
		$schoollist = Db::table('schoolinfo')->order(['Order'=>'desc','SchoolName'=>'asc'])->select();
		$merinfo = Db::table('merchantinfo')->where('School',$schoolid);
		
		if($type > 0)
		{
			$merinfo = $merinfo -> where('Type',$type);
		}
		if($redu == 1)
		{
			$merinfo = $merinfo -> order('Redu desc');
		}
		else if($redu == 2)
		{
			$merinfo = $merinfo -> order('Redu asc');
		}	
		
		$merinfo = $merinfo->select();
		$view -> merinfo = $merinfo;
		$view -> mercount = $mercount;
		$view -> schoolinfo = $schoolinfo;
		$view -> schoollist = $schoollist;
		$view -> baidukey = $baidukey;
		$view ->type = $type;
		$view ->redu = $redu;
		return $view -> fetch();
	}
	
	public function wpr()
	{
		$view = new View();
		return $view -> fetch();
	}
	
	public function merinfo($mid=0)
	{
		$view = new View();
		$merinfo = Db::table('merchantinfo')->where('ID',$mid)->find();
		$shoplist = Db::table('shopinfo')->where('MerID',$merinfo['ID'])->select();
	
		$view -> merinfo = $merinfo;
		$view -> shoplist = $shoplist;

		$view -> userid = session('userid');
		return $view -> fetch();
	}
	
	public function merdetail($id = 0)
	{
		$view = new View();
		$shopinfo = Db::table('shopinfo')->where('ID',$id)->find();
		$view ->shopinfo = $shopinfo;
		$view ->userid = session('userid');
		return $view -> fetch();
	}
	
	public function merorder()
	{
		$view = new View();
		return $view -> fetch();
	}	
	
	public function k3()
	{
		echo 11;
		cookie('kk1', 'value', 3600);
		dump($_COOKIE);
	}
	
	public function bookinfocookie($saleid)
	{
		if(!isset($_COOKIE['bookhistory']))
		{
	
			$result = array();
			array_push($result,$saleid);		
		
			cookie('bookhistory', json_encode($result), 3600);
		}
		else
		{
			$result = json_decode(cookie('bookhistory'));
			if(in_array($saleid,$result))
			{
				
			}
			else
			{
				array_push($result,$saleid);
				cookie('bookhistory', json_encode($result), 3600);
			}
		}
		
	}
	
	public function merpayurlcookie($url1)
	{
		cookie('merpaycookie', $url1, 3600);
		echo $url1;
	}

	public function studentinfocookie($id)
	{
		if(!isset($_COOKIE['studenthistory']))
		{
	
			$result = array();
			array_push($result,$id);		
		
			cookie('studenthistory', json_encode($result), 3600);
		}
		else
		{
			$result = json_decode(cookie('studenthistory'));
			if(in_array($id,$result))
			{
				
			}
			else
			{
				array_push($result,$id);
				cookie('studenthistory', json_encode($result), 3600);
			}
		}
		
	}
	
	public function history($type=0)
	{
		$view = new View();
		

		$salebooklist = array();
		$studentlist = array();
		
		
		if($type == 0)
		{
			$bookcookies = cookie('bookhistory');
			$arraybook = str_replace('"','',$bookcookies);
			$arraybook = substr($arraybook,1);
			$arraybook = substr($arraybook,0,-1);
			$salebooklist = Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status')->alias('a')
					->join('bookinfo w','a.BookID = w.ID','LEFT')	
					
					->where('a.ID','IN',$arraybook)				
					->select();
		}
		else if($type == 1)
		{
			$studentcookies = cookie('studenthistory');		
			$arraystudent = str_replace('"','',$studentcookies);
			$arraystudent = substr($arraystudent,1);
			$arraystudent = substr($arraystudent,0,-1);
			$studentlist = Db::table('merchantinfo')->where('ID','IN',$arraystudent)->select();
		}
		$view -> salebooklist = $salebooklist;
		$view -> studentlist = $studentlist;
		$view -> type = $type;
		$view -> userid = session('userid');
		return $view -> fetch();
		
		//return $view -> fetch();
	}
	
	public function fail()
	{
		$view = new View();
		return $view -> fetch();
	}
	
	public function getsessioncode()
	{
		session_start();
		$kk1 = $_SESSION['vcode'];
		
	}
	
	public function buy($bookid)
	{
		
		$schoolid = 0;
		$schoolname = '定位';
		if(!isset($_COOKIE['schoolhistory']))
		{
			$firstload = Db::table('userinfo')->alias('a')->field('a.FirstLoad,a.SchoolID,b.SchoolName')->join('schoolinfo b','a.SchoolID = b.SchoolID')->where('a.ID',session('userid'))->find();
			$schoolid = $firstload['SchoolID'];
			$schoolname = $firstload['SchoolName'];
			$schoolhistory = [
				'schoolid' => $schoolid,
				'schoolname' => $schoolname
				
			];
			cookie('schoolhistory', $schoolhistory, 7200);
		}
		else
		{
			$schoolhistory = cookie('schoolhistory');
			
			$schoolid = $schoolhistory['schoolid'];
			$schoolname = $schoolhistory['schoolname'];
			
		}
		
		$userid = session('userid');
		$view = new View();
		$salebooklist = Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status,a.ID as sid,x.NickName,x.ID as uid,x.Sex,x.UserImg')->alias('a')
							->join('bookinfo w','a.BookID = w.ID','LEFT')	
							->join('userinfo x','a.Suserid = x.ID','LEFT')
							->where('a.BookID',$bookid)		
							->where('a.SchoolID',$schoolid)
							->order('Discount asc')
							->select();
		
		$view -> salebooklist = $salebooklist;	
	
		$view -> userid = session('userid');
		return $view -> fetch();
	}
	
	public function studentpay()
	{
		$view = new View();
		return $view ->fetch();
	}
	
	public function firstload()
	{
		$view = new View();
		$schoolinfo = Db::table('schoolinfo')->select();
		//$departmentlist = Db::table('fenleiinfo')->where('SchoolID','1')->where('ParentID',0)->order(['Order'=>'desc','Name'=>'asc'])->select();
		//$view -> departmentlist = $departmentlist;
		$view -> schoollist = $schoolinfo;
		return $view -> fetch();
	}
	
	public function getfirst($sclass,$schoolid,$department,$major)
	{
		
		$params = [
			'SchoolID' => $schoolid,
			'Class' => $sclass,
			'Department' => $department,
			'Major' => $major,
			'FirstLoad' => 1
		];
		$result = Db::table('userinfo')->where('ID',session('userid'))->update($params);	
	
		$schoolinfo = Db::table('schoolinfo')->where('SchoolID',$schoolid)->field('SchoolName')->find();
		$schoolname = $schoolinfo['SchoolName'];
		
		$schoolhistory = [
			'schoolid' => $schoolid,
			'schoolname' => $schoolname
		];
		cookie('schoolhistory', $schoolhistory, 7200);	

		
		return $result;
	}
	
	public function getmajor($dpid)
	{
		$majorlist = Db::table('fenleiinfo')->where('ParentID',$dpid)->order(['Order'=>'desc','Name'=>'asc'])->select();
		return json_encode($majorlist,JSON_UNESCAPED_UNICODE);
	}
	
	public function getxueyuan($schoolid)
	{
		$zhuanyelist = Db::table('fenleiinfo')->where('ParentID',0)->where('SchoolID',$schoolid)->order(['Order'=>'desc','Name'=>'asc'])->select();
		return json_encode($zhuanyelist,JSON_UNESCAPED_UNICODE);
	}	
	
	public function incomeinfo()
	{
		$view = new View();
		$userid = session('userid');
		$bookorderlist = Db::table('orderinfo')->field('OrderID,Amount as Price1,1 as OrderType,CreateTime,2 as PType')->where('UserID',$userid)->where('Status','IN','2,4,6')->select();
		$studentorderlist = Db::table('studentorder')->field('OrderID,Price as Price1,2 as OrderType,CreateTime,3 as PType')->where('UserID',$userid)->where('Status',0)->select();
		$userincomelist = Db::table('incomeinfo')->field('OrderID,Income as Price1,1 as OrderType,CreateTime,1 as PType')->where('Suserid',$userid)->whereOr('UserID',$userid)->select();
		$result = array();
		$result = array_merge($result,$bookorderlist);
		$result = array_merge($result,$studentorderlist);
		$result = array_merge($result,$userincomelist);
		

		$result = arraySort($result, 'CreateTime', 'desc');
		$view -> incomelist = $result;
		return $view -> fetch();
	}
	
	public function incomedetail($ptype = 0,$orderid)
	{
		$view = new View();
		if($ptype == 1)
		{
			$result = Db::table('incomeinfo')->alias('a')->field('a.OrderID,a.Type,a.CreateTime,a.PayType,a.Income as Price,b.SaleID')
					->join('orderinfo b','a.OrderID = b.OrderID')
					->where('a.OrderID',$orderid)->find();
					
			$result['SaleID'] = json_decode($result['SaleID']);   //获取书本交易信息
			
		}
		else if($ptype == 2)
		{
			$result = Db::table('orderinfo')->field('OrderID,CreateTime,PayType,Amount as Price,SaleID')
					->where('OrderID',$orderid)->find();
					$result['SaleID'] = json_decode($result['SaleID']);   //获取书本交易信息
					
		}
		else if($ptype == 3)
		{
			$result = Db::table('studentorder')->field('OrderID,CreateTime,PayType,Price,Content')
						
					->where('OrderID',$orderid)->find();
					$result['Content'] = json_decode($result['Content']);   //获取学生折交易信息
		}
		$view -> result = $result;
		$view -> ptype = $ptype;
		$view -> orderid = $orderid;
		return $view -> fetch();
		
		
	}
	
	public function getordercount()
	{
		$userid = session('userid');
		$result = Db::table('orderinfo')->field('count(case when Status = 1 then Status end) as count1,count(case when Status = 2 then Status end) as count2,count(case when Status = 5 then Status end) as count3')->where('UserID',session('userid'))->find();
		$couponinfo = Db::table('couponinfo')->field('count(ID) as cp1')->where('UserID',$userid)->where('Type',0)->where('ShopID','>',0)->find();
		$result = array_merge($result,['total'=> $result['count1'] + $result['count2'] + $result['count3'] + $couponinfo['cp1']]);
		$result = array_merge($result,$couponinfo);
		return json_encode($result);
	}
	
	public function getmertypecount()
	{
		$isorschoolhistory = $this->isorschoolhistory();
		$schoolhistory = cookie('schoolhistory');
		$schoolid = $schoolhistory['schoolid'];
		$result = Db::table('merchantinfo')->field('count(case when Type = 1 then Type end) as count1,count(case when Type = 2 then Type end) as count2,count(case when Type = 3 then Type end) as count3,count(case when Type = 4 then Type end) as count4,count(case when Type = 5 then Type end) as count5,count(case when Type = 6 then Type end) as count6')
					->where('School',$schoolid)
					->find();
		return $result;
	}
	
	///判断学区cookies是否存在
	public function isorschoolhistory()
	{
		$schoolhistory = '';
		if(!isset($_COOKIE['schoolhistory']))
		{
			$useinfo = Db::table('userinfo')->alias('a')->field('a.FirstLoad,a.SchoolID,b.SchoolName')->join('schoolinfo b','a.SchoolID = b.SchoolID')->where('a.ID',session('userid'))->find();
			$schoolid = $useinfo['SchoolID'];
			$schoolname = $useinfo['SchoolName'];
			$schoolhistory = [
				'schoolid' => $schoolid,
				'schoolname' => $schoolname
				
			];
			cookie('schoolhistory', $schoolhistory, 7200);
		}

		return 'ok';
	}
	

}
