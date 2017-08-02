<?php

/**
 * displays a collection
 */
function asa_collection ($label, $type=false, $tpl=false)
{
    global $asa;
    echo $asa->getCollection($label, $type, $tpl);
}

/**
 * returns the rendered collection
 */
function asa_get_collection ($label, $type=false, $tpl=false)
{
    global $asa;
    return $asa->getCollection($label, $type, $tpl);
}

/**
 * displays one item, can be used everywhere in php code, eg sidebar
 */
function asa_item ($asin, $tpl=false)
{
    global $asa;
    echo $asa->getItem($asin, $tpl);
}

/**
 * return the rendered product template
 * @param string $asin
 * @param string $tpl
 */
function asa_get_item($asin, $tpl=false)
{
    global $asa;
    return $asa->getItem($asin, $tpl);
}

/**
 * shortcode handler for [asa] tags
 * @param array $atts
 * @param string $content
 * @param string $code
 * @return string
 */
function asa_shortcode_handler($atts, $content=null, $code="")
{
    $tpl = false;
    if (!empty($atts[0])) {
        $tpl = $atts[0];
    }
    return asa_get_item($content, $tpl);
}
