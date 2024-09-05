# SeaTable-Demo

This repository hosts the configuration and daily reset scripts for a demo instance of the SeaTable system. The setup is based on the [seatable-release](https://github.com/seatable/seatable-release) repository.
This goal is to reset this demo instance every night.

Current URL of this setup: https://demo.seatable.io/

## Prerequisites

1. Install standard SeaTable Server on a server with python pipeline
2. install git and `git clone https://christophdb:TOKEN@github.com/datamate-rethink-it/seatable-demo-daily-recreate.git` # https://stackoverflow.com/a/70151967
3. download this repository to `/opt/daily-reset`
4. create .env file with the variables like described in env-example
5. `chmod +x /opt/daily-reset/reset.sh`
6. copy the cert files from `/opt/daily-reset/files/certs/` to `/opt/seatable-server/certs/` in seatable container for SSO.

Also I force always to use the lastest docker images by adding this to the .env file.

```
# IMAGES
SEATABLE_IMAGE='seatable/seatable-enterprise:latest'
PYTHON_SCHEDULER_IMAGE='seatable/seatable-python-scheduler:latest'
PYTHON_STARTER_IMAGE='seatable/seatable-python-starter:latest'
PYTHON_RUNNER_IMAGE='seatable/seatable-python-runner:latest'
```

## How to use

Create a cronjob that simply executes /opt/daily-reset/reset.sh like

```
59 1 * * * /opt/daily-reset/reset.sh
```
