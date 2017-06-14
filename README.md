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
https://api.ovh.com/createToken/index.cgi?POST=/cloud/project/*/instance/*/snapshot&POST=/cloud/project/*/volume/*/snapshot

### Second step

Create `snapshot.yml` in root directory with your credentials and the list of your instances/volumes :

```
---
applicationKey: <ovh_application_key>
applicationSecret: <ovh_application_secret>
consumerKey: <ovh_consumer_key>

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

## Run

`php snapshot.php`

## Crontab

You can automate the snapshot creation by creating a crontab making a call to this tool.
