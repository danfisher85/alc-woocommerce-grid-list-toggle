<?php
/*
Plugin Name: Alchemists WooCommerce Grid / List toggle
Plugin URI: https://github.com/danfisher85/alc-woocommerce-grid-list-toggle
Description: Adds a grid/list view toggle to product archives
Version: 1.1.6
Author: Dan Fisher
Author URI: https://themeforest.net/user/dan_fisher
Requires at least: 4.7
Tested up to: 6.6
WC requires at least: 4.4
WC tested up to: 9.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: alc-woocommerce-grid-list-toggle
Domain Path: /languages/
*/

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'alc-woocommerce-grid-list-toggle', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * WC_List_Grid class
	 **/
	if ( ! class_exists( 'WC_List_Grid' ) ) {

		class WC_List_Grid {

			// Declare the settings property
			private $settings;

			public function __construct() {
				// Hooks
				add_action( 'wp' , array( $this, 'setup_gridlist' ) , 20);
				add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility_with_custom_order_tables' ) );

				// Init settings
				$this->settings = array(
					array(
						'name' 	=> esc_html__( 'Default catalog view', 'alc-woocommerce-grid-list-toggle' ),
						'type' 	=> 'title',
						'id' 	=> 'wc_glt_options'
					),
					array(
						'name' 		=> esc_html__( 'Default catalog view', 'alc-woocommerce-grid-list-toggle' ),
						'desc_tip' 	=> esc_html__( 'Display products in grid or list view by default', 'alc-woocommerce-grid-list-toggle' ),
						'id' 		=> 'wc_glt_default',
						'type' 		=> 'select',
						'options' 	=> array(
							'grid'  => esc_html__( 'Grid', 'alc-woocommerce-grid-list-toggle' ),
							'list' 	=> esc_html__( 'List', 'alc-woocommerce-grid-list-toggle' )
						)
					),
					array(
						'name' 		=> esc_html__( 'Number of columns', 'alc-woocommerce-grid-list-toggle' ),
						'desc_tip' 	=> esc_html__( 'Number of grid columns. Note: applied for grid view only', 'alc-woocommerce-grid-list-toggle' ),
						'id' 		=> 'wc_glt_cols',
						'type' 		=> 'select',
						'options' 	=> array(
							'2'  => esc_html__( '2 columns', 'alc-woocommerce-grid-list-toggle' ),
							'3' 	=> esc_html__( '3 columns', 'alc-woocommerce-grid-list-toggle' ),
							'4' 	=> esc_html__( '4 columns', 'alc-woocommerce-grid-list-toggle' ),
						),
						'default' => '3',
					),
					array(
						'name' 		=> esc_html__( 'Products per page', 'alc-woocommerce-grid-list-toggle' ),
						'desc_tip' 	=> esc_html__( 'Number of products on Shop page.', 'alc-woocommerce-grid-list-toggle' ),
						'id' 		=> 'wc_glt_count',
						'type' 		=> 'text',
						'default' => '6,12,24'
					),
					array( 'type' => 'sectionend', 'id' => 'wc_glt_options' ),
				);

				// Default options
				add_option( 'wc_glt_default', 'grid' );
				add_option( 'wc_glt_cols', '3' );
				add_option( 'wc_glt_count', '6,12,24' );

				// Admin
				add_action( 'woocommerce_settings_product_rating_options_after', array( $this, 'admin_settings' ), 20 );
				add_action( 'woocommerce_update_options_catalog', array( $this, 'save_admin_settings' ) );
				add_action( 'woocommerce_update_options_products', array( $this, 'save_admin_settings' ) );
			}

			/*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/

			function admin_settings() {
				woocommerce_admin_fields( $this->settings );
			}

			function save_admin_settings() {
				woocommerce_update_options( $this->settings );
			}

			// Declare compatibility with Custom Order Tables
			function declare_compatibility_with_custom_order_tables() {
				if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				}
			}

			// Setup
			function setup_gridlist() {
				if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'setup_scripts_script' ), 20);
					add_action( 'woocommerce_before_shop_loop', array( $this, 'gridlist_toggle_button' ), 40);
					add_action( 'woocommerce_after_subcategory', array( $this, 'gridlist_cat_desc' ) );
				}
			}

			function setup_scripts_script() {
				wp_enqueue_script( 'cookie', plugins_url( '/assets/js/jquery.cookie.min.js', __FILE__ ), array( 'jquery' ), '1.1.0', false );
				wp_enqueue_script( 'grid-list-scripts', plugins_url( '/assets/js/jquery.gridlistview.min.js', __FILE__ ), array( 'jquery' ), '1.1.5', false);
				add_action( 'wp_footer', array( $this, 'gridlist_set_default_view' ) );
				add_action( 'wp_footer', array( $this, 'gridlist_set_default_cols' ) );
			}

			// Toggle button
			function gridlist_toggle_button() {

				$grid_view = esc_html__( 'Grid view', 'alc-woocommerce-grid-list-toggle' );
				$list_view = esc_html__( 'List view', 'alc-woocommerce-grid-list-toggle' );

				$output = sprintf( '<nav class="shop-filter__layout"><a href="#" id="grid" title="%1$s" class="shop-filter__grid-layout icon-grid-layout"><span class="icon-grid-layout__inner"><span class="icon-grid-layout__item"></span><span class="icon-grid-layout__item"></span><span class="icon-grid-layout__item"></span></span></a><a href="#" id="list" title="%2$s" class="shop-filter__list-layout icon-list-layout"><span class="icon-list-layout__inner"><span class="icon-list-layout__item"></span><span class="icon-list-layout__item"></span><span class="icon-list-layout__item"></span></span></a></nav>', $grid_view, $list_view );

				echo apply_filters( 'gridlist_toggle_button_output', $output, $grid_view, $list_view );
			}

			function gridlist_set_default_view() {
				$default = get_option( 'wc_glt_default' );
				?>
					<script>
						if (jQuery.cookie( 'gridcookie' ) == null) {
							jQuery( 'ul.products' ).addClass( '<?php echo $default; ?>' );
							jQuery( '.gridlist-toggle #<?php echo $default; ?>' ).addClass( 'active' );
						}
					</script>
				<?php
			}

			function gridlist_set_default_cols() {
				$cols = get_option( 'wc_glt_cols' );
				?>
					<script>
						(function($){
							$(function() {
								var getUrlParameter = function getUrlParameter(sParam) {
									var sPageURL = window.location.search.substring(1),
										sURLVariables = sPageURL.split('&'),
										sParameterName,
										i;

									for (i = 0; i < sURLVariables.length; i++) {
										sParameterName = sURLVariables[i].split('=');

										if (sParameterName[0] === sParam) {
											return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
										}
									}
								};

								var layout = getUrlParameter('layout');
								if ( layout == 'fullwidth' ) {
									$( 'ul.products' ).addClass( 'products--grid-4' );
								} else {
									if ($.cookie( 'gridcookie' ) == null || $('ul.products').hasClass('grid') ) {
										$( 'ul.products' ).addClass( 'products--grid-<?php echo $cols; ?>' );
									}
								}
							});
						})(jQuery);
					</script>
				<?php
			}

			function gridlist_cat_desc( $category ) {
				global $woocommerce;
				echo apply_filters( 'gridlist_cat_desc_wrap_start', '<div itemprop="description">' );
					echo $category->description;
				echo apply_filters( 'gridlist_cat_desc_wrap_end', '</div>' );

			}
		}

		$WC_List_Grid = new WC_List_Grid();
	}
}
