
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Security - Mentor Graphics</title>

<style>
.progress 
{
    height: 20px;
}
.progress > svg 
{
	height: 100%;
	display: block;
}

.flex-container 
{
	height: 100%;
	padding: 0;
	margin: 0;
	display: -webkit-box;
	display: -moz-box;
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	align-items: center;
	justify-content: center;
}
.row {
	width: 80%;
	
}
.flex-item {
	text-align: center;
}
.box {
      border:2px solid #4FFFA1;
      padding:10px;
      background:#F6FFA1;
      width:100px;
      border-radius:25px;
    }
</style
</head>
<body>
	

	<div class="flex-container">
		<div class="row"> 
			<br>
			<div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#4682B4;color:white;" class="flex-item"> 
			<img style="float:left;" height="50px" src="{{ asset('apps/ishipment/images/mentor.png') }}"></img>
			<div style="margin-right:150px;"> Cryptography Analysis Dashoard <span style="color:orange"> {{$file_name}} </span></div>
			<div> ffff</div>
			</div>
			<div class="flex-item"> 
			<small class="flex-item" style="font-size:12px;"><a id="" href="#"></a></small>
			</div>
			<hr>
			
			<div class="flex-item"> 
			<div id="file">
				
			</div>
			</div>
		</div>
	</div>
	<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
	<script>
	var file_data =  @json($file_data);
	$(document).ready(function()
	{
		var style = '';
		for(var i=0;i<file_data.length;i++)
		{
			var line = file_data[i];
			if(line.startsWith("HIT"))
				style = 'color:red;font-weight:bold;';
			$('#file').append('<div style="float:left;'+style+'">'+line+'</div><br>');
			if(line.startsWith("HIT"))
				style ='color:red;font-weight:bold;';
			else
				style ='color:black';
			
			console.log(line);
		}
	});
	</script>
</body>
</html>