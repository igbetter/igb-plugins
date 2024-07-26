<div class="woocommerce-shipping-debug-info-container">
	<div>
		<?php echo esc_html( sprintf( __( '%s debug mode is on - to hide these messages, turn debug mode off in the settings.', 'woocommerce-shipping-usps' ), $service_name ) ); ?>
	</div>
	<div class="woocommerce-shipping-debug-info-accordion">
		<h1>
			<?php echo esc_html( sprintf( __( '%s debug info', 'woocommerce-shipping-usps' ), $service_name ) ); ?>
		</h1>
		<div>
		<?php foreach( $requests as $request ) : ?>
			<h2>Request</h2>
			<pre><?php echo esc_html( $request ); ?></pre>
		<?php endforeach; ?>
		<?php foreach( $responses as $response ) : ?>
			<h2>Response</h2>
			<pre><?php echo esc_html( $response ); ?></pre>
		<?php endforeach; ?>
			<h2>Debug notes</h2>
			<div>
				<?php
				foreach ( $notes as $note ) {
					echo '<pre>' . wp_kses_post( $note ) . '</pre>';
				}
				?>
			</div>
		</div>
	</div>
</div>
