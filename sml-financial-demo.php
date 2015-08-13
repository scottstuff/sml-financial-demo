<?php

/*
 * Plugin Name: SML Financial Demo
 * Plugin URI: http://www.scogee.com
 * Description: Demo Plugin to aggregate API data
 * Author: Scott Latsa
 * Authoor: URI: http://www.scogee.com
 * License: GPL2
 */

require_once ('src/jpgraph.php'         );
require_once ('src/jpgraph_line.php'    );
require_once ('SML_Financial_Widget.php');


// Note: API definition can be found here
// https://www.quandl.com/api/v2/datasets.json?query=*&source_code=YAHOO&per_page=300&page=1
define("QUANDL_API_KEY", "nS_YbTJrTyXVXY_HuhqJ");
define("QUANDL_ROOT_URL", 'https://www.quandl.com/api/v3/datasets/');
define("QUANDL_DEFAULT_SEARCH", 'YAHOO/');

add_action('wp_enqueue_scripts', 'sml_fin_sytles_and_scripts');

add_action('admin_enqueue_scripts', 'sml_fin_sytles_and_scripts');

add_action('init', 'sml_register_shortcodes');

add_action('save_post', 'sml_fin_save_metabox');

add_action('widgets_init', 'sml_fin_widget_init');

// Meta Box is causing an error on saving - disabling until fixed
// add_action('add_meta_boxes', 'sml_fin_add_metabox');

function sml_fin_sytles_and_scripts() {

	// css files
	wp_enqueue_style('sml-fin-css', plugins_url( '/sml-fin.css', __FILE__ ) );

	wp_enqueue_style('bootstrap-css',
		plugins_url( '/bootstrap/css/bootstrap.min.css', __FILE__ ) );

	wp_enqueue_script('jquery-js',
		plugins_url( '/bootstrap/js/jquery.min.js', __FILE__ ) );

	wp_enqueue_script('bootstrap-js',
		plugins_url( '/bootstrap/js/bootstrap.min.js', __FILE__ ) );

	wp_enqueue_script('sml-fin.js',
		plugins_url( '/sml-fin.js', __FILE__ ) );

}

function sml_register_shortcodes() {

	add_shortcode('sml-ticker', 'sml_symbol');
}

function sml_symbol($args, $content) {

	if (strlen($args['ticker']) > 0) {
		$ticker = $args['ticker'];
	} else {
		$ticker = 'YHOO';
	}

	if (strlen($args['start_date']) > 0 && strlen($args['end_date'] > 0)) {
		$start_date = $args['start_date'] ;
		$end_date  = $args['end_date'] ;
	} else {
		$start_date = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")-8, date("Y")));
		$end_date = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));
	}

	$dataset_search  = QUANDL_DEFAULT_SEARCH;

	$result_json = sml_fin_get_data( array(
		'dataset_search' => QUANDL_DEFAULT_SEARCH,
		'ticker' => $ticker,
		'api_key' => QUANDL_API_KEY,
		'start_date' => $start_date,
		'end_date' => $end_date,
		));


	$parsed_data = sml_fin_parse_api($result_json);

	$graph_file = sml_fin_gen_graph(array(
		'ticker_high' => $parsed_data['ticker_high'], 
		'ticker_low' => $parsed_data['ticker_low'],
		'ticker_dates' => $parsed_data['ticker_dates'], 
		'ticker_values' => $parsed_data['ticker_values'], 
		'ticker_name' => $parsed_data['ticker_name'],
		'graph_name' => 'shortcode',

		));

    return '<img src="'.get_site_url().'/'.$graph_file.'?v='.time().'" alt="Financial Graph" height="250" width="350">';

}

function sml_fin_get_data($api_options) {

	$quote_url = QUANDL_ROOT_URL.$api_options['dataset_search'].$api_options['ticker'].'.json?api_key='.$api_options['api_key'].'&start_date='.$api_options['start_date'].'&end_date='.$api_options['end_date'];

	$json_response  = wp_remote_get($quote_url);

	return $json_response;
}

