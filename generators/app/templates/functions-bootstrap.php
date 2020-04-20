<?php
/**
 * <%= themename %> version <%= version %>
 * <%= repository %>
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if(file_exists($composer_autoload)) {
	require_once $composer_autoload;
	$timber = new Timber\Timber();
}

/**
 * Create a new SCSS compiler for online compiling of SCSS after ACF Pro custom field changes.
 */

use ScssPhp\ScssPhp\Compiler;
$scss = new Compiler();
$scss->setImportPaths(array(
  get_stylesheet_directory() . '/vendor/',
  get_stylesheet_directory() . '/static/scss/'));
$scss->setFormatter('ScssPhp\ScssPhp\Formatter\Compressed');
$scss->setSourceMap(Compiler::SOURCE_MAP_FILE);
$scss->setSourceMapOptions([
  'sourceMapWriteTo'  => get_stylesheet_directory() . '/style.css.map',
  'sourceMapURL'      => get_stylesheet_directory_uri() . '/style.css.map',
  'sourceMapFilename' => 'style.css',
  'sourceMapBasepath' => get_stylesheet_directory_uri()
]);

/**
 * This ensures that Timber is loaded and available as a PHP class.
 * If not, it gives an error message to help direct developers on where to activate
 */
if(!class_exists('Timber')) {
	add_action(
		'admin_notices',
		function() {
			echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url(admin_url('plugins.php#timber')) . '">' . esc_url(admin_url('plugins.php')) . '</a></p></div>';
		}
	);

	add_filter(
		'template_include',
		function($template) {
			return get_stylesheet_directory() . '/static/no-timber.html';
		}
	);
	return;
}

/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array('templates', 'views');

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;

/**
 * Add Advanced Custom Fields Pro paths to enable composer installation
 */
define('ACF_PATH', get_stylesheet_directory() . '/vendor/advanced-custom-fields/advanced-custom-fields-pro/');
define('ACF_URL', get_stylesheet_directory_uri() . '/vendor/advanced-custom-fields/advanced-custom-fields-pro/');

include_once(ACF_PATH . 'acf.php');

/**
 * Enable Classic Editor
 */
add_filter('use_block_editor_for_post','__return_false');

/**
 * We're going to configure our theme inside of a subclass of Timber\Site
 * You can move this to its own file and include here via php's include("MySite.php")
 */
class <%= functionsafe %>Site extends Timber\Site {
	/** Add timber support. */
	public function __construct() {
		add_filter('acf/settings/url', array($this, 'acf_settings_url'));
		// add_filter('acf/settings/show_admin', array($this, 'acf_settings_show_admin'));

		add_action('after_setup_theme', array($this, 'theme_supports'));
		add_filter('timber/context', array($this, 'add_to_context'));
		add_filter('timber/twig', array($this, 'add_to_twig'));
		// add_action('init', array($this, 'register_post_types'));
		// add_action('init', array($this, 'register_taxonomies'));

		add_action('wp_enqueue_scripts', array($this, 'load_stylesheet'));

		add_filter('get_image_tag_class',array($this, 'use_only_imgfluid_class'));

    add_filter('post_thumbnail_html', array($this, 'remove_width_attribute'), 10);
    add_filter('image_send_to_editor', array($this, 'remove_width_attribute'), 10);
    add_filter('the_content', array($this, 'filter_ptags_on_images'));
		add_filter('acf_the_content', array($this, 'filter_ptags_on_images'), 30);
		
		add_action('init', array($this, 'custom_image_sizes'), 10);
		add_action('init', array($this, 'sort_image_sizes'), 15);
		add_filter('image_size_names_choose', array($this, 'image_size_selector_options'));

    add_action('init', array($this, 'disable_emojis'));
    add_action('wp_enqueue_scripts', array($this, 'replace_jquery_with_site_js'));

    add_action('wp_enqueue_scripts', array($this, 'add_typekit'));
    add_action('wp_enqueue_scripts', array($this, 'add_googlefonts'));
    
		add_action('wp_enqueue_scripts', array($this, 'add_googleanalytics'));
		
		add_action('wp_enqueue_scripts', array($this, 'remove_wp_block_library_css'));
		
		/* If having issues with password protected links, try code below: */
    // add_filter('allowed_redirect_hosts', array($this, 'amend_redirect_hosts'), 10, 2);

		add_action('admin_menu', array($this, 'add_options_pages'));
    
    add_action('acf/save_post', array($this, 'update_branding'), 20);
		
		parent::__construct();
  }

