<?php
namespace app\admin\controller;
use think\Db;
use think\View;
use think\Request;
use think\Controller;
use think\Session;
class Admin extends Controller
{

    public function _initialize()
    {
        $session = Session::has('user');
		$request = Request::instance();
		
		$check = array("login", "getlogin");
		
		if(!in_array($request -> action(),$check))
		{

			if(!$session)
			{
				$this -> redirect('/bookshop/public/index.php/admin/admin/login');
			}
			
		}
		else
		{
	
		}
		
		

        
    }
	
	public function getlogin()
	{	
		$request = Request::instance();
		$reqParam = $request->param();
		$result = Db::table('admininfo')->where('AdminName',$reqParam['AdminName'])->where('AdminPass',$reqParam['Pass'])->find();
		$count = count($result);
		if($count > 1)
		{
			Session::set('user.name',$result['AdminName']);
			Session::set('user.pass',$result['AdminPass']);
			Session::set('user.type',$result['AdminType']);
			Session::set('user.merid',$result['MerID']);
			//$this->success('登录成功','/bookshop/public/index.php/admin/admin/index');
			return $count;
		}
		else
		{
			//$this->error("shibai");
			return $count;
		}	
	}
	
	public function getout()
	{
		Session::delete('user');
		$this->redirect('admin/admin/login');
	}

	
	public function foodtypelist()
	{
		$sinfo = Session::get('user');
		$foodtype = Db::table('foodtype')->where('UserType',$sinfo['usertype'])->paginate(10);
		$view = new View();
		$view -> foodtypelist = $foodtype;
		
		return $view -> fetch();
	}
	
	public function index()
	{
		$view = new View();
		$sinfo = Session::get('user');
		
		$view -> merid = $sinfo['merid'];
		return $view -> fetch();
	}
	
	public function day()
	{
		$view = new View();
		$date = date("Y-m-d",time());
		$orderreport = Db::table('orderinfo')
							->where('CreateTime','between',$date.','.date('Y-m-d',strtotime("$date +1 day")))	
							->where('SaleType','2')	
							->where('Status','>','0')
							->select();
		$count = count($orderreport);
		$amount = 0;
		$success = 0;
		$prcent = 0;
		foreach($orderreport as $k1 => $k2)
		{
			if($k2['Status'] == 2 || $k2['Status'] ==4)
			{
				$success = $success + 1;
				$amount = $amount + $k2['Amount'];
			}
		}
		if($success > 0)
		{
			$prcent = number_format((($success / $count) * 100), 2, '.', '');
		}
		$view -> count1 = $count;
		$view -> amount = $amount;
		$view -> success = $success;
		$view -> orderreport = $orderreport;	
		$view -> prcent = $prcent;		
		return $view -> fetch();
	}
	
	public function addbook()
	{
		$view = new View();

		return $view -> fetch();		
	}

	public function booklist()
	{
		
		$view = new View();
		$booklist = Db::table('bookinfo')->order('ID desc')->paginate(10);
		
		$view -> booklist = $booklist;
		
		return $view -> fetch();
			
	}
	
	public function userlist()
	{
		$view = new View();
		$userlist = Db::table('userinfo')->alias('a')->join('schoolinfo w','a.SchoolID = w.SchoolID','LEFT')->order('ID desc')->paginate(10);
		
		$view -> userlist = $userlist;
		
		return $view -> fetch();
	}
	
	public function bookinfo($isbn)
	{
		$view = new View();
		$length = strlen($isbn);
		
		if($length == 10)
		{
			$IsExit = Db::table('bookinfo')->where('ISBN10',$isbn)->find();
			
		}
		else if($length == 13)
		{
			$IsExit = Db::table('bookinfo')->where('ISBN13',$isbn)->find();
		}
		else
		{
			return null;
		}



		$view -> bookinfo = $IsExit;
		return $view -> fetch();
	}
	
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
	
	public function SetBlack($userid,$status)
	{
		$param = ['Status' => $status];
		$result = Db::table('userinfo')->where('ID',$userid)->update($param);
		return $result;
	}
	
