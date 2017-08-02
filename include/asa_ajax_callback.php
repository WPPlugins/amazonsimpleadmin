<?php
// add the ajax actions
add_action('wp_ajax_asa_async_load', 'asa_async_load_callback');
add_action('wp_ajax_nopriv_asa_async_load', 'asa_async_load_callback');

/**
 * Load asynchronous
 */
function asa_async_load_callback() {
    global $asa;
    check_ajax_referer('amazonsimpleadmin', 'nonce');
    define('ASA_ASYNC_REQUEST', 1);

    $asin = esc_attr($_POST['asin']);
    $tpl = $asa->getTpl(esc_attr($_POST['tpl']), $asa->getDefaultTpl());

    $params = $_POST['params'];

    $params = json_decode(stripcslashes($params), true);
    if (is_array($params)) {
        $params = array_map('strip_tags', $params);
    }
    // debug
    //echo '<pre>' . print_r($_POST) . '</pre>';

    if (isset($params['asa-block-errorlog'])) {
        $asa->getLogger()->setBlock(true);
    }

    echo $asa->parseTpl($asin, $tpl, $params);
    exit;
}

