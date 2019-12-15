<?php
#
#   Copyright (C) 2007-2014 Marcel Krause <marcel_k@web.de>
#
#
#   This file is part of lib_string_tools.
#
#   lib_string_tools is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   lib_string_tools is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with lib_string_tools; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#   or download it from http://www.gnu.org/copyleft/gpl.html .
#


function safeentities($text) {
  $text = (string)$text;
  if ($text === '') { return $text; }
  foreach (array('UTF-8', 'ISO-8859-15') as $charset) {
    $enc_try = @htmlentities($text, ENT_QUOTES, $charset);
    if ('' !== (string)$enc_try) { break; }
  }
  $text = $enc_try; $enc_try = NULL;
  $text = preg_replace("/&(amp;)+([\w#]+);/i", "&\\2;", $text);
  return $text;
}

function xmldefuse($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'ISO-8859-1');
  # ^-- for h.sp.ch., ISO-* is compatible to UTF-8, as opposed to
  #     the other way around.
}

function getifset($array, $key, $def) {
  if (!isset($array)) { return $def; }
  if (!is_array($array)) { return $def; }
  if (!isset($array[$key])) { return $def; }
  return $array[$key];
}

function requestvar($var, $def) {
  $wert = getifset($_REQUEST, $var, $def);
  $wert = str_replace("\r", "", $wert);
  if (get_magic_quotes_gpc()) {
    if (is_array($wert)) {
      array_walk_recursive($wert, 'stripslashes__2param_callback');
    } else {
      $wert = stripslashes((string)$wert);
    }
  }
  return $wert;
}

function stripslashes__2param_callback($wert, $dummy) {
  return stripslashes((string)$wert);
}

function erste_gesetzte_requestvar($moegliche, $default) {
  while(count($moegliche) > 0) {
    $vn = array_pop($moegliche);
    if (isset($_REQUEST[$vn])) { return $vn; }
  }
  return $default;
}

function str_kuerzen($text, $maxlen, $vorne) {
  $len = strlen($text);
  if ($len <= $maxlen) { return $text; }
  return substr($text, 0, $vorne) . '...' . substr($text, ($len - $maxlen + 3 + $vorne));
}

function iif($c, $t, $f) {
  if ($c) { return $t; }
  return $f;
}

function replace_vars($text, $vars) {
  $keys = array_keys($vars);
  $anzahl = count($keys);

  for($idx = 0; $idx < $anzahl; $idx++) {
    $key = $keys[$idx];
    $text = str_replace('%' . $key . '%', $vars[$key], $text);
  }

  return $text;
}

function is_unsig_int($text) { return preg_match("/^[0-9]+$/", $text); }

function beginnt_mit($text, $moegl_anfang)
  { return (substr($text, 0, strlen($moegl_anfang)) == $moegl_anfang); }

function str_isin($nadel, $heu) { return (strpos($heu, $nadel) !== false); }
function str_isin_ic($nadel, $heu) { return (stripos($heu, $nadel) !== false); }

function endet_auf($text, $moegl_ende) {
  $len = strlen($moegl_ende);
  return (substr($text, -$len, $len) == $moegl_ende);
}

function say($text) { echo $text; echo "\n"; }

function zahl_mind_nstellig($zahl, $n) {
  $diff = $n - strlen($zahl);
  for ($idx = 0; $idx < $diff; $idx++) { $zahl = '0' . $zahl; }
  return $zahl;
}

function nice_filesize($bytes) {
  $einheiten = 'KMGT';

  $einheit = 'Byte';
  if ($bytes != 1) { $einheit .= 's'; }

  while( ($bytes >= 1024) and ($einheiten != '') ) {
    $bytes = $bytes / 1024;
    $einheit = substr($einheiten, 0, 1) . 'B';
    $einheiten = substr($einheiten, 1);
  }

  return round($bytes, 0) . ' ' . $einheit;
}
