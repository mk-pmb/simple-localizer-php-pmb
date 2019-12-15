<?php


function simple_config_file2hash($fn, $sepchar = '=') {
  $zeilen = file($fn);
  if ($zeilen === false) { return false; }

  $hash = array();
  foreach ($zeilen as $zeile) {
    $zeile = trim($zeile, "\n\r");
    $teile = explode($sepchar, $zeile, 2);
    $var = $teile[0];
    $wert = (isset($teile[1]) ? $teile[1] : '');
    if (substr($wert, -4) == "\\EOL") $wert = substr($wert, 0, -4);
    if (isset($hash[$var])) { $hash[$var] .= "\n" . $wert; } else { $hash[$var] = $wert; }
  }

  return $hash;
}


function simple_config_hash2file($fn, $hash, $sepchar = '=') {
  $text = '';
  $linejoiner = "\n" . $var . $sepchar;

  foreach ($hash as $var => $wert) {
    $wert = trim($wert, "\r");
    $text .= $var . $sepchar . str_replace("\n", $linejoiner, $var) . "\n";
  }

  return file_put_contents($fn, $text);
}
