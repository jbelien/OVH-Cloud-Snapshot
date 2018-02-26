<?php

require __DIR__.'/vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Ovh\Api;
use Symfony\Component\Yaml\Yaml;

$options = getopt('', ['dry-run']);
$dryrun = isset($options['dry-run']);

$log = new Logger('snapshot');
$log->pushHandler(new StreamHandler(__DIR__.'/snapshot.log', Logger::DEBUG));
$log->pushProcessor(new PsrLogMessageProcessor());

$config = Yaml::parse(file_get_contents(__DIR__.'/snapshot.yml'));

$ovh = new Api($config['applicationKey'], $config['applicationSecret'], 'ovh-eu', $config['consumerKey']);

foreach ($config['projects'] as $p) {
    echo '--------------------------------------------------'.PHP_EOL;
    echo 'PROJECT: '.$p['id'].PHP_EOL;
    echo count($p['instances']).' INSTANCE(S)'.PHP_EOL;
    echo count($p['volumes']).' VOLUME(S)'.PHP_EOL;

    if (isset($config['duration']) && !empty($config['duration'])) {
        $time = new DateTime();
        $time->sub(new DateInterval($config['duration']));
        echo 'Delete snapshots older than '.$time->format('Y-m-d H:i:s').PHP_EOL;

        if ($dryrun !== true) {
            $log->debug('PROJECT: {project} - Delete snapshots older than '.$time->format('Y-m-d H:i:s'), [
                'project' => $p['id'],
            ]);
        }

        $count = 0;

        $list = $ovh->get('/cloud/project/'.$p['id'].'/snapshot');
        foreach ($list as $snapshot) {
            $snapshot_time = new DateTime($snapshot['creationDate']);
            if ($snapshot_time < $time) {
                if ($dryrun !== true) {
                    $delete = $ovh->delete('/cloud/project/'.$p['id'].'/snapshot/'.$snapshot['id']);
                    $log->debug('PROJECT: {project} - Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')', [
                        'project'  => $p['id'],
                        'snapshot' => $snapshot,
                        'delete'   => $delete,
                    ]);
                }

                echo 'Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')'.PHP_EOL;

                $count++;
            }
        }

        $list = $ovh->get('/cloud/project/'.$p['id'].'/volume/snapshot');
        foreach ($list as $snapshot) {
            $snapshot_time = new DateTime($snapshot['creationDate']);
            if ($snapshot_time < $time) {
                if ($dryrun !== true) {
                    $delete = $ovh->delete('/cloud/project/'.$p['id'].'/volume/snapshot/'.$snapshot['id']);
                    $log->debug('PROJECT: {project} - Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')', [
                        'project'  => $p['id'],
                        'snapshot' => $snapshot,
                        'delete'   => $delete,
                    ]);
                }

                echo 'Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')'.PHP_EOL;

                $count++;
            }
        }

        echo sprintf('%d deleted snapshot(s)', $count).PHP_EOL;
    }

    if ($dryrun !== true) {
        $log->debug('PROJECT: {project} - Create snapshot of '.count($p['instances']).' instance(s)', [
            'project' => $p['id'],
        ]);
    }

    foreach ($p['instances'] as $instance) {
        echo 'INSTANCE: '.$instance['name'].PHP_EOL;

        if ($dryrun !== true) {
            $snapshot = $ovh->post('/cloud/project/'.$p['id'].'/instance/'.$instance['id'].'/snapshot', [
                'snapshotName' => $instance['name'].' ('.date('Y-m-d H:i:s').')',
            ]);
            print_r($snapshot);

            $log->debug('PROJECT: {project} - Create snapshot instance "'.$instance['name'].'"', [
                'project'  => $p['id'],
                'instance' => $instance,
                'snapshot' => $snapshot,
            ]);
        }
    }

    if ($dryrun !== true) {
        $log->debug('PROJECT: {project} - Create snapshot of '.count($p['volumes']).' volume(s)', [
            'project' => $p['id'],
        ]);
    }

    foreach ($p['volumes'] as $volume) {
        echo 'VOLUME: '.$volume['name'].PHP_EOL;

        if ($dryrun !== true) {
            $snapshot = $ovh->post('/cloud/project/'.$p['id'].'/volume/'.$volume['id'].'/snapshot', [
                'name' => $volume['name'].' ('.date('Y-m-d H:i:s').')',
            ]);
            print_r($snapshot);

            $log->debug('PROJECT: {project} - Create snapshot volume "'.$volume['name'].'"', [
                'project'  => $p['id'],
                'volume'   => $volume,
                'snapshot' => $snapshot,
            ]);
        }
    }
}

exit();
