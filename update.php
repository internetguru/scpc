<?php

echo "<pre><code>";

$pull = shell_exec("/usr/local/bin/git pull 2>&1");
if ($pull != "Already up to date.\n") {
  echo shell_exec("/usr/local/bin/jupyter nbconvert --to python scpc.ipynb 2>&1 && echo done || echo failed");
} else {
  echo "$pull";
}
