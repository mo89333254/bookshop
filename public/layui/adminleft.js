

var foodtype = {
	
	del:function(fid){
		
		  layui.use(['layer'], function(){
		var layer = layui.layer;
			  //询问框
			layer.confirm('确定要删除吗？', {
			  btn: ['是','否'] //按钮
			}, function(){
			$.post("/admin/admin/delfoodtypemethod/id/",{ id: fid }, function(data) {
			if(data == 1)
			{
				layer.close();
				layer.msg("删除成功");
				setTimeout("location.reload()",1000);
			}
			});
			}, function(){

			});

  
});
	},
	
	edit:function(fid){
				  layui.use(['layer'], function(){
		var layer = layui.layer;
			  //询问框
			layer.open({
			  type: 1,
			  title: false,
			  closeBtn: 0,
			  shadeClose: true,
			  skin: 'yourclass',
			  content: '<div class="popup">修改食物类型：<input type="text" class="typename"  name="typename" /></div>',
			  btn: ['修改','离开'],
			  btn1: function(){
			  var tn = $(".typename").val();
				 if(!tn)
				 {
					 layer.msg("请输入食物类型");
					 return;
				 }
			$.post("/admin/admin/editfoodtypemethod/id/",{ id: fid , typename: tn }, function(data) {
			if(data == 1)
			{
				layer.close();
				layer.msg("修改成功");
				setTimeout("location.reload()",1000);
			}
			});
			  }
			});

  
});
	},
		add:function(){
				  layui.use(['layer'], function(){
		var layer = layui.layer;
			  //询问框
			layer.open({
			  type: 1,
			  title: false,
			  closeBtn: 0,
			  shadeClose: true,
			  skin: 'yourclass',
			  content: '<div class="popup">请输入食物类型：<input type="text" class="typename"  name="typename" /></div>',
			  btn: ['修改','离开'],
			  btn1: function(){
			  var tn = $(".typename").val();
					if(!tn)
					{
						layer.msg("请输出食物类型");
						return;
					}
			$.post("/admin/admin/addfoodtypemethod",{ typename: tn }, function(data) {
			if(data == 1)
			{
				layer.close();
				layer.msg("添加成功");
				setTimeout("location.reload()",1000);
			}
			});
			  }
			});

  
});
	
}
}