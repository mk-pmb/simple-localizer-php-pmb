<?php

$tpl_path = (string)@getenv('SLOC_TPL_PATH');
if ($tpl_path === '') { $tpl_path = '.dev'; }
$tpl_path = str_replace("\\", '/', $tpl_path);
if (!preg_match('!^([a-zA-Z]:|)/!', $tpl_path)) {
  $tpl_path = getcwd() . '/' . $tpl_path;
}
if (substr($tpl_path, -1, 1) !== '/') { $tpl_path .= '/'; }
$lib_path = dirname(__FILE__) . '/';

require_once $lib_path . 'lib_simple_config_files.php';
require_once $lib_path . 'lib_defusers.php';
require_once $lib_path . 'lib_string_tools.php';

logmsg('SimpleLocalizer gestartet am ' . date('d.m.Y') . '.');

$jobconfig = array();
if (file_exists($tpl_path . 'sloc.conf')) {
  $jobconfig = simple_config_file2hash($tpl_path . 'sloc.conf');
}

set_default_array_value($jobconfig, 'lang', 'german');
set_default_array_value($jobconfig, 'files', '*.php');
set_default_array_value($jobconfig, 'haxx0r-detect0r', 'detect_hacking_attempt();');
set_default_array_value($jobconfig, 'html.all_entities', '0');
set_default_array_value($jobconfig, 'voc.var-defuser', ($jobconfig['html.all_entities'] > 0 ?
  'htmlentities' : 'htmlspecialchars') . '($*, ENT_QUOTES)');

$custom_warnings = array();
foreach ($jobconfig as $cwort => $cwarn) {
  if (substr($cwort, 0, 5) == 'warn:') { $custom_warnings[substr($cwort, 5)] = $cwarn; }
}

$transfn = $jobconfig['lang'] . '.voc';
logmsg("Lese Uebersetzungsdatei '$transfn'...");
if (!file_exists($tpl_path . $transfn)) { die(' Fehler: Datei nicht gefunden!'); }
$translation = simple_config_file2hash($tpl_path . $transfn);
$voccnt = count(array_keys($translation));
logmsg("OK, $voccnt Vokabeln gefunden.");

$voc_used = array();
foreach ($translation as $voc => $text) {
  if (trim($text) != '') { $voc_used[$voc] = 0; }
}

$fcnt = 0;
$log_warn_cnt = 0;
$log_error_cnt = 0;

$dirs = explode("\n", $jobconfig['files']);
foreach ($dirs as $dir) {
  $files = glob($tpl_path . $dir);
  $reldir = dirname($dir) . '/';

  foreach ($files as $fn) {
    $fnbase = basename($fn);
    if (substr($fnbase, 0, 1) != '_') {
      logmsg("Uebersetze $reldir$fnbase...");
      $code = trim(file_get_contents($fn), "\r");

      if (($fnbase != 'index.php') && (!beginnt_mit($fnbase, 'lib_'))) {
        if (!str_isin($jobconfig['haxx0r-detect0r'], $code)) {
          log_warn('kein "' . $jobconfig['haxx0r-detect0r'] . '" gefunden!');
        }
      }

      if (str_isin_ic(':TODO:', $code)) { log_warn('":TODO:" gefunden!'); }
      foreach ($custom_warnings as $cwort => $cwarn) {
        if (str_isin_ic($cwort, $code)) { log_warn($cwarn); }
      }

      $vocbase = str_replace(array('mod_', 'lib_', '.php'), '', $fnbase) . ':';
      $code = preg_replace_callback('!%([\'";,]?)([x=]?)voc ([\w\-\. :]+)%!', 'translate', $code);

      $orig_code = false;
      if (file_exists($reldir . $fnbase)) { $orig_code = file_get_contents($reldir . $fnbase); }
      if ($code !== $orig_code) { file_put_contents($reldir . $fnbase, $code); }
      $fcnt++;
    }
  }
}

