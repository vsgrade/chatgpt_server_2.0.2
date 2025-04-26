<?php
function log_error($message, $file = null, $line = null) {
  file_put_contents(__DIR__.'/log_debug.txt', date('Y-m-d H:i:s')." log_error called: $message ($file:$line)\n", FILE_APPEND);

  try {
    global $db;
    if (!isset($db)) {
      require_once __DIR__ . "/db.php";
    }
    $message = substr($message, 0, 65535);

    $stmt = $db->prepare("INSERT INTO error_logs (message, file, line) VALUES (?, ?, ?)");
    $stmt->execute([$message, $file, $line]);
    file_put_contents(__DIR__.'/log_debug.txt', date('Y-m-d H:i:s')." DB write OK\n", FILE_APPEND);

  } catch (Exception $e) {
    $fallback_path = __DIR__ . '/php_error.log';
    $data = date('Y-m-d H:i:s') . " | $message | $file | $line | FALLBACK: " . $e->getMessage() . "\n";
    file_put_contents($fallback_path, $data, FILE_APPEND);
    file_put_contents(__DIR__.'/log_debug.txt', date('Y-m-d H:i:s')." DB write FAIL: ".$e->getMessage()."\n", FILE_APPEND);
  }
}

define('LOG_LEVELS', ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL']);

function log_message($level, $message, $file = null, $line = null) {
    if (!in_array($level, LOG_LEVELS)) $level = 'INFO';
    log_error("[$level] $message", $file, $line);
}

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
  log_message('ERROR', $errstr, $errfile, $errline);
  return false;
});

set_exception_handler(function ($exception) {
  log_message('CRITICAL', $exception->getMessage(), $exception->getFile(), $exception->getLine());
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        log_message('CRITICAL', "SHUTDOWN: {$err['message']}", $err['file'], $err['line']);
    }
});
?>
