<h3 class="frm_add_autoresponder_link <?php echo esc_attr( $is_active ? ' frm_hidden' : '' ); ?>" id="autoresponder_link_<?php echo esc_attr( $action_key ); ?>" >
	<a href="#" class="frm_add_form_autoresponder" data-emailkey="<?php echo esc_attr( $action_key ); ?>" id="email_autoresponder_<?php echo esc_attr( $action_key ); ?>" >
		<?php esc_html_e( 'Setup Automation', 'formidable-autoresponder' ); ?>
	</a>
</h3>

<div class="frm_autoresponder_rows <?php echo esc_attr( $is_active ? '' : ' frm_hidden' ); ?>">
	<input type="hidden" class="frm-autoresponder-is-active" name="<?php echo esc_attr( $input_name ); ?>[is_active]" value="<?php echo esc_attr( $autoresponder['is_active'] ); ?>" />
	<h3><?php esc_html_e( 'Automation', 'formidable-autoresponder' ); ?> <a href="#" class="frm_icon_font frm_delete_icon frm_remove_autoresponder"> </a></h3>
	<div id="frm_autoresponder_row_<?php echo esc_attr( $action_key ); ?>">
		<select class="frm-autoresponder-trigger-select" name="<?php echo esc_attr( $input_name ); ?>[do_default_trigger]">
			<option value="no" <?php selected( 'no', $autoresponder['do_default_trigger'] ); ?>><?php esc_html_e( 'Ignore', 'formidable-autoresponder' ); ?></option>
			<option value="yes" <?php selected( 'yes', $autoresponder['do_default_trigger'] ); ?>><?php esc_html_e( 'Respect', 'formidable-autoresponder' ); ?></option>
		</select>	
		<span class="frm-autoresponder-trigger-verbage frm-inline-select">
			<?php
			/* translators: %s: Setting name */
			printf( esc_html__( 'the "%s" setting above', 'formidable-autoresponder' ), '<em>' . esc_html__( 'Trigger this action after', 'formidable-autoresponder' ) . '</em>' );
			?>
		</span>
		<p class="frm_autoresponder_row">
			<?php esc_html_e( 'Send this notification', 'formidable-autoresponder' ); ?>
			<input type="number" name="<?php echo esc_attr( $input_name ); ?>[send_interval]" value="<?php echo esc_attr( empty( $autoresponder['send_interval'] ) ? '1' : $autoresponder['send_interval'] ); ?>" min="1" />
			<select name="<?php echo esc_attr( $input_name ); ?>[send_unit]">
				<?php foreach ( $time_units as $unit => $label ) : ?>
					<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $autoresponder['send_unit'], $unit ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>	
			</select>	
			<select class="frm-autoresponder-before-after" name="<?php echo esc_attr( $input_name ); ?>[send_before_after]">
				<?php
				foreach ( array(
					'after'  => __( 'After', 'formidable-autoresponder' ),
					'before' => __( 'Before', 'formidable-autoresponder' ),
				) as $unit => $label ) :
					?>
					<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $autoresponder['send_before_after'], $unit ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>	
			</select>
			<select class="frm_autoresponder_date_field" name="<?php echo esc_attr( $input_name ); ?>[send_date]">
				<option value="">&mdash; <?php esc_html_e( 'Select Field', 'formidable-autoresponder' ); ?> &mdash;</option>
				<option value="create" <?php selected( 'create', $autoresponder['send_date'] ); ?>><?php esc_html_e( 'Create Date', 'formidable-autoresponder' ); ?></option>
				<option value="update" <?php selected( 'update', $autoresponder['send_date'] ); ?>><?php esc_html_e( 'Update Date', 'formidable-autoresponder' ); ?></option>
				<?php foreach ( $date_fields as $field ) : ?>
					<option value="<?php echo esc_attr( $field['id'] ); ?>" <?php selected( $autoresponder['send_date'], $field['id'] ); ?>>
						<?php echo esc_html( $field['name'] ); ?>
					</option>
					<?php foreach ( $time_fields as $time ) : ?>
						<option value="<?php echo esc_attr( $field['id'] . '-' . $time['id'] ); ?>" <?php selected( $autoresponder['send_date'], $field['id'] . '-' . $time['id'] ); ?>>
							<?php echo esc_html( $field['name'] . ' + ' . $time['name'] ); ?>
						</option>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="frm_autoresponder_row">
			<label>
				<input type="checkbox" class="frm-autoresponder-send-after" name="<?php echo esc_attr( $input_name ); ?>[send_after]" value="1" <?php checked( 1, $autoresponder['send_after'] ); ?> />
				<?php esc_html_e( '...and then every', 'formidable-autoresponder' ); ?> 
			</label>	
			<span class="frm-autoresponder-send-after-meta <?php echo esc_attr( $autoresponder['send_after'] ? '' : 'frm_hidden' ); ?>">
				<?php if ( $has_number_field ) : ?>
					<input type="radio" name="<?php echo esc_attr( $input_name ); ?>[send_after_interval_type]" value="number" <?php checked( true, empty( $autoresponder['send_after_interval_type'] ) || ( 'number' == $autoresponder['send_after_interval_type'] ) ); ?> />
					<input type="number" class="frm-autoresponder-send-after-interval" name="<?php echo esc_attr( $input_name ); ?>[send_after_interval]" value="<?php echo esc_attr( ( empty( $autoresponder['send_after_interval'] ) || ( 'field' === $autoresponder['send_after_interval_type'] ) ) ? '1' : $autoresponder['send_after_interval'] ); ?>" />
					<?php esc_html_e( 'or', 'formidable-autoresponder' ); ?>
					<input type="radio" name="<?php echo esc_attr( $input_name ); ?>[send_after_interval_type]" value="field" <?php checked( 'field', $autoresponder['send_after_interval_type'] ); ?> />
					<select class="frm-autoresponder-send-after-interval-field" name="<?php echo esc_attr( $input_name ); ?>[send_after_interval_field]">
						<option value="">&mdash; <?php esc_html_e( 'Select Field', 'formidable-autoresponder' ); ?> &mdash;</option>
						<?php
						foreach ( $fields as $field ) :
							if ( 'number' === $field['type'] ) :
								?>
							<option value="<?php echo esc_attr( $field['id'] ); ?>" <?php selected( true, 'field' === $autoresponder['send_after_interval_type'] && $autoresponder['send_after_interval_field'] == $field['id'] ); ?>>
								<?php echo esc_html( $field['name'] ); ?>
							</option>
							<?php endif; ?>
						<?php endforeach; ?>	
					</select>
				<?php else : ?>	
					<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>[send_after_interval_type]" value="number" <?php checked( true, empty( $autoresponder['send_after_interval_type'] ) || ( 'number' == $autoresponder['send_after_interval_type'] ) ); ?> />
					<input type="number" class="frm-autoresponder-send-after-interval" name="<?php echo esc_attr( $input_name ); ?>[send_after_interval]" value="<?php echo esc_attr( empty( $autoresponder['send_after_interval'] ) ? '0' : $autoresponder['send_after_interval'] ); ?>" />
				<?php endif; ?>	
				<select name="<?php echo esc_attr( $input_name ); ?>[send_after_unit]">
					<?php foreach ( $time_units as $unit => $label ) : ?>
						<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $autoresponder['send_after_unit'], $unit ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>	
				</select>	
				<?php esc_html_e( 'after that', 'formidable-autoresponder' ); ?>
			</span>
		</p>
		<p class="frm_autoresponder_row">
			<label>
				<input type="checkbox" class="frm-autoresponder-send-after-limit" name="<?php echo esc_attr( $input_name ); ?>[send_after_limit]" value="1" <?php checked( 1, $autoresponder['send_after_limit'] ); ?> />
				<?php esc_html_e( '...a maximum of', 'formidable-autoresponder' ); ?> 
			</label>	
			<span class="frm-autoresponder-send-after-meta <?php echo esc_attr( $autoresponder['send_after_limit'] ? '' : 'frm_hidden' ); ?>">
				<input type="number" name="<?php echo esc_attr( $input_name ); ?>[send_after_count]" value="<?php echo esc_attr( empty( $autoresponder['send_after_count'] ) ? '1' : $autoresponder['send_after_count'] ); ?>" />
				<?php esc_html_e( 'times', 'formidable-autoresponder' ); ?>
			</span>
		</p>
		<div>
			<label title="<?php echo esc_attr( __( 'Will write some debug messages out to a log file specific to this action.  Refreshing this page will load a list of whatever log files have been created for this action. The log files start fresh every day.  Note, the times in the log files are in UTC +0 timezone.', 'formidable-autoresponder' ) ); ?>" class="frm_help" >
				<input type="checkbox" class="frm-autoresponder-debug" id="frm-autoresponder-debug-<?php echo esc_attr( $action_key ); ?>" name="<?php echo esc_attr( $input_name ); ?>[debug]" value="1" <?php checked( 1, $autoresponder['debug'] ); ?> />
				<?php esc_html_e( 'Turn debug on', 'formidable-autoresponder' ); ?>
			</label>
			<div class="frm-autoresponder-debug-detail <?php echo esc_attr( $autoresponder['debug'] ) ? '' : 'frm_hidden'; ?>" id="frm-autoresponder-debug-urls-<?php echo esc_attr( $action_key ); ?>" style="padding:1em">
				<?php if ( empty( $debug_urls ) ) : ?>
					<?php esc_html_e( 'No log files to show yet.  Go fill out the form!', 'formidable-autoresponder' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Latest log files for this action:', 'formidable-autoresponder' ); ?>
					<ul>
						<?php foreach ( $debug_urls as $index => $url ) : ?>
							<li <?php echo ( $index >= $debug_urls_more ) ? 'class="frm-autoresponder-more frm_hidden"' : ''; ?>>
								<a href="<?php echo esc_attr( $url ); ?>" target="_blank">
									<?php echo esc_html( preg_replace( '#^.*=([^=]+)$#', '$1', basename( $url ) ) ); ?>
								</a>
								<a href="#" class="frm_icon_font frm_delete_icon frm_remove_autoresponder_log" data-deleteconfirm="<?php esc_attr_e( 'Are you sure you want to delete that log?', 'formdiable-autoresponder' ); ?>"> </a>
							</li>
							<?php if ( $index == $debug_urls_more ) : ?>
								<li class="frm-autoresponder-toggle frm-autoresponder-toggle-more">
									<a href="#">
										<?php
										/* translators: %d: count */
										echo esc_html( sprintf( __( '+ View %d More', 'formdiable-autoresponder' ), count( $debug_urls ) - $debug_urls_more ) );
										?>
									</a>
								</li>
								<li class="frm-autoresponder-toggle frm-autoresponder-toggle-less frm-autoresponder-more frm_hidden">
									<a href="#"><?php echo esc_html__( '- View Less', 'formdiable-autoresponder' ); ?></a>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<div class="frm-autoresponder-queue">
				<h3>
					<?php esc_html_e( 'Current Queue', 'formidable-autoresponder' ); ?>
					<span class="coming-soon frm_hidden">
						<span href="#" class="frm-autoresponder-refresh dashicons dashicons-update"> </span>
						<span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php echo esc_attr( __( 'Clear and then refresh this queue.  Please note, this will start by removing all items from the queue and then it will go through all entries for this form and recalculate any form action automation.  This is a good action to take if you have made changes to your automation and wish to have those changes take effect for existing entries.', 'formidable-autoresponder' ) ); ?>"></span>
					</span>
				</h3>
				<?php if ( empty( $queue ) ) : ?>
					<p class="description"><?php esc_html_e( 'Empty', 'formidable-autoresponder' ); ?></p>
				<?php else : ?>
					<table class="table-striped" style="width:100%">
						<thead>
							<tr>
								<th style="width:75px"w><?php esc_html_e( 'Entry', 'formidable-autoresponder' ); ?></th>
								<th><?php esc_html_e( 'When', 'formidable-autoresponder' ); ?></th>
								<th style="width:50px !important">&nbsp;</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $queue as $index => $event ) : ?>
							<tr <?php echo ( $index >= $queue_more ) ? 'class="frm-autoresponder-more frm_hidden"' : ''; ?>>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=formidable-entries&frm_action=show&id=' . $event['entry_id'] ) ); ?>">
										#<?php echo absint( $event['entry_id'] ); ?>
									</a>
								</td>
								<td class="<?php echo ( $event['timestamp'] < strtotime( '+3 days' ) ) ? 'frm-autoresponder-debug-queue-time' : ''; ?>" data-timestamp="<?php echo esc_attr( $event['timestamp'] ); ?>">
									<?php echo esc_html( FrmAppHelper::get_localized_date( $date_format . ' ' . $time_format, $event['pretty_time'] ) ); ?>
								</td>
								<td style="width:50px !important">
									<a href="#" data-timestamp="<?php echo esc_attr( $event['timestamp'] ); ?>" data-entry-id="<?php echo esc_attr( $event['entry_id'] ); ?>" data-action-id="<?php echo esc_attr( $action_key ); ?>" class="frm_icon_font frm_delete_icon frm_remove_autoresponder_queue" data-deleteconfirm="<?php esc_attr_e( 'Are you sure you want to delete that scheduled event?', 'formdiable-autoresponder' ); ?>"> </a>
								</td>
							</tr>
							<?php if ( $index == $queue_more ) : ?>
								<tr class="frm-autoresponder-toggle frm-autoresponder-toggle-more">
									<td colspan="3">
										<a href="#">
											<?php
											/* translators: %d: count */
											echo esc_html( sprintf( __( '+ View %d More', 'formdiable-autoresponder' ), count( $queue ) - $queue_more ) );
											?>
										</a>
									</td>
								</tr>
								<tr class="frm-autoresponder-toggle frm-autoresponder-toggle-less frm-autoresponder-more frm_hidden">
									<td colspan="3">
										<a href="#"><?php esc_html_e( '- View Less', 'formdiable-autoresponder' ); ?></a>
									</td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
