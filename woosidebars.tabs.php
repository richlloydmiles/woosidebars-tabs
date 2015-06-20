<?php
/*
Plugin Name: WooSidebars Tabs
Plugin URI:  http://richmiles.co.za
Description: Adds a tabbed menu, to widgets admin area for woosidebars.
Version:     1.0
Author:      Richard Miles
Author URI:  http://richmiles.co.za
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

add_action( 'init', 'check_woo_sidebars' );

function check_woo_sidebars() {
	if( class_exists( 'Woo_Sidebars' ) ) {
	//new ajax tabs function action
		add_action('wp_ajax_woosidebars_widget_tabs' , 'woosidebars_widget_tabs' ); 
	//adds tab markup to widgets admin page.
		add_action('widgets_admin_page' , 'ajax_widget_areas' );
	} else {
		add_action( 'admin_notices', function() {
			if (isset($_POST['woo_sidebar_notice'])) {
				update_option( 'woo_sidebar_notice', 'true' ); 
			}
			if (get_option('woo_sidebar_notice' , 'false') != 'true') {
				?>
				<div id="message" class="notice is-dismissible"><p><a href="update.php?action=install-plugin&plugin=woosidebars&_wpnonce=55a05b6c99" >WooSidebars</a> needs to be installed for <b>WooSidebar Tabs</b> to have any effect.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
					<form action="" method="POST" style="padding: 10px 0;">
						<input class="skip button" name="woo_sidebar_notice" type="submit" value="Hide this message">
					</form>
				</div>
				<?php
			}
		} );
	}
}


/**
 * ajax request to to conditionally display widget areas.
 */
function woosidebars_widget_tabs() {
    // The $_REQUEST contains all the data sent via ajax
	if ( isset($_REQUEST) ) {
		$replace = $_REQUEST['replace'];
		$sidebars = array();
			//query post meta table to find conditions related to replaced sidebar. 
		$query_args = array(
			'post_type' => 'sidebar',
			'post_status'=>'publish' , 
			'meta_query' => array(
				array(
					'key' => '_condition',
					'value' => "$replace" ,
					'compare' => '=',
					)
				)
			);
		query_posts( $query_args );

		/* The Loop. */
		if ( have_posts() ) {
			while ( have_posts() ) { 
				the_post(); 
					//pushes replaced sidebars that are foud in query to array
				array_push($sidebars, get_post_meta(get_the_id(), '_sidebar_to_replace' , true));
			}
		}
		wp_reset_postdata(); 
			//array values are parsed to json format and returned to ajax_widget_area function.
		echo json_encode($sidebars); // result may contain multiple rows
	} // End woosidebars_widget_tabs() 
	die();
}

/**
 * Populates widget area page with conditional tabs.
 */
function ajax_widget_areas() {
		//Query to display all sidebar conditionals in widget admin area.
	$args = array(
		'post_type'  => 'sidebar'
		);
	query_posts( $args );
	if ( have_posts() ) {
		$conditions = array();
		?>
		<div class="nav-tab-wrapper">
			<a href data-replace="_all" class="nav-tab nav-tab-active">All</a>
			<?php
			$pages = array();
			while ( have_posts() ) {the_post(); 
				global $post;
				foreach (get_post_meta( $post->ID, '_condition', false ) as $value) {
					if (!in_array($value, $pages)) {
						array_push($pages, $value);
						?>
						<a data-replace="<?php echo $value;  ?>" class="nav-tab"><?php echo get_the_title(str_replace('post-', '', $value)) ;
							?></a>
							<?php
							}//end if in array
						}//end foreach
					}//end post while
					?>
				</div>
				<?php
			}//end if
			wp_reset_postdata();	
			?>
			
			<!-- This javascript should be moved to a js file at some point. -->
			<script>
				jQuery(document).ready(function($) {
					jQuery('.nav-tab').css('cursor' , 'pointer');
					jQuery(document).on('click', '.nav-tab', function(event) {
						jQuery('.nav-tab').removeClass('nav-tab-active');
						event.preventDefault();
						jQuery(this).addClass('nav-tab-active');
						jQuery.ajax({
							url: ajaxurl,
							type: 'POST',
							dataType: 'json',
							data: {action: 'woosidebars_widget_tabs' , replace : jQuery(this).data('replace')},
						})
						.done(function(data) {
							jQuery('.widgets-holder-wrap').stop(true,true).show();
							for (var i = data.length - 1; i >= 0; i--) {
								jQuery('#' + data[i]).parent('div').stop(true,true).hide();
							}
						});			
					});
				});</script>
				<?php
		} // End ajax_widget_areas()
