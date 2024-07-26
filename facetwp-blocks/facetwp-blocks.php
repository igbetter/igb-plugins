<?php
/*
Plugin Name: FacetWP - Blocks
Description: Integrate FacetWP with WordPress Gutenberg Blocks
Version: 0.2
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-blocks
*/

defined( 'ABSPATH' ) or exit;


class FacetWP_Blocks_Integration {

  public $kadence_posts_block_classes;

  public $no_results_block;


  function __construct() {

    define( 'FACETWP_BLOCKS_VERSION', '0.2' );
    define( 'FACETWP_BLOCKS_URL', plugins_url( '', __FILE__ ) );

    add_action( 'enqueue_block_editor_assets', [ $this, 'add_facetwp_block_setting' ] );
    add_filter( 'facetwp_assets', array( $this, 'add_front_assets' ) );

    // Needs to run late, after WooCommerce 'query_loop_block_query_vars' filter and after any pre_get_posts() and other filters.
    add_filter( 'query_loop_block_query_vars', [ $this, 'query_loop_set_facetwp_query_args' ], 999, 3 );
    add_filter( 'query_loop_block_query_vars', [ $this, 'wc_products_default_args' ], 10, 3 );

    add_filter( 'block_type_metadata_settings', [ $this, 'latest_posts_set_facetwp_query_args' ], 10, 2 );

    add_filter( 'render_block_data', [ $this, 'prepare_block_data' ], 10, 3 );

    add_filter( 'render_block_core/post-template', [ $this, 'query_loop_render_block' ], 10, 3 );
    add_filter( 'render_block_core/query-no-results', [ $this, 'query_loop_disable_no_results_block' ], 10, 3 );

    add_filter( 'render_block_core/latest-posts', [ $this, 'latest_posts_render_no_results' ], 10, 3 );

    add_filter( 'render_block_generateblocks/grid', [ $this, 'generateblocks_render_no_results' ], 10, 3 );
    add_filter( 'render_block_kadence/posts', [ $this, 'kadence_posts_render' ], 10, 3 );
    add_filter( 'render_block_stackable/posts', [ $this, 'stackable_posts_render' ], 10, 3 );
    add_filter( 'render_block_uagb/post-grid', [ $this, 'uagb_post_grid_render' ], 10, 3 );
    add_filter( 'render_block_uagb/loop-builder', [ $this, 'uagb_loop_builder_render' ], 10, 3 );
    add_filter( 'render_block_uagb/loop-wrapper', [ $this, 'uagb_loop_wrapper_render' ], 10, 3 );
    add_filter( 'render_block_themeisle-blocks/posts-grid', [ $this, 'themeisle_blocks_posts_grid_render' ], 10, 3 );

  }


  /**
   * Add custom FacetWP toggle setting to block sidebar.
   *
   * @since 0.1
   */

  function add_facetwp_block_setting() {

    wp_register_script( 'fwp_block_assets', FACETWP_BLOCKS_URL . '/build/index.js', [
      'wp-blocks',
      'wp-dom',
      'wp-dom-ready',
      'wp-edit-post'
    ], FACETWP_BLOCKS_VERSION );
    wp_enqueue_script( 'fwp_block_assets' );

  }


  /**
   * Add front CSS and JS assets
   *
   * CSS add 100% width to block flex columns when only one 'facetwp-no-results' list item exists.
   *
   * @since 0.1
   */

  function add_front_assets( $assets ) {

    $assets['facetwp-wp-blocks.css'] = [ FACETWP_BLOCKS_URL . '/assets/css/front.css', FACETWP_BLOCKS_VERSION ];

    if ( defined( 'UAGB_VER' ) ) {
      $assets['uagb-postgrid-front.js'] = [
        FACETWP_BLOCKS_URL . '/assets/js/uagb-postgrid-front.js',
        FACETWP_BLOCKS_VERSION
      ];
    }

    return $assets;
  }


  /**
   * Add query arguments needed for FacetWP for 'core/query' blocks.
   *
   * Runs only for 'core/query' block and once for each contained child block.
   * Is limited to running once on 'core/post-template', otherwise it runs twice when you have a 'core/query-no-results' block.
   *
   * @since 0.1
   */

  function query_loop_set_facetwp_query_args( $query, $block, $page ) {

    // Stop rendering the block if it happens within get_the_excerpt().
    // See explanation in prepare_block_data().
    // Currently this issue does not happen in this block. This fix is to prevent possible future double query issues in Query Loop blocks, caused by add_facetwp_query_args().
    if ( doing_filter( 'get_the_excerpt' ) ) {
      return $query;
    }

    if ( ( strpos( ( $classname = $block->parsed_block['attrs']['className'] ?? '' ), 'facetwp-template' ) !== false ) ) {

      // Set flag if it is a re-render.
      if ( $block->parsed_block['attrs']['isRerender'] ?? false ) {
        $query['rerender'] = true;
      }

      return $this->add_facetwp_query_args( $query );

    }

    return $query;
  }


