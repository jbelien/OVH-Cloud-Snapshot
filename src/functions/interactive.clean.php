<?php

declare(strict_types=1);

use App\Snapshot;

return function (Snapshot $app): void {
    $p = $app->io->select(
        'Project:',
        array_column($app->config['projects'], 'id'),
        false
    );
    $project = $app->config['projects'][intval($p)];
    $service = $project['id'];

    $snapshotsInstance = $app->ovh->get("/cloud/project/$service/snapshot");
    $snapshotsVolume = $app->ovh->get("/cloud/project/$service/volume/snapshot");

    if (count($snapshotsInstance) > 0 && count($snapshotsVolume) > 0) {
        $type = $app->io->select(
            'Type:',
            ['Instance', 'Volume'],
            false
        );
        $type = intval($type);
    } elseif (count($snapshotsInstance) > 0) {
        $type = 0;
    } elseif (count($snapshotsVolume) > 0) {
        $type = 1;
    }

    switch ($type) {
        case 0:
            $snapshots = $snapshotsInstance;

            $s = $app->io->select(
                'Instance snapshot:',
                array_column($snapshots, 'name'),
                false
            );
            break;

        case 1:
            $snapshots = $snapshotsVolume;

            $s = $app->io->select(
                'Volume snapshot:',
                array_column($snapshots, 'name'),
                false
            );
            break;
    }

    $protected = (($type === 0 && in_array($snapshots[$s]['id'], $project['protected']['instances'] ?? [])) || ($type === 1 && in_array($snapshots[$s]['id'], $project['protected']['volumes'] ?? [])));

    if ($protected === true) {
        $confirm = $app->io->askConfirmation(sprintf('<bg=red;options=bold>Are you sure you want to delete the protected "%s" snapshot (y/n) ?</>', $snapshots[$s]['name']), false);
    } else {
        $confirm = $app->io->askConfirmation(sprintf('<question>Are you sure you want to delete the "%s" snapshot (y/n) ?</question>', $snapshots[$s]['name']), false);
    }

    if ($confirm === true) {
        $id = $snapshot['id'];
        switch ($type) {
            case 0:
                // $delete = $app->ovh->delete("/cloud/project/$service/snapshot/$id");
                break;
            case 1:
                // $delete = $app->ovh->delete("/cloud/project/$service/volume/snapshot/$id");
                break;
        }
    }
};
