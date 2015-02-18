HTTP Purger
------------------------------------------------------------------------------

This HTTP Purger submodule provides a manually configurable purger that performs
outgoing HTTP requests to purge something and resembles the same configurability
as Purge had in versions ``7.x-1.x`` and ``6.x-1.x``.

#### TODO / ROADMAP

* Identify all HTTP options (see UI) from ``7.x-1.x``.
* Judge per option if they can still apply technically.
* Judge per option if they can be set with Guzzle, https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Http%21Client.php/class/Client/8
* Write CMI yml file with all options and defaults.
* Once CMI yml file is somewhat stable, write schema for it.
* Write form elements matching CMI options. Form can be tested easily on http://SITE/admin/config/development/performance/purge/http
* LATER: implement \Plugin\PurgePurger\Http but take into account that the API might change meanwhile.
