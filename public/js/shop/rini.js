function rini(bookid,obj){
	
		var kk = bookid
		$.post('/bookshop/public/index.php/index/caozuo/adddaohuo',{bookid:kk},function(data){
			if(data == 2)
			{
				d_messages('已取消到货提醒');
				obj.value='到货提醒';
				return false;
			}
			else if(data == 1)
			{
				d_messages('已加入到货提醒');
				obj.value='已设置';
				return false;
			}
			else if(data == 0)
			{
				d_messages('加入失败');
				return false;
			}
	})		

}

function indexserch()
{
	
	$search = $("input[name='search']").val();
	$stype =  $("input[name='stype']").val();
	window.location.href = "/bookshop/public/index.php/index/index/index1?search=" + $search+"&stype=" + $stype;
}