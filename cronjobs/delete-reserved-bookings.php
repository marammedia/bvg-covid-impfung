<?php

$root = dirname(dirname(__FILE__));
require_once $root.'/src/config.php';
require_once $root.'/src/mysqli.php';

$sql = 'DELETE FROM appointments
        WHERE statusId = 2
        AND FROM_UNIXTIME(dateCreated) < DATE_SUB(NOW(), INTERVAL 15 MINUTE)';
$conn->query($sql);