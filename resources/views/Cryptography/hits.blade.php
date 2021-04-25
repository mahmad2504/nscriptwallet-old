
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>File - OSS Governance</title>

<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator.min.css') }}" />


<style>
.progress 
{
    height: 20px;
}
.progress > svg 
{
	height: 100%;
	display: block;
}

.flex-container 
{
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
	width: 80%;
	
}
.flex-item {
	text-align: center;
}
.box {
      border:2px solid #4FFFA1;
      padding:10px;
      background:#F6FFA1;
      width:100px;
      border-radius:25px;
    }
	/* The Modal (background) */
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  padding-top: 100px; /* Location of the box */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

/* Modal Content */
.modal-content {
  background-color: #fefefe;
  margin: auto;
  padding: 20px;
  border: 1px solid #888;
  width: 80%;
}

/* The Close Button */
.close {
  color: #aaaaaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: #000;
  text-decoration: none;
  cursor: pointer;
}
table {width:50%;text-align:center } /* Make table wider */  
td, th { border: 1px solid #CCC; text-align:left} /* Add borders to cells */  
tr { font-size:12px } /* Add borders to cells */
</style
</head>
<body>
<!-- The Modal -->
	<div id="modal" class="modal" style="display:none">
	  <!-- Modal content -->
	  <div class="modal-content" style="width:60%;margin: auto;">
		<span id="closemodal" class="close">&times;</span>
		<h3 id="cve_title"></h3>
		<h4>File Data Content</h4>
			<div id="filedata"></div>
		<hr>
		<small style="font-size:10px;margin-top:0px;float:right"><a id="download">Download file</a></small>
	  </div>
	</div>
	<!-- **************************************************************************** -->
	<div class="flex-container">
		
		<div class="row"> 
			<br>
			<div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#00FF99;color:white;" class="flex-item"> 
			<img style="float:left;" height="50px" src="{{ asset('apps/ishipment/images/mentor.png') }}"></img>
			<div style="margin-right:150px;">OSS Governance [ File - <span style=""> <a id="download1">{{ $package }}{{$file_name}}</a>]</span></div>
			</div>
			<div class="flex-item"> 
			<small class="flex-item" style="font-size:12px;"><a id="" href="#"></a></small>
			</div>
			<hr>
			
			<div class="flex-item"> 
				<div id="tabulator-table"></div>
			</div>
			
		</div>
		<!-- **************************************************************************** -->
	</div>
	<script src="{{ asset('apps/cryptography/js/progressbar.min.js') }}"></script>
	<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	
	<script>
	var package = '{{ $package }}';
	var hits = @json($hits);
	var project = @json($project);
	var file_identifier = '{{ $file_identifier }}';
	
		
	columns = 
	[
		//{title:"ID", field:"id", sorter:"string"},
        {title:"Evidence", field:"evidence_type", sorter:"string"},
        {title:"Line", field:"line_number", sorter:"number"},
		{title:"Text", field:"line_text", sorter:"string",width:600},
		{title:"Triage", field:"triage.text",editor:"select", editorParams:{values:["","Valid", "Ignore"]},
			cellEdited:function(cell)
			{
				//cell - cell component
				var data = cell.getData();
				console.log("Row updated");
				console.log(data);
				var status = {};
				status.id = data.id;
				status.text = data.triage.text;
				status.comment = data.triage.comment;
				UpdateTriageStatus(cell,status);
			}
		},
		{title:"Comment", field:"triage.comment",editor:"input",
			cellEdited:function(cell)
			{
				//cell - cell component
				var data = cell.getData();
				console.log("Row updated");
				console.log(data);
				var status = {};
				status.id = data.id;
				status.text = data.triage.text;
				status.comment = data.triage.comment;
				UpdateTriageStatus(cell,status);
			}
		},
		//{title:"Suspicious", field:"triage.suspicious"},
		//{title:"Triaged", field:"triage.triaged"}
	];
	//https://drive.google.com/file/d/1Hor-YlP_eD0C_RqH1KjO6fi8FW7XKClx/view?usp=sharing
	
	function UpdateTriageStatus(cell,d)
	{
		cell.getRow().getElement().style.backgroundColor = "#8FBC8F";
		d._token = "{{ csrf_token() }}";
		$.ajax({
			type:"PUT",
			url:'{{route("oss.triage")}}',
			cache: false,
	
			data:d,
			success: function(response)
			{
				cell.getRow().getElement().style.backgroundColor = "#8FBC8F";
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
			error: function(response)
			{
				cell.restoreOldValue();
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
	$(document).ready(function()
	{
		console.log("Cryptography Page Loaded");
		var table = new Tabulator("#tabulator-table", {
		data:hits,
		columns:columns,
		pagination:"local",
		paginationSize:50,
		layout:"fitDataStretch",
		paginationSizeSelector: [10, 25, 50, 100],
		cellClick:function(e, cell)
		{
			var data = cell.getData();
			var field = cell.getField();
			console.log('clicked');
			if((field == 'evidence_type'))
			{
				var url = '/oss/search/'+project.name+"/"+package+"/"+data.id;
				window.open(url, '_blank');
				return;
			}
			if((field == 'triage.text')||(field == 'triage.suspicious')||(field == 'triage.triaged')||(field == 'triage.comment'))
				return;
			$('#modal').show();
			var line_number = data.line_number;
			$('#filedata').empty();
			if(data.line_text_before_3.length > 0)
				$('#filedata').append('<div>'+(line_number-3)+"  "+data.line_text_before_3+'</div>');
			$('#filedata').append('<div>'+(line_number-2)+"  "+data.line_text_before_2+'</div>');
			$('#filedata').append('<div>'+(line_number-1)+"  "+data.line_text_before_1+'</div>');
			$('#filedata').append('<div style="color:red;">'+(line_number)+"  "+data.line_text+'</div>');
			$('#filedata').append('<div>'+(line_number+1)+"  "+data.line_text_after_1+'</div>');
			$('#filedata').append('<div>'+(line_number+2)+"  "+data.line_text_after_2+'</div>');
			if(data.line_text_after_3.length > 0)
				$('#filedata').append('<div>'+(line_number+3)+"  "+data.line_text_after_3+'</div>');
		}
		});
		var url = '/oss/file/'+project.name+"/"+file_identifier;
		console.log(url);
		$('#download').attr("href", url);
		$('#download1').attr("href", url);
	});
	$('#closemodal').on( "click", function() 
	{
		$('#modal').hide();
	});
	</script>
</body>
</html>