  /**
   * Set default 'offset' and 'posts_per_page' query arguments for re-render of WooCommerce Products block inner 'core/post-template' block.
   *
   * Prevents 'undefined array key offset' and 'undefined array key posts_per_page' when re-rendered in query_loop_render_block() situations.
   * Also sets correct 'posts_per_page' so the max number of posts are re-rendered, for inline CSS styles numbering.
   * Errors stem from: /wp-content/plugins/woocommerce/packages/woocommerce-blocks/src/BlockTypes/ProductQuery.php - build_query() L245-248
   *
   * @since 0.1
   */

  function wc_products_default_args( $query, $block, $page ) {

    if ( isset( $block->parsed_block['attrs']['__woocommerceNamespace'] ) && $block->parsed_block['attrs']['__woocommerceNamespace'] == 'woocommerce/product-query/product-template' ) {

      if ( $block->parsed_block['attrs']['isRerender'] ?? false ) {
        $query['offset'] = 0;
        $query['posts_per_page'] = FWP()->facet->pager_args['per_page'];
      }
    }

    return $query;
  }


  /**
   * Build query arguments needed for FacetWP.
   *
   * Adds 'facetwp = true' argument for query detection.
   * Adds pagination arguments to AJAX refreshes.
   *
   * @since 0.1
   */

  function add_facetwp_query_args( $query ) {

    $query['facetwp'] = true;

    // Sets paged and offset.
    $prefix = FWP()->helper->get_setting( 'prefix' );
    $paged = isset( $_GET[ $prefix . 'paged' ] ) ? (int) $_GET[ $prefix . 'paged' ] : 1;

    // For AJAX refreshes, grabs the page number from the response.
    if ( ! FWP()->request->is_preload ) {
      $post_data = FWP()->request->process_post_data();
      $paged = (int) $post_data['paged'];
    }

    $per_page = isset( $query['posts_per_page'] ) ? (int) $query['posts_per_page'] : 10;

    $GLOBALS['wp_the_query']->set( 'page', $paged );
    $GLOBALS['wp_the_query']->set( 'paged', $paged );
    $query['paged'] = $paged;

    // Prevent block rendering from looping when an offset argument is used that is larger than the number of posts.
    // This can happen when a page is loaded with a '_paged=x' parameter where x is beyond the range of total posts, resulting in fatal memory errors.
    // To prevent this, 'offset' is only set if it is less than number of posts.
    // Normal block queries behave different than those using "Inherit from query". The former need offset to not be set (or 0). The latter need it to be set to 0.
    // There may be a better way to prevent this, this works for now. FWP()->facet->pager_args['total_rows']; is not available here.

    // Get total number of posts
    $temp_query = array_merge( $query, [
      'paged'                  => 1,
      'posts_per_page'         => - 1,
      'update_post_meta_cache' => false,
      'update_post_term_cache' => false,
      'cache_results'          => false,
      'nopaging'               => true,
      'facetwp'                => false,
      'fields'                 => 'ids',
      'no_found_rows'          => false // Needed for pagination, in e.g. Otter blocks Posts block
    ] );

    $block_query = new WP_Query( $temp_query );
    $total = $block_query->found_posts;

    $offset = ( 1 < $paged ) ? ( ( $paged - 1 ) * $per_page ) : 0;

    // Set offset only when smaller than total number of posts, to prevent the looping.
    if ( $offset < $total ) {
      $query['offset'] = $offset;
    } else {
      $query['offset'] = 0; // Query Blocks with "Inherit from query" enabled need offset set to 0;
    }

    // If it's a re-render, set offset to 0 so it always renders the maximum number of posts, also on a last paged page with less posts than posts_per_page.
    if ( $query['rerender'] ?? false ) {
      $query['offset'] = 0;
    }

    return $query;
  }


  /**
   * Add 'facetwp = true' argument for query detection.
   *
   * Runs only for 'core/latest-post' blocks.
   * There is no hook for the query in 'core/latest-post block', like for the 'core/query' block, so this is the only way.
   * The argument is added to each 'core/latest-post' block, there is no way to know which one has FacetWP enabled, but without the 'facetwp-template' class it can do no harm.
   * Solution based on: https://wordpress.stackexchange.com/a/405596
   */

  /**
   * Part 1: Change render callback.
   *
   * @since 0.1
   */

