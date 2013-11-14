<?php

$url = 'https://maps.googleapis.com/maps/api/directions/json?sensor=true&';

$url .= 'origin=' . $_POST['from_lat'] . ',' . $_POST['from_lng'] . '&';
$url .= 'destination=' . $_POST['to_lat'] . ',' . $_POST['to_lng'] . '&';
$url .= 'mode=walking&';
$url .= 'alternatives=false&';

$data = file_get_contents($url);

print($data);