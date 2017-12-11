<?php
namespace app\index\controller;
use think\Db;
use think\View;
use think\Request;
use think\Cache;
use think\Session;
use think\Exception;
use think\Log;
use think\model\shopcart\Getgoodsparam;
use think\model\shopcart\GetResultParam;
use think\model\shopcart\GetShopcartCode;
class Caozuo
{
    public function index()
    {
		$view = new View();
		
        return $view -> fetch();
    }
	

	///qty 购买数量
	///attr_number 当前库存
	///limit_number 用户可购买的数量
	public function GetBookInfo($saleid=4,$onload=0,$qty=1)
	{
		$bookcache = Cache::get('booksidsid_'.$saleid);
		
		if($bookcache)
		{
			$bookinfo = $bookcache;
		}
		else
		{
			$bookinfo =	Db::table('saleinfo')->field('w.*,a.*,a.Status as S_Status')->alias('a')
					->join('bookinfo w','a.BookID = w.ID','LEFT')	
					->where('a.ID',$saleid)		
					->find();
			Cache::set('booksid_'.$saleid,$bookinfo,3600);	

		}
		
		$discount = floatval($bookinfo['Discount']);
		$price = floatval($bookinfo['Amount']);
		$tureprice = round($discount * $price / 10,2);
		$result = 
		["err_msg"=> "",
		"result"=> "¥".$tureprice,
		"qty"=> 1,
		"attr_number"=> $bookinfo['Count'],
		"limit_number"=> $qty,
		"shop_price"=> "¥1.00",
		"market_price"=> "¥2.00",
		"show_goods"=> $bookinfo['Count'],
		"spec_price"=> "¥3.00",
		"marketPrice_amount"=> $bookinfo['Amount'],
		"discount"=> $discount,
		"result_market"=> "¥".$price,
		"onload"=> "3"];
		if($onload == 1)
		{
			
		}
		else if($onload == 3)
		{
			
		}
		return json_encode($result);

	}
	
	///加入购物车
	public function AddShopCart($goods)
	{
		$info = new Getgoodsparam();
		$info = json_decode($goods);
		
		
		$code = new GetShopcartCode();
		$code -> goods_number = 0;
		$code -> error = 0;
		$code ->bookcount = 0;
		
		$goods_id = $info->goods_id;
		$user_id = session('userid');
		$suser_id = $info->suser_id;
		$sale_id = $info->sale_id;
		
		$code -> saleid = $sale_id;
		$bookcount = Db::table('saleinfo')->field('Count')->where('ID',$sale_id)->find();
		$IsExit = Db::table('shopcartinfo')->field('a.*,b.Count as bookcount,b.ID as sid')->alias('a')
					->join('saleinfo b','a.BookID = b.BookID','LEFT')
					->where('a.BookID',$goods_id)->where('a.UserID',$user_id)->where('a.SaleID',$sale_id)->find();
		$goodnum = $info->number;
		
		if($bookcount['Count'] == 0)
		{
			$code-> message = '库存不足';
			$goods_num = Db::table("shopcartinfo")->field('sum(Count) as goodsnum')->where('UserID',$user_id)->find();
			$code -> goods_number = $goods_num['goodsnum'];
			return json_encode($code);
		}
		
		if($IsExit)
		{

			$rc = $IsExit['Count'];
			$params =['Count' => $rc + $goodnum,'CreateTime' => date("Y-m-d H:i:s")];
			
			$result = Db::table('shopcartinfo')->where('SaleID',$sale_id)->where('UserID',$user_id)->update($params);
		}
		else
		{
			$params =['CreateTime'=>date("Y-m-d H:i:s"),'BookID' => $goods_id,'UserID' => $user_id, 'Count' => $goodnum, 'Suserid'=> $suser_id, 'SaleID' => $sale_id];
			$result = Db::table('shopcartinfo')->insert($params);
			
		}
		$goods_num = Db::table("shopcartinfo")->field('sum(Count) as goodsnum')->where('UserID',$user_id)->find();
		$lessbook = Db::table('saleinfo')->where('ID',$sale_id)->setDec('Count',$goodnum);

		$code -> message = '商品已加入购物车';
		if($goods_num)
		{
			
			$code -> goods_number = $goods_num['goodsnum'];
		}
		$code -> bookcount = $bookcount['Count'] - $goodnum;
		return json_encode($code);
	}
	

	
	public function DelShopCart($sid)
	{
		$shopcartlist = Db::table('shopcartinfo') -> where('ID','IN',$sid) ->select();
		foreach($shopcartlist as $k1 => $k2)
		{
			Db::table('saleinfo')->where('ID',$k2['SaleID'])->setInc('Count',$k2['Count']);
		}
		$result = Db::table('shopcartinfo')->where('ID','IN',$sid)->delete();
		$code = new GetResultParam();
		if($result > 0)
		{
			$code -> error = 0;
		}
		
		return json_encode($code);
	}
	
