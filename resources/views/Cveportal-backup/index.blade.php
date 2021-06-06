<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Security - Mentor Graphics</title>

<link type="text/css" rel="stylesheet" href="{{ asset('apps/cveportal/css/mgc_agg.css') }}" />
<link type="text/css" rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator.min.css') }}" />
<link type="text/css" rel="stylesheet" href="{{ asset('apps/cveportal/css/mgc-icons-legacy.css') }}" />
<link type="text/css" rel="stylesheet" href="{{ asset('apps/cveportal/css/cveportal.css') }}" />

<style>
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
	<header class="header-main bg-secondary flex-none" role="navigation">
		<div class="header-main-logo">
			<a class="logo-mentor m-t-xs" href="https://mentor.com"><span class="sr-only">Mentor, A Siemens Business</span></a>
		</div>
		<nav class="header-main-navigation navbar-primary" id="primary">
			<ul>
				<a href="https://mentor.com/products/" title="Products">
					<i class="menu-icon menu-icon-animated position-relative m-r-sm" style="top: -3px;"><span></span></i>
						Products & Solutions
				</a>
				<li class="training"><a href="https://mentor.com/training/" title="Training and Services">Training</a></li>
				<li class="services"><a href="https://mentor.com/training-and-services/" title="Training and Services">Services</a></li>
				<li class="company"><a href="/company/" title="Company">Company</a></li>
				<li class="blogs"><a href="https://mentor.com/blogs/" title="Blogs">Blogs</a></li>
				<li class="support"><a href="https://mentor.com/support/" title="Support">Support</a></li>
			</ul>
		</nav>
	</header>
	<!-- **************************************************************************** -->
	<header id="header-secondary" class="bg-secondary-darker p-y" role="banner">
		<div class="container">
			<div class="row row-flex middle-lg">
				<div class="col-md-9 col-xs-12">
					<div id="section">
						<a href="#" title="Security Vulnerabilities">Security Vulnerability Database</a>
					</div>
				</div>
			</div>
		</div>
	</header>
	<!-- **************************************************************************** -->
	<div id="content" class="flex-content">
		<div class="bg-white border-bottom text-webfont-one m-b-md">
			<div class="container position-relative">
				<nav id="bread" class="breadcrumb item-flex-main">
					<ol>
					<!-- BREADCRUMB -->
						<li class="breadcrumb1 first">
							<a href="https://www.mentor.com/">
								<svg class="icon icon-home2" aria-hidden="true"><use xlink:href="#icon-home2"></use></svg>
								<span class="sr-only">
									<span>Home</span>
								</span>
							</a>
						</li>
						<li class="breadcrumb2 first">
							<a href="https://www.mentor.com/embedded-software/">
								<span>Embedded Software</span>
							</a>
						</li>
						<li class="breadcrumb3 first">
							<a href="#">
								<span>Security Vulnerabilities</span>
							</a>
						</li>
					</ol>
				</nav>
			</div>
		</div>
		<!-- **************************************************************************** -->
		<div  id="copy" style="width:92%; margin: auto;" class="container1">
			<br>
			<div class="row row-flex" style="width:100%;!important">
				<div id="sidebar" class="col-md-2 col-xs-15 last-xs first-md content-sidebar">
					<h2 class="hidden-lg-up header-group"><span><a href="#" title="Security Vulnerabilities">Security Vulnerabilities</a></span></h2>
					<div class="card callout callout-callout">		
						<div class="card-header">
							<h2 class="text-uc text-gray-dark m-b-0">Products</h2>
						</div>
						@for ($i = 0; $i < count($group_names); $i++)
						<li class="list-group-item list-group-item-nav p-a-0">
							<a  class="productbutton" data-index="{{$i}}"  title="{{$group_names[$i]}}">{{$group_names[$i]}}</a>
						</li>
						@endfor
					</div>
					<div class="card callout callout-callout">
						<div class="card-header">
							<h2 class="text-uc text-gray-dark m-b-0">Contact Embedded</h2>
						</div>
						<div class="card-block">
							<ul class="list-unstyled">
							<li class="list-icon text-sm">
								<svg class="icon icon-envelope" aria-hidden="true"><use xlink:href="#icon-envelope"></use></svg>
								<a href="/embedded-software/iot/security/contactme" rel="600||500" class="mgclightbox lb_iframe position-static"><b>Email Us</b></a>
							</li>
							<li class="list-icon text-sm mgc-chat-available hide">
								<svg class="icon icon-bubbles2" aria-hidden="true"><use xlink:href="#icon-bubbles2"></use></svg>
								<a href="/embedded-software/iot/security" class="mgc-chat-btn" rel="600||500 nofollow" data-context="%2Fembedded%2Dsoftware%2Fiot%2Fsecurity"><b>Chat Online</b></a>
							</li>
							<li class="list-icon text-sm mgc-chat-unavailable hide">
								<svg class="icon icon-bubbles2" aria-hidden="true"><use xlink:href="#icon-bubbles2"></use></svg>
								<b>Chat Online</b><br /><span class="text-xs">No agents are available</span>
							</li>
							<li class="list-icon text-sm">
								<svg class="icon icon-phone" aria-hidden="true"><use xlink:href="#icon-phone"></use></svg>
								<strong class="text-xs">1-800-547-3000</strong>
								<br><span class="text-xs">Direct: (503) 685-8000</span></li>
						</ul>
						</div>
					</div>
				</div>
				<div class="col-md-10 col-xs-12 first-xs last-md content-main">
					<div class="card">
					<div class="card-header card-header-secondary  card-page-title">
						<span style="color:white;font-size:20px;" id="title">Vulnerabilities</span>
						<select  id="select_group" style="margin-left:50px;float:none;">
						</select>
						<select  id="select_product" style="margin-left:10px;float:none;">
						</select>
						<select  id="select_version" style="margin-left:10px;float:none;">
						</select>
						<a id="download" style="color:white;float:right;display:none" href="">Download</a>
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
		<!--pageElement:FOOTER-->
		<footer class="footer-site flex-none">
			<div class="container">
			<div class="row-flex middle-md">
				<div class="col-md-8 col-xs-12 text-center text-lg-left">
					<p class="text-xs">
						<a href="/sitemap">Site Map</a> <span class="text-pipe">|</span>
						<a href="/company/news/">News & Press</a> <span class="text-pipe">|</span>
						<a href="/company/careers/">Careers</a> <span class="text-pipe">|</span>
						<a href="/consulting/">Consulting</a> <span class="text-pipe">|</span>
						<a href="/company/partner_programs/">Partners/Foundry Support</a> <span class="text-pipe">|</span>
						<a href="/company/international">International Websites</a>
					</p>
					<p class="text-xs">
						<a class="text-muted" href="/terms_conditions">Terms</a>  <span class="text-pipe">|</span>
						<a class="text-muted" href="/terms_conditions/privacy">Privacy Policy</a>
					</p>
					<p class="text-xs m-b-md m-lg-b-0">&copy; Mentor, a Siemens Business, All rights reserved</p>
				</div>
				<div class="col-md-4 col-xs-16 text-center text-lg-right text-xs">
					<div class="m-b" id="footer-site-social-links">
						<h3 class="text-uc m-b-sm">Follow Mentor</h3>
						<a href="http://www.linkedin.com/company/mentor_graphics" class="circle circle-md bg-opaque-light m-x-xs"><svg class="icon icon-linkedin2"><use xlink:href="#icon-linkedin2"></use></svg></a>
						<a href="https://twitter.com/mentor_graphics" class="circle circle-md bg-opaque-light m-x-xs"><svg class="icon icon-twitter"><use xlink:href="#icon-twitter"></use></svg></a>
						<a href="https://www.facebook.com/pages/Mentor-Graphics/362609027104610" class="circle circle-md bg-opaque-light m-x-xs"><svg class="icon icon-facebook"><use xlink:href="#icon-facebook"></use></svg></a>
						<a href="http://www.youtube.com/channel/UC6glMEaanKWD86NEjwbtgfg" class="circle circle-md bg-opaque-light m-x-xs"><svg class="icon icon-youtube"><use xlink:href="#icon-youtube"></use></svg></a>
						<a href="https://plus.google.com/b/102197424811444669688/102197424811444669688/posts" class="circle circle-md bg-opaque-light m-x-xs"><svg class="icon icon-google-plus"><use xlink:href="#icon-google-plus"></use></svg></a>
					</div>
					<p class="m-b-0"><a class="text-white p-r-sm text-emphasis text-no-underline" href="tel:+18005473000">1-800-547-3000</a> <a href="/company/contact_us" class="btn btn-sm btn-info">Contact Mentor</a></p>
				</div>
			</div>
		</div>
		</footer>
	</div>
	<script src="https://cdn.jsdelivr.net/bluebird/3.5.0/bluebird.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fetch/2.0.3/fetch.js"></script>
	<script src="https://cdn.polyfill.io/v2/polyfill.min.js"></script>
	<script src="{{ asset('apps/cveportal/js/svg.js') }}"></script>
	<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
	<script src="{{ asset('libs/sheetjs/xlsx.full.min.js')}}" ></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	<script>
	var group_names = @json($group_names);
	var product_names = @json($product_names);
	var version_names = @json($version_names);
	var jira_url = '{{$jira_url}}';
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
			
			//{title:"Jira", field:"jira", sorter:"string", width:100,
			//	formatter:function(cell, formatterParams, onRendered)
			//	{	
			//		if(cell.getValue() != '')
			//			return '<a href="'+jira_url+'/browse/'+cell.getValue()+'">'+cell.getValue()+'</a>';
			//		return '';
			//	}
			//}
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
					cve = response.data[i];
					var selected_productid = cve.status.productid;
					cve.component = '';
					var del = '';
					for(j=0;j<cve.product.length;j++)
					{
						product=cve.product[j];
						for(k=0;k<product.component.length;k++)
						{
							component  = product.component[k];
							if(selected_productid == product.id)
							{
								cve.component += del+component.name+" "+component.version;
								del = ' / ';
							}
							//cve.component = component.name+" "+component.version;
						}
						if(product.status.publish !== undefined)
						{
							if((product.status.publish == 1)||(product.status.publish =='1'))
								product.status.publish = 1;
							else
								product.status.publish = 0;
						}
					}
					if(cve.status.source !== undefined)
					{
						cve.jira = '';
						if(cve.status.source != 'manual')
						{
							cve.jira = cve.status.source;
						}
					}
					if(cve.status.publish !== undefined)
					{
						if( (cve.status.publish == 1)||(cve.status.publish == "1"))
							cve.status.publish='Yes';
						else
							cve.status.publish='No';
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
					if(cve.status.triage == "Not Applicable")
					{
						cve._priority = 'Rejected';
						//cve.basescore = '';
						cve.title = 'This CVE is triaged and marked invalid';
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
				//console.log(cell.getField() );
				if(cell.getField() == 'jira')
				{
					// Do default click and list functions;
				}
				else
				{
					PopulateModal(cell.getRow().getData());
					$('#modal').show();
				}
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
		table.setSort("_priority", "asc");
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
		
		for(i=0;i<data.product.length;i++)
		{
			html+='<tr>';
			product = data.product[i];
			html += '<td>'+product.group+'</td><td>'+product.name+"</td><td>"+product.version+'</td>';
			html += '<td>';
			for(j=0;j<product.component.length;j++)
			{
				component=product.component[j];
				html += component.name+component.version+' ';
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
		
	});
	</script>
</body>
</html>