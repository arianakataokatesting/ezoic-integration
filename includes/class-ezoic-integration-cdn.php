<?php

namespace Ezoic_Namespace;


use WP_Error;
use WP_Post;
use WP_Comment;
use WP_Theme;

class Ezoic_Integration_CDN {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;


	private $ezoic_cdn_already_purged = array();
	private $ezoic_cdn_keys_purged = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Helper Function to retrieve the API Key from WordPress Options
	 *
	 * @param boolean $refresh Set to true if you want to force re-fetching of the option rather than use static version.
	 *
	 * @return string API Key
	 * @since 1.0.0
	 */
	public static function ezoic_cdn_api_key( $refresh = false ) {
		static $api_key = null;
		if ( is_null( $api_key ) || $refresh ) {
			$api_key = get_option( 'ezoic_cdn_api_key' );
		}

		return $api_key;
	}


	/**
	 * Helper function to get the Ezoic Domain from the WordPress Options
	 *
	 * @param boolean $default Set to true if you want to generate the domain from WordPress Site URL.
	 *
	 * @return string Domain Name as defined in Ezoic
	 * @since 1.1.1
	 */
	public static function ezoic_cdn_get_domain( $default = false ) {
		static $cdn_domain = null;

		if ( is_null( $cdn_domain ) && ! $default ) {
			$cdn_domain = get_option( 'ezoic_cdn_domain' );
		}
		if ( ! $cdn_domain || $default ) {
			$cdn_domain = wp_parse_url( get_site_url(), PHP_URL_HOST );
			$cdn_domain = preg_replace( '@^www\.@msi', '', $cdn_domain );
		}

		return $cdn_domain;
	}

	/**
	 * Helper function to determine if auto-purging of the Ezoic CDN is enabled or not.
	 *
	 * Note if there is not an API key stored, this is always false.
	 *
	 * @param boolean $refresh Set to true if you want to re-fetch the option instead of using static variable.
	 *
	 * @return boolean
	 * @see ezoic_cdn_api_key()
	 * @since 1.0.0
	 */
	public static function ezoic_cdn_is_enabled( $refresh = false ) {
		static $cdn_enabled = null;
		if ( ! self::ezoic_cdn_api_key() ) {
			return false;
		}
		if ( is_null( $cdn_enabled ) || $refresh ) {
			$cdn_enabled = ( get_option( 'ezoic_cdn_enabled', 'on' ) === 'on' );
		}

		return $cdn_enabled;
	}

	/**
	 * Helper Function to determine if we are always purging the home page when purging anything.
	 *
	 * @param boolean $refresh Set to true if you want to re-fetch the option instead of using static variable.
	 *
	 * @return boolean
	 * @since 1.1.2
	 */
	public static function ezoic_cdn_always_purge_home( $refresh = false ) {
		static $always_home = null;
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return false;
		}
		if ( is_null( $always_home ) || $refresh ) {
			$always_home = ( get_option( 'ezoic_cdn_always_home', 'on' ) === 'on' );
		}

