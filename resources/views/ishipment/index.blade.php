<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Local Shipments Pakistan</title>
		<link rel="stylesheet" href="{{ asset('apps/ishipment/tabulator/css/tabulator.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('apps/ishipment/attention/attention.css') }}" />
		<link rel="stylesheet" href="{{ asset('apps/ishipment/stepprogress/stepprogressbar.css') }}" />
		<style>
		.tabulator [tabulator-field="summary"]{
				max-width:200px;
		}

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
		.label_green {
			color:#ffffff;
			background-color: #4CAF50;
			width:100%;
		}
		.label_aqua {
			color:#000000;
			background-color: #00FFFF;
			width:100%;
		}
		.label_orange {
			color:#000000;
			background-color: #FF8C00;
			width:100%;
		}
		label_orchid {
			color:#ffffff;
			background-color: #9932CC;
			width:100%;
		}
		

    </style>
    </head>
    <body>
	<div class="flex-container">
	
		<div class="row"> 
			<br>
			<div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#4682B4;color:white;" class="flex-item"> 
			 <img style="float:left;" height="50px" src="{{ asset('apps/ishipment/images/mentor.png') }}"></img>
			 <div style="margin-right:150px;"> International Shipments Dashboard </div>
			</div>
			<div class="flex-item"> 
			<small class="flex-item" style="font-size:12px;">This Dashboard lists down Open,In progress and recently delivered shipment tickets<a id="update" href="#"></a></small>
			</div>
			<hr>
			
			<div class="flex-item"> 
				<br>
			</div>
			<div class="flex-item">
				<div style="box-shadow: 3px 3px #888888;" id="table"></div>
			</div>
			
			<div class="flex-item">
				
				<small style="font-size:10px;">Automted by mumtaz.ahmad@siemens.com for engineering operations Pakistan<a id="update" href="#"></a></small><br>
				<small style="font-size:10px;">Last updated on {{$lastupdated}} PKT</small>
			</div>
		</div>
	</div>
    </body>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/core.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/md5.js"></script>
	<script src="{{ asset('apps/ishipment/tabulator/js/tabulator.min.js') }}" ></script>
	<script src="{{ asset('apps/ishipment/attention/attention.js') }}" ></script>
	<script>
	//define data
	var tabledata = @json($tickets);
	console.log(tabledata);
	
	var columns=[
		{title:"Hardware", field:"hardware", sorter:"string", align:"left",width:"350",formatter:
			function(cell, formatterParams, onRendered)
			{
				var row = cell.getRow().getData();
				var url = row.url;
				return "<a href='"+url+"'>"+cell.getValue()+'</a>';		
			}
		},
		{title:"Owner", field:"owner", sorter:"string", align:"left"},
		{title:"Source", field:"source", sorter:"string", align:"left"},
		{title:"Team", field:"team", sorter:"string", align:"left"},
		{title:"Dispatched on", field:"shipment_date", sorter:"string", align:"center",formatter:
			function(cell, formatterParams, onRendered)
			{
				var dt  = new Date(cell.getValue());
				var rval = dt.toString().substring(0, 15);
				if(rval == 'Invalid Date')
					return '';
				return dt.toString().substring(0, 15);
			}
		
		},
		{title:"Tracking", field:"trackingno", sorter:"string", align:"center",formatter:
			function(cell, formatterParams, onRendered)
			{
				var dhlurl = "https://www.packagetrackr.com/track/dhl_express/"+cell.getValue();
				return "<a href='"+dhlurl+"'>"+'<img title="Tracking # '+cell.getValue()+'" width="50" style="margin-top:5px;" src="{{ asset('apps/ishipment/images/dhl.png') }}">'+'</a>';;
	
			}
		},
		{title:"Status", field:"status", sorter:"string", align:"left",formatter:
			function(cell, formatterParams, onRendered)
			{
				switch(cell.getValue())
				{
					case 'Ready':
						return "<button title='Shipment is ready and will be dispatched as soon as approval is done' class='label_orange'>"+cell.getValue()+'</button>';
						break;
					case 'Dispatched':
						return "<button title='Shipment is in Transit' class='label_orchid'>"+cell.getValue()+'</button>';
						break;
					case 'Customs':
						return "<button title='Shipment is in Customs' class='label_aqua'>"+cell.getValue()+'</button>';
						break;
					case 'Received':
						return "<button title='Shipment is received in office' class='label_green'>"+cell.getValue()+'</button>';
						break;
					default:
						return "<button >"+cell.getValue()+'</button>';
					break
				}		
			}
		}
	];
	$(document).ready(function()
	{
		var table = new Tabulator("#table", {
			data:tabledata,
			columns:columns,
			tooltips:true
		});
	});
	</script>
</html>
