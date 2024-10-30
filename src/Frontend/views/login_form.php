<?php
/**
 * Login Form.
 *
 * Template for login form.
 *
 * @package MZMBOACCESS
 */

use MZoo\MzMboAccess\Core as Core;

?>
	<div id="mzLogInContainer">

		<form role="form" class="form-group" style="margin:1em 0;" data-async id="mzLogIn" method="POST">

			<h3><?php echo esc_html( $data->atts['call_to_action'] ); ?></h3>

			<input type="hidden" name="nonce" value="<?php echo esc_html( $data->login_nonce ); ?>"/>

			<input type="hidden" name="access_permissions_nonce" value="<?php echo esc_html( $data->check_access_permissions_nonce ); ?>"/>

			<input type="hidden" name="siteID" value="<?php echo esc_html( $data->site_id ); ?>" />

			<div class="row">

				<div class="form-group col-xs-8 col-sm-6" style="margin:0.5em 0;">

					<label for="username">Email</label>

					<input type="email" size="10" class="form-control col-6" id="email" name="email" placeholder="<?php echo esc_html( $data->email ); ?>" required>

				</div>

			</div>

			<div class="row">

				<div class="form-group col-xs-8 col-sm-6" style="margin:0.5em 0;">

					<label for="password">Password</label>

					<input type="password" size="10" class="form-control col-6" name="password" id="password" placeholder="<?php echo esc_html( $data->password ); ?>" required>

				</div>

			</div>

			<div class="row" style="margin:0.5em 0;">

				<div class="col-12 mb-2">

					<button type="submit" class="btn btn-primary btn-xs">
						<?php echo esc_html( $data->login ); ?>
					</button>

					<?php if ( ! empty( $data->password_reset_request ) ) : ?>
						<?php
						$reset_link  = 'https://clients.mindbodyonline.com/PasswordReset';
						$reset_link .= '?&studioid=' . esc_html( $data->site_id );
						?>
					<a style="text-decoration:none;" 
						href="<?php echo esc_html( $reset_link ); ?>" 
						class="btn btn-primary btn-xs" 
						target="_blank">
						<?php echo esc_html( $data->password_reset_request ); ?>
					</a>

					<?php endif; ?>

					<?php if ( ! empty( $data->manage_on_mbo ) ) : ?>

						<?php
						$mbo_link  = 'https://clients.mindbodyonline.com/ws.asp';
						$mbo_link .= '?&amp;sLoc=1&studioid=' . esc_html( $data->site_id );
						?>
					<a href="<?php echo esc_html( $mbo_link ); ?>" 
						class="btn btn-primary btn-xs" 
						style="text-decoration:none;" 
						id="MBOSite" target="_blank">
						<?php echo esc_html( $data->manage_on_mbo ); ?>
					</a>

					<?php endif; ?>

				</div>

			</div>

		</form>

	</div>
