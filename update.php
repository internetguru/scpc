<?php

echo "<pre><code>";
echo $pull = shell_exec("/usr/local/bin/git pull");
if ($pull == "Already up to date.\n") {
  exit;
}
echo shell_exec("/usr/local/bin/jupyter nbconvert --to python scpc.ipynb");
