<?php
/**
 * @param string $path
 * @param string $plugin
 * @return string
 */
function asa_plugins_url($path = '', $plugin = '') {
    if (getenv('ASA_APPLICATION_ENV') == 'development') {
        return get_bloginfo('wpurl') . '/wp-content/plugins/amazonsimpleadmin/' . $path;
    }
    return plugins_url($path, $plugin);
}

/**
 * @return bool
 */
function is_asa_admin_page() {
    return is_admin() && isset($_GET['page']) && $_GET['page'] == 'amazonsimpleadmin/amazonsimpleadmin.php';
}

/**
 * Retrieves the ASA news feed items
 * @return string
 */
function asa_get_feed_items() {
    $rss = fetch_feed('http://www.wp-amazon-plugin.com/feed/');
    $result = array();

    if ($rss instanceof SimplePie) {

        foreach ($rss->get_items(0, 3) as $item) {

            $item_tmp = array();

            $item_tmp['url'] = esc_url($item->get_link());

            $title = esc_attr($item->get_title());

            if (empty($title)) {
                $title = __('Untitled');
            }
            $item_tmp['title'] = $title;

            $desc = str_replace(array("\n", "\r"), ' ', esc_attr(strip_tags(@html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset')))));
            $desc = wp_html_excerpt($desc, 360);

            if (strstr($desc, 'Continue reading →')) {
                $desc = str_replace('Continue reading →', '<a href="'. $item_tmp['url'] .'" target="_blank">Continue reading →</a>', $desc);
            }
            $item_tmp['desc'] = $desc;

            $date = $item->get_date();
            $diff = '';

            if ($date) {

                $diff = human_time_diff(strtotime($date, time()));
                $date_stamp = strtotime($date);
                if ($date_stamp) {
                    $date = '<span class="rss-date">' . date_i18n(get_option('date_format'), $date_stamp) . '</span>';
                } else {
                    $date = '';
                }
            }

            $item_tmp['date'] = $date;
            $item_tmp['diff'] = $diff;

            $result[] = $item_tmp;
        }
    }

    $output = '';
    if (count($result) > 0) {
        $output .= '<div id="asa_feed_box_inner">';
        $output .= '<h3>' . __('ASA News', 'asa1') . '</h3>';
        $output .= '<ul>';
        foreach ($result as $item) {
            $output .= '<li>';
            $date = !empty($item['data']) ? $item['data'] : '';
            $output .= sprintf('<a class="rsswidget" title="" href="%s" target="_blank">%s</a><span class="rss-date">%s</span>',
                $item['url'], $item['title'], $date
            );
            $output .= sprintf('<div class="rssSummary"><strong>%s</strong> - %s</div>',
                $item['diff'], $item['desc']
            );
            $output .= '</li>';
        }
        $output .= '</ul>';
    }
    $output .= '</div>';

    return $output;
}

/**
 * @param $var
 * @return bool
 */
function asa_debug($var) {
    $debugFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'asadebug.txt';

    if (!is_writable($debugFile)) {
        return false;
    }

    $bt = debug_backtrace();
    $info = pathinfo($bt[0]['file']);

    $output = 'File: '. $info['basename'] . PHP_EOL .
        'Line: '. $bt[0]['line'] . PHP_EOL .
        'Time: '. date('Y/m/d H:i:s') . PHP_EOL .
        'Type: '. gettype($var) . PHP_EOL . PHP_EOL;

    if (is_array($var) || is_bool($var) || is_object($var)) {
        $output .= var_export($var, true);
    } else {
        $output .= $var;
    }

    $output .=
        PHP_EOL . PHP_EOL .
        '-------------------------------------------------------'.
        PHP_EOL . PHP_EOL;

    file_put_contents($debugFile, $output, FILE_APPEND);
}

