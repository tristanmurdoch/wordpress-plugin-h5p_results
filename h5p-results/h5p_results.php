<?php
/**
 * Plugin Name: H5P Results
 * Description: Adds custom shortcodes for H5P results.
 * Author: Tristan Mackay
 * Version: 1.0
 * Text Domain: h5p-results
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    Tristan Mackay
 * @copyright Copyright (c) 2017
 *
 */


defined( 'ABSPATH' ) or exit;

function h5p_results_func($atts){
	
	global $wpdb;

 	$atts = shortcode_atts( array(
        	'display' => 'list',
		'tablename' => 'H5P Results',
		'userid' => 'none',
    	), $atts );


	$current_user = wp_get_current_user();
	   	if($atts['userid'] !== 'none')
		  $current_user->ID = $atts['userid'];

	$results = $wpdb->get_results( "SELECT id, slug FROM `wp_h5p_contents`;" );

	$sql_string = 'SELECT ';

   	if($atts['userid'] == 'all'){
		$sql_string = "SELECT u.display_name, ";
		$atts['display'] = 'row';
   	}

	foreach ($results as $result){
		$sql_string .=  "IFNULL(CONCAT(r".$result->id.".score , '/', r".$result->id.".max_score),'N/A') as '".$result->slug."', ";
	}

	$sql_string = substr($sql_string, 0, -2);

	$sql_string .= " FROM `wp_users` as u ";

	foreach ($results as $result){

		$sql_string .= "left join `wp_h5p_results` as r".$result->id." on r".$result->id.".user_id = u.id and r".$result->id.".content_id = ".$result->id." ";
	}

	if($atts['userid'] !== 'all')
	   $sql_string .= "WHERE u.id =".$current_user->ID ;
	
	$results = $wpdb->get_results($sql_string);

	if($atts['display'] == 'row'){

		$html = "<table border='1'>".
				'<table class="h5p-results">
				<thead>
				<tr>';

		foreach ($results as $result){
			$result = (array)$result;
				$result_keys = array_keys($result); // get keys
		}

		foreach ($result_keys as $result_key){
			$html .= '<th>'.$result_key.'</th>';
		}

		$html .= '</tr></thead>';

		foreach ($results as $row){
			$html .= "<tr>";
		
			foreach ($row as $r){
				$html .= "<td>" . $r . "</td>";
			}
		$html .= "</tr>";
	//close off table mn
		}
	$html .= "</tbody></table>";

	}

	if($atts['display'] == 'list'){

		$sql = "SELECT c.title, c.slug, r.score, r.max_score, ".
		"r.opened, r.finished FROM `wp_h5p_results` r LEFT JOIN ".
		"`wp_h5p_contents` AS c ON c.id = r.content_id WHERE r.user_id = "
		.$current_user->ID;

		$results = $wpdb->get_results($sql);

		$html = '<table>
				<caption>'.$atts['tablename'].'</caption>
				<thead>
				<tr>
				<th scope="col">Title</th>
				<th scope="col">Result</th>
				<th scope="col">Result %</th>
				<th scope="col">Completion date</th>
				<th scope="col">Time taken</th>
				</tr>
				</thead>
				<tbody>';

		if(count($results) >= 1){

			foreach($results as $result){
				// work out time taken 
				$duration = (string)$result->finished - (string)$result->opened;		
				$minutes = floor($duration / 60);
				$seconds = $duration % 60;
				// convert score to %
				$score_percent = (string)$result->score / (string)$result->max_score * 100;

				$result->finished = date('m/d/Y', $result->finished);

				$html .= '<tr><td>'.$result->title.'</td><td>'.
				$result->score.'/'.$result->max_score.'</td><td>'.
				substr($score_percent,0,5).'</td><td>'.
				$result->finished.'</td><td>'.
				$minutes.':'.$seconds.'</td></tr>';
				}
		}else{
		$html .= '<tr><td colspan="5">No results</td></tr>';
		}
	$html .= '</tbody></table>';

	}
unset($atts);
unset($current_user);
return($html);
}


add_shortcode('h5p_results', 'h5p_results_func');
