Purge
The purge module clears urls from reverse proxy caches like Varnish
(http://varnish-cache.org/) or Squid (http://www.squid-cache.org/) by issuing
an http PURGE request to them. It works in conjunction with the Cache
Expiration (http://drupal.org/project/expire) module to act on events that are
likely to expire urls from the proxy cache. This allows delivering content
updates faster to end users.

Requirements:
- One or more reverse proxy caches (http://en.wikipedia.org/wiki/Reverse_proxy)
like Varnish (recommended) or Squid that point to your webserver(s).
- Varnish needs a modification to its configuration file. See the included
varnish_example.vcl for details.
- Squid needs to have purging enabled in its configuration. See
http://docstore.mik.ua/squid/FAQ-7.html#ss7.5
- A cachable version of Drupal 6. This can be an official Drupal release with
a patch applied (http://drupal.org/node/466444) or use Pressflow
(http://pressflow.org/), a cachable friendly fork of Drupal.
- PHP with curl(http://php.net/manual/en/book.curl.php) enabled. The Purge
module uses curl for issuing the http PURGE requests.
- Purge requires the expire module http://drupal.org/project/expire

Installation:
- Unpack, place and enable just like any other module.
- Navigate to Administration -> Site configuration -> Purge settings
- Set your proxy url(s) like "http://localhost" or
"http://192.168.1.23:8080 http://192.168.2.34:8080"

Q&A:
Q: How do I know if its working?
A: Purge reports errors to watchdog. Also when running "varnishlog" on the
proxy your should see PURGE requests scrolling by when you (for instance)
update an existing node.
You can also test if your proxy is configured correctly by issuing a curl
command in a shell on any machine in the access list of your proxy: 
curl -X PURGE -H "Host: example.com" http://192.168.1.23/node/2

Q: Why choose this over the Varnish module (http://drupal.org/project/varnish)?
A: Purge just issues purge requests to your proxy server(s) over standard http
on every url the expire module detects. It requires modification of your
Varnish configuration file.
The varnish module has more internal logic to purge your Varnish cache
completely, which can be more disruptive then the expire module integration it
also offers. It uses a terminal interface to communicate to varnish instead of
http. This allows for more features but also hands over full control over the
varnish instance to any machine having network access to it. (This is a
limitation of Varnish.) Also firewall or other security policies could pose a
problem. It does not require modification of your config file. If you have the
choice Varnish module is probably your best bet but Purge might help you out in
places where Varnish module is not an option.

Credits:
Paul Krischer / "SqyD" on drupal.org
paul@krischer.nl / sqyd@sqyd.net

