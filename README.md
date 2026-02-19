# SeaTable-Demo Recreate

This repository hosts the configuration and daily reset scripts for a demo instance of the SeaTable system. The setup is based on the [seatable-release](https://github.com/seatable/seatable-release) repository.
This goal is to reset this demo instance every night and to install the latest public available seatable server version and to do some configurations.

Current URL of this setup: https://seatable-demo.de/

## Prerequisites

1. Install a [standard SeaTable Server](https://admin.seatable.io) on a server with python pipeline and collabora
2. Install git
3. Download this repository to `/opt/` with `git clone https://github.com/datamate-rethink-it/seatable-demo-recreate.git`
4. Create `.env` file with the variables like described in env-example. Important: The admin credentials you already used in the /opt/seatable-compose/.env must also be added to /opt/seatable-demo-recreate/.env).
5. `chmod +x /opt/seatable-demo-recreate/reset.sh`
6. Save the four necessary cert files for SSO to `/opt/seatable-demo-recreate/certs/`

## Setup

### What is configured automatically

- All public available plugins
- Some Templates
- Two teams (the Avengers and Sesamstraße) with default passwords
- SSO-Login
- Login-Backupground

### How to install a specific version

I force always to use the lastest docker images by adding this to the .env file.

```
# IMAGES
SEATABLE_IMAGE='seatable/seatable-enterprise:latest'
PYTHON_SCHEDULER_IMAGE='seatable/seatable-python-scheduler:latest'
PYTHON_STARTER_IMAGE='seatable/seatable-python-starter:latest'
PYTHON_RUNNER_IMAGE='seatable/seatable-python-runner:latest'
```

If you want to install a specific version, simply replace `latest` with the desired tag.

## Daily refresh

Create a cronjob that simply executes `/opt/seatable-demo-recreate/reset.sh` like

```
59 1 * * * /opt/seatable-demo-recreate/reset.sh > /opt/reset.log
```

## How to update

To update the recreate logic, simply pull the latest files from git.

```
cd /opt/seatable-demo-recreate/
git pull
```

Please be aware, that this does not load the latest versions of the yml files from [seatable-release](https://github.com/seatable/seatable-release) repository.
