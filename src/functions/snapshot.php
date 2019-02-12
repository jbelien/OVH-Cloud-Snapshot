<?php

declare(strict_types=1);

use App\Snapshot;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

return function (Snapshot $app, bool $dryRun) : void {
    $log = new Logger('snapshot');
    $log->pushHandler(new StreamHandler($app->projectRoot.'logs/'.date('Ym').'.log', Logger::DEBUG));
    $log->pushProcessor(new PsrLogMessageProcessor());

    foreach ($app->config['projects'] as $project) {
        $service = $project['id'];

        $app->io->write("<question>Project: $service</question>");

        foreach ($project['instances'] ?? [] as $instance) {
            $id = $instance['id'];
            $name = $instance['name'];

            $app->io->write(sprintf('Create snapshot for instance "%s"', $name), false);

            if ($dryRun === false) {
                try {
                    // $snapshot = $app->ovh->post("/cloud/project/$service/instance/$id/snapshot", [
                    //     'snapshotName' => $name.' ('.date('Y-m-d H:i:s').')',
                    // ]);
                    $snapshot = null;

                    $app->io->write(' - <fg=green>OK</>');

                    $log->debug('PROJECT: {project} - Create snapshot instance "'.$name.'"', [
                        'project'  => $service,
                        'instance' => $instance,
                        'api'      => $snapshot,
                    ]);
                } catch (RequestException $exception) {
                    $app->io->write(sprintf(' - <fg=red>Error: %s</>', $exception->getMessage()));
                }
            } else {
                $app->io->write(' - <fg=blue>Skipped</>');
            }
        }

        foreach ($project['volumes'] ?? [] as $volume) {
            $id = $volume['id'];
            $name = $volume['name'];

            $app->io->write(sprintf('Create snapshot for volume "%s"', $name), false);

            if ($dryRun === false) {
                try {
                    // $snapshot = $app->ovh->post("/cloud/project/$service/volume/$id/snapshot", [
                    //     'snapshotName' => $name.' ('.date('Y-m-d H:i:s').')',
                    // ]);
                    $snapshot = null;

                    $app->io->write(' - <fg=green>OK</>');

                    $log->debug('PROJECT: {project} - Create snapshot instance "'.$name.'"', [
                        'project'  => $service,
                        'volume'   => $volume,
                        'api'      => $snapshot,
                    ]);
                } catch (RequestException $exception) {
                    $app->io->write(sprintf(' - <fg=red>Error: %s</>', $exception->getMessage()));
                }
            } else {
                $app->io->write(' - <fg=blue>Skipped</>');
            }
        }
    }
};