	public function idinfo($type = 1)
	{
		$view = new View();
		
		$idinfolist = Db::table('idinfo')->field('w.*,a.*,a.Status as IDStatus')->alias('a')->join('userinfo w','a.UserID = w.ID','LEFT');
		
		if($type == 1)
		{
			$idinfolist = $idinfolist ->where('a.Status',4);
		}
		else if($type == 2)
		{
			$idinfolist = $idinfolist ->where('a.Status','<',4);
		}
						
						
		$idinfolist = $idinfolist->order('a.CreateTime desc') ->paginate(10);
		
		$view -> idinfolist = $idinfolist;
		
		return $view -> fetch();
	}
	
	public function bookorder($start="",$end="")
	{
		$view = new View();
		$bookorderlist = Db::table('orderinfo')->field('a.*,a.Status as IDStatus,b.NickName')->alias('a')
					->join('userinfo b','a.UserID = b.ID','LEFT')					
					->where('a.Status','>','0')
					->where('a.IsDel',0)
					->order('a.CreateTime desc');
		if($start!="" && $end!="")
		{
			$endresult =  date('Y-m-d',strtotime("$end +1 day"));
			
			$bookorderlist = $bookorderlist->where('a.CreateTime','>=',$start)->where('a.CreateTime','<',$endresult);
		}
		
		$bookorderlist = $bookorderlist		->paginate(15,false,['query' => request()->param()]);
				
	
		
		
		$view -> bookorderlist = $bookorderlist;
		$view -> startdate = $start;
		$view -> enddate = $end;
		return $view -> fetch();
	}
	
	public function salebook()
	{
		$view = new View();
		$salebooklist = Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status,b.NickName')->alias('a')
				->join('bookinfo w','a.BookID = w.ID','LEFT')				
				->join('userinfo b','a.Suserid = b.ID','LEFT')
				->order('a.CreateTime desc')
				->paginate(10);
		
		$view -> salebooklist = $salebooklist;
		
		return $view -> fetch();
		
	}
	
	public function kk1()
	{$view = new View();
		return $view -> fetch();
	}
	
	public function addmer()
	{
		$schoollist = Db::table('schoolinfo')->select();
		
		$view = new View();
		$view -> schoollist = $schoollist;
		return $view -> fetch();
	}
	
	public function isexitadmin($admin="")
	{
		$isexit = Db::table('admininfo')->field('AdminName')->where('AdminName',$admin)->find();
		if($isexit)
		{
			return 0;
		}
		else
		{
			return 1;
		}
	}
	
	public function AddMerMode($admin,$pass,$average,$title,$subtitle,$point,$mname,$location,$price,$discount,$type,$img = '',$simg = '',$school,$content="",$tel,$imglist = '')
	{

		$param = [
			'Merchant' => $mname,
			'Location' => $location,
			'Tel' => $tel,
			'School' => $school,
			'Discount' => $discount,
			'Type' => $type,
			'Content' => $content,
			'Price' => $price,
			'CreateTime' => date("Y-m-d H:i:s"),
			'Point' => $point,
			'Img'=> $img,
			'SImg'=> $simg,
			'Average' => $average,
			'Title' => $title,
			'SubTitle' => $subtitle,
			'ImgList' => $imglist
			
		];
		
	

		$result = Db::table('merchantinfo')->insert($param);
		$merid = Db::table('merchantinfo')->getLastInsID();
		
		$adminparam = [
			'AdminName' => $admin,
			'AdminPass' => $pass,
			'AdminType' => 2,
			'CreateTime' =>  date("Y-m-d H:i:s"),
			'MerID' => $merid
		];
		
		Db::table('admininfo')->insert($adminparam);
		if($result > 0)
		{
	
			return $this->success('添加成功', HOSTPATH.'/index.php/admin/admin/merlist');
		}
		
		else
		{
			return $this->error('添加失败');
		}
		return $result;
	}
	
