 <form>
    <br>
	<div class='componentWrapper'><div class="header">Drivers</div>
	<div>
	   	<table style="width: 100%;">
		<colgroup>
			<col span="1" style="width: 30%;">
			<col span="1" style="width: 25%;">
			<col span="1" style="width: 25%;">
			<col span="1" style="width: 20%;">
		</colgroup>
		<tbody>
		<tr>
		    <th>&nbsp</th>
			<th>&nbsp</th>
			<th>&nbsp</th>
			<th>&nbsp</th>
		</tr>
		
		</tr>
			<td>
				<span style="margin-left:10px">Architecture</span>
			</td>
			<td>
				<select class="form" style="width:90%">
				    <option>ARMv7m</option>
				</select>
			</td>
			<td></td>
			<td></td>
		<tr>
		
		</tr>
			<td>
				<span style="margin-left:10px">Architecture Type</span>
			</td>
			<td>
				<select class="form" style="width:90%">
				    <option>Unicore</option>
					<option>SMP</option>
					<option>AMP</option>
				</select>
			</td>
			<td></td>
			<td></td>
		<tr>
		<tr>
		    <th>&nbsp</th>
			<th>&nbsp</th>
			<th>&nbsp</th>
			<th>&nbsp</th>
		</tr>
		<tr>
		    <th></th>
			<th style="text-align:left">Compatibility String</th>
			<th style="text-align:left">Availability</th>
			<th style="text-align:left">Estimate(SP)</th>
		</tr>
		
			<td>
				 <span style="margin-left:10px">CPU Driver</span>
			</td>
			<td>
				<input  data-group="cpu" type="text" class="form driver_textbox cpu" style="width:90%" placeholder="Compatibility String">
			</td>
			<td>
				<select id="driver_cpu_os" class="form cpu" style="width:90%">
				<option>Nucleus 4.x</option>
				</select>
			</td>
			<td>
				<input id="driver_cpu_sp" type="text" class="form cpu" style="width:90%" placeholder="Story Points ">
			</td>
		</tr>
		
		<tr>
			<td>
				<span  style="margin-left:10px">Cache Controller</span>
			</td>
			<td>
				<input  data-group="cache" type="text" class="form driver_textbox cache" style="width:90%" placeholder="Compatibility String">
			</td>
			<td>
				<select class="form cache" style="width:90%">
				<option>Nucleus 4.x</option>
				</select>
			</td>
			<td>
				<input type="text" class="form cache" style="width:90%" placeholder="Story Points ">
			</td>
		</tr>
		
		<tr>
			<td>
				<span style="margin-left:10px">Interrupt Controller</span> 
			</td>
			<td>
				<input  data-group="interrupt" type="text" class="form driver_textbox interrupt" style="width:90%" placeholder="Compatibility String">
			</td>
			<td>
				<select class="form interrupt" style="width:90%" >
				<option>Nucleus 4.x</option>
				</select>
			</td>
			<td>
				<input type="text" class="form interrupt" style="width:90%" placeholder="Story Points ">
			</td>
		</tr>
		
		<tr>
			<td>
				<span style="margin-left:10px">OS Timer</span> 
			</td>
			<td>
				<input  data-group="timer" type="text" class="form driver_textbox timer" style="width:90%" placeholder="Compatibility String">
			</td>
			<td>
				<select class="form timer" style="width:90%">
				<option>Nucleus 4.x</option>
				</select>
			</td>
			<td>
				<input type="text" class="form timer" style="width:90%" placeholder="Story Points ">
			</td>
		</tr>
		<tr>
			<td>
				<span style="margin-left:10px">Pin Control</span>
			</td>
			<td>
				<input  data-group="pincontrol" type="text" class="form driver_textbox pincontrol"  style="width:90%" placeholder="Compatibility String">
			</td>
			<td>
				<select class="form pincontrol" style="width:90%">
				<option>Nucleus 4.x</option>
				</select>
			</td>
			<td>
				<input type="text" class="form pincontrol" style="width:90%" placeholder="Story Points ">
			</td>
		</tr>
		<tr>
			<td>
				<span style="margin-left:10px">Clock</span>
			</td>
			<td>
				<input  data-group="clock" type="text" class="form driver_textbox clock" style="width:90%" placeholder="Compatibility String">
			</td>
			<td>
				<select class="form clock" style="width:90%">
				<option>Nucleus 4.x</option>
				</select>
			</td>
			<td>
				<input type="text" class="form clock" style="width:90%" placeholder="Story Points ">
			</td>
		</tr>
		</tbody>
		</table>
	</div>
	</div>
<form>