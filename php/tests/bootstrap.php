<?php

/**
 * Configure include paths used by the unit tests.
 *
 * @return void
 */
function configure_include_paths()
{
    set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . "/../src");
}

configure_include_paths();

require 'mondocrawler/Page.php';
require 'mondocrawler/WebCrawler.php';