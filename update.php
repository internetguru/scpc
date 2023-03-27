<?php

echo "<pre><code>";
echo $pull = shell_exec("/usr/bin/git pull 2>&1");
if ($pull == "Already up to date.\n") {
  exit;
}
echo shell_exec("/usr/local/bin/jupyter nbconvert --to python scpc.ipynb --output-dir=build 2>&1");
