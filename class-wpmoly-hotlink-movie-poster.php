<?php
/**
 * WPMovieLibrary-Hotlink-Movie-Poster
 *
 * @package   WPMovieLibrary-Hotlink-Movie-Poster
 * @author    Filipe
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2016
 */

if ( ! class_exists( 'WPMovieLibrary_Hotlink_Movie_Poster' ) ) :

	/**
	* Plugin class
	*
	* @package WPMovieLibrary-Hotlink_Movie_Poster
	* @author  Fliipe
	*/
	class WPMovieLibrary_Hotlink_Movie_Poster extends WPMOLY_HT_Module {

		/**
		 * Initialize the plugin by setting localization and loading public scripts
		 * and styles.
		 *
		 * @since     1.0
		 */
		public function __construct() {

			$this->init();
		}

		/**
		 * Initializes variables
		 * WPMOLY_HT_Module Abstract method
		 * @since    1.0
		 */
		public function init() {

			$this->register_hook_callbacks();
		}

		/**
		 * Make sure WPMovieLibrary is active and compatible.
		 * 
		 * Deprecated since 2.0: causing bugs even though WPMOLY is
		 * installed and active.
		 *
		 * @since    1.0
		 * 
		 * @return   boolean    Requirements met or not?
		 */
		private function wpmoly_requirements_met() {

			$wpmoly_active  = is_wpmoly_active();
			$wpmoly_version = ( is_wpmoly_active() && version_compare( WPMOLY_VERSION, WPMOLYTR_REQUIRED_WPMOLY_VERSION, '>=' ) );

			if ( ! $wpmoly_active || ! $wpmoly_version )
				return false;

			return true;
		}

		/**
		 * Register callbacks for actions and filters
		 * WPMOLY_HT_Module Abstract method
		 * @since    1.0
		 */
		public function register_hook_callbacks() {

			add_action( 'plugins_loaded', 'wpmolymp_l10n' );

			add_action( 'activated_plugin', __CLASS__ . '::require_wpmoly_first' );

			// Enqueue scripts and styles
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			add_action( 'admin_post_thumbnail_html', __CLASS__ . '::load_posters_hotlink', 10, 2 );
			
			add_action( 'wp_ajax_wpmoly_set_featured_hotlink', __CLASS__ . '::set_featured_image_hotlink_callback' );
			
			add_filter( 'wp_get_attachment_url',  __CLASS__ . '::wpmoly_external_replace' );
		}
				
		public static function wpmoly_external_replace($url){
			 
			 $total = substr_count($url, 'http');			 
			if($total > 1){
				$uploads = wp_get_upload_dir();				
				return str_replace($uploads['baseurl']. "/","",$url);
			}
			 return $url;
		}
		
		/**
		 * Add a link to the current Post's Featured Image Metabox to trigger
		 * a Modal window. This will be used by the future Movie Posters
		 * selection Modal, yet to be implemented.
		 * 
		 * @since    1.0
		 * 
		 * @param    string    $content Current Post's Featured Image Metabox content, ready to be edited.
		 * @param    string    $post_id Current Post's ID (unused at that point)
		 * 
		 * @return   string    Updated $content
		 */
		 public static function load_posters_hotlink( $content, $post_id ) {

			 $post = get_post( $post_id );
			 if ( ! $post || 'movie' != get_post_type( $post ) )
				 return $content;
			//on click event is triggered by id
			 $content .= '<p class="hide-if-no-js"><a id="tmdb_load_posters_hotlink" href="#">' . __( 'See available Movie Posters to Hotlink', 'wpmovielibrary' ) . '</a></p>';
			 $content .= wpmoly_nonce_field( 'set-movie-poster-hotlink', false, false );

			 return $content;
		 }

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                     Plugin  Activate/Deactivate
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Fired when the plugin is activated.
		 *
		 * @since    1.0
		 *
		 * @param    boolean    $network_wide    True if WPMU superadmin uses
		 *                                       "Network Activate" action, false if
		 *                                       WPMU is disabled or plugin is
		 *                                       activated on an individual blog.
		 */
		public function activate( $network_wide ) {

			global $wpdb;

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				if ( $network_wide ) {
					$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog );
						$this->single_activate( $network_wide );
					}

					restore_current_blog();
				} else {
					$this->single_activate( $network_wide );
				}
			} else {
				$this->single_activate( $network_wide );
			}

		}

		/**
		 * Fired when the plugin is deactivated.
		 * 
		 * When deactivatin/uninstalling WPMOLY, adopt different behaviors depending
		 * on user options. Movies and Taxonomies can be kept as they are,
		 * converted to WordPress standars or removed. Default is conserve on
		 * deactivation, convert on uninstall.
		 *
		 * @since    1.0
		 */
		public function deactivate() {
		}

		/**
		 * Runs activation code on a new WPMS site when it's created
		 *
		 * @since    1.0
		 *
		 * @param    int    $blog_id
		 */
		public function activate_new_site( $blog_id ) {
			switch_to_blog( $blog_id );
			$this->single_activate( true );
			restore_current_blog();
		}

		/**
		 * Prepares a single blog to use the plugin
		 *
		 * @since    1.0
		 *
		 * @param    bool    $network_wide
		 */
		protected function single_activate( $network_wide ) {

			self::require_wpmoly_first();
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                     Scripts/Styles and Utils
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


		/**
		 * Register and enqueue public-facing style sheet.
		 *
		 * @since    1.0
		 */
		public function admin_enqueue_scripts() {

			//Extension script from wpmoly-media.js, doesn't work without it
			wp_enqueue_script( WPMOLYMP_SLUG . 'admin-js', WPMOLYMP_URL . '/assets/js/wpmoly-hotlink-movie-poster.js', array( WPMOLY_SLUG . '-admin' ), WPMOLYMP_VERSION, true );
		}

		/**
		 * Make sure the plugin is load after WPMovieLibrary and not
		 * before, which would result in errors and missing files.
		 *
		 * @since    1.0
		 */
		public static function require_wpmoly_first() {

			$this_plugin_path = plugin_dir_path( __FILE__ );
			$this_plugin      = basename( $this_plugin_path ) . '/wpmoly-hotlink-movie-poster.php';
			$active_plugins   = get_option( 'active_plugins' );
			$this_plugin_key  = array_search( $this_plugin, $active_plugins );
			$wpmoly_plugin_key  = array_search( 'wpmovielibrary/wpmovielibrary.php', $active_plugins );

			if ( $this_plugin_key < $wpmoly_plugin_key ) {

				unset( $active_plugins[ $this_plugin_key ] );
				$active_plugins = array_merge(
					array_slice( $active_plugins, 0, $wpmoly_plugin_key ),
					array( $this_plugin ),
					array_slice( $active_plugins, $wpmoly_plugin_key )
				);

				update_option( 'active_plugins', $active_plugins );
			}
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                               Callbacks
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
		
		/**
		 * Upload an image and set it as featured image of the submitted
		 * post.
		 * 
		 * Extract params from $_POST values. Image URL and post ID are
		 * required, title is optional. If no title is submitted file's
		 * basename will be used as image name.
		 * 
		 * Return the uploaded image ID to updated featured image preview
		 * in editor.
		 *
		 * @since    1.0
		 */
		public static function set_featured_image_hotlink_callback() {

			wpmoly_check_ajax_referer( 'set-movie-poster-hotlink' );

			$image_type = 'poster';	
			$image   = ( isset( $_POST['image'] )   && '' != $_POST['image']   ? $_POST['image']   : null );
			$post_id = ( isset( $_POST['post_id'] ) && '' != $_POST['post_id'] ? $_POST['post_id'] : null );
			$title   = ( isset( $_POST['title'] )   && '' != $_POST['title']   ? $_POST['title']   : null );
			$tmdb_id = ( isset( $_POST['tmdb_id'] ) && '' != $_POST['tmdb_id'] ? $_POST['tmdb_id'] : null );

			// if ( 1 != wpmoly_o( 'poster-featured' ) )
				// return new WP_Error( 'no_featured', __( 'Movie Posters as featured images option is active. Update your settings to deactivate this.', 'wpmovielibrary' ) );

			if ( is_null( $image ) || is_null( $post_id ) )
				return new WP_Error( 'invalid', __( 'An error occured when trying to import image: invalid data or Post ID.', 'wpmovielibrary' ) );

			$url = WPMOLY_TMDb::get_image_url( $image['file_path'], $image_type, wpmoly_o( 'poster-size' ) );					
			// Check the type of file. We'll use this as the 'post_mime_type'.
			$filetype = wp_check_filetype( $url, null );

			//Gather metadata to update_hotlink_attachment_meta (taken from class-wpmoly-media.php function update_attachment_meta)
			$tmdb_id    = WPMOLY_Movies::get_movie_meta( $post_id, 'tmdb_id' );
			$title      = WPMOLY_Movies::get_movie_meta( $post_id, 'title' );
			$production = WPMOLY_Movies::get_movie_meta( $post_id, 'production_companies' );
			$director   = WPMOLY_Movies::get_movie_meta( $post_id, 'director' );
			$original_title = WPMOLY_Movies::get_movie_meta( $post_id, 'original_title' );

			$production = explode( ',', $production );
			$production = trim( array_shift( $production ) );
			$year       = WPMOLY_Movies::get_movie_meta( $post_id, 'release_date' );
			$year       = apply_filters( 'wpmoly_format_movie_date',  $year, 'Y' );

			$find = array( '{title}', '{originaltitle}', '{year}', '{production}', '{director}' );
			$replace = array( $title, $original_title, $year, $production, $director );

			$_description = str_replace( $find, $replace, wpmoly_o( "{$image_type}-description" ) );
			$_title       = str_replace( $find, $replace, wpmoly_o( "{$image_type}-title" ) );

			// Prepare an array of post data for the attachment.
			$attachment = array(
				'guid'           => $url, 
				'post_mime_type' => $filetype['type'],
				'post_title'     => $_title, //preg_replace( '/\.[^.]+$/', '', basename( $url ) ),
				'post_content'   => $_description,
				'post_excerpt'   => $_description,
				'post_status'    => 'inherit'
			);
			// Insert the attachment.
			$attach_id = wp_insert_attachment( $attachment, $url );		
			if ( is_wp_error( $attach_id ) ) {
				return new WP_Error( $attach_id->get_error_code(), $attach_id->get_error_message() );
			}
			
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			// Generate the metadata for the attachment, and update the database record.
			wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $url ) );
			set_post_thumbnail( $post_id, $attach_id );
			
			//TODO: Commented because it return 200 but still enters on js error function
			//wpmoly_ajax_response( $attach_id );
		}		
	}
endif;