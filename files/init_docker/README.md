# Docker init

this is a initialization container to prepare the SeaTable Server. This init container will create two teams, team members, uploads bases and templates.

## How to use

```
# to build the container
sudo docker --no-cache build -t php-init .

# to execute
sudo docker run --rm \
 -v $(pwd)/createOrgsTemplatesPlugins.php:/app/createOrgsTemplatesPlugins.php \
 -v $(pwd)/../templates:/tmp/templates \
 -v $(pwd)/../plugins:/tmp/plugins \
 -v $(pwd)/../avatars:/tmp/avatars \
 -v $(pwd)/../output:/tmp/output \
 -v $(pwd)/../../.env:/tmp/.env \
php-init
```
