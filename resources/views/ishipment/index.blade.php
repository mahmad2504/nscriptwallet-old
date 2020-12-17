<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>International Shipments Pakistan</title>
		<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/alertifyjs/css/alertify.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/alertifyjs/css/themes/bootstrap.min.css') }}" />
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
		.label_orchid {
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
			<small class="flex-item" style="font-size:12px;">This Dashboard lists down Open, In progress and recently delivered shipments<a id="" href="#"></a></small>
			</div>
			<hr>
			
			<div class="flex-item"> 
				<br>
			</div>
			<div class="flex-item">
				<div style="box-shadow: 3px 3px #888888;" id="table"></div>
			</div>
			
			<div class="flex-item">
				
				<small style="font-size:10px;">Automted by mumtaz.ahmad@siemens.com for engineering operations Pakistan </small><br>
				<small style="font-size:10px;">Last updated on {{$lastupdated}} PKT  <span style="text-decoration: underline;cursor: pointer;" title="Request for dashboard update" id="update" href="#">Update now</span></small>
			</div>
		</div>
	</div>
    </body>
	<script src="{{ asset('libs//jquery/jquery.min.js')}}" ></script>
	<script src="{{ asset('libs/sheetjs/xlsx.full.min.js')}}" ></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	<script src="{{ asset('libs/alertifyjs/alertify.min.js') }}" ></script>
	<script>
	//define data
	var tabledata = @json($tickets);
	var sync_requested = 0;
	var columns=[
		{title:"Hardware", field:"hardware", sorter:"string", align:"left",width:"250",formatter:
			function(cell, formatterParams, onRendered)
			{
				var row = cell.getRow().getData();
				var url = row.url;
				return "<a href='"+url+"'>"+cell.getValue()+'</a>';		
			}
		},
		{title:"Owner", field:"owner", sorter:"string", align:"left"},
		{title:"Team", field:"team", sorter:"string", align:"left"},
		{title:"Source", field:"source", sorter:"string", align:"left"},
		{title:"Dispatched on", field:"shipment_date", sorter:"string", align:"center",formatter:
			function(cell, formatterParams, onRendered)
			{
				var dt  = new Date(cell.getValue());
				var rval = dt.toString().substring(0, 15);
				if(rval == 'Invalid Date')
					return 'N/A';
				return dt.toString().substring(0, 15);
			}
		
		},
		{title:"Tracking", field:"trackingno", sorter:"string", align:"center",formatter:
			function(cell, formatterParams, onRendered)
			{
				var row = cell.getRow().getData();
				if(1)//row.status != 'Received')
				{
					if(cell.getValue() == '')
						return "N/A";
					var dhlurl = "https://www.packagetrackr.com/track/dhl_express/"+cell.getValue();
					return "<a href='"+dhlurl+"'>"+'<img title="Tracking # '+cell.getValue()+'" width="50" style="margin-top:5px;" src="{{ asset('apps/ishipment/images/dhl.png') }}">'+'</a>';;
				}
				return '';
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
		},
		{title:"Received on", field:"received_date", sorter:"string", align:"left",formatter:
			function(cell, formatterParams, onRendered)
			{
				var dt  = new Date(cell.getValue());
				var rval = dt.toString().substring(0, 15);
				if(rval == 'Invalid Date')
					return 'N/A';
				return dt.toString().substring(0, 15);
			}
		}
	];
	function Get(url,success,failure)
	{
		$.ajax(
		{
			type:"GET",
			url:url,
			data:null,
			success: success,
			error: failure
		});
	}
	function OnTimeOut()
	{
		if(sync_requested)
		{
			Get("{{ route('ishipment.issynced')}}",
			function (response){
				console.log(response);
				if(response.status == 1)
				{
					alertify.success('Updated');
					location.reload();
				}
				else
				{
					setTimeout(OnTimeOut, 20000);
				}
			},
			function (error) {});
		}
				
	}
	$(document).ready(function()
	{
		$( "#update" ).click(function() {
			    console.log("clicked");
				if(sync_requested)
					return;
				Get("{{ route('ishipment.sync')}}",
				function(response)
				{
					console.log(response);
					alertify.success('Update Requested');
					$('#update').html("Update requested");
					sync_requested = 1;
					setTimeout(OnTimeOut, 20000);
				},
				function (error) 
				{
					console.log(error); 
					alertify.error('Network Error');
					sync_requested = 0;					
				}
			);
		});
		var table = new Tabulator("#table", {
			data:tabledata,
			columns:columns,
			tooltips:true
		});
	});
	</script>
</html>