ksort($voc_used);
foreach ($voc_used as $voc => $used) {
  $text = $translation[$voc];
  if ($used < 1) { log_warn("Vokabel '$voc' ('$text') wurde nicht benutzt."); }
}

logmsg("Fertig. $fcnt Dateien uebersetzt, $log_error_cnt Fehler, $log_warn_cnt Warnung(en).");

function translate($matches) {
  $qstart = $qend = '';
  $phpmode = true;

  # <;> and <,> are aliases for <"> and <'> in order to ease syntax highlighting of source files.
  switch ($matches[1]) {
    case '': $phpmode = false; break;  # no quoting at all, implies use outside of php script tags
    case '"': case ';': $qstart = $qend = '"'; break;
    case "'": case ',': $qstart = $qend = "'"; break;
    default: throw new Exception('Unknown quoting style ' . $symbol . ' (#' . ord($symbol) . ').');
  }

  $escape_type = $matches[2];
  $xml_mode = ($escape_type == 'x');
  $params = explode(' ', $matches[3]);
  $wort = $params[0];

  if (!($phpmode || $xml_mode))
    { return log_err("Vokabel '$wort': Mindestens ein Ausgabemodus muss benutzt werden!"); }
  if ($escape_type == '') { log_warn("veraltete Syntax: Vokabel '$wort' ohne Escaping-Angabe"); }

  global $vocbase;
  $text = find_translation(array($wort, $vocbase . $wort));

  if ($text === '') {
    logmsg('  - Fehler: Keine Vokabel für "' . $wort . '" gefunden!');
    $text = '[Fehlende Vokabel: ' . $wort . ']';
  }

  if ($xml_mode) {
    if ($GLOBALS['jobconfig']['html.all_entities'] > 0) {
      $text = safeentities($text);
    } else {
      $text = xmldefuse($text);
    }
  }
  if ($phpmode) { $text = sq_defuse($text); }
  # zuerst XML escapen, weil ENT_QUOTES die Apostrophe schon escaped haben könnte.

  $pcnt = count($params);
  $jobcfg = $GLOBALS['jobconfig'];

  for ($pidx = 1; $pidx < $pcnt; $pidx++) {
    $wodurch = '';
    if ($params[$pidx] != '-') {
      $wodurch = str_replace('*', $params[$pidx], getifset($jobcfg, 'voc.var-defuser:'
        . ($escape_type == '=' ? '' : $escape_type), $jobcfg['voc.var-defuser']));
      if ($phpmode) {
        $wodurch = "$qend . $wodurch . $qstart";
      } else {
        $wodurch = '<' . '?php echo ' . $wodurch . '; ?' . '>';
        # PHP would succeed to parse the string even if its tags pop up inside in one piece. they're only
        # crippled to make it easier for other programs to syntax-highlight this file.
      }
    }
    $text = str_replace('%[' . $pidx . ']', $wodurch, $text);
  }

  if ($phpmode && (!$xml_mode)) { $text = $qend . $text . $qstart; }

  return $text;
}


function find_translation(array $words) {
  global $translation, $voc_used;
  foreach ($words as $word) {
    if ((string)@$translation[$word] !== '') {
      $voc_used[$word] = (int)@$voc_used[$word] + 1;
      return $translation[$word];
    }
  }
  return '';
}


function set_default_array_value(&$array, $key, $default) {
  if (!isset($array[$key])) { $array[$key] = $default; }
}


function log_warn($msg) {
  logmsg('  - Warnung: ' . $msg);
  $GLOBALS['log_warn_cnt']++;
  return "[WARN: $msg]";
}

function log_err($msg) {
  logmsg('  - Fehler: ' . $msg);
  $GLOBALS['log_error_cnt']++;
  return "[ERR: $msg]";
}

function logmsg($msg) { echo date('H:i:s'), ' ', $msg, "\r\n"; }
