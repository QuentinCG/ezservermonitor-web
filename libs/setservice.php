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
				exec('sudo '.$command, $output_exec);
				if ($stop != null)
				{
				  echo ' Stop demande sur port '.$port.': '.$command;
				  error_log('INFO: Stop demande sur port '.$port.': '.$command);
				}
				else
				{
				  echo ' Reload demande sur port '.$port.': '.$command;
				  error_log('INFO: Stop demande sur port '.$port.': '.$command);
				}

				echo 'Resultat de la commande: '.$output_exec;
				error_log('INFO: Resultat de la commande: '.$output_exec);
			}
			else
			{
				echo exec('sudo '.$start, $output_exec);
				echo ' Start demande sur port '.$port.': '.$command;
				error_log('INFO: Stop demande sur port '.$port.': '.$command);

				echo 'Resultat de la commande: '.$output_exec;
				error_log('INFO: Resultat de la commande: '.$output_exec);
			}


		}
	}
}


