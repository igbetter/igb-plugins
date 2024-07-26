<?php

namespace DeleteDuplicatePosts;

// this is an include only WP file
if ( !defined( 'ABSPATH' ) ) {
    die;
}
?>

<div id="sidebar-container">
	<?php 
global  $ddp_fs ;

if ( !$ddp_fs->is_registered() && !$ddp_fs->is_pending_activation() ) {
    ?>
		<div class="sidebarrow optin">
			<h3><span class="dashicons dashicons-warning"></span>
				<?php 
    esc_html_e( 'Help us improve!', 'delete-duplicate-posts' );
    ?></h3>
			<p>
				<?php 
    esc_html_e( 'Opt-in to our security and feature updates notifications, and non-sensitive diagnostic tracking.', 'delete-duplicate-posts' );
    ?>
			</p>
			<a href="javascript:;" class="button button-secondary" onclick="cp_ddp_freemius_opt_in(this)" data-opt="yes"><?php 
    esc_html_e( 'Click here to opt in.', 'delete-duplicate-posts' );
    ?></a>
			<div id="cp-ddp-opt-spin" class="spinner"></div><input type="hidden" id="cp-ddp-freemius-opt-nonce" value="<?php 
    echo  esc_attr( wp_create_nonce( 'cp-ddp-freemius-opt' ) ) ;
    ?>" />
		</div>
		<?php 
}

$my_current_user = wp_get_current_user();
$ddp_deleted_duplicates = get_option( 'ddp_deleted_duplicates' );

if ( $ddp_deleted_duplicates ) {
    ?>
		<div class="sidebarrow">
			<h3>
				<?php 
    printf(
        /* translators: %s: Number of deleted posts */
        esc_html__( '%s duplicates deleted!', 'delete-duplicate-posts' ),
        esc_html( number_format_i18n( $ddp_deleted_duplicates ) )
    );
    ?>
			</h3>
		</div>
		<?php 
}

?>

	<div class="sidebarrow">
		<p class="warning">
			<?php 
esc_html_e( 'We recommend you always make a backup before running this tool.', 'delete-duplicate-posts' );
?>
		</p>
	</div>

	<div class="sidebarrow">
		<div class="newsletter">
			<h3>Stay Ahead with Our Newsletter</h3>
			<p>Sign up to receive the latest tips and updates directly to your inbox. Ensure your WordPress site remains efficient and duplicate-free!</p>
			<form class="ml-block-form" action="https://assets.mailerlite.com/jsonp/16490/forms/106309157552916248/subscribe" data-code="" method="post" target="_blank">
				<input type="text" class="form-control" data-inputmask="" name="fields[name]" placeholder="Name" autocomplete="given-name" value="<?php 
echo  esc_html( $my_current_user->display_name ) ;
?>" required="required">
				<input type="email" class="form-control" data-inputmask="" name="fields[email]" placeholder="Email" autocomplete="email" value="<?php 
echo  esc_html( $my_current_user->user_email ) ;
?>" required="required">
				<input type="hidden" name="fields[signupsource]" value="PluginInstall">
				<input type="hidden" name="ml-submit" value="1">
				<input type="hidden" name="anticsrf" value="true">
				<button type="submit" class="button button-primary button-small">Subscribe</button>

			</form>
			<p class="ppolicy">You can unsubscribe anytime. For more details, review our <a href="https://cleverplugins.com/privacy-policy/" target="_blank" class="privacy-policy" rel="noopener">Privacy Policy</a>.</p>
		</div>
	</div>



	<?php 
$display_promotion = true;

