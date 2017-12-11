<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function getimg($img)
{
	if(strpos($img,'http') ===0)
	{
		echo $img;
	}
	else
	{
		echo HOSTPATH.'/uploads/'.$img;
	}
}

function getsex($sex)
{
	if($sex == 1)
	{
		echo '男';
	}
	else
	{
		echo '女';
	}
}

function getchoose($obj,$val,$return1,$return2)
{
	if($obj == $val)
	{
		echo $return1;
	}
	else
	{
		echo $return2;
	}
}

function getimg2($img)
{
	if($img)
	{
		getimg($img);
	}
	else
	{
		echo HOSTPATH.'/img/no_image.jpg';
	}
}

function iscollect($collect,$saleid)
{
	$array = explode(',',$collect);

	if($collect == "")
	{
		return 1;
	}
	if(in_array($saleid,$array))
	{
		return 2;
	}
	else
	{
		return 1;
	}
}

function getmertype($type)
{
	switch ($type)
	{
		case 1:
		  echo "美食";
		  break;
		case 2:
		  echo "外卖";
		  break;
		case 3:
		  echo "娱乐";
		  break;
		case 4:
		  echo "住宿";
		  break;
		case 5:
		  echo "生活服务";
		  break;
		case 6:
		  echo "教育培训";
		  break;
	}
}

function changepoint($point)
{
	$change = explode(",",$point);
	$result = $point;

	if($point!='')
	{
		$result = $change[1].','.$change[0];
	}
	
	return $result;
}

function getimglist($imginfo)
{
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
	return $imglist;
}

function arraySort($arr, $keys, $type = 'asc') 
{
	$keysvalue = $new_array = array();
	foreach ($arr as $k => $v){
		$keysvalue[$k] = $v[$keys];
	}
	$type == 'asc' ? asort($keysvalue) : arsort($keysvalue);
	reset($keysvalue);
	foreach ($keysvalue as $k => $v) {
	   $new_array[$k] = $arr[$k];
	}
	return $new_array;
}

function isindaohuo($userid,$daohuoinfo)
{
	
	$daohuoinfo = json_decode($daohuoinfo);
	if(!$daohuoinfo)
	{
		return 0;
	}
	if(in_array($userid,$daohuoinfo))
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

function zxjson($jsonstr,$value)
{
	try
	{
		return json_decode($jsonstr)->$value;
	}
	catch(Exception $e)
	{
		return '';
	}
}

function defaultjson($jsonobj,$value)
{
	try
	{
		return $jsonobj->$value;
	}
	catch(Exception $e)
	{
		return '';
	}
}