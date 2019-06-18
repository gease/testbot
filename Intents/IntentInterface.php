<?php

/**
 * Interface for app-defined intent implementations.
 */
interface IntentInterface {

  /**
   * IntentInterface constructor.
   *
   * @param array $nlp
   *   Intents part of parsed input message.
   * @param string $message
   *   Input message text.
   */
  public function __construct(array $nlp, string $message);

  /**
   * Generate reply message.
   *
   * @return string
   */
  public function message();
}