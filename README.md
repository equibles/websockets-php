# Equibles WebSockets PHP

## Requirements

PHP 7.1 and later

## Installation & Usage
### Composer

To install the bindings via [Composer](http://getcomposer.org/), add the following to `composer.json`:

```
{
  "repositories": [
    {
      "type": "git",
      "url": "https://github.com/equibles/websockets-php.git"
    }
  ],
  "require": {
    "equibles/websockets-php": "*@dev"
  }
}
```

Then run `composer install`

### Manual Installation

Download the files and include `autoload.php`:

```php
require_once('/path/to/EquiblesWebsockets/vendor/autoload.php');
```

## Getting Started

```php
<?php
require_once("vendor/autoload.php");

use Equibles\Websockets\Endpoint;
use Equibles\Websockets\EquiblesWebsocketsClient;
use Equibles\Websockets\Messages\Quote;

$client = new EquiblesWebsocketsClient("MY_API_KEY", Endpoint::Stocks, ["AAPL", "TSLA"]);

$client->connect()->then(function () use ($client) {
    $client->onQuote(function (Quote $quote){
        error_log($quote->getTicker() . " | " . $quote->getPrice());
    });

    // We can add more tickers if we want
    $client->addTickers(["MSFT", "GOOG"]);

    $client->wait();
});
?>
```

## Author
[Equibles](https://www.equibles.com)\
equibles@gmail.com