function sml_fin_parse_api($result_json) {

	$result_body_json = json_decode($result_json['body'], true);

	if (array_key_exists('quandl_error', $result_body_json)) {
		// If API fails do nothing -- Better error hanndling is needed
		return '';
	}

	$ticker_values = $result_body_json['dataset']['data'];

	$ticker_dates     = array();
	$ticker_open      = array();
	$ticker_high      = array();
	$ticker_low       = array();
	$ticker_close     = array();
	$ticker_volume    = array();
	$ticker_adj_close = array();

	foreach ($ticker_values as $ticker_value) {

		$ticker_dates[]     = substr($ticker_value[0], 5, 2).'/'.substr($ticker_value[0], 8, 2);
		$ticker_open[]      = $ticker_value[1];
		$ticker_high[]      = $ticker_value[2];
		$ticker_low[]       = $ticker_value[3];
		$ticker_close[]     = $ticker_value[4];
		$ticker_volume[]    = $ticker_value[5];
		$ticker_adj_close[] = $ticker_value[6];

	}

	return array (
		'ticker_high' => $ticker_high,
		'ticker_low' => $ticker_low,
		'ticker_dates' => $ticker_dates,
		'ticker_values' => $ticker_values,
		'ticker_name' => $result_body_json['dataset']['name']		
		);
}


function sml_fin_gen_graph($graph_data) {

	$graph = new Graph(350,250);
	$graph->SetScale('textlin');

	// Create the linear plot
	$lineplot1=new LinePlot(array_reverse($graph_data['ticker_high']));
	$lineplot1->SetColor('blue');

	// Add the plot to the graph
	$graph->Add($lineplot1);

	$lineplot2=new LinePlot(array_reverse($graph_data['ticker_low']));
	$lineplot2->SetColor('green');

	// Add the plot to the graph
	$graph->Add($lineplot2);

	$graph->xaxis->SetTickLabels(array_reverse($graph_data['ticker_dates']));
	$graph->title->Set(substr($graph_data['ticker_name'],0, strlen($graph_data['ticker_name'])-2));
	$graph->footer->center->Set('( '.count($graph_data['ticker_values']).' day trend starting ' . 
		substr(array_reverse($graph_data['ticker_values'])[0][0], 5, 2).
		'/'.substr(array_reverse($graph_data['ticker_values'])[0][0], 8, 2).'/'.substr(array_reverse($graph_data['ticker_values'])[0][0], 0, 4)
		. ' )');

	$graph->title->SetColor('darkred');
	$graph->footer->center->SetColor('darkred');

	// Build the graph
	$plugin_file_dir = 'wp-content/uploads/sml-fin/';
    if (!is_dir($plugin_file_dir)){
    	wp_mkdir_p( $plugin_file_dir );
    } 

	$output_file = $plugin_file_dir . $graph_data['graph_name']. '-post-graph.jpg';
    $graph->Stroke($output_file);

    return $output_file;

}

function sml_fin_add_metabox() {

	add_meta_box('sml_fin_ticker', 'Yahoo Stock Ticker', 'sml_fin_handler', 'post');

}

