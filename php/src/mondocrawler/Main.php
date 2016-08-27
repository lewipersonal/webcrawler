<?php

namespace mondocrawler;

require 'WebCrawler.php';
require 'Page.php';
/**
 * Entry point to parse the command line and kick things off
 */
class WebCrawlerCli
{
	public function runcli($args)
	{
		$options = getopt('h');

        if (isset($options['h'])) {
            $this->help();

            return;
        }

        $wc = new WebCrawler($args);
        $sitemap = $wc->crawl();

        var_dump(array_keys($sitemap));

        var_dump(array_map(function ($page) {return $page->getLinks();}, $sitemap));
        var_dump(array_map(function ($page) {return $page->getStaticFiles();}, $sitemap));
	}

    public function help()
    {
        echo "Crawls a webpage and produces a sitemap.\n";
        echo "Usage:\tphp main.php [target] [-h]\n";
        echo "\t\ttarget\tis the url to start crawling from.\n";
        echo "\t\t-h\tDisplays this help.\n";
    }
}

$wcc = new WebCrawlerCli();
$wcc->runcli($argv[1]);
