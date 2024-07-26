<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsContentHelper {

	/**
	 * @var array|string
	 */
	private $content;

	/**
	 * @param array|string $content the post content.
	 */
	public function __construct( $content ) {
		$this->content = $content;
		if ( is_string( $content ) && ! is_serialized( $content ) && false !== strpos( $content, '"box":' ) ) {
			$this->content = FrmAppHelper::maybe_json_decode( $content, true );
		} else {
			FrmAppHelper::unserialize_or_decode( $this->content );
		}
	}

	/**
	 * @return string
	 */
	public function get_excerpt() {
		$content = $this->content_is_an_array() ? $this->get_content_from_first_available_box() : $this->content;
		return $this->strip_tags_and_truncate( $content );
	}

	/**
	 * @param string $content
	 * @return string
	 */
	private function strip_tags_and_truncate( $content ) {
		return FrmAppHelper::truncate( strip_tags( $content ), 100 );
	}

	/**
	 * Content with a layout is encoded as an array.
	 * The index page tries to pull truncated form data to show in the columns.
	 * We need to make sure that before strip_tags and truncate are called on content, that we're handling a string.
	 * This function gets content from the first box in the array. An empty string is returned if nothing is found.
	 *
	 * @return string
	 */
	private function get_content_from_first_available_box() {
		$output = '';
		foreach ( $this->content as $box_data ) {
			if ( isset( $box_data['box'] ) && ! empty( $box_data['content'] ) ) {
				$output = $box_data['content'];
				break;
			}
		}
		return $output;
	}

	/**
	 * @return bool
	 */
	public function content_is_an_array() {
		return is_array( $this->content );
	}

	/**
	 * @return array|string
	 */
	public function get_content() {
		if ( ! is_array( $this->content ) || ! $this->box_values_are_saved_as_strings() ) {
			return $this->content;
		}
		return array_reduce(
			$this->content,
			function ( $total, $current ) {
				if ( isset( $current['box'] ) ) {
					$current['box'] = absint( $current['box'] );
				}
				$total[] = $current;
				return $total;
			},
			array()
		);
	}

	/**
	 * @return bool if true we need to convert the string values to int first. if false, the value is already an int and can be left alone.
	 */
	private function box_values_are_saved_as_strings() {
		if ( ! is_array( $this->content ) || ! isset( $this->content[0] ) || ! isset( $this->content[0]['box'] ) ) {
			// never iterate through invalid data.
			return false;
		}
		return is_string( $this->content[0]['box'] );
	}
}
