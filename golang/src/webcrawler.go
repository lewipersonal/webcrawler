package main

import (
    "bytes"
    "fmt"
    "flag"
    "os"
    "net/http"
    "strings"
    "strconv"
    "sync"

    "golang.org/x/net/html"
)

var client http.Client
var initialSite string
var wg sync.WaitGroup
var sitemap Sitemap

// Crawl uses fetcher to recursively crawl
// pages starting with url, to a maximum of depth.
func Crawl(url string, depth int, c chan *Page) {
    // TODO: Fetch URLs in parallel.
    // TODO: Don't fetch the same URL twice.
    // This implementation doesn't do either:
    url, err := Clean(url)
    if err != nil {
        return
    }

    if depth <= 0 {
        return
    }

    sitemap.RLock()
    _, ok := sitemap.m[url]
    sitemap.RUnlock()
    if ok {
        return
    }

    // Mark page as being fetched
    sitemap.Lock()
    sitemap.m[url] = &Page{}
    sitemap.Unlock()

    page, err := Fetch(url)
    if err != nil {
        fmt.Println(err)
        return
    }

    page.depth = depth

    c <- page
    return
}

func main() {
    sitemap = Sitemap{m: make(map[string]*Page)}
    flag.Parse()
    args := flag.Args()
    wg.Add(1)

    if len(args) < 1 {
        fmt.Println("Please specify start page")
        os.Exit(1)
    }

    initialSite = args[0]

    resultsChannel := make(chan *Page)

    go HandleCrawlResults(resultsChannel)

    Crawl(args[0], 6, resultsChannel)

    wg.Wait()

    sitemap.PrintToFile()
}

func Fetch(url string) (*Page, error) {
    resp, err := client.Get(url)
    if err != nil {
        return nil, err
    }

    defer resp.Body.Close()

    page := Page{url: url, response: resp}
    
    page.ProcessBody()
    return &page, nil
}

func Clean(url string) (string, error) {
    if strings.Contains(url, "#") {
        var index int
        for n, str := range url {
            if strconv.QuoteRune(str) == "'#'" {
                index = n
                break
            }
        }
        url = url[:index]
    }

    if strings.HasPrefix(url, "/") {
        url = initialSite + url
    }

    if !strings.HasPrefix(url, initialSite) {
        return "", fmt.Errorf("Url not in scope")
    }

    return url, nil
}

func HandleCrawlResults(c chan *Page) {
    for page := range c {
        fmt.Printf("Depth: %v, url: %v\n", page.depth, page.url)
        sitemap.Lock()
        sitemap.m[page.url] = page
        sitemap.Unlock()
        for _, u := range page.urls {
            url := u
            wg.Add(1)
            go func() {
                defer wg.Done()
                Crawl(url, page.depth-1, c)
            }()
        }
        if page.url == initialSite {
            wg.Done()
        }
    }
}




type Sitemap struct {
    sync.RWMutex
    m map[string]*Page
}

func (s *Sitemap) PrintToFile() {
    f, err := os.Create("/tmp/test")
    if err != nil {
        panic(err)
    }

    defer f.Close()

    for _, page := range s.m {
        f.WriteString(fmt.Sprintf("%v", page))
    }
}



type Page struct {
    url string
    response *http.Response
    urls []string
    staticFiles []string
    depth int
}

func (p *Page) ProcessBody() {
    // Code mostly taken from https://github.com/JackDanger/collectlinks
    tokens := html.NewTokenizer(p.response.Body)
    for {
        tokenType := tokens.Next()
        if tokenType == html.ErrorToken {
            return
        }
        token := tokens.Token()
        if tokenType == html.StartTagToken && token.DataAtom.String() == "a" {
            for _, attr := range token.Attr {
                if attr.Key == "href" && attr.Val != "#" {
                    p.urls = append(p.urls, attr.Val)
                }
            }
        }

        if tokenType == html.StartTagToken && (token.DataAtom.String() == "img" || token.DataAtom.String() == "script") {
            for _, attr := range token.Attr {
                if attr.Key == "src" {
                    p.staticFiles = append(p.staticFiles, attr.Val)
                }
            }
        }

        if tokenType == html.StartTagToken && token.DataAtom.String() == "link" {
            tmpVal := ""
            addThis := false
            for _, attr := range token.Attr {
                if attr.Key == "rel" && attr.Val == "stylesheet"  {
                    addThis = true
                } else if attr.Key == "href" {
                    tmpVal = attr.Val
                }
            }

            if addThis {
                p.staticFiles = append(p.staticFiles, tmpVal)
            }
        }
    }

    return
}

func (p *Page) String() string {
    var buffer bytes.Buffer
    buffer.WriteString(p.url)
    buffer.WriteString(":\n")
    buffer.WriteString(fmt.Sprintf("\tLinks (%v):\n", len(p.urls)))
    for _, link := range p.urls {
        buffer.WriteString(fmt.Sprintf("\t\t%v\n", link))
    }
    buffer.WriteString(fmt.Sprintf("\tStatic Files (%v):\n", len(p.staticFiles)))
    for _, staticFile := range p.staticFiles {
        buffer.WriteString(fmt.Sprintf("\t\t%v\n", staticFile))
    }
    buffer.WriteString("\n")

    return buffer.String()
}