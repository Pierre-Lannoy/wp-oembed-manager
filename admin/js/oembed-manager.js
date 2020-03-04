jQuery(document).ready( function($) {
	$( ".oemm-about-logo" ).css({opacity:1});
	$( ".oemm-select" ).each(
		function() {
			var chevron  = 'data:image/svg+xml;base64,PHN2ZwogIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICB3aWR0aD0iMjQiCiAgaGVpZ2h0PSIyNCIKICB2aWV3Qm94PSIwIDAgMjQgMjQiCiAgZmlsbD0ibm9uZSIKICBzdHJva2U9IiM3Mzg3OUMiCiAgc3Ryb2tlLXdpZHRoPSIyIgogIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIKICBzdHJva2UtbGluZWpvaW49InJvdW5kIgo+CiAgPHBvbHlsaW5lIHBvaW50cz0iNiA5IDEyIDE1IDE4IDkiIC8+Cjwvc3ZnPgo=';
			var classes  = $( this ).attr( "class" ),
			id           = $( this ).attr( "id" ),
			name         = $( this ).attr( "name" );
			var template = '<div class="' + classes + '">';
			template    += '<span class="oemm-select-trigger">' + $( this ).attr( "placeholder" ) + '&nbsp;<img style="width:18px;vertical-align:top;" src="' + chevron + '" /></span>';
			template    += '<div class="oemm-options">';
			$( this ).find( "option" ).each(
				function() {
					template += '<span class="oemm-option ' + $( this ).attr( "class" ) + '" data-value="' + $( this ).attr( "value" ) + '">' + $( this ).html().replace("~-", "<br/><span class=\"oemm-option-subtext\">").replace("-~", "</span>") + '</span>';
				}
			);
			template += '</div></div>';

			$( this ).wrap( '<div class="oemm-select-wrapper"></div>' );
			$( this ).after( template );
		}
	);
	$( ".oemm-option:first-of-type" ).hover(
		function() {
			$( this ).parents( ".oemm-options" ).addClass( "option-hover" );
		},
		function() {
			$( this ).parents( ".oemm-options" ).removeClass( "option-hover" );
		}
	);
	$( ".oemm-select-trigger" ).on(
		"click",
		function() {
			$( 'html' ).one(
				'click',
				function() {
					$( ".oemm-select" ).removeClass( "opened" );
				}
			);
			$( this ).parents( ".oemm-select" ).toggleClass( "opened" );
			event.stopPropagation();
		}
	);
	$( ".oemm-option" ).on(
		"click",
		function() {
			$(location).attr("href", $( this ).data( "value" ));
		}
	);
	$( "#oemm-chart-button-calls" ).on(
		"click",
		function() {
			$( "#oemm-chart-calls" ).addClass( "active" );
			$( "#oemm-chart-data" ).removeClass( "active" );
			$( "#oemm-chart-uptime" ).removeClass( "active" );
			$( "#oemm-chart-button-calls" ).addClass( "active" );
			$( "#oemm-chart-button-data" ).removeClass( "active" );
			$( "#oemm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#oemm-chart-button-data" ).on(
		"click",
		function() {
			$( "#oemm-chart-calls" ).removeClass( "active" );
			$( "#oemm-chart-data" ).addClass( "active" );
			$( "#oemm-chart-uptime" ).removeClass( "active" );
			$( "#oemm-chart-button-calls" ).removeClass( "active" );
			$( "#oemm-chart-button-data" ).addClass( "active" );
			$( "#oemm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#oemm-chart-button-uptime" ).on(
		"click",
		function() {
			$( "#oemm-chart-calls" ).removeClass( "active" );
			$( "#oemm-chart-data" ).removeClass( "active" );
			$( "#oemm-chart-uptime" ).addClass( "active" );
			$( "#oemm-chart-button-calls" ).removeClass( "active" );
			$( "#oemm-chart-button-data" ).removeClass( "active" );
			$( "#oemm-chart-button-uptime" ).addClass( "active" );
		}
	);
} );
