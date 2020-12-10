<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel</title>
		<link rel="stylesheet" href="{{ asset('libs/smartwizard/css/smart_wizard_all.min.css') }}" />
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
			width:50%;
		}
		.flex-item {
			text-align: center;
		}
    </style>
    </head>
    <body>
	<div class="flex-container">
		<div class="row"> 
			<div id="smartwizard" style="display:block">
				<ul class="nav">
					<li>
						<a class="nav-link" href="#step-1">
							Step 1
							</a>
					</li>
					<li>
						<a class="nav-link" href="#step-2">
							Step 2
						</a>
					</li>
					<li>
						<a class="nav-link" href="#step-3">
							Step 3
						</a>
					</li>
					<li>
						<a class="nav-link" href="#step-4">
							Step 4
						</a>
					</li>
				</ul>
				<div class="tab-content">
				   <div id="step-1" class="tab-pane" role="tabpanel">
					  <form>
  <label for="fname">First name:</label><br>
  <input type="text" id="fname" name="fname"><br>
  <label for="lname">Last name:</label><br>
  <input type="text" id="lname" name="lname">
</form>
				   </div>
				   <div id="step-2" class="tab-pane" role="tabpanel">
					  Step content 2
				   </div>
				   <div id="step-3" class="tab-pane" role="tabpanel">
					  Step content 3
				   </div>
				   <div id="step-4" class="tab-pane" role="tabpanel">
					  Step content 4
				   </div>
				</div>
			</div>
		</div>
	</div>
    </body>
	<script src="{{ asset('libs//jquery/jquery.min.js')}}" ></script>
	<script src="{{ asset('libs//smartwizard/js/jquery.smartWizard.min.js')}}" ></script>
	<script>
	$(document).ready(function()
	{
		$('#smartwizard').smartWizard({
			theme: 'arrows'});
		
	});
	</script>
</html>
