<?php

require_once('conf.php');
require_once('includes/class.DB.php');

$db = new DataBase(defined('DB_COLLATION') ? DB_COLLATION : false, DB_INF_NAME, false, DB_INF_HOST, DB_INF_USER, DB_INF_PASSWORD);

$places = $db->getRow('places', array('id', 'title', 'address', 'url', 'lat', 'lng', 'phone'), '1', 'AND', 'id', 'ASC', '', array(), true);

if(isset($_GET['geojson'])){
	$_all = '{"type":"FeatureCollection","features":[%%markers%%]}';
	$_marker = '{"type":"Feature","geometry":{"type":"Point", "coordinates": ["%%lng%%","%%lat%%"]},"properties":{"title":"%%title%%","address":"%%address%%","phone":"%%phone%%","url":"%%url%%","marker-size":"large","marker-color":"#000","marker-symbol":"bicycle"}}';
}else if(isset($_GET['js'])){
	$_all = 'var addressPoints = [%%markers%%];';
	$_marker = '[%%lat%%, %%lng%%, "%%content%%"]';
}else
	die('Wrong data type');

if($places)
	foreach($places as &$place){
		$place = str_replace(
			array(
				'%%lat%%',
				'%%lng%%',
				'%%title%%',
				'%%address%%',
				'%%phone%%',
				'%%url%%'
			),
			array(
				$place['lat'],
				$place['lng'],
				$place['title'],
				$place['address'],
				$place['phone'],
				$place['url']
			),
			$_marker
		);
	}

$all = str_replace('%%markers%%', implode(',', $places), $_all);

print($all);
