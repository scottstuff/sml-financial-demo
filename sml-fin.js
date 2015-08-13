/*
* JavaScript for sml financial demo plugin
*/

jQuery(document).ready(function ($) {

	if($("#sml_fin_ticker input[name='sml_fin_radio']:checked").val() == "no"){
		$("#sml_fin_display_ticker_symbol").addClass('hidden');
		$("#sml_fin_display_ticker_time").addClass('hidden');
		$("#sml_fin_display_ticker_daily").addClass('hidden');
		$("#sml_fin_display_ticker_weekly").addClass('hidden');
	} else {		
		if($("#sml_fin_ticker input[name='sml_day_radio']:checked").val() == "week"){
			$("#sml_fin_display_ticker_daily").addClass('hidden');
		} else {
			$("#sml_fin_display_ticker_weekly").addClass('hidden');
		}
	}


	$("#sml_fin_ticker input[name='sml_fin_radio']").click(function(){

	    if($("#sml_fin_ticker input[name='sml_fin_radio']:checked").val() == "yes"){
	        $("#sml_fin_display_ticker_symbol").removeClass('hidden');
	        $("#sml_fin_display_ticker_time").removeClass('hidden');
			if($("#sml_fin_ticker input[name='sml_day_radio']:checked").val() == "day"){
				$("#sml_fin_display_ticker_daily").removeClass('hidden');
			} else {
				$("#sml_fin_display_ticker_weekly").removeClass('hidden');
			}
	    } else {
	    	$("#sml_fin_display_ticker_symbol").addClass('hidden');
	    	$("#sml_fin_display_ticker_time").addClass('hidden');
			$("#sml_fin_display_ticker_daily").addClass('hidden');
			$("#sml_fin_display_ticker_weekly").addClass('hidden');	    
		}
	});

	$("#sml_fin_ticker input[name='sml_day_radio']").click(function(){

	    if($("#sml_fin_ticker input[name='sml_day_radio']:checked").val() == "week"){
	   		$("#sml_fin_display_ticker_daily").addClass('hidden');
			$("#sml_fin_display_ticker_weekly").removeClass('hidden');
	    } else {
	   		$("#sml_fin_display_ticker_daily").removeClass('hidden');
			$("#sml_fin_display_ticker_weekly").addClass('hidden');
		}
	});

});
