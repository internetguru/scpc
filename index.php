<?php

$input = $_POST['input'] ?? "1 2 3 4&#10;1 3 4 5&#10;1 2 4 5&#10;2 4 5 6";
$properties = ["under-closed", "semi-closed", "weakly-closed", "chordal", "closed", "unit-interval", "traceable", "Hamiltonian", "weakly-traceable", "weakly-Hamiltonian"];
$log = "log.txt";
$status_ok = "done";
$status_size = "max_size_exceeded";
$status_timeout = "timeout_exceeded";
$max_input_size = 1000;
$timeout = 120;

function getClientIP () {
  $keys = array('HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED',
    'HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR');
  foreach($keys as $key) {
    if (!empty($_SERVER[$key])) {
      return $_SERVER[$key];
    }
  }
  return "UNKNOWN";
}

function log_status ($status) {
  global $log, $input;
  $date = new DateTime();
  file_put_contents($log, sprintf("%s (%s): %s [ %s ]\n",
    $date->getTimestamp(), getClientIP(), $input, $status), FILE_APPEND | LOCK_EX);
}

function execute ($cmd, $stdin = null, &$stdout, &$stderr, $timeout = false) {
  $pipes = array();
  $process = proc_open(
    $cmd,
    [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
    $pipes
  );
  $start = time();
  $stdout = '';
  $stderr = '';

  if(is_resource($process)) {
    stream_set_blocking($pipes[0], 0);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);
  }

  while (is_resource($process)) {
    //echo ".";
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);

    if($timeout !== false && time() - $start > $timeout) {
      proc_terminate($process, 9);
      return 100;
    }

    $status = proc_get_status($process);
    if (!$status['running']) {
      fclose($pipes[1]);
      fclose($pipes[2]);
      proc_close($process);
      return $status['exitcode'];
    }
    usleep(100000);
  }
  return 1;
}

$output = "---";
try {
  if (isset($_POST['input']) && isset($_POST['property'])) {
    if (strlen($_POST['input']) > $max_input_size) {
      log_status($status_size);
      throw new Exception("Input matrix is to big");
    }
    $code = execute(
      "echo \"{$_POST['input']}\" | /usr/local/bin/python3.7 scpc.py --property {$_POST['property']} 2>&1",
      null, $output, $output, $timeout
    );
    if ($code == 100) {
      log_status($status_timeout);
      throw new Exception("Script timeout exceeded");
    }
    log_status($status_ok);
  }
} catch (Exception $ex) {
  $output = "Exception: ".$ex->getMessage();
}
$output = htmlentities($output);

$radios = "";
foreach($properties as $property) {
  $radios .= "<dd><label><input type='radio' name='property' value='$property'"
    . ((isset($_POST['property']) && $_POST['property'] == $property) || $property == $properties[0] ? " checked" : "")
    . "/> $property</label></dd>";
}

$commit_id = substr(shell_exec("/usr/local/bin/git rev-parse HEAD"), 0, 7);

$python_file = file("scpc.py");
$heading = substr($python_file[5], 4);
$line_num = 6;
$desc = "";
while ($python_file[$line_num++] != "\n") {
  $desc .= '<p>'.substr($python_file[$line_num++], 2).'</p>';
}

echo <<<EOT
<!DOCTYPE html>
<html>
  <head>
    <title>$heading</title>
    <meta charset='UTF-8'/>
  </head>
  <body>
    <h1>$heading</h1>
    $desc
    <h2>Program Input</h2>
    <form method="POST" action="">
      <dl>
        <dt>Input matrix</dt>
      	<dd><textarea name="input" rows="10" cols="20">$input</textarea></dd>
      	<dt>Properties</dt>
        $radios
        <dt>Send form</dt>
        <dd><input type="submit" value="Submit" /></dd>
      </dl>
    </form>
    <h2>Program Output</h2>
    <pre><code>$output</code></pre>
    <hr/>
    <ul>
      <li><a href="https://colab.research.google.com/github/martapavelka/scpc/blob/dev/scpc.ipynb">Source code on Google Colab</a> (developer version)</li>
      <li><a href="https://github.com/martapavelka/scpc">Source code on GitHub</a> (all versions)</li>
      <li>Current online version commit: $commit_id</li>
      <li>Author: Marta Pavelka, <a href="mailto:pavelka@math.miami.edu">pavelka@math.miami.edu</a></li>
    </ul>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-S1ZRPY85CD"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-S1ZRPY85CD');
    </script>
EOT;
