<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

?>
<div class="frmcal-calendar">
	<div class="frmcal-row-headings">
		<?php
		for ( $i = $week_begins; $i < $week_begins + 7; $i++ ) :
			?>
				<div>
					<div class="frmcal-day-name">
						<span class="frmcal-hide-on-desktop"><?php echo isset( $day_names[ $i ] ) ? esc_html( substr( $day_names[ $i ], 0, 3 ) ) . ' ' : ''; ?></span><span class="frmcal-hide-on-mobile"><?php echo isset( $day_names[ $i ] ) ? esc_html( $day_names[ $i ] ) . ' ' : ''; ?></span>
					</div>
				</div>
			<?php
		endfor;
		?>
	</div>

	<?php
	for ( $i = $week_begins; $i < $maxday + $startday; $i++ ) {
		$pos    = $i % 7;
		$end_tr = false;
		$day    = $i - $startday + 1;
		$date   = $year . '-' . $month . '-' . $day;
		if ( $pos == $week_begins ) {
			$week_start_date = $i < $startday ? $year . '-' . $prev_month . '-' . ( $prev_month_startday + $i ) : $date;
			echo "<div>\n";
		}

		// add classes for the day
		$day_class  = 'frmcal-day';
		$day_class .= $i < $startday ? ' frm-inactive' : '';
		// check for today
		if ( isset( $today ) && $day == $today ) {
			$day_class .= ' frmcal-today';
		}

		if ( 0 == $pos || 6 == $pos ) {
			$day_class .= ' frmcal-week-end';
		}

		?>
	<div <?php echo ! empty( $day_class ) ? 'class="' . esc_attr( $day_class ) . '"' : ''; ?> data-week-start-date="<?php echo esc_attr( $week_start_date ); ?>" data-date="<?php echo esc_attr( $date ); ?>" ><div class="frmcal_date">
			<?php
			unset( $day_class );

			if ( $i >= $startday ) {
				?><div class="frmcal_num"><?php echo esc_html( $day ); ?></div></div> <div class="frmcal-content">
				<?php
				if ( ! empty( $daily_entries[ $i ] ) ) {

					// Set up current entry date for [event_date] shortcode
					$current_entry_date = $year . '-' . $month . '-' . ( $day < 10 ? '0' . $day : $day );

					$pass_atts = array(
						'event_date' => $current_entry_date,
						'day_count'  => count( $daily_entries[ $i ] ),
						'view'       => $view,
					);
					do_action( 'frm_before_day_content', $pass_atts );

					$count = 0;
					foreach ( $daily_entries[ $i ] as $entry ) {
						++$count;
						$popup = new FrmViewsCalendarPopupHelper( $view, $entry );

						if ( ! isset( $used_entries[ $entry->id ] ) ) {

							if ( $entry->is_multiday ) :
								?>
								<div class="frmcal-daily-event frmcal-hide-on-desktop"></div><?php // Mobile bullet event ?>
								<div class="frmcal-multi-day-event frmcal-hide frmcal-hide-on-mobile" data-entry-id="<?php echo (int) $entry->id; ?>" data-start-day="<?php echo (int) $pos - $week_begins; ?>" data-days-count="<?php echo (int) $entry->event_length; ?>" data-start-date="<?php echo esc_attr( $entry->event_start_date ); ?>" data-end-date="<?php echo esc_attr( $entry->event_end_date ); ?>">
								<?php
							endif;

							// switch [event_date] to [calendar_date] so it can be replaced on each individual date instead of each entry
							$new_content  = str_replace( array( '[event_date]', '[event_date ' ), array( '[calendar_date]', '[calendar_date ' ), $new_content );
							$this_content = apply_filters(
								'frm_display_entry_content',
								$new_content,
								$entry,
								$shortcodes,
								$view,
								'all',
								'',
								array(
									'event_date' => $current_entry_date,
								)
							);

							$used_entries[ $entry->id ] = $this_content;
							FrmProContent::replace_entry_position_shortcode( compact( 'entry', 'view' ), compact( 'count' ), $this_content );
							?>
							<div class="frmcal-daily-event" <?php echo $popup->get_attr_data( $entry->is_multiday ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> >
							<div class="frmcal-hide-on-mobile frmcal-event-content"><?php echo FrmProContent::replace_calendar_date_shortcode( $this_content, $current_entry_date ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div>
							<?php

							if ( $entry->is_multiday ) :
								?></div>
								<?php
							endif;

						} else {
							// Handle multi-day events for mobile only
							// switch [event_date] to [calendar_date] so it can be replaced on each individual date instead of each entry
							$new_content  = str_replace( array( '[event_date]', '[event_date ' ), array( '[calendar_date]', '[calendar_date ' ), $new_content );
							$this_content = apply_filters(
								'frm_display_entry_content',
								$new_content,
								$entry,
								$shortcodes,
								$view,
								'all',
								'',
								array(
									'event_date' => $current_entry_date,
								)
							);

							$used_entries[ $entry->id ] = $this_content;
							FrmProContent::replace_entry_position_shortcode( compact( 'entry', 'view' ), compact( 'count' ), $this_content );
							?>
							<div class="frmcal-daily-event frmcal-hide-on-desktop">
								<div class="frmcal-hide-on-mobile frmcal-event-content">
									<?php echo FrmProContent::replace_calendar_date_shortcode( $this_content, $current_entry_date ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							</div>
							<?php
						}

						unset( $this_content );
					}

					do_action( 'frm_after_day_content', $pass_atts );
				}
			} elseif ( $i < $startday ) {
				?><div class="frmcal_num frm-inactive"><?php echo esc_html( $prev_month_startday + $i ); ?></div>
				<?php
			}
			?></div>
	</div>
		<?php
		if ( $pos == $week_ends ) {
			$end_tr = true;
			echo "</div>\n";
		}
	}

	++$pos;
	if ( 7 == $pos ) {
		$pos = 0;
	}
	if ( $pos != $week_begins ) {
		$next_month_day = 1;
		if ( $pos > $week_begins ) {
			$week_begins = $week_begins + 7;
		}
		for ( $e = $pos; $e < $week_begins; $e++ ) {
			$day_class = '';
			if ( 6 == $e || 7 == $e ) {
				$day_class = ' class="frmcal-week-end"';
			}
			echo '<div' . $day_class // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						. '><div class="frmcal_date"><div class="frmcal_num frm-inactive">' . (int) $next_month_day . "</div></div></div>\n";
			$next_month_day++;
		}
	}

	if ( ! $end_tr ) {
		echo '</div>';
	}
	?>
</div>

