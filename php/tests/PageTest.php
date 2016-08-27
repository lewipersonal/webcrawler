<?php

class PageTest extends PHPUnit_Framework_TestCase
{
    public function testSetDomFromHtml()
    {
        $page = new mondocrawler\Page();
        $page->setHtml(file_get_contents('testpage.html', true));

        $return = $page->setDomFromHtml();

        $this->assertTrue($return);
    }

    public function testGetLinks()
    {
        $page = new mondocrawler\Page();
        $page->setHtml(file_get_contents('testpage.html', true));

        $page->setDomFromHtml();

        $page->processDom();

        $expectedResult = ["/",
                           "/about",
                           "/archive",
                           "/random",
                           "http://tomblomfield.com/rss",
                           "http://t.umblr.com/redirect?z=https%3A%2F%2Fgetmondo.co.uk&t=ZWVmMDQyNDFhOTU1OWQyMmIwYmJkMDVjZDk1NDU3MjZjNDM0MjliYixUbU4zdWpESg%3D%3D",
                           "http://t.umblr.com/redirect?z=https%3A%2F%2Fgocardless.com&t=NDY4MTNjYWRjMGJmZWE5N2QzNzA3NDQyMDdkZWE3Y2Y4OGNhZjFlYyxUbU4zdWpESg%3D%3D",
                           "http://t.umblr.com/redirect?z=https%3A%2F%2Fgithub.com%2Ftomblomfield&t=NDZiOWYxZDc0Yjc5NTJmMTg1NmI5ODQ0YzM2Y2M1YTRmMGI1MjM1ZSxUbU4zdWpESg%3D%3D",
                           "http://tomblomfield.com",
                           "https://twitter.com/t_blom",
                           "http://tomblomfield.disqus.com/?url=ref",
                           "http://www.tumblr.com/",
                           ];

        $this->assertEquals($expectedResult, $page->getLinks());
    }

    public function testGetStaticContent()
    {
        $page = new mondocrawler\Page();
        $page->setHtml(file_get_contents('testpage.html', true));

        $page->setDomFromHtml();

        $page->processDom();

        $expectedResult = ['http://www.gravatar.com/avatar/c833be5582482777b51b8fc73e8b0586?s=128&d=identicon&r=PG',
                           'https://px.srvcs.tumblr.com/impixu?T=1472146726&J=eyJ0eXBlIjoidXJsIiwidXJsIjoiaHR0cDpcL1wvdG9tYmxvbWZpZWxkLmNvbVwvYWJvdXQiLCJyZXF0eXBlIjowLCJyb3V0ZSI6ImN1c3RvbV9wYWdlIiwibm9zY3JpcHQiOjF9&U=AJCKLLCJKD&K=35f6feec5b553e16bd79c8afbb0c0c1f5874d6b8736f1385ba4f619d6977bb65&R=',
                           'http://disqus.com/forums/tomblomfield/embed.js',
                           'http://requirejs.org/docs/release/1.0.3/minified/require.js',
                           'http://assets.tumblr.com/client/prod/standalone/tumblelog/index.build.js?_v=b754240effd321494a4cb840633c5e94',
                           ];

        $this->assertEquals($expectedResult, $page->getStaticFiles());
    }

    public function testTransformUrls()
    {
        $webcrawler = new mondocrawler\WebCrawler('tomblomfield.com');
        $input = ["/",
                  "/about",
                  "/archive",
                  "/random",
                  "http://tomblomfield.com/rss",
                  "http://tomblomfield.com?query=value",
                  "http://tomblomfield.com?query=value#anchor",
                  ];
        $output = ["http://tomblomfield.com/",
                   "http://tomblomfield.com/about",
                   "http://tomblomfield.com/archive",
                   "http://tomblomfield.com/random",
                   "http://tomblomfield.com/rss",
                   "http://tomblomfield.com?query=value",
                   "http://tomblomfield.com?query=value",
                   ];

        $this->assertEquals($output, array_map([$webcrawler, 'transformUrl'], $input));
    }
}