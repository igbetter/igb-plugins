<?php

namespace WooCommerce\BoxPacker\Original;

use WooCommerce\BoxPacker\Package;

/**
 * WC_Boxpack_Box class.
 */
class Box implements \WooCommerce\BoxPacker\Box {

	/**
	 * @var string ID of the box - given to packages
	 */
	private $id = '';
	/**
	 * @var float Weight of the box itself
	 */
	private $weight;
	/**
	 * @var float Max allowed weight of box + contents
	 */
	private $max_weight;
	/**
	 * @var float Outer dimension of box sent to shipper
	 */
	private $outer_height;
	/**
	 * @var float Outer dimension of box sent to shipper
	 */
	private $outer_width;
	/**
	 * @var float Outer dimension of box sent to shipper
	 */
	private $outer_length;
	/**
	 * @var float Inner dimension of box used when packing
	 */
	private $height;
	/**
	 * @var float Inner dimension of box used when packing
	 */
	private $width;
	/**
	 * @var float Inner dimension of box used when packing
	 */
	private $length;
	/**
	 * @var float Dimension is stored here if adjusted during packing
	 */
	private $packed_height;
	private $maybe_packed_height = NULL;
	/**
	 * @var float Dimension is stored here if adjusted during packing
	 */
	private $packed_width;
	private $maybe_packed_width = NULL;
	/**
	 * @var float Dimension is stored here if adjusted during packing
	 */
	private $packed_length;
	private $maybe_packed_length = NULL;
	/**
	 * @var float Volume of the box
	 */
	private $volume;
	/**
	 * @var array Valid box types which affect packing
	 */
	private $valid_types = array( 'box', 'tube', 'envelope', 'packet' );
	/**
	 * @var string This box type
	 */
	private $type = 'box';

	public function __construct( $length, $width, $height, $weight = 0.0, $max_weight = 0.0, $type = 'box' ) {
		$dimensions = array( floatval( $length ), floatval( $width ), floatval( $height ) );

		sort( $dimensions );

		$this->outer_length = $this->length = $dimensions[2];
		$this->outer_width  = $this->width = $dimensions[1];
		$this->outer_height = $this->height = $dimensions[0];
		$this->weight       = floatval( $weight );
		$this->max_weight   = floatval( $max_weight );

		if ( in_array( $type, $this->valid_types ) ) {
			$this->type = $type;
		}
	}

	/**
	 * @inerhitDoc
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @inerhitDoc
	 */
	public function set_volume( $volume ) {
		$this->volume = floatval( $volume );
	}

	/**
	 * @inerhitDoc
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * @inerhitDoc
	 */
	public function set_type( $type ) {
		if ( in_array( $type, $this->valid_types ) ) {
			$this->type = $type;
		}
	}

	/**
	 * @inerhitDoc
	 */
	public function get_max_weight() {
		return $this->max_weight;
	}

	/**
	 * @inerhitDoc
	 */
	public function set_max_weight( $weight ) {
		$this->max_weight = $weight;
	}

	/**
	 * @inerhitDoc
	 */
	public function set_inner_dimensions( $length, $width, $height ) {
		$dimensions = array( $length, $width, $height );

		sort( $dimensions );

		$this->length = $dimensions[2];
		$this->width  = $dimensions[1];
		$this->height = $dimensions[0];
	}

	/**
	 * See if an item fits into the box.
	 *
	 * @param object $item
	 *
	 * @return bool
	 */
	public function can_fit( $item ) {
		switch ( $this->type ) {
			// Tubes are designed for long thin items so see if the item meets that criteria here.
			case 'tube':
				$can_fit = $this->get_length() >= $item->get_length() && $this->get_width() >= $item->get_width() && $this->get_height() >= $item->get_height() && $item->get_volume() <= $this->get_volume();
				$can_fit = $can_fit && $item->get_length() >= ( ( $item->get_width() + $this->get_height() ) * 2 );
				break;
			// Packets are flexible
			case 'packet':
				$can_fit = $this->get_packed_length() >= $item->get_length() && $this->get_packed_width() >= $item->get_width() && $item->get_volume() <= $this->get_volume();

				if ( $can_fit && $item->get_height() > $this->get_packed_height() ) {
					$this->maybe_packed_height = $item->get_height();
					$this->maybe_packed_length = $this->get_packed_length() - ( $this->maybe_packed_height - $this->get_height() );
					$this->maybe_packed_width  = $this->get_packed_width() - ( $this->maybe_packed_height - $this->get_height() );

					$can_fit = $this->maybe_packed_height < $this->maybe_packed_width && $this->maybe_packed_length >= $item->get_length() && $this->maybe_packed_width >= $item->get_width();
				}
				break;
			// Boxes are easy
			default:
				$can_fit = $this->get_length() >= $item->get_length() && $this->get_width() >= $item->get_width() && $this->get_height() >= $item->get_height() && $item->get_volume() <= $this->get_volume();
				break;
		}

		return $can_fit;
	}