  function latest_posts_set_facetwp_query_args( $settings, $metadata ) {

    // Gutenberg plugin uses a proprietary callback
    $allowed_callbacks = array( 'render_block_core_latest_posts', 'gutenberg_render_block_core_latest_posts' );

    if ( $metadata['name'] !== 'core/latest-posts' || ! in_array( $settings['render_callback'], $allowed_callbacks, true ) ) {
      return $settings;
    }

    $settings['render_callback'] = array( $this, 'latest_posts_render_block_core' );

    return $settings;
  }

  /**
   * Part 2: Insert 'facetwp = true' query argument with custom render callback, with pre_get_posts().
   *
   * @since 0.1
   */

  function latest_posts_render_block_core( $attributes, $content, $block ) {

    $callback = function( $query ) {
      $query->set( 'facetwp', true );
    };

    add_action( 'pre_get_posts', $callback, 10 );
    $output = render_block_core_latest_posts( $attributes, $content, $block );
    remove_action( 'pre_get_posts', $callback, 10 );

    return $output;
  }


  /**
   * Prepare blocks for rendering.
   *
   * Adds 'facetwp-template' class to supported blocks if 'enableFacetWP' is set with custom block setting.
   *
   * @since 0.1
   */

  function prepare_block_data( $block, $source_block, $parent_block ) {

    // Stop rendering the block if it happens within get_the_excerpt().
    // This prevents multiple queries on the page with facetwp=true, causing filtering issues,
    // which currently happens only in the Stackable Posts block, when:
    // - using Yoast SEO OpenGraph data or Twitter card data features, which run get_the_excerpt().
    // - using get_the_excerpt() on the page in other custom code.
    // - both only in non-block themes.
    // This fix could be directley in add_facetwp_query_args(). But stopping it here, sooner, also prevents this whole function from double running.
    if ( doing_filter( 'get_the_excerpt' ) ) {
      return $block;
    }

    // Accommodate for nested 'core/post-template' and 'core/query-no-results' blocks of any level.
    // Runs for each specified containerblock. Checks if parent is 'core/query' block with attribute 'enableFacetWP' set with custom block setting, or if it is a nested containerblock.
    // If the block has a 'core/post-template' child, it sets a flag.
    // If the block has a 'core/query-no-results' child, it sets a flag.
    $containerblocks = [
      'core/group',
      'core/columns',
      'core/column',

      // GenerateBlocks
      'generateblocks/container',

      // Kadence Blocks
      'kadence/rowlayout',
      'kadence/column',
      'kadence/tabs',
      'kadence/tab',
      'kadence/accordion',
      'kadence/pane',

      // Stackable
      'stackable/columns',
      'stackable/column',
      'stackable/tabs',
      'stackable/tab-content',
      'stackable/accordion',

      // Spectra
      'uagb/container',
      'uagb/tabs',
      'uagb/tabs-child',

      // Otter blocks
      'themeisle-blocks/advanced-columns',
      'themeisle-blocks/advanced-column',
      'themeisle-blocks/tabs',
      'themeisle-blocks/tabs-item',
      'themeisle-blocks/accordion',
      'themeisle-blocks/accordion-item'
    ];

    if ( in_array( $block['blockName'], $containerblocks ) ) {

      if ( ( $enablefacetwp = $parent_block->parsed_block['attrs']['enableFacetWP'] ?? null ) === true || ( $isnestedblock = $parent_block->parsed_block['attrs']['isNestedBlock'] ?? null ) === true ) {

        if ( isset( $block['innerBlocks'] ) ) {

          $innerblocks = $block['innerBlocks'];

          foreach ( $innerblocks as $innerblock ) {

            if ( in_array( $innerblock['blockName'], [ 'core/post-template', 'generateblocks/grid', 'uagb/loop-wrapper' ] ) ) {
              $block['attrs']['hasTemplateChild'] = true;
            } elseif ( $innerblock['blockName'] === 'core/query-no-results' ) {
              $block['attrs']['hasNoResultsChild'] = true;
            } elseif ( in_array( $innerblock['blockName'], $containerblocks ) ) {
              $block['attrs']['isNestedBlock'] = true;
            }

          }

        }

      }

    }

    // Store the 'core/query-no-results' for later use in query_loop_render_block(), also if nested.
    // Note: nested 'No Results' blocks need to be before the 'Post Template' block or its container in the tree. Stand-alone 'No Results' blocks can be before or after it.
    if ( ( $block['blockName'] === 'core/query' && ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) || ( $hasnoresultschild = $block['attrs']['hasNoResultsChild'] ?? null ) === true ) {

      if ( isset( $block['innerBlocks'] ) ) {
        $innerblocks = $block['innerBlocks'];

        foreach ( $innerblocks as $innerblock ) {

          if ( $innerblock['blockName'] === 'core/query-no-results' ) {

            // Circumvent render_block_core_query_no_results() query logic by renaming its blockName.
            // This function checks for wp_query->have_posts(), with query args built with build_query_vars_from_query_block().
            // This does not work for non-archive FacetWP templates, causing the no results block to output nothing.
            // So the block's content is stored temporarily to retrieve it in the  'core/post-template' block if that block is empty.
            // Also the rendering of the 'core/no-results block' is disabled when it is rendered in "inherit query from template" archives.
            $innerblock['blockName'] = 'fwp-custom-no-results';
            $this->no_results_block = $innerblock;

          }

        }

      }

    }

    // Set 'facetwp-template' class to Query Loop block child 'core/post-template' block.
    // Runs for 'core/post-template' block when parent 'core/query' block has attribute 'enableFacetWP' set with custom block setting.

    // Check for our custom attribute set by a custom toggle in 'core/query' block
    if ( $block['blockName'] === 'core/post-template' && ( ( $enablefacetwp = $parent_block->parsed_block['attrs']['enableFacetWP'] ?? null ) === true || ( $hasTemplateChild = $parent_block->parsed_block['attrs']['hasTemplateChild'] ?? null ) === true ) ) {

      // Set 'facetwp-template' classname and preserve custom class.
      // Class is set on the <ul>. It is also used as the identifier of the loop in render_no_results().
      $this->set_facetwp_template_class( $block );

    }

    // Set 'facetwp-template' class to the block.
    // Runs for 'core/latest-posts' block when this block has attribute 'enableFacetWP' set with custom block setting.
    // The class is set on the <ul>. It is also used as the identifier of the loop in render_no_results().
    if ( $block['blockName'] === 'core/latest-posts' && ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

      $this->set_facetwp_template_class( $block );

    }

    // Runs for 'generateblocks/grid' block when parent 'generateblocks/query-loop' block has attribute 'enableFacetWP' set with custom block setting.
    if ( defined( 'GENERATEBLOCKS_VERSION' ) && $block['blockName'] === 'generateblocks/grid' && ( ( $enablefacetwp = $parent_block->parsed_block['attrs']['enableFacetWP'] ?? null ) === true || ( $hasTemplateChild = $parent_block->parsed_block['attrs']['hasTemplateChild'] ?? null ) === true ) ) {

      $this->set_facetwp_template_class( $block );

      add_filter( 'generateblocks_query_loop_args', [ $this, 'add_facetwp_query_args' ], 10, 2 );

    }

    // Runs for 'kadence/posts' block when this block has attribute 'enableFacetWP' set with custom block setting.
    if ( defined( 'KADENCE_BLOCKS_VERSION' ) && $block['blockName'] === 'kadence/posts' && ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

      $this->set_facetwp_template_class( $block );

      add_filter( 'kadence_blocks_posts_query_args', [ $this, 'add_facetwp_query_args' ], 10, 2 );

      // Run classes filter to get all layout block classes and store them for use with the no-results block.
      add_filter( 'kadence_blocks_posts_container_classes', function( $classes ) {

        $this->kadence_posts_block_classes = $classes;

        return $classes;

      }, 10, 1 );

    }

    /**
     * @since 0.2
     */

    // Runs for 'stackable/posts' block when this block has attribute 'enableFacetWP' set with custom block setting.
    if ( defined( 'STACKABLE_VERSION' ) && $block['blockName'] === 'stackable/posts' && ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

      add_filter( 'stackable/posts/post_query', [ $this, 'add_facetwp_query_args' ], 10, 2 );

    }

    /**
     * @since 0.2
     */

    // Runs for (Spectra) 'uagb/post-grid' block when this block has attribute 'enableFacetWP' set with custom block setting.
    if ( defined( 'UAGB_VER' ) && $block['blockName'] === 'uagb/post-grid' && ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

      // If pagination is enabled, set 'paginationType' to 'normal' to prevent the block's own AJAX pagination and intercept clicks in uagb-postgrid-front.js
      if ( isset( $block['attrs']['postPagination'] ) && $block['attrs']['postPagination'] === true ) {
        $block['attrs']['paginationType'] = 'normal';
      }

      $this->set_facetwp_template_class( $block );

      add_filter( 'uagb_post_query_args_grid', [ $this, 'add_facetwp_query_args' ], 10, 2 );

    }

    /**
     * @since 0.2
     */

    // Runs only for Spectra Pro 'uagb/loop-builder' block.
    if ( defined( 'SPECTRA_PRO_VER' ) && defined( 'UAGB_VER' ) ) {

      // Runs only when this block has attribute 'enableFacetWP' set with custom block setting.
      if ( $block['blockName'] === 'uagb/loop-builder' && ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

        // Set the query vars with the Query Loop hook.
        add_filter( 'query_loop_block_query_vars', [ $this, 'add_facetwp_query_args' ], 10, 2 );

      }

      // We set a flag on the 'uagb/loop-wrapper' block  because we can't check for the 'facetwp-template' class. The 'uagb/loop-wrapper' block does not work like 'core/post-template', it runs for each post item.
      if ( $block['blockName'] === 'uagb/loop-wrapper' && ( ( $enablefacetwp = $parent_block->parsed_block['attrs']['enableFacetWP'] ?? null ) === true || ( $hasTemplateChild = $parent_block->parsed_block['attrs']['hasTemplateChild'] ?? null ) === true ) ) {

        $block['attrs']['isFacetWPenabled'] = true;

      }

    }

    /**
     * @since 0.2
     */

    // Runs for (Otter Blocks) 'themeisle-blocks/posts-grid' block when this block has attribute 'enableFacetWP' set with custom block setting.
    if ( defined( 'OTTER_BLOCKS_VERSION' ) && $block['blockName'] === 'themeisle-blocks/posts-grid' && ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

      add_filter( 'themeisle_gutenberg_posts_block_query', [ $this, 'add_facetwp_query_args' ], 10, 2 );

    }


    return $block;
  }