	public function EditMerMode($pass,$average,$title,$subtitle,$point,$mname,$location,$price,$discount,$type,$simg = '',$img = '',$school,$content="",$tel,$merid,$imglist ='')
	{
		
		$param = [
			'Merchant' => $mname,
			'Location' => $location,
			'Tel' => $tel,
			'School' => $school,
			'Discount' => $discount,
			'Type' => $type,
			'Content' => $content,
			'Price' => $price,
			'Point' => $point,
			'Average' => $average,
			'Img' => $img,
			'SImg' => $simg,
			'Average' => $average,
			'Title' => $title,
			'SubTitle' => $subtitle,
			'ImgList' => $imglist
		];
		
		$adminparam = [
			'AdminPass' => $pass
		];
		$result1 = Db::table('admininfo')->where('MerID',$merid)->update($adminparam);
		$result = Db::table('merchantinfo')->where('ID',$merid)->update($param);
		if($result > 0 || $result1 > 0)
		{
			return $this->success('修改成功', HOSTPATH.'/index.php/admin/admin/merlist');
		}
		
		else
		{
			return $this->error('无修改信息');
		}
		return $result;
	}
	
	///商家修改相册和密码
	public function EditSjMerMode($pass,$merid,$imglist ='',$simg='',$img='')
	{
		
		$param = [

			'Img' => $img,
			'SImg' => $simg,
			'ImgList' => $imglist
		];
		
		$adminparam = [
			'AdminPass' => $pass
		];
		$result1 = Db::table('admininfo')->where('MerID',$merid)->update($adminparam);
		$result = Db::table('merchantinfo')->where('ID',$merid)->update($param);
		if($result > 0 || $result1 > 0)
		{
			return $this->success('修改成功');
		}
		
		else
		{
			return $this->error('无修改信息');
		}
		return $result;
	}
	
	public function merlist()
	{
		$view = new View();
		$merlist = Db::table('merchantinfo')->alias('a')
					->join('schoolinfo w','a.School = w.SchoolID','LEFT')
					->paginate(10);
		
		$view -> merlist = $merlist;
		
		return $view -> fetch();
	}
	
	public function merinfo($merid)
	{
		$view = new View();
		$schoollist = Db::table('schoolinfo')->select();		
		
		
		
		$merinfo = Db::table('merchantinfo')->field('a.*,b.AdminName,b.AdminPass')->alias('a')
					->join('admininfo b','a.ID = b.MerID','LEFT')
					->where('a.ID',$merid)->find();
		$imginfo = $merinfo['ImgList'];
		$imglist = array();


		if($imginfo!="")
		{
			if(strstr($imginfo,','))
			{
				$imglist = explode(',',$imginfo);			
			}
			else
			{			
				array_push($imglist,$imginfo);
			
			}
		}
		$view -> merinfo = $merinfo;
		$view -> imglist = $imglist;
		$view -> schoollist = $schoollist;
		return $view -> fetch();
	}
	
	///商家查看编辑相册
	public function sjmerinfo($merid)
	{
		$view = new View();
		$merinfo = Db::table('merchantinfo')->field('a.*,b.AdminName,b.AdminPass')->alias('a')
					->join('admininfo b','a.ID = b.MerID','LEFT')
					->where('a.ID',$merid)->find();
		$imginfo = $merinfo['ImgList'];
		$imglist = array();


		if($imginfo!="")
		{
			if(strstr($imginfo,','))
			{
				$imglist = explode(',',$imginfo);			
			}
			else
			{			
				array_push($imglist,$imginfo);
			
			}
		}
		$view -> merinfo = $merinfo;
		$view -> imglist = $imglist;
		return $view -> fetch();
	}	
	
	public function shopinfo($merid = 0)
	{
		$view = new View();
		$view ->merid = $merid;
		return $view -> fetch();
	}
	
	public function editshopinfo($merid = 0,$id)
	{
		$view = new View();
		$view ->merid = $merid;
		$shopinfo = Db::table('shopinfo')->where('ID',$id)->find();
		$view -> shopinfo = $shopinfo;
		return $view -> fetch();
	}
	
