<?php

function getActionLink($action, $record) {
  return $_SERVER['PHP_SELF'].'?action='.$action.'&record='.$record;
}

function getLink($action, $record) {
  return '<a href="'.getActionLink($action, $record).'"'.
         ($action == 'delete' ?
          ' onclick="return confirm(\'Are you sure you would like to delete this record?\')"' :
          '').
         '>'.$action.'</a>';
}

function isReadOnly($device, $key) {
  return $device !== ''
      && in_array($key, array('key', 'typeOfObject', 'app', 'timestamp',
                              'underReview', 'deviceID'), true);
}

function getFormAction($action, $limit = 1, $record = null, $device = null) {
  return $_SERVER['PHP_SELF'].'?action='.urlencode($action).
                   ($device ? '&device='.urlencode($device) : '').
                   ($record ? '&record='.urlencode($record) : '').
                    ($limit ? '&limit='.urlencode($limit) : '');
}
