<?php

$ntb_crc = hash_file('crc32b', 'scpc.ipynb');
$python_file = "build/$ntb_crc.py";
if (!is_file($python_file)) {
  shell_exec("jupyter nbconvert --to python scpc.ipynb --output-dir=build --output=$ntb_crc 2>&1");
}

$input = $_POST['input'] ?? "1 2 3 4&#10;1 3 4 5&#10;1 2 4 5&#10;2 4 5 6";
$properties = [
  "closed", "unit-interval", "under-closed", "semi-closed", "weakly-closed",
  "weakly-traceable", "traceable", "weakly-hamiltonian", "hamiltonian",
  "weakly-chordal", "skeleton-weakly-chordal", "chordal"
];
$properties_translation = [
  "hamiltonian" => "Hamiltonian",
  "weakly-hamiltonian" => "weakly-Hamiltonian"
];
$log = "logs/scpc.txt";
$status_ok = "done";
$status_size = "max_size_exceeded";
$status_timeout = "timeout_exceeded";
$max_input_size = 1000;
$timeout = 120;

function getClientIp () {
  $keys = array('HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED',
    'HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR');
  foreach($keys as $key) {
    if (!empty($_SERVER[$key])) {
      return $_SERVER[$key];
    }
  }
  return "UNKNOWN";
}

function logStatus ($status) {
  global $log, $input;
  $date = new DateTime();
  file_put_contents($log, sprintf("%s (%s): %s [ %s ]\n",
    $date->getTimestamp(), getClientIp(), $input, $status), FILE_APPEND | LOCK_EX);
}

function getOutput ($output, $code) {
  global $properties_translation;
  // 0 == ok, 1 == unexpected, 2 == invalid input,
  // 3 == no match, 100 == timeout
  switch ($code) {
    case 0:
      $output_template = 'Simplicial complex is %2$s with the labeling:'."\n".'%1$s';
    break;
    case 1:
      $output_template = 'Unexpected Exception: %s';
    break;
    case 2:
      $output_template = 'Invalid input Exception: %s';
    break;
    case 3:
      $output_template = 'Simplicial complex is NOT %2$s.';
    break;
    case 100:
      logStatus($status_timeout);
      throw new Exception("Script timeout exceeded.");
    default:
      throw new Exception(sprintf("Unexpected return code %s.", $code));
    break;
  }
  return sprintf(
    $output_template,
    $output,
    $properties_translation[$_POST['property']] ?? $_POST['property']
  );
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
      logStatus($status_size);
      throw new Exception("Input matrix is to big.");
    }
    $code = execute(
      "echo \"{$_POST['input']}\" | python3 $python_file --property \
      {$_POST['property']} 2>&1",
      null, $output, $output, $timeout
    );
    $output = getOutput($output, $code);
    logStatus($status_ok."(".$code.")");
  }
} catch (Exception $ex) {
  $output = sprintf("Runtime Exception: %s", $ex->getMessage());
}
$output = htmlentities($output);

$radios = "";
foreach($properties as $property) {
  $radios .= sprintf(
    '<dd><label><input type="radio" name="property" value="%s" %s/>%s</label></dd>',
    $property,
    ((isset($_POST['property']) && $_POST['property'] == $property)
      || $property == $properties[0] ? " checked" : ""),
    $properties_translation[$property] ?? $property
  );
}