	public function test1()
	{
		$kk = new Getgoodsparam();
		$kk -> goods_num = 1;
		dump($kk);
	}
	
	public function GetShopCartTotal()
	{
		$s_sid = $_POST['sid'];
		$s_none = 0;
		if( isset( $_POST['none'] ) ) 
		{
			$s_none = $_POST['none'];
		}
		$shopcartlist = Db::table('shopcartinfo') -> alias('a')
						->field('a.*,w.*,x.*,y.*,a.Count as scount,a.ID as sid')
						->join('bookinfo w','a.BookID = w.ID','LEFT')	
						->join('saleinfo y','y.ID = a.SaleID','LEFT')							
						->join('userinfo x','y.SuserID = x.ID','LEFT')	
						->where('a.ID','IN',$s_sid)
						->select();
		

		$stotal = 0;
		$scount = 0;
		if($shopcartlist)
		{
			foreach($shopcartlist as $key => $value) 
				{ 
					$stotal = $stotal + ($shopcartlist[$key]['scount'] * 0.1 * $shopcartlist[$key]['Amount'] * $shopcartlist[$key]['Discount']);
					$scount = $scount + $shopcartlist[$key]['scount'];
				
				} 


		}
		$code = ['content' => number_format($stotal, 2, '.', ''), 'cart_number' => $scount, 'none' => $s_none];
		echo json_encode($code);
	}
	
	public function ChangeBook($cartid,$saleid,$suserid)
	{
		$shopcartinfo = Db::table('shopcartinfo')->field('ID,SaleID,Count')->where('ID',$cartid)->find();
		$isExit = Db::table('shopcartinfo')->where('UserID',session('userid'))->where('SaleID',$saleid)->where('Suserid',$suserid)->find();
		$saleinfo = Db::table('saleinfo')->field('Count')->where('ID',$saleid)->find();
		
		if($isExit)
		{			
			return 2;
		}
		if($saleinfo['Count'] == 0)
		{
			return 3;
		}

		
		$result = Db::table('shopcartinfo')->where('ID',$cartid)->update(['Suserid' => $suserid,'SaleID' => $saleid, 'Count' => 1]);
		
		//被替换的书  库存整理
		$saleiresult = Db::table('saleinfo')->where('ID',$shopcartinfo['SaleID'])->setInc('Count',$shopcartinfo['Count']);
		
		//替换的书 库存整理		
		$saleinfo = Db::table('saleinfo')->where('ID',$saleid)->setDec('Count',1);
		return $result;
	}
	
	public function DelBook($saleid)
	{
		
		return Db::table('saleinfo')->where('ID',$saleid)->delete();
	}
	
	
	///$type 1 - 收藏 2 - 取消
	public function AddtoCollect($saleid,$type)
	{
		$userid = session('userid');
		$userinfo = Db::table('userinfo')->where('ID',$userid)->find();
		$param = $userinfo['Collect'];
		$paramarray = explode(',',$param);
		
		if($type == 1)
		{
			if(in_array($saleid,$paramarray))
			{
				return 0;
			}
			else
			{
				$collect = $param.$saleid.',';
				return Db::table('userinfo')->where('ID',$userid)->update(['Collect' => $collect]);
			}
		}
		else if($type ==2)
		{
		
	
			if(in_array($saleid,$paramarray))
			{
				$replace = $saleid.',';

				$collect = str_replace($replace,"",$param);
				return Db::table('userinfo')->where('ID',$userid)->update(['Collect' => $collect]);
			}
			else
			{
				return 0;
			}
		}
	}
	
