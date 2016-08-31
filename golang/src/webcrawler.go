package main

import (
    "fmt"
    "flag"
    "os"
    "net/http"
    "strings"
    "strconv"

    "golang.org/x/net/html"
)

var client http.Client
var sitemap = make(map[string]*Page)
var initialSite string

// Crawl uses fetcher to recursively crawl
// pages starting with url, to a maximum of depth.
func Crawl(url string, depth int) {
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

    _, ok := sitemap[url]
    if ok {
        return
    }

    page, err := Fetch(url)
    if err != nil {
        fmt.Println(err)
        return
    }

    sitemap[url] = page
    for _, u := range page.urls {
        Crawl(u, depth-1)
    }
    return
}

func main() {
    flag.Parse()
    args := flag.Args()

    if len(args) < 1 {
        fmt.Println("Please specify start page")
        os.Exit(1)
    }

    initialSite = args[0]

    Crawl(args[0], 6)
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











type Page struct {
    url string
    response *http.Response
    urls []string
    staticFiles []string
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
                if attr.Key == "href" {
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