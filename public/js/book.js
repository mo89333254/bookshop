$(document).ready(function(){
	$(".sb").click(function(){
		var s_bookid = $(this).attr('data-bookid');

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
var basepath = "/bookshop/public";
var book = {
	
	loadBookInfo:function(){
	var iisbn = $("#iISBN").val();
	if(iisbn.length != 10 && iisbn.length != 13)
	{
		alert('请输入正确的ISBN');
		return false;
	}
	
	$("#myModal").modal("show");	
	
	$.post(basepath + "/index.php/salebook/salebook/GetBookInfo",{isbn:iisbn,json:1},
	function(data){

		 //var json=$.parseJSON(data);	
		var json = data;
		
		if(data == "")
		{
			$("#myModal").modal("hide");	
			alert('找不到书本信息');
		}
		 if(json.BookSimg.indexOf("http") > -1)
		 {
			
			
		 }
		 else
		 {
			 json.BookSimg = basepath + "/uploads/" + json.BookSimg;
		 }
 
		 $("#iTitle").val(json.BookName);
		 $("#iAuthor").val(json.Author);
		 $("#iPublish").val(json.PublishName);
		 $("#iPublishTime").val(json.PublishDate);
		 $("#iPrice").val(json.Amount);
		 $("#iSummary").text(json.Summary);
		 $("#bookimg").attr('src',json.BookSimg);
		 $("#myModal").modal("hide");
	}).error(function(data){
		$(".modal-body").text("加载错误");
		
		
	});
		
	},
	edit:function(){
		var s_name = $("#iTitle").val();
		var s_discount = $("#iDiscount").val();
		var s_ft = $("#iFType").val();
		var s_st = $("#iSType").val();
		var s_tt = $("#iTType").val();
		var s_price = $("#iPrice").val();
		var s_author = $("#iAuthor").val(); 
		var s_summary = $("#iSummary").text();
		var s_publish = $("#iPublish").val();
		var s_publishtime = $("#iPublishTime").val(); 
		var s_isbn = $("#iISBN").val(); 
		var s_img = $("#iImg").val(); 
		var s_simg = $("#iSImg").val(); 
		if(s_name.length < 1)
		{
			alert('请输入书籍名称');
			return false;
		}

			$.post("/bookshop/public/index.php/salebook/salebook/EditBookMode",{simg:s_simg,img:s_img, isbn:s_isbn, title:s_name, price:s_price, discount:s_discount, ft:s_ft, st:s_st, tt:s_tt, summary:s_summary, publish:s_publish, publishtime:s_publishtime, author:s_author}, 
		function(data){
		if(data >= 1)
		{
			alert("录入成功");
			window.location.href='/bookshop/public/index.php/admin/admin/booklist';
		}
		else if(data == 0)
		{
			alert("无更新信息");
		}
		else if(data == -1)
		{
			alert("录入失败");
		}

		}).error(function(data){
			$(".modal-body").text("加载错误");
			
			
		});		
	},
	show:function(isbn){
		window.location.href="/bookshop/public/index.php/admin/admin/bookinfo/isbn/"+isbn;
	}
	
}