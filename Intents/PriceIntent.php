<?php

include 'IntentInterface.php';
use Symfony\Component\Yaml\Yaml;

/**
 * Reply to price inquiry.
 */
class PriceIntent implements IntentInterface {
  private $item;
  private $price;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $nlp, string $message) {
    $this->item = $nlp['price'][0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function message() {
    if ($this->getPrice()) {
      return "Price for " . $this->item . " is " . $this->getPrice();
    }
    else {
      return "I cannot tell you the price for " . $this->item;
    }
  }

  /**
   * Get price of an item from yaml file.
   */
  private function getPrice() {
    if (!isset($this->price)) {
      $prices = Yaml::parseFile('./Intents/prices.yml');
      $this->price = $prices[$this->item] ?? FALSE;
    }
    return $this->price;
  }

}
