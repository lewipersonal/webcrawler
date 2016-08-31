<?php

namespace mondocrawler;

/**
 * Each Page object represents the information we are interested in about a
 * single web page
 */
class Page
{
	protected $url;
	protected $responseInfo;
	protected $links;
	protected $staticFiles;
	protected $html;
	protected $dom;

	public function getUrl()
	{
		return $this->url;
	}
	public function setUrl($var)
	{
		$this->url = $var;
		return $this;
	}

	public function getResponseInfo()
	{
		return $this->responseInfo;
	}
	public function setResponseInfo($var)
	{
		$this->responseInfo = $var;
		return $this;
	}

	public function getHttpCode()
	{
		return $this->responseInfo['http_code'];
	}
	public function getRedirect()
	{
		return $this->responseInfo['redirect_url'];
	}

	public function getLinks()
	{
		return $this->links;
	}
	public function setLinks($var)
	{
		$this->links = $var;
		return $this;
	}

	public function getStaticFiles()
	{
		return $this->staticFiles;
	}
	public function setStaticFiles($var)
	{
		$this->staticFiles = $var;
		return $this;
	}

	public function getHtml()
	{
		return $this->html;
	}
	public function setHtml($var)
	{
		$this->html = $var;
		return $this;
	}

	public function setDomFromHtml()
	{
		libxml_use_internal_errors(true);
		$this->dom = new \DOMDocument(); 
		$success = $this->dom->loadHTML($this->getHtml());
		libxml_use_internal_errors(false);

		return $success;
	}

	public function processDom()
	{
		$this->setLinksFromDom();
		$this->setStaticFilesFromDom();
	}

	protected function setLinksFromDom()
	{
		$links = $this->getLinksFromDom($this->dom);
		$this->setLinks($links);

		return $this;
	}

	protected function setStaticFilesFromDom()
	{
		$images = $this->getImagesFromDom($this->dom);
		$scripts = $this->getScriptsFromDom($this->dom);
		$styles = $this->getStylesFromDom($this->dom);

		$this->setStaticFiles(array_merge($images, $scripts, $styles));

		return $this;
	}

	protected function getLinksFromDom($dom)
	{
		$links = [];
		foreach ($this->dom->getElementsByTagName('a') as $link) {
			if ($link->getAttribute('href') && strpos($link->getAttribute('href'), '#') !== 0) {
				$links[] = $link->getAttribute('href');
			}
		}

		return $links;
	}

	protected function getImagesFromDom($dom)
	{
		$images = [];
		foreach ($dom->getElementsByTagName('img') as $image) {
			$images[] = $image->getAttribute('src');
		}

		return $images;
	}

	protected function getScriptsFromDom($dom)
	{
		$scripts = [];
		foreach ($dom->getElementsByTagName('script') as $script) {
			if ($script->getAttribute('src')) {
				$scripts[] = $script->getAttribute('src');
			}
		}

		return $scripts;
	}

	protected function getStylesFromDom($dom)
	{
		$styles = [];
		foreach ($dom->getElementsByTagName('link') as $style) {
			if ($style->getAttribute('rel') == 'stylesheet' && $style->getAttribute('href')) {
				$styles[] = $style->getAttribute('href');
			}
		}

		return $styles;
	}
}