<?php

namespace ACA\WC\ListScreen;

use ACP;

class Product extends ACP\ListScreen\Post
{

    private $column_config;

    public function __construct(array $column_config)
    {
        parent::__construct('product');

        $this->group = 'woocommerce';
        $this->column_config = $column_config;
    }

    protected function register_column_types(): void
    {
        parent::register_column_types();

        $this->register_column_types_from_list($this->column_config);
    }

}