<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Security - Mentor Graphics</title>

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
</style
</style>
</head>
<body>
	<div class="flex-container">
		<div class="row"> 
			<br>
			<div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#4682B4;color:white;" class="flex-item"> 
			<img style="float:left;" height="50px" src="{{ asset('apps/ishipment/images/mentor.png') }}"></img>
			<div style="margin-right:150px;"> Cryptography Analysis Dashoard <span style="color:orange"> {{$file_name}} </span></div>
			<div> ffff</div>
			</div>
			<div class="flex-item"> 
			<small class="flex-item" style="font-size:12px;"><a id="" href="#"></a></small>
			</div>
			<hr>
			
			<div class="flex-item"> 
			<div id="tabulator-table"></div>
			</div>
		</div>
	</div>
	<script src="{{ asset('apps/cryptography/js/progressbar.min.js') }}"></script>
	<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
	<script src="{{ asset('libs/tabulator/js/tabulator.min.js') }}" ></script>
	<script>
	var hits = @json($hits);
	console.log(hits);
	
	columns = 
	[
        {title:"Evidence", field:"evidence_type", sorter:"string"},
        {title:"Line", field:"line_number", sorter:"number"},
		{title:"Library", field:"encryption_library", sorter:"string"},
		{title:"Suspicios", field:"suspicios", sorter:"number"},
		{title:"Progress", field:"progress", width:120,formatter:"progress", 
			formatterParams:function(cell)
			{
				return {
					min:0,
					max:100,
					color:["green", "green", "green"],
					legendColor:"#000000",
					legendAlign:"center",
					legend:cell.getValue()+"%"
					}
			}
		}
	];
	$(document).ready(function()
	{
		console.log("Cryptography Page Loaded");
		var table = new Tabulator("#tabulator-table", {
		data:hits,
		columns:columns,
		pagination:"local",
		paginationSize:50,
		paginationSizeSelector: [10, 25, 50, 100],
		
		});
	});
	</script>
</body>
</html>