	public function AddShopInfoMode($iContent ='')
	{
		$name = $_POST['iName'];
		$type = $_POST['iType'];
		$img = $_POST['iImg'];
		$simg = $_POST['iSImg'];
		$price = $_POST['iPrice'];
		$content = $iContent;
		$merid = $_POST['iMerid'];
		$param = [
			'MerID' => $merid,
			'ShopName' => $name,
			'Type' => $type,
			'Content' => $content,
			'Price' => $price,
			'Img' => $img,
			'SImg' => $simg
		];
		$result = Db::table('shopinfo')->insert($param);
		
		if($result > 0)
		{
			return $this->success('新增成功', HOSTPATH.'/index.php/admin/admin/shoplist?merid='.$merid);
		}
		
		else
		{
			return $this->error('新增失败');
		}
		
	}
	
	public function EditShopInfoMode($iContent ='',$iMerid,$iID)
	{
		$name = $_POST['iName'];
		$type = $_POST['iType'];
		$img = $_POST['iImg'];
		$price = $_POST['iPrice'];
		$simg = $_POST['iSImg'];
		$content = $iContent;
		$merid = $iMerid;
		$param = [		
			'ShopName' => $name,
			'Type' => $type,
			'Content' => $content,
			'Price' => $price,
			'Img' => $img,
			'SImg' => $simg
		];
		
		$result = Db::table('shopinfo')->where('ID',$iID)->update($param);
		
		if($result > 0)
		{
			return $this->success('修改成功', HOSTPATH.'/index.php/admin/admin/shoplist?merid='.$merid);
		}
		
		else
		{
			return $this->error('无修改信息');
		}
	}
	
	public function shoplist($merid)
	{
		$view = new View();
		$shoplist = Db::table('shopinfo')->where('MerID',$merid)->paginate(10);;
		$view -> merid =$merid;
		$view -> shopinfolist = $shoplist;
		return $view -> fetch();		
		
		
	}
	
	public function pickmoneylist($start="",$end="")
	{
		$view = new view();
		$pickmoneylist = Db::table("pickmoneyinfo");
		if($start!="" && $end!="")
		{
			$endresult =  date('Y-m-d',strtotime("$end +1 day"));
			
			$pickmoneylist = $pickmoneylist->where('CreateTime','>=',$start)->where('CreateTime','<',$endresult);
		}
		$pickmoneylist = $pickmoneylist->order('CreateTime desc')->paginate(10);
		$view -> pickmoneylist = $pickmoneylist;
		$view -> startdate = $start;
		$view -> enddate = $end;
		return $view -> fetch();
	}
	
	public function test1()
	{
		$view = new View();
		return $view ->fetch();
	}
	
	public function kk3()
	{
		$foo = new \img\Image('../public/uploads/20170513/');
		$kk1 = $foo -> thumb('1ad3f11d85185e6e7ce28ab1591bb0c6.jpg',160,120);
		return $kk1;
	}
	
	public function changepickmoney($id)
	{
		return Db::table('pickmoneyinfo')->where('ID',$id)->update(['Status' => 1]);
	}
	
	public function studentorder($merid=0,$start="",$end="")
	{
		$view = new View();
		$orderlist = Db::table('studentorder')->alias('a')->field('a.*,b.Merchant')->join('merchantinfo b','a.MerID = b.ID','LEFT')->where('Status',0);
		if($start!="" && $end!="")
		{
			$endresult =  date('Y-m-d',strtotime("$end +1 day"));
			
			$orderlist = $orderlist->where('a.CreateTime','>=',$start)->where('a.CreateTime','<',$endresult);
		}
		if($merid > 0)
		{
			$orderlist = $orderlist->where('a.MerID',$merid);
		}
		$orderlist = $orderlist->order('a.CreateTime desc')->paginate(15,false,['query' => request()->param()]);
		$view -> orderlist = $orderlist;
		$view ->startdate = $start;
		$view ->enddate = $end;
		return $view -> fetch();
	}
	
