<?php

declare (strict_types = 1);

use App\Snapshot;

return function (Snapshot $app) : void {
    $p = $app->io->select(
        'Project:',
        array_column($app->config['projects'], 'id'),
        false
    );
    $project = $app->config['projects'][intval($p)];
    $service = $project['id'];

    $type = $app->io->select(
        'Type:',
        ['Instance', 'Volume'],
        false
    );
    $type = intval($type);

    switch ($type) {
        case 0:
            $s = $app->io->select(
                'Instance snapshot:',
                array_column($project['instances'] ?? [], 'name'),
                false
            );

            $confirm = $app->io->askConfirmation(sprintf('<question>Are you sure you want to create a snapshot of instance "%s" (y/n) ?</question>', $project['instances'][$s]['name']), false);

            if ($confirm === true) {
                $id = $project['instances'][$s]['id'];
                $name = $project['instances'][$s]['name'];

                // $snapshot = $app->ovh->post("/cloud/project/$service/instance/$id/snapshot", [
                //     'snapshotName' => $name.' ('.date('Y-m-d H:i:s').')',
                // ]);
            }
            break;
        case 1:
            $s = $app->io->select(
                'Volume snapshot:',
                array_column($project['volumes'] ?? [], 'name'),
                false
            );

            $confirm = $app->io->askConfirmation(sprintf('<question>Are you sure you want to create a snapshot of volume "%s" (y/n) ?</question>', $project['volumes'][$s]['name']), false);

            if ($confirm === true) {
                $id = $project['volumes'][$s]['id'];
                $name = $project['instances'][$s]['name'];

                // $snapshot = $app->ovh->post("/cloud/project/$service/volume/$id/snapshot", [
                //     'snapshotName' => $name.' ('.date('Y-m-d H:i:s').')',
                // ]);
            }
            break;
    }


};