  public function update_branding() {
    $screen = get_current_screen();

    if(strpos($screen->id, "acf-options-branding") == true) {
      if(get_field('colors', 'options')) {
        while(have_rows('colors', 'options')) {
          the_row();

          $colors_file = fopen(get_stylesheet_directory() . '/static/scss/variables/_colors.scss', 'w');

          fprintf($colors_file, '$primary:       %s;
$secondary:     %s;
$success:       %s;
$info:          %s;
$warning:       %s;
$danger:        %s;
$light:         %s;
$dark:          %s;',
            get_sub_field('primary'),
            get_sub_field('secondary'),
            get_sub_field('success'),
            get_sub_field('info'),
            get_sub_field('warning'),
            get_sub_field('danger'),
            get_sub_field('light'),
            get_sub_field('dark')
          );
        }
      }

      if(get_field('body', 'options')) {
        while(have_rows('body', 'options')) {
          the_row();

          $body_file = fopen(get_stylesheet_directory() . '/static/scss/variables/_body.scss', 'w');

          fprintf($body_file, '$body-bg: %s;
$body-color: %s;',
            get_sub_field('background'),
            get_sub_field('color')
          );
        }
      }

      if(get_field('spacer', 'options')) {
        $spacer_file = fopen(get_stylesheet_directory() . '/static/scss/variables/_spacer.scss', 'w');

        fprintf($spacer_file, '$spacer: %s;',
          get_field('spacer', 'options')
        );
      }

      if(get_field('fonts', 'options')) {
        while(have_rows('fonts', 'options')) {
          the_row();

          $fonts_file = fopen(get_stylesheet_directory() . '/static/scss/variables/_fonts.scss', 'w');

          $body_font = '$font-family-sans-serif'; // default
          $headings_font = 'null'; //default

          if(get_sub_field('font_families')) {
            while(have_rows('font_families')) {
              the_row();

              $body_font = get_sub_field('body');
              $headings_font = get_sub_field('headings');
            }
          }

          fprintf($fonts_file, '$font-family-base:            %s;
$font-size-base:              %s;
$headings-font-family:        %s;',
            $body_font,
            get_sub_field('base_font_size'),
            $headings_font
          );
        }
      }

      global $scss;

      $scssIn = file_get_contents(__DIR__ . '/static/scss/style.scss');
      $cssOut = $scss->compile($scssIn);
      file_put_contents(__DIR__ . '/style.css', $cssOut);
    }
  }

