 <form>
    <br>
	<br>
    <div class='componentWrapper'><div class="header">Dev Tasks</div>
		<table style="width: 100%;">
			<colgroup>
				<col span="1" style="width: 90%;">
				<col span="1" style="width: 10%;">
			</colgroup>
			<tbody id="devtasks">
				<tr>
					<th align= "left">Title</th>
					<th align= "left">Estimate</th>
				</tr>
			</tbody>
		</table>
	</div>
<form>
<script>

var devtasks = @json($devtasks);
function Pupulate_DevTasks()
{
	function CreateColumn(obj,number)
	{
		var td = $('<td></td>');
		switch(number)
		{
			case 1:
				td.html(obj.title);
				break;
			case 2:
				var input = $('<input  type="text" class="form" style="width:90%" placeholder="" >');
				input.val(obj.estimates);
				if(obj.immuteable == 1)
					input.prop( "disabled", true );
				input.attr("data-id",obj.id);  
				input.addClass('qatasks');
				td.append(input);
				break;
		}
		return td;
	}
	for(var i in devtasks)
	{	
		var o = devtasks[i];
		var tr = $('<tr></tr>');
		tr.append(CreateColumn(o,1))
		tr.append(CreateColumn(o,2));
		$('#devtasks').append(tr);
	}
}
//Did not participate in class
//Did not complete homework
</script>