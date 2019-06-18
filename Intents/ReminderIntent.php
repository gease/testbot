<?php

include 'IntentInterface.php';

/**
 * Reply to request for a reminder.
 */
class ReminderIntent implements IntentInterface {

  /**
   * Type as provided by nlp, can be 'value' or 'interval'.
   *
   * @var string
   */
  private $datetimeType;

  /**
   * Structured array provided by nlp, different for value and interval types.
   *
   * @var array
   */
  private $datetime;

  /**
   * The reminder part of input message.
   *
   * @var string
   */
  private $reminder;

  /**
   * Date format to use in reply message.
   *
   * @var string
   */
  private $dateFormat = 'F, d, Y : H.i T';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $nlp, string $message) {
    $this->datetimeType = $nlp['datetime'][0]['type'];
    $this->datetime = $nlp['datetime'][0]['values'][0];
    $this->reminder = $nlp['my_reminder'][0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function message() {
    if ($this->datetimeType == 'interval') {
      $datetime = $this->datetime['from']['value'];
    }
    elseif ($this->datetimeType == 'value') {
      $datetime = $this->datetime['value'];
    }
    else {
      return "I cannot set the reminder.";
    }
    return "Setting the reminder for " . date($this->dateFormat, strtotime($datetime)) . " to " . $this->reminder;

  }

}