  public function remove_wp_block_library_css() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
  }
  
  public function add_options_pages() {
    acf_add_options_page(array(
      'page_title' 	=> 'Logo & Copyright Owner',
      'menu_title'	=> 'Theme Settings',
      'menu_slug' 	=> 'theme-settings',
      'capability'	=> 'edit_posts',
      'redirect'		=> false
    ));
    
    acf_add_options_sub_page(array(
      'page_title' 	=> 'Branding',
      'menu_title'	=> 'Branding',
      'parent_slug'	=> 'theme-settings',
    ));
    
    acf_add_options_sub_page(array(
      'page_title' 	=> 'Contact',
      'menu_title'	=> 'Contact',
      'parent_slug'	=> 'theme-settings',
    ));
  }

	public function acf_settings_url($url) {
    return ACF_URL;
	}

	public function acf_settings_show_admin($show_admin) {
    return false;
	}

	public function amend_redirect_hosts($allowed_hosts, $this_host) {
    $allowed_hosts[] = $this_host;

    return $allowed_hosts;
	}

	public function filter_ptags_on_images($content) {
    return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
  }

  public function remove_width_attribute($html) {
    $html = preg_replace('/(width|height)="\d*"\s/', "", $html);
    return $html;
  }
  
  public function use_only_imgfluid_class($class) {
    $class = 'img-fluid';
    return $class;
	}
	
	/** This is where you can register custom post types. */
	public function register_post_types() {
		// CUSTOM POSTS
    // $labels = array(
    //   'name'                  =>_x('Custom Posts', 'post type general name', 'artlytical-media'),
    //   'singular_name'         =>_x('Custom Post', 'post type singular name', 'artlytical-media'),
    //   'menu_name'             =>_x('Custom Posts', 'admin menu', 'artlytical-media'),
    //   'name_admin_bar'        =>_x('Custom Post', 'add new on admin bar', 'artlytical-media'),
    //   'add_new'               =>_x('Add Custom Post', 'exhibit', 'artlytical-media'),
    //   'add_new_item'          =>__('Add New Custom Post', 'artlytical-media'),
    //   'new_item'              =>__('New Custom Post', 'artlytical-media'),
    //   'edit_item'             =>__('Edit Custom Post', 'artlytical-media'),
    //   'view_item'             =>__('View Custom Posts', 'artlytical-media'),
    //   'all_items'             =>__('All Custom Posts', 'artlytical-media'),
    //   'search_items'          =>__('Search Custom Posts', 'artlytical-media'),
    //   'parent_item_colon'     =>__('Parent Custom Post:', 'artlytical-media'),
    //   'not_found'             =>__('No custom posts found.', 'artlytical-media'),
    //   'not_found_in_trash'    =>__('No custom posts found in Trash.', 'artlytical-media')
    // );

    // $args = array(
    //   'labels'             => $labels,
    //   'description'        => __('Description.', 'artlytical-media'),
    //   'public'             => true,
    //   'publicly_queryable' => true,
    //   'show_ui'            => true,
    //   'show_in_menu'       => true,
    //   'query_var'          => true,
    //   'rewrite'            => array('slug' => 'custom-post'),
    //   'capability_type'    => 'post',
    //   'has_archive'        => false,
    //   'hierarchical'       => false,
    //   'menu_position'      => null,
    //   'supports'           => array('title', 'editor', 'thumbnail', 'revisions', 'page-attributes'),
    //   'menu_icon'					 =>'dashicons-format-image'
    // );

    // register_post_type('custom_post', $args);
	}
	/** This is where you can register custom taxonomies. */
	public function register_taxonomies() {
		// CUSTOM POST TYPES
    // $labels = array(
    // 	'name'                       => _x('Custom Post Types', 'taxonomy general name', 'artlytical-media'),
    // 	'singular_name'              => _x('Custom Post Type', 'taxonomy singular name', 'artlytical-media'),
    // 	'search_items'               => __('Search Custom Post Types', 'artlytical-media'),
    // 	'all_items'                  => __('All Custom Post Types', 'artlytical-media'),
    // 	'parent_item'                => null,
    // 	'parent_item_colon'          => null,
    // 	'edit_item'                  => __('Edit Custom Post Type', 'artlytical-media'),
    // 	'update_item'                => __('Update Custom Post Type', 'artlytical-media'),
    // 	'add_new_item'               => __('Add New Custom Post Type', 'artlytical-media'),
    // 	'new_item_name'              => __('New Custom Post Type Name', 'artlytical-media'),
    // 	'separate_items_with_commas' => __('Separate custom post types with commas', 'artlytical-media'),
    // 	'add_or_remove_items'        => __('Add or remove custom post types', 'artlytical-media'),
    // 	'not_found'                  => __('No custom post types found.', 'artlytical-media'),
    // 	'menu_name'                  => __('Custom Post Types', 'artlytical-media'),
    // 	'choose_from_most_used'			 => __('Choose from most used custom post types', 'artlytical-media')
    // );

    // $args = array(
    // 	'hierarchical'          => false,
    // 	'labels'                => $labels,
    // 	'show_ui'               => true,
    // 	'show_admin_column'     => true,
    // 	'update_count_callback' => '_update_post_term_count',
    // 	'query_var'             => true,
    // 	'rewrite'               => array('slug' => 'custom-post-type'),
    // );

    // register_taxonomy('custom_post_type', 'custom_post', $args);
	}

	/** This is where you add some context
	 *
	 * @param string $context context['this'] Being the Twig's {{ this }}.
	 */
	public function add_to_context($context) {
		$context['options'] = get_fields('options');
		
		if(isset($context['options']['navigation_menu'])) {
      $context['menu'] = new Timber\Menu($context['options']['navigation_menu']);
    } else {
      $context['menu'] = new Timber\Menu();
		}
		
    $context['site'] = $this;
    return $context;
	}

	public function theme_supports() {
		// Add default posts and comments RSS feed links to head.
		add_theme_support('automatic-feed-links');

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support('title-tag');

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support('post-thumbnails');

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);

		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
		add_theme_support(
			'post-formats',
			array(
				'aside',
				'image',
				'video',
				'quote',
				'link',
				'gallery',
				'audio',
			)
		);

		add_theme_support('menus');
	}

	/** This is where you can add your own functions to twig.
	 *
	 * @param string $twig get extension.
	 */
	public function add_to_twig($twig) {
		$twig->getExtension('Twig_Extension_Core')->setTimezone('CEST');
    
    return $twig;
	}

	/**
  * Disable the emoji's
  */
  public function disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('tiny_mce_plugins', array($this, 'disable_emojis_tinymce'));
    add_filter('wp_resource_hints', array($this, 'disable_emojis_remove_dns_prefetch'), 10, 2);
  }

  /**
  * Filter function used to remove the tinymce emoji plugin.
  * 
  * @param array $plugins 
  * @return array Difference betwen the two arrays
  */
  public function disable_emojis_tinymce($plugins) {
    if(is_array($plugins)) {
      return array_diff($plugins, array('wpemoji'));
    } else {
      return array();
    }
  }

  /**
  * Remove emoji CDN hostname from DNS prefetching hints.
  *
  * @param array $urls URLs to print for resource hints.
  * @param string $relation_type The relation type the URLs are printed for.
  * @return array Difference betwen the two arrays.
  */
  public function disable_emojis_remove_dns_prefetch($urls, $relation_type) {
    if('dns-prefetch' == $relation_type) {
      /** This filter is documented in wp-includes/formatting.php */
      $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');

      $urls = array_diff($urls, array($emoji_svg_url));
    }

    return $urls;
  }

  // Remove default jQuery
  public function replace_jquery_with_site_js() {
    if(!is_admin()) {
      wp_deregister_script('jquery');
      wp_register_script('jquery', get_stylesheet_directory_uri() . '/static/site.min.js', false);
      wp_enqueue_script('jquery');
    }
  }

  public function add_typekit() {
    $adobe_typekit = get_field('adobe_typekit', 'options');
    if($adobe_typekit) {
      wp_enqueue_style('adobetypekit', $adobe_typekit);
    }
  }

  public function add_googlefonts() {
    $google_fonts = get_field('google_fonts', 'options');
    if($google_fonts) {
      wp_enqueue_style('googlefonts', $google_fonts);
    }
  }

  public function add_googleanalytics() {
    if(get_field('google_analytics_code', 'options')) {
      wp_register_script('ga', 'https://www.googletagmanager.com/gtag/js?id=' . get_field('google_analytics_code', 'options'));
      wp_enqueue_script('ga');
      ?>
          <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '<?php the_field('google_analytics_code', 'options') ?>');
          </script>
      <?php
    }
	}

  public function load_stylesheet() {
		wp_enqueue_style('style', get_stylesheet_uri(), false, filemtime(get_stylesheet_directory() . '/style.css'));
	}
}

new <%= functionsafe %>Site();