<?php

require __DIR__.'/vendor/autoload.php';

use Ovh\Api;
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parse(file_get_contents(__DIR__.'/snapshot.yml'));

$ovh = new Api($config['applicationKey'], $config['applicationSecret'], 'ovh-eu', $config['consumerKey']);

foreach ($config['projects'] as $p) {
    echo '--------------------------------------------------'.PHP_EOL;
    echo 'PROJECT: '.$p['id'].PHP_EOL;

    if (isset($config['duration']) && !empty($config['duration'])) {
        $list = $ovh->get('/cloud/project/'.$p['id'].'/snapshot');

        $time = new DateTime();
        $time->sub(new DateInterval($config['duration']));
        echo 'Delete snapshots older than '.$time->format('Y-m-d H:i:s').PHP_EOL;

        $count = 0;
        foreach ($list as $snapshot) {
            $snapshot_time = new DateTime($snapshot['creationDate']);
            if ($snapshot_time < $time) {
                $ovh->delete('/cloud/project/'.$p['id'].'/snapshot/'.$snapshot['id']);
                echo 'Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')'.PHP_EOL;
                $count++;
            }
        }
        echo sprintf('%d deleted snapshot(s)', $count).PHP_EOL;
    }

    foreach ($p['instances'] as $instance) {
        $snapshot = $ovh->post('/cloud/project/'.$p['id'].'/instance/'.$instance['id'].'/snapshot', [
            'snapshotName' => $instance['name'].' ('.date('Y-m-d H:i:s').')',
        ]);
        echo 'INSTANCE: '.$instance['name'].PHP_EOL;
        print_r($snapshot);
    }

    foreach ($p['volumes'] as $volume) {
        $snapshot = $ovh->post('/cloud/project/'.$p['id'].'/volume/'.$volume['id'].'/snapshot', [
            'name' => $volume['name'].' ('.date('Y-m-d H:i:s').')',
        ]);
        echo 'VOLUME: '.$volume['name'].PHP_EOL;
        print_r($snapshot);
    }
}

exit();
