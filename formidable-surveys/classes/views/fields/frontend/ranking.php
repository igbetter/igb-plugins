<?php
/**
 * Show the Ranking Field on the front-end.
 *
 * @package FrmSurveys
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

use FrmSurveys\controllers\RankingController;
if ( $field['options'] ) :
	if ( FrmAppHelper::is_form_builder_page() ) {
		FrmAppHelper::include_svg();
	}
	$option_index      = 0;
	$answers_limit     = isset( $field['limit_selections'] ) && 0 !== $field['limit_selections'] && isset( $field['answers_limit'] ) ? $field['answers_limit'] : 0;
	$is_image_option   = isset( $field['image_options'] ) && 0 !== (int) $field['image_options'];
	$active_selections = array();

	// Remove the attribute NAME from SELECT tag in builder page as it conflicts with "add option" script. It will make sure that the Ranking Field options are taken as checkbox/radio options rather than dropdowns/select option.
	$select_name_attr = FrmAppHelper::is_form_builder_page() || FrmAppHelper::doing_ajax() ? '' : 'name=' . $field_name . '[order][]';

	if ( isset( $field['randomize_options'] ) && 1 === (int) $field['randomize_options'] ) {
		shuffle( $field['options'] );
	}

	$options = ! empty( $field['value'] ) ? RankingController::build_active_options( $field ) : $field['options'];
	?>

	<input type="hidden" class="frm-ranking-answers-limit" value="<?php echo (int) $answers_limit; ?>"/>
	<?php
	foreach ( $options as $opt_key => $opt ) :

		$field_val        = FrmFieldsHelper::get_value_from_array( $opt, $opt_key, $field );
		$option_label     = FrmFieldsHelper::get_label_from_array( $opt, $opt_key, $field );
		$classname        = 'frm-ranking-field-option frm-flex-box frm-justify-between frm-items-center';
		$classname       .= RankingController::is_option_active( $field, $opt ) ? ' frm-ranking-draggable-option' : ' frm-no-drag';
		$has_image        = isset( $opt['image'] ) && 0 !== (int) $opt['image'];
		$options_count    = 0 !== (int) $answers_limit ? (int) $answers_limit : count( $options );
		$active_selection = null !== RankingController::get_active_position( $field, $opt ) ? ( RankingController::get_active_position( $field, $opt ) + 1 ) : null;

		if ( $is_image_option ) {
			$return     = array( 'label' );
			$image      = FrmProImages::single_option_details( compact( 'opt', 'opt_key', 'field', 'return' ) );
			$classname .= ' frm_image_option';
		};

		if ( null !== $active_selection ) {
			$active_selections[] = $active_selection;
		}

		?>

		<div class="<?php echo esc_attr( $classname ); ?>" id="<?php echo esc_attr( FrmFieldsHelper::get_checkbox_id( $field, $opt_key ) ); ?>">
			<span>
				<select <?php echo esc_attr( $select_name_attr ); ?> class="frm-ranking-position">
					<option value="0">&mdash;</option>
					<?php for ( $i = 1; $i <= $options_count;  $i++ ) : ?>
						<option <?php echo $active_selection === $i ? 'selected' : ''; ?> value="<?php echo (int) $i; ?>" > <?php echo (int) $i; ?> </option>
					<?php endfor; ?>
				</select>
				<input class="frm-ranking-field-order" type="hidden" data-field-type="ranking" name="<?php echo esc_attr( $field_name ); ?>[]" id="<?php echo esc_attr( $html_id ); ?>-<?php echo esc_attr( $opt_key ); ?>" value="<?php echo esc_attr( $field_val ); ?>" />

				<?php if ( $is_image_option ) : ?>
					<span class="frm_image_option_container frm_label_with_image">

						<?php if ( $has_image ) : ?>
							<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $option_label ); ?>">
						<?php else : ?>
							<div class="frm_empty_url">
								<?php echo FrmAppHelper::kses( FrmProImages::get_image_icon_markup(), 'all' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php endif; ?>

						<span class="frm_text_label_for_image"><span class="frm_text_label_for_image_inner"><?php echo esc_html( $option_label ); ?></span></span>
					</span>
					<?php
					else :
						echo esc_html( $option_label );
					endif;
					?>
			</span>

			<?php echo FrmAppHelper::kses( RankingController::get_drag_icon(), 'all' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		++$option_index;
	endforeach;
	if ( ! empty( $active_selections ) ) :
		?>
		<input type="hidden" class="frm-ranking-active-selections" value="<?php echo esc_html( implode( ',', $active_selections ) ); ?>"/>
		<?php
	endif;
endif;
