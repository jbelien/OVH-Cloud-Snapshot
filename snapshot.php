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
    echo "\033[36m--------------------------------------------------\033[0m".PHP_EOL;
    echo sprintf("\033[36mPROJECT: %s\033[0m", $p['id']).PHP_EOL;
    if (isset($p['instances']) && is_array($p['instances'])) {
        echo sprintf("\033[36m%d INSTANCE(S)\033[0m", count($p['instances'])).PHP_EOL;
    }
    if (isset($p['volumes']) && is_array($p['volumes'])) {
        echo sprintf("\033[36m%d VOLUME(S)\033[0m", count($p['volumes'])).PHP_EOL;
    }

    if (isset($config['duration']) && !empty($config['duration'])) {
        $time = new DateTime();
        $time->sub(new DateInterval($config['duration']));
        echo sprintf("\033[33mDelete snapshots older than %s\033[0m", $time->format('Y-m-d H:i:s')).PHP_EOL;

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
                    echo sprintf("\033[37mDelete snapshot \"%s\" (%s)\033[0m", $snapshot['name'], $snapshot_time->format('Y-m-d H:i:s'));
                    echo sprintf("\033[37m - Skipped (protected) !\033[0m").PHP_EOL;
                } else {
                    echo sprintf("\033[32mDelete snapshot \"%s\" (%s)\033[0m", $snapshot['name'], $snapshot_time->format('Y-m-d H:i:s')).PHP_EOL;

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

                        echo sprintf("\033[31m%s\033[0m", $exception->getMessage()).PHP_EOL;
                    }
                }
            }
        }

        $list = $ovh->get('/cloud/project/'.$p['id'].'/volume/snapshot');
        foreach ($list as $snapshot) {
            $snapshot_time = new DateTime($snapshot['creationDate']);

            if ($snapshot_time < $time && $dryrun !== true) {
                if (isset($p['protected'], $p['protected']['volumes']) && in_array($snapshot['id'], $p['protected']['volumes'])) {
                    echo sprintf("\033[37mDelete snapshot \"%s\" (%s)\033[0m", $snapshot['name'], $snapshot_time->format('Y-m-d H:i:s'));
                    echo sprintf("\033[37m - Skipped (protected) !\033[0m").PHP_EOL;
                } else {
                    echo sprintf("\033[32mDelete snapshot \"%s\" (%s)\033[0m", $snapshot['name'], $snapshot_time->format('Y-m-d H:i:s')).PHP_EOL;

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

                        echo sprintf("\033[31m%s\033[0m", $exception->getMessage()).PHP_EOL;
                    }
                }
            }
        }

        echo sprintf("\033[32m%d deleted snapshot(s)\033[0m", $count).PHP_EOL;
    }

    if (isset($p['instances']) && is_array($p['instances'])) {
        if ($dryrun !== true) {
            $log->debug('PROJECT: {project} - Created snapshot of '.count($p['instances']).' instance(s)', [
                'project' => $p['id'],
            ]);
        }

        foreach ($p['instances'] as $instance) {
            echo sprintf("\033[32mINSTANCE: %s\033[0m", $instance['name']).PHP_EOL;

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

                    echo sprintf("\033[31m%s\033[0m", $exception->getMessage()).PHP_EOL;
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
            echo sprintf("\033[VOLUME: %s\033[0m", $volume['name']).PHP_EOL;

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

                    echo sprintf("\033[31m%s\033[0m", $exception->getMessage()).PHP_EOL;
                }
            }
        }
    }
}

exit();
