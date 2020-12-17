<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Local Shipments Pakistan</title>
		<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/attention/attention.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/stepprogress/stepprogressbar.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/alertifyjs/css/alertify.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/alertifyjs/css/themes/bootstrap.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/css/common.css') }}" />
		<style>
		.tabulator [tabulator-field="summary"]{
				max-width:200px;
		}
		.row {
			width:90%;
		}
		</style>
    </head>
    <body>
	<div class="flex-container">
		<div class="row"> 
		    <br>
			<div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#4682B4;color:white;" class="flex-item"> 
			 <img style="float:left;" height="50px" src="{{ asset('apps/lshipment/images/mentor.png') }}"></img>
			 <div style="margin-right:150px;">Heading</div>
			</div>
			<div class="flex-item"> 
			<small class="flex-item" style="font-size:12px;">Summary</small>
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
			
			<div class="flex-item">
			</div>
		</div>
	</div>
    </body>
	<script src="{{ asset('libs//jquery/jquery.min.js')}}" ></script>
	<script src="{{ asset('libs/alertifyjs/alertify.min.js') }}" ></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	<script src="{{ asset('libs/attention/attention.js') }}" ></script>
	<script>
	var drivers = @json($drivers);
	console.log(drivers);
	var columns=[
	{title:"Source", field:"product", sorter:"string", align:"left"},
	{title:"Class", field:"class", sorter:"string", align:"left"},
	{title:"Driver", field:"name", sorter:"string", align:"left"},
	{title:"Identifiers", field:"identifiers", sorter:"string", align:"left",width:"200"},
	
	{//create column group
		title:"Estimates(Story Points)",
		align:"right",
		columns:[
			{title:"Nuc 4.x", field:"estimates.765e3650c22dfd96fba3f4d1a10d238d", sorter:"number", align:"left",width:"100",editor:"input"},
			{title:"Nuc 3.x", field:"estimates.7c98b35fa2b3b347fcbf26accfc83404", sorter:"nu,ber", align:"left",width:"100",editor:"input"}
		],
    }
		
	];
	$(document).ready(function()
	{
		console.log("Loaded");
		var table = new Tabulator("#table", {
			data:drivers,
			columns:columns,
			tooltips:true,
			layout:"fitDataFill",
			//autoColumns:true,
		});
	});
	
	</script>
</html>