  /**
   * Preserve custom classes and set 'facetwp-template' class.
   *
   * @since 0.1
   */

  function set_facetwp_template_class( &$block ) {

    if ( isset( $block['attrs']['className'] ) ) {
      $block['attrs']['className'] .= ' facetwp-template';
    } else {
      $block['attrs']['className'] = 'facetwp-template';
    }

  }


  /**
   * Re-render the 'core/post-template' Query Loop child block in certain situations.
   * Inject 'core/query-no-results' block or message when no results.
   *
   * The block is re-rendered to:
   * - set inline numbered CSS classes in the inline style tag with id="core-block-supports-inline-css", for as many numbered items as posts_per_page. Without this, on first page load there are numbered CSS rules missing for the numbered post items that are not present.
   * - generate all inline block styles in the header and footer, for the block and all inner blocks.
   * - generate the layout classes and styling attributes set on the <ul>.
   *
   * The block is only re-rendered in these situations:
   * - if the page loads with no results.
   * - if the page loads with a post count less than the posts_per_page:
   * -- because the posts are filtered.
   * -- because it is the last paged page with less posts than previous pages.
   * The block is not re-rendered if there is only one page with less posts than the posts
   *
   * If the page loads with no results, the 'core/query-no-results' block (or a translatable 'Nothing found' message) is injected into the 'core/post-template' block. This is needed because the 'core/query-no-results' block does not work in FacetWP context, due to its internal logic.
   *
   * @since 0.1
   */

