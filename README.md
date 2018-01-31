[![Latest Stable Version](https://poser.pugx.org/jbelien/ovh-cloud-snapshot/v/stable)](https://packagist.org/packages/jbelien/ovh-cloud-snapshot)
[![Total Downloads](https://poser.pugx.org/jbelien/ovh-cloud-snapshot/downloads)](https://packagist.org/packages/jbelien/ovh-cloud-snapshot)
[![Monthly Downloads](https://poser.pugx.org/jbelien/ovh-cloud-snapshot/d/monthly.png)](https://packagist.org/packages/jbelien/ovh-cloud-snapshot)

# OVH Cloud Automated Snapshot

## Requirements

* [PHP](https://www.php.net/)
* [Composer](https://getcomposer.org/)

## Installation

```
composer create-project jbelien/ovh-cloud-snapshot
```

## Configuration

### First step

Create credentials :
<https://api.ovh.com/createToken/index.cgi?POST=/cloud/project/*/instance/*/snapshot&POST=/cloud/project/*/volume/*/snapshot&GET=/cloud/project/*/snapshot&DELETE=/cloud/project/*/snapshot/*>

### Second step

Create `snapshot.yml` in root directory with your credentials and the list of your instances/volumes :

```
---
applicationKey: <ovh_application_key>
applicationSecret: <ovh_application_secret>
consumerKey: <ovh_consumer_key>

duration: <date-interval>

projects:
  - id: "<project-1-id>"
    instances:
      - &myinstance
          id: "<instance-id>"
          name: "My Instance"
    volumes:
      - &myvolume
        id: "<volume-id>"
        name: "My Volume"
  - id: "<project-2-id>"
    instances:
      ...
    volumes:
      ...
  ...
```

#### Configure `duration`

To determine after how many days/weeks/months/... you want snapshots to be delete, use `duration` option.  
This option uses PHP `DateInterval` format : <http://php.net/manual/en/dateinterval.construct.php>

The format starts with the letter P, for "period." Each duration period is represented by an integer value followed by a period designator. If the duration contains time elements, that portion of the specification is preceded by the letter T.

Here are some simple examples. Two days is `P2D`. Two seconds is `PT2S`. Six years and five minutes is `P6YT5M`.

## Run

`php snapshot.php`

## Crontab

You can automate the snapshot creation by creating a crontab making a call to this tool.
