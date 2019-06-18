<?php

define("DEBUG", TRUE);
define("FB_APP_TOKEN", 'EAAIXoFI1lvcBADcPdcZAq7eLwOeZAZA4XWKGECWjh1daRHZA5JJEyKUMImTHyI4kNZCZAWQbZCgatfMiUNXaWGaKZB5n7FHJWRrYOs21i3cXOGkWkQsQm06TeL6Rfo1HLcSJzx83ludBsbyIIpe4omvyKQjybqAhIwvKG2IMi6skdCZC8PWzAY31o');
define ("TEXT_DEFAULT", "I don't understand you");
define("VERIFY_TOKEN", "deneuvy_test");
define("LOG_PATH", "log");

include('./vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;

// This function is needed only on the first app call.
verify();

$intents = Yaml::parseFile('./intents.yml');
foreach ($intents as $key => &$value) {
  sort($value);
}

$input = json_decode(file_get_contents('php://input'), TRUE);

$text = TEXT_DEFAULT;
// Check if the message is not empty.
if (is_array($input) && !empty($input['entry'][0]['messaging'][0]['message'])) {
  $message = new Message($input);
}
else {
  exit;
}

// Match the signature of message (intents found) to signatures app distinguishes.
$user_intent = match_intent($message, $intents);

if ($user_intent) {
  /** @var $instance \IntentInterface */
  $instance = get_instance($user_intent, $message->getNlp(), $message->getText());
  if ($instance) {
    $text = $instance->message();
  }
}

send_message($message->getSender(), $text);

/**
 * App verification function.
 */
function verify() {
  if (isset($_REQUEST['hub_challenge']) && isset($_REQUEST['hub_verify_token'])) {
    $challenge = $_REQUEST['hub_challenge'];
    $verify_token = $_REQUEST['hub_verify_token'];
    // Set this Verify Token Value on your Facebook App.
    if ($verify_token === VERIFY_TOKEN) {
      echo $challenge;
    }
  }
}

/**
 * @param \Message $message
 *   Received message.
 * @param array $intents
 *   Intents defined by the app.
 * @return bool|string
 *   Name of intent distinguished or false.
 */
function match_intent(Message $message, array $intents) {
  $signature = $message->getSignature();
  $num = count($signature);
  foreach ($intents as $key => $value) {
    if (count($value) != $num) {
      continue;
    }
    else {
      for ($i = 0; $i < $num; $i++) {
        if ($signature[$i] != $intents[$key][$i]) {
          continue 2;
        }
      }
    }
    return $key;
  }
  return FALSE;
}

/**
 * @param string $intent
 *   Intent key.
 * @param array $nlp
 *   Intents part of the parsed input message.
 * @param string $message
 *   Input message text.
 *
 * @return \IntentInterface|null
 *   Instance of intent class.
 */
function get_instance(string $intent, array $nlp, string $message) {
  $class_name = ucfirst($intent) . 'Intent';
  $filepath = './Intents/' . $class_name . '.php';
  if (file_exists($filepath)) {
    include_once $filepath;
    return new $class_name($nlp, $message);
  }
  return NULL;
}

/**
 * Send actual message.
 *
 * @param string $sender_id
 *   Facebook sender id.
 * @param string $text
 *   Reply message.
 */
function send_message(string $sender_id, string $text) {
  //API Url and Access Token, generate this token value on your Facebook App Page
  $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . FB_APP_TOKEN;
  // Initiate cURL.
  $ch = curl_init($url);
  // The JSON data.
  $jsonData = '{
    "recipient":{
        "id":"' . $sender_id . '"
    }, 
    "message":{
        "text":" ' . $text . '"
    }
  }';
  if (DEBUG) {
    write_log($jsonData, 'reply');
  }
  // Tell cURL that we want to send a POST request.
  curl_setopt($ch, CURLOPT_POST, 1);
  // Attach our encoded JSON string to the POST fields.
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
  // Set the content type to application/json.
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  // Execute the request.
  $result = curl_exec($ch);
}

/**
 * Log to file.
 *
 * @param $input
 * @param string $type
 */
function write_log($input, string $type) {
  file_put_contents(LOG_PATH, print_r("\n******" . $type . '*************' . date('c') . "***************\n", TRUE), FILE_APPEND);
  file_put_contents(LOG_PATH, print_r($input, TRUE), FILE_APPEND);
}

/**
 * Helper class to handle input message.
 */
class Message {

  /**
   * Facebook sender id.
   *
   * @var string
   */
  private $sender;

  /**
   * Full message text.
   *
   * @var string
   */
  private $text;

  /**
   * Intents found within a message.
   *
   * @var array
   */
  private $nlp;

  /**
   * Message constructor.
   *
   * @param array $input
   *   Input message.
   */
  public function __construct(array $input) {
    // It is not clear how and when indexes for 'entries' and 'messaging' get higher than 0.
    // For simple phrases it should be safe to assume them as 0.
    // @todo Nevertheless, check and throw an error if indexes are > 0.
    $this->text = $input['entry'][0]['messaging'][0]['message']['text'];
    $this->nlp = $input['entry'][0]['messaging'][0]['message']['nlp']['entities'];
    $this->sender = $input['entry'][0]['messaging'][0]['sender']['id'];
    if (DEBUG) {
      write_log($input['entry'][0]['messaging'][0], 'message');
    }
  }

  /**
   * Get the intents within the message.
   */
  public function getSignature() {
    if (!empty($this->nlp) && is_array($this->nlp)) {
      $entities = array_keys($this->nlp);
      sort($entities);
      return $entities;
    }
    else {
      // No intents detected.
      return FALSE;
    }
  }

  public function getSender() {
    return $this->sender;
  }

  public function getNlp() {
    return $this->nlp;
  }

  public function getText() {
    return $this->text;
  }
}
