<?php
/*
Plugin Name: Ozh' Spam Magnet Checker
Plugin URI: http://planetozh.com/blog/2010/09/spam-magnet-blog-posts/
Description: Check if particular posts are magnets for spammers.
Author: Ozh
Version: 1.0.1
Author URI: http://ozh.org/
*/

/* Release:
 * 1.0     Initial release
 * 1.0.1   Added some Ajax to close comments
 */

add_action( 'admin_menu', 'wp_ozh_smc_add_menu' );
add_action('wp_ajax_ozh_smc_close', 'wp_ozh_smc_ajaxclose');

function wp_ozh_smc_add_menu() {
	add_dashboard_page( 'Spam Magnets', 'Spam Magnets', 'edit_posts', 'ozh_smc', 'wp_ozh_smc_print' );
}

function wp_ozh_smc_print() {
	global $wpdb;

	$sql = "SELECT COUNT($wpdb->posts.ID) as spam_count, $wpdb->posts.ID, $wpdb->posts.comment_status, $wpdb->posts.post_title
	FROM $wpdb->posts, $wpdb->comments
	WHERE $wpdb->comments.comment_approved = 'spam' AND $wpdb->comments.comment_post_ID=$wpdb->posts.ID
	GROUP BY $wpdb->posts.ID
	ORDER BY spam_count DESC";

	$spams = $wpdb->get_results( $sql );

	?>
    <div class="wrap">
    <?php screen_icon(); ?>
    <h2>Ozh' Spam Magnets Checker</h2>
	
	<p>This plugin will list posts spammers find the most attractive.
	It will help you identify on which forgotten post you should close comments,
	and also define potentially spam attractive keywords in your titles (eg. "comments", "feedback", "guestbook"...)</p>
	<?php
	
	
	if( !$spams ) {
		echo "<p><strong>You don't have spam currently</strong>. Come back later!</p>";

	} else {
	
		?>
		<h3>The pretty chart</h3>
		
		<div id="chart_div"></div>
		<script type="text/javascript" src="http://www.google.com/jsapi"></script>
		<script type="text/javascript">
		google.load("visualization", "1", {packages:["corechart"]});
		google.setOnLoadCallback(drawChart);
		function drawChart() {
			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Post title');
			data.addColumn('number', 'Spam');
			data.addRows([
			<?php foreach( $spams as $spam ) {
				echo "['". esc_js( $spam->post_title ) . "', " . absint( $spam->spam_count ) . "],\n";
			} ?>
			]);
			var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
			chart.draw(data, {width: 600, height: 400, backgroundColor: 'white', sliceVisibilityThreshold: 1/90, is3D: true, title: 'Spam Magnet Blog Posts'});
		}
		</script>
		<style>
		#chart_div{width:600px;height:400px; border:1px solid #ccc}
		</style>
		
		<h3>The list (total spam per post)</h3>
		
		<ul>
		<?php
		foreach( $spams as $spam ) {
			$action = $spam->comment_status == 'open' ? 'close' : 'closed' ;
			$action_link = $action == 'close' ? "<a href='#close' class='ozh_smc_close' title='Close comments on this post'>close</a>" : 'closed' ;
			printf( "<li id='post_%s'>%05d : <strong>%s</strong> <a href='%s' title='View post'>view</a> $action_link</li>\n",
				$spam->ID, $spam->spam_count, $spam->post_title, get_permalink( $spam->ID) );
		}
		?>
		</ul>
		<script type="text/javascript">
		(function($) {
			$('.ozh_smc_close').click(function(){
				var id = $(this).parent().attr('id').replace('post_', '');
				var link = this;
				var data = {
					action: 'ozh_smc_close',
					id: id,
					nonce: '<?php echo wp_create_nonce( 'ozh-smc_close' ); ?>'
				};
				jQuery.post(ajaxurl, data, function(response) {
					if( response == 'success' ) {
						$(link).after('closed').remove()
					} else {
						alert( 'Error' );
					};
				});
				return false;
			});
		})(jQuery);
		</script>

		<p>See <a href="http://planetozh.com/blog/2010/09/spam-magnet-blog-posts/">Spam Magnet Blog Posts</a> for more infos.</p>
		
		<?php
	
	}
	
}

function wp_ozh_smc_ajaxclose() {

	if ( !current_user_can( 'edit_posts' ) )
		die( 'yeah right.' );
	
	$nonce = $_POST['nonce'];
	if ( ! wp_verify_nonce( $nonce, 'ozh-smc_close' ) )
		die( 'yeah right.' );

	$post_id = absint( $_POST['id'] );
	if( $post_id != $_POST['id'] )
		die( 'yeah right.' );
		
	$close = wp_update_post( array( 'ID' => $post_id, 'comment_status' => 'closed' ) );
	
	if( $close == $post_id ) {
		echo 'success';
	} else {
		echo 'error';
	}

	die();
}
