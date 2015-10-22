HTTP Purger
------------------------------------------------------------------------------

This HTTP Purger submodule provides a manually configurable purger that performs
outgoing HTTP requests to purge something and resembles the same configurability
as Purge had in versions ``7.x-1.x`` and ``6.x-1.x``.

#### TODO / ROADMAP

* Identify all HTTP options (see UI) from ``7.x-1.x``.
* Judge per option if they can still apply technically.
* Judge per option if they can be set with Guzzle, https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Http%21Client.php/class/Client/8
* LATER: implement \Plugin\Purge\Purger\Htt
