<?php
/*
Plugin Name: Popular Posts from a Matomo instance
Plugin URI: https://larslo.de
Description: get popular posts from your local matomo user tracking
Version: 1.0
Requires at least: 5.0
Tested up to: 5.9
Requires PHP: 7.3
Author: larslo
Author URI: https://larslo.de
Contributors: larslo
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: yours59
*/

/**
 * Use your matomo data for things like "popular posts" or "Most read posts"
 * be gdpr safe and do not send your user's data around the internets (like jetpack does)
 */

/**
 * this plugin is a blueprint for developers
 * its not made for typical WordPress end-user
 * please read the code and adjust it to your needs.
 *
 * before you can use it, some parts in this file need to be adjusted.
 * These are marked with
 * --adust--
 * see more comments there. you need to go through these
 * -
 * */

/* tags: #matomo, #CMB2, #most-read, #popularpost*/

/**
 * outline:
 *
 * provides dashboard widgets
 * connects to matomo and saves results in a transient
 * js is used to render the results
 *
 * you need to adjust the fields of your posts in function parse_matomo_data (preview_media, custom fields etc)
 * and the markup of the output in JS (file yours59_matomo_popular_posts_frontend.js)
 *
 * enable DEBUG output with adding
 * define( 'YOURS59_MATOMO_DEBUG', true );
 * to wp-config.php
 *
 * it uses Actions.getPageUrls from Matomo-Reporting-Api see
 * https://developer.matomo.org/api-reference/reporting-api#VisitsSummary
 *
 * since matomo does not keep track of post-ids we make use of
 * url_to_postid.
 * if you develop on an other system than the one which gets tracked by matomo
 * than you need to some url-string-replacement in order to get results from
 * url_to_postid
 * $url = str_replace( 'https://LIVESITE.de', 'http://DEVELOPMENT_SITEURL', $url );
 *
 *
 * requires plugin CMB2
 *
 *
 */
namespace Yours59MatomoPopularPostsNS;

define( 'YOURS59_MATOMO_DEBUG', true );
$matomo_popular_posts = Yours59MatomoPopularPosts::getInstance();


class Yours59MatomoPopularPosts {

	protected static $instance = null;
	private static $name       = 'Matomo Popular Posts';


