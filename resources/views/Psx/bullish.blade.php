<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bullish Stocks</title>
		<link rel="icon" type="image/png" href="{{ asset('apps/bspestimate/appicon.png') }}"/>
		<link rel="stylesheet" href="{{ asset('libs/fontawesome/fontawesome.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/fontawesome/all.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/attention/attention.css') }}" />
		
		<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator_site.css') }}" />
		
		<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator_midnight.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator_modern.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/tabulator/css/tabulator_simple.css') }}" />
>
		
		
	<style>
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
		#container {
			height: 600px;
			border: 1px solid black;
		}
    </style>
    </head>
    <body>
		<div class="flex-container">
		<div class="row"> 
		    <div style="font-weight:bold;font-size:20px;line-height: 50px;height:50px;background-color:#4682B4;color:white;" class="flex-item"> 
			 <img style="float:left;" height="50px" src="{{ asset('libs/mentor/images/logo.jpg') }}"></img>
			 <div style="margin-right:150px;"> Bullish Stocks </div>
			</div>
			<div id="container" class="flex-item">
				<div id="sparkline"></div>
				<div style="box-shadow: 3px 3px #888888;" id="table"></div>
			</div>
		</div>
	</div>
	
    </body>
	<script src="https://code.jquery.com/jquery-3.2.1.min.js" ></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>

  <script src="https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/fetch/2.0.4/fetch.min.js"></script>


	<script src="http://tabulator.info/js/tabulator/4.9/tabulator.min.js" ></script>
	<script src="{{ asset('libs/attention/attention.js') }}" ></script>
	<script src="http://tabulator.info/js/sparkline.js" ></script>
	<script>
	
	//Formatter to generate line chart
	var vollineFormatter =  function(cell, formatterParams, onRendered)
	{
		onRendered(function()
		{ //instantiate sparkline after the cell element has been aded to the DOM
			//$(cell.getElement()).sparkline(cell.getValue(), {width:"100%", type:"line", disableTooltips:true});
			//console.log(cell.getValue());
			var data_row = cell.getRow().getData();
			//var color='red';
			//if(data_row.macd < 0)
			//	var color='green';
		
			if(parseInt(data_row.vol) > parseInt(data_row.prev_vol))
				$(cell.getElement()).css({"background-color":"#F0FFF0"});
			else 
				$(cell.getElement()).css({"background-color":"#FFF0F5"});
			
			
			$(cell.getElement()).sparkline(cell.getValue(), {
            type: 'bar',
			barColor :'green',
            barWidth: 6,
            barSpacing: 2,
            //barColor: color,
            nullColor: '#3366cc '});
			
		});
	};
	var lineFormatter = function(cell, formatterParams, onRendered)
	{
		onRendered(function()
		{ //instantiate sparkline after the cell element has been aded to the DOM
			//$(cell.getElement()).sparkline(cell.getValue(), {width:"100%", type:"line", disableTooltips:true});
			//console.log(cell.getValue());
			var data_row = cell.getRow().getData();
			//var color='red';
			//if(data_row.macd < 0)
			//	var color='green';
		
			if(data_row.macd < 0)
				$(cell.getElement()).css({"background-color":"#FFF0F5"});
			else
				$(cell.getElement()).css({"background-color":"#F0FFF0"});
			
			$(cell.getElement()).sparkline(cell.getValue(), {
            type: 'bar',
			barColor :'green',
            barWidth: 6,
            barSpacing: 2,
            //barColor: color,
            nullColor: '#3366cc '});
		});
	};
	var pricelineFormatter = function(cell, formatterParams, onRendered)
	{
		onRendered(function()
		{   var data_row = cell.getRow().getData();
			var close = data_row.close;
			var prev_close = data_row.prev_close;
			if( parseInt(close) > parseInt(prev_close))
				$(cell.getElement()).css({"background-color":"#F0FFF0"});
			else
				$(cell.getElement()).css({"background-color":"#FFF0F5"});
			$(cell.getElement()).sparkline(cell.getValue(), {
            type: 'bar',
			barColor :'green',
            barWidth: 6,
            barSpacing: 2,
            //barColor: color,
            nullColor: '#3366cc '});
		});
	};
	//generate box plot
	
	var boxFormatter = function(cell, formatterParams, onRendered){
		onRendered(function(){ //instantiate sparkline after the cell element has been aded to the DOM
			var data_row = cell.getRow().getData();
			var change = data_row.change;
			var color ='red';
			if(change >= 0)
				color ='green';
			$(cell.getElement()).sparkline(cell.getValue(), {raw:true,outlierLineColor:"black", outlierFillColor:"black",medianColor:color, whiskerColor:"black",boxFillColor:color,showOutliers:false,width:"100%", type:"box", disableTooltips:true});
		});
	};
	
	var data = @json($data);
	
	$(document).ready(function()
	{
		var table = new Tabulator("#table", {
		layout:"fitDataFill",
		data:data,
		maxHeight:"100%",
		columns:[
			//{title:"Date", field:"date"},
			{title:"Symbol", field:"symbol"},
			{title:"Close", field:"close", formatter:
				function(cell, formatterParams, onRendered)
				{
					var value = cell.getValue();
					var data = cell.getRow().getData();
					if( (parseInt(value) > parseInt(data.ema10)) && (parseInt(value) > parseInt(data.ema25) ))
					{
						$(cell.getElement()).css({"font-weight":"Bold"});
						$(cell.getElement()).css({"color":"Green"});
					}
					else if( (parseInt(value) < parseInt(data.ema10)) && (parseInt(value) > parseInt(data.ema25) ))
					{
						$(cell.getElement()).css({"font-weight":"Bold"});
						$(cell.getElement()).css({"color":"Orange"});
					}
					else
						$(cell.getElement()).css({"color":"red"});
					return value;
				}
			},
			{title:"EMA10", field:"ema10", formatter:
				function(cell, formatterParams, onRendered)
				{
					var value = cell.getValue();
					var data = cell.getRow().getData();
					if(data.ema10 > data.ema25)
					{
						$(cell.getElement()).css({"font-weight":"Bold"});
						$(cell.getElement()).css({"color":"Green"});
					}
					else
						$(cell.getElement()).css({"color":"red"});
					return value;
				}
			},
			{title:"EMA25", field:"ema25", formatter:
				function(cell, formatterParams, onRendered)
				{
					var value = cell.getValue();
					var data = cell.getRow().getData();
					if( data.ema10 > data.ema25)
					{
						$(cell.getElement()).css({"color":"Green"});
					}
					else
					{
						$(cell.getElement()).css({"font-weight":"Bold"});
						$(cell.getElement()).css({"color":"red"});
					}
					return value;
				}
			},
			{title:"RSI", field:"rsi", formatter:
				function(cell, formatterParams, onRendered)
				{
					var value = cell.getValue();
					if(value < 40)
						$(cell.getElement()).css({"color":"Green"});
					else
						$(cell.getElement()).css({"color":"orange"});
					return value;
				}
			},
			/*{title:"Volume", field:"vol",formatter:
				function(cell, formatterParams, onRendered)
				{
					var value = cell.getValue();
					var data = cell.getRow().getData();
					$(cell.getElement()).css({"font-weight":"Bold"});
					if(parseInt(data.vol) > parseInt(data.prev_vol))
						$(cell.getElement()).css({"color":"Green"});
					else 
						$(cell.getElement()).css({"color":"Red"});
					
					return value;
				}
			},*/
			{title:"Change", field:"change",formatter:
				function(cell, formatterParams, onRendered)
				{
					var value = cell.getValue();
					$(cell.getElement()).css({"font-weight":"Bold"});
					if(value > 0)
						$(cell.getElement()).css({"color":"Green"});
					else
						$(cell.getElement()).css({"color":"Red"});
					return value;
				}
			},
			
			{title:"Volumes", field:"vol_graph",  formatter:vollineFormatter, sorter:
				function(a, b, aRow, bRow, column, dir, sorterParams)
				{
					return aRow.getData().vol - aRow.getData().prev_vol;
					//return a - b; //you must return the difference between the two values
				},
			},
			{title:"EMA didd", field:"ema_graph", formatter:lineFormatter, sorter:
				function(a, b, aRow, bRow, column, dir, sorterParams)
				{
					return aRow.getData().macd - bRow.getData().macd;
					//return a - b; //you must return the difference between the two values
				},
			},
			
			{title:"Macd", field:"macd_graph", formatter:lineFormatter, sorter:
				function(a, b, aRow, bRow, column, dir, sorterParams)
				{
					return aRow.getData().macd - bRow.getData().macd;
					//return a - b; //you must return the difference between the two values
				},
			},
			{title:"Open", field:"open_graph", formatter:pricelineFormatter, sorter:
				function(a, b, aRow, bRow, column, dir, sorterParams)
				{
					return aRow.getData().macd - bRow.getData().macd;
					//return a - b; //you must return the difference between the two values
				},
			},
			{title:"High", field:"high_graph", formatter:pricelineFormatter},
			{title:"Low", field:"low_graph", formatter:pricelineFormatter},
			{title:"Price", field:"price_graph", formatter:pricelineFormatter, sorter:
				function(a, b, aRow, bRow, column, dir, sorterParams)
				{
					return aRow.getData().close - bRow.getData().prev_close;
					//return a - b; //you must return the difference between the two values
				},
			},
			{title:"Candle", field:"box", width:100, formatter:boxFormatter},
			//{title:"Open", field:"open"},
			//{title:"Close", field:"close"},
			//{title:"Low", field:"low"},
			//{title:"High", field:"high"},
		]}
		);
	});
	
	</script>
</html>
