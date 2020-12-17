<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel</title>
		<link rel="stylesheet" href="{{ asset('libs/smartwizard/css/smart_wizard_all.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/form/form.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('libs/css/common.css') }}" />
	<style>
    </style>
    </head>
    <body>
	<br>
	<div class="flex-container">	
		<div class="row"> 
		    <div style="font-weight:bold;font-size:20px;line-height: 70px;height:75px;background-color:#4682B4;color:white;" class="flex-item"> 
			 <img style="float:left;" height="20px" src="{{ asset('apps/ishipment/images/mentor.png') }}"></img>
			 <h3 style="margin-right:150px;"> BSP Estimation Wizard </h3>
			</div>
			<br>
		    <div id="smartwizard" style="display:block">
			 	<ul class="nav">
					<li>
						<a class="nav-link" href="#step-1">
							General Information
						</a>
					</li>
					<li>
						<a class="nav-link" href="#step-2">
							OSS Components
						</a>
					</li>
					<li>
						<a class="nav-link" href="#step-3">
							Architecture<br>Port
						</a>
					</li>
					<li>
						<a class="nav-link" href="#step-4">
							Peripheral Driver Support
						</a>
					</li>
					<li>
						<a class="nav-link" href="#step-5">
							MCF Support<br>(For AMP SOCs)
						</a>
					</li>
					
				</ul>
				<div class="tab-content">
				   <div id="step-1" class="tab-pane" role="tabpanel">
				     @include('bspestimate.general')
				   </div>
				   <div id="step-2" class="tab-pane" role="tabpanel">
					 @include('bspestimate.oss')
				   </div>
				   <div id="step-3" class="tab-pane" role="tabpanel">
					  @include('bspestimate.ap')
				   </div>
				   <div id="step-4" class="tab-pane" role="tabpanel">
					  @include('bspestimate.pds')
				   </div>
				   <div id="step-5" class="tab-pane" role="tabpanel">
					  Step content 5
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
		
		$(".driver_textbox").change(function(){
			
			var group = $(this).data('group');
			var elements = $('.'+group);
			
			for(var i=0;i<elements.length;i++)
			{
				var element = $(elements[i]);
				switch(element.prop("tagName"))
				{
					case 'INPUT':
						console.log(element.val());
						break;
					break;
					case 'SELECT':
						console.log(element.val());
					
					break;
					
				}
			}
			
		});

		
		$('#smartwizard').smartWizard({
			theme: 'arrows',
			enablePagination: true
			});
		
	});
	</script>
</html>
