 <form>
    <br>
	<br>
	
	 
    <div class='componentWrapper'><div class="header">Drivers</div>
	
		<table style="width: 100%;">
			<colgroup>
				<col span="1" style="width: 10%;">
				<col span="1" style="width: 40%;">
				<col span="1" style="width: 25%;">
				<col span="1" style="width: 25%;">
			</colgroup>
			<tbody>
				<tr>
					<th align= "left">Class</th>
					<th align= "left">Driver</th>
					<th align= "left">Available In</th>
					<th align= "left">Estimates</th>
				</tr>
				<tr>
					<td align= "left">Cpu</td>
					<td align= "left"><select style="border: 2px solid red;" id="cpu" class="tokenize2" multiple></select></td>
					<td align= "left">Nucleus 4.x</td>
					<td align= "left">23</td>
				</tr>
				<tr>
					<td align= "left">Cpu</td>
					<td align= "left">Cpu</td>
					<td align= "left">Nucleus 4.x</td>
					<td align= "left">23</td>
				</tr>
			</tbody>
		</table>
		
		
		<table style="width: 100%;">
			<colgroup>
				<col span="1" style="width: 10%;">
				<col span="1" style="width: 40%;">
				<col span="1" style="width: 25%;">
				<col span="1" style="width: 25%;">
			</colgroup>
			<tbody>
				<tr>
					<th align= "left">Class</th>
					<th align= "left">Driver</th>
					<th align= "left">Available In</th>
					<th align= "left">Estimates</th>
				</tr>
				<tr>
					<td align= "left">Cpu</td>
					<td align= "left"><select style="border: 2px solid red;" id="cpu" class="tokenize2" multiple></select></td>
					<td align= "left">Nucleus 4.x</td>
					<td align= "left">23</td>
				</tr>
			</tbody>
		</table>
		
	</div>

<form>

<script>

function Pupulate_Drivers()
{
	function datasource(search, object)
	{
		var id = $(object.element).attr('id');
		$.ajax('remote.php', {
				data: { id : id, search: search, start: 1 },
				dataType: 'json',
				success: function(data){
					var $items = [];
					$.each(data, function(k, v){
						$items.push(v);
					});
					object.trigger('tokenize:dropdown:fill', [$items]);
				}
			});
	}
	$('.tokenize2').tokenize2({
		tokensMaxItems: 1,
		dataSource: datasource
	});
}
//Did not participate in class
//Did not complete homework
</script>