	public function login()
	{
		$view = new View();
		return $view -> fetch();
	}
	
	public function get6dayreport()
	{
		$today = date("Y-m-d 00:00:00",strtotime("+1 day"));
		$lastday = date("Y-m-d 00:00:00",strtotime("-4 day"));
		$orderlist = Db::table('orderinfo')
						->field('count(OrderID) as total,CreateDate')
						->where('CreateTime','>=',$lastday)
						->where('CreateTime','<',$today)
						->where('SaleType',2)
						->where('Status','>',0)
						->group('CreateDate')
						->select();
		$dayreport = array();				
		for($i = 0; $i<5; $i++)
		{
			$param = ['elapsed' => date("Y-m-d",strtotime("-".$i." day")),'value'=>0];
			foreach($orderlist as $k1 => $k2)
			{
				if($param['elapsed'] == $k2['CreateDate'])
				{
					$param['value'] = $k2['total'];
				}
			}
			array_push($dayreport,$param);
		}
		return json_encode($dayreport);
	}
	public function delmer($id)
	{
		Db::table('shopinfo')->where('MerID',$id)->delete();
		return Db::table('merchantinfo')->where('ID',$id)->delete();
	}
	
	public function delshop($id)
	{
		return Db::table('shopinfo')->where('ID',$id)->delete();
	}
	
	public function schoollist()
	{
		$view = new View();
		$schoollist = Db::table('schoolinfo')->order(['Order'=>'desc','SchoolName'=>'asc'])->paginate(10);
		$view -> schoollist = $schoollist;
		return $view -> fetch();
	}
	
	public function addschool()
	{
		$view = new View();
		$prolist = Db::table("areainfo")->group('ProvinceID')->select();
		$view -> prolist = $prolist;
		return $view -> fetch();
	}
	
	public function getcity($proid)
	{
		$prolist = Db::table("areainfo")->field('CityName,AreaCode')->where('ProvinceID',$proid)->select();
		return json_encode($prolist,JSON_UNESCAPED_UNICODE);
	}
	
	public function addschoolmode($school,$pro,$area,$img,$simg,$imglist = '',$content = '')
	{
		$param = [
			'SchoolName' => $school,
			'ProvinceID' => $pro,
			'AreaID' => $area,
			'Img' => $img,
			'SImg' => $simg,
			'ImgList' => $imglist,
			'Content' => $content
		];
		$result = Db::table('schoolinfo')->insert($param);
		if($result > 0)
		{
			$schoolinfo = Db::table('schoolinfo')->select();
			cache('schoollist', NULL); 
			cache('schoollist',$schoolinfo);
			return $this->success('添加成功', HOSTPATH.'/index.php/admin/admin/schoollist');
		}
		else
		{
			return $this->error('添加失败');
		}
	}
	
	public function delschoolmode($id)
	{
		return Db::table('schoolinfo')->where('SchoolID',$id)->delete();
	}
	
	public function editschoolmode($school,$pro,$area,$img,$simg,$id,$imglist = '',$content ='')
	{
			$param = [
			'SchoolName' => $school,
			'ProvinceID' => $pro,
			'AreaID' => $area,
			'Img' => $img,
			'SImg' => $simg,
			'ImgList' => $imglist,
			'Content' => $content
		];
		$result = Db::table('schoolinfo')->where('SchoolID',$id)->update($param);
		if($result > 0)
		{
			$schoolinfo = Db::table('schoolinfo')->select();
			cache('schoollist', NULL); 
			cache('schoollist',$schoolinfo);
			return $this->success('修改成功', HOSTPATH.'/index.php/admin/admin/schoollist');
		}
		else
		{
			return $this->error('无修改信息');
		}
	}
	