  function query_loop_render_block( $block_content, $block, $instance ) {

    // Class is set in prepare_block_data().
    if ( ( strpos( ( $classname = $block['attrs']['className'] ?? '' ), 'facetwp-template' ) !== false ) ) {

      // Prevent warnings when using wrong template, e.g. inherit from query enabled on normal page template.
      if ( ! isset( FWP()->facet->pager_args ) ) {
        return $block_content;
      }

      // Get all paging data used to determine if we need to re-render or not.
      $page = FWP()->facet->pager_args['page'];
      $per_page = FWP()->facet->pager_args['per_page'];
      $total_rows = FWP()->facet->pager_args['total_rows'];
      $is_filtering = FWP()->is_filtered;

      if ( - 1 == $per_page ) {
        $per_page = $total_rows;
      }

      if ( 0 < $total_rows ) {
        $lower = ( 1 + ( ( $page - 1 ) * $per_page ) );
        $upper = ( $page * $per_page );
        $upper = ( $total_rows < $upper ) ? $total_rows : $upper;
        $postcount = $upper - $lower + 1;
      } else {
        $postcount = 0;
      }

      // If there are 0 results, or it is a paged page with less than the posts per page.
      // But not if it is an unfiltered first page with less than the posts per page. User could set it very high or -1, with no paging.
      if ( $total_rows == 0 || ( ( $is_filtering || $page > 1 ) && $postcount < $per_page ) ) {

        // Temp remove to prevent looping.
        remove_filter( 'render_block_core/post-template', [ $this, 'query_loop_render_block' ], 10 );

        // Copy the block to add a flag, used to reset query offset to 0, to render max number of posts for numbered CSS classes.
        $block_copy = $block;
        $block_copy['attrs']['isRerender'] = true;

        // Re-render the block
        $re_renderedblock = render_block( $block_copy );

        add_filter( 'render_block_core/post-template', [ $this, 'query_loop_render_block' ], 10, 3 );

      }

      // Runs only if 0 results on page load.
      // Empty $block_content is double check for no results.
      if ( $block_content == '' && $total_rows === 0 && isset( $re_renderedblock ) ) {

        // Remove li's and closing ul tag.
        $ul_html = preg_replace( '/<li\b[^>]*>.*?<\/li>|<\/ul>/is', '', $re_renderedblock );

        // Add a fall-back message if no 'No results' block present.
        // Allow customization or override of the message HTML with the 'facetwp_blocks_query_loop_no_results' hook.
        $no_results_html = '<p>' . facetwp_i18n( __( 'Nothing found.', 'fwp-front' ) ) . '</p>';
        $no_results_html = apply_filters( 'facetwp_blocks_query_loop_no_results', $no_results_html );

        $block_inner_content = '<li class="facetwp-no-results">' . $no_results_html . '</li>';

        // Get the the stored 'core/query-no-results' block if it is set.
        if ( isset( $this->no_results_block ) ) {

          //  Allow customization or override of the No Results block with 'facetwp_blocks_query_loop_no_results' hook.
          $no_results_html = render_block( $this->no_results_block );
          $no_results_html = apply_filters( 'facetwp_blocks_query_loop_no_results', $no_results_html );

          // Preserve No Results block custom classes
          $no_results_class = isset( $this->no_results_block['attrs']['className'] ) ? ' ' . $this->no_results_block['attrs']['className'] : '';

          // Render the 'fwp-custom-no-results' block content.
          // Add the 'facetwp-no-results' class on the <li> to set the single flex column to 100% with block_css() if there no results.
          $block_inner_content = '<li class="facetwp-no-results' . $no_results_class . '">' . $no_results_html . '</li>';

        }

        $block_content = $ul_html . $block_inner_content . '</ul>';

      }
    }

    return $block_content;
  }


