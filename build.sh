#!/bin/bash
# -*- coding: utf-8, tab-width: 2 -*-
SELFPATH="$(readlink -m "$0"/..)"


function main () {
  # cd "$SELFPATH" || return $?

  local CFG_FN=sloc.conf
  [ -f "$CFG_FN" ] || cd "$1"
  [ -f "$CFG_FN" ] || return 10$(echo "E: $CFG_FN missing, flinching." >&2)

  grep -qPe '\r' "$CFG_FN" -m 1 && return 30$(
    echo "E: $CFG_FN has CR in it, flinching." >&2)

  local TPL_PATH=
  detect_tpl_path || return $?
  if [ ! -d "$TPL_PATH" ]; then
    echo "E: template path seems to not exist: $TPL_PATH" >&2
    echo "I: working directory is $PWD" >&2
    return 1
  fi
  check_unsupported_hooks || return $?

  chmod u+w *.php
  run_hooks prepare || return $?

  SLOC_TPL_PATH="$TPL_PATH" php5 "$SELFPATH"/build.php 2>&1 \
    | tee "$TPL_PATH"/sloc.log

  run_hooks cleanup || return $?
  chmod a-w *.php

  return 0
}


function detect_tpl_path () {
  TPL_PATH="$SLOC_TPL_PATH"
  [ -n "$TPL_PATH" ] && return 0

  local DEST_PATH=
  if [ -f "$CFG_FN" ]; then
    DEST_PATH="$(grep "$CFG_FN" -Pe '^path:dest=' -m 1 | cut -d = -sf 2-)"
    if [ -n "$DEST_PATH" ]; then
      TPL_PATH="$PWD"
      [ -d "$DEST_PATH" ] || echo "W: dest path is not a dir: $DEST_PATH" >&2
      cd "$DEST_PATH"
      return $?
    fi
  fi

  TPL_PATH=".dev"
  case "$PWD" in
    */"$TPL_PATH" )
      cd "${PWD%$TPL_PATH}"
      return $?;;
  esac

  echo 'E: In order to use SimpleLocalizer, you have to run it from' \
    "a directory whose path ends in '/$TPL_PATH' or that contains" \
    "a subdirectory named '$TPL_PATH'." >&2
  return 2
}


function check_unsupported_hooks () {
  local HOOK_NAME=
  local HOOK_FN=
  local FAILED_HOOKS=()
  for HOOK_NAME in prepare cleanup; do
    HOOK_FN="sloc-$HOOK_NAME.cmd"
    # ^-- sloc on windows really used a dash there, to avoid multiple dots.
    [ -f "$TPL_PATH/$HOOK_FN" ] && FAILED_HOOKS+=( "$HOOK_FN" )
  done
  [ -z "${FAILED_HOOKS[*]}" ] && return 0
  echo "E: found these unsupported hooks: ${FAILED_HOOKS[*]}" >&2
  return 82
}


function run_hooks () {
  local HOOK_NAME="$1"
  local HOOK_FEXT=
  local HOOK_FN=
  local HOOK_PROG=
  local RETVAL=
  for HOOK_FEXT in sh=bash; do
    HOOK_PROG="${HOOK_FEXT#*=}"
    HOOK_FEXT="${HOOK_FEXT%%=*}"
    HOOK_FN="$TPL_PATH/sloc.$HOOK_NAME.$HOOK_FEXT"
    if [ -f "$HOOK_FN" ]; then
      $HOOK_PROG "$HOOK_FN"
      RETVAL=$?
      [ $RETVAL == 0 ] || return $RETVAL$(
        echo "E: Hook $HOOK_FN failed with rv=$RETVAL." >&2)
    fi
  done
  return 0
}


















main "$@"; exit $?
