<?php

class SML_Financial_Widget extends WP_Widget {

	function SML_Financial_Widget () {

		$widget_options = array(
			'classname' => 'sml_class',
			'description' => 'Show Financial Graph' );

		$this->WP_Widget('sml_id', 'Yahoo Financial Graph', $widget_options);
	}

	/**
	 * form data
	 */
	function form($instance) {

		$defaults = array(
			'title' => 'Financial Graph',
			'ticker' => 'Ticker Symbol',
			'start_date' => 'Start Date - YYYY-MM-DD',
			'end_date' => 'End Date - YYYY-MM-DD'
			);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = esc_attr($instance['title']);
		$ticker = esc_attr($instance['ticker']);
		$start_date = esc_attr($instance['start_date']);
		$end_date = esc_attr($instance['end_date']);

		echo '<p>Title <input type="text" class="widefat" name="'.$this->get_field_name('title').'" value="'.$title.'" /></p>';
		echo '<p>Ticker <input type="text" class="widefat" name="'.$this->get_field_name('ticker').'" value="'.$ticker.'" /></p>';
		echo '<p>Start Date <input type="text" class="widefat" name="'.$this->get_field_name('start_date').'" value="'.$start_date.'" /></p>';
		echo '<p>End Date <input type="text" class="widefat" name="'.$this->get_field_name('end_date').'" value="'.$end_date.'" /></p>';
		echo '<p>Resuts are best deplayed with 5 day span, Monday to Friday</p>';

	}

	function update ($new_instance, $old_instance) {

		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['ticker'] = strip_tags($new_instance['ticker']);
		$instance['start_date'] = strip_tags($new_instance['start_date']);
		$instance['end_date'] = strip_tags($new_instance['end_date']);

		return $instance;
	}

	function widget($args, $instance) {

		extract($args);

		$title = apply_filters('widget_title', $instance['title']);

		if (is_single()) {
			echo $before_widget;
			echo $before_title.$title.$after_title;

			$sml_fin_graph = esc_url(get_post_meta(get_the_ID(), 'sml_fin_graph', true));

			$result_json = sml_fin_get_data( array(
				'dataset_search' => QUANDL_DEFAULT_SEARCH,
				'ticker' => $instance['ticker'],
				'api_key' => QUANDL_API_KEY,
				'start_date' => $instance['start_date'],
				'end_date' => $instance['end_date'],
				));


			$parsed_data = sml_fin_parse_api($result_json);


			$graph_file = sml_fin_gen_graph(array(
				'ticker_high' => $parsed_data['ticker_high'], 
				'ticker_low' => $parsed_data['ticker_low'],
				'ticker_dates' => $parsed_data['ticker_dates'], 
				'ticker_values' => $parsed_data['ticker_values'], 
				'ticker_name' => $parsed_data['ticker_name'], 
				'graph_name' => 'widget',

				));

			// print widget
		    echo '<img src="'.get_site_url().'/'.$graph_file.'" alt="Financial Graph" height="250" width="350">';

			echo $after_widget;
		}

	}
}
