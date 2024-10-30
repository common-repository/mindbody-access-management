<?php
/**
 * Access Container
 *
 * Template for access shortcode.
 *
 * @package MZMBOACCESS
 */

use MZoo\MzMboAccess\Core as Core;
use MZoo\MzMindbody as MZ;
?>
<div id="mzAccessContainer">

<?php
if ( false === $data->logged_in ) :
	include 'login_form.php';
else :
	?>
	<p class="mbo-user">Hi, <?php echo esc_html( $data->client_name ); ?>.</p>
	<?php
	if ( ( ! empty( $data->atts['level_1_redirect'] ) || ! empty( $data->atts['level_2_redirect'] ) ) ) {
		// this is being used as a redirect login form so just echo content if it exists.
		echo $data->content;

		?>
		<div class="row" style="margin:.5em;">
			<span class="btn btn-primary btn-xs" id="MBOLogout" target="_blank"><?php echo $data->logout; ?></span>
		</div>
		<?php
	} else {
		if ( ! $data->has_access ) {
			?>
			<div class="alert alert-warning">
			<?php echo '<strong>' . $data->atts['denied_message'] . '</strong>:'; ?>
				<ul>
			<?php
			foreach ( $data->required_access_levels as $level ) {
				echo '<li>' . $level['access_level_name'] . '</li>';
			}
			?>
				</ul>
			</div>
			<?php
		} else {
			echo $data->content;
		}
		?>
			<div class="row" style="margin:.5em;">
				<div class="col-12">
		<?php if ( ! empty( $data->manage_on_mbo ) ) : ?>
					<?php
					$url  = 'https://clients.mindbodyonline.com';
					$url .= '/ws.asp?&amp;sLoc=1&studioid=' . esc_html( $data->site_id );
					?>
					<a style="text-decoration:none;" href="<?php echo esc_html( $url ); ?>" 
						class="btn btn-primary btn-xs" id="MBOSite" target="_blank">
						<?php echo $data->manage_on_mbo; ?>
					</a>
		<?php endif; ?>
					<span class="btn btn-primary btn-xs" id="MBOLogout" target="_blank">
						<?php echo $data->logout; ?>
					</span>
				</div>
			</div>
	<?php } // End not a redirect login form. ?>
<?php endif; ?>

</div>
<div style="display:none">
<?php require 'login_form.php'; // for use in logout routine. ?>
</div>
