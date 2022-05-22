<?php

namespace Equibles\Websockets;

use Equibles\Websockets\Messages\Quote;
use Equibles\Websockets\SignalR\Client;
use React\Promise\Promise;

class EquiblesWebsocketsClient {
    private const Domain = "https://websockets.equibles.com";
    private $apiKey;
    private $tickers;
    private $client;
    private $quoteListeners = [];
    private $listenersRegistered = false;

    public function __construct(string $apiKey, string $endpoint, array $tickers) {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->tickers = $tickers;
        if (!$this->tickers) {
            $this->tickers = [];
        }

        $this->client = new Client(self::Domain . "/" . $endpoint);
    }

    private function registerListeners(): void {
        if($this->listenersRegistered) return;
        $this->listenersRegistered = true;

        // On Quote
        $this->client->on("Quote", function ($quoteData){
            foreach ($this->quoteListeners as $quoteListener){
                $quote = new Quote($quoteData->ticker, $quoteData->price, $quoteData->volume, $quoteData->timestamp);
                $quoteListener($quote);
            }
        });

        // On AuthenticationResult
        $this->client->on("AuthenticationResult", function (bool $success, ?string $errorMessage){
            if($success){
                $this->sendAddTickers($this->tickers);
            }else{
                error_log($errorMessage);
            }
        });

        // On StartListeningResult
        $this->client->on("StartListeningResult", function (bool $success, ?string $errorMessage){
            if($success){
                error_log("Connection successful. Waiting for quotes...");
            }else{
                error_log("Error while adding tickers. Message: " . $errorMessage);
            }
        });

        // On StopListeningResult
        $this->client->on("StopListeningResult", function (bool $success, ?string $errorMessage){
            if($success){
                error_log("Stopped listening to tickers with success.");
            }else{
                error_log("Error while removing tickers. Message: " . $errorMessage);
            }
        });

    }

    private function sendAddTickers(array $tickers): void{
        $this->client->send("StartListening", [$tickers]);
    }

    private function sendStopTickers(array $tickers): void{
        $this->client->send("StopListening", [$tickers]);
    }

    public function connect() : Promise{
        $this->registerListeners();
        return $this->client->run(function (){
            $this->client->send("Authentication", [$this->apiKey]);
        });
    }

    public function onQuote($callback): void{
        $this->quoteListeners[] = $callback;
    }

    public function removeQuoteListener($callback): void{
        $pos = array_search($callback, $this->quoteListeners);
        unset($this->quoteListeners[$pos]);
    }

    public function clearQuoteListener(): void{
        $this->quoteListeners = [];
    }

    public function addTickers(array $tickers): void{
        $this->sendAddTickers($tickers);
    }

    public function removeTickers(array $tickers): void{
        $this->sendStopTickers($tickers);
    }

    public function wait() : void {
        $this->client->wait();
    }
}