  /**
   * Disable independent rendering of the 'core/query-no-results' block. This block only generates output in archive template with "inherit query from template".
   *
   * Runs only for 'core/query-no-results' block.
   * Without this, the 'core/query-no-results' block is rendered twice in this situation, because its content is already rendered in the 'core/post-template' block when that block has no content.
   *
   * @since 0.1
   */

  function query_loop_disable_no_results_block( $block_content, $block, $instance ) {

    $block_content = '';

    return $block_content;
  }


  /**
   * Add 'Nothing found' message when no results.
   *
   * Runs only for 'core/latest-posts' block.
   *
   * @since 0.1
   */

  function latest_posts_render_no_results( $block_content, $block, $instance ) {

    // Was set in prepare_block_data().
    if ( ( strpos( ( $classname = $block['attrs']['className'] ?? '' ), 'facetwp-template' ) !== false ) ) {

      // To detect the empty posts ul seems to be the only way to detect 'no results'.
      if ( strpos( $block_content, '<ul' ) !== false ) { // Check if $html contains <ul>.

        // Allow customization or override of the no results HTML with the 'facetwp_blocks_latest_posts_no_results' hook.
        $no_results_html = '<p>' . facetwp_i18n( __( 'Nothing found.', 'fwp-front' ) ) . '</p>';
        $no_results_html = apply_filters( 'facetwp_blocks_latest_posts_no_results', $no_results_html );

        $li_exists = strpos( $block_content, '<li' ) !== false; // Check if any <li> is present in $block_content.
        $li = $li_exists ? '' : '<li class="facetwp-no-results">' . $no_results_html . '</li>'; // If no <li>, create new <li>.
        preg_match( '/<ul.*?>/', $block_content, $matches ); // Match the <ul> element and its attributes.
        $insert_before = $li_exists ? $matches[0] : '</ul>'; // Determine where to insert the new <li> element.
        $block_content = str_replace( $insert_before, $li . $insert_before, $block_content ); // Insert the new <li> element.

      }
    }

    return $block_content;
  }


  /**
   * Add 'Nothing found' message when no results.
   *
   * Runs only for 'generateblocks/grid' block.
   *
   * @since 0.1
   */

  function generateblocks_render_no_results( $block_content, $block, $instance ) {

    if ( ! defined( 'GENERATEBLOCKS_VERSION' ) ) {
      return $block_content;
    }

    // Was set in prepare_block_data().
    if ( ( strpos( ( $classname = $block['attrs']['className'] ?? '' ), 'facetwp-template' ) !== false ) ) {

      // Preserve classnames for block 'generateblocks/grid' CSS.
      $classnames = 'gb-grid-wrapper gb-query-loop-wrapper ' . $block['attrs']['className'];

      if ( isset( $block['attrs']['uniqueId'] ) ) {
        $classnames = 'gb-grid-wrapper-' . $block['attrs']['uniqueId'] . ' ' . $classnames;
      }

      // Detect no results.
      if ( strpos( $block_content, 'gb-query-loop-item' ) === false ) {

        // Allow customization or override of the no results HTML with the 'facetwp_blocks_gb_query_loop_no_results' hook.
        $no_results_html = '<p class="facetwp-no-results">' . facetwp_i18n( __( 'Nothing found.', 'fwp-front' ) ) . '</p>';
        $no_results_html = apply_filters( 'facetwp_blocks_gb_query_loop_no_results', $no_results_html );

        $block_content = '<div class="' . $classnames . '"><div class="gb-grid-column gb-query-loop-item">' . $no_results_html . '</div></div>';

      }
    }

    return $block_content;
  }


