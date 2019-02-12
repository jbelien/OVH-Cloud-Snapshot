<?php

declare(strict_types=1);

use App\Snapshot;
use Composer\Script\Event;

return function (Event $event) {
    $app = new Snapshot($event->getIO(), $event->getComposer());

    foreach ($app->config['projects'] as $project) {
        $service = $project['id'];

        $app->io->write("<question>Project: $service</question>");

        $snapshots = $app->ovh->get("/cloud/project/$service/snapshot");

        foreach ($snapshots as $snapshot) {
            $app->io->write(sprintf('<comment>- INSTANCE: %s</comment> (%s)', $snapshot['name'], $snapshot['id']));
            $app->io->write(sprintf('    Date: %s', $snapshot['creationDate']));
            $app->io->write(sprintf('    Size: %.2f Go', $snapshot['size']));
            if (in_array($snapshot['id'], $project['protected']['instances'] ?? [])) {
                $app->io->write('    <bg=red>PROTECTED</>');
            }
        }

        $snapshots = $app->ovh->get("/cloud/project/$service/volume/snapshot");

        foreach ($snapshots as $snapshot) {
            $app->io->write(sprintf('<comment>- VOLUME: %s</comment> (%s)', $snapshot['name'], $snapshot['id']));
            $app->io->write(sprintf('    Date: %s', $snapshot['creationDate']));
            $app->io->write(sprintf('    Size: %.2f Go', $snapshot['size']));
            if (in_array($snapshot['id'], $project['protected']['volumes'] ?? [])) {
                $app->io->write('    <bg=red>PROTECTED</>');
            }
        }
    }
};
