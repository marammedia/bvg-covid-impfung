<?php

function id($x) {
  return $x;
}

function idx(array $array, $key, $default = null) {
  if (isset($array[$key])) {
    return $array[$key];
  }

  if ($default === null || array_key_exists($key, $array)) {
    return null;
  }

  return $default;
}

function tag($tag, array $attributes = array(), $content = null) {
  if (!empty($attributes['href'])) {
    $href = (string)$attributes['href'];

    if (isset($href[0])) {
      $is_internal_href = ($href[0] == '/');

      if ($is_internal_href) {
        $attributes['href'] = './'.ltrim($href, '/');
      }
    }
  }

  if (isset($attributes['active'])) {
    if (!$attributes['active']) {
      unset($attributes['active']);
    }
  }

  if (!strcasecmp($tag, 'form')) {
    if (isset($attributes['action'])) {
      $attributes['action'] = './'.ltrim($attributes['action'], '/');
    }
    if (!isset($attributes['method'])) {
      $attributes['method'] = 'POST';
    }
  }

  static $self_closing_tags = array(
    'area'    => true,
    'base'    => true,
    'br'      => true,
    'col'     => true,
    'command' => true,
    'embed'   => true,
    'frame'   => true,
    'hr'      => true,
    'img'     => true,
    'input'   => true,
    'keygen'  => true,
    'link'    => true,
    'meta'    => true,
    'param'   => true,
    'source'  => true,
    'track'   => true,
    'wbr'     => true,
  );

  $attr_string = null;
  foreach ($attributes as $k => $v) {
    if ($v === null) {
      continue;
    }
    $v = escape_html($v);
    $attr_string .= ' '.$k.'="'.$v.'"';
  }

  if ($content === null) {
    if (isset($self_closing_tags[$tag])) {
      return '<'.$tag.$attr_string.' />';
    }
  } else {
    $content = escape_html($content);
  }

  return '<'.$tag.$attr_string.'>'.$content.'</'.$tag.'>';
}


function escape_html($string) {
  if (is_array($string)) {
    $result = '';
    foreach ($string as $item) {
      $result .= escape_html($item);
    }
    return $result;
  }

  return $string;
}


function escape_uri($string) {
  return str_replace('%2F', '/', rawurlencode($string));
}

function fetchReturn($data, $err = null) {
  $response = array(
    'error' => $err,
    'data' => $data
  );
  
  echo str_replace(
    array('<', '>'),
    array('\u003c', '\u003e'),
    json_encode($response));

  exit();
}

function formatDate(DateTime $datetime) {
  static $weekdays;
  static $months;
  if (!$weekdays) { // w
    $weekdays = array(
      0 => 'Sonntag',
      1 => 'Montag',
      2 => 'Dienstag',
      3 => 'Mittwoch',
      4 => 'Donnerstag',
      5 => 'Freitag',
      6 => 'Samstag',
    );
  }
  if (!$months) { // n
    $months = array(
      1 => 'Januar',
      2 => 'Februar',
      3 => 'März',
      4 => 'April',
      5 => 'Mai',
      6 => 'Juni',
      7 => 'Juli',
      8 => 'August',
      9 => 'September',
      10 => 'Oktober',
      11 => 'November',
      12 => 'Dezember',
    );
  }

  $formated = $datetime->format('Y-n-d-w');
  list($year, $month, $day, $weekday) = explode('-', $formated);

  return sprintf(
    '%s, %d. %s %d',
    $weekdays[$weekday],
    $day,
    $months[$month],
    $year);
}


function booking_form() {
  $fields = array(
    array(
      'danumber' => array(
        'name' => 'DA-Nummer',
        'type' => 'text',
        'maxlength' => 6,
        'pattern' => '\b[A-Za-zÖÄÜöäü0]{0,1}[0-9]{1,5}\b',#'[\d]+',
        'inputmode' => 'text',
        'required' => true,
      ),
    ),
    array(
      'firstname' => array(
        'name' => 'Vorname',
        'type' => 'text',
        'autocomplete' => 'given-name',
        'maxlength' => 100,
        'pattern' => '[\p{L} \-]+',
        'required' => true,
      ),
      'lastname' => array(
        'name' => 'Nachname',
        'type' => 'text',
        'autocomplete' => 'family-name',
        'maxlength' => 100,
        'pattern' => '[\p{L} \-]+',
        'required' => true,
      ),
    ),
    array(
      'postalcode' => array(
        'name' => 'Postleitzahl',
        'type' => 'text',
        'autocomplete' => 'postal-code',
        'maxlength' => 5,
        'pattern' => '[\d]+',
        'inputmode' => 'numeric',
        'required' => true,
      ),
    ),
    array(
      'email' => array(
        'name' => 'E-Mail',
        'type' => 'email',
        'autocomplete' => 'email',
        'required' => true,
      ),
      'phone' => array(
        'name' => 'Telefon',
        'type' => 'tel',
        'autocomplete' => 'tel',
        'maxlength' => 20,
        'pattern' => '[\d \-\+]+',
        'required' => true,
      ),
    )
  );

  $items = array();
  foreach ($fields as $groups) {
    $stacks = array();
    foreach ($groups as $key => $value) {
      $label = tag(
        'label',
        array(
          'for' => $key,
        ),
        $value['name']);

      unset($value['name']);

      $basics = array();
      $basics['id'] = $key;
      $basics['name'] = $key;

      $attrs = array_merge($basics, $value);

      if ($attrs['type'] == 'select') {
        $options = array();
        foreach ($attrs['options'] as $k => $v) {
          $options[] = tag(
            'option',
            array('value' => $k),
            $v);
        }

        unset($attrs['type']);
        unset($attrs['options']);

        $input = tag(
          'select',
          $attrs,
          $options);
      } else {
        $input = tag(
          'input',
          $attrs);
      }

      $stacks[] = tag(
        'div',
        array('class' => 'input-stack'),
        array($label, $input));
    }

    $num = count($stacks);

    $classes = array();
    $classes[] = 'fieldset-item';
    if ($num > 1) {
      $classes[] = 'repeat-'.$num;
    }

    $items[] = tag(
      'div',
      array('class' => implode(' ', $classes)),
      $stacks);
  }    

  return tag(
    'form',
    array('class' => 'dialog-form'),
    $items);
}