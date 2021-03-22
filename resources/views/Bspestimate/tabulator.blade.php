<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>BSP Estimator</title>
		<link rel="icon" type="image/png" href="{{ asset('apps/bspestimate/appicon.png') }}"/>
		<link rel="stylesheet" href="{{ asset('libs/fontawesome/fontawesome.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/fontawesome/all.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('apps/bspestimate/modal.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/attention/attention.css') }}" />
	<style>
	.dropdown-menu
	{
	background-color:rgba(205, 205, 205, 0.7);
	width:50%
	}
	.tokens-container
	{
		border: 1px solid red;
	}
	input select .tokenize
	{
	    border: 1px solid red;
	}
	ul {
		list-style-type:none;
	}
	body {font-family: Arial, Helvetica, sans-serif;}
	
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
		<!-- The Modal -->
	<div id="myModal" class="modal">
	  <!-- Modal content -->
	  <div class="modal-content">
		<div class="modal-header">
		  <span class="close">&times;</span>
		  <h2>Configure <span id="gclass"></span> Driver</h2>
		</div>
		<div class="modal-body">
		  <!-- <select id="cpu" class="tokenize2" multiple></select> -->
		  <table  style="width:90%">
			<colgroup>
				<col width="70%" />
				<col width="12%" />
				<col width="13%" />
				<col width="6%" />
			</colgroup>
			<tr>
				<th align="left">Task</th>
				<th align="left">Dev</th>
				<th align="left">QA</th>
				<th align="right">Enabled</th>
			</tr>
			<tbody id="conftable">
			</tbody>
		  </table>
		</div>
		<div class="modal-footer">
		  <!-- <h3>Modal Footer</h3> -->
		  <br>
		</div>
	  </div>

	</div>

		<!-- The Modal 
		<div id="myModal" class="modal">
		  <div class="modal-content">
		    <span id='gclass'></span>
		    <select id="cpu" class="tokenize2" multiple></select>
			<span class="close">&times;</span>
		  </div>
		</div> -->
        
		
		<div class="flex-container">
		<div class="row"> 
		    <div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#4682B4;color:white;" class="flex-item"> 
			 <img style="float:left;" height="50px" src="{{ asset('libs/mentor/images/logo.jpg') }}"></img>
			 <div style="margin-right:150px;"> BSP Estimator </div>
			</div>
			
			<div class="flex-item"> 
				<a style="float:left" id="reset" href="#">Reset</a>
				<div style="float:right"><a id="download" href="#">Download</a> </div>
				<br>
				<br>
			</div>
			<div class="flex-item">
				<div style="box-shadow: 3px 3px #888888;" id="standard_tasks"></div>
			</div>
			<div class="flex-item"> 
				<br>
			</div>
			<div class="flex-item">
				<div style="box-shadow: 3px 3px #888888;" id="driver_estimates"></div>
			</div>
			<div class="flex-item">				
				<small style="font-size:10px;">Nucleus Bsp estimator is created by mumtaz.ahmad@siemens.com - Embedded Platform Solutions
				&nbsp&nbsp&nbsp<a id="update" href="#">Update Meta Data</a>
				</small>
			</div>
		</div>
	</div>
		
		
		
		
		
		
		
		
		
    </body>
	<script src="{{ asset('libs//jquery/jquery.min.js')}}" ></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	<script src="{{ asset('libs/sheetjs/xlsx.full.min.js')}}" ></script>
	<script src="{{ asset('libs/attention/attention.js') }}" ></script>
	<script>
	var tasks = [@json($tasks)];
	var drivers = [@json($drivers)];
	var dest_var = '4';
	var data = localStorage.getItem('tasks');
	if(data != null)
		tasks = JSON.parse(data);
	
	var table = new Tabulator("#standard_tasks", {
		//height:"311px",
		layout:"fitDataFill",
		data:tasks,
		dataTree:true,
		dataTreeBranchElement:false,
		dataTreeStartExpanded:[false, false],
		columns:[
		{title:"Title", field:"title", width:400, responsive:0}, //never hide this column
		{title:"Details", field:"readonly", width:300,formatter:
			function(cell, formatterParams, onRendered)	
		    {
			   return '';
			}
		},
		{title:"R", field:"readonly", sorter:"string",align:"left",visible:false,
			formatter:function(cell, formatterParams, onRendered)
			{
				if(cell.getRow().getData()._children === undefined)
				{
					if(cell.getRow().getData().readonly)
					{
						row = cell.getRow();
						$(row.getElement()).css({"color":"grey"});
						
					}
					return cell.getValue()
				}
				if((cell.getRow().getData().readonly)&&(cell.getRow().getData()._children.length==0))
				{
					row = cell.getRow();
					$(row.getElement()).css({"color":"grey"});
				}
				return cell.getValue();
			}
		},
		
		{title:"Dev Estimates", field:"dev_estimate", width:150, responsive:2,  editor:"input",
		    editable: function(cell) 
			{ 
				if(cell.getRow().getData().team == 'qa')
				{
					return false; 
				}
				if(cell.getRow().getData().readonly)
					return false; 
				else
					return true;
			}
		},
		{title:"QA Estimates", field:"qa_estimate", width:150, responsive:2,  editor:"input", 
			editable: function(cell) 
			{ 
				if(cell.getRow().getData().team == 'dev')
					return false; 
				
				if(cell.getRow().getData().readonly)
					return false; 
				else
					return true;
			},
		},
		],
		cellEdited:function(cell)
		{
			var tree_row = table.getRowFromPosition(0);
			var child_rows = tree_row.getTreeChildren();
			
			//UpdateEstimates(data);
			for(var i=0;i<tasks[0]._children.length;i++)
			{
				var child = tasks[0]._children[i];
				total = 0;
				for(var j=0;j<child._children.length;j++)
				{
					var cchild = child._children[j];
					if(cchild.dev_estimate !== undefined)
						if(cchild.dev_estimate > 0 )
						{
							total = total + parseInt(cchild.dev_estimate);
							child_rows[0].update({"dev_estimate":total});
							tree_row.update({"dev_estimate":total});
						}
					if(cchild.qa_estimate !== undefined)
						if(cchild.qa_estimate > 0 )
						{
							total = total + parseInt(cchild.qa_estimate);
							child_rows[1].update({"qa_estimate":total});
							tree_row.update({"qa_estimate":total});
						
						}
					
				}
				//child.
			}
			localStorage.setItem('tasks', JSON.stringify(tasks));
		},
	});

	@include('bspestimate.drivers')
	

	$(document).ready(function()
	{
		$( "#reset" ).click(function() {
			localStorage.removeItem('drivers');
			localStorage.removeItem('tasks');
			location.reload();
		});
		
		$( ".close" ).click(function() {
			
			UpdateEstimates(window.selected_row);
			$('#myModal').hide();
		});
		
		$('#update').on('click',function()
		{
			new Attention.Alert({
					title: 'Alert',
					content: 'Meta data update requested.\n You will be notifed when done',
					afterClose: () => {
						
					}
					});
			setTimeout(function() {
				$('.attention-component').remove();
			}, 5000); //
			
			$.ajax({
				type:"GET",
				url:'{{route("bspestimate.sync")}}',
				cache: false,
				data:null,
				success: function(response){
					
					new Attention.Alert({
					title: 'Alert',
					content: 'Updated. Page will be refreshed',
					afterClose: () => {
						localStorage.removeItem('drivers');
						localStorage.removeItem('tasks');
						location.reload();
					}
					});
					setTimeout(function() {
						localStorage.removeItem('drivers');
						localStorage.removeItem('tasks');
						location.reload();
					}, 5000); //
				},
				error: function(response){
					new Attention.Alert({
					title: 'Alert',
					content: 'Failed',
					afterClose: () => {
						
					}
					});
				}
			});	
		});
		$('#download').on('click',function()
		{
			console.log('download');
			//table.download("xlsx", "support.xlsx", {sheetName:"tickets"});
			var xls = [];
			var xlsHeader = ["Type","Title", "Dev Estimates","QA Estimates"];
			var xlsRows = [];
			
			for(var i=0;i<tasks.length;i++)
			{
				var task = tasks[i];
				
				for(var j=0;j<task._children.length;j++)
				{
					var group = task._children[j];
					for(var k=0;k<group._children.length;k++)
					{
						var child = group._children[k];
						var nchild = {};
						nchild.category = group.title;
						nchild.title = child.title;
						nchild.dev_estimate = child.dev_estimate;
						nchild.qa_estimate = child.qa_estimate;
						
						if( typeof nchild.dev_estimate != 'number')
							nchild.dev_estimate = 0;
						if( typeof nchild.qa_estimate != 'number')
							nchild.qa_estimate = 0;
				
						xlsRows.push(nchild);
					}
				}
			}
			
			for(var i=0;i<drivers[0]._children.length;i++)
			{
				var child = drivers[0]._children[i];
				var nchild = {};
				nchild.category = child.name;
				if(( child.selected_option == '')||(child.selected_option == 'None'))
					continue;
				
				nchild.title = child.selected_option;
				nchild.dev_estimate = child.dev_estimate;
				nchild.qa_estimate = child.qa_estimate;
				if( typeof nchild.dev_estimate != 'number')
					nchild.dev_estimate = 0;
				if( typeof nchild.qa_estimate != 'number')
					nchild.qa_estimate = 0;
					
				xlsRows.push(nchild);
				for(var j=0;j<child.children.length;j++)
				{
					var cchild = child.children[j];
					if(cchild.enabled)
					{
						var nchild = {};
						nchild.category = '';
						nchild.title = cchild.name;
						nchild.title = nchild.title+" Dev="+cchild.dev_estimate+" QA="+cchild.qa_estimate;
						xlsRows.push(nchild);
					}
				}
			}

			xls.push(xlsHeader);
			 /* File Name */
		 $.each(xlsRows, function(index, value) {
            var innerRowData = [];
       
            $.each(value, function(ind, val) {

                innerRowData.push(val);
            });
            xls.push(innerRowData);
        });
		
        var filename = "BSP_Estimates.xlsx";

        /* Sheet Name */
        var ws_name = "Estimates";

        if (typeof console !== 'undefined') console.log(new Date());
        var wb = XLSX.utils.book_new(),
            ws = XLSX.utils.aoa_to_sheet(xls);

        /* Add worksheet to workbook */
        XLSX.utils.book_append_sheet(wb, ws, ws_name);

        /* Write workbook and Download */
        if (typeof console !== 'undefined') console.log(new Date());
        XLSX.writeFile(wb, filename);
        if (typeof console !== 'undefined') console.log(new Date());
			
		});
	});
	function ShowModal(data)
	{
		window.selected_row = data;
		console.log(data);
		$('#gclass').html(data.class);
		$('#conftable').empty();
		for(var i=0;i<data.children.length;i++)
		{
			var child = data.children[i];
			var status = ''; 
			if((child.readonly)&&(child.enabled))
				status = '<input class="enable_checkbox" data-id="'+child.id+'" type="checkbox" checked disabled>';
			else if((child.readonly)&&(!child.enabled))
				status = '<input class="enable_checkbox" data-id="'+child.id+'" type="checkbox"  disabled>';
			else if((!child.readonly)&&(child.enabled))
				status = '<input class="enable_checkbox" data-id="'+child.id+'" type="checkbox" checked>';
			else if((!child.readonly)&&(!child.enabled))
				status = '<input class="enable_checkbox" data-id="'+child.id+'" type="checkbox" >';
			
			
			var row = $('<tr style="font-size:13px"></tr>');
			row.append('<td style="text-overflow:ellipsis; overflow: hidden;">'+child.name+'</td>');
			row.append('<td>'+child.dev_estimate+'</td>');
			row.append('<td>'+child.qa_estimate+'</td>');
			row.append('<td>'+status+'</td>');
			$('#conftable').append(row);
			
			//$('.modal-body').append('Line 1');
		}
		$('#myModal').show();
		$('.enable_checkbox').click(function()
		{
			var id = $(this).data('id');
			if($(this).is(':checked'))
				enabled=1;
			else
				enabled=0;
			
			for(var i=0;i<data.children.length;i++)
			{
				if(data.children[i].id == id)
					data.children[i].enabled = enabled;
			}
			console.log(data);
			//console.log($(this).data('id'));
		});
	}
	
	</script>
</html>
