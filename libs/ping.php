<?php
require '../autoload.php';
$Config = new Config();


$datas = array();

if (count($Config->get('ping:hosts')) > 0)
    $hosts = $Config->get('ping:hosts');
else
    $hosts = array('google.com', 'wikipedia.org');

$remote_addr = $_SERVER["REMOTE_ADDR"];
array_push($hosts, $remote_addr);
array_unshift($hosts, array_pop($hosts));

foreach ($hosts as $host)
{
    exec('/bin/ping -qc 1 '.$host.' | awk -F/ \'/^(rtt|round-trip)/ { print $5 }\'', $result);

    if (!isset($result[0]))
    {
        $result[0] = "+Infinity";
    }

    $datas[] = array(
        'host' => $host,
        'ping' => $result[0],
    );

    unset($result);
}

$datas[0]["host"] = "Remote client ({$remote_addr})";

echo json_encode($datas);
