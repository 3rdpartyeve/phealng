# Pheal

Copyright (C) 2010 by Peter Petermann
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
    $pheal = new Pheal("myUserid", "myAPI key"[, "scope for request"]);
the scope is the one used for the API requests, ex. account/char/corp/eve/map/server see API Reference the scope can be changed during runtime and defaults to account

for public API's you can leave userID/apiKey empty.
    $pheal = new Pheal();
    $pheal->scope = 'map';
or
    $pheal = new Pheal(null, null, 'map');

### Request Information
    $result = $pheal>ApiPage();
this will return an Object of type PhealResult which then can be used to read the api result
If you want to access the raw http/xml result for whatever reason, you can just ask the xml 
attribute afterwords.
    $rawxml = $pheal->xml;

### Example 1, getting a list of characters on the account:
    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    $pheal = new Pheal("myUserid", "myAPI key"[, "scope for request"]);

    $result = $pheal->Characters();
    foreach($result->characters as $character)
      echo $character->name;

### Example 2, getting the id for a given character name
    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    $pheal = new Pheal("myUserid", "myAPI key"[, "scope for request"]);

    $pheal->scope = "eve";
    $result = $pheal->CharacterID(array("names" => "Peter Powers"));
    echo $result->characters[0]->characterID;

### Using the cache
Pheal comes with a simple file cache, to make use of this cache:
`PhealConfig::getInstance()->cache = new PhealFileCache("/path/to/cache/directory/");`
does the magic. if you dont give a path it defaults to $HOME/.pheal/cache

### Example 3, doing a cached request
    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->cache = new PhealFileCache();
    $pheal = new Pheal("myUserid", "myAPI key"[, "scope for request"]);

    $pheal->scope = "eve";
    $result = $pheal->CharacterID(array("names" => "Peter Powers"));
    echo $result->characters[0]->characterID;
now the request will first check if the xml is allready in the cache, if it is still valid, and if so use the cached, only if the cache until of the saved file has expired, it will request again.

### Exceptions
Pheal throws an Exception of type PhealAPIException (derived from PhealException)
whenever the EVE API returns an error, this exception has an attribute called "code"
which is the EVE APIs error code, and also contains the EVE API message as message.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->cache = new PhealFileCache();
    $pheal = new Pheal("myUserid", "myAPI key"[, "scope for request"]);
    try {
        $pheal->Killlog(array("characterID" => 12345));
    } catch(PhealException $e) {
        echo 'error: ' . $e->code . ' meesage: ' . $e->getMessage();
    }

### Archiving
If you wanna archive your api requests for future use, backups or possible feature 
additions you can add an archive handler that saves your api responses in a similiar
way like the cache handler is doing it. Only non-error API responses are beeing cached.
The files are grouped by date and include the gmt timestamp.

Make sure that you've a cronjob running that moves old archive folders into zip/tar/7z 
archives. Otherwise you endup with million xml files in your filesystem.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->cache = new PhealFileCache();
    PhealConfig::getInstance()->archive = new PhealArchiveCache();
    $pheal = new Pheal(null, null, 'map');
    try {
        $pheal->Sovereignty();
    } catch(PhealException $e) {
        echo 'error: ' . $e->code . ' meesage: ' . $e->getMessage();
    }

### HTTP request options
There're 2 methods available for requesting the API information. Due to the some 
php or webhosting restrictions file_get_contents() isn't available for remote 
requests. You can choose between 'curl' and 'file'. Additionly you can set the 
http method (GET or POST) and set your custom useragent string so CCP can recognize
you while you're killing their API servers.

    require_once "Pheal/Pheal.php";
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->http_method = 'curl';
    PhealConfig::getInstance()->http_post = false;
    PhealConfig::getInstance()->http_user_agent = 'my mighty api tool';
    PhealConfig::getInstance()->http_interface_ip' = '1.2.3.4';
    PhealConfig::getInstance()->http_timeout = 5;
    
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

