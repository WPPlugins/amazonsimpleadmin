<?php
define('ASA_INCLUDE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR);
include_once ASA_INCLUDE_DIR . 'asa_helper_functions.php';
include_once ASA_INCLUDE_DIR . 'ifw-php-lib-functions.php';

class AmazonSimpleAdmin {
    
    const DB_COLL         = 'asa_collection';
    const DB_COLL_ITEM    = 'asa_collection_item';

    const VERSION = '1.0.3';
    
    /**
     * this plugins home directory
     */
    protected $plugin_dir = '/wp-content/plugins/amazonsimpleadmin';
    
    protected $plugin_url = 'options-general.php?page=amazonsimpleadmin/amazonsimpleadmin.php';
    
    /**
     * supported amazon country IDs
     */
    protected $_amazon_valid_country_codes = array(
        'BR', 'CA', 'DE', 'FR', 'IN', 'JP', 'MX', 'UK', 'US', 'IT', 'ES', 'CN'
    );
    
    /**
     * the international amazon product page urls
     */
    protected $amazon_url = array(
        'BR'    => 'http://www.amazon.com.br/exec/obidos/ASIN/%s/%s',
        'CA'    => 'http://www.amazon.ca/exec/obidos/ASIN/%s/%s',
        'DE'    => 'http://www.amazon.de/exec/obidos/ASIN/%s/%s',
        'FR'    => 'http://www.amazon.fr/exec/obidos/ASIN/%s/%s',
        'JP'    => 'http://www.amazon.jp/exec/obidos/ASIN/%s/%s',
        'MX'    => 'http://www.amazon.com.mx/exec/obidos/ASIN/%s/%s',
        'UK'    => 'http://www.amazon.co.uk/exec/obidos/ASIN/%s/%s',
        'US'    => 'http://www.amazon.com/exec/obidos/ASIN/%s/%s',
        'IN'    => 'http://www.amazon.in/exec/obidos/ASIN/%s/%s',
        'IT'    => 'http://www.amazon.it/exec/obidos/ASIN/%s/%s',
        'ES'    => 'http://www.amazon.es/exec/obidos/ASIN/%s/%s',
        'CN'    => 'http://www.amazon.cn/exec/obidos/ASIN/%s/%s',
    );

    /**
     * @var string
     */
    protected $amazon_shop_url;

    /**
     * available template placeholders
     */
    protected $tpl_placeholder = array(
        'ASIN',
        'SmallImageUrl',
        'SmallImageWidth',
        'SmallImageHeight',
        'MediumImageUrl',
        'MediumImageWidth',
        'MediumImageHeight',
        'LargeImageUrl',
        'LargeImageWidth',
        'LargeImageHeight',
        'Label',
        'Manufacturer',
        'Publisher',
        'Studio',
        'Title',
        'AmazonUrl',
        'TotalOffers',
        'LowestOfferPrice',
        'LowestOfferCurrency',
        'LowestOfferFormattedPrice',
        'LowestNewPrice',
        'LowestNewOfferFormattedPrice',
        'LowestUsedPrice',
        'LowestUsedOfferFormattedPrice',
        'AmazonPrice',
        'AmazonPriceFormatted',
        'ListPriceFormatted',
        'AmazonCurrency',
        'AmazonAvailability',
        'AmazonLogoSmallUrl',
        'AmazonLogoLargeUrl',
        'DetailPageURL',
        'Platform',
        'ISBN',
        'EAN',
        'NumberOfPages',
        'ReleaseDate',
        'Binding',
        'Author',
        'Creator',
        'Edition',
        'AverageRating',
        'TotalReviews',
        'RatingStars',
        'RatingStarsSrc',
        'Director',
        'Actors',
        'RunningTime',
        'Format',
        'CustomRating',
        'ProductDescription',
        'AmazonDescription',
        'Artist',
        'Comment',
        'PercentageSaved',
        'Prime',
        'PrimePic',
        'ProductReviewsURL',
        'TrackingId',
        'AmazonShopURL',
        'SalePriceAmount',
        'SalePriceCurrencyCode',
        'SalePriceFormatted',
        'Class',
        'OffersMainPriceAmount',
        'OffersMainPriceCurrencyCode',
        'OffersMainPriceFormattedPrice'
    );

    /**
     * template placeholder prefix
     */
    protected $tpl_prefix = '{$';
    
    /**
     * template placeholder postfix
     */
    protected $tpl_postfix = '}';
    
    /**
     * template dir
     */
    protected $tpl_dir = 'tpl';
    
    /**
     * AmazonSimpleAdmin bb tag regex
     */
    protected $bb_regex = '#\[asa(.[^\]]*|)\]([\w-]+)\[/asa\]#Usi';
    
    /**
     * AmazonSimpleAdmin bb tag regex
     */
    protected $bb_regex_collection = '#\[asa_collection(.[^\]]*|)\]([\w-\s]+)\[/asa_collection\]#Usi';
    
    /**
     * param separator regex
     */
    protected $_regex_param_separator = '/(,)(?=(?:[^"]|"[^"]*")*$)/m';    
    
    /**
     * my Amazon Access Key ID
     */
    protected $amazon_api_key_internal = '';
    
    /**
     * user's Amazon Access Key ID
     */
    protected $_amazon_api_key;
    
    /**
     * user's Amazon Access Key ID
     * @var string
     */
    protected $_amazon_api_secret_key = '';    
    
    /**
     * user's Amazon Tracking ID
     */
    protected $amazon_tracking_id;
    
    /**
     * selected country code
     */
    protected $_amazon_country_code = 'US';

    /**
     * @var
     */
    protected $_amazon_api_connection_type = 'http';

    /**
     * product preview status
     * @var bool
     */
    protected $_product_preview = false;
    
    /**
     * product preview status
     * @var bool
     */
    protected $_parse_comments = false;

    /**
     * use AJAX
     * @var bool
     */
    protected $_async_load = false;

    /**
     * use only amazon prices for placeholder $AmazonPrice
     * @var bool
     */
    protected $_asa_use_amazon_price_only = false;
    
    /**
     * internal param delimiter
     * @var string
     */
    protected $_internal_param_delimit = '[#asa_param_delim#]';
    
    /**
     * 
     * @var string
     */
    protected $task;
    
    /**
     * wpdb object
     */
    protected $db;
    
    /**
     * collection object
     */
    protected $collection;
    
    protected $error = array();
    protected $success = array();
    
    /**
     * the amazon webservice object
     */
    protected $amazon;
    
    /**
     * the cache object
     */
    protected $cache;

    /**
     * @var Asa_Debugger
     */
    protected $_debugger;

    /**
     * @var debugger error message
     */
    protected $_debugger_error;

    /**
     * @var AsaEmail
     */
    protected $_email;



    /**
     * constructor
     */
    public function __construct ($wpdb) 
    {
        //$libdir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib';
        //set_include_path(get_include_path() . PATH_SEPARATOR . $libdir);

        require_once ASA_LIB_DIR . 'AsaZend/Uri/Http.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/Accessories.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/EditorialReview.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/Image.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/Item.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/ListmaniaList.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/Offer.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/OfferSet.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/Query.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/ResultSet.php';
        require_once ASA_LIB_DIR . 'AsaZend/Service/Amazon/SimilarProduct.php';
        require_once dirname(__FILE__) . '/AsaWidget.php';

        if ($this->isDebug()) {
            $this->_initDebugger();
        }
        
        if (isset($_GET['task'])) {
            $this->task = strip_tags($_GET['task']);
        }
        
        $this->tpl_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->tpl_dir . DIRECTORY_SEPARATOR;
        
        $this->db = $wpdb;
        
        $this->cache = $this->_initCache();

        // init translation
        load_plugin_textdomain('asa1', false, 'amazonsimpleadmin/lang');
                
        // Hook for adding admin menus
        add_action('admin_menu', array($this, 'createAdminMenu'));
        
        // Hook for adding content filter
        add_filter('the_content', array($this, 'parseContent'), 1);
        add_filter('the_excerpt', array($this, 'parseContent'), 1);
        add_filter('widget_text', array($this, 'parseContent'), 1);
        
        // register shortcode handler for [asa] tags
        add_shortcode( 'asa', 'asa_shortcode_handler' );

        if (!get_option('_asa_hide_meta_link')) {
            add_action('wp_meta', array($this, 'addMetaLink'));
        }
        
        $this->_getAmazonUserData();
        $this->_loadOptions();
                
        if ($this->_parse_comments == true) {
            // Hook for adding content filter for user comments
            // Feature request from Sebastian Steinfort
            add_filter('comment_text', array($this, 'parseContent'), 1);
        }
        
        if ($this->_product_preview == true) {
            add_action('wp_footer', array($this, 'addProductPreview'));
        }
        
        add_filter('upgrader_pre_install', array($this, 'onPreInstall'), 10, 2);
        add_filter('upgrader_post_install', array($this, 'onPostInstall'), 10, 2);
        add_action('in_plugin_update_message-amazonsimpleadmin/amazonsimpleadmin.php', array($this, 'handleUpdateMessage'));
        add_filter('plugin_action_links_amazonsimpleadmin/amazonsimpleadmin.php', array($this, 'addPluginActionLinks'));

        $this->amazon = $this->connect();

        if (get_option('_asa_error_email_notification')) {
            require_once dirname(__FILE__) . '/AsaEmail.php';
            $this->_email = AsaEmail::getInstance();
        }

        $this->_beforeOutput($this->task);

        $this->_initCallback();
    }

    /**
     *
     */
    public function addPluginActionLinks($links)
    {
        $links[] = '<a href="' . get_admin_url(null, 'options-general.php?page=amazonsimpleadmin/amazonsimpleadmin.php') . '">' . __('Settings', 'asa1') . '</a>';
        return $links;
    }

    protected function _initCallback()
    {
        add_action('init', array($this, 'onWpInit'));
    }

    public function onWpInit()
    {
        if (!is_admin() && $this->_isAsync()) {
            // be sure to have jQuery if AJAX mode is active
            wp_enqueue_script('jquery');
        }
    }

    /**
     * Called before installation / upgrade
     * 
     */
    public function onPreInstall()
    {
        try {
            $this->backupTemplates();
        } catch (Exception $e) {
            
        }
    }
    
    /**
     * Called after installation / upgrade
     * 
     */
    public function onPostInstall()
    {
        try {
            $this->restoreTemplates();
        } catch (Exception $e) {
            
        }
    }
    
    /**
     * Backups the template files
     * 
     */
    public function backupTemplates()
    {
        $dirIt = new DirectoryIterator($this->tpl_dir);
        
        $custom_tpl = array();
        foreach ($dirIt as $fileinfo) {
            
            if ($fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }
            $custom_tpl[] = $fileinfo->getFilename();
        }
        
        if (count($custom_tpl) > 0) {
            $backup_destination = $this->_getBackupDestination();
            mkdir($backup_destination);
            
            foreach($custom_tpl as $tpl_file) {
                
                $tpl_source_file = $this->tpl_dir . $tpl_file;
                $tpl_destination_file = $backup_destination . $tpl_file;
                
                $cp = copy($tpl_source_file, $tpl_destination_file);
                
                if ($cp == false) {
                    $tpl_data = file_get_contents($tpl_source_file);
                    $handle   = fopen($tpl_destination_file, 'w');
                    fwrite($handle, $tpl_data);
                    fclose($handle);
                }                
            }
        }
    }
    
    /**
     * Restores the template files
     * 
     */
    public function restoreTemplates()
    {
        $backup_destination = $this->_getBackupDestination();
        
        if (!is_dir($backup_destination)) {
            return false;
        }
        
        $dirIt = new DirectoryIterator($backup_destination);
        
        $custom_tpl = array();
        foreach ($dirIt as $fileinfo) {
            
            if ($fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }
            $custom_tpl[] = $fileinfo->getFilename();
        }
        
        if (count($custom_tpl) > 0) {
            
            foreach($custom_tpl as $tpl_file) {
                
                $tpl_source_file = $backup_destination . $tpl_file;
                $tpl_destination_file = $this->tpl_dir . $tpl_file;
                
                $cp = copy($tpl_source_file, $tpl_destination_file);
                
                if ($cp == false) {
                    $tpl_data = file_get_contents($tpl_source_file);
                    $handle   = fopen($tpl_destination_file, 'w');
                    fwrite($handle, $data);
                    fclose($handle);
                }

                unlink($tpl_source_file);
            }
        }

        rmdir($backup_destination);
    }    
    
    protected function _getBackupDestination()
    {
        $tmp = get_temp_dir() . 'amazonsimpleadmin_tpl_backup' . DIRECTORY_SEPARATOR;
        return $tmp;
    }
    
    /**
     * trys to connect to the amazon webservice
     */
    protected function connect ()
    {
        require_once ASA_LIB_DIR . 'Asa/Service/Amazon.php';
        
        try {                    
            $amazon = Asa_Service_Amazon::factory(
                $this->_amazon_api_key, 
                $this->_amazon_api_secret_key, 
                $this->amazon_tracking_id, 
                $this->_amazon_country_code,
                $this->_amazon_api_connection_type
            );

            return $amazon;
                
        } catch (Exception $e) {
            if ($this->isDebug() && $this->_debugger != null) {
                $this->_debugger->write($e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * 
     */
    protected function _initCache ()
    {
        if (!$this->_isCache()) {
            return null;
        }
        
        try {    
            
            require_once ASA_LIB_DIR . 'AsaZend/Cache.php';
            
            $_asa_cache_lifetime  = get_option('_asa_cache_lifetime');
            $_asa_cache_dir       = get_option('_asa_cache_dir');
            
            $current_cache_dir    = (!empty($_asa_cache_dir) ? $_asa_cache_dir : 'cache');

            // cache lifetime in seconds
            $lifetime = !empty($_asa_cache_lifetime) ? $_asa_cache_lifetime : 7200;

            $frontendOptions = array(
               'lifetime' => $lifetime,
               'automatic_serialization' => true
            );
            
            $backendOptions = array(
                'cache_dir' => dirname(__FILE__) . DIRECTORY_SEPARATOR . $current_cache_dir
            );
            
            // getting a AsaZend_Cache_Core object
            $cache = AsaZend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
            return $cache;

       } catch (Exception $e) {
            return null;
       }
    }
    
    /**
     * Determines if cache is activated and cache dir is writable
     * @return bool
     */
    protected function _isCache()
    {
        $_asa_cache_dir    = get_option('_asa_cache_dir');
        $current_cache_dir = (!empty($_asa_cache_dir) ? $_asa_cache_dir : 'cache');
        
        if (get_option('_asa_cache_active') && 
            is_writable(dirname(__FILE__) . '/' . $current_cache_dir)) {
            return true;
        }
        
        return false;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return get_option('_asa_debug');
    }

    /**
     * @return bool
     */
    public function isErrorHandling()
    {
        return get_option('_asa_error_handling');
    }

    public function getDebugger()
    {
        return $this->_debugger;
    }

    /**
     * @return void
     */
    protected function _initDebugger()
    {
        require_once ASA_LIB_DIR . 'Asa/Debugger.php';
        try {
            $this->_debugger = Asa_Debugger::factory();
        } catch (Exception $e) {
            $this->_debugger_error = $e->getMessage();
        }
    }
    
    /**
     * action function for above hook
     *
     */
    public function createAdminMenu () 
    {           
        // Add a new submenu under Options:
        add_options_page('AmazonSimpleAdmin', 'AmazonSimpleAdmin', 'manage_options', 'amazonsimpleadmin/amazonsimpleadmin.php', array($this, 'createOptionsPage'));
        add_action('admin_head', array($this, 'getOptionsHead'));
        wp_enqueue_script( 'listman' );
    }
    
    /**
     * creates the AmazonSimpleAdmin admin page
     *
     */
    public function createOptionsPage () 
    {
        echo '<div id="amazonsimpleadmin-general" class="wrap">';
        echo '<h2>AmazonSimpleAdmin (ASA 1)</h2>';

        $this->_displayPreDispatcher($this->task);
        echo $this->getTabMenu($this->task);
        #echo '<div style="clear: both"></div>';
        echo '<div id="asa_content">';
        $this->_displayDispatcher($this->task);
        echo '</div>';
    }
    
    /**
     * 
     */
    protected function getTabMenu ($task)
    {
        $navItemFormat = '<a href="%s" class="nav-tab %s">%s</a>';

        $nav  = '<h2 class="nav-tab-wrapper">';
        $nav .= sprintf($navItemFormat, $this->plugin_url, (in_array($task, array(null, 'checkDonation'))) ? 'nav-tab-active' : '', __('Setup', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=options', (($task == 'options') ? 'nav-tab-active' : ''), __('Options', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=collections', (($task == 'collections') ? 'nav-tab-active' : ''), __('Collections', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=cache', (($task == 'cache') ? 'nav-tab-active' : ''), __('Cache', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=usage', (($task == 'usage') ? 'nav-tab-active' : ''), __('Usage', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=faq', (($task == 'faq') ? 'nav-tab-active' : ''), __('FAQ', 'asa1'));
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=test', (($task == 'test') ? 'nav-tab-active' : ''), __('Test', 'asa1'));
        if ($this->isErrorHandling()) {
            $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=log', (($task == 'log') ? 'nav-tab-active' : ''), __('Log', 'asa1'));
        }
        $nav .= sprintf($navItemFormat, $this->plugin_url.'&task=credits', (($task == 'credits') ? 'nav-tab-active' : ''), __('Credits', 'asa1'));


        $nav .= '</h2><br />';
        return $nav;
    }
    
    /**
     * 
     * Enter description here ...
     * @param $task
     */
    protected function _getSubMenu ($task)
    {
        $_asa_donated = get_option('_asa_donated');
        
        $nav = '<div style="clear: both"></div>';

        switch ($task) {
            case 'options':
                $banner = 'options';
                break;
            case 'collections':
                $banner = 'collections';
                break;
            case 'cache':
                $banner = 'cache';
                break;
            default:
                $banner = 'default';
        }

        ?>
        <div class="asa_info_box asa_info_box_outer">
            <a href="https://getasa2.com/" target="_blank"><img src="<?php echo asa_plugins_url( 'img/asa2-banner-'. $banner .'.png', __FILE__); ?>?v=<?php echo self::VERSION; ?>" width="800" height="120" /></a>
        </div>

        <?php
        
        if (!$this->_isCache()) {
            $nav .= '<div class="error"><p>'. sprintf( __('It is highly recommended to activate the <a href="%s">cache</a>!', 'asa1'), $this->plugin_url .'&task=cache') .'</p></div>';
        }
        if ($this->isDebug()) {
            $nav .= '<div class="asa_box_warning"><p>'. __('Debugging mode is active. Be sure to deactivate it when you do not need it anymore.', 'asa1') .'</p></div>';
        }
        return $nav;
    }

    protected function _displayPreDispatcher ($task)
    {
        switch ($task) {
            case 'options':

                if (count($_POST) > 0 && isset($_POST['info_update'])) {

                    $options = array(
                        '_asa_product_preview',
                        '_asa_parse_comments',
                        '_asa_async_load',
                        '_asa_hide_meta_link',
                        '_asa_use_short_amazon_links',
                        '_asa_use_amazon_price_only',
                        '_asa_debug',
                        '_asa_get_rating_alternative',
                        '_asa_custom_widget_class',
                        '_asa_replace_empty_main_price',
                        '_asa_error_handling',
                        '_asa_admin_error_frontend',
                        '_asa_use_error_tpl',
                        '_asa_error_email_notification',
                        '_asa_error_email_notification_bridge_page_id',
                    );

                    foreach ($options as $opt) {
                        $$opt = isset($_POST[$opt]) ? sanitize_text_field($_POST[$opt]) : null;
                    }

                    update_option('_asa_product_preview', $_asa_product_preview);
                    update_option('_asa_parse_comments', $_asa_parse_comments);
                    update_option('_asa_async_load', $_asa_async_load);
                    update_option('_asa_hide_meta_link', $_asa_hide_meta_link);
                    update_option('_asa_use_short_amazon_links', $_asa_use_short_amazon_links);
                    update_option('_asa_use_amazon_price_only', $_asa_use_amazon_price_only);
                    update_option('_asa_debug', $_asa_debug);
                    update_option('_asa_get_rating_alternative', $_asa_get_rating_alternative);
                    update_option('_asa_custom_widget_class', $_asa_custom_widget_class);
                    update_option('_asa_replace_empty_main_price', $_asa_replace_empty_main_price);
                    update_option('_asa_error_handling', $_asa_error_handling);
                    update_option('_asa_admin_error_frontend', $_asa_admin_error_frontend);
                    update_option('_asa_use_error_tpl', $_asa_use_error_tpl);
                    update_option('_asa_error_email_notification', $_asa_error_email_notification);
                    update_option('_asa_error_email_notification_bridge_page_id', $_asa_error_email_notification_bridge_page_id);

                    if ($this->isErrorHandling()) {
                        $this->getLogger()->initTable();
                    }

                    $this->_displaySuccess(__('Settings saved.', 'asa1'));
                }

                if ($this->isDebug()) {
                    $this->_initDebugger();
                    if (!empty($_POST['_asa_debug_clear'])) {
                        $this->_debugger->clear();
                    }
                }

                break;
        }
    }

    /**
     * @param $task
     */
    protected function _beforeOutput($task)
    {
        switch ($task) {

            case 'collections':

                require_once(dirname(__FILE__) . '/AsaCollection.php');
                $this->collection = new AsaCollection($this->db);

                if (isset($_POST['submit_export_collection'])) {

                    $collection_id = strip_tags($_POST['select_manage_collection']);
                    $collection_label = $this->collection->getLabel($collection_id);

                    if ($collection_label !== null) {
                        $this->collection->export((int)$collection_id, $this->_amazon_country_code);
                    }

                } elseif (isset($_POST['submit_export_all_collections'])) {

                    $collections = $this->collection->getAll();

                    if (!empty($collections)) {
                        $this->collection->export(array_keys($collections), $this->_amazon_country_code);
                    }
                }

                break;
        }
    }
    
    /**
     * the actual options page content
     *
     */
    protected function _displayDispatcher ($task) 
    {
        $_asa_donated = get_option('_asa_donated');
        if ($task == 'checkDonation' && empty($_asa_donated)) {
            $this->_checkDonated();
        }
        $_asa_newsletter = get_option('_asa_newsletter');
        if ($task == 'checkNewsletter' && empty($_asa_newsletter)) {
            $this->_checkNewsletter();
        }
                
        
        
        switch ($task) {
                
            case 'collections':
                
                require_once(dirname(__FILE__) . '/AsaCollection.php');
                $this->collection = new AsaCollection($this->db);

                $params = array();
                
                
                
                if (isset($_POST['deleteit_collection_item'])) {

                    /**
                     * Delete collection item(s)
                     */
                    if (!wp_verify_nonce($_POST['nonce'], 'asa1_manage_collection')) {
                        $this->error['manage_collection'] = __('Invalid access', 'asa1');
                    } else {
                        $delete_items = $_POST['delete_collection_item'];
                        if (count($delete_items) > 0) {
                            foreach ($delete_items as $item) {
                                $this->collection->deleteAsin($item);
                            }
                        }
                    }
                }
                
                if (isset($_POST['submit_import'])) {

                    /**
                     * Import collection
                     */
                    if (!wp_verify_nonce($_POST['nonce'], 'asa1_import_collection')) {
                        $this->error['submit_new_asin'] = __('Invalid access', 'asa1');
                    } else {

                        require_once(dirname(__FILE__) . '/AsaCollectionImport.php');

                        $file = $_FILES['importfile']['tmp_name'];
                        $import = new AsaCollectionImport($file, $this->collection);
                        $import->import();

                        if ($import->getError() != null) {
                            $this->error['submit_import'] = $import->getError();
                        } else {
                            $importedCollections = $import->getImportedCollections();
                            $this->success['submit_import'] = sprintf(__('Collections imported: %s'), implode(', ', $importedCollections));
                        }
                    }
                }

                if (isset($_POST['submit_new_asin'])) {

                    /**
                     * Add item to collection
                     */

                    if (!wp_verify_nonce($_POST['nonce'], 'asa1_add_to_collection')) {
                        $this->error['submit_new_asin'] = __('Invalid access', 'asa1');
                    } else {

                        $asin = strip_tags($_POST['new_asin']);
                        $collection_id = strip_tags($_POST['collection']);
                        $item = $this->_getItem($asin);

                        if ($item === null) {
                            // invalid asin
                            $this->error['submit_new_asin'] = __('invalid ASIN', 'asa1');

                        } else if ($this->collection->checkAsin($asin, $collection_id) !== null) {
                            // asin already added to this collection
                            $this->error['submit_new_asin'] = sprintf(
                                __('ASIN already added to collection <strong>%s</strong>', 'asa1'),
                                $this->collection->getLabel($collection_id)
                            );

                        } else {

                            if ($this->collection->addAsin($asin, $collection_id) === true) {
                                $this->success['submit_new_asin'] = sprintf(
                                    __('<strong>%s</strong> added to collection <strong>%s</strong>', 'asa1'),
                                    $item->Title,
                                    $this->collection->getLabel($collection_id)
                                );
                            }
                        }
                    }
                    
                } else if (isset($_POST['submit_manage_collection'])) {
                    
                    $collection_id = strip_tags($_POST['select_manage_collection']);
                    
                    $params['collection_items'] = $this->collection->getItems($collection_id);
                    $params['collection_id']     = $collection_id;

                } else if (isset($_GET['select_manage_collection']) && isset($_GET['update_timestamp'])) {
                    
                    $item_id = strip_tags($_GET['update_timestamp']);
                    $this->collection->updateItemTimestamp($item_id);
                    
                    $collection_id = strip_tags($_GET['select_manage_collection']);
                    $params['collection_items'] = $this->collection->getItems($collection_id);
                    $params['collection_id']     = $collection_id;
                    
                } else if (isset($_POST['submit_delete_collection'])) {

                    /**
                     * Delete collection
                     */
                    if (!wp_verify_nonce($_POST['nonce'], 'asa1_manage_collection')) {
                        $this->error['manage_collection'] = __('Invalid access', 'asa1');
                    } else {

                        $collection_id = strip_tags($_POST['select_manage_collection']);
                        $collection_label = $this->collection->getLabel($collection_id);

                        if ($collection_label !== null) {
                            $this->collection->delete($collection_id);
                        }

                        $this->success['manage_collection'] = sprintf(
                            __('collection deleted: <strong>%s</strong>', 'asa1'),
                            $collection_label
                        );
                    }

                } else if (isset($_POST['submit_new_collection'])) {

                    /**
                     * Create new collection
                     */

                    if (!wp_verify_nonce($_POST['nonce'], 'asa1_create_collection')) {
                        $this->error['submit_new_collection'] = __('Invalid access', 'asa1');
                    } else {
                        $collection_label = str_replace(' ', '_', trim($_POST['new_collection']));
                        $collection_label = preg_replace("/[^a-zA-Z0-9_]+/", "", $collection_label);

                        if (empty($collection_label)) {
                            $this->error['submit_new_collection'] = __('Invalid collection label', 'asa1');
                        } else {
                            if ($this->collection->create($collection_label) == true) {
                                $this->success['submit_new_collection'] = sprintf(
                                    __('New collection <strong>%s</strong> created'),
                                    $collection_label
                                );
                            } else {
                                $this->error['submit_new_collection'] = __('This collection already exists', 'asa1');
                            }
                        }
                    }
                
                } else if (isset($_POST['submit_collection_init']) && 
                    isset($_POST['activate_collections'])) {

                    $this->collection->initDB();
                }
                
                echo $this->_getSubMenu($task);
                
                if ($this->db->get_var("SHOW TABLES LIKE '". $this->db->prefix ."asa_collection%'") === null) {
                    $this->_displayCollectionsSetup();
                } else {
                    $this->_displayCollectionsPage($params);
                }
                break;
                
            case 'usage':

                echo $this->_getSubMenu($task);

                $this->_displayUsagePage();
                break;

            case 'faq':

                echo $this->_getSubMenu($task);

                $this->_displayFaqPage();
                break;

            case 'test':

                echo $this->_getSubMenu($task);

                $this->_displayTestPage();
                break;

            case 'log':

                echo $this->_getSubMenu($task);

                $this->_displayLogPage();
                break;

            case 'credits':

                echo $this->_getSubMenu($task);

                $this->_displayCreditsPage();
                break;
                
            case 'cache':
                
                if (isset($_POST['clean_cache'])) {
                    
                    if (empty($this->cache)) {
                        $this->error['submit_cache'] = __('Cache not activated!', 'asa1');
                    } else {
                        $this->cache->clean(AsaZend_Cache::CLEANING_MODE_ALL);
                        $this->success['submit_cache'] = __('Cache cleaned up!', 'asa1');
                    }
                    
                } else if (count($_POST) > 0) {

                    foreach (array(
                                 '_asa_cache_lifetime',
                                 '_asa_cache_dir',
                                 '_asa_cache_active',
                                 '_asa_cache_skip_on_admin') as $opt) {
                        $$opt = isset($_POST[$opt]) ? sanitize_text_field($_POST[$opt]) : null;
                    }
                    update_option('_asa_cache_lifetime', intval($_asa_cache_lifetime));
                    update_option('_asa_cache_dir', $_asa_cache_dir);
                    update_option('_asa_cache_active', intval($_asa_cache_active));
                    update_option('_asa_cache_skip_on_admin', intval($_asa_cache_skip_on_admin));

                    $this->success['submit_cache'] = __('Cache options updated!', 'asa1');
                }
                
                echo $this->_getSubMenu($task);
                
                $this->_displayCachePage();
                break;

            case 'options':

                echo $this->_getSubMenu($task);

                $this->_displayOptionsPage();
                break;

            default:
                
                if (count($_POST) > 0 && isset($_POST['setup_update'])) {

                    if (!wp_verify_nonce($_POST['nonce'], 'asa_setup')) {
                        $this->_displayError(__('Invalid access.', 'asa1'));
                    } else {
                        $_asa_amazon_api_key = sanitize_text_field(strip_tags(trim($_POST['_asa_amazon_api_key'])));
                        $_asa_amazon_api_secret_key = sanitize_text_field(base64_encode(strip_tags(trim($_POST['_asa_amazon_api_secret_key']))));
                        $_asa_amazon_tracking_id = sanitize_text_field(strip_tags(trim($_POST['_asa_amazon_tracking_id'])));
                        $_asa_api_connection_type = sanitize_text_field(strip_tags(trim($_POST['_asa_api_connection_type'])));
                        $_asa_api_connection_type = ifw_filter_scalar($_asa_api_connection_type, array('http', 'https'), 'http');

                        update_option('_asa_amazon_api_key', $_asa_amazon_api_key);
                        update_option('_asa_amazon_api_secret_key', $_asa_amazon_api_secret_key);
                        update_option('_asa_amazon_tracking_id', $_asa_amazon_tracking_id);
                        update_option('_asa_api_connection_type', $_asa_api_connection_type);

                        if (isset($_POST['_asa_amazon_country_code'])) {
                            $_asa_amazon_country_code = strip_tags($_POST['_asa_amazon_country_code']);
                            if (!Asa_Service_Amazon::isSupportedCountryCode($_asa_amazon_country_code)) {
                                $_asa_amazon_country_code = 'US';
                            }
                            update_option('_asa_amazon_country_code', $_asa_amazon_country_code);
                        }

                        $this->_displaySuccess(__('Settings saved.', 'asa1'));
                    }
                }
                
                echo $this->_getSubMenu($task);
                
                $this->_displaySetupPage();
        }
    }
    
    /**
     * check if user wants to hide the donation notice
     */
    protected function _checkDonated () {
        
        if ($_POST['asa_donated'] == '1') {
            update_option('_asa_donated', '1');            
        }
    }

    /**
     * check if user wants to hide the newsletter box
     */
    protected function _checkNewsletter () {

        if ($_POST['asa_check_newsletter'] == '1') {
            update_option('_asa_newsletter', '1');
        }
    }
    
    /**
     * collections asasetup screen
     *
     */
    protected function _displayCollectionsSetup ()
    {    
        ?>        
        <div id="asa_collections_setup" class="wrap">
        <fieldset class="options">
        <h2><?php _e('Collections') ?></h2>
        
        <p><?php _e('Do you want to activate the AmazonSimpleAdmin collections feature?', 'asa1'); ?></p>
        <form name="form_collection_init" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
        <label for="activate_collections">yes</label>
        <input type="checkbox" name="activate_collections" id="activate_collections" value="1">
        <p class="submit" style="margin:0; display: inline;">
            <input type="submit" name="submit_collection_init" value="<?php _e('Activate', 'asa1'); ?>" />
        </p>
        </form>
        </fieldset>
        </div>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        
        <?php
    }
    
    /**
     * the actual options page content
     *
     */
    protected function _displayCollectionsPage ($params) 
    {
        extract($params);
                
        ?>
        <div id="collections_wrap">
        <h2><?php _e('Collections', 'asa1') ?></h2>

            <div class="asa_columns clearfix">
                <div class="asa_content">

                <p><span class="dashicons dashicons-editor-help"></span> <?php printf( __('Check out the <a href="%s" target="_blank">guide</a> if you do not know how to use collections.', 'asa1'), 'http://www.wp-amazon-plugin.com/guide/'); ?></p>

                <h3><?php _e('Create new collection', 'asa1'); ?></h3>
                <?php
                if (isset($this->error['submit_new_collection'])) {
                    $this->_displayError($this->error['submit_new_collection']);
                } else if (isset($this->success['submit_new_collection'])) {
                    $this->_displaySuccess($this->success['submit_new_collection']);
                }
                ?>

                <form name="form_new_collection" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
                    <label for="new_collection">
                        <span class="dashicons dashicons-plus"></span> <?php _e('New collection', 'asa1'); ?>:
                        <input type="text" name="new_collection" id="new_collection" maxlength="190" />
                    </label>
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('asa1_create_collection'); ?>" />

                <p style="margin:0; display: inline;">
                    <input type="submit" name="submit_new_collection" value="<?php _e('Save', 'asa1'); ?>" class="button-primary" />
                </p><br>
                    (<?php _e('Only alpha-numeric characters and underscore allowed', 'asa1'); ?>)
                </form>

                <h3><?php _e('Import collection', 'asa1'); ?></h3>
                <?php
                if (isset($this->error['submit_import'])) {
                    $this->_displayError($this->error['submit_import']);
                } else if (isset($this->success['submit_import'])) {
                    $this->_displaySuccess($this->success['submit_import']);
                }
                ?>
                <form action="<?php echo $this->plugin_url .'&task=collections'; ?>" name="form_import_collection" id="form_import_collection" method="post" enctype="multipart/form-data">
                    <label for="importfile"><?php _e('Import file', 'asa1'); ?>:</label>
                    <input type="file" name="importfile" id="importfile" accept="text/xml" />
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('asa1_import_collection'); ?>" />
                    <input type="submit" name="submit_import" value="<?php _e('Import', 'asa1'); ?>" class="button">
                    <p class="description"><?php _e('Please select a valid .xml file created by the export function.', 'asa1'); ?></p>
                    <br>

                </form>

                <h3><?php _e('Add to collection', 'asa1'); ?></h3>
                <?php
                if (isset($this->error['submit_new_asin'])) {
                    $this->_displayError($this->error['submit_new_asin']);
                } else if (isset($this->success['submit_new_asin'])) {
                    $this->_displaySuccess($this->success['submit_new_asin']);
                }
                ?>
                <form name="form_new_asin" action="<?php echo $this->plugin_url .'&task=collections'; ?>" method="post">
                    <label for="new_asin">
                        <span class="dashicons dashicons-plus"></span> <?php _e('Add Amazon item (ASIN)', 'asa1'); ?>:<br>
                        <input type="text" name="new_asin" id="new_asin" placeholder="ASIN" />
                    </label>
<!--                    <br><br>-->
<!--                    <label for="collection">--><?php //_e('to collection', 'asa1'); ?><!--:-->
                    <?php
                    $collection_id = false;
                    if (isset($_POST['collection'])) {
                        $collection_id = trim($_POST['collection']);
                    }
                    echo $this->collection->getSelectField('collection', $collection_id);
                    ?>
<!--                    </label>-->

                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('asa1_add_to_collection'); ?>" />

                    <p style="margin:0; display: inline;">
                        <input type="submit" name="submit_new_asin" value="<?php _e('Save', 'asa1'); ?>" class="button-primary" />
                    </p>
                </form>
            </div>
            <div class="asa_sidebar">
                <?php $this->_displaySidebar(); ?>
            </div>
        </div>

        <div style="clear: both;"></div>
        <a name="manage_collection"></a>
        <h3><?php _e('Manage collections', 'asa1'); ?></h3>
        <?php
        if (isset($this->error['manage_collection'])) {
            $this->_displayError($this->error['manage_collection']);
        } else if (isset($this->success['manage_collection'])) {
            $this->_displaySuccess($this->success['manage_collection']);
        }
        ?>
        <form name="manage_colection" id="manage_colection" action="<?php echo $this->plugin_url .'&task=collections'; ?>#manage_collection" method="post">
            <label for="select_manage_collection"><?php _e('Collection', 'asa1'); ?>:</label>

            <?php
            $manage_collection_id = false;
            if (isset($_POST['select_manage_collection'])) {
                $manage_collection_id = trim($_POST['select_manage_collection']);
            }
            echo $this->collection->getSelectField('select_manage_collection', $manage_collection_id);
            ?>

            <p style="margin:0; display: inline;">
                <input type="submit" name="submit_manage_collection" value="<?php _e('Browse', 'asa1'); ?>" class="button-primary" />
            </p>
            <p style="margin:0; display: inline;">
                <input type="submit" name="submit_export_collection" value="<?php _e('Export', 'asa1'); ?>" class="button" />
            </p>
            <p style="margin:0; display: inline;">
                <input type="submit" name="submit_export_all_collections" value="<?php _e('Export all', 'asa1'); ?>" class="button" />
            </p>
            <p style="margin:0; display: inline;">
                <input type="submit" name="submit_delete_collection" value="<?php _e('Delete collection', 'asa1'); ?>" onclick="return asa_deleteCollection();" class="button" />
            </p>
            <p id="asa_collection_shortcode" style="margin:0; display: inline;">
                <?php _e('Shortcode', 'asa1'); ?>: <span class="selectable" style="display: inline-block;"></span>
            </p>
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('asa1_manage_collection'); ?>" />
        </form>

        <?php
        if (isset($collection_items) && !empty($collection_items)) {

            $table = '';
            $table .= '<form id="collection-filter" action="'.$this->plugin_url .'&task=collections" method="post">';
            $table .= '<input type="hidden" name="nonce" value="' . wp_create_nonce('asa1_manage_collection') . '" />';

            $table .= '<div class="tablenav">
                <div class="alignleft">
                <input type="submit" class="button-secondary delete" name="deleteit_collection_item" value="'. __('Delete selected', 'asa1') .'" onclick="return asa_deleteCollectionItems(\''. __("Delete selected collection items from collection?", "asa1") .'\');"/>
                <input type="hidden" name="submit_manage_collection" value="1" />
                <input type="hidden" name="select_manage_collection" value="'. $collection_id .'" />
                </div>
                <br class="clearfix">
                </div>';

            $table .= '<table class="widefat"><thead><tr>';
            $table .= '<th scope="col" style="text-align: center"><input type="checkbox" onclick="asa_checkAll();"/></th>';
            $table .= '<th scope="col" width="[thumb_width]"></th>';
            $table .= '<th scope="col" width="120">ASIN</th>';
            $table .= '<th scope="col" width="120">'. __('Price', 'asa1') .'</th>';
            $table .= '<th scope="col">'. __('Title', 'asa1') .'</th>';
            $table .= '<th scope="col" width="160">'. __('Timestamp', 'asa1') . '</th>';
            $table .= '<th scope="col"></th>';
            $table .= '</tr></thead>';
            $table .= '<tbody id="the-list">';

            $thumb_max_width = array();

            for ($i=0;$i<count($collection_items);$i++) {

                $row = $collection_items[$i];
                $item = $this->_getItem((string) $row->collection_item_asin);

                if ($item === null) {
                    continue;
                }
                if ($i%2==0) {
                    $tr_class ='';
                } else {
                    $tr_class = ' class="alternate"';
                }

                $title = str_replace("'", "\'", $item->Title);

                $table .= '<tr id="collection_item_'. $row->collection_item_id .'"'.$tr_class.'>';

                $table .= '<th class="check-column" scope="row" style="text-align: center"><input type="checkbox" value="'. $row->collection_item_id .'" name="delete_collection_item[]"/></th>';
                if ($item->SmallImage == null) {
                    $thumbnail = asa_plugins_url( 'img/no_image.gif', __FILE__ );
                } else {
                    $thumbnail = $item->SmallImage->Url->getUri();
                }

                if (isset($item->Offers->Offers)) {
                    $price = $item->Offers->Offers[0]->FormattedPrice;
                } else {
                    $price = '---';
                }

                $table .= '<td width="[thumb_width]"><a href="'. $item->DetailPageURL .'" target="_blank"><img src="'. $thumbnail .'" /></a></td>';
                $table .= '<td width="120">'. $row->collection_item_asin .'</td>';
                $table .= '<td width="120">'. $price .'</td>';
                $table .= '<td><span id="">'. $item->Title .'</span></td>';
                $table .= '<td width="160">'. date(str_replace(' \<\b\r \/\>', ',', __('Y-m-d \<\b\r \/\> g:i:s a')), $row->timestamp) .'</td>';
                $table .= '<td><a href="'. $this->plugin_url .'&task=collections&update_timestamp='. $row->collection_item_id .'&select_manage_collection='. $collection_id .'" class="edit" onclick="return asa_set_latest('. $row->collection_item_id .', \''. sprintf(__('Set timestamp of &quot;%s&quot; to actual time?', 'asa1'), $title) . '\');" title="update timestamp">'. __('latest', 'asa1') .'</a></td>';
                $table .= '</tr>';

                $thumb_max_width[] = $item->SmallImage->Width;
            }

            rsort($thumb_max_width);

            $table .= '</tbody></table></form>';

            $search = array(
                '/\[thumb_width\]/',
            );

            $replace = array(
                $thumb_max_width[0],
            );

            echo preg_replace($search, $replace, $table);
            echo '<div id="ajax-response"></div>';

        } else if (isset($collection_id)) {
            echo '<p>' . __('Nothing found. Add some products.', 'asa1') .'</p>';
        }
        ?>

        </div>
        <?php
    }
    
    /**
     * the actual options page content
     *
     */
    protected function _displayUsagePage () 
    {
        ?>
        <div id="usage_wrap">
            <h2><?php _e('Usage', 'asa1') ?></h2>

            <div class="asa_columns clearfix">
                <div class="asa_content">
                    <p><span class="dashicons dashicons-editor-help"></span> <?php printf( __('Please visit the <a href="%s" target="_blank">Usage Guide</a> on the plugin\'s homepage to learn how to use it.', 'asa1'), 'http://www.wp-amazon-plugin.com/usage/' ); ?></a></p>

                    <h3><?php _e('Step by Step Guide', 'asa1'); ?></h3>
                    <p><span class="dashicons dashicons-editor-help"></span> <?php printf( __('Please read the <a href="%s" target="_blank">Step by Step Guide</a> if you are new to this plugin.', 'asa1'), 'http://www.wp-amazon-plugin.com/guide/'); ?></p>

                    <h3><?php _e('Available templates', 'asa1'); ?></h3>

                    <p><?php _e('This is a list of template files, ASA found on your server:', 'asa1') ?></p>
                    <p><span class="dashicons dashicons-editor-help"></span> <?php _e('Please read', 'asa1') ?>: <a href="http://www.wp-amazon-plugin.com/2015/13280/keeping-your-custom-templates-update-safe/" target="_blank">Keeping your custom templates update safe</a></p>
                    <ul id="tpl_list">
                    <?php
                    $templates = $this->getAllTemplates();
                    foreach ($templates as $template) {
                        echo '<li>'. $template .'</li>';
                    }
                    ?>
                    </ul>
                </div>
                <div class="asa_sidebar">
                    <?php $this->_displaySidebar(); ?>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * the actual options page content
     *
     */
    protected function _displayFaqPage ()
    {
        $faqUrl = 'http://www.wp-amazon-plugin.com/faq-remote/';

        $client = new AsaZend_Http_Client($faqUrl);
        $response = $client->request('GET');

        if ($response->isSuccessful()) {
            echo $response->getBody();
        } else {
            echo '<p>Could not load FAQ from '. $faqUrl . '</p>';
        }
    }


    /**
     * the actual options page content
     *
     */
    protected function _displayTestPage ()
    {
        $templates = $this->getAllTemplates();
        $mode = 'tpl';

        if (count($_POST) > 0 && isset($_POST['asin']) && !empty($_POST['asin'])) {
            $asin = esc_attr($_POST['asin']);
            if (isset($_POST['tpl'])) {
                $tpl = esc_attr($_POST['tpl']);
            } else {
                $tpl = 'demo';
            }
            if (isset($_POST['mode'])) {
                switch ($_POST['mode']) {
                    case 'ratings':
                        $mode = 'ratings';
                        break;
                    default:
                        $mode = 'tpl';
                }
            }
            if (isset($_POST['block-log'])) {
                $blockLog = true;
            }
        }
        ?>

        <h2><?php _e('Test', 'asa1'); ?></h2>

        <div class="asa_columns">
            <div class="asa_content">
                <p><?php printf(__('Insert an ASIN, select a template and press the "%s" button to test the output.', 'asa1'), __('Submit', 'asa1')); ?></p>
                <form method="post" id="asa_test_form">
                    <div class="form-group">
                    <label for="asin">ASIN:</label>
                    <input type="text" name="asin" id="asin" placeholder="ASIN" value="<?php if (isset($asin)): echo $asin; endif; ?>">
                </div>
                <div class="form-group">
                    <label for="tpl"><?php _e('Template', 'asa1'); ?>:</label>
                    <select name="tpl" id="tpl">
                        <?php
                        foreach ($templates as $template) {
                            $selected = (isset($tpl) && $template == $tpl) ? 'selected' : '';
                            echo '<option value="'. $template .'" '. $selected .'>'. $template .'</li>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <?php _e('Mode', 'asa1'); ?>:
                    <label>
                        <input type="radio" name="mode" id="mode_tpl" value="tpl" <?php echo ($mode == 'tpl') ? 'checked' : ''; ?>>
                        <?php _e('Template', 'asa1'); ?>
                    </label>
                    &nbsp;&nbsp;&nbsp;
                    <label>
                        <input type="radio" name="mode" id="mode_ratings" value="ratings" <?php echo ($mode == 'ratings') ? 'checked' : ''; ?>>
                        <?php _e('Ratings', 'asa1'); ?>
                    </label>
                </div>
                <?php if ($this->isErrorHandling()): ?>
                <div class="form-group">
                    <label>
                    <input type="checkbox" name="block-log" value="1" <?php echo (isset($blockLog)) ? 'checked' : ''; ?>>
                        <?php _e('Disable error log', 'asa1'); ?>
                    </label>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e('Submit', 'asa1'); ?>">
                </div>
            </form>
            </div>
            <div class="asa_sidebar">
                <?php //$this->_displaySidebar(); ?>
            </div>
        </div>

        <div class="clearfix"></div>

        <?php

        if (isset($asin) && !empty($asin)) {

            if (isset($blockLog)) {
                $this->getLogger()->setBlock(true);
            }

            echo '<h3>' . __('Result', 'asa1') . ':</h3>';

            if ($mode == 'tpl') {
                if (!isset($tpl) || empty($tpl)) {
                    $tpl = 'demo';
                }
                echo $this->getItem($asin, $tpl);
            } elseif ($mode == 'ratings') {
                $item = $this->_getItem($asin);
                // get the customer rating object
                $customerReviews = $this->getCustomerReviews($item, true);

                if ($customerReviews->isSuccess()) {

                    echo '<p>' . __('Successfully retrieved customer ratings.', 'asa1') . '</p>';
                    echo '<p>' . __('Total reviews:', 'asa1') . ' ' . $customerReviews->totalReviews . '</p>';
                    echo '<p>' . __('Average rating:', 'asa1') . ' ' . $customerReviews->averageRating . '</p>';
                    echo '<p>' . __('Image source:', 'asa1') . ' ' . $customerReviews->imgSrc . '</p>';
                    echo $customerReviews->imgTag;


                } else {

                    echo '<p>' . __('Customer ratings could not be retrieved.', 'asa1') . '</p>';
                    echo '<p>Error message: ' . $customerReviews->getErrorMessage() . '</p>';
                    echo '<pre>';
                }
            }

        } elseif (count($_POST) > 0) {
            _e('Invalid ASIN', 'asa1');
        }
    }

    protected function _displayLogPage()
    {
        require_once dirname(__FILE__) . '/AsaLogListTable.php';

        if (isset($_POST['action']) && $_POST['action'] == 'clear') {
            $this->getLogger()->clear();
            echo '<div class="updated"><p>'. __('All log entries have been deleted.') .'</p></div>';
        }

        $listTable = new AsaLogListTable();
        $listTable->setLogger($this->getLogger());
        $listTable->prepare_items();

        ?>
        <div id="asa_logs" class="wrap">
            <h2><?php _e('Log', 'asa1'); ?></h2>
            <form method="post">
            <?php $listTable->display(); ?>
            </form>
        </div>
        <?php
    }

    protected function _displayCreditsPage()
    {
        ?>
        <div id="asa_logs" class="wrap">
            <h2><?php _e('Credits', 'asa1'); ?></h2>
            <h3><?php _e('Thanks for translations', 'asa1'); ?></h3>
            <ul>
                <li><b>Serbian:</b> Ogi Djuraskovic (<a href="http://firstsiteguide.com/" target="_blank">http://firstsiteguide.com/</a>)</li>
                <li><b>Spanish:</b> Andrew Kurtis (<a href="http://www.webhostinghub.com/" target="_blank">http://www.webhostinghub.com/</a>)</li>
                <li><b>Russian:</b> Ivanka (<a href="http://www.coupofy.com/" target="_blank">http://www.coupofy.com/</a>)</li>
                <li><b>French:</b> Marie-Aude (<a href="http://www.lumieredelune.com/" target="_blank">http://www.lumieredelune.com/</a>)</li>
            </ul>
        </div>
        <?php
    }

    
    /**
     * Load options panel
     *
     */
    protected function _displayOptionsPage()
    {
        $this->_loadOptions();
    ?>
    <h2><?php _e('Options', 'asa1') ?></h2>

    <div class="asa_columns clearfix">
        <div class="asa_content">
            <form method="post">

            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_async_load"><?php _e('Use asynchronous mode (AJAX):', 'asa1') ?></label><br>
                            (BETA)
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_async_load" id="_asa_async_load" value="1"<?php echo (($this->_async_load == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('Requests to the Amazon webservice will be executed asynchronously. This will improve page load speed.', 'asa1'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_parse_comments"><?php _e('Parse comments:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_parse_comments" id="_asa_parse_comments" value="1"<?php echo (($this->_parse_comments == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('[asa] tags in comments will be parsed.', 'asa1'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_product_preview"><?php _e('Enable product preview links:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_product_preview" id="_asa_product_preview" value="1"<?php echo (($this->_product_preview == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('Product preview layers are only supported by US, UK and DE so far. This can effect the site to be loaded a bit slower due to link parsing.', 'asa1'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_hide_meta_link"><?php _e('Hide ASA link:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_hide_meta_link" id="_asa_hide_meta_link" value="1"<?php echo ((get_option('_asa_hide_meta_link') == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('Hides link to ASA homepage from Meta widget. Do not hide it to support this plugin.', 'asa1'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_use_short_amazon_links"><?php _e('Use short Amazon links:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_use_short_amazon_links" id="_asa_use_short_amazon_links" value="1"<?php echo ((get_option('_asa_use_short_amazon_links') == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php printf( __('Activates the short version of affiliate links like %s', 'asa1'), 'http://www.amazon.com/exec/obidos/ASIN/123456789/trackingid-12' ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_debug"><?php _e('Activate debugging:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_debug" id="_asa_debug" value="1"<?php echo ((get_option('_asa_debug') == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php printf( __('Important: Use debugging only temporarily if you are facing problems with ASA. Ask the <a href="%s" target="_blank">support</a> how to interpret the debugging information.', 'asa1'), 'http://www.wp-amazon-plugin.com/contact/' ); ?></p>
                            <?php if ($this->isDebug()): ?>
                            <?php if ($this->_debugger_error != null): ?>
                                <p><b><?php _e('Debugger error', 'asa1'); ?>: </b><?php echo $this->_debugger_error; ?></p>
                                <?php else:?>
                                <a href="<?php echo $this->plugin_url; ?>&task=options"><?php _e('Refresh', 'asa1'); ?></a>
                                <br />
                                <textarea name="debug_contents" id="debug_contents" rows="20" cols="100"><?php if (!empty($this->_debugger)) echo $this->_debugger->read(); ?></textarea>
                                <br />
                                <input type="checkbox" name="_asa_debug_clear" id="_asa_debug_clear" value="1" /><label for="_asa_debug_clear"><?php _e('Clear debugging data', 'asa1') ?></label>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_get_rating_alternative"><?php _e('Ratings parser alternative:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_get_rating_alternative" id="_asa_get_rating_alternative" value="1"<?php echo ((get_option('_asa_get_rating_alternative') == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('Try this option if you have problems with loading the product ratings', 'asa1'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_custom_widget_class"><?php _e('Custom widget class:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="text" name="_asa_custom_widget_class" id="_asa_custom_widget_class" value="<?php echo (get_option('_asa_custom_widget_class')) != '' ? get_option('_asa_custom_widget_class') : ''; ?>" />
                            <p class="description"><?php _e('Set a custom CSS class for the outer widget container. Default is "AmazonSimpleAdmin_widget" which may get blocked by AdBlockers.', 'asa1'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_replace_empty_main_price"><?php _e('Empty main price text:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="text" name="_asa_replace_empty_main_price" id="_asa_replace_empty_main_price" value="<?php echo (get_option('_asa_replace_empty_main_price')) != '' ? get_option('_asa_replace_empty_main_price') : ''; ?>" />
                            <p class="description"><?php _e('Enter a text which should be displayed for placeholder {$OffersMainPriceFormattedPrice} if the main price is empty. Default is "--".', 'asa1'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><h3><?php _e('Error handling', 'asa1'); ?></h3></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_error_handling"><?php _e('Error handling:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_error_handling" id="_asa_error_handling" value="1"<?php echo ((get_option('_asa_error_handling') == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('Activates the error handling. Generates log entries e.g. when using invalid ASINs (see tab "Log"). Precondition for all following options.', 'asa1'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_admin_error_frontend"><?php _e('Admin front-end errors:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_admin_error_frontend" id="_asa_admin_error_frontend" value="1"<?php echo ((get_option('_asa_admin_error_frontend') == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('If an error occures while loading the products, display the error messages instead of an empty product box in the front-end for logged in admins only. Template file <b>error_admin.htm</b> will be used.', 'asa1'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_use_error_tpl"><?php _e('Error template:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_use_error_tpl" id="_asa_use_error_tpl" value="1"<?php echo ((get_option('_asa_use_error_tpl') == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('If an error occures while loading a product, display the error template instead of an empty product box. Template file <b>error.htm</b> will be used. ', 'asa1'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="_asa_error_email_notification"><?php _e('Email notification:', 'asa1') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="_asa_error_email_notification" id="_asa_error_email_notification" value="1"<?php echo ((get_option('_asa_error_email_notification') == true) ? 'checked="checked"' : '') ?> />
                            <p class="description"><?php _e('Enables the email notification feature. Enables you to receive notifications about product parsing errors with the same information like in the log entries (invalid ASINs and location where it is used).', 'asa1'); ?><br>
                            <?php printf(__('Read the <a href="%s" target="_blank">documentation</a> about how to setup this feature. <b>Error handling must be activated.</b>', 'asa1'), 'http://www.wp-amazon-plugin.com/email-notification-feature/'); ?>
                            </p>
                        </td>
                    </tr>

                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="info_update" class="button-primary" value="<?php _e('Update Options', 'asa1') ?> &raquo;" />
            </p>
            </form>
        </div>
        <div class="asa_sidebar">
            <?php $this->_displaySidebar(); ?>
        </div>
    </div>

    <?php
    }

    protected function _displaySidebar()
    {
        ?>
        <div class="asa_widget" id="asa2_widget">
            <h3>Get ASA 2!</h3>

        <?php if ($this->task != 'collections'): ?>
            <div class="asa_widget_inner">
                <div style="text-align: center;">
                    <img src="<?php echo asa_plugins_url('img/empty_stars.png', __FILE__); ?>" width="260" style="width: 130px;">
                </div>
                <p><?php _e('Having issues with <b>empty ratings stars</b>?', 'asa1'); ?><br>
                    <?php printf(__('Check out ASA2\'s <a%s>advanced ratings mode</a>.', 'asa1'),
                        ' href="http://docs.getasa2.com/ratings.html#advanced-ratings-mode" target="_blank"'); ?>
                </p>
                <a href="http://docs.getasa2.com/ratings.html#advanced-ratings-mode" target="_blank"><img src="<?php echo asa_plugins_url('img/adv_ratings.png', __FILE__); ?>" width="275" style="width: 275px;"></a>
            </div>
        <?php endif; ?>

            <div class="asa_widget_inner">
                <a class="premiumad-button" href="https://getasa2.com/" target="_blank"><?php _e('Go Pro with ASA 2', 'asa1'); ?></a>

                <?php if ($this->task == 'collections'): ?>
                    <p><?php _e('Learn more about Collections in ASA 2.', 'asa1'); ?></p>
                    <a href="https://getasa2.com/features/#asa2_collections" target="_blank"><img src="<?php echo asa_plugins_url( 'img/asa2-collections.png', __FILE__); ?>" width="100%"></a>

                <?php elseif ($this->task == 'options' || $this->task == 'test'): ?>
                    <p><?php _e('Watch the ASA 2 <b>teaser video</b> on YouTube.', 'asa1'); ?></p>
                    <a href="https://www.youtube.com/watch?v=lhKdLgAPELk" target="_blank"><img src="<?php echo asa_plugins_url( 'img/asa2_teaser_video_thumbnail.png', __FILE__); ?>" width="100%"></a>

                <?php elseif ($this->task == 'cache'): ?>
                    <p><?php printf(__('Learn more about <a%s>ASA 2\'s caching strategy</a> in the manual.', 'asa1'), ' href="http://docs.getasa2.com/caching.html" target="_blank"'); ?></p>
                    <a href="http://docs.getasa2.com/caching.html" target="_blank"><img src="<?php echo asa_plugins_url( 'img/asa2-cache.png', __FILE__); ?>" width="100%"></a>

                <?php else: ?>
                    <p><?php _e('ASA 2 is <b>backwards compatible</b>.', 'asa1'); ?> <?php printf(__('It comes with a <a%s>Migration Wizard</a> for templates and collections.', 'asa1'), ' href="http://docs.getasa2.com/migration_wizard.html" target="_blank"'); ?></p>

                    <p><span class="dashicons dashicons-book"></span> <a href="http://docs.getasa2.com/kickstarter_guide_for_asa2_switchers.html" target="_blank"><?php _e('Kickstarter Guide for ASA 2 Switchers', 'asa1'); ?></a></p>
                    <p><b><?php _e('Just some of ASA 2\'s amazing new features:', 'asa1'); ?></b></p>
                    <ul>
                        <li><a href="http://docs.getasa2.com/managed_templates.html" target="_blank"><?php _e('Managed Templates', 'asa1'); ?></a></li>
                        <li><?php _e('Customizable Templates (without programming skills)', 'asa1'); ?><br>
                            <?php printf(__('Visit the <a%s>Templates Demo Page</a>.', 'asa1'), ' href="http://www.asa2-demo.de/templates/" target="_blank"'); ?>
                            </li>
                        <li><a href="https://www.youtube.com/watch?v=Bi_KAqCqgks" target="_blank"><?php _e('Product Picker', 'asa1'); ?></a> (<?php _e('Editor button', 'asa1'); ?>)</li>
                        <li><a href="http://docs.getasa2.com/ratings.html" target="_blank"><?php _e('Advanced Ratings Handling', 'asa1'); ?></a></li>
                        <li><a href="http://docs.getasa2.com/repo.html" target="_blank"><?php _e('Product Repository', 'asa1'); ?></a> (<?php _e('speed up your site!', 'asa1'); ?>)</li>
                        <li><?php _e('Parallel use of all Amazon stores', 'asa1'); ?></li>
                        <li><a href="http://docs.getasa2.com/setup.html#associate-id-sets" target="_blank"><?php _e('Manage multiple Associate IDs in sets', 'asa1'); ?></a></li>
                        <li><a href="http://docs.getasa2.com/template_syntax.html" target="_blank"><?php _e('Powerful template syntax', 'asa1'); ?></a> (<?php _e('Conditions', 'asa1'); ?>...)</li>
                        <li><a href="http://docs.getasa2.com/internationalization.html" target="_blank"><?php _e('Internationalization', 'asa1'); ?></a> (<?php _e('Geolocation', 'asa1'); ?>)</li>
                        <li><a href="http://docs.getasa2.com/feeds.html" target="_blank"><?php _e('Bestseller lists', 'asa1'); ?></a></li>
                        <li><a href="http://docs.getasa2.com/notifications.html" target="_blank"><?php _e('Email notifications', 'asa1'); ?></a></li>
                        <li><a href="http://docs.getasa2.com/templates_translation.html" target="_blank"><?php _e('Translated Templates', 'asa1'); ?></a></li>
                        <li><a href="http://docs.getasa2.com/caching.html" target="_blank"><?php _e('Multiple caches', 'asa1'); ?></a></li>
                        <li><a href="http://docs.getasa2.com/cronjobs.html" target="_blank"><?php _e('Cronjobs', 'asa1'); ?></a></li>
                        <li><?php _e('HTTPS ready', 'asa1'); ?></li>
                        <li><?php _e('SEO ready', 'asa1'); ?></li>
                        <li><span class="dashicons dashicons-book"></span> <a href="http://docs.getasa2.com/" target="_blank"><?php _e('Extensive documentation', 'asa1'); ?></a></li>
                    </ul>
                    <p><a href="https://getasa2.com/features/" target="_blank"><?php _e('See detailed list of features', 'asa1'); ?></a></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($this->task == null): ?>
        <div class="asa_widget" id="asa_connect">
            <h3>Connect</h3>
            <div class="asa_widget_inner">
                <p><span class="dashicons dashicons-twitter"></span> <a href="https://twitter.com/ifeelwebde" target="_blank">Timo on Twitter</a></p>
                <p><span class="dashicons dashicons-video-alt3"></span> <a href="https://www.youtube.com/channel/UCi67kdl2D4hVFNndVEl0uAw" target="_blank">ASA YouTube Channel</a></p>
                <p><span class="dashicons dashicons-admin-site"></span> <a href="http://www.wp-amazon-plugin.com/blog/" target="_blank">ASA News</a></p>
                <p><span class="dashicons dashicons-format-chat"></span> <a href="http://www.wp-amazon-plugin.com/contact/" target="_blank"><?php _e('Contact', 'asa'); ?></a></p>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $_asa_newsletter = get_option('_asa_newsletter');
        if (empty($_asa_newsletter) && $this->task != 'collections') :
            ?>

            <div class="asa_widget" id="asa_newsletter">
                <h3><?php _e('Subscribe to the ASA newsletter', 'asa1') ?></h3>
                <div class="asa_widget_inner">
                    <form action="http://wp-amazon-plugin.us7.list-manage.com/subscribe/post?u=a11948220f94721bb8bcddc8b&amp;id=69a6051b59" method="post" target="_blank" novalidate>
                        <div class="mc-field-group">
                            <label for="mce-EMAIL"><?php _e('Email Address (required)', 'asa1'); ?>:</label>
                            <input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
                        </div>
                        <input type="submit" value="<?php _e('Subscribe', 'asa1') ?>" name="subscribe" id="mc-embedded-subscribe" class="button">
                    </form>
                    <br>
                    <form action="<?php echo $this->plugin_url; ?>&task=checkNewsletter" method="post">
                        <label for="asa_check_newsletter">
                            <input type="checkbox" name="asa_check_newsletter" id="asa_check_newsletter" value="1" />
                            <?php _e('I subscribed to the ASA newsletter already. Please hide this box.', 'asa1'); ?>
                        </label>
                        <input type="submit" value="<?php _e('Hide', 'asa1'); ?>" class="button" />
                    </form>
                </div>
            </div>

            <?php
        endif;
        ?>
        <?php
    }

    /**
     * Tests connection
     *
     * @return array
     */
    public function testConnection()
    {
        $success = false;
        $message = '';

        try {
            $this->amazon = $this->connect();
            if ($this->amazon != null) {
                $this->amazon->testConnection();
                $success = true;
            } else {
                $message = __('Connection to Amazon Webservice failed. Please check the mandatory data.', 'asa1');
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        return array('success' => $success, 'message' => $message);
    }

    /**
     * Retrieves connections status
     *
     * @return bool
     */
    public function getConnectionStatus()
    {
        $result = $this->testConnection();
        return $result['success'] === true;
    }

    /**
     * Loads setup panel
     *
     */
    protected function _displaySetupPage ()
    {
        $_asa_status = false;
        
        $this->_getAmazonUserData();

        $connectionTestResult = $this->testConnection();

        if ($connectionTestResult['success'] === true) {
            $_asa_status = true;
        } else {
            $_asa_error = $connectionTestResult['message'];
        }

//        try {
//            $this->amazon = $this->connect();
//            if ($this->amazon != null) {
//                $this->amazon->testConnection();
//                $_asa_status = true;
//            } else {
//                 throw new Exception('Connection to Amazon Webservice failed. Please check the mandatory data.');
//            }
//        } catch (Exception $e) {
//            $_asa_error = $e->getMessage();
//        }
        ?>
        <div id="asa_setup">


        <h2><?php _e('Setup', 'asa1') ?></h2>
        <div class="asa_columns">
            <div class="asa_content">
                <h3><?php _e('Need help?', 'asa1') ?></h3>

                <div class="help_item">
                    <img src="<?php echo asa_plugins_url( 'img/faq.png', __FILE__); ?>" />
                    <a href="<?php echo $this->plugin_url?>&task=faq"><?php _e('FAQ', 'asa1') ?></a>
                </div>
                    <div class="help_item">
                        <img src="<?php echo asa_plugins_url( 'img/documentation.png', __FILE__); ?>" />
                        <a href="http://www.wp-amazon-plugin.com/documentation/" target="_blank"><?php _e('Online documentation', 'asa1') ?></a>
                </div>
                <div class="help_item">
                    <img src="<?php echo asa_plugins_url( 'img/forums.png', __FILE__); ?>" />
                    <a href="http://www.wp-amazon-plugin.com/forums/" target="_blank"><?php _e('Forums', 'asa1') ?></a>
                </div>
                <div class="help_item">
                    <img src="<?php echo asa_plugins_url( 'img/contact.png', __FILE__); ?>" />
                    <a href="http://www.wp-amazon-plugin.com/contact/" target="_blank"><?php _e('Contact', 'asa1') ?></a>
                </div>

                <br><br>

                <h3><?php _e('Credentials', 'asa1') ?></h3>

                <p><span id="_asa_status_label"><?php _e('Status', 'asa1') ?>:</span> <?php echo ($_asa_status == true) ? '<span class="_asa_status_ready">'. __('Ready', 'asa1') .'</span>' : '<span class="_asa_status_not_ready">'. __('Not Ready', 'asa1') .'</span>'; ?></p>

                <?php
                if (!empty($_asa_error)) {
                    echo '<div id="message" class="error"><p><strong>'. __('Error', 'asa1') .':</strong> '. $_asa_error;
                    echo '<br>'. __('Get help at', 'asa1') .' <a href="http://www.wp-amazon-plugin.com/faq/#setup_errors" target="_blank">http://www.wp-amazon-plugin.com/faq/#setup_errors</a></p></div>';
                    echo '<p class="error-message"><strong>'. __('Error', 'asa1') .':</strong> '. $_asa_error . '</p>';
                }
                ?>

                <p><?php _e('Please fill in your Amazon Product Advertising API credentials.', 'asa1') ?></p>
                <p><?php _e('Fields marked with * are mandatory:', 'asa1') ?></p>

                <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr valign="top">
                            <th scope="row">
                                <label for="_asa_amazon_api_key"<?php if (empty($this->_amazon_api_key)) { echo ' class="_asa_error_color"'; } ?>><?php _e('Your Amazon Access Key ID*:', 'asa1') ?></label>
                            </th>
                            <td>
                                <input type="text" name="_asa_amazon_api_key" id="_asa_amazon_api_key" autocomplete="off" value="<?php echo (!empty($this->_amazon_api_key)) ? $this->_amazon_api_key : ''; ?>" />
                                <a href="http://www.wp-amazon-plugin.com/register-amazon-affiliate-product-advertising-api/" target="_blank"><?php _e('How do I get one?', 'asa1'); ?></a>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <label for="_asa_amazon_api_secret_key"<?php if (empty($this->_amazon_api_secret_key)) { echo ' class="_asa_error_color"'; } ?>><?php _e('Your Secret Access Key*:', 'asa1'); ?></label>
                            </th>
                            <td>
                                <input type="password" name="_asa_amazon_api_secret_key" id="_asa_amazon_api_secret_key" autocomplete="off" value="<?php echo (!empty($this->_amazon_api_secret_key)) ? $this->_amazon_api_secret_key : ''; ?>" />
                                <a href="http://www.wp-amazon-plugin.com/register-amazon-affiliate-product-advertising-api/?#13" target="_blank"><?php _e('What is this?', 'asa1'); ?></a>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <label for="_asa_amazon_tracking_id"<?php if (empty($this->amazon_tracking_id)) { echo ' class="_asa_error_color"'; } ?>><?php _e('Your Amazon Tracking ID*:', 'asa1') ?></label>
                            </th>
                            <td>
                                <input type="text" name="_asa_amazon_tracking_id" id="_asa_amazon_tracking_id" autocomplete="off" value="<?php echo (!empty($this->amazon_tracking_id)) ? $this->amazon_tracking_id : ''; ?>" />
                                <a href="http://www.wp-amazon-plugin.com/finding-amazon-tracking-id/" target="_blank"><?php _e('Where do I get one?', 'asa1'); ?></a>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <label for="_asa_amazon_country_code"<?php if (empty($this->_amazon_country_code)) { echo ' class="_asa_status_not_ready"'; } ?>><?php _e('Your Amazon Country Code*:', 'asa1') ?></label>
                            </th>
                            <td>
                                <select name="_asa_amazon_country_code">
                                    <?php
                                    foreach (Asa_Service_Amazon::getCountryCodes() as $code) {
                                        if ($code == $this->_amazon_country_code) {
                                            $selected = ' selected="selected"';
                                        } else {
                                            $selected = '';
                                        }
                                        echo '<option value="'. $code .'"'.$selected.'>' . $code . '</option>';
                                    }
                                    ?>
                                </select> <img src="<?php echo asa_plugins_url( 'img/amazon_'. $this->_amazon_country_code .'_small.gif', __FILE__); ?>" id="selected_store" /> (<?php _e('Default', 'asa1'); ?>: US)
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <label for="_asa_api_connection_type"><?php _e('Connection type', 'asa1') ?>:</label>
                            </th>
                            <td>
                                <select name="_asa_api_connection_type">
                                    <option value="http" <?php echo $this->_amazon_api_connection_type == 'http' ? 'selected' : '';?>>HTTP</option>
                                    <option value="https" <?php echo $this->_amazon_api_connection_type == 'https' ? 'selected' : '';?>>HTTPS</option>
                                </select>
                                <?php _e('How the API should be connected.', 'asa1'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('asa_setup'); ?>">
                <input type="submit" name="setup_update" class="button-primary" value="<?php _e('Update Options', 'asa1') ?> &raquo;" />
                <!-- <input type="submit" name="info_clear" value="<?php _e('Clear Settings', 'asa1') ?> &raquo;" /> -->
                    <br /><br />
                    <b><?php _e('Notice', 'asa1'); ?>:</b><br />
                    <?php _e('If your status is ready but your implemented Amazon product boxes do not show any data, check the FAQ panel for more information (first entry).', 'asa1'); ?><br />
                </p>

                </form>

                <div id="asa_feed_box"></div>
            </div>
            <div class="asa_sidebar">
                <?php $this->_displaySidebar(); ?>
            </div>
        </div>
        </div>
        <?php
    }    
    
    /**
     * the cache options page content
     *
     */
    protected function _displayCachePage () 
    {    
        $_asa_cache_lifetime      = get_option('_asa_cache_lifetime');
        $_asa_cache_dir           = get_option('_asa_cache_dir');
        $_asa_cache_active        = get_option('_asa_cache_active');
        $_asa_cache_skip_on_admin = get_option('_asa_cache_skip_on_admin');
        $current_cache_dir        = (!empty($_asa_cache_dir) ? $_asa_cache_dir : 'cache');

        ?>
        <div id="cache_wrap">
        <h2><?php _e('Cache') ?></h2>

            <div class="asa_columns clearfix">
            <div class="asa_content">
                <form method="post">

            <?php
            if (isset($this->error['submit_cache'])) {
                $this->_displayError($this->error['submit_cache']);
            } else if (isset($this->success['submit_cache'])) {
                $this->_displaySuccess($this->success['submit_cache']);
            }
            ?>

            <div>
                <label for="_asa_cache_active"><?php _e('Activate cache:', 'asa1') ?>
                    <input type="checkbox" name="_asa_cache_active" id="_asa_cache_active" value="1" <?php echo (!empty($_asa_cache_active)) ? 'checked="checked"' : ''; ?> />
                </label>
            </div>
            <div>
                <label for="_asa_cache_skip_on_admin"><?php _e('Do not use cache when logged in as admin:', 'asa1') ?></label>
                <input type="checkbox" name="_asa_cache_skip_on_admin" id="_asa_cache_skip_on_admin" value="1" <?php echo (!empty($_asa_cache_skip_on_admin)) ? 'checked="checked"' : ''; ?> />
            </div>
            <div>
                <label for="_asa_cache_lifetime"><?php _e('Cache Lifetime (in seconds):', 'asa1') ?></label>
                <input type="text" name="_asa_cache_lifetime" id="_asa_cache_lifetime" value="<?php echo (!empty($_asa_cache_lifetime)) ? $_asa_cache_lifetime : '7200'; ?>" />
            </div>
            <div>
                <label for="_asa_cache_dir"><?php _e('Cache directory:', 'asa1') ?></label>
                <input type="text" name="_asa_cache_dir" id="_asa_cache_dir" value="<?php echo $current_cache_dir; ?>" /> (<?php _e('within asa plugin directory / default = "cache" / must be <strong>writable</strong>!', 'asa1'); ?>)
            </div>
            <div style="border: 1px solid #EDEDED; padding: 4px; background: #F8F8F8;">
            <?php
            echo dirname(__FILE__) . DIRECTORY_SEPARATOR . $current_cache_dir . ' ' . __('is', 'asa1') . ' ';
            if (is_writable(dirname(__FILE__) . '/' . $current_cache_dir)) {
                echo '<strong style="color:#177B31">'. __('writable', 'asa1') . '</strong>';
            } else {
                echo '<strong style="color:#B41216">'. __('not writable', 'asa1') . '</strong>';
            }
            ?>
            </div>
            <br />

            <p class="submit">
            <input type="submit" name="info_update" class="button-primary" value="<?php _e('Update Options', 'asa1') ?>" />
            <input type="submit" name="clean_cache" value="<?php _e('Clear Cache', 'asa1') ?>" class="button" />
            </p>

            </form>

            </div>
            <div class="asa_sidebar">
                <?php $this->_displaySidebar(); ?>
            </div>
        </div>
        </div>
        <?php
    }    
    
    /**
     * 
     */
    protected function _displayError ($error) 
    {
        echo '<div class="error"><p>'. __('Error', 'asa1') .': '. $error .'</p></div>';
    }
    
    /**
     * 
     */
    protected function _displaySuccess ($success) 
    {
        echo '<div class="updated"><p>'. __('Success', 'asa1') .': '. $success .'</p></div>';
    }    
    
    /**
     * parses post content
     * 
     * @param         string        post content
     * @return         string        parsed content
     */
    public function parseContent ($content)
    {
        $matches         = array();
        $matches_coll     = array();

        // single items
        preg_match_all($this->bb_regex, $content, $matches);

        if ($matches && count($matches[0]) > 0) {
            
            // get defaul template file
            $tpl_src = $this->getDefaultTpl();

            for ($i=0; $i<count($matches[0]); $i++) {
                
                $match         = $matches[0][$i];
                                
                $tpl_file        = null;
                $asin           = $matches[2][$i]; 

                $params         = preg_replace($this->_regex_param_separator, $this->_internal_param_delimit, strip_tags(trim($matches[1][$i])));
                $params         = explode($this->_internal_param_delimit, $params);
                $params         = array_map('trim', $params);
                $parse_params   = array();

                if (!empty($params[0])) {
                    foreach ($params as $param) {
                        $param = trim($param);
                        if (!strstr($param, '=')) {
                            $tpl_file = $param;
                        } else {
                            if (strstr($param, 'comment=')) {
                                // the comment feature
                                preg_match('/comment="([^"\r\n]*)"/', $param, $comment_match);
                                $parse_params['comment'] = html_entity_decode($comment_match[1]);
                            } elseif (strstr($param, 'class=')) {
                                // the comment feature
                                preg_match('/class="([^"\r\n]*)"/', $param, $comment_match);
                                $parse_params['class'] = html_entity_decode($comment_match[1]);
                            } else {
                                $tp = explode('=', $param);
                                $parse_params[$tp[0]] = $tp[1];
                            }    
                        }
                    }
                }

                $tpl = $this->getTpl($tpl_file, $tpl_src);
                
                if (!empty($asin)) {
                    //$content = str_replace($match, $this->parseTpl($asin, $tpl, $parse_params, $tpl_file), $content);

                    $pos = strpos($content, $match);
                    if ($pos !== false) {
                        $content = substr_replace($content, $this->parseTpl($asin, $tpl, $parse_params, $tpl_file), $pos, strlen($match));
                    }
                }
            }
        }

        // collections
        preg_match_all($this->bb_regex_collection, $content, $matches_coll);
        
        if ($matches_coll && count($matches_coll[0]) > 0) {
            
            // get defaul template file
            $tpl_src = $this->getDefaultTpl();

            for ($i=0; $i<count($matches_coll[0]); $i++) {
                
                $match         = $matches_coll[0][$i];
                $coll_label    = $matches_coll[2][$i];
                
                $tpl_file        = null;
                $params            = explode(',', strip_tags(trim($matches_coll[1][$i])));
                $params         = array_map('trim', $params);
                $coll_options   = array();

                if (!empty($params[0])) {
                    foreach ($params as $param) {
                        if (!strstr($param, '=')) {
                            $tpl_file = $param;
                        } else {
                            $tp = explode('=', $param);
                            $coll_options[$tp[0]] = $tp[1];    
                        }
                    }
                }
                
                $tpl = $this->getTpl($tpl_file, $tpl_src);
                
                if (!empty($coll_label)) {
                    
                    require_once(dirname(__FILE__) . '/AsaCollection.php');
                    $this->collection = new AsaCollection($this->db);
                    
                    $collection_id = $this->collection->getId($coll_label);

                    $coll_items = $this->collection->getItems($collection_id);
                    
                    if (count($coll_items) == 0) {
                        $content = str_replace($match, '', $content);
                    } else {
                        
                        $coll_html = '';
                        if (isset($coll_options['type']) && $coll_options['type'] == 'random' && !isset($coll_options['items'])) {
                            // only one random collection item
                            $coll_items = array($coll_items[rand(0, count($coll_items)-1)]);
                        } else if (isset($coll_options['items']) && (!isset($coll_options['type']) || $coll_options['type'] == 'latest')) {
                            // only get the defined number of the latest collection items 
                            if ((int)$coll_options['items'] !== 0) {
                                $coll_items_limit = (int)$coll_options['items'];
                            }
                        } else if (isset($coll_options['items']) && isset($coll_options['type']) && $coll_options['type'] == 'random') {
                            // only get a limited number of random items                            
                            $new_coll_items = array();
                            $items_limit = (int)$coll_options['items'];
                            if ($items_limit > count($coll_items) || $items_limit === 0) {
                                $items_limit = count($coll_items);
                            }
                            while (count($new_coll_items) < $items_limit) {

                                $rand_item_index = rand(0, count($coll_items)-1);
                                
                                if (!isset($new_coll_items[$rand_item_index])) {
                                    $new_coll_items[$rand_item_index] = $coll_items[$rand_item_index];
                                }
                            }
                            $coll_items = $new_coll_items;                            
                        }
                        
                        $coll_items_counter = 1;
                        foreach ($coll_items as $row) {
                            $coll_html .= $this->parseTpl($row->collection_item_asin, $tpl, null, $tpl_file);
                            $coll_items_counter++;
                            if (isset($coll_items_limit) && $coll_items_counter > $coll_items_limit) {
                                break;
                            }
                        }
                        $content = str_replace($match, $coll_html, $content);
                    }                    
                }                
            }
        }
        
        return $content;
    }
    
    /**
     * Retrieves default template file
     */
    public function getDefaultTpl()
    {
        $tpl_src_custom  = dirname(__FILE__) .'/tpl/default.htm';
        $tpl_src_builtin = dirname(__FILE__) .'/tpl/built-in/default.htm';
        
        if (file_exists($tpl_src_custom)) {
            $tpl_src = file_get_contents($tpl_src_custom);
        } else {
            $tpl_src = file_get_contents($tpl_src_builtin);
        }    
        return $tpl_src;
    }
    
    /**
     * Retrieves all existing template files
     */
    public function getAllTemplates()
    {
        $availableTemplates = array();

        foreach($this->getTplLocations() as $loc) {

            if (!is_dir($loc)) {
                continue;
            }
            $dirIt = new DirectoryIterator($loc);

            foreach ($dirIt as $fileinfo) {

                $filename = $fileinfo->getFilename();

                if ($fileinfo->isDir() || $fileinfo->isDot()) {
                    continue;
                }

                $filePathinfo = pathinfo($filename);

                if (!in_array($filePathinfo['extension'], $this->getTplExtensions())) {
                    continue;
                }

                array_push($availableTemplates, $filePathinfo['filename']);
            }
        }

        $availableTemplates = array_unique($availableTemplates);
        sort($availableTemplates);

        return $availableTemplates;
    }
    
    /**
     * Retrieves template file to use
     */
    public function getTpl($tpl_file, $default=false)
    {
        if (!empty($tpl_file)) {

            foreach ($this->getTplLocations() as $loc) {
                if (!is_dir($loc)) {
                    continue;
                }
                foreach ($this->getTplExtensions() as $ext) {
                    $tplPath = $loc . $tpl_file . '.' . $ext;
                    if (file_exists($tplPath)) {
                        $tpl = file_get_contents($tplPath);
                    }
                }
                if (isset($tpl)) {
                    break;
                }
            }
        }

        if (!isset($tpl)) {
            $tpl = $default;
        }

        return $tpl;
    }

    /**
     * @return mixed|void
     */
    public function getTplLocations()
    {
        $tplLocations = array(
            get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'asa' . DIRECTORY_SEPARATOR,
            dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR,
            dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'built-in' . DIRECTORY_SEPARATOR
        );
        return apply_filters('asa_tpl_locations', $tplLocations);
    }

    /**
     * @return mixed|void
     */
    public function getTplExtensions()
    {
        $tplExtensions = array('htm', 'html');
        return apply_filters('asa_tpl_extensions', $tplExtensions);
    }


    /**
     * parses the chosen template
     *
     * @param $asin
     * @param $tpl
     * @param null $parse_params
     * @param null $tpl_file
     * @internal param \amazon $string asin
     * @internal param \the $string template contents
     *
     * @return     string        the parsed template
     */
    public function parseTpl ($asin, $tpl, $parse_params=null, $tpl_file=null)
    {
        if (($this->_isAsync() && !defined('ASA_ASYNC_REQUEST') && !isset($parse_params['no_ajax'])) ||
            (!$this->_isAsync() && isset($parse_params['force_ajax']))) {
            // on AJAX request
            return $this->_getAsyncContent($asin, $tpl_file, $parse_params);
        }

        // get the item data
        $item = $this->_getItem($asin);

        if ($item instanceof AsaZend_Service_Amazon_Item) {

            $search = $this->_getTplPlaceholders($this->tpl_placeholder, true);

            $lowestOfferPrice = null;

            $tracking_id = '';

            if (!empty($this->amazon_tracking_id)) {
                // set the user's tracking id
                $tracking_id = $this->amazon_tracking_id;
            }

            // get the customer rating object
            $customerReviews = $this->getCustomerReviews($item);


//            if ($item->CustomerReviewsIFrameURL != null) {
//                require_once(dirname(__FILE__) . '/AsaCustomerReviews.php');
//                $customerReviews = new AsaCustomerReviews($item->ASIN, $item->CustomerReviewsIFrameURL, $this->cache);
//
//
//                if (strstr($averageRating, ',')) {
//                    $averageRating = str_replace(',', '.', $averageRating);
//                }
//
//            }



            if (isset($item->Offers->LowestUsedPrice) && isset($item->Offers->LowestNewPrice)) {

                $lowestOfferPrice = ($item->Offers->LowestUsedPrice < $item->Offers->LowestNewPrice) ?
                    $item->Offers->LowestUsedPrice : $item->Offers->LowestNewPrice;
                $lowestOfferCurrency = ($item->Offers->LowestUsedPrice < $item->Offers->LowestNewPrice) ?
                    $item->Offers->LowestUsedPriceCurrency : $item->Offers->LowestNewPriceCurrency;
                $lowestOfferFormattedPrice = ($item->Offers->LowestUsedPrice < $item->Offers->LowestNewPrice) ?
                    $item->Offers->LowestUsedPriceFormattedPrice : $item->Offers->LowestNewPriceFormattedPrice;

            } else if (isset($item->Offers->LowestNewPrice)) {

                $lowestOfferPrice          = $item->Offers->LowestNewPrice;
                $lowestOfferCurrency       = $item->Offers->LowestNewPriceCurrency;
                $lowestOfferFormattedPrice = $item->Offers->LowestNewPriceFormattedPrice;

            } else if (isset($item->Offers->LowestUsedPrice)) {

                $lowestOfferPrice          = $item->Offers->LowestUsedPrice;
                $lowestOfferCurrency       = $item->Offers->LowestUsedPriceCurrency;
                $lowestOfferFormattedPrice = $item->Offers->LowestUsedPriceFormattedPrice;
            }

            $lowestOfferPrice = $this->_formatPrice($lowestOfferPrice);
            $lowestNewPrice = isset($item->Offers->LowestNewPrice) ? $this->_formatPrice($item->Offers->LowestNewPrice) : '';
            $lowestNewOfferFormattedPrice = isset($item->Offers->LowestNewPriceFormattedPrice) ? $item->Offers->LowestNewPriceFormattedPrice : '';
            $lowestUsedPrice = isset($item->Offers->LowestUsedPrice) ? $this->_formatPrice($item->Offers->LowestUsedPrice) : '';
            $lowestUsedOfferFormattedPrice = isset($item->Offers->LowestUsedPriceFormattedPrice) ? $item->Offers->LowestUsedPriceFormattedPrice : '';

            $amazonPrice = $this->getAmazonPrice($item);
            $amazonPriceFormatted = $this->getAmazonPrice($item, true);

            if (isset($item->Offers->Offers[0]->Price) && !empty($item->Offers->Offers[0]->Price)) {

                if (isset($item->Offers->SalePriceAmount)) {
                    // set main price to sale price
                    $offerMainPriceAmount = $this->_formatPrice((string)$item->Offers->SalePriceAmount);
                    $offerMainPriceCurrencyCode = $item->Offers->SalePriceCurrencyCode;
                    $offerMainPriceFormatted = $item->Offers->SalePriceFormatted;
                } else {
                    $offerMainPriceAmount = $this->_formatPrice((string)$item->Offers->Offers[0]->Price);
                    $offerMainPriceCurrencyCode = (string)$item->Offers->Offers[0]->CurrencyCode;
                    $offerMainPriceFormatted = (string)$item->Offers->Offers[0]->FormattedPrice;
                }

            } else {
                // empty main price
                $emptyMainPriceText = get_option('_asa_replace_empty_main_price');
                $offerMainPriceCurrencyCode = '';
                if (!empty($emptyMainPriceText)) {
                    $offerMainPriceFormatted = $emptyMainPriceText;
                    $offerMainPriceAmount = $emptyMainPriceText;
                } else {
                    $offerMainPriceFormatted = '--';
                    $offerMainPriceAmount = '--';
                }
            }

            $listPriceFormatted = $item->ListPriceFormatted;

            $totalOffers = $item->Offers->TotalNew + $item->Offers->TotalUsed +
                $item->Offers->TotalCollectible + $item->Offers->TotalRefurbished;

            $platform = $item->Platform;
            if (is_array($platform)) {
                $platform = implode(', ', $platform);
            }

            $percentageSaved = $item->PercentageSaved;

            $no_img_url = asa_plugins_url( 'img/no_image.gif', __FILE__ );

            $replace = array(
                $item->ASIN,
                ($item->SmallImage != null) ? $item->SmallImage->Url->getUri() : $no_img_url,
                ($item->SmallImage != null) ? $item->SmallImage->Width : 60,
                ($item->SmallImage != null) ? $item->SmallImage->Height : 60,
                ($item->MediumImage != null) ? $item->MediumImage->Url->getUri() : $no_img_url,
                ($item->MediumImage != null) ? $item->MediumImage->Width : 60,
                ($item->MediumImage != null) ? $item->MediumImage->Height : 60,
                ($item->LargeImage != null) ? $item->LargeImage->Url->getUri() : $no_img_url,
                ($item->LargeImage != null) ? $item->LargeImage->Width : 60,
                ($item->LargeImage != null) ? $item->LargeImage->Height : 60,
                $item->Label,
                $item->Manufacturer,
                $item->Publisher,
                $item->Studio,
                $item->Title,
                $this->getItemUrl($item),
                empty($totalOffers) ? '0' : $totalOffers,
                empty($lowestOfferPrice) ? '---' : $lowestOfferPrice,
                isset($lowestOfferCurrency) ? $lowestOfferCurrency : '',
                isset($lowestOfferFormattedPrice) ? str_replace('$', '\$', $lowestOfferFormattedPrice) : '',
                empty($lowestNewPrice) ? '---' : $lowestNewPrice,
                str_replace('$', '\$', $lowestNewOfferFormattedPrice),
                empty($lowestUsedPrice) ? '---' : $lowestUsedPrice,
                str_replace('$', '\$', $lowestUsedOfferFormattedPrice),
                empty($amazonPrice) ? '---' : str_replace('$', '\$', $amazonPrice),
                empty($amazonPriceFormatted) ? '---' : str_replace('$', '\$', $amazonPriceFormatted),
                empty($listPriceFormatted) ? '---' : str_replace('$', '\$', $listPriceFormatted),
                isset($item->Offers->Offers[0]->CurrencyCode) ? $item->Offers->Offers[0]->CurrencyCode : '',
                isset($item->Offers->Offers[0]->Availability) ? $item->Offers->Offers[0]->Availability : '',
                asa_plugins_url( 'img/amazon_' . (empty($this->_amazon_country_code) ? 'US' : $this->_amazon_country_code) .'_small.gif', __FILE__ ),
                asa_plugins_url( 'img/amazon_' . (empty($this->_amazon_country_code) ? 'US' : $this->_amazon_country_code) .'.gif', __FILE__ ),
                $this->_handleItemUrl($item->DetailPageURL),
                $platform,
                $item->ISBN,
                $item->EAN,
                $item->NumberOfPages,
                $this->getLocalizedDate($item->ReleaseDate),
                $item->Binding,
                is_array($item->Author) ? implode(', ', $item->Author) : $item->Author,
                is_array($item->Creator) ? implode(', ', $item->Creator) : $item->Creator,
                $item->Edition,
                $customerReviews->averageRating,
                ($customerReviews->totalReviews != null) ? $customerReviews->totalReviews : 0,
                ($customerReviews->imgTag != null) ? $customerReviews->imgTag : '<img src="'. asa_plugins_url( 'img/stars-0.gif', __FILE__ ) .'" class="asa_rating_stars" />',
                ($customerReviews->imgSrc != null) ? $customerReviews->imgSrc : asa_plugins_url( 'img/stars-0.gif', __FILE__ ),
                is_array($item->Director) ? implode(', ', $item->Director) : $item->Director,
                is_array($item->Actor) ? implode(', ', $item->Actor) : $item->Actor,
                $item->RunningTime,
                is_array($item->Format) ? implode(', ', $item->Format) : $item->Format,
                !empty($parse_params['custom_rating']) ? '<img src="' . asa_plugins_url( 'img/stars-'. $parse_params['custom_rating'] .'.gif', __FILE__ ) .'" class="asa_rating_stars" />' : '',
                isset($item->EditorialReviews[0]) ? $item->EditorialReviews[0]->Content : '',
                !empty($item->EditorialReviews[1]) ? $item->EditorialReviews[1]->Content : '',
                is_array($item->Artist) ? implode(', ', $item->Artist) : $item->Artist,
                !empty($parse_params['comment']) ? $parse_params['comment'] : '',
                !empty($percentageSaved) ? $percentageSaved : 0,
                !empty($item->Offers->Offers[0]->IsEligibleForSuperSaverShipping) ? 'AmazonPrime' : '',
                !empty($item->Offers->Offers[0]->IsEligibleForSuperSaverShipping) ? '<img src="'. asa_plugins_url( 'img/amazon_prime.png', __FILE__ )  .'" class="asa_prime_pic" />' : '',
                $this->getAmazonShopUrl() . 'product-reviews/' . $item->ASIN . '/&tag=' . $this->getTrackingId(),
                $this->getTrackingId(),
                $this->getAmazonShopUrl(),
                isset($item->Offers->SalePriceAmount) ? $this->_formatPrice($item->Offers->SalePriceAmount) : '',
                isset($item->Offers->SalePriceCurrencyCode) ? $item->Offers->SalePriceCurrencyCode : '',
                isset($item->Offers->SalePriceFormatted) ? $item->Offers->SalePriceFormatted : '',
                !empty($parse_params['class']) ? $parse_params['class'] : '',
                $offerMainPriceAmount,
                $offerMainPriceCurrencyCode,
                $offerMainPriceFormatted,
            );

            $result = preg_replace($search, $replace, $tpl);

            // check for unresolved
            preg_match_all('/\{\$([a-z0-9\-\>]*)\}/i', $result, $matches);
            
            $unresolved = $matches[1];
            
            if (count($unresolved) > 0) {

                $unresolved_names        = $matches[1];
                $unresolved_placeholders = $matches[0];
                
                $unresolved_search  = array();
                $unresolved_replace = array();
                
                
                for ($i=0; $i<count($unresolved_names);$i++) {

                    if (isset($unresolved_names[$i]) && property_exists($item, $unresolved_names[$i])) {
                        $value = $item->{$unresolved_names[$i]};
                    } else {
                        $value = '';
                    }

                    if (strstr($value, '$')) {
                        $value = str_replace('$', '\$', $value);
                    }
                    
                    $unresolved_search[]  = $this->TplPlaceholderToRegex($unresolved_placeholders[$i]);
                    $unresolved_replace[] = $value;                    
                }
                if (count($unresolved_search) > 0) {
                    $result = preg_replace($unresolved_search, $unresolved_replace, $result);
                }
            }
            return $result;

        } elseif ($this->isErrorHandling() && $item instanceof Asa_Service_Amazon_Error &&
            get_option('_asa_admin_error_frontend') && is_super_admin()) {

            // show admin error
            $errors = $item->getErrors();
            $error = array_shift($errors);

            // load error_admin.htm
            $search = $this->_getTplPlaceholders(array('Error', 'Message', 'ASIN'), true);
            $replace = array($error['Code'], $error['Message'], $error['ASIN']);
            $output = preg_replace($search, $replace, $this->getTpl('error_admin'));

            echo $output;

        } elseif ($item instanceof Asa_Service_Amazon_Error && get_option('_asa_use_error_tpl')) {

            $errors = $item->getErrors();
            $error = array_shift($errors);

            // load error.htm
            $search = $this->_getTplPlaceholders(array('Error', 'Message', 'ASIN'), true);
            $replace = array($error['Code'], $error['Message'], $error['ASIN']);
            $output = preg_replace($search, $replace, $this->getTpl('error'));

            echo $output;

        } elseif ($item === null && $this->isErrorHandling()) {

            // general error
            $message = __('Error while loading product data.', 'asa1');
            $search = $this->_getTplPlaceholders(array('Error', 'Message', 'ASIN'), true);
            $replace = array('General error', $message, $asin);

            if (get_option('_asa_admin_error_frontend') && is_super_admin()) {
                $output = preg_replace($search, $replace, $this->getTpl('error_admin'));
            } else {
                $output = preg_replace($search, $replace, $this->getTpl('error'));
            }

            echo $output;

        } else {

            return '';
        }
    }
    
    /**
     * get item information from amazon webservice or cache
     * 
     * @param string ASIN
     * @return object AsaZend_Service_Amazon_Item object
     */    
    protected function _getItem ($asin)
    {
        try {
            if ($this->cache == null || $this->_useCache() === false) {
                // if cache could not be initialized
                $item = $this->_getItemLookup($asin);

            } else {

                // try to load item from cache
                $item = $this->cache->load($asin);

                if ($item === false || !($item instanceof AsaZend_Service_Amazon_Item)) {
                    // item could not be loaded from cache or is not an item object
                    //asa_debug('could not load from cache: ' . $asin);

                    $item = $this->_getItemLookup($asin);

                    if (!($item instanceof Asa_Service_Amazon_Error)) {
                        // put asin in cache if it is not an error response
                        $this->cache->save($item, $asin);
                    }

                } else {
                    // asin could be loaded from cache

                    // debug
                    //asa_debug('loaded from cache: ' . $asin);
                    //asa_debug($item);
                }

            }

            return $item;
            
        } catch (Exception $e) {

            if ($this->isErrorHandling()) {

                $message = "Error while trying to load item data: %s\n\nASIN: %s";
                $error = array();
                $error['Message'] = sprintf($message, $e->getMessage(), $asin);
                $error['ASIN'] = $asin;
                $error['Code'] = 'General error';

                $this->getLogger()->logError($error);
            }

            return null;
        }
    }

    /**
     * Public alias for self::_getItem($asin)
     *
     * @param $asin
     * @return object
     */
    public function getItemObject($asin)
    {
        return $this->_getItem($asin);
    }
    
    /**
     * get item information from amazon webservice
     * 
     * @param       string      ASIN
     * @return      object      AsaZend_Service_Amazon_Item object
     */     
    protected function _getItemLookup ($asin)
    {
        $result = $this->amazon->itemLookup($asin, array(
                    'ResponseGroup' => 'ItemAttributes,Images,Offers,OfferListings,Reviews,EditorialReview,Tracks'));

        if ($result instanceof Asa_Service_Amazon_Error) {
            // handle errors
            if ($this->isErrorHandling()) {
                $this->getLogger()->logError($result);
            }
        }

        return $result;
    }
            
    
    /**
     * gets options from database options table
     */
    protected function _getAmazonUserData ()
    {
        $this->_amazon_api_key = get_option('_asa_amazon_api_key');
        $this->_amazon_api_secret_key = base64_decode(get_option('_asa_amazon_api_secret_key'));
        $this->amazon_tracking_id = get_option('_asa_amazon_tracking_id');
        $this->_amazon_api_connection_type = ifw_filter_scalar(get_option('_asa_api_connection_type'), array('http', 'https'), 'http');

        $amazon_country_code = get_option('_asa_amazon_country_code');
        if (!empty($amazon_country_code)) {
            $this->_amazon_country_code = $amazon_country_code;
        }
    }

    /**
     * Loads options
     */
    protected function _loadOptions()
    {
        $_asa_product_preview = get_option('_asa_product_preview');
        if (empty($_asa_product_preview)) {
            $this->_product_preview = false;
        } else {
            $this->_product_preview = true;
        }

        $_asa_parse_comments = get_option('_asa_parse_comments');
        if (empty($_asa_parse_comments)) {
            $this->_parse_comments = false;
        } else {
            $this->_parse_comments = true;
        }

        $_asa_async_load = get_option('_asa_async_load');
        if (empty($_asa_async_load)) {
            $this->_async_load = false;
        } else {
            $this->_async_load = true;
        }

        $_asa_use_amazon_price_only = get_option('_asa_use_amazon_price_only');
        if (empty($_asa_use_amazon_price_only)) {
            $this->_asa_use_amazon_price_only = false;
        } else {
            $this->_asa_use_amazon_price_only = true;
        }
    }

    /**
     * generates right placeholder format and returns them as array
     * optionally prepared for use as regex
     *
     * @param         bool        true for regex prepared
     * @return array
     */
    protected function _getTplPlaceholders ($placeholders, $regex=false)
    {
        $result = array();
        foreach ($placeholders as $ph) {
            $result[] = $this->tpl_prefix . $ph . $this->tpl_postfix;
        }
        if ($regex == true) {
            return array_map(array($this, 'TplPlaceholderToRegex'), $result);
        }
        return $result;
    }
    
    /**
     * excapes placeholder for regex usage
     * 
     * @param         string        placehoder
     * @return         string        escaped placeholder
     */
    public function TplPlaceholderToRegex ($ph)
    {
        $search = array(
            '{',
            '}',
            '$',
            '-',
            '>'
        );
        
        $replace = array(
            '\{',
            '\}',
            '\$',
            '\-',
            '\>'
        );
        
        $ph = str_replace($search, $replace, $ph);
        
        return '/'. $ph .'/';
    }
    
    /**
     * formats the price value from amazon webservice
     * 
     * @param         string        price
     * @return         mixed        price (float, int for JP)
     */
    protected function _formatPrice ($price)
    {
        if ($price === null || empty($price)) {
            return $price;
        }
        
        if ($this->_amazon_country_code != 'JP') {
            $price = (float) substr_replace($price, '.', (strlen($price)-2), -2);
        } else {
            $price = intval($price);
        }    
        
        $dec_point         = '.';
        $thousands_sep     = ',';
        
        if ($this->_amazon_country_code == 'DE' ||
            $this->_amazon_country_code == 'FR') {
            // taken the amazon websites as example
            $dec_point         = ',';
            $thousands_sep     = '.';
        }
        
        if ($this->_amazon_country_code != 'JP') {
            $price = number_format($price, 2, $dec_point, $thousands_sep);
        } else {
            $price = number_format($price, 0, $dec_point, $thousands_sep);
        }
        return $price;
    }
    
    /**
     * includes the css file for admin page
     */
    public function getOptionsHead ()
    {
        echo '<link rel="stylesheet" type="text/css" media="screen" href="' . asa_plugins_url( 'css/options.css?v='. self::VERSION , __FILE__ ) .'" />';
        //echo '<script type="text/javascript" src="' . asa_plugins_url( 'js/asa.js?v='. self::VERSION , __FILE__ ) .'"></script>';
    }
    
    /**
     * Adds the meta link
     */
    public function addMetaLink() 
    {
        echo '<li>Powered by <a href="http://www.wp-amazon-plugin.com/" target="_blank" title="Open AmazonSimpleAdmin homepage">AmazonSimpleAdmin</a></li>';
    }    
    
    /**
     * enabled amazon product preview layers
     */
    public function addProductPreview ()
    {
        $js = '<script type="text/javascript" src="http://www.assoc-amazon.[domain]/s/link-enhancer?tag=[tag]&o=[o_id]"></script>';
        $js .= '<noscript><img src="http://www.assoc-amazon.[domain]/s/noscript?tag=[tag]" alt="" /></noscript>';
        
        $search = array(
            '/\[domain\]/',
            '/\[tag\]/',
            '/\[o_id\]/',
        );        
        
        switch ($this->_amazon_country_code) {
            
            case 'DE':
                $replace = array(
                    'de',
                    (!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : ''),
                    '3'
                );                
                $js = preg_replace($search, $replace, $js);
                break;
                
            case 'UK':
                $replace = array(
                    'co.uk',
                    (!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : ''),
                    '2'
                );                
                $js = preg_replace($search, $replace, $js);
                break;
                
            case 'US':
            case false:
                $replace = array(
                    'com',
                    (!empty($this->amazon_tracking_id) ? $this->amazon_tracking_id : ''),
                    '1'
                );
                
                $js = preg_replace($search, $replace, $js);
                break;

            default:
                $js = '';
        }
        
        echo $js . "\n";    
    }

    /**
     * 
     */
    public function getCollection ($label, $type=false, $tpl=false)
    {    
        $collection_html = '';
        
        $sql = '
            SELECT a.collection_item_asin as asin
            FROM `'. $this->db->prefix . self::DB_COLL_ITEM .'` a
            INNER JOIN `'. $this->db->prefix . self::DB_COLL .'` b USING(collection_id)
            WHERE b.collection_label = "'. $this->db->escape($label) .'"
            ORDER by a.collection_item_timestamp DESC
        ';
        
        $result = $this->db->get_results($sql);
        
        if (count($result) == 0) {
            return $collection_html;    
        }
        
        if ($tpl == false) {
            $tpl = 'collection_sidebar_default';    
        }
        if ($type == false) {
            $type = 'all';    
        }

        $tpl_src = $this->getTpl($tpl);
        
        switch ($type) {
            
            case 'latest':
                $collection_html .= $this->parseTpl($result[0]->asin, $tpl_src, null, $tpl);
                break;
            
            case 'all':
            default:
                foreach ($result as $row) {
                    $collection_html .= $this->parseTpl($row->asin, $tpl_src, null, $tpl);
                }
        }
        
        return $collection_html;
    }
    
    /**
     * 
     */
    public function getItem ($asin, $tpl=false)
    {   
        $item_html = '';
        
        if ($tpl == false) {
            $tpl = 'sidebar_item';
        }

        $tpl_src = $this->getTpl($tpl);
        
        $item_html .= $this->parseTpl(trim($asin), $tpl_src, null, $tpl);
        
        return $item_html;
    }

    /**
     * @return bool
     */
    protected function _isAsync()
    {
        return $this->_async_load === true;
    }

    /**
     * @param $asin
     * @param $tpl
     * @param $parse_params
     * @param $match
     * @return string
     */
    protected function _getAsyncContent($asin, $tpl, $parse_params)
    {
        $containerID = 'asa-' . md5(uniqid(mt_rand()));

        if ($this->getLogger()->isBlock()) {
            $parse_params['asa-block-errorlog'] = true;
        }
        $params = str_replace("'", "\'", json_encode($parse_params));
        $nonce = wp_create_nonce('amazonsimpleadmin');
        $site_url = site_url();
        if (defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE == true) {
            $site_url = network_site_url();
        }
        if (empty($tpl)) {
            $tpl = 'default';
        }

        $output = '<div id="'. $containerID .'" class="asa_async_container asa_async_container_'. $tpl .'"></div>';
        $output .= "<script type='text/javascript'>jQuery(document).ready(function($){var data={action:'asa_async_load',asin:'$asin',tpl:'$tpl',params:'$params',nonce:'$nonce'};if(typeof ajaxurl=='undefined'){var ajaxurl='$site_url/wp-admin/admin-ajax.php'}$.post(ajaxurl,data,function(response){jQuery('#$containerID').html(response)})});</script>";
        return $output;
    }

    /**
     * @param $item
     * @param bool $formatted
     * @return mixed|null
     */
    public function getAmazonPrice($item, $formatted=false)
    {
        $result = null;

        if (isset($item->Offers->SalePriceAmount) && $item->Offers->SalePriceAmount != null) {
            if ($formatted === false) {
                $result = $this->_formatPrice($item->Offers->SalePriceAmount);
            } else {
                $result = $item->Offers->SalePriceFormatted;
            }
        } elseif (isset($item->Offers->Offers[0]->Price) && $item->Offers->Offers[0]->Price != null) {
            if ($formatted === false) {
                $result = $this->_formatPrice($item->Offers->Offers[0]->Price);
            } else {
                $result = $item->Offers->Offers[0]->FormattedPrice;
            }
        } elseif (isset($item->Offers->LowestNewPrice) && !empty($item->Offers->LowestNewPrice)) {
            if ($formatted === false) {
                $result = $this->_formatPrice($item->Offers->LowestNewPrice);
            } else {
                $result = $item->Offers->LowestNewPriceFormattedPrice;
            }
        } elseif (isset($item->Offers->LowestUsedPrice) && !empty($item->Offers->LowestUsedPrice)) {
            if ($formatted === false) {
                $result = $this->_formatPrice($item->Offers->LowestUsedPrice);
            } else {
                $result = $item->Offers->LowestUsedPriceFormattedPrice;
            }
        }

        return $result;
    }

    /**
     * Retrieve the customer reviews object
     *
     * @param $item
     * @param bool $uncached
     * @return AsaCustomerReviews|null
     */
    public function getCustomerReviews($item, $uncached = false)
    {
        require_once(dirname(__FILE__) . '/AsaCustomerReviews.php');

        $iframeUrl = ($item->CustomerReviewsIFrameURL != null) ? $item->CustomerReviewsIFrameURL : '';

        if ($uncached) {
            $cache = null;
        } else {
            $cache = $this->cache;
        }

        $reviews = new AsaCustomerReviews($item->ASIN, $iframeUrl, $cache);
        if (get_option('_asa_get_rating_alternative')) {
            $reviews->setFindMethod(AsaCustomerReviews::FIND_METHOD_DOM);
        }
        $reviews->load();
        return $reviews;
    }

    /**
     * @param $item
     * @return string
     */
    public function getItemUrl($item)
    {
        if (get_option('_asa_use_short_amazon_links')) {
            $url = sprintf($this->amazon_url[$this->_amazon_country_code],
                $item->ASIN, $this->amazon_tracking_id);
        } else {
            $url = $item->DetailPageURL;
        }

        return $this->_handleItemUrl($url);
    }

    /**
     * @param $url
     * @return string
     */
    protected function _handleItemUrl($url)
    {
        $url = urldecode($url);

        $url = strtr($url, array(
            '%' => '%25'
        ));

        return $url;
    }

    /**
     * @param $date
     * @return bool|string
     */
    public function getLocalizedDate($date)
    {
        if (!empty($date)) {
            $dt = new DateTime($date);

            $format = get_option('date_format');

            $date = date($format, $dt->format('U'));
        }

        return $date;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->_amazon_country_code;
    }

    /**
     * @return mixed
     */
    public function getAmazonShopUrl()
    {
        if ($this->amazon_shop_url == null) {
            $url = $this->amazon_url[$this->getCountryCode()];
            $this->amazon_shop_url = current(explode('exec', $url));
        }
        return $this->amazon_shop_url;
    }

    /**
     * @return mixed
     */
    public function getTrackingId()
    {
        return $this->amazon_tracking_id;
    }

    /**
     * @return bool
     */
    protected function _useCache()
    {
        if ((int)get_option('_asa_cache_skip_on_admin') === 1 && current_user_can('install_plugins')) {
            return false;
        }
        return true;
    }

    /**
     * @return AsaLogger
     */
    public function getLogger()
    {
        require_once dirname(__FILE__) . '/AsaLogger.php';
        return AsaLogger::getInstance($this->db);
    }

    /**
     * @param $plugin_data
     * @param $meta_data
     */
    public function handleUpdateMessage($plugin_data, $meta_data)
    {
        printf('<div style="border: 1px dashed #C9381A; padding: 4px; margin-top: 5px;"><span class="dashicons dashicons-info" style="color: #C9381A;"></span> %s <a href="http://www.wp-amazon-plugin.com/2015/13280/keeping-your-custom-templates-update-safe/" target="_blank">%s</a>.</div>',
            __('Remember to <b>backup your custom template files</b> before updating!', 'asa1'),
            __('Read more', 'asa1')
        );
    }

}

global $wpdb;
$asa = new AmazonSimpleAdmin($wpdb);

include_once ASA_INCLUDE_DIR . 'asa_php_functions.php';
include_once ASA_INCLUDE_DIR . 'asa_ajax_callback.php';
include_once ASA_INCLUDE_DIR . 'asa_actions.php';
