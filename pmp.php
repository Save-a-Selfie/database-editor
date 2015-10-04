<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<title>Save a Selfie info editor</title>

<script src="//maps.googleapis.com/maps/api/js"></script>
<script src="//google-maps-utility-library-v3.googlecode.com/svn/trunk/geolocationmarker/src/geolocationmarker-compiled.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="map.js"></script>

<link rel="stylesheet" type="text/css" href="pmp.css"/>
</head>
<body>
<h1>Save a Selfie info editor</h1>

<?php
include 'util.php';
include 'preferences.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'default';
$device = isset($_REQUEST['device']) ? $_REQUEST['device'] : null;
$record = isset($_REQUEST['record']) ? $_REQUEST['record'] : null;

session_start();

if (isset($_REQUEST['access'])) {
  $_SESSION['device'] = '';
  $_SESSION['has_access'] = false;
  $has_access = false;
  if ($_REQUEST['access'] == $token) {
    $_SESSION['has_access'] = true;
  }
}
if ($device != '') {
  $_SESSION['has_access'] = true;
}
if ($_SESSION['has_access']) {
  $has_access = true; 
}

if (!$has_access) {
  echo 'Sorry, you don\'t have access to this resource.';
  exit;
}

if (isset ($_REQUEST['device'])) {
  $device = $_REQUEST['device'];
}
if ($device == '') {
  $device = $_SESSION['device'];
}
if ($device != '') {
  $_SESSION['device'] = $device;
}

if (isset ($_REQUEST['limit'])) {
  $limit = $_REQUEST['limit'];
}

$db = new PDO($connectString, $dbuser, $dbpassword);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

echo '<form method="get" action="'.getFormAction($action).'">

Records to View: <input name="limit" size="3" value="'.$limit.'"/>
<input type="submit" value="Show" />
</form>';

$showList = true;

switch ($action) {
case 'delete':
  $stmt = $db->prepare('delete from selfie_info where `key` = ? limit 1');
  $stmt->execute(array($record));

  echo '<p id="deleted">You\'ve deleted '.$stmt->rowCount().' rows.</p>';
  break;

case 'edit':
  $showList = false;

  $stmt = $db->prepare(
    'select `key`, typeOfObject, app, thumbnail, standard, latitude,'.
          ' longitude, caption, timestamp, underReview, deviceID'.
     ' from selfie_info'.
    ' where `key` = ? limit 1');
  $stmt->execute(array($record));
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as $row) {
    echo '<div id="tableAndMap"><div id="modify">';
    echo '<form method="post" action="'.getFormAction('savechanges', $limit, $record, $device).'"><table>';
    foreach ($row as $key => $value) {
      if (strpos($value, '.jpg') !== false) {
        $fielddata = '<img class="'.$key.'" src="'.$imageRootURL.$value.'"/>';
      } else {
        $readonly = isReadOnly($device, $key) ? ' readonly="readonly"' : '';
        $clean_data = htmlentities($value, ENT_QUOTES);
        if ($key == 'caption') {
          $fielddata = '<textarea id="'.$key.'" name="'.$key.'">'.$clean_data.'</textarea>';
        } else {
          $fielddata = '<input id="'.$key.'" name="'.$key.'" value="'.$clean_data.'"'.$readonly.'/>';
        }
      }
      echo '<tr><td>'.$key.'</td><td>'.$fielddata.'</td></tr>';
    }
    $longitude = $row['longitude'];
    $latitude  = $row['latitude'];
    echo '</table><input type="submit" value="Save Changes"/></form>'
       . '</div><div id="modifyMap"/></div>'
       . '</div>'
       . '<p id="modifyMapNotice">Drag and drop marker to change GPS coordinates of photo / device.</p>'
       . '<script>var marker = [ {\'latitude\': '.$latitude.', \'longitude\': '.$longitude.'} ];</script>';
  }
  break;
case 'savechanges':
  $stmt = $db->prepare(
    'update selfie_info'.
      ' set `key` = ?, typeOfObject = ?, app = ?, latitude = ?, longitude = ?,'.
          ' caption = ?, timestamp = ?, underReview = ?, deviceID = ?'.
    ' where `key` = ? limit 1');
  $stmt->execute(array($_REQUEST['key'], $_REQUEST['typeOfObject'],
                       $_REQUEST['app'], $_REQUEST['latitude'],
                       $_REQUEST['longitude'], $_REQUEST['caption'],
                       $_REQUEST['timestamp'], $_REQUEST['underReview'],
                       $_REQUEST['deviceID'], $record));

  echo '<p id="saved">Your changes were applied to '.$stmt->rowCount().' rows.</p>';
  break;
}

if ($showList) {
  if ($device == '') {
    $stmt = $db->prepare(
      'select `key`, typeOfObject, thumbnail, latitude,'.
            ' longitude, caption, unix_timestamp(timestamp) as timestamp,'.
            ' underReview, deviceID'.
       ' from selfie_info'.
      ' order by timestamp desc limit ?');
    $stmt->execute(array($limit));
  } else {
    $stmt = $db->prepare(
      'select `key`, typeOfObject, thumbnail, latitude,'.
            ' longitude, caption, unix_timestamp(timestamp) as timestamp,'.
            ' underReview, deviceID'.
       ' from selfie_info'.
      ' where deviceID = ?'.
      ' order by timestamp desc limit ?');
    $stmt->execute(array($device, $limit));
  }
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo '<table><thead><tr><th class="delete">delete</th><th class="edit">edit</th>';

  foreach ($rows[0] as $key => $value) {
    echo '<th class='.$key.'>'.$key.'</th>';
  }

  echo '<th class="edit">edit</th></tr></thead><tbody>';

  $n = 0;
  $markers = '';

  foreach ($rows as $row) {
    echo '<tr'.($row['underReview'] == 'y' ? ' class="fadeRow"' : '').'>'
       . '<td class="del">'
       . ($device == '' ? getLink('delete', $row['key']) : '')
       . '</td><td class="mod">'
       . getLink('edit', $row['key'])
       . '</td>';

    foreach ($row as $key => $value) {
      $display = $value;

      if (strpos($value, '.jpg') !== false) {
        $display = '<img src="'.$imageRootURL.$value.'"/>';
      } else {
        switch ($key) {
        case 'typeOfObject':
          $display = '<img src="'.$deviceIcons[$value].'"/>';
          break;
        case 'timestamp':
          $display = date("d F Y H:i:s", $value);
          break;
        default:
          $display =
            str_replace (array('%20', '%2C'),
                         array(' ', ','),
                         htmlentities ($display, ENT_QUOTES));
          break;
        }
      }
      echo '<td class='.$key.'>'.$display.'</td>';
    }
    echo '<td class="map"><div class="SASMap" id="map'.$n.'"></div></td>';
    echo '</tr>';

    $markers .= "'$n': {'latitude': '".$row['latitude']."', 'longitude': '".$row['longitude']."'},";
    $n++;
  }

  echo '</tbody></table>';
  echo '<script type="text/javascript">var marker={'.$markers.'};</script>';
}
?>
</body>
</html>
