// $Id$
// Varnish vcl configuration hints for implementing PURGE requests.
//
// Warning: This is not a complete replacement for a varnish configuration.
// Use the info provided here to ajust your varnish configuration
//
// Made originaly as documentation for the drupal purge module
// http://drupal.org/project/purge
// Should work with Mediawiki, Wordpress etc.

// First it highly recommended to set an acl for all ip adresses allowed to
// perform PURGE requests to prevent mailcious purging in a ddos scenario.
// In general these are the ip-adresses of your webserver(s)
acl purge {
  "localhost";
  "127.0.0.1";
}

// Your current .vcl probably allready has a sub vcl_recv section.
// If it doesn't, add it. 
sub vcl_recv {

  // Here is where you can add your own vcl.recv code. 
  
  // BEGIN PURGE 
  // Check the incoming request type is "PURGE", not "GET" or "POST"
  if (req.request == "PURGE") {
    // Check if the ip coresponds with the acl purge
    if (!client.ip ~ purge) {
      // Return error code 405 (Forbidden) when not
      error 405 "Not allowed.";
    }
    // Purge all objects from cache that match the incoming url and host
    purge("req.url == " req.url " && req.http.host == " req.http.host);
    // Return a http error code 200 (Ok)
    error 200 "Purged.";
  }
  // END PURGE

} // Make sure you don't forget closing brackets
