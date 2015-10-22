# API
An API for the Eskimo EPOS

## Requirements
-----------

* [PHP Curl Class](https://github.com/php-curl-class/php-curl-class)
* If you decide to manually update the recipient database, [MeekroDB](https://github.com/SergeyTsalkov/meekrodb) is a simple and lightweight PHP MySQL Library ([further documentation](http://meekro.com/docs.php)).  This is useful for testing purposes and is used in the example code.


## Configuration and notes 
-----------

Initial API setup and proof of concept with CSCart.  This is a work in progress and open to suggestions.

Configuration is contained within [eskimo.php](eskimo.php), but can moved to an alternative method.

A few simple examples have been provided for CSCart within [cscart.php](examples/cscart.php).


### Initialise class
```
$cscart = new cscart($config);
```

### Obtains, sorts and uploads full list of categories
```
$res = $cscart->ul_categories();
```

### Obtains and uploads full list of tax codes
```
$res = $cscart->ul_tax_codes();
```

