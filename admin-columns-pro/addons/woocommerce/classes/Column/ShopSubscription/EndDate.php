<?php

namespace ACA\WC\Column\ShopSubscription;

use AC;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACP;
use ACP\Search\Comparison\MetaFactory;

/**
 * @since 3.4
 */
class EndDate extends AC\Column\Meta
	implements ACP\Search\Searchable, ACP\Editing\Editable {

	public function __construct() {
		$this->set_type( 'end_date' )
		     ->set_original( true );
	}

	public function get_value( $id ) {
		return null;
	}

	public function get_meta_key() {
		return '_schedule_end';
	}

	public function search() {
        return (new MetaFactory())->create_datetime_iso(
            $this->get_meta_key(),
            $this->get_meta_type(),
            $this->get_post_type()
        );
	}

	public function editing() {
		return new Editing\ShopSubscription\Date( 'end', $this->get_meta_key() );
	}

}