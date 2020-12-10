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

    </style>
    </head>
    <body>
	<div class="flex-container">
		<div class="row"> 
		    <br>
			<div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#4682B4;color:white;" class="flex-item"> 
			 <img style="float:left;" height="50px" src="{{ asset('apps/lshipment/images/mentor.png') }}"></img>
			 <div style="margin-right:150px;">{{ $team }} - Local Shipments Dashboard - Pakistan</div>
			</div>
			<div class="flex-item"> 
			<small class="flex-item" style="font-size:12px;">This Dashboard lists down Open,In progress and recently delivered shipment tickets</small>
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
				
				<small style="font-size:10px;">Dashboard created by mumtaz.ahmad@siemens.com for engineering operations Pakistan</small><br>
				<small style="font-size:10px;">Last updated on {{$lastupdated}} PKT <span style="text-decoration: underline;cursor: pointer;" title="Request for dashboard update" id="update" href="#">Update now</span></small>
			</div>
		</div>
	</div>
    </body>
	<script src="{{ asset('libs//jquery/jquery.min.js')}}" ></script>
	<script src="{{ asset('libs/sheetjs/xlsx.full.min.js')}}" ></script>
	<script src="{{ asset('libs/crypto/core.js')}}" ></script>
	<script src="{{ asset('libs/crypto/md5.js') }}" ></script>
	<script src="{{ asset('libs/alertifyjs/alertify.min.js') }}" ></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	<script src="{{ asset('libs/attention/attention.js') }}" ></script>
	<script>
	//define data
	var labels = {};
	var admin = {{$admin}};
	var sync_requested = 0;
	var tabledata = @json($tickets);
	
	for(i=0;i<tabledata.length;i++)
	{
		var row=tabledata[i];
		
		var l = row.label.replace(/ /g, '');
		if(l.length == 0)
			row.label = 'Others';
		if(row.label=='Others')
			continue;
		labels[row.label]=row.label;
		
	}
	
	labels['Others']='Others';
	$('#select').append('<option value="'+'Select'+'" selected="selected">'+'Select'+'</option>');
	for(var i in labels)
    {
		console.log(labels[i]);
		$('#select').append('<option value="'+labels[i]+'" >'+labels[i]+'</option>');
	}
	
	var columns=[
	{title:"ID", field:"index", sorter:"string", align:"left",width:"1"},
	{title:"Requestor", field:"requestor", sorter:"string", align:"left",width:"100"},
	{title:"Hardware Details", field:"details", sorter:"string", align:"left",width:"350",formatter:
		function(cell, formatterParams, onRendered)
		{
			console.log(cell.getValue());
			var row = cell.getRow().getData();
			var url = row.url;
			if(admin==1)
				return "<a href='"+url+"'>"+cell.getValue()+'</a>';
			else
				return cell.getValue();
			//return '<a href="'+jira_url+cell.getValue()+'">'+cell.getValue()+'</a>';
		}
	},
	{title:"Source", field:"source", sorter:"string", align:"left",width:"110"},
	{title:"Destination", field:"dest", sorter:"string", align:"left",width:"110"},
	{title:"Priority", field:"priority", sorter:"string", align:"left",width:"60"},
	{title:"Team", field:"label", sorter:"string", align:"left",width:"100"},
	{title:"Shipment", field:"name", sorter:"string", align:"left",visible:false},
	{title:"Status", field:"name", align:"center",width:270,tooltips:false, visible:true,formatter:
		function(cell, formatterParams, onRendered)
		{
			var row = cell.getRow().getData();
			//row.checkItems;
			//row.checkItemsChecked;
			$(cell.getElement()).css({"padding":"0px"});
			$(cell.getElement()).css({"margin-bottom":"-5px"});
			var html='<div class="container"><ul class="progressbar">';
			var data = cell.getRow().getData();
			if((data.dueComplete === true)&&(data.progress ==100))
				row.checkItemsChecked = 5;
			var cls = '';
			if(row.checkitems['Package Ready'] == 'complete')
				cls = 'active';
			
			html += '<li title="Package Ready" class="'+cls+'"><small style="font-size:7px"></small></li>';
			
			cls = '';
			if(row.checkitems['Consignee Approval'] == 'complete')
				cls = 'active';
			
			if('Vector VN3600 (from Danish Hassan cubical)' == row.details.trim())
			{
				console.log(row.details+" "+row.checkitems['Consignee Approval']);
				console.log(row.checkitems);
				console.log(cls);
			}
			
			html += '<li title="Consignee Approved" class="'+cls+'"><small style="font-size:7px"></small></li>';
			
			cls = '';
			if(row.checkitems['Update Hardware Inventory'] == 'complete')
				cls = 'active';
			
			html += '<li title="Updated in Hardware Inventory" class="'+cls+'"><small style="font-size:7px"></small></li>';
			
			cls = '';
			if(row.checkitems['Package Picked'] == 'complete')
				cls = 'active';
			
			html += '<li title="Package Picked" class="'+cls+'"><small style="font-size:7px"></small></li>';
			
			cls = '';
			if(row.checkitems['Package Delivered'] == 'complete')
				cls = 'active';
			
			html += '<li title="Package Delivered" class="'+cls+'"><small style="font-size:7px"></small></li>';
			
			
			
		/*	for(var i=0;i<row.checkItemsChecked;i++)
			{
				if(i==0)
					html += '<li title="Package Ready" class="active"><small style="font-size:7px"></small></li>';
				else if(i==1)
					html += '<li title="Cosignee Approved" class="active"><small style="font-size:7px"></small></li>';
				else if(i==2)
					html += '<li title="Updated in Hardware Inventory" class="active"><small style="font-size:7px"></small></li>';
				else if(i==3)
					html += '<li title="Package Picked" class="active"><small style="font-size:7px"></small></li>';
				else if(i==4)
					html += '<li title="Package Delivered" class="active"><small style="font-size:7px"></small></li>';
			}
			for(var i=row.checkItemsChecked;i<5;i++)
			{
				if(i==0)
					html += '<li title="Package Ready"><small style="font-size:7px"></small></li>';
				else if(i==1)
					html += '<li title="Cosignee Approved"><small style="font-size:7px"></small></li>';
				else if(i==2)
					html += '<li title="Updated in Hardware Inventory"><small style="font-size:7px"></small></li>';
				else if(i==3)
					html += '<li title="Package Picked"><small style="font-size:7px"></small></li>';
				else if(i==4)
					html += '<li title="Package Delivered"><small style="font-size:7px"></small></li>';
				
			}*/
			html += '</ul>';

			return html;
			
			var imagename = "/images/"+row.checkItems+"_"+row.checkItemsChecked+".png";
			$(cell.getElement()).css({"background":"white"});
			$(cell.getElement()).css({"padding":"0px"});
			$(cell.getElement()).css({"margin":"0px"});
			$(cell.getElement()).css({"height":"23px"});
			return "<img  width='200px' src='"+imagename+"'>";
			//return '<a href="'+jira_url+cell.getValue()+'">'+cell.getValue()+'</a>';
		}
	},
	{title:"Progress", field:"progress", sorter:"number", align:"left",visible:false,
			formatter:function(cell, formatterParams, onRendered)
		{
			var time_consumed = cell.getValue();
			var row = cell.getRow();
			$(cell.getElement()).css({"background":"white"});
			//if(time_consumed == 100)
			//{
			//	return  '<span style="text-align: center;display: inline-block;width:'+'100'+'%;color:white;background-color:grey;"><small>'+time_consumed+'%</small></span>';
			//}
			if(time_consumed <50)
			{
				bcolor='Orange';
				fcolor='white';
			}
			else if(time_consumed <75)
			{
				bcolor='DarkSeaGreen';
				fcolor='black';
			}
			else if(time_consumed <100)
			{
				bcolor='MediumSeaGreen';
				fcolor='white';
			}
			else
			{
				
				bcolor='green';
				fcolor='white';
				return  '<span style="text-align: center;display: inline-block;width:'+time_consumed+'%;color:'+fcolor+';background-color:'+bcolor+';"><small>Delivered</small></span>';

			}
			
			return  '<span style="text-align: center;display: inline-block;width:'+time_consumed+'%;color:'+fcolor+';background-color:'+bcolor+';"><small>'+time_consumed+'%</small></span>';
		}
	},
	{title:"Requested date", field:"createdon", sorter:"string", align:"left",visible:true,
		formatter:function(cell, formatterParams, onRendered)
		{
			return new Date(cell.getValue()).toString().substr(0,15);
		}
	
	},
	{title:"Delivery date", field:"due", sorter:"string", align:"left",visible:true,
		formatter:function(cell, formatterParams, onRendered)
		{
			var data = cell.getRow().getData();
			if((data.dueComplete === true)&&(data.progress ==100))
			{
				$(cell.getRow().getElement()).css({"color":"#989898"});
				return new Date(data.deliveredon).toString().substr(0,15);
			}
			return new Date(cell.getValue()).toString().substr(0,15);
		}
	},
	
	{title:"Due", field:"deliveredon", sorter:"string", align:"left",visible:false},
	{title:"Due", field:"dueComplete", sorter:"string", align:"left",visible:false}

	];
	
	function OnTimeOut()
	{
		if(sync_requested)
		{
			Get("{{ route('lshipment.issynced')}}",
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
	$(document).ready(function()
	{
		var getUrl = window.location;
		var baseUrl = getUrl .protocol + "//" + getUrl.host;
		
		$( "#update" ).click(function() {
				console.log("clicked");
				if(sync_requested)
					return;
				Get("{{ route('lshipment.sync')}}",
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
			tooltips:true,
			//autoColumns:true,
			initialSort:[
				{column:"due", dir:"dsc"}, //sort by this first
			]
		});
		$('select').on('change', function() {
			if(this.value == "Select")
				table.clearFilter(true);
			else
			{
				table.setFilter("label", "=", this.value);
				var md5=CryptoJS.MD5(this.value.toLowerCase()).toString();
				var url  = baseUrl + "/" + this.value.toLowerCase()+ "/" + md5.substring(0,6);
				var url = '{{route("lshipment.active")}}/'+this.value.toLowerCase()+"/"+md5.substring(0,6);
				$('#teamurl').html('<a href="'+url+'">Share Link</a>');
			}
		});
		if(admin==1)
			$('#selectdiv').show();
		//table.setFilter("label", "=", "AND");
		
	});
	
	</script>
</html>
