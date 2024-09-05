# SeaTable-Demo

This repository hosts the configuration and daily reset scripts for a demo instance of the SeaTable system. The setup is based on the [seatable-release](https://github.com/seatable/seatable-release) repository.
This goal is to reset this demo instance every night.

Current URL of this setup: https://demo.seatable.io/

## Prerequisites

1. Install standard SeaTable Server on a server with python pipeline
2. install git and download this repository to `/opt/` with `git clone https://github.com/datamate-rethink-it/seatable-demo-recreate.git`
3. create `.env` file with the variables like described in env-example. Important: The admin credentials of the two .env files must match (/opt/seatable-compose/.env and /opt/seatable-demo-recreate/.env)
4. `chmod +x /opt/seatable-demo-recreate/reset.sh`
5. save the four cert files for SSO to `/opt/seatable-demo-recreate/certs/`

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

## How to update on the server

```
cd /opt/seatable-demo-recreate/
git pull
```
