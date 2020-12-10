<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>EPS Sprint Calendar</title>
		<link rel="stylesheet" href="{{ asset('libs/sprintcalendar/calendar.css') }}" />
    <style>
		.flex-container {
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
			width: auto;
			
		}
		.flex-item {
			text-align: center;
		}

    </style>
    </head>
    <body>
	  <div class="flex-container">
		<div class="row"> 
		    <br>
			<div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#4682B4;color:white;" class="flex-item"> 
			 <img style="float:left;" height="50px" src="{{ asset('apps/lshipment/images/mentor.png') }}"></img>
			 <div style="margin-right:150px;"> Sprint Calendar </div>
			</div>
			<div class="flex-item"> 
			<small class="flex-item" style="font-size:12px;"><a id="update" href="#"></a></small>
			</div>
			<hr>
			<div style="display:none" id="selectdiv">
				<span style="font-weight:bold;">Team&nbsp&nbsp</span><select  id='select'>Team</select><span>&nbsp&nbsp</span><span id="teamurl"></span>
			</div>
			<div class="flex-item"> 
				<br>
			</div>
			<div class="flex-item">
				<div style="box-shadow: 3px 3px #888888;" id="table"></div>
			</div>
			<div id="table"></div>
			<div class="flex-item">
				
				<small style="font-size:10px;">Dashboard created by mumtaz.ahmad@siemens.com for engineering operations Pakistan<a id="update" href="#"></a></small><br>
				
			</div>
		</div>
	</div>
		
	
    </body>
	<script src="{{ asset('libs//jquery/jquery.min.js')}}" ></script>
	<script src="{{ asset('libs/sprintcalendar/calendar.js') }}" ></script>
	<script>
	//define data
	var tabledata = @json($tabledata);
	console.log(tabledata.months);
	$(document).ready(function()
	{
		console.log("Showing sprint table");
		var rmo = new Rmo(tabledata);	
		rmo.Show("table");
	});
	
	</script>
</html>