	///判断用户是否使用过体验卷
	/// 0 - 购买成功 1 - 已经购买过体验卷
	public function GetExp()
	{
		$userinfo = Db::table('userinfo')->field('ID,Exp')->where('ID',$session{'userid'})->find();
		if($userinfo['Exp'] > 1)
		{
			return 1;
		}
		else
		{
			Db::table('userinfo')->where('ID',session('userid'))->update(['Exp' => 2]);
			return 0;
		}
	}
	
	public function checkshiming()
	{
		$userinfo = Db::table('userinfo')->where('ID',session('userid'))->find();
		if($userinfo['UserType'] == 1)
		{
			return 1;
		}
		else if($userinfo['UserType'] == 2)
		{
			return 2;
		}
	}
	
	public function getshopcartcount()
	{
		$count = 0;
		$result = Db::table('shopcartinfo')->field('sum(Count) as countsum')->where('UserID',session('userid'))->find();
		if($result['countsum'] > 0)
		{
			$count = $result['countsum'];
		}
		return $count;
	}
	
	public function checkphone()
	{
		$result = Db::table('userinfo')->where('ID',session('userid'))->find();
		if($result['PhoneType'] > 1)
		{
			return 2;
		}
		else
		{
			return 1;
		}
	}
	
	///加入到货提醒 2 - 已加入到货提醒 
	public function adddaohuo($bookid)
	{
		$daohuo = Db::table('daohuoinfo')->where('BookID',$bookid)->find();
		$userid = session('userid');
		if($daohuo)
		{
			$userlist = json_decode($daohuo['UserList']);
			if(in_array($userid,$userlist))
			{
				$key=array_search($userid ,$userlist);
				array_splice($userlist,$key,1);
				$userlistjson = json_encode($userlist);
				$result = Db::table('daohuoinfo')->where('BookID',$bookid)->update(['UserList'=>$userlistjson]);
				return 2;
			}
			else
			{
				array_push($userlist,$userid);
				$userlistjson = json_encode($userlist);
				$result = Db::table('daohuoinfo')->where('BookID',$bookid)->update(['UserList'=>$userlistjson]);
				return $result;
			}
		}
		else
		{
			$userlist = array();
			array_push($userlist,$userid);
			$userlistjson = json_encode($userlist);
			$result = Db::table('daohuoinfo')->insert(['BookID' => $bookid,'UserList' => $userlistjson]);
			return $result;
		}
	}
	
	///判断是否已消费学生折 2 - 已存在
	public function merisexit($shopid,$userid)
	{
		
		$daohuo = Db::table('userinfo')->where('ID',$userid)->find();
		

		$merlist = json_decode($daohuo['MerList']);

		if(!$merlist)
		{
			
			return 1;
		}

		else
		{
			if(in_array($shopid,$merlist))
			{
				return 2;
			}
			else
			{
				return 1;
			}
		}

	}
	
	public function rokk()
	{
		$request = Request::instance();

		$file = $request->file('file');	
	

		    // 移动到框架应用根目录/public/uploads/ 目录下
		if($file){
			

			$info = $file ->validate(['size'=>2300000,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
			if($info){
				
				
				
				$fimage = $info->getSaveName();
				$fsize = $info->getSize();
				$fext = $info->getExtension();
					
				$savename = $info->getFilename(); 

				
			}else{
				// 上传失败获取错误信息
				echo $file->getError();
			}
		}
		else{
				echo '上传失败';
				
			}	
	}
	
	///筛选学校返回首页
	public function getlocation($schoolid,$schoolname)
	{
		$schoolhistory = [
				'schoolid' => $schoolid,
				'schoolname' => $schoolname				
			];
		cookie('schoolhistory', $schoolhistory, 7200);
		return redirect('index/index/index1');
	}
	
	///筛选学校返回学生折
	public function getlocation2($schoolid,$schoolname)
	{
		$schoolhistory = [
				'schoolid' => $schoolid,
				'schoolname' => $schoolname				
			];
		cookie('schoolhistory', $schoolhistory, 7200);
		return redirect('index/index/student');
	}
}
