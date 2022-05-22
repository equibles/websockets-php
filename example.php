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

