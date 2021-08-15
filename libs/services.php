<?php
require '../autoload.php';
$Config = new Config();


$datas = array();

$available_protocols = array('tcp', 'udp');

$show_port = $Config->get('services:show_port');

if (count($Config->get('services:list')) > 0)
{
    foreach ($Config->get('services:list') as $service)
    {
        $host     = $service['host'];
        $port     = $service['port'];
        $name     = $service['name'];
        $stop     = array_key_exists('stop', $service) ? $service['stop'] : null;
        $start    = array_key_exists('start', $service) ? $service['start'] : null;
        $reload   = array_key_exists('reload', $service) ? $service['reload'] : null;
        $protocol = array_key_exists('protocol', $service) && in_array($service['protocol'], $available_protocols) ? $service['protocol'] : 'tcp';

        if (Misc::scanPort($host, $port, $protocol))
        {
            $status = 1;
        }
        else
        {
            $status = 0;
        }

        $datas[] = array(
            'port'      => $show_port === true ? $port : '',
            'name'      => $name,
            'status'    => $status,
            'stop'      => $stop,
            'start'     => $start,
            'reload'     => $reload,
        );
    }
}


echo json_encode($datas);