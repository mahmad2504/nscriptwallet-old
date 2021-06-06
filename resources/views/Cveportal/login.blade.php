<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Security - Siemens Embedded CVE Portal - Login</title>

<link rel="stylesheet" href="https://static.sw.cdn.siemens.com/css/resource/disw-style.css" />
<link rel="shortcut icon" href="https://www.plm.automation.siemens.com/favicon.ico" type="image/x-icon" />
<script type="module"
	src="https://static.sw.cdn.siemens.com/disw/universal-components/1.x/esm/index.module.js"></script>
<script src="https://static.sw.cdn.siemens.com/disw/disw-utils/next/disw-utils.min.js"></script>
<script type="module">
    window.universalComponents.init(['disw-header-v2', 'disw-footer']);
	
</script>
<style>

body{
  font-family: 'Open Sans', sans-serif;
  overflow: hidden; 
  margin: 0 auto 0 auto;  
  width:100%; 
  text-align:center;
  margin: 20px 0px 20px 0px;   
}

p{
  font-size:12px;
  text-decoration: none;
  color:#ffffff;
}

h1{
  font-size:1.5em;
  color:#525252;
}

.box{
  background:0xaaaaaa;
  width:300px;
  border-radius:6px;
  margin: 0 auto 0 auto;
  padding:0px 0px 70px 0px;
  border: #2980b9 4px solid;
  margin-top:200px;  
}

.user{
  background:#ecf0f1;
  border: #ccc 1px solid;
  border-bottom: #ccc 2px solid;
  padding: 8px;
  width:250px;
  color:#AAAAAA;
  margin-top:10px;
  font-size:1em;
  border-radius:4px;
}

.organization{
  background:#ecf0f1;
  border: #ccc 1px solid;
  border-bottom: #ccc 2px solid;
  padding: 8px;
  width:250px;
  color:#AAAAAA;
  margin-top:10px;
  font-size:1em;
  border-radius:4px;
}
.password{
  border-radius:4px;
  background:#ecf0f1;
  border: #ccc 1px solid;
  padding: 8px;
  width:250px;
  font-size:1em;
}

.btn{
  background:#2ecc71;
  width:125px;
  padding-top:5px;
  padding-bottom:5px;
  color:white;
  border-radius:4px;
  border: #27ae60 1px solid;
  
  margin-top:20px;
  margin-bottom:0px;
  float:none;
  margin-left:0px;
  font-weight:800;
  font-size:0.8em;
}

.btn:hover{
  background:#2CC06B; 
}
</style>
<html>
<body>

<disw-header-v2 account="true" scroll="true" locales="true">
</disw-header-v2>	
<div style="display:none;" class="loading">Loading&#8230;</div>
<form  action="#" id="loginform" method="#">
	<div class="box">
		<h1 style="margin-top:10px">Login</h1>
		<input type="organization" name="organization" value="organization" onFocus="field_focus(this, 'organization');" onblur="field_blur(this, 'organization');" class="user" />  
		<input type="user" name="user" value="user" onFocus="field_focus(this, 'user');" onblur="field_blur(this, 'user');" class="user" />  
		<input type="password" name="password" value="password" onFocus="field_focus(this, 'password');" onblur="field_blur(this, 'password');" class="user" />
		<button class="btn" id="submit" type="submit">Login</button>
		
	</div>
</form>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script>

function ShowLoading()
{
	$('.loading').show();	
}
function HideLoading()
{
	$('.loading').hide();	
}

function field_focus(field, default_value)
{
	if(field.value == default_value)
	{
	  field.value = '';
	  $(field).css('color','black');
	  console.log('black');
	}
}

function field_blur(field, default_value)
{
	if(field.value == '')
	{
	  field.value = default_value;
	  $(field).css('color','grey');
	  
	}
}

$(document).ready(function()
{
	console.log("Login Page Loaded");
	window.disw.init();
	$("#loginform").submit(function (e)
	{
		console.log("Submitted");
		e.preventDefault();
		formdata = {};
		formdata.data = {};
		$("#loginform").serializeArray().map(function(x)
		{
			if(x.value == '')
			{
				alert(x.name+" is empty");
				formdata.data = null;
			}
			formdata.data[x.name] = x.value;
		}); 
		console.log(formdata);
		if(formdata.data == null)
			return;
			
		ShowLoading();
		formdata._token = "{{ csrf_token() }}";
		
		$.ajax(
		{
			type:"POST",
			url:'{{route("cveportal.authenticate")}}',
			cache: false,
			data:formdata,
			success: function(response)
			{
				window.location.href = "{{route('cveportal.index')}}";
			},
			error: function(response)
			{
				HideLoading();
				alert(response.responseJSON.error);
			}
		});	
	});
});

</script>
</body>
</html>