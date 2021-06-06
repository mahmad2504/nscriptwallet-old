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
</style>

</head>

<body>	
	<!-- The Modal -->
	<div id="modal" class="modal">
	  <!-- Modal content -->
	  <div class="modal-content" style="width:60%;margin: auto;">
		<span id="closemodal" class="close">&times;</span>
		<h3 id="cve_title"></h3>
		<h4>Description</h4>
		<p id="cve_description"></p>
		<p id="cve_solution"></p>
		<div  class="card card-block" style="margin-bottom:0px;">
			<div>
				<small style="float:left;margin-top:-10px;"><span style="font-weight:bold;">Vector: </span><span id="cvss_vector"></span></small>
				<small style="float:right;margin-top:-10px;"><span style="font-weight:bold;">SVM Priority: </span><small id="cvss_attackvector"></small></small>
			</div>
			<br>
			<div>
				<small style="float:left;margin-top:-10px;"><span style="font-weight:bold;">Score: </span><span id="cvss_basescore"></span></small>
				<small style="float:right;margin-top:-10px;"><span style="font-weight:bold;">CVSS Severity: </span><small id="cvss_severity"></small></small>
			</div>
			<br>
			<div>
				<small style="float:left;margin-top:-10px;"><span style="font-weight:bold;">Published: </span><span id="cve_published"></span></small>
				<small style="float:right;margin-top:-10px;"><span style="font-weight:bold;">Modified: </span><small id="cve_modified"></small></small>
			</div>
		</div>
		<h4 style="margin-top:5px;">Products Affected</h4>
		<div id="package_table"></div>
		<hr>
		<small style="font-size:10px;margin-top:0px;float:left"><a id="notification_link">SVM Notification</a></small>
		<small style="font-size:10px;margin-top:0px;float:right">Find out more about <span style="font-weight:bold;" id="cve_number"></span> from the <a id="mitre_link">MITRE-CVE</a> dictionary and <a id="nvd_link">NIST NVD</a></small>
	  </div>
	</div>
	<!-- End The Modal -->
	<!-- **************************************************************************** -->
	
	<disw-header-v2 account="true" scroll="true"></disw-header-v2>	
	<BR>
	<BR>
	<BR>
	<!-- ***END Siemens header ****************************************************** -->
	
	<header id="header-secondary" class="bg-secondary-darker p-y" role="banner">
		<div class="container">
			<div class="">
				<a style="color:white;font-size:25px;font-weight:bold" href="#" title="Security Vulnerabilities">Security Vulnerabilities</a>
				<select  title="Select Product Group" id="select_group" style="margin-left:150px">
				</select>
				<select  id="select_product" style="margin-left:10px;">
				</select>
				<select   id="select_version" style="margin-left:10px;">
				</select>
				<img   width=30 title="download in xlsx format" id="download" style="margin-top:10px;margin-right:50px;display:none;float:right" src="{{ asset('apps/cveportal/images/xlsx_download.png') }}"></img>
			</div>
		</div>
	</header>
	

	
	<!-- **************************************************************************** -->
	<div id="content" class="flex-content">
		<div  id="copy" style="width:92%; margin: auto;" class="container1">
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
	
	<script src="https://static.sw.cdn.siemens.com/disw/disw-utils/1.x/disw-utils.min.js"></script>
	<script src="https://cdn.jsdelivr.net/bluebird/3.5.0/bluebird.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fetch/2.0.3/fetch.js"></script>
	<script src="{{ asset('apps/cveportal/js/svg.js') }}"></script>
	<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
	<script src="{{ asset('libs/sheetjs/xlsx.full.min.js')}}" ></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	
	<script type="module" src="https://static.sw.cdn.siemens.com/disw/universal-components/1.x/esm/index.module.js">
	</script>
	<script type="module">
		window.universalComponents.init(['disw-header-v2', 'disw-footer']);
	</script>
	<script>
		
		var group_names = @json($group_names);
		var product_names = @json($product_names);
		var version_names = @json($version_names);
		var last_updated = '{{$last_updated}}';
		var organization = '{{$organization}}';
		function Get3Columns()
		{
			columns = [
			{title:"CVE", field:"cve", sorter:"string", width:130},
			{title:"Description", field:"title", sorter:"string", width:720},
			{title:"Priority", field:"_priority", sorter:"string", width:100},
			{title:"CVSS", field:"basescore", width:100}
			//{title:"Updated", field:"last_update", sorter:"string", width:100}
			];
			return columns;
		}
		function Get4Columns()
		{
			columns = [
				{title:"#", field:"index", formatter:"rownum"},
				{title:"CVE", field:"cve", sorter:"string", width:130,headerFilter:true
				
				},
				{title:"Description", field:"title", sorter:"string", width:480,headerFilter:true,},
				{title:"Package", field:"component", sorter:"string", width:130,headerFilter:true,},
				{title:"Priority", field:"_priority", sorter:"string", width:90,headerFilter:true},
				{title:"CVSS", field:"basescore", sorter:"number", width:40,headerFilter:true},
				{title:"Status", field:"status.triage", sorter:"string", width:100,headerFilter:true},
				{title:"Published", field:"status.publish", width:100, headerFilter:true}
			];
			return columns;
		}
		function AddOption(id,optionText,optionValue,selected) 
		{ 
			if(!selected)
				$('#'+id).append('<option value="'+optionValue+'">'+ optionText +"</option>"); 
			else							
				$('#'+id).append('<option value="'+optionValue+'" selected>'+ optionText +"</option>");							
		} 
		$("#select_group").on("change", function()
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
			
			url = '/cveportal/cve/'+selected_group+'/'+selected_product+'/'+selected_version;
			if(selected_version == 'all')
				columns = Get3Columns()
			else
				columns = Get4Columns()
			console.log(url);
			CreateTable(url,columns);
		}
		var table =null;
		function CreateTable(url,columns)
		{
			url = url + "/all/"+organization;
			var pagination="local";
			if(columns.length == 4)
			{
				pagination="remote";
				headerSort=false;
				$('#download').hide();
			}
			else
			{
				pagination="local";
				headerSort=true;
				$('#download').show();
			}
			headerSort=true;
			table = new Tabulator("#vulnerability-table", {
				columns:columns,
				pagination:pagination,
				paginationSize:25,
				headerFilterPlaceholder:"", 
				paginationSizeSelector: [10, 25, 50, 100],
				tooltipsHeader:true,
				//autoColumns:true,
				//ajaxParams:{token:"ABC123"},
				selectable:1,
				headerSort:headerSort,
				tooltips:true,
				ajaxURL:url,
				ajaxResponse:function(url, params, response)
				{
					//url - the URL of the request
					//params - the parameters passed with the request
					//response - the JSON object returned in the body of the response.
					//response = response.data;
					console.log(response);
					for(i=0;i<response.data.length;i++)
					{
						var cve = response.data[i];
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
							if((product.status.publish == "1")||(product.status.publish == 1))
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
								cve.status.publish='Yes';
							else
								cve.status.publish='No';
							
						}
					}
					if(response.last_page==-1)
						return response.data;
					return response; //return the tableData property of a response json object
				},
				cellClick:function(e, cell)
				{
					PopulateModal(cell.getRow().getData());
					$('#modal').show();
				},
				rowFormatter:function(row)
				{
					//row - row component
					var data = row.getData();
					
					if(data.invalid_cve == 1)
					{
						row.getElement().style.color = "#87898c"; //apply css change to row element
					}
				},
				renderComplete:function()
				{
					table.redraw();
				}
			});
			//table.setHeaderFilterValue("status.publish", "Yes");
			table.setFilter("status.triage", "!=", "Not Applicable");
			table.setSort([
			{column:"basescore", dir:"dsc"},
			{column:"_priority", dir:"asc"}
			]
			);
		}
		function PopulateModal(data)
		{
			console.log(data);
			
			$('#cve_title').text(data.cve);
			$('#cve_description').text(data.title);
			$('#cve_solution').text("Solution : "+data.solution);
			var published = new Date(data.publish_date);
			var published = published.toString().slice(4,15);
			$('#cve_published').text(published);
			
			var modified = new Date(data.last_update);
			var modified = modified.toString().slice(4,15);
			$('#cve_modified').text(modified);
			
			$('#cvss_vector').text(data.vector);
			$('#cvss_basescore').text(data.basescore);
			
			//if(data.cvss.accessVector !== undefined)
			$('#cvss_attackvector').text(data.priority);
			
			//if(data.cvss.attackVector !== undefined)
			//	$('#cvss_attackvector').text(data.vector);
			
			//if(data.cvss.baseSeverity !== undefined)
			$('#cvss_severity').text(data.severity);
			
			$('#cve_number').text(data.cve);
			link = "https://cve.mitre.org/cgi-bin/cvename.cgi?name="+data.cve;
			$("#mitre_link").attr("href",link);
			link = "https://nvd.nist.gov/vuln/detail/"+data.cve;
			$("#nvd_link").attr("href",link);
			$("#notification_link").attr("href","https://svm.cert.siemens.com/portal/notifications/show/"+data.notification_id);
			html='<table style="table-layout: auto; width: 100%;">';
			html+='<colgroup>';
			html+='<col span="1" style="width: 25%;">';
			html+='<col span="1" style="width: 25%;">';
			html+='<col span="1" style="width: 5%;">';
			html+='<col span="1" style="width: 25%;">';
			html+='<col span="1" style="width: 15%;">';
			html+='<col span="1" style="width: 5%;">';
			html+='</colgroup>';
		
			html+='<tr>';
			html+='<th>Product</th>';
			html+='<th>Part</th>';
			html+='<th>Version</th>';
			html+='<th>Package</th>';
			html+='<th>Status</th>';
			html+='<th>Published</th>';
			html+='</tr>';
			
			for(i=0;i<data.products.length;i++)
			{
				var pid = data.products[i];
				
				product = data.product[pid];
				if(product.current == 1)
					html+='<tr style="color:green">';
				else
					html+='<tr>';
				html += '<td>'+product.group+'</td><td>'+product.name+"</td><td>"+product.version+'</td>';
				html += '<td>';
				var del = '';
				for (var component in product.component) 
				{
					var comp = product.component[component];
					html += del+comp.name+" "+comp.version;
					del = '/';
				}
				html += '</td>';
				html += '<td>'+product.status.triage+'</td>';
				
				if(product.status.publish)
					html += '<td>Yes</td>';
				else
					html += '<td>No</td>';
				html +='</tr>';
			}
			html +='</table>';
			$('#package_table').empty();
			$('#package_table').append(html);

		}
		$('#closemodal').on( "click", function() 
		{
			$('#modal').hide();
		});
		$(document).ready(function()
		{
			console.log("Vulnerability Page Loaded");
			window.disw.init();
			$('#last_updated').append(last_updated);
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
			
			url = '/cveportal/cve/'+selected_group+'/'+selected_product+'/'+selected_version;
			CreateTable(url,Get3Columns());
				
			$('#download').on('click',function(event)
			{
				table.download("xlsx", "cves.xlsx", {sheetName:"cve"});
				event.preventDefault();
				return false;
			});
			setTimeout(function(){showuser()},1000);
		});
		function showuser()
		{
			if ($(".disw-header-addons")[0])
			{
				var html = '<button id="triage" style="margin-right:100px">Triage</button>';
				$('.disw-header-addons').append(html);
				$('#triage').on('click',function(event)
				{
					window.location.href = '{{route("cveportal.triage")}}';
					
				});
			} 
			else 
			{
				setTimeout(function(){showuser()},1000);
			}
		}
	</script>
</body>
</html>