<?php
/**
 * Allows retrive sharing stats for specefied url/page.
 *
 * @author Matthew Barby
 * @version  1.0 2015-10-06
 */

require_once dirname(__FILE__)."/wpUrlList.php";

if ( ! function_exists( 'wp_handle_upload' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class PagesSocialStatsPlugin
{
	/**
	 * Plugin settings.
	 * @var assoc
	 */
	public $settings = array(
		'auto-refresh-time' => 60, // period of the auto refresh
		'url-refresh-time' => 30,  // timeout for the stats cache is valid
		'refresh-limit' => 5,      // limit for items that can be rechecked during one recheck request

		// page titles
		'plugin-name' => 'Social Footprint',
		'menu-main-title' => 'Social Footprint',
		'page-manage-items-title' => 'Manage Pages',
		'page-help-title' => 'About',

		// state of services (1 - active, 0 - inective), can be managed on the settings page
		'services' => array(
			'fb' => 1,
			'tw' => 1,
			'g' => 1,
			'ln' => 0,
			'p' => 0,
			'su' => 0,
			're' => 0,
		)
	);

	/**
	 * Plugin id
	 * @see  get_id
	 * @var string
	 */
	public $id;

	/**
	 * Name of the shortcode that used for stats presentation on the any page.
	 * @var string
	 */
	public $shortcode_name = 'authorship_social_counter_stats';

	/**
	 * Access level for the plugin pages.
	 * @see hook_admin_menu
	 * @var integer
	 */
	public $access_level = 10;

	/**
	 * Name of the action used to refresh all items stats.
	 * @var string
	 */
	private $refresh_stats_action = 'asc_recheck_all';

	/**
	 * singleton pattern
	 * @var PagesSocialStatsPlugin
	 */
	private static $instance;

	/**
	 * Flag that indicates that settings have been loaded from DB.
	 * @see  db_load_settings
	 * @see  db_save_settings
	 * @var boolean
	 */
	private $settings_loaded = false;

	/**
	 * Cache of the services active status.
	 * @see  get_services
	 * @var assoc
	 */
	private $cache_services;

	public $db_settings_loaded;

	/**
	 * Cache for the items.
	 * @var array
	 */
	protected $items = null;


	public $wpUrlList;

	public function init()
	{


		add_shortcode($this->shortcode_name, array($this, 'render_shortcode'));

		add_action( 'wp_ajax_' . $this->refresh_stats_action, array($this, 'action_refresh_all_stats'));
		add_action( 'wp_ajax_nopriv_' . $this->refresh_stats_action, array($this, 'action_refresh_all_stats'));


		// processing by mahabub
		add_action( 'admin_enqueue_scripts', array($this, 'add_css_js_for_custom_work'));
		add_action( 'wp_ajax_import_post_into_asc', array($this, '_import_post_into_asc') );
		//add_action('wp_ajax__fetch_ajax_response', array($this, '_fetch_ajax_response'));
		add_action('wp_ajax__fetch_ajax_response', array($this, '_fetch_ajax_response'));
        add_action('wp_ajax__delete_an_item', array($this, '_delete_an_item'));
        add_action('wp_ajax__refresh_an_item', array($this, '_refresh_an_item'));
        add_action('wp_ajax__delete_selected_urls', array($this, '_delete_selected_urls'));
        add_action('wp_ajax__reload_table_data', array($this, '_reload_table_data'));
        add_action('wp_ajax__refresh_selected_urls', array($this, '_refresh_selected_urls'));
	}

    public  function _delete_selected_urls(){
        $this->items = $this->get_items();
        $selected_urls = (array) $_REQUEST['delete_items'];
        if(!empty($selected_urls)){
            foreach($selected_urls as $select_url){
                if(isset($this->items[$select_url])){
                    unset($this->items[$select_url]);
                }
            }
        }
        $this->save_items();
        wp_die('delete items');
    }


    public  function _refresh_selected_urls(){
        $this->items = $this->get_items();
        $selected_urls = (array) $_REQUEST['delete_items'];
        $processInEachRequest = $_REQUEST['processInEachRequest'];
        $currentStep = $_REQUEST['currentStep'] - 1;
        $chunked_array = array_chunk($selected_urls,$processInEachRequest);

        if(!empty($chunked_array[$currentStep])){
            foreach($chunked_array[$currentStep] as $select_url){
                if(isset($this->items[$select_url])){
                    $stats = $this->get_stats_for_url($select_url);
                    $stats['ct'] = time();
                    $this->items[$select_url]  = $stats;
                }
            }
        }
        $this->save_items();
        wp_die('Item refreshed');

    }


    function _reload_table_data(){
        $this->_fetch_ajax_response();
    }

	public function _fetch_ajax_response()
	{
		check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');
		if (empty($this->wpUrlList)) {
			$this->wpUrlList = new wpUrlList($this, $this->get_items());
		}
		$this->wpUrlList->ajax_response();
	}


    public function _delete_an_item(){
        check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');
        $this->delete_item($_REQUEST['delete_url']);
        if (empty($this->wpUrlList)) {
            $this->wpUrlList = new wpUrlList($this, $this->get_items());
        }
        $this->_fetch_ajax_response();
    }


    public function _get_total_status(){
        $total_stats = $this->get_total_stats();
        $total_result = array_sum($total_stats);
        $output = '';
        if( $total_result > 0){
            $output .= '<table class="widefat fixed" cellspacing="0"><tbody>';
            $count = 0;
            foreach ($total_stats as $key => $value) {
                $class = ($count % 2 == 0) ? 'alternate' : '';
                $count++;
                $output .= '<tr class="'.$class.'">';
                $output .= '<td class="column-columnname">'.$this->get_service_label_by_code($key).'</td>';
                $output .= '<td class="column-columnname">'.$value.'</td>';
                $output .= '</tr>';

            }
            $class = ($class == 'alternate') ? '' : 'alternate';
            $output .= '<tr class="'.$class.'">';
            $output .= '<td class="column-columnname">Total</td>';
            $output .= '<td class="column-columnname">'.$total_result.'</td>';
            $output .= '</tr>';
            $output .= '</tbody></table>';
        }
        return $output;
    }


    public function _refresh_an_item(){
        check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');
        $this->refresh_url_stats($_REQUEST['refresh_url']);
        if (empty($this->wpUrlList)) {
            $this->wpUrlList = new wpUrlList($this, $this->get_items());
        }
        $this->_fetch_ajax_response();
    }


	function _import_post_into_asc(){
		if(!empty($_POST['processPostPerStep'])){
			$processPostPerStep = (int) $_POST['processPostPerStep'];
		}
		$chunk_size = ($processPostPerStep > 0 ) ? $processPostPerStep : 10;

		$posts = get_posts( array(
			'posts_per_page'	=>	-1,
			'post_type'			=>	'post',
			'post_status' 		=> 	'publish'
		) );

		$array_by_page = array_chunk($posts, $chunk_size, true);
		$current_step = (int) $_POST['currentStep'];
		if($current_step >= 0 && isset($array_by_page[$current_step]) && !empty($array_by_page[$current_step])){
			foreach($array_by_page[$current_step] as $post){
				$url['url'] = get_the_permalink($post->ID);
				$this->create_item($url);
			}
		}
		wp_die();
	}

	/**
	 * custom ajax work
	 */
	public function add_css_js_for_custom_work(){
		//adding jqmeter

		global $current_screen;
		if ( 'toplevel_page_authorship-social-counter' != $current_screen->id ){
			return ;
		}

		wp_enqueue_script(
			'custom_progress_bar',
			plugins_url('/assets/progressbar.js', dirname(__FILE__)),
			array( 'jquery' )
		);

		wp_enqueue_script(
			'share_table_js',
			plugins_url('/assets/sharetable.js', dirname(__FILE__)),
			array( 'jquery' )
		);

		// custom Js
		wp_enqueue_script(
			'custom_ajax_work',
			plugins_url('/assets/custom_ajax_work.js', dirname(__FILE__)),
			array( 'jquery', 'share_table_js' )
		);



		$count_posts = (array) wp_count_posts();
		wp_localize_script( 'custom_ajax_work', 'ascTotalPost', $count_posts );
	}

	public function get_id()
	{
		return $this->id ? $this->id : 'authorship-social-counter';
	}

	/*** hooks [start] ***/
	public function hook_admin_menu()
	{
		$slug = $this->get_id();

		add_menu_page(
			$this->get_setting('page-manage-items-title', 'Manage URLs'),
			$this->get_setting('menu-main-title','Social Footprint'),
			$this->access_level,
			$slug,
			array($this, 'action_manage_urls'),
			plugins_url( 'social-footprint/includes/rsz_1rsz_1mattlogo.png' )
		);

		$settings_title = $this->get_setting('page-settings-title', 'Settings');
		add_submenu_page($slug,
			$settings_title,
			$settings_title,
			$this->access_level,
			$slug . '-settings',
			array($this, 'action_settings')
		);

		$help_title = $this->get_setting('page-help-title', 'Help');
		add_submenu_page($slug,
			$help_title,
			$help_title,
			$this->access_level,
			$slug . '-help',
			array($this, 'action_help')
		);
	}

	public function hook_activate()
	{
		if (!extension_loaded('curl')) {
			exit('Please enable CURL extension to be able to use ' . $this->get_setting('plugin-name') . ' plugin.');
		}

		add_option($this->get_option_name('settings'), null, '', 'yes');
		add_option($this->get_option_name('items'), array(), '', 'no');
		add_option($this->get_option_name('totals'), null, '', 'yes');
	}

	public function hook_deactivate()
	{
		delete_option($this->get_option_name('settings'));
		delete_option($this->get_option_name('items'));
		delete_option($this->get_option_name('totals'));
	}
	/*** hooks [end] ***/

	/*** actions [start] ***/
	public function action_manage_urls()
	{

		$cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : '';

		$formErrors = array();
		$formData = array();
		try {
			switch ($cmd) {

				case 'create_url':
					$formData = isset($_POST['f']) ? $_POST['f'] : array();
					try {
						$this->create_item($formData);
						$formData = array();
					} catch (Exception $e) {
						$formErrors[] = $e->getMessage();
					}
					break;

				case 'delete_url':
					$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
					$this->delete_item($url);
					//wp_redirect($this->get_page_url(), 302);
					break;

				case 'refresh_stats':
					$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
					$this->refresh_url_stats($url);
					break;

				case 'upload_csv':
					if($_FILES['csv']['error'] == 0){
						$name = $_FILES['csv']['name'];
						$ext = strtolower(end(explode('.', $_FILES['csv']['name'])));
						$type = $_FILES['csv']['type'];
						$tmpName = $_FILES['csv']['tmp_name'];

						// check the file is a csv
						if($ext === 'csv'){
							if(($handle = fopen($tmpName, 'r')) !== FALSE) {
								// necessary if a large csv file
								set_time_limit(0);

								$row = 0;

								while(($data = fgetcsv($handle, 1000, '\t')) !== FALSE) {
									$url['url'] = $data[0];
									$this->create_item($url);

									// inc the row
									$row++;
								}
								fclose($handle);
							}
						}
					}
					break;
				case 'import_wordpress':
					$posts = get_posts( array(
						'posts_per_page'	=>	-1,
						'post_type'			=>	'post',
						'post_status' 		=> 	'publish'
						) );
					//print_r($posts_array); exit;
					//$posts = get_posts( $posts_array );

					if ( $posts ) {
						foreach ( $posts as $post ) {
							$url[url] = get_the_permalink($post->ID);
							$this->create_item($url);
						}
					}

					break;
			}

			echo $this->render_view('manage_urls', array(
				'items' => $this->get_items(),
				'form_data' => $formData,
				'form_errors' => $formErrors,
			));
		} catch (Exception $e) {
			echo 'Unexpected error: ' . $e->getMessage();
		}
	}

	public function action_settings()
	{
		$cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : '';

		if ('update-services' == $cmd) {
			$formErrors = array();
			$activeServices = isset($_POST['services']) ? $_POST['services'] : array();
			try {
				$this->update_services($activeServices);
				$this->get_total_stats(true); // to recalculate totals
			} catch (Exception $e) {
				$formErrors[] = $e->getMessage();
			}
		}

		echo $this->render_view('settings', array(
			'services' => $this->get_services()
		));
	}

	public function action_help()
	{
		echo $this->render_view('help', array(
			'text' => file_get_contents(dirname( __FILE__ ) . '/../readme.txt')
		));
	}

	public function action_refresh_all_stats()
	{
		ob_start();
		ignore_user_abort(true); // optional
		echo ';';
		$size = ob_get_length();
		header("Connection: close");
		header("Content-Length: $size");
		ob_flush();
		flush();
		if (session_id()) session_write_close();

		$mode = is_admin() && isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

		$url_refresh_time = $this->get_setting('url-refresh-time', 30);
		$refresh_limit = $this->get_setting('refresh-limit', 5);

		if ('all' == $mode) {
			$refresh_limit = 0;
			$url_refresh_time = 0;
		}

		$need_refresh = array();

		$minTime = time() - $url_refresh_time;

		$items = $this->get_items();
		foreach ($items as $url => $stats) {
			if (!isset($stats['ct']) || $stats['ct'] < $minTime) {
				$need_refresh[$url] = $stats['ct'];
			}
		}

		asort($need_refresh);

		$refreshed_counter = 0;
		foreach ($need_refresh as $url => $ctime) {
			try {
				$this->refresh_url_stats($url, false);
				$refreshed_counter++;
			} catch (Exception $e) {
				//TODO send errors to log
			}

			if ($refresh_limit > 0 && $refreshed_counter >= $refresh_limit) {
				break;
			}
		}

		if ($refreshed_counter) {
			$this->save_items();
		}
		//echo "{$refreshed_counter};";
		echo '"ok";';
		exit();
	}
	/*** actions [end] ***/

	/*** urls management [start] ***/
	protected function get_items()
	{
		if (null === $this->items) {
			$this->items = get_option($this->get_option_name('items'), array());
           // print_r($this->items); exit;
		}

		return $this->items;
	}



	protected function create_item(array $data)
	{
		if (empty($data['url'])) {
			throw new Exception('URL is required.');
		}

		$url = $data['url'];
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			throw new Exception('Please check URL format.');
		}

		$items = $this->get_items();
		if (isset($items[$url])) {
			return;
			//throw new Exception('An item already exists. If bulk importing, other URLs will still be added.');
		}
		$this->items[$url] = array();
		$this->save_items( false );
		$this->refresh_url_stats($url);
	}

	protected function delete_item($url, $save = true)
	{
		$items = $this->get_items();
		if (isset($items[$url])) {
			unset($this->items[$url]);

			if ($save) {
				$this->save_items();
			}
		}
	}

	protected function save_items($recalculate_totals = true)
	{
		if ($recalculate_totals) {
			$this->get_total_stats(true);
		}

		update_option($this->get_option_name('items'), $this->items ? $this->items : array());
	}

	public function refresh_url_stats($url, $save = true)
	{
		$items = $this->get_items();
		if (!isset($items[$url])) {
			throw new Exception(strtr('URL "{url}" does not exist.',array(
				'{url}' => $url
			)));
		}

		$stats = $this->get_stats_for_url($url);
		$stats['ct'] = time();

		$this->items[$url] = $stats;
		if ($save) {
			$this->save_items();
		}
	}
	/*** urls management [end] ***/

	/**
	 * Calculate sharing stats for specefied url.
	 * @param  string $url
	 * @return assoc
	 */
	protected function get_stats_for_url($url)
	{
		$checker = $this->get_stats_checker();
		$encoded_url = $checker->encode_url($url);

		$result = array();
		$stat_keys = $this->get_active_services();

		foreach($stat_keys as $service_key) {
			$s_result = 0;

            switch($service_key) {
			case 'fb':
				$s_result = $checker->get_fb_stats($encoded_url, true);
				break;
			case 'tw':
				$s_result = $checker->get_tweeter_stats($encoded_url, true);
				break;
			case 'g':
				$s_result = $checker->get_google_stats($url, true);
				break;
			case 'ln':
				$s_result = $checker->get_linkedin_stats($encoded_url, true);
				break;
			case 'p':
				$s_result = $checker->get_piterest_stats($encoded_url, true);
				break;
			case 'su':
				$s_result = $checker->get_stumbleUpon_stats($encoded_url, true);
				break;
			case 're':
				$s_result = $checker->get_reddit_stats($encoded_url, true);
				break;

			}

			$result[$service_key] = $s_result;
            //$result[$service_key] = rand(20,100);
		}

		return $result;
	}

	public function get_total_stats($refresh_cache = false)
	{
		$cached = get_option($this->get_option_name('totals'), null);

		if (!$refresh_cache && $cached) {
			return $cached;
		}

		$stat_keys = $this->get_active_services();
		$result = array();
		foreach ($stat_keys as $service_key) {
			$result[$service_key] = 0;
		}

		$items = $this->get_items();
		foreach ($items as $url => $item_stats) {
			foreach ($stat_keys as $key) {
				if (isset($item_stats[$key])) {
					$result[$key] += $item_stats[$key];
				}
			}
		}

		update_option($this->get_option_name('totals'), $result);

		return $result;
	}

	public function render_shortcode($atts)
	{
		$options = shortcode_atts(array(
			'mode' => '',//single
		), $atts);

		$cache_refresh_flag = $this->get_option_name('auto-refreshed');

		$is_fresh = get_transient($cache_refresh_flag);

		if (!$is_fresh) {
			set_transient($cache_refresh_flag, true, $this->get_setting('auto-refresh-time'));
		}

		$output = '';
		if (!$is_fresh) {
			$output .= '<script type="text/javascript" async="async" src="' . $this->get_refresh_stats_url() . '"></script>';
		}
		$output .= '<div style="border:1px solid #EEE;padding:5px;text-align:center;">';
		if ('single' == $options['mode']) {
			$output .= 'Pages sharing total: ' . array_sum($this->get_total_stats()) . '</div>';
		} else {
			$parts = array();
			$stats = $this->get_total_stats();
			foreach ($stats as $key => $value) {
				//if ($value > 0) {
					$parts[] = $this->get_service_label_by_code($key) .' <strong>' . $value . '</strong>';
				//}
			}

			$output .= 'Pages sharing stats: ' . join(' | ', $parts);
		}
		$output .= '</div>';

		return $this->render_view('shortcode', array(
			'is_single' => 'single' == $options['mode'],
			'send_refresh_request' => !$is_fresh,
			'stats' => $this->get_total_stats()
		));
	}

	public function get_page_url($page_name = 'manage')
	{
		$mainSlug = $this->get_id();
		$urls = array(
			'manage' => $mainSlug,
			'settings' => $mainSlug . '-settings',
		);

		$page_id = isset($urls[$page_name]) ? $urls[$page_name] : $page_name;

		return get_admin_url('', 'admin.php?page=' . $page_id);
	}

	public function get_refresh_stats_url($allItems = false)
	{
		return admin_url( 'admin-ajax.php' ) . '?action=' . $this->refresh_stats_action . ($allItems ? '&mode=all' : '');
	}

	/*** views rendering [start] ***/
	protected function render_view($name, array $data)
	{
		extract($data);

		ob_start();

		include $this->get_view_file_path($name);

		return ob_get_clean();
	}

	protected function get_view_file_path($view_name)
	{
		$file_name = $view_name;

		if (!preg_match('/\.php$/', $file_name)) {
			$file_name .= '.php';
		}

		$file_path = dirname(__FILE__) . '/../views/' . $file_name;

		if (!file_exists($file_path)) {
			throw new Exception(strtr('File "{filePath}" does not exist.', array(
				'{filePath}' => $file_path
			)));
		}

		return $file_path;
	}
	/*** views rendering [end] ***/

	/*** services [start] ***/
	public function get_service_label_by_code($field = null)
	{
		$list = array(
			'fb' => 'Facebook',
			'tw' => 'Twitter',
			'g' => 'Google+',
			'ln' => 'LinkedIn',
			'p' => 'Pinterest',
			'su' => 'StumbleUpon',
			're' => 'Reddit',
		);

		if (null == $field) {
			return $list;
		}

		return isset($list[$field]) ? $list[$field] : $field;
	}

	public function get_service_codes()
	{
		return array_keys($this->get_service_label_by_code(null));
	}

	public function get_active_services($reload = false)
	{
		$allServices = $this->get_services($reload);
		$result = array();
		foreach ($allServices as $serviceKey => $isActive) {
			if ($isActive) {
				$result[] = $serviceKey;
			}
		}
		return $result;
	}
	/**
	 * Returun list of all services with flag of their activity status.
	 * @param  boolean $reload if cache should be reloaded to read all data from settings
	 * @return assoc
	 */
	public function get_services($reload = false)
	{
		if (null == $this->cache_services || $reload) {
			$fullList = $this->get_service_codes();
			$defValues = array();
			foreach ($fullList as $serviceKey) {
				$defValues[$serviceKey] = 0;
			}
			$this->cache_services = array_merge($defValues, $this->get_setting('services', array()));
		}

		return $this->cache_services;
	}

	/**
	 * Updates activity status for services.
	 * @param  assoc $newValues  key - code of the service, value (1,0) - active or not
	 * @return void
	 */
	public function update_services($newValues)
	{
		$saveValues = array();
		$curValues = $this->get_services();

		foreach ($curValues as $serviceKey => $curValue) {
			$saveValues[$serviceKey] = !empty($newValues[$serviceKey]) ? 1 : 0;
		}
		$this->set_setting('services', $saveValues);
		$this->get_services(true); // to reset services cache
	}
	/*** services [end] ***/

	/*** settings [start] ***/
	public function get_setting($name = null, $default = null)
	{
		$this->db_load_settings();
		if (null === $name) {
			return $this->settings;
		}

		return isset($this->settings[$name]) ? $this->settings[$name] : $default;
	}

	protected function set_setting($name, $value, $save = true)
	{
		$this->settings[$name] = $value;
		if ($save) {
			$this->db_save_settings();
		}
	}
	/*** settings [end] ***/

	/*** methods related on the managed settings [start] ***/
	protected function db_get_saved_settings_list()
	{
		return array(
			'services' => array()
		);
	}

	protected function db_save_settings()
	{
		$saveMap = array();
		$savedKeys = $this->db_get_saved_settings_list();
		foreach ($savedKeys as $settingName => $defaultValue) {
			$saveMap[$settingName] = $this->get_setting($settingName, $defaultValue);
		}
		update_option($this->get_option_name('settings'), $saveMap);
		// use if would like to force reload loading during 1 page processing
		//$this->db_settings_loaded = false;
	}

	protected function db_load_settings()
	{
		if($this->db_settings_loaded) {
			return;
		}

		$loadedData = get_option($this->get_option_name('settings'));
		if(empty($loadedData)){
			return;
		}

		$savedKeys = $this->db_get_saved_settings_list();
		foreach ($savedKeys as $settingKey => $defValue) {
			if (array_key_exists($settingKey, $loadedData)) {
				$this->settings[$settingKey] = $loadedData[$settingKey];
			}
		}

		$this->db_settings_loaded = true;
	}

	protected function get_option_name($name)
	{
		return $this->get_id() . '-' . $name;
	}
	/*** methods related on the managed settings [start] ***/

	/**
	 * @return UrlSocialStatsChecker
	 */
	protected function get_stats_checker()
	{
		static $stats_instance;

		if (null == $stats_instance) {
			if (!class_exists('UrlSocialStatsChecker')) {
				require dirname(__FILE__) . '/UrlSocialStatsChecker.php';
			}
			$stats_instance = new UrlSocialStatsChecker();
		}

		return $stats_instance;
	}

	// singleton pattern
	private function __construct()
	{

	}

	public static function get_instance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

function cust_sort($a,$b) {
	return $a[$_POST['set_order']] < $b[$_POST['set_order']];
}

function calculate_total( $array ) {
	foreach($array as $key => $value) {
		$t = 0;
		foreach($array[$key] as $k => $v) {
			if($k != 'ct')
			{
				$t = $t + (int)$v;
			}
		}

		$array[$key]['totals'] = $t;
	}

	return $array;
}
