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

        $this->writeToFile($sitemap);
	}

    public function help()
    {
        echo "Crawls a webpage and produces a sitemap.\n";
        echo "Usage:\tphp main.php [target] [-h]\n";
        echo "\t\ttarget\tis the url to start crawling from.\n";
        echo "\t\t-h\tDisplays this help.\n";
    }

    protected function writeToFile($sitemap)
    {
        $file = fopen("output.txt","w");
        foreach ($sitemap as $url => $page) {
            fwrite($file, $url."\n");
            if ($page->getLinks()) {
                fwrite($file, sprintf("\tLinks (%d):", count($page->getLinks()))."\n");
                foreach ($page->getLinks() as $link) {
                    fwrite($file, "\t\t".$link."\n");
                }
            } else {
                fwrite($file, "\tLinks (0):"."\n");
            }

            if ($page->getStaticFiles()) {
                fwrite($file, sprintf("\tStatic Files (%d):", count($page->getStaticFiles()))."\n");
                foreach ($page->getStaticFiles() as $staticFile) {
                    fwrite($file, "\t\t".$staticFile."\n");
                }
            } else {
                fwrite($file, "\tStatic Files (0):"."\n");
            }
        }
        fclose($file);
    }
}

$wcc = new WebCrawlerCli();
$wcc->runcli($argv[1]);
