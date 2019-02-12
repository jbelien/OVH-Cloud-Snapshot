<?php

declare(strict_types=1);

namespace App;

// Not sure why it doesn't work without this...
// Fatal error: Uncaught Error: Call to undefined function GuzzleHttp\choose_handler() in vendor\guzzlehttp\guzzle\src\HandlerStack.php:40
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Ovh\Api;
use Symfony\Component\Yaml\Yaml;

class Snapshot
{
    /** @var array */
    private $config;

    /** @var Composer */
    private $composer;

    /** @var string Path to this file. */
    private $appSource;

    /** @var IOInterface */
    private $io;

    /** @var string */
    private $projectRoot;

    /** @var Api */
    private $ovh;

    public function __construct(IOInterface $io, Composer $composer)
    {
        $this->io = $io;
        $this->composer = $composer;

        $composerFile = Factory::getComposerFile();

        $this->projectRoot = realpath(dirname($composerFile)) ?? '';
        $this->projectRoot = rtrim($this->projectRoot, '/\\').'/';

        $this->appSource = realpath(__DIR__).'/';

        $this->config = Yaml::parse(file_get_contents($this->projectRoot.'config/snapshot.yml'));

        $this->ovh = new Api($this->config['applicationKey'], $this->config['applicationSecret'], 'ovh-eu', $this->config['consumerKey']);
    }

    public static function lookup(Event $event) : void
    {
        (require('functions/lookup.php'))($event);
    }

    public static function interactive(Event $event) : void
    {
        $app = new self($event->getIO(), $event->getComposer());

        $snapshot = $app->io->askConfirmation('<question>Do you want to create new snapshots (y/n) ?</question>', false);

        if ($snapshot === true) {
            (require('functions/interactive.create.php'))($app);
        }

        $clean = $app->io->askConfirmation('<question>Do you want to delete snapshots (y/n) ?</question>', false);

        if ($clean === true) {
            (require('functions/interactive.clean.php'))($app);
        }
    }

    public static function clean(Event $event) : void
    {
        $app = new self($event->getIO(), $event->getComposer());

        $dryRun = in_array('--dry-run', $event->getArguments());

        if (isset($app->config['duration'])) {
            (require('functions/clean.php'))($app, $dryRun);
        } else {
            $app->io->write('<warning>Duration not configured ! Automatic cleaning skipped !</warning>');
        }
    }

    public static function snapshot(Event $event) : void
    {
        $app = new self($event->getIO(), $event->getComposer());

        $dryRun = in_array('--dry-run', $event->getArguments());

        (require('functions/snapshot.php'))($app, $dryRun);
    }
}