  /**
   * Add 'Nothing found' message when no results.
   *
   * Runs only for 'kadence/posts' block.
   *
   * @since 0.1
   */

  function kadence_posts_render( $block_content, $block, $instance ) {

    if ( ! defined( 'KADENCE_BLOCKS_VERSION' ) ) {
      return $block_content;
    }

    // Was set in prepare_block_data().
    if ( ( strpos( ( $classname = $block['attrs']['className'] ?? '' ), 'facetwp-template' ) !== false ) ) {

      // Preserve all classnames.
      $classes_array = $this->kadence_posts_block_classes;

      $classes = implode( " ", $classes_array );

      // Detect no results.
      if ( strpos( $block_content, 'entry' ) === false ) {

        // Allow customization or override of the no results HTML with the 'kadence_blocks_posts_empty_query' hook.
        $no_results_html = '<p class="facetwp-no-results">' . facetwp_i18n( __( 'Nothing found.', 'fwp-front' ) ) . '</p>';
        $no_results_html = apply_filters( 'kadence_blocks_posts_empty_query', $no_results_html );

        $block_content = '<div class="' . $classes . '">' . $no_results_html . '</div>';

      }
    }

    return $block_content;
  }


  /**
   * Add 'facetwp-template' class to stk-block-posts__items container.
   * Add 'Nothing found' message when no results.
   *
   * Runs only for 'stackable/posts' block.
   *
   * @since 0.2
   */

  function stackable_posts_render( $block_content, $block, $instance ) {

    if ( ! defined( 'STACKABLE_VERSION' ) ) {
      return $block_content;
    }

    if ( ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

      // Add the 'facetwp-template' class to the most inner container, to prevent gaps when using a Load more facet.
      $block_content = str_replace( 'stk-block-posts__items', 'stk-block-posts__items facetwp-template', $block_content );

      // Detect no results. Match for the 'stk-block-posts__item' class.
      if ( ! preg_match( '/\bstk-block-posts__item\b/', $block_content ) ) {

        // Allow customization or override of the no results HTML with the 'facetwp_blocks_stk_posts_no_results' hook.
        $no_results_html = '<p class="facetwp-no-results">' . facetwp_i18n( __( 'Nothing found.', 'fwp-front' ) ) . '</p>';
        $no_results_html = apply_filters( 'facetwp_blocks_stk_posts_no_results', $no_results_html );

        $block_content = str_replace( '<div class="stk-block-posts__items facetwp-template"></div>', '<div class="stk-block-posts__items facetwp-template">' . $no_results_html . '</div>', $block_content );

      }
    }

    return $block_content;
  }


  /**
   * Add 'Nothing found' message when no results.
   *
   * Runs only for Spectra (Ultimate Addons for Gutenberg) 'uagb/post-grid' block.
   *
   * @since 0.2
   */

  function uagb_post_grid_render( $block_content, $block, $instance ) {

    if ( ! defined( 'UAGB_VER' ) ) {
      return $block_content;
    }

    // Was set in prepare_block_data().
    if ( ( strpos( ( $classname = $block['attrs']['className'] ?? '' ), 'facetwp-template' ) !== false ) ) {

      // Detect no results.
      if ( strpos( $block_content, 'uagb-post__no-posts' ) !== false) {

        if ( ! empty( $block['attrs']['postDisplaytext'] ) ) {
          $noresultstext = $block['attrs']['postDisplaytext'];
        } else {
          $noresultstext = 'Nothing found.';
        }

        // Allow customization or override of the no results HTML with the 'facetwp_blocks_uagb_postgrid_results' hook.
        $no_results_html = '<p class="uagb-post__no-posts facetwp-no-results">' . facetwp_i18n( __( $noresultstext, 'fwp-front' ) ) . '</p>';
        $no_results_html = apply_filters( 'facetwp_blocks_uagb_postgrid_no_results', $no_results_html );

        $block_content = preg_replace('/<p class="uagb-post__no-posts">.*?<\/p>/s', $no_results_html, $block_content );

      }
    }

    return $block_content;
  }


  /**
   * Add 'facetwp-template' class.
   *
   * Runs only for Spectra Pro (Ultimate Addons for Gutenberg) 'uagb/loop-builder' block.
   *
   * @since 0.2
   */

