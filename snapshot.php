<?php
// php -c /etc/php/7.0/fpm/php.ini /home/admin/ovh/snapshot.php

require __DIR__ . '/vendor/autoload.php';

use Ovh\Api;
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parse(file_get_contents('snapshot.yml'));
//var_dump($config);

$ovh = new Api($config['applicationKey'], $config['applicationSecret'], 'ovh-eu', $config['consumerKey']);

foreach ($config['projects'] as $p) {
  echo '--------------------------------------------------'.PHP_EOL;
  echo 'PROJECT: '.$p['id'].PHP_EOL;

  foreach ($p['instances'] as $instance) {
    $snapshot = $ovh->post('/cloud/project/'.$p['id'].'/instance/'.$instance['id'].'/snapshot', array(
      'snapshotName' => $instance['name'].' ('.date('Y-m-d H:i:s').')'
    ));
    echo 'INSTANCE: '.$instance['name'].PHP_EOL;
    print_r($snapshot);
  }

  foreach ($p['volumes'] as $volume) {
    $snapshot = $ovh->post('/cloud/project/'.$p['id'].'/volume/'.$volume['id'].'/snapshot', array(
      'name' => $volume['name'].' ('.date('Y-m-d H:i:s').')'
    ));
    echo 'VOLUME: '.$volume['name'].PHP_EOL;
    print_r($snapshot);
  }
}

exit();