// Definitions of the available properties. Keys match the $properties slugs,
// values are HTML with TeX (rendered client side by MathJax). Single quoted
// strings only, so that neither PHP nor the heredoc below eats the backslashes
// and the dollar signs.
$definitions = [
  'closed' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ is closed if there exists a vertex'
    .' labeling of $\Delta$ such that for every pair of $d$-faces $F=a_0a_1\dots a_d$ and'
    .' $G=b_0b_1\dots b_d$ (written in increasing order) with $a_i=b_i$ for some $i$, the complex'
    .' $\Delta$ contains the full $d$-skeleton of the simplex on $F \cup G$.',
  'unit-interval' =>
    'Let $\Delta$ be a pure $d$-dimensional simplicial complex with $n$ vertices. The complex'
    .' $\Delta$ is called unit-interval if there exists a labeling $1, \ldots, n$ of its vertices'
    .' such that for any $d$-face $F=a_0 a_1 \cdots a_d$ (written in increasing order) of $\Delta$,'
    .' the complex $\Delta$ contains the whole $d$-skeleton of the simplex with vertex set'
    .' $\{a_0, a_0 + 1, a_0 + 2, \ldots, a_d\}$.',
  'under-closed' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ is under-closed if there is a vertex'
    .' labeling of $\Delta$ such that for every $d$-face $F=a_0a_1\dots a_d$ (written in increasing'
    .' order) the complex $\Delta$ contains all faces of the form $a_0b_1b_2\dots b_d$ with'
    .' $b_1\leq a_1$, $b_2\leq a_2$, $\dots$, $b_d\leq a_d$.',
  'semi-closed' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ is semi-closed if there is a vertex'
    .' labeling of $\Delta$ such that for every $d$-face $F=a_0a_1\dots a_d$ (written with'
    .' $a_0 &lt; a_1 &lt; \dots &lt; a_d$) at least one of the following conditions hold:'
    .'<ol>'
    .'<li>the complex $\Delta$ contains all faces of the form $a_0b_1b_2\dots b_d$ with'
    .' $b_1\leq a_1$, $b_2\leq a_2$, $\dots$, $b_d\leq a_d$, or</li>'
    .'<li>the complex $\Delta$ contains all faces of the form $i_0i_1 \dots i_{d-1}a_d$ with'
    .' $i_0\geq a_0$, $i_1\geq a_1$, $\dots$, $i_{d-1} \geq a_{d-1}$.</li>'
    .'</ol>',
  'weakly-closed' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ is weakly-closed if there is a vertex'
    .' labeling of $\Delta$ such that for every $d$-face $F=a_0a_1\dots a_d$ (written with'
    .' $a_0 &lt; a_1 &lt; \dots &lt; a_d$) and for every $g\notin F$ with $a_0 &lt; g &lt; a_d$'
    .' there exists a $d$-face $G$ adjacent to $F$ containing $g$ such that'
    .' either $\max G \neq \max F$ or'
    .' $\min G \neq \min F$.',
  'weakly-traceable' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ on $n$ vertices is weakly-traceable if'
    .' there is a vertex labeling of $\Delta$ such that $\Delta$ contains a subset $H_{i_1}$,'
    .' $H_{i_2}$, $\dots$, $H_{i_k}$ of $\{H_1, H_2 \dots , H_{n-d} \}$ that'
    .'<ol>'
    .'<li>covers all the vertices and</li>'
    .'<li>$H_{i_{j}}$ is incident (non-empty intersection) to $H_{i_{j+1}}$ for each'
    .' $j \in \{1, 2, \dots , k-1\}$.</li>'
    .'</ol>'
    .'<p>Here $H_i$ is the facet $(i, i+1, \dots , i+d)$.</p>',
  'traceable' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ on $n$ vertices is traceable if there is a'
    .' vertex labeling of $\Delta$ such that $\Delta$ contains facets $H_1$, $H_2$, $\dots$,'
    .' $H_{n-d}$. Here $H_i$ is the facet $(i, i+1, \dots , i+d)$.',
  'weakly-hamiltonian' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ on $n$ vertices is weakly-Hamiltonian if it'
    .' has a labeling such that $\Delta$ contains a subset $H_{i_1}$, $\dots$, $H_{i_k}$ of'
    .' $\{H_1, \dots , H_n\}$ that'
    .'<ol>'
    .'<li>altogether cover all vertices,</li>'
    .'<li>$H_{i_j}$ is incident to $H_{i_{j+1}}$ for each $j \in \{1, \dots , k-1\}$,</li>'
    .'<li>$H_{i_k}$ is incident to $H_{i_1}$.</li>'
    .'</ol>'
    .'<p>Here $H_i$ is the facet $(i , i+1 , \dots , i+d)$ using modulo $n$ for anything greater'
    .' than $n$. For instance, $H_n = (1,2,3, \dots d, n)$.</p>',
  'hamiltonian' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ on $n$ vertices is Hamiltonian if there is'
    .' a vertex labeling of $\Delta$ such that $\Delta$ contains facets $H_1$, $H_2$, $\dots$,'
    .' $H_{n}$. Here $H_i$ is the facet $(i , i+1 , \dots , i+d)$ using modulo $n$ for anything'
    .' greater than $n$. For instance, $H_n = (1,2,3, \dots d, n)$.',
  'weakly-chordal' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ on $n$ vertices is weakly-chordal if it has'
    .' a labeling such that, for every two facets $F \ne G$ in $\Delta$ with the same maximum,'
    .' $\Delta$ also contains some facet $H$ such that $H \subseteq F \cup G - \{ \max F\}$.',
  'skeleton-weakly-chordal' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ is skeleton-weakly-chordal if there is a'
    .' vertex labeling of $\Delta$ such that the $k$-skeleton of $\Delta$ meets the weakly-chordal'
    .' condition for all $k\in \{1, 2, \dots, d\}$.',
  'chordal' =>
    'A pure $d$-dimensional simplicial complex $\Delta$ is E-chordal if there is a vertex labeling'
    .' of $\Delta$ such that for every pair of $d$-faces $F=a_0a_1\dots a_d$ and'
    .' $G=b_0b_1\dots b_d$ (written in increasing order) with $a_d=b_d$, the complex $\Delta$'
    .' contains the whole $d$-skeleton of the simplex on $F \cup G$.',
];
$definition_titles = [
  'chordal' => 'chordal (E-chordal)',
];