	public function editschool($id)
	{
		$view = new View();
		$schoolinfo = Db::table('schoolinfo')->where('SchoolID',$id)->find();
		$imginfo = $schoolinfo['ImgList'];
		$imglist = array();


		if($imginfo!="")
		{
			if(strstr($imginfo,','))
			{
				$imglist = explode(',',$imginfo);			
			}
			else
			{			
				array_push($imglist,$imginfo);
			
			}
		}
		
		
		$prolist = Db::table("areainfo")->group('ProvinceID')->select();
		$arealist = Db::table("areainfo")->where('ProvinceID',$schoolinfo['ProvinceID'])->select();
		$view -> prolist = $prolist;
		$view -> arealist = $arealist;
		$view -> schoolinfo = $schoolinfo;
		$view -> imglist = $imglist;
		return $view -> fetch();
	}
	
	public function departmentlist($schoolid)
	{
		$view = new View();
		$majorlist = Db::table('fenleiinfo')->where('SchoolID',$schoolid)->where('ParentID',0)->order(['Order'=>'desc','Name'=>'asc'])->paginate(15,false,['query' => request()->param()]);
		
		$view -> majorlist = $majorlist;
		$view -> schoolid = $schoolid;
		return $view -> fetch();
	}
	public function majorlist($majorid,$schoolid)
	{
		$view = new View();
		$majorlist = Db::table('fenleiinfo')->where('ParentID',$majorid)->order(['Order'=>'desc','Name'=>'asc'])->paginate(15,false,['query' => request()->param()]);
		$view -> majorlist = $majorlist;
		$view -> majorid = $majorid;
		$view -> schoolid = $schoolid;
		return $view -> fetch();
	}	
	
	public function editcatalog($id,$name)
	{
		$param = [
			'Name' => $name
		];
		$result = Db::table('fenleiinfo')->where('ID',$id)->update($param);
		return $result;
	}
	
	public function addcatalog($schoolid,$name)
	{
		$param = [
			'Name' => $name,
			'SchoolID' => $schoolid,
			'Type' => 1
		];
		$result = Db::table('fenleiinfo')->insert($param);
		return $result;
	}
	public function addmajor($schoolid,$majorid,$name)
	{
		$param = [
			'Name' => $name,
			'SchoolID' => $schoolid,
			'ParentID' => $majorid,
			'Type' => 1
		];
		$result = Db::table('fenleiinfo')->insert($param);
		return $result;
	}	
	
	public function editmajor($id,$name)
	{
		return Db::table('fenleiinfo')->where('ID',$id)->update(['Name'=> $name]);
	}
	
	public function deldepartment($id)
	{
		return Db::table('fenleiinfo')->where('ID',$id)->whereOr('ParentID',$id)->delete();
	}
	
	public function delmajor($id)
	{
		return Db::table('fenleiinfo')->where('ID',$id)->delete();
	}
	
	public function fenlei()
	{
		$view = new View();
		$result = Db::table('fenleiinfo')->where('ParentID',0)->where('Type','IN','2,3')->order(['Order'=>'desc','Name'=>'asc'])->paginate(15,false,['query' => request()->param()]);
		$view -> fenlei = $result;
		return $view -> fetch();
	}
	
	public function erjifenlei($parentid,$type)
	{
		$view = new View();
		$result = Db::table('fenleiinfo')->where('ParentID',$parentid)->order(['Order'=>'desc','Name'=>'asc'])->paginate(15,false,['query' => request()->param()]);
		$view -> erjifenlei = $result;
		$view ->parentid = $parentid;
		$view -> type = $type;
		return $view -> fetch();
	}
	
	public function addfenlei($type,$name)
	{
		$param = ['Type' => $type,'Name'=> $name];
		$result = Db::table('fenleiinfo')->insert($param);
		return $result;
	}
	public function adderjifenlei($type,$name,$parentid)
	{
		$param = ['Type' => $type,'Name'=> $name,'ParentID' => $parentid];
		$result = Db::table('fenleiinfo')->insert($param);
		return $result;
	}
	
	
	public function delfenlei($id)
	{
		return Db::table('fenleiinfo')->where('ID',$id)->whereOr('ParentID',$id)->delete();
	}
	
