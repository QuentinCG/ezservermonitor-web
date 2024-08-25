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
			$stop = isset($service['stop']) ? $service['stop'] : null;
			$reload = isset($service['reload']) ? $service['reload'] : null;
			$start = $service['start'];

			$protocol = isset($service['protocol']) && in_array($service['protocol'], $available_protocols) ? $service['protocol'] : 'tcp';
			if (Misc::scanPort($host, $port, $protocol))
			{
				$command = ($stop != null ? $stop : $reload);
				if ($stop != null)
				{
				  echo ' Stop demande sur port '.$port.': '.$command;
				  error_log("INFO: Stop demande sur port $port: $command");
				}
				else
				{
				  echo ' Reload demande sur port '.$port.': '.$command;
				  error_log("INFO: Reload demande sur port $port: $command");
				}
				exec("sudo $command", $output_exec);

				$display_output = var_dump(implode(",", $output_exec));
				echo 'Resultat de la commande: '.$display_output;
				error_log("INFO: Resultat de la commande: $display_output");
			}
			else
			{
				echo ' Start demande sur port '.$port.': '.$start;
				error_log("INFO: Start demande sur port $port: $start");

				echo exec('sudo '.$start, $output_exec);

				$display_output = var_dump(implode(",", $output_exec));
				echo 'Resultat de la commande: '.$display_output;
				error_log("INFO: Resultat de la commande: $display_output");
			}


		}
	}
}


