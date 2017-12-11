var mer = {
	save:function(){

	
		var s_name = $("#iName").val();

		var s_discount = $("#iDiscount").val();
		var s_tt = $("#iType").val();
		var s_price = $("#iPrice").val();
		var s_content = UE.getEditor('editor').getContent();
		var s_point = $("#iPoint").val();
		var s_school = $("#iSchool").val();
		var s_tel = $("#iTel").val();
		var s_location = $("#iLocation").val();
		var s_title = $("#iTitle").val();
		var s_subtitle = $("#iSubTitle").val();
		var s_average = $("#iAverage").val();
			$.post("/bookshop/public/index.php/admin/admin/AddMerMode",{mname:s_name, 
			location:s_location, discount:s_discount, price:s_price, school:s_school, 
			type:s_tt, content:s_content, tel:s_tel,point:s_point,title:s_title,
			sub:s_subtitle,average:s_average}, 
		function(data){
		if(data >= 1)
		{
			alert("录入成功");

		}
		else if(data == 0)
		{
			alert("无更新信息");
		}
		else if(data == -1)
		{
			alert("录入失败");
		}

		})
		
	},
		edit:function(s_merid){

	
		var s_name = $("#iName").val();

		var s_discount = $("#iDiscount").val();
		var s_tt = $("#iType").val();
		var s_price = $("#iPrice").val();
		var s_content = UE.getEditor('editor').getContent();
		var s_point = $("#iPoint").val();
		var s_school = $("#iSchool").val();
		var s_tel = $("#iTel").val();
		var s_location = $("#iLocation").val();
		var s_title = $("#iTitle").val();
		var s_subtitle = $("#iSubTitle").val();
		var s_average = $("#iAverage").val();
			$.post("/bookshop/public/index.php/admin/admin/EditMerMode",{merid:s_merid, mname:s_name, 
			location:s_location, discount:s_discount, price:s_price,
			point:s_point,title:s_title,subtitle:s_subtitle,average:s_average,
			school:s_school, type:s_tt, content:s_content, tel:s_tel}, 
		function(data){
		if(data >= 1)
		{
			alert("录入成功");

		}
		else if(data == 0)
		{
			alert("无更新信息");
		}
		else if(data == -1)
		{
			alert("录入失败");
		}

		})
		
	},
	getInfo:function(merid){
		window.location.href = '/bookshop/public/index.php/admin/admin/merinfo/merid/' + merid;

	}
	
}