<?php

include 'IntentInterface.php';

/**
 * Reply to any greeting, detected by wit.
 */
class GreetingsIntent implements IntentInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $nlp, string $message) {

  }

  /**
   * {@inheritdoc}
   */
  public function message() {
    return "Hello! Nice to see you.";
  }
}
