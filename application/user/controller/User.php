<?php
namespace app\user\controller;
use think\Db;
use think\View;
use think\Request;
use think\Controller;
use think\Session;
use think\Http;
use think\Exception;
use app\salebook\controller\SaleBook;
use think\Cache;

class User extends Controller
{
	///用户审核通过
	public function ChangeIDType($userid,$type,$id,$schoolcard)
	{	
		$param = ['Status' => $type  ];

		if($type == 2)
		{
			Db::table("userinfo") -> where('ID',$userid) -> update(['UserType' => 2,'Remark' => $schoolcard]);
		}
		if($type == 3)
		{
			Db::table("userinfo") -> where('ID',$userid) -> update(['UserType' => 5]);
		}
		
		$result = Db::table("idinfo") -> where('ID',$id) -> update($param);
		return $result;
	}
	
	public function edituser()
	{
		$view = new view();
		$openid = session('openid');

		$userinfo = Db::table('userinfo')->field('a.*,b.*')->alias('a')
					->join('schoolinfo b','a.SchoolID = b.SchoolID','LEFT')
					->where('OpenID',$openid)->find();
		$schoollist = $this -> GetSchoolList();
		$departmentlist = Db::table('fenleiinfo')->where('SchoolID','1')->where('ParentID',0)->order(['Order'=>'desc','Name'=>'asc'])->select();
		$majorlist = Db::table('fenleiinfo')->field('b.*')->alias('a')
		->join('fenleiinfo b','a.ID = b.ParentID')->where('a.Name',$userinfo['Department'])->order(['Order'=>'desc','Name'=>'asc'])->select();
		
		
		$view -> departmentlist = $departmentlist;
		$view -> majorlist = $majorlist;
		$view ->userinfo = $userinfo;
		$view ->schoollist = $schoollist;
		return $view -> fetch();
	}
	
	public function editmobile()
	{
		$view = new view();
		return $view -> fetch();
	}
	
	public function editsex()
	{
		$view = new view();
		return $view -> fetch();		
	}
	
	public function editschool()
	{
		$view = new view();
		return $view -> fetch();		
	}	
	
	public function EditInfoMode($ret = 0)
	{
		$request = Request::instance();
		$info = $request->param();

		$param = [ $info['obj'] => $info['val']];
		
		
		try
		{
			$result = Db::table('userinfo')->where('OpenID',session('openid'))->update($param);
			if($ret == 0)
			{
				return $this->redirect('user/user/edituser');
			}
		}
		catch(Exception $ex)
		{
			return $this->redirect('wallet/wallet/errorindex');
		}
	}
	
	///我要卖书(带图片上传)
	public function test222($type=0,$userid=0,$saleid=0,$bookid=0,$booktype = 1)
	{
		$request = Request::instance();
		//$file = $request->file('file');

		
		
		$content = $_POST['content'];
		$discount = $_POST['discount'];
		$count = $_POST['count'];
		$img = '';
		
		$param = [
			"Discount" => $discount,
			"Content" => $content,
			"BookID" => $bookid,
			"Suserid" => $userid,
			"Type" => 2,
			"CreateTime" => date("Y-m-d H:i:s"),
			"CreateDate" => date("Y-m-d"),
			"Count" => $count,
			"Status" => 1,
			"BookType" => $booktype
			];
		
		$imgparam = array();
		if($_POST['filecount'])
		{
			$filecount = $_POST['filecount'];
			
			if($filecount > 0)
			{
				$filelist = array();
				for($i = 0;$i < $filecount; $i++)
				{
					array_push($filelist,$_FILES['img'.$i]);
				}
				
			}

				// 移动到框架应用根目录/public/uploads/ 目录下
			if($filelist){
				$dir = './uploads/'.date("Ymd");
				
				if(!is_dir($dir)) 
				{
					mkdir($dir);
				}
				
				for($i = 0; $i < $filecount; $i++)
				{				
					$filename = time().$i.substr($filelist[$i]['name'], strrpos($filelist[$i]['name'],'.'));  
					$path = date("Ymd")."/".$filename;
					move_uploaded_file($filelist[$i]['tmp_name'], $dir.'/'.$filename);
					$img = $img.$path.',';
				}
				$imgparam = ["Img" => $img];
				$param = array_merge($param,$imgparam);
			}
		}
		if($type ==0)
		{	
	
			$isExit = Db::table('saleinfo')->where('BookID',$param['BookID'])->where('Suserid',$param['Suserid'])->find();
			if($isExit)
			{
				return json_encode(["code" => 'no']);
			}
			$result = Db::table('saleinfo')->insert($param);			
		}
		else
		{	
			$editparam = [
				"Discount" => $discount,
				"Content" => $content,
				"Count" => $count,
				"Status" => 1
				];
			$editparam = array_merge($editparam,$imgparam);

			$result = Db::table('saleinfo')->where('ID',$saleid)->update($editparam);
			$bookcache = Cache::get('booksidsid_'.$saleid);
		
			if($bookcache)
			{
				$bookinfo = $bookcache;
				$bookinfo['Count'] = $count;
				$bookinfo['Content'] = $content;
				$bookinfo['Discount'] = $discount;
				Cache::set('booksid_'.$saleid,$bookinfo,3600);	
			}
			else
			{
				$bookinfo =	Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status')->alias('a')
					->join('bookinfo w','a.BookID = w.ID','LEFT')	
					->where('a.ID',$saleid)		
					->find();
				Cache::set('booksid_'.$saleid,$bookinfo,3600);	
			}
		}
		$code = json_encode(["code" => $result]);
		return $code;
	}	