	public function editfenlei($id,$name)
	{
		return Db::table('fenleiinfo')->where('ID',$id)->update(['Name'=> $name]);
	}
	
	public function delerjifenlei($id)
	{
		return Db::table('fenleiinfo')->where('ID',$id)->delete();
	}
	
	public function editerjifenlei($id,$name)
	{
		return Db::table('fenleiinfo')->where('ID',$id)->update(['Name'=> $name]);
	}
	
	///修改分类排序
	public function editfenleiorder($id,$order)
	{
		return Db::table('fenleiinfo')->where('ID',$id)->update(['Order'=> $order]);
	}	
	
	///修改学校排序
	public function editschoolorder($id,$order)
	{
		return Db::table('schoolinfo')->where('SchoolID',$id)->update(['Order'=> $order]);
	}		
	
	//认证成功 发送短信 type 2 - 认证成功 3 - 认证失败
	public function sendrzmessage($num,$type)
	{
		try {
			// 请根据实际 appid 和 appkey 进行开发，以下只作为演示 sdk 使用
			$appid = config('sms1.appid');
			$appkey = config('sms1.appkey');
			$phoneNumber1 = "12345678901";
			$phoneNumber2 = $num;
			$phoneNumber3 = "12345678903";
			if($type == 2)
			{
				$templId = config('sms1.rzcg');
			}
			else
			{
				$templId = config('sms1.rzsb');
			}
			

			$singleSender = new \phpsms\SmsSingleSender($appid, $appkey);
			$util = new \phpsms\SmsSenderUtil();


			
			// 指定模板单发
			// 假设模板内容为：测试短信，{1}，{2}，{3}，上学。
			$random = $util -> getRandom();
			$params = array();
			$result = $singleSender->sendWithParam("86", $phoneNumber2, $templId, $params, "", "", "");
			$rsp = json_decode($result);
			echo $result;
		


		} 
		catch (\Exception $e) 
		{
			echo var_dump($e);
		}
	}
	
	public function userinfo($id)
	{
		$view = new View();
		$userinfo = Db::table('userinfo')->where('ID',$id)->find();
		$view -> userinfo = $userinfo;
		return $view ->fetch();
	}
	
	public function studentorderinfo($orderid)
	{
		$view = new View();
		$orderinfo = Db::table('studentorder')->alias('a')->field('a.*,b.Merchant')->join('merchantinfo b','a.MerID = b.ID','LEFT')->where('a.OrderID',$orderid)->find();;

		$view -> orderinfo = $orderinfo;
		return $view -> fetch();
	}
	
	//书本交易详细信息
	public function bookorderinfo($orderid)
	{
		$view = new View();
		$bookorderinfo = Db::table('orderinfo')->field('a.*,a.Status as IDStatus,b.NickName')->alias('a')
					->join('userinfo b','a.UserID = b.ID','LEFT')					
					->where('a.Status','>','0')
					->where('a.IsDel',0)	
					->where('OrderID',$orderid)
					->find();
				

		$bookorderinfo['SaleID'] = json_decode($bookorderinfo['SaleID']);
		
		$view -> bookorderinfo = $bookorderinfo;

		return $view -> fetch();
	}
	
	public function bababook()
	{
		$view = new View();
		$userinfo = Db::table('userinfo')->where('ID',0)->find();
		$view -> userinfo = $userinfo;
		return $view -> fetch();
	}
	
	public function editzyxx($zyname="",$phone="")
	{
		$result = Db::table('userinfo')->where('ID',0)->update(['NickName'=> $zyname,'Phone'=> $phone]);
		if($result > 0)
		{
			return $this->success('修改成功', HOSTPATH.'/index.php/admin/admin/bababook');
		}
		else
		{
			return $this->error('无修改信息');
		}
	}
	
	///使用体验劵页面
	public function usecoupon()
	{
		$view = new View();
		return $view ->fetch();
	}
	
