<?php

namespace Equibles\Websockets\Messages;

class Quote {
    /**
     * @var string|null
     */
    private $ticker;
    /**
     * @var float
     */
    private $price;
    /**
     * @var float
     */
    private $volume;
    /**
     * @var int
     */
    private $timestamp;

    public function __construct(?string $ticker, float $price, float $volume, int $timestamp) {
        $this->ticker = $ticker;
        $this->price = $price;
        $this->volume = $volume;
        $this->timestamp = $timestamp;
    }

    /**
     * @return string|null
     */
    public function getTicker(): ?string {
        return $this->ticker;
    }

    /**
     * @return float
     */
    public function getPrice(): float {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getVolume(): float {
        return $this->volume;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int {
        return $this->timestamp;
    }

}