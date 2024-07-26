<tr valign="top" id="product_dimensions">
	<th scope="row" class="titledesc">
		<?php printf( __( 'Default Product Dimensions (%s)', 'woocommerce-shipping-usps' ), get_option( 'woocommerce_dimension_unit' ) ); ?>

		<img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( __( 'These dimensions (LxWxH) will be used for products that do not have dimensions set.', 'woocommerce-shipping-usps' ) ); ?>" src="<?php echo esc_attr( WC()->plugin_url() . '/assets/images/help.png' ); ?>" height="16" width="16"/>
	</th>
	<td class="forminp">
		<input placeholder="1" class="input-text wc_input_decimal" style="width:calc(390px/3);" type="text" name="default_product_length" value="<?php echo esc_attr( $this->product_dimensions[0] ); ?>"/>
		<input placeholder="1" class="input-text wc_input_decimal" style="width:calc(390px/3);" type="text" name="default_product_width" value="<?php echo esc_attr( $this->product_dimensions[1] ); ?>"/>
		<input placeholder="1" class="input-text wc_input_decimal last" style="width:calc(390px/3);" type="text" name="default_product_height" value="<?php echo esc_attr( $this->product_dimensions[2] ); ?>"/>
	</td>
</tr>