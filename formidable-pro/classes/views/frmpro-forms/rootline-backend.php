<?php
/**
 * View for rootline in form builder
 *
 * @since 6.9
 * @package FormidablePro
 *
 * @var FrmProRootline $this          Rootline object.
 * @var array          $wrapper_attrs Wrapper element attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$titles = array_values( $this->titles );
?>
<div<?php FrmAppHelper::array_to_html_params( $wrapper_attrs, true ); ?>>
	<ul>
		<?php
		$i = 0;
		foreach ( $this->page_breaks as $page_break ) {
			if ( count( $this->page_breaks ) === $i + 1 ) {
				?>
				<li class="frm-rootline-item-more frm_hidden">
					<span class="frm-rootline-number">&hellip;</span>
				</li>
				<?php
			}
			?>
			<li>
				<?php
				if ( 'rootline' === $this->type && $this->show_numbers ) {
					echo '<span class="frm-rootline-number">' . intval( $i + 1 ) . '</span>';
				}
				if ( $this->show_titles && ! empty( $titles[ $i ] ) ) {
					echo '<span class="frm-rootline-title">' . esc_html( $titles[ $i ] ) . '</span>';
				}
				?>
			</li>
			<?php
			++$i;
		}
		?>
	</ul>

	<div class="frm-progress-bar-numbers frm_clearfix">
		<div class="frm_percent_complete"><?php esc_html_e( '0% complete', 'formidable-pro' ); ?></div>
		<div class="frm_pages_complete">
			<?php
			// translators: number of pages.
			printf(
				esc_html__( '1 of %s', 'formidable-pro' ),
				'<span class="frm_pages_total">' . ( count( $this->page_breaks ) + 1 ) . '</span>'
			);
			?>
		</div>
	</div>
</div>
