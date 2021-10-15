<?php

declare(strict_types=1);

use App\Snapshot;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

return function (Snapshot $app, bool $dryRun): void {
    $time = new DateTime();
    $time->sub(new DateInterval($app->config['duration']));

    $log = new Logger('snapshot');
    $log->pushHandler(new StreamHandler($app->projectRoot.'logs/'.date('Ym').'.log', Logger::DEBUG));
    $log->pushProcessor(new PsrLogMessageProcessor());

    $app->io->write(sprintf('<warning>Delete snapshots older than %s</warning>', $time->format('Y-m-d H:i:s')));

    foreach ($app->config['projects'] as $project) {
        $service = $project['id'];

        $app->io->write("<question>Project: $service</question>");

        $snapshots = $app->ovh->get("/cloud/project/$service/snapshot");
        foreach ($snapshots as $snapshot) {
            $id = $snapshot['id'];
            $snapshotTime = new DateTime($snapshot['creationDate']);

            if ($snapshotTime < $time) {
                if (isset($project['protected'], $project['protected']['instances']) && in_array($id, $project['protected']['instances'])) {
                    $app->io->write(sprintf('Delete instance snapshot "%s" (%s) - <bg=red>PROTECTED</>', $snapshot['name'], $snapshotTime->format('Y-m-d H:i:s')));
                } else {
                    $app->io->write(sprintf('<comment>Delete instance snapshot "%s" (%s)</comment>', $snapshot['name'], $snapshotTime->format('Y-m-d H:i:s')), false);

                    if ($dryRun === false) {
                        try {
                            // $delete = $app->ovh->delete("/cloud/project/$service/snapshot/$id");
                            $delete = null;

                            $app->io->write(' - <fg=green>OK</>');

                            $log->debug('PROJECT: {project} - Delete snapshot instance "'.$name.'" ('.$snapshotTime->format('Y-m-d H:i:s').')', [
                                'project'  => $service,
                                'snapshot' => $snapshot,
                                'api'      => $delete,
                            ]);
                        } catch (RequestException $exception) {
                            $app->io->write(sprintf(' - <fg=red>Error: %s</>', $exception->getMessage()));
                        }
                    } else {
                        $app->io->write(' - <fg=blue>Skipped</>');
                    }
                }
            }
        }

        $snapshots = $app->ovh->get("/cloud/project/$service/volume/snapshot");
        foreach ($snapshots as $snapshot) {
            $id = $snapshot['id'];
            $snapshotTime = new DateTime($snapshot['creationDate']);

            if ($snapshotTime < $time) {
                if (isset($project['protected'], $project['protected']['volumes']) && in_array($id, $project['protected']['volumes'])) {
                    $app->io->write(sprintf('Delete volume snapshot "%s" (%s) - <bg=red>PROTECTED</>', $snapshot['name'], $snapshotTime->format('Y-m-d H:i:s')));
                } else {
                    $app->io->write(sprintf('<comment>Delete volume snapshot "%s" (%s)</comment>', $snapshot['name'], $snapshotTime->format('Y-m-d H:i:s')), false);

                    if ($dryRun === false) {
                        try {
                            // $delete = $app->ovh->delete("/cloud/project/$service/volume/snapshot/$id");
                            $delete = null;

                            $app->io->write(' - <fg=green>OK</>');

                            $log->debug('PROJECT: {project} - Delete snapshot volume "'.$name.'" ('.$snapshotTime->format('Y-m-d H:i:s').')', [
                                'project'  => $service,
                                'snapshot' => $snapshot,
                                'api'      => $delete,
                            ]);
                        } catch (RequestException $exception) {
                            $app->io->write(sprintf(' - <fg=red>Error: %s</>', $exception->getMessage()));
                        }
                    } else {
                        $app->io->write(' - <fg=blue>Skipped</>');
                    }
                }
            }
        }
    }
};
