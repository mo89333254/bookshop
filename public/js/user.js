var user ={
	change:function(id,type,userid,schoolcard,num){
			$.post("/bookshop/public/index.php/user/user/ChangeIDType",{ id:id,type:type,userid:userid,schoolcard:schoolcard},
	function(data){
		if(data > 0)
		{
			$.post('/bookshop/public/index.php/admin/admin/sendrzmessage',{num:num,type:type},function(data){
				
				
			})
			
			alert('操作成功');
			$("#myModal").modal('hide');	
			window.location.reload();
			
		}
		else
		{
			
			alert('已审核');
			$("#myModal").modal('hide');
		}
	}).error(function(data){
		alert('程序错误');
		
		
	});
	}
}
$(document).ready(function(){
	$(".showid").click(function(){

		$("#myModal").modal('show');
		var s_img = $(this).attr('data-img');
		var s_span = $(this).attr('data-si');
		var s_id = $(this).attr('data-id');
		var s_num = $(this).attr('data-num');
		var path = '/bookshop/public/uploads/';
		var s_userid = $(this).attr('data-userid');
		
		$("#s_suc").attr('onclick','return user.change('+s_id+',2,'+ s_userid+','+ s_span+','+s_num+')');
		$("#s_fail").attr('onclick','return user.change('+s_id+',3,'+ s_userid+','+ s_span+','+s_num+')');
		$("#s_span").text(s_span);
		$("#s_img").attr('src',path + s_img);
})
});

