# webcrawler
PHP:

All the following from the php folder.

To build:
```
make composer
```
Tests:
```
make tests
```
Run:
```
php src/mondocrawler/Main.php tomblomfield.com
```
This will create a file tomblomfield.com.txt that shows the sitemap and static assets for the website tomblomfield.com

Todo:
Obey robots.txt
Add a depth counter so we don't crawl indefintely