		return boolval( $always_home );
	}


	/**
	 * Helper function to determine if verbose mode is on.
	 *
	 * @param boolean $refresh Set to true if you want to re-fetch the option instead of using the static variable.
	 *
	 * @return boolean
	 * @since 1.1.2
	 */
	public static function ezoic_cdn_verbose_mode( $refresh = false ) {
		static $verbose_mode = null;
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return false;
		}
		if ( is_null( $verbose_mode ) || $refresh ) {
			$verbose_mode = ( get_option( 'ezoic_cdn_verbose_mode', 'off' ) === 'on' );
		}

		return boolval( $verbose_mode );
	}


	/**
	 * Implementation of post_updated action
	 *
	 * When a post is modified, clear Ezoic CDN cache for the post URL and all related archive pages (both before and after the change)
	 *
	 * @param int $post_id ID of the Post that has been modified.
	 * @param WP_Post $old_post The WordPress Post object of the post before modification.
	 * @param WP_Post $new_post The WordPress Post object of the post after modification.
	 *
	 * @return void
	 * @see ezoic_cdn_clear_urls()
	 * @since 1.0.0
	 */
	function ezoic_cdn_post_updated( $post_id, WP_Post $old_post, WP_Post $new_post ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		if ( wp_is_post_revision( $new_post ) ) {
			return;
		}

		// If the post wasn't published before and isn't published now, there is no need to purge anything.
		if ( 'publish' !== $old_post->post_status && 'publish' !== $new_post->post_status ) {
			return;
		}

		$urls = $this->ezoic_cdn_get_recache_urls_by_post( $post_id, $old_post );
		$urls = array_merge( $urls, $this->ezoic_cdn_get_recache_urls_by_post( $post_id, $new_post ) );
		$urls = array_unique( $urls );

		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id, $old_post );
		$keys = array_merge( $keys, self::ezoic_cdn_get_surrogate_keys_by_post( $post_id, $new_post ) );
		$keys = array_unique( $keys );

		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}

	/**
	 * Determines list of URLs related to a post that should be recached when the post is updated
	 *
	 * @param int $post_id ID of the Post.
	 * @param WP_Post $post WordPress post object (found with get_post if omitted).
	 *
	 * @return array $urls Array of URLs to be recached for a given post
	 * @since 1.1.0 Added support for custom taxonomies and author archives.
	 * @since 1.0.0
	 */
	function ezoic_cdn_get_recache_urls_by_post( $post_id, WP_Post $post = null ) {
		if ( ! $post ) {
			$post = get_post( $post_id );
		}

		$urls = array();

		$urls[] = get_permalink( $post );
		if ( 'page' !== $post->post_type ) {
			$urls[] = get_post_type_archive_link( $post->post_type );
		}

		$categories = get_the_terms( $post, 'category' );
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$urls[] = get_term_link( $category );
				$urls[] = get_category_feed_link( $category->term_id, 'atom' );
				$urls[] = get_category_feed_link( $category->term_id, 'rss2' );
			}
		}

		$tags = get_the_terms( $post, 'post_tag' );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$urls[] = get_term_link( $tag );
				$urls[] = get_tag_feed_link( $tag->term_id, 'atom' );
				$urls[] = get_tag_feed_link( $tag->term_id, 'rss2' );
			}
		}

		$taxonomies = get_object_taxonomies( $post, 'names' );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( in_array( $taxonomy, array( 'category', 'post_tag', 'author' ), true ) ) {
					continue;
				}

				$terms = get_the_terms( $post, $taxonomy );
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$urls[] = get_term_link( $term, $taxonomy );
						$urls[] = get_term_feed_link( $term->term_id, $taxonomy, 'atom' );
						$urls[] = get_term_feed_link( $term->term_id, $taxonomy, 'rss2' );
					}
				}
			}
		}

		$urls[] = get_author_posts_url( $post->post_author );
		$urls[] = get_author_feed_link( $post->post_author, 'atom' );
		$urls[] = get_author_feed_link( $post->post_author, 'rss2' );

		if ( function_exists( 'coauthors' ) ) {
			$authors = get_coauthors( $post_id );
			if ( $authors ) {
				foreach ( $authors as $author ) {
					$urls[] = get_author_posts_url( $author->ID, $author->user_nicename );
					$urls[] = get_author_feed_link( $author->ID, 'atom' );
					$urls[] = get_author_feed_link( $author->ID, 'rss2' );
				}
			}
		}

		if ( comments_open( $post ) ) {
			$urls[] = get_bloginfo( 'comments_atom_url' );
			$urls[] = get_bloginfo( 'comments_rss2_url' );
			$urls[] = get_post_comments_feed_link( $post_id, 'atom' );
			$urls[] = get_post_comments_feed_link( $post_id, 'rss2' );
		}

		if ( Ezoic_Integration_CDN::ezoic_cdn_always_purge_home() ) {
			$urls[] = get_site_url( null, '/' );
			$urls[] = get_home_url( null, '/' );
		}

		if ( 'post' !== $post->post_type ) {
			return $urls;
		}

		$urls[] = get_bloginfo( 'atom_url' );
		$urls[] = get_bloginfo( 'rss_url' );
		$urls[] = get_bloginfo( 'rss2_url' );
		$urls[] = get_bloginfo( 'rdf_url' );

		$date   = strtotime( $post->post_date );
		$urls[] = get_year_link( gmdate( 'Y', $date ) );
		$urls[] = get_month_link( gmdate( 'Y', $date ), gmdate( 'm', $date ) );
		$urls[] = get_day_link( gmdate( 'Y', $date ), gmdate( 'm', $date ), gmdate( 'j', $date ) );

		return $urls;
	}


	/**
	 * Uses Ezoic CDN API to purge cache for a single URL
	 *
	 * @param string $url URL to purge from Ezoic CDN Cache.
	 *
	 * @return array|void|WP_Error wp_remote_post() response array
	 * @since 1.0.0
	 */
	function ezoic_cdn_clear_url( $url = null ) {
		global $ezoic_cdn_already_purged;

		if ( in_array( $url, $ezoic_cdn_already_purged, true ) ) {
			return;
		}

		$api_url = 'https://api-gateway.ezoic.com/gateway/cdnservices/clearcache?developerKey=' . self::ezoic_cdn_api_key();

		$verbose = self::ezoic_cdn_verbose_mode();

		$args = array(
			'timeout'     => 45,
			'blocking'    => $verbose,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( array( 'url' => $url ) ),
		);

		$results = wp_remote_post( $api_url, $args );

		if ( $verbose ) {
			self::ezoic_cdn_add_notice( 'Single URL', $results, $url );
		}

		$ezoic_cdn_already_purged[] = $url;

		self::ezoic_cdn_purge_home();

		return $results;
	}

	/**
	 * Uses Ezoic CDN API to purge cache for an array of URLs
	 *
	 * @param array $urls List of URLs to purge from Ezoic Cache.
	 * @param bool $scheduled True if this is a scheduled run of this removal request.
	 *
	 * @return array|void|WP_Error wp_remote_post() response array
	 * @since 1.1.3 Once a removal has been submitted, submit another one 1 minute later.
	 * @since 1.0.0
	 */
	function ezoic_cdn_clear_urls( $urls = array(), $scheduled = false ) {

		$urls = array_unique( array_diff( $urls, $this->ezoic_cdn_already_purged ) );

		if ( ! $urls ) {
			return;
		}

		$api_url = 'https://api-gateway.ezoic.com/gateway/cdnservices/bulkclearcache?developerKey=' . self::ezoic_cdn_api_key();

		$verbose = self::ezoic_cdn_verbose_mode();

		$args = array(
			'timeout'     => 45,
			'blocking'    => $verbose,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( array( 'urls' => array_values( $urls ) ) ),
		);

		$results = wp_remote_post( $api_url, $args );

		$this->ezoic_cdn_already_purged = array_merge( $this->ezoic_cdn_already_purged, $urls );

		if ( $verbose ) {
			$label = ( $scheduled ) ? 'Scheduled Purge' : 'Bulk Purge';
			self::ezoic_cdn_add_notice( $label, $results, $urls );
		}

		return $results;
	}

	/**
	 * Pings Ezoic CDN API for successful integration
	 *
	 * @return array|void|WP_Error wp_remote_post() response array
	 * @since 1.1.3 Once a removal has been submitted, submit another one 1 minute later.
	 * @since 1.0.0
	 */
	public static function ezoic_cdn_ping() {

		if ( empty( self::ezoic_cdn_api_key() ) ) {
			return array( false, "Please enter a valid CDN API key." );
		}

		$api_url = 'https://api-gateway.ezoic.com/gateway/cdnservices/ping?developerKey=' . self::ezoic_cdn_api_key();

		$args = array(
			'timeout'     => 45,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => '',
		);

		$results = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $results ) ) {
			$error_string = $results->get_error_message();
			if ( is_array( $error_string ) || is_object( $error_string ) ) {
				return array( false, print_r( $error_string, true ) );
			} else {
				return array( false, $error_string );
			}
		} else {
			$response = json_decode( $results['body'], true );
			if ( $response && is_array( $response ) && $response['Success'] ) {
				// successfully busted cache!
				return array( true, "" );
			} else {
				// error
				error_log( 'Error accessing Ezoic API: ' . $response['Error'] );

				return array( false, $response['Error'] );
			}
		}
	}

	/**
	 * Add an admin notice for verbose mode
	 *
	 * @param string $label Label for the notice.
	 * @param mixed $results The verbose output.
	 * @param mixed $params Any parameters relevant to the submission.
	 * @param string $class Notice Class.
	 *
	 * @return void
	 * @since 1.1.2
	 */
	function ezoic_cdn_add_notice( $label, $results, $params = null, $class = 'info' ) {
		static $notices = array();

		$raw = null;

		if ( ! $notices ) {
			$notices = get_transient( 'ezoic_cdn_admin_notice' );
		}

		if ( is_array( $results ) && ! empty( $results['response'] ) && ! empty( $results['body'] ) ) {
			$raw = $results;

			$results         = $raw['response'];
			$results['body'] = $raw['body'];
		}

		$notices[] = array(
			'label'   => $label,
			'results' => $results,
			'params'  => $params,
			'class'   => $class,
			'raw'     => $raw,
		);

		set_transient( 'ezoic_cdn_admin_notice', $notices, 600 );
	}


	/**
	 * Determines list of SurrogateKeys related to a post that should be recached when the post is updated
	 *
	 * @param int $post_id ID of the Post.
	 * @param WP_Post $post WordPress post object (found with get_post if omitted).
	 *
	 * @return array $keys Array of Surrogate Keys to be recached for a given post
	 * @since 1.2.0
	 */
	function ezoic_cdn_get_surrogate_keys_by_post( $post_id, WP_Post $post = null ) {
		if ( ! $post ) {
			$post = get_post( $post_id );
		}

		$keys = array();

		$keys[] = "single-{$post_id}";

		$categories = get_the_terms( $post, 'category' );
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$keys[] = "category-{$category->term_id}";
				$keys[] = "category-{$category->slug}";
			}
		}

		$tags = get_the_terms( $post, 'post_tag' );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$keys[] = "tag-{$tag->term_id}";
				$keys[] = "tag-{$tag->slug}";
			}
		}

		$taxonomies = get_object_taxonomies( $post, 'names' );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( in_array( $taxonomy, array( 'category', 'post_tag', 'author' ), true ) ) {
					continue;
				}

				$terms = get_the_terms( $post, $taxonomy );
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$keys[] = "tax-{$taxonomy}-{$term->term_id}";
						$keys[] = "tax-{$taxonomy}-{$term->slug}";
					}
				}
			}
		}

		$keys[] = 'author-' . get_the_author_meta( 'user_nicename', $post->post_author );

		if ( function_exists( 'coauthors' ) ) {
			$authors = get_coauthors( $post_id );
			if ( $authors ) {
				foreach ( $authors as $author ) {
					$keys[] = "author-{$author->user_nicename}";
				}
			}
		}

		if ( self::ezoic_cdn_always_purge_home() ) {
			$keys[] = 'front';
			$keys[] = 'home';
		}

		if ( 'post' !== $post->post_type ) {
			return array_unique( $keys );
		}

		$date   = strtotime( $post->post_date );
		$keys[] = 'date-' . gmdate( 'Y', $date );
		$keys[] = 'date-' . gmdate( 'Ym', $date );
		$keys[] = 'date-' . gmdate( 'Ymd', $date );

		return array_unique( $keys );
	}


	/**
	 * Purge pages from Ezoic CDN by Surrogate Keys
	 *
	 * @param array $keys Array of Surrogate Keys to purge from Ezoic CDN cache.
	 * @param string $domain Domain Name to purge for.
	 *
	 * @return array|void|WP_Error wp_remote_post() response array
	 * @since 1.2.0
	 */
	function ezoic_cdn_clear_surrogate_keys( $keys = array(), $domain = null ) {

		if ( ! $domain ) {
			$domain = self::ezoic_cdn_get_domain();
		}

		$keys = array_unique( array_diff( $keys, $this->ezoic_cdn_keys_purged ) );

		if ( ! $keys ) {
			return;
		}

		$api_url = 'https://api-gateway.ezoic.com/gateway/cdnservices/clearbysurrogatekeys?developerKey=' . self::ezoic_cdn_api_key();

		$verbose = self::ezoic_cdn_verbose_mode();

		$args = array(
			'timeout'     => 45,
			'blocking'    => $verbose,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode(
				array(
					'keys'   => implode( ',', $keys ),
					'domain' => $domain,
				)
			),
		);

		$results = wp_remote_post( $api_url, $args );

		$this->ezoic_cdn_keys_purged = array_merge( $this->ezoic_cdn_keys_purged, $keys );

		if ( $verbose ) {
			self::ezoic_cdn_add_notice( 'Surrogate Key Purge', $results, $keys );
		}

		return $results;
	}


	/**
	 * Set Cache Headers for Ezoic CDN
	 *
	 * Sets Cache-Control and Last-Modified headers as well as Surrogate Keys headers used for recaching whole archives including pagination.
	 *
	 * @return void
	 * @since 1.2.0
	 */
	function ezoic_cdn_add_headers() {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		global $wp_query;

		$object         = get_queried_object();
		$surrogate_keys = array();
		$last_modified  = time();

		$browser_max_age = 60 * 60; // Browser Cache pages 1 hour.
		$server_max_age  = 86400 * 365 * 3; // Server Cache pages 3 years.

		if ( is_singular() ) {
			$surrogate_keys[] = 'single';
			$surrogate_keys[] = 'single-' . get_post_type();
			$surrogate_keys[] = 'single-' . get_the_ID();

			$last_modified = strtotime( $object->post_modified );
		} elseif ( is_archive() ) {
			$surrogate_keys[] = 'archive';
			if ( is_category() ) {
				$surrogate_keys[] = 'category';

				$surrogate_keys[] = 'category-' . $object->slug;
				$surrogate_keys[] = 'category-' . $object->term_id;
			} elseif ( is_tag() ) {
				$surrogate_keys[] = 'tag';
				$surrogate_keys[] = 'tag-' . $object->slug;
				$surrogate_keys[] = 'tag-' . $object->term_id;
			} elseif ( is_tax() ) {
				$surrogate_keys[] = 'tax';
				$surrogate_keys[] = "tax-{$object->taxonomy}";
				$surrogate_keys[] = "tax-{$object->taxonomy}-{$object->slug}";
				$surrogate_keys[] = "tax-{$object->taxonomy}-{$object->term_id}";
			} elseif ( is_date() ) {
				$surrogate_keys[] = 'date';
				if ( is_day() ) {
					$surrogate_keys[] = 'date-day';
					$surrogate_keys[] = "date-{$wp_query->query_vars['year']}{$wp_query->query_vars['monthnum']}{$wp_query->query_vars['day']}";
				} elseif ( is_month() ) {
					$surrogate_keys[] = 'date-month';
					$surrogate_keys[] = "date-{$wp_query->query_vars['year']}{$wp_query->query_vars['monthnum']}";
				} elseif ( is_year() ) {
					$surrogate_keys[] = 'date-year';
					$surrogate_keys[] = "date-{$wp_query->query_vars['year']}";
				}
			} elseif ( is_author() ) {
				$surrogate_keys[] = 'author';
				$surrogate_keys[] = "author-{$object->user_nicename}";
			} elseif ( is_post_type_archive() ) {
				$surrogate_keys[] = 'type-' . get_post_type();
			}

			$paged = get_query_var( 'pagenum' ) ? get_query_var( 'pagenum' ) : false;
			if ( ! $paged && get_query_var( 'paged' ) ) {
				$paged = get_query_var( 'paged' );
			}
			if ( $paged ) {
				$surrogate_keys[] = 'paged';
				$surrogate_keys[] = "paged-{$paged}";
			}
		}

		if ( is_front_page() ) {
			$surrogate_keys[] = 'front';
			$browser_max_age  = 600;   // Home page likely changes frequently, browser cache only 10 minutes.
			$server_max_age   = 86400; // Home page likely changes frequently, server cache only 1 day.
		}
		if ( is_home() ) {
			$surrogate_keys[] = 'home';
			$browser_max_age  = 600;
			$server_max_age   = 86400;
		}

		if ( ! headers_sent() ) {
			if ( is_user_logged_in() ) {
				header( 'Cache-Control: max-age=0, no-store', true );
				header_remove( 'Expires' );
				header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', $last_modified ), true );
			} else {
				header( "Cache-Control: max-age={$browser_max_age}, s-maxage={$server_max_age}, public", true );
				header_remove( 'Expires' );
				header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', $last_modified ), true );
			}
			if ( $surrogate_keys ) {
				header( 'Surrogate-Key: ' . implode( ' ', $surrogate_keys ), true );
			}
		}
	}


	/**
	 * Implementation of save_post action, like the updated one but for new posts.
	 *
	 * @param int $post_id ID of the post created or updated.
	 * @param WP_Post $post The WP_Post object.
	 * @param boolean $update true if this is an update of an existing post.
	 *
	 * @return void
	 * @since 1.1.3
	 */
	function ezoic_cdn_save_post( $post_id, WP_Post $post, $update = false ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		// If this is an update to an existing post, this will be handled by the post_updated action instead.
		if ( $update ) {
			return;
		}
		// No need to purge anything if the new post isn't published.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id, $post );
		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id, $post );
		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}

	/**
	 * Implementation of comment_post action
	 *
	 * When a comment is saved, clear Ezoic CDN cache for the post URL and all related archive pages
	 *
	 * @param int        $comment_id  ID of the Comment that has been modified.
	 * @param int|string $comment_approved  Whether the comment is approved 1, 0, "spam".
	 * @param array      $commentdata  The comment data.
	 *
	 * @return void
	 * @since 1.3.0
	 */
	function ezoic_cdn_comment_post( $comment_id, $comment_approved, $commentdata ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		if ( 0 === $comment_approved || 'spam' === $comment_approved ) {
			return;
		}

		$comment = get_comment( $comment_id );
		$post_id = $comment->comment_post_ID;

		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id );
		$urls = array_unique( $urls );

		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id );
		$keys = array_unique( $keys );

		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}
	/**
	 * Implementation of edit_comment action
	 *
	 * When a comment is modified, clear Ezoic CDN cache for the post URL and all related archive pages (both before and after the change)
	 *
	 * @param int   $comment_id  ID of the Comment that has been modified.
	 * @param array $data  The comment data.
	 * *
	 * @return void
	 * @since 1.3.0
	 */
	function ezoic_cdn_edit_comment( $comment_id, $data ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		$comment = get_comment( $comment_id );
		$post_id = $comment->comment_post_ID;

		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id );
		$urls = array_unique( $urls );

		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id );
		$keys = array_unique( $keys );

		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}

	/**
	 * Implementation of delete_comment action
	 *
	 * When a comment is trashed or deleted, clear Ezoic CDN cache for the post URL and all related archive pages (both before and after the change)
	 *
	 * @param int   $comment_id  ID of the Comment that has been modified.
	 * @param WP_Comment $comment The WP_Comment object.
	 * *
	 * @return void
	 * @since 1.3.0
	 */
	function ezoic_cdn_delete_comment( $comment_id, WP_Comment $comment ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		$post_id = $comment->comment_post_ID;

		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id );
		$urls = array_unique( $urls );

		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id );
		$keys = array_unique( $keys );

		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}

	/**
	 * Implementation of wp_set_comment_status
	 *
	 * When a comment's status changes, clear the cache if the new status is 'approved'
	 *
	 * @param int		$comment_id ID of the comment whose status has changed
	 * @param string	$comment_status The new status of the comment
	 *
	 * @return void
	 */
	function ezoic_cdn_comment_change_status( $comment_id, $comment_status ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		if ( 'approve' === $comment_status ) {
			$comment = get_comment( $comment_id );
			$post_id = $comment->comment_post_ID;

			$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id );
			$urls = array_unique( $urls );

			self::ezoic_cdn_clear_urls( $urls );

			$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id );
			$keys = array_unique( $keys );

			self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
		}
	}

	/**
	 * Implementation of activate plugin action
	 *
	 * When a plugin is activated, clear Ezoic CDN cache for the domain
	 *
	 * @since 1.6.3
	 * @param string  $plugin  Path to the plugin file.
	 * @param boolean $network_wide Enable the plugin for all sites inthe network.
	 * @return void
	 */
	function ezoic_cdn_activated_plugin( $plugin, $network_wide ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	/**
	 * Implementation of delete plugin action
	 *
	 * When a plugin is deleted, clear Ezoic CDN cache for the domain
	 *
	 * @since 1.6.3
	 * @param string  $plugin  Path to the plugin file.
	 * @param boolean $deleted  Whether the plugin was deleted.
	 * @return void
	 */
	function ezoic_cdn_deleted_plugin( $plugin, $deleted ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		if ( ! $deleted ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	/**
	 * Implementation of deactivated plugin action
	 *
	 * When a plugin is deactivated, clear Ezoic CDN cache for the domain
	 *
	 * @since 1.6.3
	 * @param string  $plugin  Path to the plugin file.
	 * @param boolean $network_deactivating  Whether the plugin is deactivated for all sites in the network or just the current site.
	 * @return void
	 */
	function ezoic_cdn_deactivated_plugin( $plugin, $network_deactivating ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	/**
	 * Implementation of switch theme action
	 *
	 * When a theme is switched, clear Ezoic CDN cache for the domain
	 *
	 * @since 1.6.3
	 * @param string   $new_name  Name of new theme.
	 * @param WP_Theme $new_theme  the new Wp Theme.
	 * @param WP_Theme $old_theme  the old Wp Theme.
	 * @return void
	 */
	function ezoic_cdn_switch_theme( $new_name, WP_Theme $new_theme, WP_Theme $old_theme ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	/**
	 * Verbose Mode output notices
	 *
	 * @return void
	 * @since 1.1.2
	 */
	function ezoic_cdn_display_admin_notices() {
		if ( ! self::ezoic_cdn_verbose_mode() ) {
			return;
		}
		$notices = get_transient( 'ezoic_cdn_admin_notice' );
		if ( ! $notices ) {
			return;
		}

		foreach ( $notices as $key => $notice ) {
			?>
            <div class="notice notice-<?php echo esc_attr( $notice['class'] ); ?> is-dismissable">
                <p><strong>Ezoic CDN Notice <?php echo esc_attr( $key ); ?>
                        : <?php echo esc_attr( $notice['label'] ); ?></strong></p>
				<?php
				echo '<pre>Input: ';
				print_r( $notice['params'] );
				echo "\nResult: ";
				print_r( $notice['results'] );
				echo '</pre>';
				echo '<!-- Raw Results: ';
				print_r( $notice['raw'] );
				echo '-->';
				?>
            </div>
			<?php
		}

		delete_transient( 'ezoic_cdn_admin_notice' );
	}


	/**
	 * Implementation of after_delete_post action
	 *
	 * When a post is deleted, clear Ezoic CDN cache for the post URL, and all related archive pages
	 *
	 * @param int $post_id ID of the deleted post.
	 * @param WP_Post|null $old_post WordPress Post object as it was before deletion.
	 *
	 * @return void
	 * @see ezoic_cdn_clear_urls()
	 * @since 1.0.0
	 */
	function ezoic_cdn_post_deleted( $post_id, WP_Post $old_post = null ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		if ( empty( $old_post ) ) {
			$old_post = get_post( $post_id );
		}

		if ( wp_is_post_revision( $old_post ) ) {
			return;
		}

		if ( 'publish' !== $old_post->post_status ) {
			return;
		}

		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id, $old_post );
		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id, $old_post );
		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}


	/**
	 * Our own action to use with scheduling cache clears.
	 *
	 * @param array $urls List of URLs to purge from Ezoic Cache.
	 *
	 * @return void
	 * @since 1.1.3
	 */
	function ezoic_cdn_scheduled_clear_action( $urls = array() ) {
		self::ezoic_cdn_clear_urls( $urls, true );
	}


	/**
	 * Implementation of all of the following actions: w3tc_flush_posts, w3tc_flush_all, and wp_cache_cleared
	 *
	 * Completely purges Ezoic CDN cache for domain when these caches are purged
	 *
	 * @return void
	 * @since 1.1.2 auto-purge home when configured
	 * @since 1.1.1
	 */
	function ezoic_cdn_cachehook_purge_posts_action() {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	/**
	 * Implementation of w3tc_flush_post action
	 *
	 * Purges Ezoic CDN Cache when a post is flushed by the W3TC plugin
	 *
	 * @param int $post_id ID of the Post.
	 *
	 * @return void|bool
	 * @since 1.1.1
	 * @since 1.1.2 auto-purge home when configured
	 */
	function ezoic_cdn_cachehook_purge_post_action( $post_id = null ) {
		if ( ! self::ezoic_cdn_is_enabled() || ! $post_id ) {
			return;
		}
		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id );
		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id );
		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );

		return true;
	}


	/**
	 * Implementation of rocket_purge_cache action
	 *
	 * When WP-Rocket purges cache for various page types, also purge the corresponding URLs from the Ezoic CDN
	 *
	 * @param string $type Type of cache clearance: 'all', 'post', 'term', 'user', 'url'.
	 * @param int $id The post ID, term ID, or user ID being cleared. 0 when $type is not 'post', 'term', or 'user'.
	 * @param string $taxonomy The taxonomy the term being cleared belong to. '' when $type is not 'term'.
	 * @param string $url The URL being cleared. '' when $type is not 'url'.
	 *
	 * @return array|void|WP_Error
	 * @since 1.1.2 Added support for WP-Rockets 'term' and 'url' based purges as well
	 * @since 1.1.1
	 */
	function ezoic_cdn_rocket_purge_action( $type = 'all', $id = 0, $taxonomy = '', $url = '' ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		switch ( $type ) {
			case 'all':
				return self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
			case 'post':
				$urls = self::ezoic_cdn_get_recache_urls_by_post( $id );
				self::ezoic_cdn_clear_urls( $urls );

				$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $id );
				self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );

				return;
			case 'term':
				$urls   = array();
				$urls[] = get_term_link( $id, $taxonomy );
				$urls[] = get_term_feed_link( $id, $taxonomy, 'atom' );
				$urls[] = get_term_feed_link( $id, $taxonomy, 'rss2' );
				self::ezoic_cdn_clear_urls( $urls );

				$term = get_term( $id, $taxonomy );

				if ( 'category' === $taxonomy ) {
					$keys[] = "category-{$id}";
					$keys[] = "category-{$term->slug}";
				} elseif ( 'post_tag' === $taxonomy ) {
					$keys[] = "tag-{$id}";
					$keys[] = "tag-{$term->slug}";
				} else {
					$keys[] = "tax-{$taxonomy}-{$id}";
					$keys[] = "tax-{$taxonomy}-{$term->slug}";
				}
				self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );

				return;
			case 'url':
				$urls = array( $url );
				self::ezoic_cdn_clear_urls( $urls );

				return;
		}
	}

	/**
	 * Implementation of after_rocket_clean_post action
	 *
	 * When WP Rocket plugin purges local cache for a post, also clear appropriate urls from the Ezoic CDN.
	 *
	 * @param WP_Post $post The post object.
	 * @param array $purge_urls URLs cache files to remove.
	 * @param string $lang The post language.
	 *
	 * @return void
	 * @since 1.1.1
	 * @since 1.1.2 Added support for purging all the urls passed in $purge_urls as well
	 */
	function ezoic_cdn_rocket_clean_post_action( $post, $purge_urls = array(), $lang = '' ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post->ID, $post );
		$urls = array_merge( $urls, $purge_urls );
		$urls = array_unique( $urls );
		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post->ID, $post );
		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}

	/**
	 * Uses Ezoic CDN API to purge cache for an entire domain
	 *
	 * @param string $domain Domain Name to purge Ezoic Cache for.
	 *
	 * @return array|WP_Error wp_remote_post() response array
	 * @since 1.0.0
	 */
	function ezoic_cdn_purge( $domain = null ) {
		$api_url = 'https://api-gateway.ezoic.com/gateway/cdnservices/purgecache?developerKey=' . self::ezoic_cdn_api_key();

		$verbose = self::ezoic_cdn_verbose_mode();

		$args = array(
			'timeout'     => 45,
			'blocking'    => $verbose,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( array( 'domain' => $domain ) ),
		);

		$results = wp_remote_post( $api_url, $args );

		if ( $verbose ) {
			self::ezoic_cdn_add_notice( 'Purge', $results, array( 'domain' => $domain ) );
		}

		return $results;
	}


	/**
	 * When purging for any other reason, submit a separate purge of the home page
	 *
	 * @return boolean|array|WP_Error Returns false if not set to auto-purge home page, otherwise returns the response from doing separate purge.
	 * @since 1.1.2
	 */
	function ezoic_cdn_purge_home() {
		if ( ! self::ezoic_cdn_always_purge_home() ) {
			return false;
		}

		$urls = array(
			get_site_url(),
			get_home_url(),
			get_post_type_archive_link( 'post' ),
		);

		$urls = array_unique( $urls );

		return self::ezoic_cdn_clear_urls( $urls );
	}


}

?>
