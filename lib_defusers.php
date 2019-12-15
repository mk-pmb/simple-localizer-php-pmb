<?php

function sq_defuse($text)
{
  return str_replace(array("\\", "'"), array("\\\\", "\\'"), $text);
}
