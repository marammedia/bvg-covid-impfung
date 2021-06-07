<?php

$root = dirname(dirname(__FILE__));
require_once $root.'/src/config.php';
require_once $root.'/src/mysqli.php';
require_once $root.'/src/utils.php';

require_once $root.'/src/Encryption.php';

header('Content-Type: application/json');

$timezone = new DateTimeZone('Europe/Berlin');

$action = isset($_GET['a']) ? $_GET['a'] : null;
if (!$action) {
  // ToDo: Header Error 404
  exit();
}

switch ($action) {
  // Change Date
  case 'changedate':
    $date = isset($_GET['d']) ? (int)$_GET['d'] : null;
    if (!$date) {
      // Header Error 404
      exit();
    }

    $starttime = new DateTime($date);
    $starttime->setTimezone($timezone);
    $endtime = clone $starttime;

    $sql = sprintf(
      'SELECT
          a.appointmentHash,
          s.statusClass
        FROM appointments AS a
        INNER JOIN appointments_status AS s ON (s.id = a.statusId)
        WHERE DATE(FROM_UNIXTIME(a.appointment)) = "%s"
        ORDER BY a.locationId ASC, a.appointment ASC',
      $conn->real_escape_string($starttime->format('Y-m-d')));
    $ret = $conn->query($sql);

    $appointments = array();
    while ($row = $ret->fetch_assoc()) {
      $appointments[$row['appointmentHash']] = $row['statusClass'];
    }
        
    $sql = sprintf(
      'SELECT
          l.id,
          l.locationName,
          le.epochStart,
          le.epochEnd,
          le.dailyVaccinations
        FROM locations AS l
        INNER JOIN locations_epoch AS le ON (le.locationId = l.id)
        WHERE le.epochDate = "%s"
        ORDER BY l.locationName ASC',
      $conn->real_escape_string($starttime->format('Y-m-d')));
    $ret = $conn->query($sql);

    $first_open = null;
    $last_closed = null;

    $locations = array();
    while ($row = $ret->fetch_assoc()) {
      $locations[] = $row;

      if ($first_open === null ||
          $first_open > $row['epochStart']) {
        /* $first_open = new DateTime($row['epochStart']);
        $first_open->format('Hi'); */
        $first_open = $row['epochStart'];
      }
      if ($last_closed === null ||
          $last_closed < $row['epochEnd']) {
          #$last_closed = new DateTime($row['epochEnd']);
          $last_closed = $row['epochEnd'];
      }
    }

    if (!$locations) {
      fetchReturn('Keine Standorte oder Zeitfenster verfügbar.');
    }

    $th = array();
    $th[] = tag('th', array());
    foreach ($locations as $location) {
      $th[] = tag(
        'th',
        array(),
        array(
          $location['locationName'],
          tag('br', array()),
          tag('p', array(), $location['epochStart'].' - '.$location['epochEnd'])
        ));
    }

    $tr = array();
    $tr[] = tag(
      'tr',
      array(),
      $th);
    
    list($fh, $fm, $fs) = explode(':', $first_open, 3);
    list($lh, $lm, $ls) = explode(':', $last_closed, 3);

    $num = 0;
    $starttime->setTime($fh, $fm, $fs);
    $endtime->setTime($lh, $lm, $ls);
    while ($starttime < $endtime) {
      $td = array();

      $td[] = tag(
        'td',
        array('class' => 'calendar-hour'),
        $starttime->format('H:i'));
      
      foreach ($locations as $location) {
        $time = clone $starttime;

        $start = new DateTime($location['epochStart']);
        $end = new DateTime($location['epochEnd']);
        if ($time->format('His') < $start->format('His') ||
            $time->format('His') >= $end->format('His')) {
          $td[] = tag('td', array());
          continue;
        }

        $duration = $start->diff($end);
        $duration_in_min = ($duration->h * 60 + $duration->i);
        $per_hour = round($location['dailyVaccinations'] / ($duration_in_min / 60));

        $columns = array();

        $cells = array();
        $ii = 0;
        for ($ii = 0; $ii < $per_hour; $ii++) {
          if (++$num > $location['dailyVaccinations']) {
            break;
          }

          $timestamp = $time->getTimestamp();

          $crypt = new Encryption();
          $hash = $crypt->encryptString($timestamp.'-'.$location['id'].'-'.$ii);

          $ajax = true;
          $classes = array();
          $classes[] = 'appointment';
          if (isset($appointments[$hash])) {
            $ajax = false;
            $classes[] = $appointments[$hash];
          } else {
            $classes[] = 'is-available';
          }

          $cells[] = tag(
            'div',
            array(
              'class' => implode(' ', $classes),
              'data-ajax' => $ajax,
              'data-hash' => $hash,
              'data-dialog' => 'dialog-booking',
            ),
            $time->format('H:i'));

          $time->modify('+'.floor(60 / $per_hour * 60).' seconds');
          //$time->modify('+2 minutes');
          //$time->modify('+30 seconds');
        }

        $columns[] = tag(
          'div',
          array('class' => 'appointments'),
          $cells);        

        $td[] = tag(
          'td',
          array(),
          $columns);
      }

      $tr[] = tag(
        'tr',
        array(),
        $td);

      $starttime->modify('+1 hour');
    }

    $tr[] = tag(
      'tr',
      array(),
      $th);

    $response = tag(
      'table',
      array('class' => 'calendar-view'),
      $tr);
    break;


  case 'reserve':
    $hash = isset($_GET['h']) ? $_GET['h'] : null;
    if (!$hash) {
      // ToDo: Header Error 404
      exit();
    }

    $crypt = new Encryption();
    $decrypted = $crypt->decryptString($hash);
    list($timestamp, $location, $worker) = explode('-', $decrypted);

    $sql = sprintf(
      'INSERT INTO appointments (locationId, appointment, appointmentHash, statusId, dateCreated)
        VALUES (%d, %d, "%s", 2, UNIX_TIMESTAMP())',
      $conn->real_escape_string($location),
      $conn->real_escape_string($timestamp),
      $conn->real_escape_string($hash));
    $conn->query($sql);

    $response = array('status' => 'OK');
    break;


  case 'release':
    $hash = isset($_GET['h']) ? $_GET['h'] : null;
    if (!$hash) {
      // ToDo: Header Error 404
      exit();
    }

    $crypt = new Encryption();
    $decrypted = $crypt->decryptString($hash);
    list($timestamp, $location, $worker) = explode('-', $decrypted);

    $sql = sprintf(
      'DELETE FROM appointments WHERE appointmentHash = "%s"',
      $conn->real_escape_string($hash));
    $conn->query($sql);

    $response = array('status' => 'OK');
    break;


  case 'proband':
    $hash = isset($_GET['h']) ? $_GET['h'] : null;
    $errors = array();
    if (!$hash) {
      // Header Error 404
      exit();
    }

    $crypt = new Encryption();

    $data = array();
    $post = $_POST;
    if (!$post) {
      // ToDo: Header Error 404
      exit();
    }

    foreach ($post as $k => $v) {
      $data[$k] = trim($v);
    }

    $v_danumber = substr(preg_replace('/[^0-9]/', '', $data['danumber']), -5);
    $v_lastname = $data['lastname'];
    $v_firstname = $data['firstname'];
    $v_email = strtolower($data['email']);
    $v_phone = preg_replace('/[^0-9]{10,20}/', '', $data['phone']);
    $v_postalcode = preg_replace('/[^0-9]{5}/', '', $data['postalcode']);

    if (!$v_danumber) {
      $errors[] = 'Geben Sie eine gültige Dienstausweisnummer ein.';
    } else if (!preg_match('/[\p{L} \-]+/', $v_lastname)) {
      $errors[] = 'Der eingegeben Nachname enthält ungültige Zeichen.';
    } else if (strlen($v_lastname) < 2) {
      $errors[] = 'Geben Sie Ihren vollständigen Nachnamen ein.';
    } else if (!preg_match('/[\p{L} \-]+/', $v_firstname)) {
      $errors[] = 'Der eingegeben Vorname enthält ungültige Zeichen.';
    } else if (strlen($v_firstname) < 2) {
      $errors[] = 'Geben Sie Ihren vollständigen Vornamen ein.';
    } else if (!$v_phone) {
      $errors[] = 'Geben Sie eine gültige Telefonnummer ein.'.$v_phone;
    } else if (!$v_postalcode) {
      $errors[] = 'Geben Sie eine gültige Postleitzahl ein.';
    } else if (!filter_var($v_email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Geben Sie ein gültige E-Mail Adresse ein.';
    }

    if ($errors) {
      $response = array('errors' => $errors);
    } else {
      $v_danumber = $crypt->encryptString($v_danumber);
      $v_lastname = $crypt->encryptString($v_lastname);
      $v_firstname = $crypt->encryptString($v_firstname);
      $v_email = $crypt->encryptString($v_email);
      $v_phone = $crypt->encryptString($v_phone);
      $v_postalcode = $crypt->encryptString($v_postalcode);

      $v_danumber = $conn->real_escape_string($v_danumber);
      $v_lastname = $conn->real_escape_string($v_lastname);
      $v_firstname = $conn->real_escape_string($v_firstname);
      $v_email = $conn->real_escape_string($v_email);
      $v_phone = $conn->real_escape_string($v_phone);
      $v_postalcode = $conn->real_escape_string($v_postalcode);
      
      $sql = sprintf(
        'INSERT INTO probands
          (daNumber, lastName, firstName, email, phone, postalcode, dateCreated)
          VALUES ("%s", "%s", "%s", "%s", "%s", "%s", UNIX_TIMESTAMP())',
        $v_danumber,
        $v_lastname,
        $v_firstname,
        $v_email,
        $v_phone,
        $v_postalcode);
      $conn->query($sql);
  
      $last_id = $conn->insert_id;
  
      $map = '0123456789'.
             'abcdefghij'.
             'klmnopqrst'.
             'uvwxyzABCD'.
             'EFGHIJKLMN'.
             'OPQRSTUVWX'.
             'YZ';
      $code = substr(str_shuffle($map), 0, 8);
  
      $sql = sprintf(
        'UPDATE appointments SET
            statusId = 3,
            probandId = %d,
            appointmentCode = "%s"
          WHERE appointmentHash = "%s"
          LIMIT 1',
        $last_id,
        $code,
        $hash);
      $conn->query($sql);
  
      $response = array('status' => 'OK');
    }
    break;


  case 'savebooking':
    $hash = isset($_GET['h']) ? $_GET['h'] : null;
    if (!$hash) {
      // ToDo: Header Error 404
      exit();
    }

    $sql = sprintf(
      'UPDATE appointments SET
          statusId = 3
        WHERE appointmentHash = "%s"
        LIMIT 1',
          $hash);
    $conn->query($sql);

    $response = array('status' => 'OK');
    break;


  case 'bookingsummary':
    $hash = isset($_GET['h']) ? $_GET['h'] : null;
    if (!$hash) {
      // ToDo: eader Error 404
      exit();
    }

    $sql = sprintf(
      'SELECT
          a.appointment,
          a.appointmentCode,
          l.locationName,
          l.locationAddress
        FROM appointments AS a
        INNER JOIN locations AS l ON (l.id = a.locationId)
        WHERE a.appointmentHash = "%s"
        LIMIT 1',
      $conn->real_escape_string($hash));
    $ret = $conn->query($sql);
    $row = $ret->fetch_assoc();

    $date = new DateTime('@'.$row['appointment']);
    $date->setTimezone($timezone);

    $fields = array(
      'summary-date' => formatDate($date),
      'summary-time' => $date->format("H:i").' Uhr',
      'summary-location' => $row['locationName'],
      'summary-address' => $row['locationAddress'],
      'summary-code' => $row['appointmentCode'],
    );

    $response = array(
      'status' => 'OK',
      'fields' => $fields,
    );
    break;


  case 'code': 
    $errors = array();
    $crypt = new Encryption();

    $data = array();
    $post = $_POST;
    if (!$post) {
      // ToDo: Header Error 404
      #exit();
    }

    /* $post = array(
      'danumber' => 72308,
      'code' => 'CdORw6tY'
    ); */

    foreach ($post as $k => $v) {
      $data[$k] = trim($v);
    }

    $v_danumber = isset($data['danumber']) ? $data['danumber'] : $_GET['danumber'];
    $v_code = isset($data['code']) ? $data['code']  : $_GET['code'];
    $v_danumber = substr(preg_replace('/[^0-9]/', '', $data['danumber']), -5);
    $v_code = preg_replace('/[^a-zA-Z0-9]{8}/', '', $data['code']);

    if (!$v_danumber) {
      $errors[] = 'Geben Sie eine gültige Dienstausweisnummer ein.';
    } else if (strlen($v_code) != 8) {
      $errors[] = 'Geben Sie Ihren 8-stelligen Buchungscode ein.';
    } else {
      $v_danumber = $crypt->encryptString($v_danumber);

      $sql = sprintf(
        'SELECT *
          FROM appointments AS ap
          INNER JOIN probands AS p ON (p.id = ap.probandId)
          WHERE ap.appointmentCode = "%s"
          AND p.daNumber = "%s"
          LIMIT 1',
          $v_code,
          $v_danumber);
      $ret = $conn->query($sql);
      $row = $ret->fetch_assoc();

      if (!$row) {
        $errors[] = 'Es wurde keine gültige Buchung zu den Angaben gefunden.';
      } else {
        $datetime = new DateTime('@'.$row['appointment']);
        $datetime->setTimezone($timezone);

        $fields = array(
          'details-firstname' => $crypt->decryptString($row['firstName']),
          'details-lastname' => $crypt->decryptString($row['lastName']),
          'details-email' => $crypt->decryptString($row['email']),
          'details-phone' => $crypt->decryptString($row['phone']),
          'details-date' => $datetime->format('d.m.Y'),
          'details-time' => $datetime->format('H:i'),
        );

        $response = array(
          'status' => 'OK',
          'fields' => $fields,
        );
      }
    }

    if ($errors) {
      $response = array('errors' => $errors);
    } else {
      /* $sql = sprintf(
        'DELETE FROM pobands WHERE daNumber = "%s"',
        $conn->real_escape_string($v_danumber));
      $conn->query($sql); */
    }
  break;
}

if (!$response) {
  // ToDo: Header Error 404
  exit();
}

fetchReturn($response);