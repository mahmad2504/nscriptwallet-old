<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Security - Siemens Embedded CVE Portal - Triage</title>

<link rel="stylesheet" href="https://static.sw.cdn.siemens.com/css/resource/disw-style.css" />
<link type="text/css" rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator.min.css') }}" />
<link type="text/css" rel="stylesheet" href="{{ asset('apps/cveportal/css/cveportal.css') }}" />
<link type="text/css" rel="stylesheet" href="{{ asset('apps/cveportal/css/mgc_agg.css') }}" />
<link type="text/css" rel="stylesheet" href="{{ asset('apps/cveportal/css/mgc-icons-legacy.css') }}" />

<link rel="shortcut icon" href="https://www.plm.automation.siemens.com/favicon.ico" type="image/x-icon" />

<script type="module"
	src="https://static.sw.cdn.siemens.com/disw/universal-components/1.x/esm/index.module.js"></script>
<script src="https://static.sw.cdn.siemens.com/disw/disw-utils/next/disw-utils.min.js"></script>
<script type="module">
    window.universalComponents.init(['disw-header-v2', 'disw-footer']);
</script>
   

<style>
#download:hover { border: 1px solid green; border-radius: 2px; }
#svmsync:hover { border: 1px solid green; border-radius: 2px; }
#publish:hover { border: 1px solid green; border-radius: 2px; }
</style>


</head>