	///我要卖书(不带图片上传)
	public function test333($type=0,$userid=0,$saleid=0,$bookid=0,$booktype = 1)
	{
		$request = Request::instance();
		//$file = $request->file('file');

		
		$userinfo = Db::table('userinfo')->field('SchoolID')->where('ID',$userid)->find();
	
		$discount = $_POST['discount'];
		$count = $_POST['count'];
		$booktype = $_POST['booktype'];
		$kecheng = $_POST['kecheng'];
		$fenleiid = $_POST['fenleiid'];
		$schoolid = $userinfo['SchoolID'];
		
		$param = [
			"Discount" => $discount,			
			"BookID" => $bookid,
			"Suserid" => $userid,
			"Type" => 2,
			"CreateTime" => date("Y-m-d H:i:s"),
			"CreateDate" => date("Y-m-d"),
			"Count" => $count,
			"Status" => 1,
			"BookType" => $booktype,
			'CataID' => $fenleiid,
			'KeCheng' => $kecheng,
			'SchoolID' => $schoolid
			];		

		if($type ==0)
		{	
	
			$isExit = Db::table('saleinfo')->where('BookID',$param['BookID'])->where('Suserid',$param['Suserid'])->find();
			if($isExit)
			{
				return json_encode(["code" => 'no']);
			}
			$result = Db::table('saleinfo')->insert($param);			
		}
		else
		{	
			$editparam = [
				"Discount" => $discount,
				
				"Count" => $count,
				"Status" => 1,
				"BookType" => $booktype,
				"CataID" => $fenleiid,
				"KeCheng" => $kecheng
				];
	
			$result = Db::table('saleinfo')->where('ID',$saleid)->update($editparam);
		
		

			$bookinfo =	Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status')->alias('a')
				->join('bookinfo w','a.BookID = w.ID','LEFT')	
				->where('a.ID',$saleid)		
				->find();

		}
		$code = json_encode(["code" => $result]);
		return $code;
	}	
	
