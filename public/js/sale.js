$(document).ready(function(){
	$(".sb").click(function(){
		var s_bookid = $(this).attr('data-bookid')；
					$.post("/bookshop/public/index.php/SaleBook/SaleBook/CreateSaleMode",{ bookid:s_bookid,type:1},
		function(data){
			if(data > 0)
			{
				alert('上架成功');
				
				
				
			}
			else
			{
				
				alert('改书已经上架');
				
			}
		}).error(function(data){
			alert('程序错误');
			
			
		});
	})
});