if ( $display_promotion ) {
    ?>
		<div class="sidebarrow ddppro">
			<h3><span class="dashicons dashicons-star-filled"></span> DDP Pro <span class="dashicons dashicons-star-filled"></span></h3>
			<ul class="linklist">
				<li><strong>301 Redirects for Deleted Posts:</strong> Seamlessly redirect deleted posts to original pages.</li>
				<li><strong>Search by Duplicate Post Meta:</strong> Advanced search options for identifying duplicates based on post metadata.</li>
				<li><strong>Automatic Deletion:</strong> Automatic management of duplicate posts.</li>
				<li><strong>Email Notifications:</strong> Alerts when duplicates are found and removed.</li>
				<li><strong>Search Any Post Status:</strong> Deeper search capabilities, including unpublished posts.</li>
				<li><strong>Filter and Delete Unpublished Duplicates:</strong> Proactively manage and prevent unseen duplicates.</li>
				<li><strong>WooCommerce Compatibility:</strong> Look for and delete duplicate products with same SKUs.</li>
				<li><strong>No ads</strong> - Support the developer ;-)</li>
			</ul>
			<p>Annual: $24.99 <del>$29.99</del> /year</p>
			<?php 
    $target_url = 'https://checkout.freemius.com/mode/dialog/plugin/925/plan/9473/licenses/1/?billing_cycle=annually';
    $lifetime_url = 'https://checkout.freemius.com/mode/dialog/plugin/925/plan/9473/licenses/1/?billing_cycle=lifetime';
    // if $my_current_user->user_email is set, then add it as a pararmeter to the URL, &user_email= using add_query_arg()
    
    if ( $my_current_user->user_email ) {
        $target_url = add_query_arg( 'user_email', $my_current_user->user_email, $target_url );
        $lifetime_url = add_query_arg( 'user_email', $my_current_user->user_email, $lifetime_url );
    }
    
    ?>
				
			<a href="<?php 
    echo  esc_url( $target_url ) ;
    ?>" class="button button-primary button-hero" id="ddpprobutton" target="_blank"><?php 
    esc_html_e( 'Click here', 'delete-duplicate-posts' );
    ?></a>
			<p>
				<center><em>Or get <a href="<?php 
    echo  esc_url( $lifetime_url ) ;
    ?>" target="_blank">a lifetime license for only $49.99</a></em> - You can transfer your license to other websites.</center>
			</p>
			<div class="moneybackguarantee">
				<p><strong>Money Back Guarantee!</strong></p>
				<p>You are fully protected by our 100% Money Back Guarantee. If during the next 30 days you experience an issue that makes the plugin unusable and we are unable to resolve it, we'll happily consider offering a full refund of your money.</p>
			</div>
		</div><!-- .sidebarrow -->

		<?php 
}

?>

	

	<div class="sidebarrow">
		<h3><?php 
esc_html_e( 'Our other plugins', 'delete-duplicate-posts' );
?></h3>
		<a href="https://wpsecurityninja.com" target="_blank" style="float: right;" rel="noopener"><img src="<?php 
echo  esc_url( plugin_dir_url( __FILE__ ) . 'images/security-ninja-logo.png' ) ;
?>" alt="Visit wpsecurityninja.com" class="logo"></a>

		<strong>WordPress Security made easy</strong>
		<p>Complete WordPress site protection with firewall, malware scanner, scheduled scans, security tests and much more - all you need to keep your website secure. Free trial.</p>

		<p><a href="https://wpsecurityninja.com/" target="_blank" rel="noopener" class="button button-primary">Visit wpsecurityninja.com</a></p>
		<br />
		<a href="https://cleverplugins.com" target="_blank" style="float: right;" rel="noopener"><img src="<?php 
echo  esc_url( plugin_dir_url( __FILE__ ) . 'images/seoboosterlogo.png' ) ;
?>" alt="Visit cleverplugins.com" class="logo"></a>
		<p>SEO Booster is a powerful tool for anyone serious about SEO. <a href="https://wordpress.org/plugins/seo-booster/" target="_blank" rel="noopener">wordpress.org/plugins/seo-booster/</a><br />
		<p><a href="https://cleverplugins.com/" target="_blank" rel="noopener" class="button button-primary">Visit cleverplugins.com</a></p>

	</div><!-- .sidebarrow -->
	<div class="sidebarrow">
		<h3>Need help?</h3>
		<p>Email support only for pro customers.</p>
		<p>Free users: <a href="https://wordpress.org/support/plugin/delete-duplicate-posts/" target="_blank" rel="noopener"><?php 
esc_html_e( 'Support Forum on WordPress.org', 'delete-duplicate-posts' );
?></a></p>
		<form method="post" id="ddp_reactivate">
			<?php 
wp_nonce_field( 'ddp_reactivate_nonce' );
?>
			<input class="button button-secondary button-small" type="submit" name="ddp_reactivate" value="<?php 
esc_html_e( 'Recreate Databases', 'delete-duplicate-posts' );
?>" />
		</form>
	</div><!-- .sidebarrow -->
</div><?php 