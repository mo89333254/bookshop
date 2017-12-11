<?php
namespace app\salebook\controller;
use think\Db;
use think\View;
use think\Request;
use think\Controller;
use think\Session;
use think\Http;
use think\Exception;
use think\Cache;
class SaleBook extends Controller
{
	
	public function GetBookList()
	{	
		$BookInfoList= Db::table('bookinfo')->select();
		
		$haha = $this -> GetBookInfo();
		dump($haha);
	}
		
	public function GetBookInfo($isbn=9787539983240,$json = 0)
	{
		$request = Request::instance();
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
			throw new Exception("请输入正确的ISBN");
		}

		if($IsExit)
		{
			echo header("Access-Control-Allow-Origin:*");
			if($json == 0)
			{
				return json_encode($IsExit,JSON_UNESCAPED_UNICODE);
			}
			else
			{				
				return $IsExit;
			}
			
		}
		else
		{
			if($json = 0)
			{
				return json_encode($this -> FromISBNGetInfo($isbn),JSON_UNESCAPED_UNICODE);
			}
			else
			{
				return $this -> FromISBNGetInfo($isbn);
			}
		}
	}
	
	///聚合网图书数据
	public function FromISBNGetInfo1($isbn)
	{
		$http = new Http();
		$key = config('isbn.key');
		$pararms = ['sub' => $isbn , 'key' =>$key ];
		$uri = config('isbn.uri');
		
		$data = $http->http($uri,$pararms);
		$data = json_decode($data);
		
		$code = $data->error_code;
		
		if($code == '0')
		{
			$bookinfo = $data ->result;
			$booklimg = $bookinfo->images_large;
			$booksimg = $bookinfo->images_medium;
			$booktitle = $bookinfo->title;
			$bookpublish = $bookinfo->publisher;
			$bookisbn10 = $bookinfo->isbn10;
			$bookisbn13 = $bookinfo->isbn13;
			$amount = $bookinfo->price;
			$author = $bookinfo->author;
			$publishdate = $bookinfo->pubdate;
			$summary = $bookinfo->summary;
			$origintitle = $bookinfo->origin_title;
			$levelnum = $bookinfo->levelNum;
			$pages = $bookinfo->pages;
			$subtitle = $bookinfo->subtitle;
			$inserparam = ['BookName' => $booktitle, 'PublishName' => $bookpublish, 'BookLimg' => $booklimg,
					'BookSimg' => $booksimg, 'Summary' => $summary, 'Author' => $author, 'ISBN10' => $bookisbn10,
					'ISBN13' => $bookisbn13, 'OriginTitle' => $origintitle, 'PublishDate' => $publishdate,
					'Amount' => $amount, 'CreateTime' => date("Y-m-d H:i:s"), 'Pages' => $pages, 'SubTitle' => $subtitle,
					'LevelNum' => $levelnum
			];
			$bookid = Db::name('bookinfo')->insertGetId($inserparam);
			$newresult = array_merge($inserparam,["ID" => $bookid]);
			return $newresult;
			
		}
		else
		{
			return null;
		}
			

		
	}
	
	///从豆瓣网获取数据
	public function FromISBNGetInfo($isbn)
	{
		$http = new Http();
		$key = config('isbn.doubanurl');
		
		$uri = $key.$isbn;
	
		$data = $http->http($uri,array());

		
		$data = json_decode($data);
		
		try
		{
	
			$bookinfo = $data;
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
			$bookid = Db::name('bookinfo')->insertGetId($inserparam);
	
		
			$newresult = array_merge($inserparam,["ID" => $bookid]);
	
			return $newresult;
			
		}
		catch(Exception $e)
		{
			return null;
		}
		
	}
	
	public function EditBookMode($simg="",$isbn=0,$title="",$price=0,$ft=0,$st=0,$tt=0,$summary="",$publish="",$publishtime="",$author="",$img="")
	{
		$length = strlen($isbn);		
		
		$editparam = ['BookName' => $title, 'PublishName' => $publish,
			'Summary' => $summary, 'Author' => $author, 
			'PublishDate' => $publishtime,
			'FirstType' => $ft, 'SecondType' => $st, 'ThirdType' => $st,
			'Amount' => $price
			
			];
			
		if($img!="")
		{
			$imgarray = ['BookSimg' => $img, 'BookLimg' => $simg];
			$editparam = array_merge($editparam,$imgarray);
		}
		if($length == 10)
		{
			$result = Db::table('bookinfo')->where('ISBN10',$isbn)->update($editparam);
			return $result;
			
		}
		else if($length == 13)
		{
			$result = Db::table('bookinfo')->where('ISBN13',$isbn)->update($editparam);
			return $result;
		}
		
		return -1;
		
			
	}
	

	public function SaleBookMode($saleid,$status)
	{
		$param = ['Status' => $status];
		$result = Db::table('saleinfo')->where('ID',$saleid)->update($param);
		return $result;
	}
	
	public function EditSaleBook($objtype,$objvalue,$saleid,$bookid)
	{
		$param = [$objtype => $objvalue];
		$result = Db::table('saleinfo')->where('ID',$saleid)->update($param);
		
		if(Cache::get('book_'.$bookid))
		{
			$info = Cache::get('book_'.$bookid);
			Cache::rm('book_'.$bookid);
			$info[$objtype] = $objvalue;
			Cache::set('book_'.$bookid,$info,3600);
		}

		return $result;

	}
	
	public function CreateSaleMode($bookid,$suserid=0,$type,$count = 0,$discount = 0)
	{
		$IsExit = Db::table('saleinfo')->where('BookID',$bookid)->where('Type',$type)->find();
		if($IsExit)
		{
			return 0;
		}
		else
		{
			$param = ['BookID' => $bookid, 'Status' => 1, 'CreateTime' => date("Y-m-d H:i:s"), 'CreateDate' => date("Y-m-d"), 'Type' => $type,
			'Count' => $count, 'Discount' => $discount, 'Suserid' => $suserid, 'BookType' => 1
			];
			$result = Db::table('saleinfo')->insert($param);
			return $result;
		}
	}
	
	public function mm()
	{
		$kk = ['aa' => 'fdfd'];
		$kk1 =['bb' => 'fddd333'];
		$kk = array_merge($kk,["ID" => 1111]);
		dump($kk);
	}

	public	function findNum($str='')
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
	

}