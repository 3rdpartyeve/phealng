# Pheal

Copyright (C) 2010-2011 by Peter Petermann
All rights reserved.

Pheal is a port of EAAL to PHP

## WARNING
Pheal is not a stable release yet,
stuff might or might not work as expected

## LICENSE
Pheal is licensed under a MIT style license, see LICENSE.txt
for further information

## FEATURES
- does not need to change when EVE API changes

## REQUIREMENTS
- PHP 5.2 (might run on earlier versions, untested)


## INSTALLATION
1. `git clone git://github.com/ppetermann/pheal.git`
2. make sure your autoloader is able to find the classes
   (filename example.php matches classname "example" OR
   `include "../path/to/Pheal.php"; spl_autoload_register("Pheal::classload");`
   in your application, which will use a simple buildin autoloader

## USAGE

### Initialize the API Object
    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    $pheal = new Pheal("keyID", "vCode"[, "scope for request"]);
the scope is the one used for the API requests, ex. account/char/corp/eve/map/server 
see API Reference the scope can be changed during runtime and defaults to account

for public API's you can leave keyID/vCode empty.
    $pheal = new Pheal();
    $pheal->scope = 'map';
or
    $pheal = new Pheal(null, null, 'map');

### Request Information
    $result = $pheal->ApiPage();
this will return an Object of type PhealResult which then can be used to read the api result
If you want to access the raw http/xml result for whatever reason, you can just ask the xml 
attribute afterwords.
    $rawxml = $pheal->xml;

### Example 1, getting a list of characters on the account:
    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    $pheal = new Pheal("keyID", "vCode"[, "scope for request"]);

    $result = $pheal->Characters();
    foreach($result->characters as $character)
      echo $character->name;

### Example 2, getting the id for a given character name
    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    $pheal = new Pheal("keyID", "vCode"[, "scope for request"]);

    $pheal->scope = "eve";
    $result = $pheal->CharacterID(array("names" => "Peter Powers"));
    echo $result->characters[0]->characterID;

### Using the cache
Pheal comes with a simple file cache, to make use of this cache:
`PhealConfig::getInstance()->cache = new PhealFileCache("/path/to/cache/directory/");`
does the magic. if you don't give a path it defaults to $HOME/.pheal/cache

### Example 3, doing a cached request
    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->cache = new PhealFileCache();
    $pheal = new Pheal("keyID", "vCode"[, "scope for request"]);

    $pheal->scope = "eve";
    $result = $pheal->CharacterID(array("names" => "Peter Powers"));
    echo $result->characters[0]->characterID;

now the request will first check if the xml is already in the cache, if it is still
valid, and if so use the cached, only if the cache until of the saved file has 
expired, it will request again.

### Fluent interface to scope
erikfercak added a way to set the scope fluently, you now can do
    $pheal->scopenameScope->apiCall()
for example
    $pheal->eveScope->CharacterID();
be aware that this sets the scope to the last used scope in the fluent interface!

### Exceptions
Pheal throws an Exception of type PhealAPIException (derived from PhealException)
whenever the EVE API returns an error, this exception has an attribute called "code"
which is the EVE APIs error code, and also contains the EVE API message as message.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->cache = new PhealFileCache();
    $pheal = new Pheal("keyID", "vCode"[, "scope for request"]);
    try {
        $pheal->Killlog(array("characterID" => 12345));
    } catch(PhealException $e) {
        echo 'error: ' . $e->code . ' message: ' . $e->getMessage();
    }

### Archiving
If you wanna archive your api requests for future use, backups or possible feature 
additions you can add an archive handler that saves your api responses in a similar
way like the cache handler is doing it. Only non-error API responses are being cached.
The files are grouped by date and include the gmt timestamp.

Make sure that you've a cron job running that moves old archive folders into zip/tar/7z
archives. Otherwise you end up with million xml files in your filesystem.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->cache = new PhealFileCache();
    PhealConfig::getInstance()->archive = new PhealFileArchive();
    $pheal = new Pheal(null, null, 'map');
    try {
        $pheal->Sovereignty();
    } catch(PhealException $e) {
        echo 'error: ' . $e->code . ' message: ' . $e->getMessage();
    }

### Logging
Pheal allows you to log all api calls that are requested from CCP's API Server. This
is useful for debugging and performance tracking (response times) of the API server.

The responseTime is being tracked. The API Key will be truncated to for better
security. This can be turned of via the module options array. Pheal will use 2 log files.
One 'pheal_access.log' for successful calls and a 'pheal_error.log' for failed requests.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->log = new PhealFileLog();
    $pheal = new Pheal(null, null, 'map');
    try {
        $pheal->Sovereignty();
    } catch(PhealException $e) {
        echo 'error: ' . $e->code . ' message: ' . $e->getMessage();
    }

### HTTP request options
There're 2 methods available for requesting the API information. Due to the some
php or webhosting restrictions file_get_contents() isn't available for remote 
requests. You can choose between 'curl' and 'file'. Additionally you can set the
http method (GET or POST) and set your custom useragent string so CCP can recognize
you while you're killing their API servers. Keep-Alive keeps the connection open
for X seconds, this reduce the tcp/ssl handshake overhead if you're doing multiple
api calls. The connection will be automatically closed after X seconds or at the
end of the script. Keep in mind that multiple running api requests (different scripts,
cronjobs, www, etc) can interfere with the max allowed connections per IP on the
remote server. HTTP Keep-Alive is only available with the curl method.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->http_method = 'curl';
    PhealConfig::getInstance()->http_post = false;
    PhealConfig::getInstance()->http_user_agent = 'my mighty api tool';
    PhealConfig::getInstance()->http_interface_ip' = '1.2.3.4';
    PhealConfig::getInstance()->http_timeout = 15;
    PhealConfig::getInstance()->http_keepalive = true; // default 15 seconds
    PhealConfig::getInstance()->http_keepalive = 10; // KeepAliveTimeout in seconds

### SSL Encrypted API Calls
With Incursion 1.1.2 CCP allows you to make SSL encrypted calls. To accomplish that
you only need to point the api_base to the correct SSL url.

since 0.1.0 SSL is enabled by default.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->api_base = 'https://api.eveonline.com/';

If you've trouble with the SSL connection you can turn off the peer and certificate
verification (for debug purposes), but keep in mind you'll be vulnerable to
man-in-the-middle attacks then.

    PhealConfig::getInstance()->http_ssl_verifypeer = false;

### Helper Function
The method **toArray()** can be called on any level of the api result. It's useful
if you wanna convert an api result object into a json string or if you wanna use the 
result array in your favorite template engine.

    $pheal = new Pheal();
    $result = $pheal->eveScope->FacWarStats();
    $array = $result->toArray();
    $json = json_encode($array);

### Legacy API Keys - Basics
Since 0.1.0 Pheal is using the customizeable Keys by default. If you for what ever reason
need to use legacy keys (you should not), you can enable the old behaviour by
setting api_customkeys to false on the PhealConfig

    PhealConfig::getInstance()->api_customkeys = false;

### Customizable API Keys - Access Check
This config options allows you to verify the call for a given accessLevel before any
external API request leaves your software. This is useful to prevent generating api
errors before you get banned cause of too many api errors.
Pheal will throw an PhealAccessException so you can react on the access limitations.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->api_base = 'https://api.eveonline.com/';
    PhealConfig::getInstance()->api_customkeys = true;
    PhealConfig::getInstance()->access = new PhealCheckAccess();

    // fetch keyID, vCode, keyType, accessMask from your KeyStorage (DB)
    $pheal = new Pheal($keyID, $vCode);
    $pheal->setAccess($keyType, $accessMask);
    try {
        $result = $pheal->charScope->Contracts();

    } catch(PhealAccessException $e) {
        echo "access error: ".$e->getMessage();
        /* do something - example: disable key */

    } catch(PhealAPIException $e) {
       echo 'api error: ' . $e->code . ' message: ' . $e->getMessage();
       /* do something - example: disable key */

    } catch(PhealException $e) {
        echo 'generic error: ' . $e->code . ' message: ' . $e->getMessage();
        /* do something - example: wait 5 minute next key usage (network/cluster problem) */
    }

    // call clearAccess or empty setAccess to reset the given keyType/accessMask
    // $pheal->clearAccess();

### Customizable API Keys - Access Check Autodetect
Instead of taking keyType and accessMask from your API key storage you can also use
the detectAccess method. This will do the APIKeyInfo query and set correct key information
to prevent you from doing invalid calls. If you're managing a lot of API keys please it's
still better to store keyType and accessMask along your with the api keys and check them
if the access configuration is changing.

Keep in mind the detectAccess() method will throw PhealExceptions like your normal API
request if the API key isn't longer valid or the API Servers are down.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->api_base = 'https://api.eveonline.com/';
    PhealConfig::getInstance()->api_customkeys = true;
    PhealConfig::getInstance()->access = new PhealCheckAccess();

    $pheal = new Pheal($keyID, $vCode);
    try {
        $pheal->detectAccess();
        $result = $pheal->charScope->Contracts();
    } catch( ... ) { ... }


## TODO
- more documentation
- more error handling

## LINKS
- [Github](http://github.com/ppetermann/pheal)
- [devedge](http://devedge.eu/project/pheal/)

## CONTACT
- Peter Petermann <ppetermann80@googlemail.com>

## Contributors
- Daniel Hoffend (Wollari)

## ACKNOWLEDGEMENTS
- Pheal is based on [EAAL](http://github.com/ppetermann/eaal)
- Pheal is written in [PHP](http://php.net)
- Pheal is build for use of the [EVE Online](http://eveonline.com) API

