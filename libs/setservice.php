<?php

if(isset($_GET['id']))
{
	$id = $_GET['id'] ;

	require '../autoload.php';
	$Config = new Config();

	$datas = array();

	$available_protocols = array('tcp', 'udp');
	$show_port = $Config->get('services:show_port');

	if (count($Config->get('services:list')) > 0)
	{
		$services = $Config->get('services:list');
		if(isset($services[$id]))
		{
			$service = $services[$id];

			$host     = $service['host'];
			$port     = $service['port'];
			if (in_array("stop", $service)) {
				$stop = $service['stop'];
			} else {
				$stop = null;
			}
			if (in_array("reload", $service)) {
				$reload = $service['reload'];
			} else {
				$reload = null;
			}
			if (in_array("start", $service)) {
				$start = $service['start'];
			} else {
				$start = null;
			}

			$protocol = isset($service['protocol']) && in_array($service['protocol'], $available_protocols) ? $service['protocol'] : 'tcp';
			if (Misc::scanPort($host, $port, $protocol))
			{
				$command = ($stop != null ? $stop : $reload);
				echo exec('sudo '.$command);
				if ($stop != null)
				{
				  echo ' stop demande ';
				}
				else
				{
				  echo ' reload demande ';
				}
			}
			else
			{
				echo exec('sudo '.$start);
				echo ' start demande ';
			}
		}
	}
}