	public function test111()
	{
		
		$request = Request::instance();
		$userid = $_POST['userid'];
		$file = $request->file('img');	
	

		    // 移动到框架应用根目录/public/uploads/ 目录下
		if($file){
			

			$info = $file ->validate(['size'=>2300000,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
			if($info){
				
				
				
				$fimage = $info->getSaveName();
				$fsize = $info->getSize();
				$fext = $info->getExtension();
					
				$savename = $info->getFilename(); 
				$param = ['SaveName' => $savename, 'Ext' => $fext, 'Size' => $fsize, 'SavePath' => $fimage, 'SaveDate' => date("Y-m-d H:i:s")];
				Db::table("fileinfo")->insert($param);
				$result = Db::table("userinfo")->where('ID',$userid)->update(["UserImg" => $fimage]);
				$code = ["path" => $fimage, "error" => $result];
				echo json_encode($code);
				
			}else{
				// 上传失败获取错误信息
				echo $file->getError();
			}
		}
		else{
				echo '上传失败';
				
			}	
	}

	///实名认证上传图片
	public function realnameupload()
	{
		
		$request = Request::instance();
		$userid = $_POST['userid'];
		$file = $request->file('img');	
	

		    // 移动到框架应用根目录/public/uploads/ 目录下
		if($file){
			

			$info = $file ->validate(['size'=>2300000,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
			if($info){
				
				
				
				$fimage = $info->getSaveName();
				$fsize = $info->getSize();
				$fext = $info->getExtension();
					
				$savename = $info->getFilename(); 
				$param = ['SaveName' => $savename, 'Ext' => $fext, 'Size' => $fsize, 'SavePath' => $fimage, 'SaveDate' => date("Y-m-d H:i:s")];
				$result = Db::table("fileinfo")->insert($param);
				
				$code = ["path" => $fimage, "error" => $result];
				echo json_encode($code);
				
			}else{
				// 上传失败获取错误信息
				echo $file->getError();
			}
		}
		else{
				echo '上传失败';
				
			}	
	}

	
	public function loadbook()
	{
		$view = new View();
		$wpay = controller('wpay/wpay');	
		$request = Request::instance();		
		
		$url = urldecode($request->url(true));
		
		$appid = config('wpay.appid');		
		$time = time();
		$noncestr = $wpay -> CreateNoncestr();
		$jsapi_ticket = $wpay -> GetTicket();
		$token = $wpay -> GetToken();
		
		$data = [
			'jsapi_ticket' =>$jsapi_ticket,
			'noncestr' => $noncestr,
			'timestamp' => $time,
			'url' => $url,
		];	

		$signature = $wpay -> ToSign($data,true);		
		
		$result = array_merge($data,['signature' => $signature,'appId' => $appid]);	
		
		$view->assign('appid',$result['appId']);
        $view->assign('noncestr',$result['noncestr']);
		$view->assign('time',$result['timestamp']);
		$view->assign('sign',$result['signature']);

		$view -> userid = session('userid');	
		
		return $view ->fetch();
	}
	
	public function GetSchoolList()
	{

		if(cache('schoollist'))
		{
			return cache('schoollist');
		}
		else
		{
			$result = Db::table('schoolinfo')->select();
			cache('schoollist', NULL); 
			cache('schoollist',$result);
			return cache('schoollist');
		}
	}
	
	public function editbook()
	{
		$view = new View();		
		$suserid = session('userid');
		//$suserid = 8;
		$salebooklist = Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status,a.ID as sid')->alias('a')
				->join('bookinfo w','a.BookID = w.ID','LEFT')	
				
				->where('a.Suserid',$suserid)				
				->select();
		$suserinfo = Db::table('userinfo')->where('ID',$suserid)->find();		
		$view -> salebooklist = $salebooklist;	
		$view -> suserinfo = $suserinfo;			
		$view -> suserid = $suserid;
		$view -> userid = session('userid');
        return $view -> fetch();	
	}
	
	public function editdetail($saleid)
	{
		$view = new View();
		$userid = session('userid');

		
		
		/*推荐列表*/
		$salelist = Db::table('saleinfo a')->field('a.*,b.Name as sname,c.Name as fname,b.ID as sflid,d.Amount,c.ID as fid')->alias('a')
						->join('fenleiinfo b','a.CataID = b.ID','LEFT')
						->join('fenleiinfo c','b.ParentID = c.ID','LEFT')
						->join('bookinfo d','a.BookID = d.ID','LEFT')
						->where('a.ID',$saleid)->find();		

		$bookid = $salelist['BookID'];
		
		
		$fenleilist = Db::table('fenleiinfo')->where('Type',$salelist['BookType'])->select();
		
		
		$view -> bookid = $bookid;
		$view -> bookinfo = $salelist;
		$view -> fenleilist = $fenleilist;
		$view -> userid = $userid;
		return $view -> fetch();
		
	}
	
	public function editschooladd($type="",$val="")
	{
		
		$view = new View();
		$view -> type = $type;
		$view -> val = $val;
		return $view-> fetch();
	}
	public function coupon($type = 2)
	{
		$view = new View();
		$userid = session('userid');
		$couponlist = Db::table('couponinfo')
								
								->where('UserID',$userid)->where('ShopID','>',0);
		
		if($type < 2)
		{
			$couponlist = $couponlist -> where('Type',$type);
		}
		
		$couponlist = $couponlist->order('ID desc')->select();
		foreach($couponlist as $c1 => $c2)
		{
			$couponlist[$c1]['Content'] = json_decode($c2['Content']);
			$couponlist[$c1]['CreateTime'] = date("Y-m-d",strtotime($c2['CreateTime'])); 
			$couponlist[$c1]['EndTime'] = date("Y-m-d",strtotime($c2['EndTime'])); 
			$couponlist[$c1]['ID'] = $c2['ID'];
		}
	
		$view -> couponlist = $couponlist;
		$view -> type = $type;
		
		return $view -> fetch();
	}
	
	public function coupondetail($id=0)
	{
		$view = new View();
		$couponinfo = Db::table('couponinfo')->where('ID',$id)->find();
		$code = $couponinfo['Code'];
		$view -> code = $code;
		return $view -> fetch();
	}
	
	public function realname()
	{
		$view = new View();
		$view -> userid = session('userid');
		$view -> url1 = cookie('merpaycookie');
		return $view -> fetch();
	}
	
	///申请实名认证 -3 - 学号已存在
	public function GetRealName($img,$idcard,$schoolid)
	{
		$xuehao = Db::table('userinfo')->field('Remark')->where('Remark',$idcard)->where('SchoolID',$schoolid)->find(); //判断学号是否存在
		if($xuehao)
		{
			return -3;
		}
		$userinfo = Db::table('userinfo')->field('UserType')->where('ID',session('userid'))->find();
		$usertype = $userinfo['UserType'];
		$resulttype = 4;
		if($usertype == 5)
		{
			$resulttype = 6;
		}
		Db::table('userinfo')->where('ID',session('userid'))->update(['UserType' => $resulttype]);
		
		$param = [
			'UserID' => session('userid'),
			'SchoolCard' => $idcard,
			'Status' => 4,
			'CreateTime' => date("Y-m-d H:i:s"),
			'Img' => $img
		];
		$isExit = Db::table('idinfo')->where('UserID',session('userid'))->where('Status',1)->find();
		if($isExit)
		{
			return 3;
		}
		$result = Db::table('idinfo')->insert($param);
		return $result;
	}
	
	//上传图片
	public function UpLoadImg()
	{
		
		    // 获取表单上传文件 例如上传了001.jpg
		$request = Request::instance();
		$reqParam = $request->param();

		$file = request()->file('file-zh');
		
		
    // 移动到框架应用根目录/public/uploads/ 目录下
		if($file){
		    $info = $file->validate(['size'=>2300000,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
			
			if($info){

				$fimage = $info->getSaveName();
				$fsize = $info->getSize();
				$fext = $info->getExtension();
				
				$savename = $info->getFilename(); 
				
				
				$fimage = str_replace("\\","/",$fimage);
				//生成缩略图

				$simginfo = explode('/',$fimage);
				
				$simgpath = $simginfo[0];
				
				$foo = new \img\Image('../public/uploads/'.$simgpath.'/');
				$simgname = $foo -> thumb($simginfo[1],config('simginfo.width'),config('simginfo.height'));
				
				$param = ['SaveName' => $savename, 'Ext' => $fext, 'Size' => $fsize, 
				'SavePath' => $fimage, 'SaveDate' => date("Y-m-d H:i:s")];
				Db::table("fileinfo")->insert($param);
				
				$result = ['code' => '1','imgpath' => $fimage,'simgpath' => $simgpath.'/'.$simgname];
				echo json_encode($result);
				
			}else{
				// 上传失败获取错误信息
				echo $file->getError();
			}
		}
		else{
				echo '上传失败';
				
			}	
	}
	
	//上传多张图片
	public function UpLoadMubImg()
	{
		
		$request = Request::instance();
		//$file = $request->file('file');
		$img = '';
		

		$imgparam = array();
		$simgparam = array();
		
		if($_POST['filecount'])
		{
			$filecount = $_POST['filecount'];
			
			if($filecount > 0)
			{
				$filelist = array();
				for($i = 0;$i < $filecount; $i++)
				{
					array_push($filelist,$_FILES['img'.$i]);
				}
				
			}

				// 移动到框架应用根目录/public/uploads/ 目录下
			if($filelist){
				$dir = './uploads/'.date("Ymd");
				
				if(!is_dir($dir)) 
				{
					mkdir($dir);
				}
				
				for($i = 0; $i < $filecount; $i++)
				{				
					$filename = time().$i.substr($filelist[$i]['name'], strrpos($filelist[$i]['name'],'.'));  
					$path = date("Ymd")."/".$filename;
					$spath = date("Ymd")."/th_".$filename;
					move_uploaded_file($filelist[$i]['tmp_name'], $dir.'/'.$filename);
					array_push($simgparam,HOSTPATH.'/uploads/'.$spath);
					array_push($imgparam,HOSTPATH.'/uploads/'.$path);
					$img = $img.$path.',';
					
					//生成缩略图				
		
					$foo = new \img\Image($dir.'/');
					$simgname = $foo -> thumb($filename,400,300);
				}
				
				
			}
		}


		
		return json_encode(['imglist' => $imgparam, 'simglist' => $simgparam]);
	}	
	
	/// 3 - 验证码错误 1 - 成功
	public function editmobilemode($mobile,$code)
	{
		session_start();
		$checkcode = $_SESSION['vcode'];
		if($code != $checkcode)
		{
			return 3;
		}
		$result = Db::table('userinfo')->where('ID',session('userid'))->update(['PhoneType' => 2,'Phone' => $mobile]);
		return $result;
	}
	
	public function getcode()
	{
		
		try {
			// 请根据实际 appid 和 appkey 进行开发，以下只作为演示 sdk 使用
			$appid = config('sms1.appid');
			$appkey = config('sms1.appkey');
			$phoneNumber1 = "12345678901";
			$phoneNumber2 = $_POST['num'];
			$phoneNumber3 = "12345678903";
			$templId = config('sms1.codeid');

			$singleSender = new \phpsms\SmsSingleSender($appid, $appkey);
			$util = new \phpsms\SmsSenderUtil();
			// 普通单发
			/*$result = $singleSender->send(0, "86", $phoneNumber2, "测试短信，普通单发，深圳，小明，上学。", "", "");
			$rsp = json_decode($result);
			echo $result;
			echo "<br>";*/

			
			// 指定模板单发
			// 假设模板内容为：测试短信，{1}，{2}，{3}，上学。
			$random = $util -> getRandom();
			$params = array(strval($random), "30");
			$result = $singleSender->sendWithParam("86", $phoneNumber2, $templId, $params, "", "", $random);
			$rsp = json_decode($result);
			//echo $result;
			session_start();
			$_SESSION['vcode'] = $random;

			//$multiSender = new SmsMultiSender($appid, $appkey);

			// 普通群发
			/*$phoneNumbers = array($phoneNumber1, $phoneNumber2, $phoneNumber3);
			$result = $multiSender->send(0, "86", $phoneNumbers, "测试短信，普通群发，深圳，小明，上学。", "", "");
			$rsp = json_decode($result);
			echo $result;
			echo "<br>";*/

			// 指定模板群发，模板参数沿用上文的模板 id 和 $params
		   /* $params = array("指定模板群发", "深圳", "小明");
			$result = $multiSender->sendWithParam("86", $phoneNumbers, $templId, $params, "", "", "");
			$rsp = json_decode($result);
			echo $result;
			echo "<br>";*/
		} 
		catch (\Exception $e) 
		{
			echo var_dump($e);
		}
	}
	
	public function publishbook($bookid,$price)
	{
		$view = new View();
		$userid = session('userid');
		$userinfo = Db::table('userinfo')->field('SchoolID')->where('ID',$userid)->find();
		$salelist = Db::table('saleinfo a')->field('a.*,b.Name as sname,c.Name as fname,b.ID as sflid')->alias('a')
						->join('fenleiinfo b','a.CataID = b.ID','LEFT')
						->join('fenleiinfo c','b.ParentID = c.ID','LEFT')
						->where('a.BookID',$bookid)->select();
		$view -> bookid = $bookid;
		$view -> price = $price;
		$view -> userid = $userid;
		$view -> schoolid = $userinfo['SchoolID'];
		$view -> salelist = $salelist;
		
		return $view ->fetch();
	}
	
	public function merimglist($merid = 0)
	{
		$view = new View();
		$merinfo = Db::table('merchantinfo')->where('ID',$merid)->find();
		$imglist = getimglist($merinfo['ImgList']);
		$view -> imglist = $imglist;
		return $view-> fetch();
	}
	
	public function schoolimglist($schoolid = 0)
	{
		$view = new View();
		$schoolinfo = Db::table('schoolinfo')->where('SchoolID',$schoolid)->find();
		$imglist = getimglist($schoolinfo['ImgList']);
		$view -> imglist = $imglist;
		$view -> schoolinfo = $schoolinfo;
		return $view-> fetch();
	}
	
	public function setpaypass()
	{
		$view = new View();
		return $view-> fetch();
	}
	
	public function setpaymode($pass)
	{
		return Db::table('userinfo')->where('ID',session('userid'))->update(['PayPass' => $pass]);
	}
	
	public function getfenlei($type,$schoolid=1)
	{

		$fenleilist = Db::table('fenleiinfo')->field('Name,ID')->where('Type',$type);
		
		if($type == 1)
		{
			$fenleilist = $fenleilist ->where('SchoolID',$schoolid);
		}		
		$fenleilist = $fenleilist ->where('ParentID',0)->order(['Order'=>'desc','Name'=>'asc'])->select();
		
		return json_encode($fenleilist,JSON_UNESCAPED_UNICODE);
	}
	
	public function geterjifenlei($parentid)
	{
		
		$fenleilist = Db::table('fenleiinfo')->field('Name,ID')->where('ParentID',$parentid)->order(['Order'=>'desc','Name'=>'asc'])->select();
		
		return json_encode($fenleilist,JSON_UNESCAPED_UNICODE);		
	}
}