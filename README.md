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
more documentation to come

## TODO
- more documentation
- error handling

## LINKS
- [Github](http://github.com/ppetermann/pheal)

## CONTACT
- Peter Petermann <ppetermann80@googlemail.com>

## ACKNOWLEDGEMENTS
- Pheal is based on [EAAL](http://github.com/ppetermann/eaal)
- Pheal is written in [PHP](http://php.net)
- Pheal is build for use of the [EVE Online](http://eveonline.com) API