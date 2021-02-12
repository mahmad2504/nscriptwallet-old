    var data = localStorage.getItem('drivers');
	if(data != null)
		drivers = JSON.parse(data);
	
	var driver_table = new Tabulator("#driver_estimates", {
		//height:"311px",
		layout:"fitDataFill",
		data:drivers,
		dataTree:true,
		dataTreeBranchElement:false,
		dataTreeStartExpanded:[true, false],
		columns:[
		{title:"Title", field:"title", width:400, responsive:0,
			formatter:function(cell, formatterParams, onRendered)	
			{
				data  = cell.getRow().getData();
				if(data.type =='parent')
					return cell.getValue();
				return '<i class="fas fa-cog"></i>&nbsp&nbsp&nbsp'+cell.getValue();
				//data  = cell.getRow().getData();
			}
		}, 
		{title:"ID", field:"id", width:50, visible :false}, 
		{title:"Class", field:"class",width:100,visible :false},
		{title:"Type", field:"type",width:100,visible :false},
		{title:"Parent", field:"parent",width:100,visible :false},
		{title:"Catalog", field:"selected_option", editor:"select", width:300,editorParams:
		    function(cell)
			{
			   return {values:cell.getData().options
			};
		}},
		{title:"Dev Estimates", field:"dev_estimate", width:150, responsive:2,  editor:"input",
		    editable: function(cell) 
			{ 
			    return false; 
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
		{title:"QA Estimates", field:"qa_estimate", width:150,  
			editable: function(cell) 
			{ 
				return false; 
				if(cell.getRow().getData().team == 'dev')
					return false; 
				
				if(cell.getRow().getData().readonly)
					return false; 
				else
					return true;
			},
			editor:"autocomplete", editorParams:{
			values:{
				"Steve Boberson":"Steve Boberson",
				"bob":"Bob Jimmerson",
				"jim":"Jim Stevenson",
			}
			}
		}
		],
		cellEdited:function(cell)
		{
			var data = cell.getRow().getData();
			UpdateEstimates(data);
		},
		cellClick: function (e, cell) {
			var field = cell.getField();
			var data = cell.getRow().getData();
			if((field == 'title')&&(data.type !='parent')&&(data.selected_option != '')&&(data.selected_option != 'None'))
			{
				ShowModal(cell.getRow().getData());
			}
			else if((field == 'title')&&(data.type !='parent')&&((data.selected_option == '')||(data.selected_option == 'None')))
			{
				alert('Select Driver from catalog first');
			}
			
		}
	});
	function UpdateEstimates(data)
	{
		var fields= data.selected_option.split(':');
		if(fields.length == 3)
			var source_ver = fields[2];
		if(fields.length == 2)
			var source_ver = fields[1];
		
		var dev_field_name = 'dev_estimate_'+source_ver+'_to_'+dest_var;
		var qa_field_name = 'qa_estimate_'+source_ver+'_to_'+dest_var;
		
		
		if(data[dev_field_name] === undefined)
			dev_field_name = 'dev_estimate_new';
		
		if(data[qa_field_name] === undefined)
			qa_field_name = 'qa_estimate_new';
		
		data.dev_estimate = data[dev_field_name];
		data.qa_estimate = data[qa_field_name];
		
		console.log(data.selected_option);
		
		
			
		for(var i=0;i<data.children.length;i++)
		{
			data.children[i].dev_estimate = data.children[i][dev_field_name];
			data.children[i].qa_estimate = data.children[i][qa_field_name];
			if(data.children[i].enabled)
			{
				data.dev_estimate += data.children[i].dev_estimate ;
				data.qa_estimate += data.children[i].qa_estimate ;
			}
		}
		
		drivers[0].qa_estimate = 0;
		drivers[0].dev_estimate = 0;
		for(var i=0;i<drivers[0]._children.length;i++)
		{
			var qa_estimate = parseInt(drivers[0]._children[i].qa_estimate,10);
			if(qa_estimate > 0)
			{
				drivers[0].qa_estimate =  drivers[0].qa_estimate + qa_estimate;
				
			}
			var dev_estimate=parseInt(drivers[0]._children[i].dev_estimate,10)
			
			if(dev_estimate > 0)
			{
				drivers[0].dev_estimate =  parseInt(drivers[0].dev_estimate,10)  + dev_estimate;
			}
		}
		if(data.selected_option.trim() == 'None')
		{
			data.qa_estimate = 0;
			data.dev_estimate = 0;
			drivers[0].qa_estimate = 0;
			drivers[0].dev_estimate = 0;
		}
		
		if(drivers[0].dev_estimate == 0)
			drivers[0].dev_estimate = '';
		if(drivers[0].qa_estimate == 0)
			drivers[0].qa_estimate = '';
		driver_table.setData(drivers);
		console.log('saved');
		localStorage.setItem('drivers', JSON.stringify(drivers));
	}
