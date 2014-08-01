Purge

The Purge module aims to provide a framework to clear pages from external
caches like Reverse Proxy Caches (Varnish, Nginx, Squid), hosting plaforms and
CDNs. 

Note: Purge doesn't act on itself. It requires input on _what_ to purge. The
expire module provides a good starting point for most users. More advanced
needs can be soled by using the Rules module. http://drupal.org/project/rules

Supported platforms and integrations:
- Example configurations available for Varnish, Nginx and Squid. See
  INSTALL.txt for platform specific installation instructions.
- Integration with the Expire module. (recommended)
  http://drupal.org/project/expire


Q&A:
Q: How do I know if its working?
A: Purge reports errors to watchdog. Also when running "varnishlog" on the
proxy your should see PURGE requests scrolling by when you (for instance)
update an existing node.

Q: How can I test this more efficiently?
A: The expire module has drush support so you can issue purge commands from
the command line. See http://drupal.org/node/1054584
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
Thanks:
Mike Carper / mikeytown2 on drupal.org, Author of Expire
Brian Mercer / brianmercer on drupal.org, nginx testing and debugging

Changelog:
1.0 Initial release. Basic purge functionality in place
1.1 Refactoring for Nginx and future platform support and better error handling
1.2 (Upcoming) Acquia Hosting support, form validation
1.3 Bugfix release. Issue 1235674. Output buffering patch by mauritsl on drupal.org.
