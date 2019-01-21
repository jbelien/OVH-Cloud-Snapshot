<?php

require __DIR__.'/vendor/autoload.php';

use GuzzleHttp\Exception\RequestException;
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
    if (isset($p['instances']) && is_array($p['instances'])) {
        echo count($p['instances']).' INSTANCE(S)'.PHP_EOL;
    }
    if (isset($p['volumes']) && is_array($p['volumes'])) {
        echo count($p['volumes']).' VOLUME(S)'.PHP_EOL;
    }

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

            if ($snapshot_time < $time && $dryrun !== true) {
                if (isset($p['protected'], $p['protected']['instances']) && in_array($snapshot['id'], $p['protected']['instances'])) {
                    echo 'Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')';
                    echo ' - Skipped (protected) !'.PHP_EOL;
                } else {
                    echo 'Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')'.PHP_EOL;

                    try {
                        $url = '/cloud/project/'.$p['id'].'/snapshot/'.$snapshot['id'];
                        $delete = $ovh->delete($url);

                        $log->debug('PROJECT: {project} - Deleted snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')', [
                            'project'  => $p['id'],
                            'snapshot' => $snapshot,
                            'delete'   => $delete,
                            'url'      => $url,
                        ]);

                        $count++;
                    } catch (RequestException $exception) {
                        $log->debug($exception->getMessage(), [
                            'project'   => $p['id'],
                            'snapshot'  => $snapshot,
                            'delete'    => $delete ?? null,
                            'url'       => $url,
                            'exception' => $exception,
                        ]);

                        echo $exception->getMessage().PHP_EOL;
                    }
                }
            }
        }

        $list = $ovh->get('/cloud/project/'.$p['id'].'/volume/snapshot');
        foreach ($list as $snapshot) {
            $snapshot_time = new DateTime($snapshot['creationDate']);

            if ($snapshot_time < $time && $dryrun !== true) {
                if (isset($p['protected'], $p['protected']['volumes']) && in_array($snapshot['id'], $p['protected']['volumes'])) {
                    echo 'Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')';
                    echo ' - Skipped (protected) !'.PHP_EOL;
                } else {
                    echo 'Delete snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')'.PHP_EOL;

                    try {
                        $url = '/cloud/project/'.$p['id'].'/volume/snapshot/'.$snapshot['id'];
                        $delete = $ovh->delete($url);

                        $log->debug('PROJECT: {project} - Deleted snapshot "'.$snapshot['name'].'" ('.$snapshot_time->format('Y-m-d H:i:s').')', [
                            'project'  => $p['id'],
                            'snapshot' => $snapshot,
                            'delete'   => $delete,
                            'url'      => $url,
                        ]);

                        $count++;
                    } catch (RequestException $exception) {
                        $log->error($exception->getMessage(), [
                            'project'   => $p['id'],
                            'snapshot'  => $snapshot,
                            'delete'    => $delete ?? null,
                            'url'       => $url,
                            'exception' => $exception,
                        ]);

                        echo $exception->getMessage().PHP_EOL;
                    }
                }
            }
        }

        echo sprintf('%d deleted snapshot(s)', $count).PHP_EOL;
    }

    if (isset($p['instances']) && is_array($p['instances'])) {
        if ($dryrun !== true) {
            $log->debug('PROJECT: {project} - Created snapshot of '.count($p['instances']).' instance(s)', [
                'project' => $p['id'],
            ]);
        }

        foreach ($p['instances'] as $instance) {
            echo 'INSTANCE: '.$instance['name'].PHP_EOL;

            if ($dryrun !== true) {
                try {
                    $url = '/cloud/project/'.$p['id'].'/instance/'.$instance['id'].'/snapshot';
                    $snapshot = $ovh->post($url, [
                        'snapshotName' => $instance['name'].' ('.date('Y-m-d H:i:s').')',
                    ]);

                    $log->debug('PROJECT: {project} - Created snapshot instance "'.$instance['name'].'"', [
                        'project'  => $p['id'],
                        'instance' => $instance,
                        'snapshot' => $snapshot,
                        'url'      => $url,
                    ]);
                } catch (RequestException $exception) {
                    $log->error($exception->getMessage(), [
                        'project'   => $p['id'],
                        'instance'  => $instance,
                        'snapshot'  => $snapshot ?? null,
                        'url'       => $url,
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }

    if (isset($p['volumes']) && is_array($p['volumes'])) {
        if ($dryrun !== true) {
            $log->debug('PROJECT: {project} - Create snapshot of '.count($p['volumes']).' volume(s)', [
                'project' => $p['id'],
            ]);
        }

        foreach ($p['volumes'] as $volume) {
            echo 'VOLUME: '.$volume['name'].PHP_EOL;

            if ($dryrun !== true) {
                try {
                    $url = '/cloud/project/'.$p['id'].'/volume/'.$volume['id'].'/snapshot';
                    $snapshot = $ovh->post($url, [
                        'name' => $volume['name'].' ('.date('Y-m-d H:i:s').')',
                    ]);

                    $log->debug('PROJECT: {project} - Create snapshot volume "'.$volume['name'].'"', [
                        'project'  => $p['id'],
                        'volume'   => $volume,
                        'snapshot' => $snapshot,
                        'url'      => $url,
                    ]);
                } catch (RequestException $exception) {
                    $log->error($exception->getMessage(), [
                        'project'   => $p['id'],
                        'instance'  => $instance,
                        'snapshot'  => $snapshot ?? null,
                        'url'       => $url,
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }
}

exit();