<body class=" mgc flex-body products">
	<!-- The Modal -->
	<div id="tmodal" class="modal">
		<!-- Modal content -->
		<div class="modal-content" style="width:90%;margin: auto;">
			<span id="closetmodal" class="close">&times;</span><br>
			<h3 class="cve_title"></h3>
			<small class="cve_description"></small><br>
			<small style="font-weight:bold" class="cve_solution"></small>
			<div  class="card card-block" style="margin-bottom:0px;">
				<div>
					<small style="float:left;margin-top:-10px;"><span style="font-weight:bold;">Vector: </span><span class="cvss_vector"></span></small>
					<small style="float:right;margin-top:-10px;"><span style="font-weight:bold;">SVM Priority: </span><small class="cvss_attackvector"></small></small>
				</div>
				<br>
				<div>
					<small style="float:left;margin-top:-10px;"><span style="font-weight:bold;">Score: </span><span class="cvss_basescore"></span></small>
					<small style="float:right;margin-top:-10px;"><span style="font-weight:bold;">Severity: </span><small class="cvss_severity"></small></small>
				</div>
				<br>
				<div>
					<small style="float:left;margin-top:-10px;"><span style="font-weight:bold;">Published: </span><span class="cve_published"></span></small>
					<small style="float:right;margin-top:-10px;"><span style="font-weight:bold;">Modified: </span><small class="cve_modified"></small></small>
				</div>
			</div>
			<div id="triage-table"></div>
			<hr>
			<small style="font-size:10px;margin-top:0px;float:right">Find out more about <span style="font-weight:bold;" class="cve_number"></span> from the <a class="mitre_link">MITRE-CVE</a> dictionary and <a class="nvd_link">NIST NVD</a></small>
		</div>
	</div>
	<!-- End The Modal -->
	
	<disw-header-v2 account="true" scroll="true" locales="true">
	</disw-header-v2>	
	
	<BR>
	<BR>
	<BR>
	
	
	<header style="background-color:orange!important" id="header-warning" class="bg-warning-darker p-y" >
		<div class="container">
			<div class="">
				<a style="font-size:25px;font-weight:bold" href="#" title="Security Vulnerabilities">Security Vulnerabilities</a>
				<select  title="Select Product Group" id="select_group" style="margin-left:150px">
				</select>
				<select  id="select_product" style="margin-left:10px;">
				</select>
				<select   id="select_version" style="margin-left:10px;">
				</select>
				<img  style="float:right;margin-top:7px" width=27  id="publish"></img>
				<img  style="float:right;margin-top:7px" width=30  id="svmsync"></img>
				<img  width=25 title="download in xlsx format" id="download" style="margin-top:10px;display:none;float:right" src="{{ asset('apps/cveportal/images/xlsx_download.png') }}"></img>
			</div>
		</div>
	</header>
			
	

	<div id="content" class="flex-content">
		<div  id="copy" style="width:90%; margin: auto;" class="container1">
		<br>
		<div class="row row-flex" style="width:100%;!important">
			<div id="sidebar" class="col-md-2 col-xs-12 last-xs first-md content-sidebar">
				<h2 class="hidden-lg-up header-group"><span><a href="#" title="Security Vulnerabilities">Security Vulnerabilities</a></span></h2>
				<div class="card callout callout-callout">	
					<div style="height:20px" class="card-header card-header-secondary">
					<small style="color:white;font-size:10px; margin-top:-6px;display: flex;justify-content: center;align-items: center;">{{$organization_name}}</small>
					</div>
					
					<div class="card-header">
						<h2 class="text-uc text-gray-dark m-b-0">Products</h2>
					</div>
					@for ($i = 0; $i < count($group_names); $i++)
					<li class="list-group-item list-group-item-nav p-a-0">
						<a class="productbutton" data-index="{{$i}}"  title="{{$group_names[$i]}}">{{$group_names[$i]}}</a>
					</li>
					@endfor
				</div>
			</div>
			<div class="col-md-10 col-xs-13 first-xs last-md content-main">
				<div class="card">
					<div style="height:5px;border:0px;" class="card-header card-header-secondary">
					<small style="color:white;font-size:10px; margin-top:-6px;display: flex;justify-content: center;align-items: center;">Last Updated on&nbsp<span id='last_updated'></span></small>
					</div>
					<div class="card-block" style="margin-top:-10px">
						<div class="row-container  ">
							<div class="row row-fluid ">
								<div id="vulnerability-table"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		</div>
	</div>
	<disw-footer slug="global-footer"></disw-footer>
	
	<script src="https://cdn.jsdelivr.net/bluebird/3.5.0/bluebird.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fetch/2.0.3/fetch.js"></script>
	<script src="{{ asset('apps/cveportal/js/svg.js') }}"></script>
	<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
	<script src="{{ asset('libs/sheetjs/xlsx.full.min.js')}}" ></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	
	
	<script>
	
	//define data
	var group_names = @json($group_names);
	var product_names = @json($product_names);
	var version_names = @json($version_names);
	var admin='{{$admin}}';
	var organization = '{{$organization}}';
	var last_updated = '{{$last_updated}}';
	var svmsyncrequest = {{$svmsyncrequest}};
	var publishrequest = {{$publishrequest}};
	function editCheck(cell)
	{
		//var cve = cell.getRow().getData();
		//if(cve.jira != '')
		//return 0;
		return 1;
    }
	function Get3Columns()
	{
		columns = [
        {title:"CVE", field:"cve", sorter:"string", width:130},
		{title:"Description", field:"title", sorter:"string", width:690},
		{title:"Priority", field:"_priority", sorter:"string", width:90},
		//{title:"Updated", field:"last_update", sorter:"string", width:100},
		{title:"CVSS", field:"basescore", width:100}
		//{title:"Published", field:"published", width:100,
		//	formatter:function(cell, formatterParams, onRendered)
		//	{	
		//		if(cell.getValue() == 1)
		//			return 'Yes';
		//		else
		//			return 'No';
		//		
		//	}
		//}
		];
		return columns;
	}
	function Get4Columns()
	{
		columns = [
			{title:"#", field:"index", formatter:"rownum"},
			{title:"CVE", field:"cve", sorter:"string", width:120,headerFilter:true},
			{title:"Description", field:"title", sorter:"string", width:500,headerFilter:true},
			{title:"Package", field:"component", sorter:"string", width:90,headerFilter:true,
				formatter:function(cell, formatterParams, onRendered)
				{	
					var data = cell.getRow().getData();
					
					return data.component+":"+data.component_version;
			   }
			},
			{title:"Priority", field:"_priority", sorter:"string", width:85,headerFilter:true},
			{title:"CVSS", field:"basescore", width:30, headerFilter:true},
			{title:"State", field:"status.triage", width:60, editor:"select", width:100,headerFilter:"input",editorParams:
				{
					"Investigate":"Investigate",
					"Vulnerable":"Vulnerable",
					"Won't Fix":"Won't Fix",
					"Fixed":"Fixed",
					"Not Applicable":"Not Applicable"
				},
				cellEdited:function(cell)
				{
					UpdateStatus(cell);
				},
				cssClass:'editable',
				editable:editCheck,
				formatter:function(cell, formatterParams, onRendered)
				{	
					if(cell.getValue() == 'Fixed')
						cell.getElement().style.color = "#006400"; //apply css change to row element
					else if( (cell.getValue() == 'Not Applicable')||(cell.getValue() == "Won't Fix"))
						cell.getElement().style.color = "#87898c"; //apply css change to row element
					else if(cell.getValue() == 'Vulnerable')
						cell.getElement().style.color = "#FF0000"; //apply css change to row element
					else if(cell.getValue() == 'Investigate')
						cell.getElement().style.color = "#40E0D0"; //apply css change to row element
					
					else
						cell.getElement().style.color = "#000000"; 
					return cell.getValue();
				},
			},
			{title:"Publish", field:"status.publish", width:90,editor:"tick",headerFilter:true,
				cellEdited:function(cell)
				{
					UpdateStatus(cell);
				},
				cssClass:'editable',
				formatter:function(cell, formatterParams, onRendered)
				{	
					if(cell.getValue()==true)
					{
						cell.getElement().style.color = "#006400"; //apply css change to row element
						return 'Published';
					}
					else
					{
						return '';
					}
					return cell.getValue();
				},
				editable:editCheck
			}
			//{title:"Modified", field:"modified", sorter:"string", width:100}
		];
		return columns;
	}
	function UpdateStatus(cell=gcell,comment=null)
	{
		selected_group = $('#select_group option:selected').val();
		selected_product = $('#select_product option:selected').val();
		selected_version = $('#select_version option:selected').val();

		data = cell.getRow().getData();
		d = {};
		
		if(data.status.publish)
			data.status.publish = "1";
		else
			data.status.publish = "0";
		
		data.status.user=admin;
		d.status = data.status;
		d.organization = organization;
		d.group = selected_group;
		d.product = selected_product;
		d.version = selected_version;
		if(comment != null)
			data.status.comment = $('#comment').val();
		
		d._token = "{{ csrf_token() }}";
		$.ajax({
			type:"PUT",
			url:'{{route("cveportal.status.update")}}',
			cache: false,
			data:d,
			success: function(response){
				cell.getRow().getElement().style.backgroundColor = "#8FBC8F";
				//console.log(cell.getRow().getData());
				d = cell.getRow().getData();
				for(i=0;i<d.products.length;i++)
				{
					var pid = d.products[i];
					if(d.product[pid].id == d.status.productid)
					{
						d.product[pid].status.triage = d.status.triage;
						d.product[pid].status.publish = d.status.publish;
						d.product[pid].status.comment = d.status.comment;
						//console.log(d.product[i].status);
					}
				}
				function colorrevert()
				{
					element = cell.getRow().getElement();
					if($(element).hasClass('tabulator-row-even'))
						element.style.backgroundColor = "#EFEFEF";
					else
						element.style.backgroundColor = "#ffffff";
				};
				setTimeout(colorrevert, 2000);
			},
			error: function(response){
				cell.restoreOldValue();
				cell.getRow().getElement().style.backgroundColor = "#FFD700";
				function colorrevert()
				{
					element = cell.getRow().getElement();
					if($(element).hasClass('tabulator-row-even'))
						element.style.backgroundColor = "#EFEFEF";
					else
						element.style.backgroundColor = "#ffffff";
				};
				setTimeout(colorrevert, 2000);
			}
		});
	}

	function AddOption(id,optionText,optionValue,selected) 
	{ 
	    if(!selected)
			$('#'+id).append('<option value="'+optionValue+'">'+ optionText +"</option>"); 
		else							
			$('#'+id).append('<option value="'+optionValue+'" selected>'+ optionText +"</option>");							
    } 
	$('#select_group').on('change', function()
	{
		index = group_names.indexOf(this.value);
		$('#select_product').children().remove();
		$('#select_version').children().remove();
		AddOption('select_product','All Products','all',0);
		AddOption('select_version','All Versions','all',0);
		if(index >= 0)
		{
			for(i=0;i<product_names[index].length;i++)
				AddOption('select_product',product_names[index][i],product_names[index][i],0);
		}
		LoadTableData();
	});
	$('#select_product').on('change', function()
	{
		combined_product_names = [];
		for(i=0;i<product_names.length;i++)
			combined_product_names = combined_product_names.concat(product_names[i]);
		product_index = combined_product_names.indexOf(this.value);
		$('#select_version').children().remove();
		AddOption('select_version','All Versions','all',0);
		if(product_index >= 0)
		{
			for(i=0;i<version_names[product_index].length;i++)
				AddOption('select_version',version_names[product_index][i],version_names[product_index][i],0);
		}
		LoadTableData();
	});
	$('#select_version').on('change', function()
	{
		LoadTableData();
	});
	function LoadTableData()
	{
		selected_group = $('#select_group option:selected').val();
		selected_product = $('#select_product option:selected').val();
		selected_version = $('#select_version option:selected').val();
		
		url = '/cveportal/cve/'+selected_group+'/'+selected_product+'/'+selected_version+'/'+admin;
		if(selected_version == 'all')
			CreateTable(url,Get3Columns(),'remote');
		else
			CreateTable(url,Get4Columns(),'local');
		
	}
	var gcell = null;
	var vulnerability_table = null;
	function CreateTable(url,columns,pagination)
	{
		url = url + "/"+organization;
		if(pagination == 'remote')
		{
			headerSort=false;
			$('#download').hide();
		}
		else
		{
			headerSort=true;
			$('#download').show();
		}
		console.log(pagination);
		vulnerability_table = new Tabulator("#vulnerability-table", {
			columns:columns,
			pagination:pagination,
			paginationSize:50,
			paginationSizeSelector: [10, 25, 50, 100],
			//autoColumns:true,
			headerSort:headerSort,
			headerFilterPlaceholder:"", 
			selectable:1,
			tooltips:true,
			ajaxURL:url,
			ajaxResponse:function(url, params, response)
			{
				//url - the URL of the request
				//params - the parameters passed with the request
				//response - the JSON object returned in the body of the response.
				console.log(response);
				for(i=0;i<response.data.length;i++)
				{
					cve = response.data[i];
					cve.component = '';
					var del = '';
					for(j=0;j<cve.products.length;j++)
					{
						var pid = cve.products[j];
						product=cve.product[pid];
						if(product.current==1)
						{
							var del = '';
							for (var cid in product.component)
							{
								component  = product.component[cid];
								cve.component += del+component.name+" "+component.version;
								del = ' / ';
							}
						}
						if((product.status.publish == 1)||(product.status.publish =='1'))
							product.status.publish = true;
						else
							product.status.publish = false;
					}

					if(cve.priority == 1) 
						cve._priority = 'Critical';
					else if(cve.priority == 2) 
						cve._priority = 'Major';
					else if(cve.priority == 3) 
						cve._priority = 'Minor';
					else 
						cve._priority = cve.priority;
					
					if(cve.invalid_cve == 1)
					{
						cve._priority = 'Rejected';
						//cve.basescore = '';
						cve.title = 'This CVE is triaged and marked invalid';
					}
					if(cve.status !== undefined)
					{
						if(cve.status.triage == "Not Applicable")
						{
							cve._priority = 'Rejected';
							cve.title = 'This CVE is triaged and marked invalid';
						}
						if( (cve.status.publish == 1)||(cve.status.publish == "1"))
							cve.status.publish=true;
						else
							cve.status.publish=false;
						
					}
				}
				if(response.last_page==-1)
					return response.data;
				return response; //return the tableData property of a response json object
			},
			cellClick:function(e, cell)
			{
				//e - the click event object
				//cell - cell component
				//cve.jira
				//var cve = cell.getRow().getData();
				
				if((cell.getField() == 'cve'))
				{
					ShowTriageView(cell);
					return;
				}
				if((cell.getField() == 'status.triage')||(cell.getField() == 'status.publish'))
				{
				}
				else
					ShowTriageView(cell);
				return;
				/*	if(cve.jir == '')
					{
						PopulateModal(cell.getRow().getData());
						gcell = cell;
						$('#modal').show();
					}
				}
				else
				{
					PopulateModal(cell.getRow().getData());
					gcell = cell;
					$('#modal').show();
				}*/
			},
			renderComplete:function()
			{
				vulnerability_table.redraw();
			},
			rowClick:function(e, row)
			{
				//e - the click event object
				//row - row component
				//PopulateModal(row.getData());
				//$('#modal').show();
			},
			rowFormatter:function(row)
			{
				var data = row.getData();
				if(data.invalid_cve == 1)
				{
					row.getElement().style.color = "#87898c"; //apply css change to row element
				}
				if(data.status !== undefined)
				{
					if( (data.status.triage == 'Not Applicable')||(data.status.triage == "Won't Fix"))
					{
						row.getElement().style.color = "#87898c"; //apply css change to row element
					}
				}
				/*else if(data.status.triage == 'Vulnerable')
				{
					row.getElement().style.color = "#ff0000"; //apply css change to row element
				}
				else if(data.status.triage == 'Fixed')
				{
					row.getElement().style.color = "#006400"; //apply css change to row element
				}*/
			},
		});
		vulnerability_table.setSort("_priority", "asc");
	}
	
	$('#closetmodal').on( "click", function() 
	{
		$('#tmodal').hide();
		
		
	});
	
	$(document).ready(function()
	{
		console.log("Vulnerability Page Loaded");
		window.disw.init();
	  
		$('#last_updated').append(last_updated);
		$('#save').click(function(){
			console.log('Save');
			UpdateStatus(gcell,$('#comment').val());
		});
		AddOption('select_group','All Products','all',0);
		AddOption('select_product','All Parts','all',0);
		AddOption('select_version','All Versions','all',0);
		for(i=0;i<group_names.length;i++)
		{
			AddOption('select_group',group_names[i],group_names[i],0);
		}
		selected_group = $('#select_group option:selected').val();
		selected_product = $('#select_product option:selected').val();
		selected_version = $('#select_version option:selected').val();
		
		url = '/cveportal/cve/'+selected_group+'/'+selected_product+'/'+selected_version+'/'+admin;
		CreateTable(url,Get3Columns(),'remote');
		
		if(svmsyncrequest == 1)
		{
			$('#svmsync').attr("src", "{{ asset('apps/cveportal/images/svmsyncrequested.jpg') }}");
			$('#svmsync').attr("title", "SVM Sync In Progress");
			setTimeout(CheckSVMSyncStatuc, 1000*30);
		}
		else
		{
			$('#svmsync').attr("src", "{{ asset('apps/cveportal/images/svmsynced.png') }}");
			$('#svmsync').attr("title", "Click to request SVM Sync");
		}
		
		if(publishrequest == 1)
		{
			$('#publish').attr("src", "{{ asset('apps/cveportal/images/publishing.gif') }}");
			$('#publish').attr("title", "Publishing for the external world ");
			setTimeout(CheckPublishStatus, 1000*30);
		}
		else
		{
			$('#publish').attr("src", "{{ asset('apps/cveportal/images/published.png') }}");
			$('#publish').attr("title", "Click to Publish Externally");
		}
		$('#publish').on('click',function(event)
		{
			var url = '{{route("cveportal.sync")}}'+"?staticpages=1&organization="+organization;
			$.ajax({
				type:"GET",
				url:url,
				cache: false,
				success: function(response)
				{
					$('#publish').attr("src", "{{ asset('apps/cveportal/images/publishing.gif') }}");
					$('#publish').attr("title", "Publishing for the external world");
					setTimeout(CheckPublishStatus, 1000*30);
				},
				error: function(response)
				{
					alert("Failed");
				}
			});
		});
		$('#svmsync').on('click',function(event)
		{
			var url = '{{route("cveportal.sync")}}'+"?svm=1&organization="+organization;
			$.ajax({
				type:"GET",
				url:url,
				cache: false,
				success: function(response)
				{
					$('#svmsync').attr("src", "{{ asset('apps/cveportal/images/svmsyncrequested.jpg') }}");
					$('#svmsync').attr("title", "SVM Sync Requested");
					setTimeout(CheckSVMSyncStatuc, 1000*30);
				},
				error: function(response)
				{
					alert("Failed");
				}
			});
		});
		$('#download').on('click',function(event)
		{
			vulnerability_table.download("xlsx", "cves.xlsx", {sheetName:"cve"});
			//setTimeout(DownloadXls, 300)
			event.preventDefault();
			
			return false;
			
		});
		setTimeout(function(){showuser()},1000);
		
	});
	function CheckPublishStatus()
	{
		var url = '{{route("cveportal.issyncrequested")}}'+"?staticpages=1&organization="+organization;
			$.ajax({
				type:"GET",
				url:url,
				cache: false,
				success: function(response)
				{
					if(response.data==0)
					{
						$('#publish').attr("src", "{{ asset('apps/cveportal/images/published.png') }}");
						$('#publish').attr("title", "Click to Publish Externally");
					}
					else
					{
						$('#publish').attr("src", "{{ asset('apps/cveportal/images/publishing.gif') }}");
						$('#publish').attr("title", "Publishing for the external world");
						setTimeout(CheckPublishStatus, 1000*30);
					}
				},
				error: function(response)
				{
					alert("Failed");
				}
			});
	}
	function CheckSVMSyncStatuc()
	{
		var url = '{{route("cveportal.issyncrequested")}}'+"?svm=1&organization="+organization;
			$.ajax({
				type:"GET",
				url:url,
				cache: false,
				success: function(response)
				{
					if(response.data==0)
					{
						$('#svmsync').attr("src", "{{ asset('apps/cveportal/images/svmsynced.png') }}");
						$('#svmsync').attr("title", "Click to request SVM Sync");
					}
					else
					{
						$('#svmsync').attr("src", "{{ asset('apps/cveportal/images/svmsyncrequested.jpg') }}");
						$('#svmsync').attr("title", "SVM Sync Requested");
						setTimeout(CheckSVMSyncStatuc, 1000*30);
					}
				},
				error: function(response)
				{
					alert("Failed");
				}
			});
	}
	function showuser()
	{
		if ($(".disw-header-addons")[0])
		{
			var html = '<figure style="margin:10px; text-align: center;">';
			var asset = "{{ asset('apps/cveportal/images/user-png-icon.png') }}";
			html += '<img title="You are logged in" width=20 src="'+asset+'"/>';
			asset = "{{ asset('apps/cveportal/images/sign-out-icon.png') }}";
			html += '<img id="logout" title="Logout" width=20 src="'+asset+'"></img>';
		
			html += '<figcaption style="font-size:10px;">'+admin+'</figcaption>';
			html += '</figure>';
		
			
			$('.disw-header-addons').append('<span>'+html+'</span>');
			$('#logout').on('click',function(event)
			{
				window.location.href = '{{route("cveportal.logout")}}';
				
			});
		} 
		else 
		{
			setTimeout(function(){showuser()},1000);
		}
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function TriageditCheck(cell)
	{
		var data = cell.getRow().getData();
		return !data.status.readonly;
    }
	
	function CreateTriageTable(url,data)
	{		
		columns = [
		{title:"", field:"error", sorter:"string",width:20,visible:false,
			formatter:function(cell, formatterParams, onRendered)
			{
				var data = cell.getRow().getData();
				if(data.status.source != 'manual')
					return '<i title="Triage Via Jira Only" class="fas fa-crosshairs"></i>';
				
				if(data.status.readonly)
					return '<i title="You donot have permissions to triage" class="fas fa-lock"></i>';
				
				if(data.status.current)
					return '<i class="fas fa-arrow-circle-right"></i>';			
			}
		},
		{title:"Product", field:"group", sorter:"string",width:250},
		{title:"Version", field:"_name", sorter:"string",width:200},
		{title:"Component", field:"_component", sorter:"string",width:150},
		{title:"Triage", field:"status.triage", editor:"select", width:100,editorParams:
			{
				"Investigate":"Investigate",
				"Vulnerable":"Vulnerable",
				"Won't Fix":"Won't Fix",
				"Fixed":"Fixed",
				"Not Applicable":"Not Applicable",
			},
			cellEdited:function(cell)
			{
				UpdateTriageStatus(cell);
			},
			editable:TriageditCheck
		},
		{title:"Publish", field:"status.publish", width:100,editor:"tick",
				cssClass:'editable',
				formatter:function(cell, formatterParams, onRendered)
				{	
					if(cell.getValue() == '1')
						return 'Published';
					else
						return '';
					return cell.getValue();
				},
				cellEdited:function(cell)
				{
					UpdateTriageStatus(cell);
				},
				editable:TriageditCheck
		},
		{title:"Comment", field:"status.comment", editor:"textarea",
				cellEdited:function(cell)
				{
					UpdateTriageStatus(cell);
				},
				editable:TriageditCheck
		
		},
		{title:"Triaged By", field:"status.user", sorter:"string",width:150},
		
		];
		data._products = [];
		for(var i=0;i<data.products.length;i++)
		{
			var pid=data.products[i];
			var product = data.product[pid];
			product._component = '';
			
			var del = '';
			for (var cid in product.component)
			{
				component  = product.component[cid];
				product._component += del+component.name+" "+component.version;
				del = ' / ';
				product._name = product.name+"  "+product.version;
			}		
			if((product.status.publish == "1")||(product.status.publish == 1))
				product.status.publish= true;
			else
				product.status.publish= false;
			data._products.push(product);
		}
		console.log(data);
		var triage_table = new Tabulator("#triage-table", {
			data:data._products,
			columns:columns,
			//autoColumns:true,
			layout:"fitColumns",
			 minHeight:90,
			 tooltips:true,
			/*ajaxURL:url,
			ajaxResponse:function(url, params, response)
			{
				console.log(response[0]);
				for(var i=0;i<response[0].product.length;i++)
				{
					var product=response[0].product[i];
					product._component = product.component[0].name+" "+product.component[0].version;
					product._name = product.name+"  "+product.version;
					
					if((product.status.publish == "1")||(product.status.publish == 1))
						product.status.publish= true;
					else
						product.status.publish= false;
					
					if( (product.status.readonly==1)||(product.status.readonly=="1"))
						product.status.readonly=1;
					else
						product.status.readonly=0;
				}
				return response[0].product;
			}*/
			rowFormatter:function(row)
			{
				//row - row component
				var data = row.getData();
				
				if(data.current)
					row.getElement().style.color = "green";
				
				if(data.status.readonly)
				{
					row.getElement().style.color = "#cacaca";
				}
			},
		});
	}
	function UpdateTriageStatus(cell)
	{
		data = cell.getRow().getData();
		d = {};
		
		if(data.status.publish)
			data.status.publish = "1";
		else
			data.status.publish = "0";
		
		data.status.user = admin;
		d.status = data.status;
		d.organization = organization;
		console.log(d.status);
		
		d._token = "{{ csrf_token() }}";
		$.ajax({
			type:"PUT",
			url:'{{route("cveportal.status.update")}}',
			cache: false,
			data:d,
			success: function(response)
			{
				var ds = cell.getRow().getData();
				ds.status = response
				cell.getRow().getElement().style.backgroundColor = "#8FBC8F";
				
				cell.getRow().update(ds);
				function colorrevert()
				{
					element = cell.getRow().getElement();
					if($(element).hasClass('tabulator-row-even'))
						element.style.backgroundColor = "#EFEFEF";
					else
						element.style.backgroundColor = "#ffffff";
					
					var tdata = triage_cell.getRow().getData();
					
					if(d.status.productid == tdata.status.productid)
					{
						tdata.status.publish = d.status.publish;
						tdata.status.triage = d.status.triage;
						tdata.status.comment = d.status.comment;
						triage_cell.getRow().update(tdata);
						console.log('Updated');
					}
				};
				setTimeout(colorrevert, 2000);
			},
			error: function(response)
			{
			}
		});
	}
	
	var triage_cell = null;
	function ShowTriageView(cell)
	{
		triage_cell = cell;
		var data = cell.getRow().getData();
			
		$('.cve_title').text(data.cve);
		$('.cve_description').text(data.description);
		var nurl = "https://svm.cert.siemens.com/portal/notifications/show/"+data.notification_id;
		var html = '<small style="font-size:10px;margin-top:0px"> <a href="'+nurl+'">Click here</a></small>';

		$('.cve_solution').html('Solution:'+' '+' '+html);
		
		$('.cvss_vector').text(data.vector);
		
		$('.cvss_attackvector').text(data.priority);
		
		
		$('.cvss_basescore').text(data.basescore);
		var published = new Date(data.publish_date);
		var published = published.toString().slice(4,15);
		$('.cve_published').text(published);
		
		$('.cvss_severity').text(data.severity);
		
		var modified = new Date(data.last_update);
		var modified = modified.toString().slice(4,15);
		$('.cve_modified').text(modified);
		
		$('.cve_number').text(data.cve);
		link = "https://cve.mitre.org/cgi-bin/cvename.cgi?name="+data.cve;
		$(".mitre_link").attr("href",link);
		link = "https://nvd.nist.gov/vuln/detail/"+data.cve;
		$(".nvd_link").attr("href",link);
		
		$(".nvd_link").attr("href",link);
		$("#notification_link").attr("href","https://svm.cert.siemens.com/portal/notifications/show/"+data.notification_id);
	
		
		
		var url = '{{route("cveportal.triage")}}/'+data.cve;
		//console.log(url);
		//console.log(data.product);
		CreateTriageTable(url,data);
		$('#tmodal').show();
	}
	
	</script>
</body>
</html>