	/**
	 * Reset packed dimensions to originals
	 */
	private function reset_packed_dimensions() {
		$this->packed_length = $this->length;
		$this->packed_width  = $this->width;
		$this->packed_height = $this->height;
	}

	/**
	 * pack function.
	 *
	 *
	 * @param mixed $items
	 *
	 * @return object Package
	 */
	public function pack( $items ) {
		$packed        = array();
		$unpacked      = array();
		$packed_weight = $this->get_weight();
		$packed_volume = 0;
		$packed_value  = 0;

		$this->reset_packed_dimensions();

		while ( sizeof( $items ) > 0 ) {
			$item = array_shift( $items );

			// Check dimensions
			if ( ! $this->can_fit( $item ) ) {
				$unpacked[] = $item;
				continue;
			}

			// Check max weight
			if ( ( $packed_weight + $item->get_weight() ) > $this->get_max_weight() && $this->get_max_weight() > 0 ) {
				$unpacked[] = $item;
				continue;
			}

			// Check volume
			if ( ( $packed_volume + $item->get_volume() ) > $this->get_volume() ) {
				$unpacked[] = $item;
				continue;
			}

			// Pack
			$packed[]      = $item;
			$packed_volume += $item->get_volume();
			$packed_weight += $item->get_weight();
			$packed_value  += $item->get_value();

			// Adjust dimensions if needed, after this item has been packed inside
			if ( ! is_null( $this->maybe_packed_height ) ) {
				$this->packed_height       = $this->maybe_packed_height;
				$this->packed_length       = $this->maybe_packed_length;
				$this->packed_width        = $this->maybe_packed_width;
				$this->maybe_packed_height = NULL;
				$this->maybe_packed_length = NULL;
				$this->maybe_packed_width  = NULL;
			}
		}

		// Get weight of unpacked items
		$unpacked_weight = 0;
		$unpacked_volume = 0;
		foreach ( $unpacked as $item ) {
			$unpacked_weight += $item->get_weight();
			$unpacked_volume += $item->get_volume();
		}

		$package = new Package( $this->id, $this->type, $packed_weight, $packed_volume, $this->get_outer_length(), $this->get_outer_width(), $this->get_outer_height(), $packed_value, $unpacked, $packed );

		// Calculate packing success % based on % of weight and volume of all items packed
		$packed_weight_ratio      = NULL;
		$packed_volume_ratio      = NULL;
		$packed_weight_to_compare = $packed_weight - $this->get_weight();

		if ( $packed_weight_to_compare + $unpacked_weight > 0 ) {
			$packed_weight_ratio = $packed_weight_to_compare / ( $packed_weight_to_compare + $unpacked_weight );
		}
		if ( $packed_volume + $unpacked_volume ) {
			$packed_volume_ratio = $packed_volume / ( $packed_volume + $unpacked_volume );
		}

		if ( is_null( $packed_weight_ratio ) && is_null( $packed_volume_ratio ) ) {
			// Fallback to amount packed
			$package->percent = ( sizeof( $packed ) / ( sizeof( $unpacked ) + sizeof( $packed ) ) ) * 100;
		} elseif ( is_null( $packed_weight_ratio ) ) {
			// Volume only
			$package->percent = $packed_volume_ratio * 100;
		} elseif ( is_null( $packed_volume_ratio ) ) {
			// Weight only
			$package->percent = $packed_weight_ratio * 100;
		} else {
			$package->percent = $packed_weight_ratio * $packed_volume_ratio * 100;
		}

		return $package;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_volume() {
		if ( $this->volume ) {
			return $this->volume;
		} else {
			return floatval( $this->get_height() * $this->get_width() * $this->get_length() );
		}
	}

	/**
	 * @inerhitDoc
	 */
	public function get_height() {
		return $this->height;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_width() {
		return $this->width;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_length() {
		return $this->length;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_weight() {
		return $this->weight;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_outer_height() {
		return $this->outer_height;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_outer_width() {
		return $this->outer_width;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_outer_length() {
		return $this->outer_length;
	}

	/**
	 * get_packed_height
	 *
	 * @return float
	 */
	public function get_packed_height() {
		return $this->packed_height;
	}

	/**
	 * get_packed_width
	 *
	 * @return float
	 */
	public function get_packed_width() {
		return $this->packed_width;
	}

	/**
	 * get_packed_length.
	 *
	 * @return float
	 */
	public function get_packed_length() {
		return $this->packed_length;
	}

}
