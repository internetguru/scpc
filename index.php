<?php

$input = $_POST['input'] ?? "1 2 3 4&#10;2 3 4 5&#10;5 6 7 8";

$properities = ["all", "under-closed", "semi-closed", "weakly-closed", "d-chordal", "closed", "almost-closed"];
$options = "";
foreach($properities as $property) {
  $options .= "<option"
    . (isset($_POST['property']) && $_POST['property'] == $property ? " selected" : "")
    . ">$property</option>";
}

$output = "---";
if (isset($_POST['input']) && isset($_POST['property'])) {
  $output = shell_exec("echo \"{$_POST['input']}\" | /usr/local/bin/python3.7 scpc.py --property {$_POST['property']} 2>&1");
}

$commit_id = substr(shell_exec("/usr/local/bin/git rev-parse HEAD"), 0, 7);

echo <<<EOT
<html>
  <head>
    <title>Simplicial Complex Property Check</title>
    <meta charset='UTF-8'/>
  </head>
  <body>
    <h1>Simplicial Complex Property Check</h1>
    <p>This program provides simplicial complex checks on under-closed, semi-closed, weakly-closed, d-chodral, closed, and almost-closed properties. To use it, simply enter a list of facets of a simplicial complex and select desired property.</p>
    <h2>Program Input</h2>
    <form method="POST" action="">
      <dl>
        <dt>Input matrix</dt>
	<dd><textarea name="input" rows="10" cols="20">$input</textarea></dd>
	<dt>Properities</dt>
        <dd><select name="property">$options</select></dd>
        <dt>Send form</dt>
        <dd><input type="submit" value="Submit" /></dd>
      </dl>
    </form>
    <h2>Program Output</h2>
    <pre><code>$output</code></pre>
    <hr/>
    <ul>
      <li><a href="https://colab.research.google.com/github/martapavelka/scpc/blob/main/scpc.ipynb">Source code on Google Colab (developer version)</a></li>
      <li><a href="https://github.com/martapavelka/scpc">Source code on GitHub (all versions)</a></li>
      <li>Current version commit id: $commit_id</li>
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


