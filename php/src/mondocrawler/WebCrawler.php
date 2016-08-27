<?php

namespace mondocrawler;

/**
 * Basic strategy here is to do a breadth-first crawl, storing the sitemap as
 * an array [url => Page] where each page is indexed by its url
 */
class WebCrawler
{
	protected $target;
	protected $parsedTarget;
	protected $sitemap;
	protected $next;

	function __construct($url)
	{
		$this->sitemap = [];
		$this->next = new \SplQueue();
		$this->target = $url;
		$this->parsedTarget = parse_url($this->target);
		if (!isset($this->parsedTarget['host'])) {
			$this->getTargetData();
		}
	}

	public function crawl()
	{

		$this->next->enqueue($this->target);

		while ($this->next->count() > 0)
		{
			$currentUrl = $this->next->dequeue();

			if ($this->isVisited($currentUrl) || !$this->inSameDomain($currentUrl)) {
				continue;
			}

			$page = $this->getPageAndProcess($currentUrl);

			if ($page->getRedirect()) {
				$this->enqueueIfAllowed($page->getRedirect());
			} elseif ($page->getLinks()) {
				foreach ($page->getLinks() as $link) {
					$this->enqueueIfAllowed($link);
				}
			}

			$this->recordPageAndMarkAsVisited($currentUrl, $page);
		}

		return $this->sitemap;
	}

	/**
	 * We're trying to take absolute and relative urls and return them as absolute
	 * using $this->target as a base
	 */
	public function transformUrl($url)
	{
		$fixedStart = (strpos($url, '/') === 0 ? $this->target : '').$url;

		$trimmedAnchor = strpos($fixedStart, '#') === false ? $fixedStart : substr($fixedStart, 0, strpos($fixedStart, '#'));

		return $trimmedAnchor;
	}

	/**
	 * Parses a page, and tries to find any in the same domain as $this->target
	 * @param string $url
	 * @return Array
	 */
	protected function getPageAndProcess($url)
	{
		$page = $this->getPage($url);

		// Check failure
		if ($page->getHtml() === false) {
			return $page;
		}

		// Check redirects
		if ($page->getHttpCode() >= 300 && $page->getHttpCode() < 400) {
			return $page;
		}

		$page->setDomFromHtml();
		$page->processDom();

		return $page;
	}

	protected function getPage($url)
	{
		$page = new Page();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		$result = curl_exec($ch);
		$responseInfo = curl_getinfo($ch);
        curl_close($ch);

        $page->setResponseInfo($responseInfo);
        $page->setHtml($result);

        return $page;
	}

	protected function inSameDomain($link)
	{
		$parsedLink = parse_url($link);
		if (!isset($parsedLink['host']) || $parsedLink['host'] == $this->parsedTarget['host']){
			return true;
		}

		return false;
	}

	protected function isVisited($link)
	{
		return isset($this->sitemap[$link]);
	}

	protected function recordPageAndMarkAsVisited($url, $page)
	{
		$this->sitemap[$url] = $page;
	}

	protected function enqueueIfAllowed($url)
	{
		$url = $this->transformUrl($url);
		if (!$this->isVisited($url) && $this->inSameDomain($url)) {
			$this->next->enqueue($url);
		}
	}

	protected function getTargetData()
	{
		$page = $this->getPageAndProcess($this->target);
		$this->parsedTarget = parse_url($page->getResponseInfo()['url']);
		$this->target = strtolower($this->parsedTarget['scheme']).'://'.$this->parsedTarget['host'];
	}
}