	//使用体验劵操作
	public function usecouponmode($zhxx)
	{
		$result = Db::table('couponinfo')->where('Code',$zhxx)->find();
		if(!$result)
		{
			return $this->error('找不到劵号信息');
		}
		else
		{
			$isuse = $result['Type'];
			if($isuse == 1)
			{
				return $this->error('劵号已使用');
			}
			$jieguo = Db::table('couponinfo')->where('Code',$zhxx)->update(['Type'=>1]);
			if($jieguo > 0)
			{
				return $this->error('使用成功');
			}
			else
			{
				return $this->error('使用失败');
			}
		}
	}
	
	///编辑上架图书分类页面
	public function salebooktype($saleid)
	{
		$view = new View();
		$view -> saleid = $saleid;
		$salebookinfo = Db::table('saleinfo')->where('ID',$saleid)->find();
		
		$booktype = $salebookinfo['BookType'];
		$bookstype = $salebookinfo['CataID'];
		$stypeid = Db::table('fenleiinfo')->where('ID',$bookstype)->find();
		$parentid = $stypeid['ParentID'];
		$stypelist = Db::table('fenleiinfo')->where('ParentID',$parentid)->select();
		$ftypelist = Db::table('fenleiinfo')->where('Type',$booktype)->select();
		
		$view -> ftypelist = $ftypelist;
		$view ->stypelist = $stypelist;
		$view -> parentid = $parentid;
		$view -> cataid = $stypeid['ID'];
		$view -> booktype = $booktype;
		$view ->kecheng = $salebookinfo['KeCheng'];
		return $view ->fetch();
	}
	
	///修改上架图书分类操作
	public function editsalebooktype($booktypep,$ftypep,$stypep,$kechengp ='',$saleid)
	{
		$editparam = [
			'BookType' => $booktypep,
			'CataID' => $stypep,
			'KeCheng' => $kechengp
		];
		$result = Db::table('saleinfo')->where("ID",$saleid)->update($editparam);
		
		if($result > 0)
		{
			return $this->error('修改成功');
		}
		else
		{
			return $this->error('修改失败');
		}
	}
	
	///banner列表页面
	public function banner()
	{
		$view = new View();
		$bannerlist = Db::table('bannerinfo')->paginate(15,false,['query' => request()->param()]);
		$view -> bannerlist = $bannerlist;
		return $view ->fetch();
	}
	
	///添加banner页面
	public function addbanner()
	{
		$view = new View();

		return $view ->fetch();
	}
	
	///添加banner操作
	public function addbannermode($img="",$mname="",$mhref="",$morder=0)
	{
		$addparam = [
			'Order'=> $morder,
			'Href' => $mhref,
			'Img' => $img,
			'Name' => $mname
		];
		$result = Db::table('bannerinfo')->insert($addparam);
		if($result > 0)
		{
			return $this->success('修改成功','admin/admin/banner');
		}
		else
		{
			return $this->error('修改失败');
		}
	}
	
	///编辑banner页面
	public function editbanner($bannerid)
	{
		$view = new View();
		$bannerinfo = Db::table('bannerinfo')->where('ID',$bannerid)->find();
		$view -> bannerinfo = $bannerinfo;
		$view -> bannerid = $bannerid;
		return $view ->fetch();
	}
	
	///编辑banner操作
	public function editbannermode($bannerid,$img="",$mname="",$mhref="",$morder=0)
	{
		$editparam = [
			'Order'=> $morder,
			'Href' => $mhref,
			'Img' => $img,
			'Name' => $mname
		];
		$result = Db::table('bannerinfo')->where("ID",$bannerid)->update($editparam);
		if($result > 0)
		{
			return $this->success('修改成功','admin/admin/banner');
		}
		else
		{
			return $this->error('修改失败');
		}
	}
	
	///删除banner操作
	public function delbannermode($bannerid)
	{
		$result = Db::table('bannerinfo')->where('ID',$bannerid)->delete();
		return $result;
	}
	
	//删除上架图书操作
	public function delsalebookmode($saleid)
	{
		$result = Db::table('saleinfo')->where('ID',$saleid)->delete();
		return $result;
	}
}