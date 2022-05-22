<?php

namespace Equibles\Websockets\SignalR;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\Promise\Promise;

class Client {
    private $endpoint;
    private $connectionToken;
    private $connectionId;
    private $wsConn;
    private $loop;
    private $callbacks;

    public function __construct($endpoint) {
        $this->endpoint = $endpoint;
        $this->callbacks = [];
    }

    public function run($callback): Promise{
        if (!$this->negotiate()) {
            throw new \RuntimeException("Cannot negotiate");
        }

        return $this->connect($callback);
    }

    public function wait() {
        $this->loop->run();
    }

    public function on($action, $callback) {
        if (!array_key_exists($action, $this->callbacks)) {
            $this->callbacks[$action] = [];
        }
        $this->callbacks[$action][] = $callback;
    }

    private function connect($callback) : Promise {
        $this->connectionStatus = -1;
        $this->loop = Loop::get();
        $connector = new Connector($this->loop);
        error_log(print_r("Connect to: " . $this->buildConnectUrl(), true));
        $promise = $connector($this->buildConnectUrl())->then(function (WebSocket $conn) use ($callback) {
            $this->wsConn = $conn;
            $conn->on('message', function (MessageInterface $msg) use ($conn) {
                $messageJson = str_replace(chr(30), "", $msg);
                $messageObject = json_decode($messageJson);

                // error_log(print_r($messageJson, true));

                if(is_object($messageObject) && property_exists($messageObject, "type")) {
                    // Invocation
                    if ($messageObject->type == 1) {
                        if (array_key_exists($messageObject->target, $this->callbacks)) {
                            foreach ($this->callbacks[$messageObject->target] as $closure) {
                                $closure(...$messageObject->arguments);
                            }
                        }
                    }
                }
            });
            $conn->send(json_encode(["protocol" => "json", "version" => 1]) . chr(30));
            if($callback != null){
                $callback();
            }
            $this->connectionStatus = 1;
        }, function (Exception $e) {
            echo "Could not connect: {$e->getMessage()}\n";
            $this->loop->stop();
            $this->connectionStatus = 0;
        });
        return $promise;
    }

    public function send(string $action, array $arguments): void {
        $this->wsConn->send(json_encode(["arguments" => $arguments, "target" => $action, "type" => 1]) . chr(30));
    }

    private function buildNegotiateUrl(): string {
        $query = ["negotiateVersion" => 1];
        return $this->endpoint . "/negotiate?" . http_build_query($query);
    }

    private function buildConnectUrl(): string {
        $query = [
            "id" => $this->connectionToken,
        ];
        $wsEndpoint = str_replace("http", "ws", $this->endpoint);
        return $wsEndpoint . "?" . http_build_query($query);
    }

    private function negotiate(): bool {
        try {
            $url = $this->buildNegotiateUrl();
            $client = new \GuzzleHttp\Client();
            $res = $client->request('POST', $url);

            $body = json_decode($res->getBody());

            $this->connectionToken = $body->connectionToken;
            $this->connectionId = $body->connectionId;
            return true;
        } catch (GuzzleException $e) {
            var_dump($e);
            return false;
        } catch (Exception $e) {
            var_dump($e);
            return false;
        }
    }
}