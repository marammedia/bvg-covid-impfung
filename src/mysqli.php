<?php

if (!class_exists('mysqli', false)) {
  exit('About to call new mysqli(), but the PHP MySQLi extension is not available!');
}

$retries = 3;
while ($retries--) {
  try {
    $conn = mysqli_init();

    @$conn->real_connect(
      $config['db.host'],
      $config['db.user'],
      $config['db.pass'],
      $config['db.database'],
      $config['db.port']);

    $errno = $conn->connect_errno;
    if ($errno) {
      $error = $conn->connect_error;
      exit(sprintf(
        'Attempt to connect to %s@%s failed with error #%d: %s.',
          $config['db.user'],
          $config['db.host'],
          $errno,
          $error));
    }

    $ok = @$conn->set_charset('utf8mb4');
    if (!$ok) {
      $ok = $conn->set_charset('utf8');
    }

    break;
  } catch (Exception $ex) {
    if ($retries && $ex->getCode() == 2003) {
      $class = get_class($ex);
      $message = $ex->getMessage();
      exit(sprintf('Retrying (%d) after %s: %s', $retries, $class, $message));
    } else {
      exit($ex);
    }
  }
}