  function uagb_loop_builder_render( $block_content, $block, $instance ) {

    if ( ! ( defined( 'SPECTRA_PRO_VER' ) && defined( 'UAGB_VER' ) ) ) {
      return $block_content;
    }

    if ( ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

      // Set the facetwp-template class to the most inner container, to prevent gaps when using a Load more facet.
      // The normal "query loop way" does not work, multiple inner containers are added hard-coded without hooks to access them.
      $block_content = str_replace( 'uagb-loop-container', 'facetwp-template uagb-loop-container', $block_content );

    }

    return $block_content;
  }


  /**
   * Add 'Nothing found' message when no results.
   *
   * Runs only for Spectra Pro (Ultimate Addons for Gutenberg) Loop Builder child block 'uagb/loop-wrapper'.
   *
   * @since 0.2
   */

  function uagb_loop_wrapper_render( $block_content, $block, $instance ) {

    if ( ! ( defined( 'SPECTRA_PRO_VER' ) && defined( 'UAGB_VER' ) ) ) {
      return $block_content;
    }

    // Check for 'uagb/loop-builder' parent. We use the flag because we can't check for the 'facetwp-template' class.
    if ( ( $enablefacetwp = $block['attrs']['isFacetWPenabled'] ?? null ) === true ) {

      // Detect no results and add message.
      // The 'uagb/loop-wrapper' block runs for each post, also if there are results so we need to check for both 'uagb-loop-post' container and post container classes.
      if ( strpos( $block_content, 'uagb-loop-post' ) === false && strpos( $block_content, 'wp-block-uagb-container' ) === false ) {

        // Allow customization or override of the no results HTML with the 'facetwp_blocks_uagb_loop_builder_results' hook.
        $no_results_html = '<p class="facetwp-no-results">' . facetwp_i18n( __( 'Nothing found.', 'fwp-front' ) ) . '</p>';
        $no_results_html = apply_filters( 'facetwp_blocks_uagb_loop_builder_no_results', $no_results_html );

        // Find the first element with the class 'uagb-loop-container' and add the no results HTML
        $template = new DOMDocument();
        $template->loadHTML($block_content, LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($template);
        $firstNode = $xpath->query('//div[contains(@class, "uagb-loop-container")][1]')->item(0);

        if ($firstNode !== null) {
          $firstNode->nodeValue = '';
          $fragment = $template->createDocumentFragment();
          if ($fragment->appendXML($no_results_html)) {
            $firstNode->appendChild($fragment);
          }
        }

        $block_content = $template->saveHTML();

      }

    }

    return $block_content;
  }

  /**
   * Add 'facetwp-template' class to list- or grid-post container.
   * Add 'Nothing found' message when no results.
   *
   * Runs only for Otter Blocks 'themeisle-blocks/posts-grid' block.
   *
   * @since 0.2
   */

  function themeisle_blocks_posts_grid_render( $block_content, $block, $instance ) {

    if ( ! defined( 'OTTER_BLOCKS_VERSION' )  ) {
      return $block_content;
    }

    // Set the facetwp-template class to the most inner container, to prevent gaps when using a Load more facet.
    if ( ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {
      $block_content = str_replace( 'is-list', 'facetwp-template is-list', $block_content );
      $block_content = str_replace( 'is-grid', 'facetwp-template is-grid', $block_content );
    }

    // Was set in prepare_block_data().
    if ( ( $enablefacetwp = $block['attrs']['enableFacetWP'] ?? null ) === true ) {

      // Detect no results.
      if ( strpos( $block_content, 'o-posts-grid-post-blog' ) === false) {

        // Allow customization or override of the no results HTML with the 'facetwp_blocks_themeisle_blocks_posts_results' hook.
        $no_results_html = '<p class="facetwp-no-results">' . facetwp_i18n( __( 'Nothing found.', 'fwp-front' ) ) . '</p>';
        $no_results_html = apply_filters( 'facetwp_blocks_themeisle_blocks_posts_no_results', $no_results_html );

        // Find the first element with the class 'facetwp-template' and add the no results HTML
        $template = new DOMDocument();
        $template->loadHTML($block_content, LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($template);
        $firstNode = $xpath->query('//div[contains(@class, "facetwp-template")][1]')->item(0);
        if ($firstNode !== null) {
          $firstNode->nodeValue = '';
          $fragment = $template->createDocumentFragment();
          if ($fragment->appendXML($no_results_html)) {
            $firstNode->appendChild($fragment);
          }
        }

        $block_content = $template->saveHTML();

      }

    }

    return $block_content;
  }


}

$fwpblocks = new FacetWP_Blocks_Integration();