	// Method to get the unique instance. Singleton
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self(); }
		self::$instance->init();

		return self::$instance;
	}

	/**
	 * is not allowed to call from outside to prevent from creating multiple instances,
	 * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
	 */
	private function __construct() {}

	/**
	 * prevent the instance from being cloned (which would create a second instance of it)
	 */
	private function __clone() {}

	/**
	 * prevent from being unserialized (which would create a second instance of it)
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize' );
	}

	public function init() {
		/* options page is made with CMB2, when not installed show message in admin */
		add_action(
			'admin_notices',
			function () {
				$required_plugins = array(
					'required_plugins' => array(
						array(
							'type'  => 'class',
							'value' => 'CMB2',
							'name'  => 'CMB2',
						),
					),
				);
				$this->check_required_plugins( $required_plugins );
			},
			10
		);

		add_action( 'cmb2_admin_init', array( self::$instance, 'add_settings_page' ) );
		add_action( 'wp_dashboard_setup', array( self::$instance, 'add_dashboard_widgets' ) );

		/* enqueue script in admin and append data */
		add_action(
			'admin_enqueue_scripts',
			function() {
				/* --adjust-- js file contains comments and console.logs */
				$js_file_name = 'yours59_matomo_popular_posts.uncompressed.js';

				wp_enqueue_script(
					'yours59_matomo_popular_posts',
					plugin_dir_url( __FILE__ ) . '/js/' . $js_file_name,
					array(
						'wp-api-fetch',
					),
					''
				);
				/**
				 * also this is a bit of a waste ,for simplicity we
				 * append popular post data on each page (frontend and admin)
				 * for now
				 */
				wp_localize_script(
					'yours59_matomo_popular_posts',
					'popular_post_data',
					array(
						'data' => self::deliver_popular_posts_data(),
					)
				);
			}
		);
		/* enqueue script in frontend and append data */
		add_action(
			'wp_enqueue_scripts',
			function() {
				/* --adjust-- js file contains comments and console.logs */
				$js_file_name = 'yours59_matomo_popular_posts_frontend.uncompressed.js';

				wp_enqueue_script(
					'yours59_matomo_popular_posts_frontend',
					plugin_dir_url( __FILE__ ) . '/js/' . $js_file_name,
					array(
						'wp-api-fetch',
					),
					''
				);
				/**
				 * also this is a bit of a waste ,for simplicity we
				 * append popular post data on each page (frontend and admin)
				 * for now
				 */
				wp_localize_script(
					'yours59_matomo_popular_posts_frontend',
					'popular_post_data',
					array(
						'data' => self::deliver_popular_posts_data(),
					)
				);
			}
		);

	}






	/**
	 * takes 'cached' data and processes it for output
	 * --adjust-- this part for your needs
	 *
	 * @return     <type>  ( description_of_the_return_value )
	 */
	private function deliver_popular_posts_data() {
		$pop_posts = json_decode( $this->request_matomo_data(), true );
		/* use a cat id to filter some data by category */
		/* --adust -- */
		$cat  = 3;
		$cat3 = array_filter(
			$pop_posts,
			function( $pp ) use ( $cat ) {
				return in_array( $cat, $pp['cats'] );
			}
		);
		/* most read first */
		usort(
			$cat3,
			function ( $item1, $item2 ) {
				return $item2['hits'] <=> $item1['hits'];
			}
		);
		/* is cat id 4 */
		$cat  = 4;
		$cat4 = array_filter(
			$pop_posts,
			function( $pp ) use ( $cat ) {
				return in_array( $cat, $pp['cats'] );
			}
		);
		/* most read first*/
		usort(
			$cat4,
			function ( $item1, $item2 ) {
				return $item2['hits'] <=> $item1['hits'];
			}
		);
		/* unset the number of hits on frontend*/
		if ( ! is_admin() ) {
			for ( $i = 0; $i < count( $film ); $i++ ) {
				unset( $film[ $i ]['hits'] );
			}
			for ( $i = 0; $i < count( $kunst ); $i++ ) {
				unset( $kunst[ $i ]['hits'] );
			}
		}
		return rest_ensure_response(
			array(
				'cat3' => $cat3,
				'cat4' => $cat4,
			)
		);
	}

	/**
	 * when transient is expired a new
	 * request to matomo is made
	 * and parse_matomo_data is called with it
	 *
	 * ! be aware: data from matomo beginns at 00:00 (so its not 24h backwards)
	 *
	 * @return     array  prepared
	 */
	private function request_matomo_data() {

		/* check if we need updates */
		$valid_pop_posts = get_transient( 'yours59_matomo_popular_posts' );

		if ( ! $valid_pop_posts || strlen( $valid_pop_posts ) < 100 ) {
			$settings                        = get_option( 'matomo_popular_posts_settings' );
			$matomo_popular_posts_url        = $settings['matomo_popular_posts_url'];
			$matomo_popular_posts_auth_token = $settings['matomo_popular_posts_auth_token'];
			$args                            = array(
				'module'       => 'API',
				'method'       => 'Actions.getPageUrls',
				'idSite'       => '1',
				'date'         => 'today',
				'period'       => 'day',
				'format'       => 'json',
				'filter_limit' => '100',
				'expanded'     => '1',
				'token_auth'   => $matomo_popular_posts_auth_token,
			);
			$getparams                       = http_build_query( $args );
			$url                             = $matomo_popular_posts_url . '?' . $getparams;
			$response                        = wp_remote_get( $url );
			$valid_pop_posts                 = 'invalid response';
			/* here we have a valid response */
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				try {
					$result          = $response['body'];
					$valid_pop_posts = $this->parse_matomo_data( json_decode( $result, true ) );

				} catch ( Exception $e ) {
					if ( YOURS59_MATOMO_DEBUG ) {
						return array(
							'matomo_response' => $response['body'],
							'e'               => $e,
							'reason'          => 'error parsing matomo response',
						);
					}
				}
			} else {
				if ( YOURS59_MATOMO_DEBUG ) {
					return array(
						'matomo_response' => $response['body'],
						'reason'          => 'error requesting matomo',
					);
				}
			}
		}
		return $valid_pop_posts;
	}



	/**
	 * prepare data for use on the website
	 * tries to find a WordPress post-id (pid) from the urls given by matomo.
	 * when there is pid, WordPress data is appended (i.e. url of thumbnail, teaser-text)
	 *
	 * works for a valid response from a request to Actions.getPageUrls
	 *
	 * --adjust-- to your needs which data you want to have in frontend
	 *
	 * @param      array   $matomodata    The matomodata
	 * @param      bool    $debug_output  The debug output
	 *
	 * @return     string  json_encoded data
	 */
	private function parse_matomo_data( array $matomodata, $debug_output = false ) {
		/**
		 * matomo (Actions.getPageUrls) delivers a json with following structur
		 * level 1: years (2022)
		 * level 2: month (03)
		 * level 3: pages (we are interested in)
		 */
		if ( $debug_output ) {
			$out  = '<table>';
			$out .= '<thead>';
			$out .= '<tr>';
			$out .= '<td>Name</td>';
			$out .= '<td>No. Besucher</td>';
			$out .= '<td>Kat (3,4)</td>';
			$out .= '<td>Teaser</td>';
			$out .= '<td>Image</td>';
			$out .= '</tr>';
			$out .= '</thead>';
		}
		$struct = array();
		foreach ( $matomodata as $year ) {
			foreach ( $year['subtable'] as $month ) {
				foreach ( $month['subtable'] as $page ) {
					$url = $page['subtable'][0]['url'];
					/* --adjust-- I (larslo) use IS_LARSLO_DEV_ENVIRONMENT in wp-config to be able to split different systems */
					if ( IS_LARSLO_DEV_ENVIRONMENT ) {
						/* --adjust--*/
						/* current wordpress-system urls are need for url_to_postid */
						$url = str_replace( 'https://livesite.de', 'http://localhost', $url );
					}
					$pid = url_to_postid( $url );
					if ( $pid == 0 ) {
						continue;
					}
					$cats    = 0;
					$excerpt = '';
					$title   = '';
					$teaser  = '';
					$image   = '';
					$post    = get_post( $pid );
					$cats    = wp_get_post_categories( (int) $pid );
					/* --adjust-- these custom-fields used here */
					$title  = get_post_meta( $pid, 'artikeltexte_teaser_haupttitel', true );
					$teaser = get_post_meta( $pid, 'artikeltexte_teaser_anmoderation', true );
					$image  = get_post_meta( $pid, 'bilder_aufmacherfoto', 1 );
					/* --adjust-- you will not need that probably */
					if ( false === filter_var( $image, FILTER_VALIDATE_URL ) ) {
						$img_url_path = content_url() . '/files_mf/';
						$image        = $img_url_path . $image;
					}
					$struct[] = array(
						'label'  => $page['label'],
						'url'    => $page['subtable'][0]['url'],
						'hits'   => $page['subtable'][0]['nb_hits'],
						'pid'    => $pid,
						'cats'   => $cats,
						'title'  => $title,
						'teaser' => $teaser,
						'image'  => $image,
					);

					if ( $debug_output ) {
						$out .= '<tr>';
						$out .= sprintf( '<td>%s</td>', $pid . ' ' . $title );
						$out .= sprintf( '<td>%s</td>', $page['subtable'][0]['nb_hits'] );
						// $out     .= sprintf( '<td>%s</td>', $url );
						// $out     .= sprintf( '<td>%s</td>', $pid );
						$out .= sprintf( '<td>%s</td>', implode( ',', $cats ) );
						$out .= sprintf( '<td>%s</td>', $teaser );
						$out .= sprintf( '<td><img src="%s" style="max-width:70px;"></img></td>', $image );
						$out .= '</tr>';
					}
				}
			}
		}
		if ( $debug_output ) {
			$out .= '</table>';
		}
		/* saving results as transient*/
		$settings = get_option( 'matomo_popular_posts_settings' );
		$expire   = 3600; // 1 hour;
		if ( $settings['matomo_popular_posts_expires'] && (int) $settings['matomo_popular_posts_expires'] > 0 ) {
			$expire = (int) $settings['matomo_popular_posts_expires'] * 60;
		}
		/* cache data in a transient */
		set_transient( 'yours59_matomo_popular_posts', json_encode( $struct ), $expire );
		if ( $debug_output ) {
			return $out;
		}
	}

	private function set_admin_notice( string $plugin_name ) {
		$error  = '<div class="updated error"><p>';
		$error .= sprintf(
			esc_html__(
				'The plugin %1$s is not installed. It is necessary for plugin ',
				'yours59'
			),
			$plugin_name,
		);
		$error .= '<b>' . self::$name . '</b></p></div>';
		return $error;
	}

	private function check_required_plugins( array $config ) {
		/************************
		check for required plugins
		 ************************/
		foreach ( $config['required_plugins'] as $plugin ) {
			if ( 'class' == $plugin['type'] ) {
				if ( ! class_exists( $plugin['value'] ) ) {
					echo $this->set_admin_notice( $plugin['name'] );
				}
			}
			if ( 'function' == $plugin['type'] ) {
				if ( ! function_exists( $plugin['value'] ) ) {
					echo $this->set_admin_notice( $plugin['name'] );
				}
			}
		}
	}



	/**
	 * listings in dashboard are rendered via JS
	 * see js/yours59_matomo_popular_posts.uncompressed.js
	 * */
	public function render_dashboard_widget() {}

	/* make sure to tick them visible in WordPress admin */
	public function add_dashboard_widgets() {
			// Add custom dashbboard widget.
			add_meta_box(
				'dashboard_popular_posts_cat3',
				__( 'Populäre Posts - Cat 3 -', 'yours59' ),
				array( self::$instance, 'render_dashboard_widget' ), // data is inserted by js
				'dashboard',
				'column3',  // $context: 'advanced', 'normal', 'side', 'column3', 'column4'
				'high',     // $priority: 'high', 'core', 'default', 'low'
			);
			add_meta_box(
				'dashboard_popular_posts_cat4',
				__( 'Populäre Posts - Cat 4 -', 'yours59' ),
				array( self::$instance, 'render_dashboard_widget' ), // data is inserted by js
				'dashboard',
				'column3',  // $context: 'advanced', 'normal', 'side', 'column3', 'column4'
				'high',     // $priority: 'high', 'core', 'default', 'low'
			);
	}

	/* rest api callback
	 * not in use at the moment
	 * might provide a different way to render outputs
	*/
	public function endpoint_yours59_matomo_popular_posts_update_reviews( \WP_REST_Request $request ) {
		$cat       = $request['cat'];
		$pop_posts = json_decode( $this->request_matomo_data(), true );
		$by_cat    = array_filter(
			$pop_posts,
			function( $pp ) use ( $cat ) {
				return in_array( $cat, $pp['cats'] );
			}
		);
		/* most read first*/
		usort(
			$by_cat,
			function ( $item1, $item2 ) {
				return $item2['hits'] <=> $item1['hits'];
			}
		);
		return rest_ensure_response( $by_cat );
	}




	/**
	 * Settings page, done via CMB2 for simplicity
	 */
	public function add_settings_page() {

		$cmb_options = new_cmb2_box(
			array(
				'id'           => 'matomo_popular_posts_settings',
				'title'        => esc_html__( 'Matomo Popular Posts Settings', 'yours59' ),
				'object_types' => array( 'options-page' ),
				'option_key'   => 'matomo_popular_posts_settings', // The option key and admin menu page slug.
				'icon_url'     => 'dashicons-welcome-learn-more', // Menu icon. Only applicable if 'parent_slug' is left empty.
				'menu_title'   => esc_html__( 'Matomo Popular Posts Settings', 'yours59' ), // Falls back to 'title' (above).
				'parent_slug'  => 'options-general.php', // Make options page a submenu item of the themes menu. Comment it out to have a main menu item
				'capability'   => 'manage_options', // Cap required to view options-page.
				'position'     => 1000, // Menu position. Only applicable if 'parent_slug' is left empty.
				// 'display_cb'      => false, // Override the options-page form output (CMB2_Hookup::options_page_output()).
				// 'save_button'     => esc_html__( 'Save Theme Options', 'yours59' ), // The text for the options-page save button. Defaults to 'Save'.
			)
		);

		$cmb_options->add_field(
			array(
				'id'      => 'matomo_popular_posts_url',
				'name'    => __( 'Base URL of Matomo Instance', 'yours59' ),
				'type'    => 'text_url',
				// 'description' => __( '', 'yours59' ),
				'default' => '',
			)
		);
		$cmb_options->add_field(
			array(
				'id'          => 'matomo_popular_posts_auth_token',
				'name'        => __( 'Matomo Auth Token', 'yours59' ),
				'type'        => 'text',
				'description' => __( 'Matomo Auth Token, to get your auth-token see <a href="https://matomo.org/faq/general/faq_114/" target="_blank">here</a>', 'yours59' ),
				'default'     => '94eee842c4e55a04b336f6dcecf9297c',
			)
		);
		$cmb_options->add_field(
			array(
				'id'          => 'matomo_popular_posts_expires',
				'name'        => __( 'Time in MINUTES for caching popular posts', 'yours59' ),
				'type'        => 'text_small',
				'description' => __( 'Set this to a reasonable value, default is 60 (one hour). Updating popular posts needs ressources and should not be done too often.', 'yours59' ),
				'attributes'  => array(
					'type' => 'number',
				),
				'default'     => 60,
			)
		);

		/* inject some debug output*/
		if ( YOURS59_MATOMO_DEBUG ) {
			$data = self::request_matomo_data();

			$markup = json_encode( $data );

			$cmb_options->add_field(
				array(
					'before_row'   => $markup,
					'id'           => 'matomo_popular_posts_result',
					'type'         => '',
					'show_in_rest' => false,
				)
			);
		}

	} // admin page



} // class