$definition_list = "";
foreach ($properties as $property) {
  if (!isset($definitions[$property])) {
    continue;
  }
  $definition_list .= sprintf(
    '<details><summary>%s</summary><div>%s</div></details>',
    $definition_titles[$property]
      ?? $properties_translation[$property]
      ?? $property,
    $definitions[$property]
  );
}

$info = [
    'version' => trim(file_get_contents('VERSION')),
];
$branch = '[detached]';
$commit = trim(file_get_contents('.git/HEAD'));
if (substr($commit, 0, 10) == 'ref: refs/') {
    $branch = $commit;
    $commit = trim(file_get_contents('.git/' . substr($branch, 5)));
}
$info += [
    'branch' => basename($branch),
    'commit' => substr($commit, 0, 7),
];
$system_info = implode(' ', $info);

// Kept out of the heredoc below: the delimiters contain dollar signs.
$mathjax = '<script>'
  .'window.MathJax = {'
  .'tex: {inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]]},'
  .'options: {enableMenu: false}'
  .'};'
  .'</script>'
  .'<script id="MathJax-script" async'
  .' src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>';

$commit_id = substr(shell_exec("git rev-parse HEAD"), 0, 7);
$python_file = file("build/$ntb_crc.py");
$heading = substr($python_file[11], 4);
$line_num = 11;
$desc = "";
while ($python_file[$line_num++] != "\n") {
  $desc .= '<p>'.substr($python_file[$line_num], 2).'</p>';
}

echo <<<EOT
<!DOCTYPE html>
<html>
  <head>
    <title>$heading</title>
    <meta charset='UTF-8'/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
      details { margin: .4em 0; }
      details > summary { cursor: pointer; font-weight: bold; }
      details > div { margin: .3em 0 .8em 1.2em; }
    </style>
    $mathjax
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
    <h2>Definitions of the available properties</h2>
    $definition_list
    <hr/>
    <ul>
      <li><a href="https://colab.research.google.com/github/martapavelka/scpc/blob/dev/scpc.ipynb">Source code on Google Colab</a> (developer version)</li>
      <li><a href="https://github.com/martapavelka/scpc">Source code on GitHub</a> (all versions)</li>
      <li>Current online version: $system_info</li>
      <li>Author: Marta Pavelka, <a href="mailto:mp@math.ku.dk">mp@math.ku.dk</a></li>
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