function sml_fin_handler() {

	$post_value = get_post_custom($post->ID);
	$trade_quote_symbol = esc_attr($post_value['sml_fin_quote'][0]);
	$trade_date = esc_attr($post_value['sml_fin_day'][0]);
	$trade_week = esc_attr($post_value['sml_fin_week'][0]);
	$trade_radio = esc_attr($post_value['sml_fin_radio'][0]);
	$trade_radio_day = esc_attr($post_value['sml_day_radio'][0]);

	$yesterday  = mktime(0, 0, 0, date("m")  , date("d")-1, date("Y"));
	$starting_monday = strtotime('this week', time());
	$trade_quote_symbol = (strlen($trade_quote_symbol) > 0 ? $trade_quote_symbol : 'YHOO');
	$trade_date = (strlen($trade_date) > 0 ? $trade_date : date('m/d/Y',$yesterday));
	$trade_week = ($trade_week > 0 ? $trade_week : $starting_monday);
	$trade_radio = (strlen($trade_radio) > 0 ? $trade_radio : 'no');
	$trade_radio_day = (strlen($trade_radio_day) > 0 ? $trade_radio_day : 'week');
	$trade_radio_yes = '';
	$trade_radio_no = '';

	if ($trade_radio == 'no') {
		$trade_radio_no = ' checked="checked"';
	} else {
		$trade_radio_yes = ' checked="checked"';
	}
	$trade_radio_day_day = '';
	$trade_radio_day_week = '';
	 
	if ($trade_radio_day == 'day') {
		$trade_radio_day_day = ' checked="checked"';
	} else {
		$trade_radio_day_week = ' checked="checked"';
	}

	$mondays = array();
	$fridays = array();

	$week_select_ddl = '<select id="sml_fin_week" name="sml_fin_week" >';

	for($i = 1; $i <= 52; $i++) {

		if ($i == 1) {

			$mondays[] = $starting_monday;
			$fridays[] = mktime(0, 0, 0, date("m",$starting_monday)  , date("d",$starting_monday)+4, date("Y",$starting_monday));

		} else {

			$last_monday = $mondays[count($mondays)-1];
			$last_friday = $fridays[count($fridays)-1];
			$mondays[] = mktime(0, 0, 0, date("m",$last_monday)  , date("d",$last_monday)-7, date("Y",$last_monday));
			$fridays[] = mktime(0, 0, 0, date("m",$last_friday)  , date("d",$last_friday)-7, date("Y",$last_friday));
		}

		$this_monday = $mondays[count($mondays)-1];
		$this_friday = $fridays[count($fridays)-1];

		$week_selected = '';
		if ($this_monday == $trade_week) {
			$week_selected = ' selected="selected"';
		}

		$week_select_ddl .= '<option value="'.$this_monday.'"'.$week_selected.'>';
		$week_select_ddl .= date("m/d/Y",$this_monday).' - ' . date("m/d/Y",$this_friday);
		$week_select_ddl .= '</option>';
	}
	$week_select_ddl .= '</select>';

	$starting_monday_formatted = date('m/d/Y',$starting_monday);

	echo '<div id="sml_fin_display_ticker"><label for="sml_fin_radio">Add Stock Ticker to Post </label><input type="radio" id="sml_fin_radio_yes" name="sml_fin_radio" value="yes"'.$trade_radio_yes.'>Yes</input>';
	echo '<input type="radio" id="sml_fin_radio_no" name="sml_fin_radio" value="no"'.$trade_radio_no.'>No</input><br><br></div>';
	echo '<div id="sml_fin_display_ticker_symbol"><label for="sml_fin_quote">Quote Symbol</label><input type="text" id="sml_fin_quote" name="sml_fin_quote" value="'.$trade_quote_symbol.'" /><br><br></div>'; 
	echo '<div id="sml_fin_display_ticker_time"><label for="sml_day_radio">Weekly or Daily</label><input type="radio" id="sml_day_radio_yes" name="sml_day_radio" value="week"'. $trade_radio_day_week.'>Weekly Graph</input>';
	echo '<input type="radio" id="sml_day_radio_no" name="sml_day_radio" value="day"'. $trade_radio_day_day .'>Daily Statistics</input><br><br></div>';
	echo '<div id="sml_fin_display_ticker_weekly"><label for="sml_fin_week">Trade Week</label>'.$week_select_ddl.'<br><br></div>'; 
	echo '<div id="sml_fin_display_ticker_daily"><label for="sml_fin_day">Trade Day</label><input type="text" id="sml_fin_day" name="sml_fin_day" value="'.$trade_date.'" /><br><br></div>'; 
}


function sml_fin_save_metabox($post_id) {

	if (isset($_POST['sml_fin_radio'])) {
		update_post_meta($post_id, 'sml_fin_radio', esc_attr($_POST['sml_fin_radio']));
	}

	if (isset($_POST['sml_fin_quote'])) {
		update_post_meta($post_id, 'sml_fin_quote', esc_attr($_POST['sml_fin_quote']));
	}

	if (isset($_POST['sml_day_radio'])) {
		update_post_meta($post_id, 'sml_day_radio', esc_attr($_POST['sml_day_radio']));
	}

	if (isset($_POST['sml_fin_week'])) {
		update_post_meta($post_id, 'sml_fin_week', esc_attr($_POST['sml_fin_week']));
	}

	if (isset($_POST['sml_fin_day'])) {
		update_post_meta($post_id, 'sml_fin_day', esc_attr($_POST['sml_fin_day']));
	}

	if (isset($_POST['sml_fin_radio'])) {
		if ($_POST['sml_fin_radio'] == 'yes') {

			$result_json = sml_fin_get_data( array(
				'dataset_search' => QUANDL_DEFAULT_SEARCH,
				'ticker' => $_POST['sml_fin_quote'],
				'api_key' => QUANDL_API_KEY,
				'start_date' => '2015-07-25',
				'end_date' => '2015-07-30',
				));


			$parsed_data = sml_fin_parse_api($result_json);


			$graph_file = sml_fin_gen_graph(array(
				'ticker_high' => $parsed_data['ticker_high'], 
				'ticker_low' => $parsed_data['ticker_low'],
				'ticker_dates' => $parsed_data['ticker_dates'], 
				'ticker_values' => $parsed_data['ticker_values'], 
				'graph_name' => 'metabox',

				));
//			var_dump ($graph_file);
            echo '<img src="'.get_site_url().'/'.$graph_file.'" alt="Financial Graph" height="250" width="350">';
//			die;			
		}

	}

}


function sml_fin_widget_init () {

	register_Widget(SML_Financial_Widget);	
}


function get_fin_yt_videoid($url) {
	parse_str(parse_url($url, PHP_URL_QUERY), $my_array_of_vars );
	return $my_array_of_vars['v'];
}