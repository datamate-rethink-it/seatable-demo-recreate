#!/bin/bash

## get passwords
source ./.env

## stop old containers
cd /opt/seatable-compose
docker compose down --remove-orphans

## remove old stuff
rm -r /opt/seatable-server
rm -r /opt/mariadb
rm -r /opt/seatable-demo-recreate/files/output/template_token.txt # da kommt der base_api_token von templates base rein...

## customizing
printf "%b(11): Customizing (Logo, Login-Background, Custom-CSS) %b\n" "$RED" "$NC"
mkdir -p /opt/seatable-server/seatable/seahub-data/custom/
cp -t /opt/seatable-server/seatable/seahub-data/custom/ \
  /opt/seatable-demo-recreate/files/mylogo.png \
  /opt/seatable-demo-recreate/files/login-bg.jpg \
  /opt/seatable-demo-recreate/files/custom.css \
  || exit 1

## restart
docker compose pull
docker compose up -d

## wait until SeaTable is available
TIMEOUT=120             # Total timeout duration in seconds (2 minutes)
INTERVAL=10             # Interval between pings in seconds
start_time=$(date +%s)  # start time

while true; do
  if curl --head --silent --fail "${SEATABLE_URL}/dtable-server/ping/" > /dev/null; then
    echo "URL is available. Continuing..."
    break
  else
    echo "URL is not available. Checking again in $INTERVAL seconds..."
  fi
  current_time=$(date +%s) # Check if the timeout has been reached
  elapsed_time=$((current_time - start_time))

  if [ "$elapsed_time" -ge "$TIMEOUT" ]; then
    echo "Timeout reached. Exiting..."
    exit 1
  fi
  sleep "$INTERVAL" # Wait for the specified interval before retrying
done

## update dtable_web_settings.py
printf "%b(7): add seatable configuration %b\n" "$RED" "$NC"
echo "
HELP_LINK = 'https://seatable.io/docs/'
BRANDING_CSS = 'custom/custom.css'
SEND_EMAIL_ON_ADDING_SYSTEM_MEMBER = False

# MULTI TENANCY MODE
CLOUD_MODE = True
MULTI_TENANCY = True
ORG_MEMBER_QUOTA_ENABLED = True
ORG_MEMBER_QUOTA_DEFAULT = 25
ENABLE_SIGNUP = False
ENABLE_USER_TO_SET_NUMBER_SEPARATOR = True

# ROLES AND PERMISSIONS
ENABLED_ROLE_PERMISSIONS = {
    'default': {
        'can_add_dtable': True,
        'can_add_group': True,
        'can_use_global_address_book': True,
        'can_generate_share_link': True,
        'can_invite_guest': True,
        'role_asset_quota': '1G',
        'row_limit': 2000,
        'can_create_common_dataset': True,
        'can_generate_external_link': True,
        'can_run_python_script': True,
        'can_use_advanced_permissions': True,
        'snapshot_days': 10,
        'scripts_running_limit': 100,
        'can_use_external_app': True,
        'can_schedule_run_script': True,
        'can_use_automation_rules': True,
        'can_archive_rows': True,
        'can_use_advanced_customization': True
    },
    'guest': {
        'can_add_dtable': False,
        'can_add_group': False,
        'can_use_global_address_book': False,
        'can_generate_share_link': False,
        'role_asset_quota': '',
        'row_limit': 500,
        'can_create_common_dataset': False,
        'can_generate_external_link': False,
        'can_run_python_script': False,
        'can_use_advanced_permissions': False,
        'snapshot_days': 30,
        'scripts_running_limit': 10,
        'can_use_external_app': False,
        'can_schedule_run_script': False,
        'can_use_automation_rules': False,
        'can_archive_rows': False,
        'can_use_advanced_customization': False
    },
    'org_free': {
        'can_add_dtable': True,
        'can_add_group': True,
        'can_use_global_address_book': True,
        'can_generate_share_link': True,
        'can_invite_guest': True,
        'role_asset_quota': '1G',
        'row_limit': 20000,
        'can_create_common_dataset': True,
        'can_generate_external_link': True,
        'can_run_python_script': False,
        'can_use_advanced_permissions': False,
        'snapshot_days': 30,
        'scripts_running_limit': 100,
        'can_use_external_app': True,
        'can_schedule_run_script': False,
        'can_use_automation_rules': False,
        'can_archive_rows': False,
        'can_use_advanced_customization': False
    },
    'org_enterprise': {
        'can_add_dtable': True,
        'can_add_group': True,
        'can_use_global_address_book': True,
        'can_generate_share_link': True,
        'can_invite_guest': True,
        'role_asset_quota': '1G',
        'row_limit': 100000,
        'can_create_common_dataset': True,
        'can_generate_external_link': True,
        'can_run_python_script': True,
        'can_use_advanced_permissions': True,
        'snapshot_days': 180,
        'scripts_running_limit': -1,
        'can_use_external_app': True,
        'can_schedule_run_script': True,
        'can_use_automation_rules': True,
        'can_archive_rows': True,
        'can_use_advanced_customization': True
    }
}
ENABLE_DELETE_ACCOUNT = False
ENABLE_ORG_DEPARTMENT = False
ENABLE_ORG_COMMON_DATASET = True
ENABLE_ORG_ADMIN_INVITE_VIA_EMAIL = False

# collabora
ENABLE_COLLABORA = True
COLLABORA_DISCOVERY_URL = 'https://${SEATABLE_URL}:6232/hosting/discovery'

# später
#ENABLE_ONLYOFFICE = True
#ONLYOFFICE_APIJS_URL = 'https://seatable-demo.de/web-apps/apps/api/documents/api.js'
#ONLYOFFICE_FILE_EXTENSION = ('doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'odt', 'fodt', 'odp', 'fodp', 'ods', 'fods', 'csv', 'ppsx', 'pps')

# UNIVERSAL APP (wird nicht mehr benötigt)
#ENABLE_UNIVERSAL_APP = True

# SAML
ENABLE_SAML = True
SAML_REMOTE_METADATA_URL = 'https://auth.seatable.io/api/v3/providers/saml/19/metadata/?download'
SAML_PROVIDER_IDENTIFIER = 'Authentik'
SAML_ATTRIBUTE_MAP = {
    'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/upn': 'uid',
    'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress': 'contact_email',
    'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name': 'name',
}
SAML_CERTS_DIR = '/shared/certs/'

# EMAIL
EMAIL_USE_TLS = True
EMAIL_HOST = '${EMAIL_HOST}'
EMAIL_HOST_USER = '${EMAIL_HOST_USER}'
EMAIL_HOST_PASSWORD = '${EMAIL_HOST_PASSWORD}'
EMAIL_PORT = 587
DEFAULT_FROM_EMAIL = 'SeaTable <no-reply@seatable.io>'
SERVER_EMAIL = 'no-reply@seatable.io'

# User management
ENABLE_DELETE_ACCOUNT = False
ENABLE_UPDATE_USER_INFO = False    # nicht dokumentiert
ENABLE_CHANGE_PASSWORD = False   # ldap darf sowieso nie ändern, normale dürfen immer. scheint nicht konfigurierbar zu sein.
" | tee -a /opt/seatable-server/seatable/conf/dtable_web_settings.py >/dev/null

## update seafile.conf
echo "
[general]
multi_tenancy = true
" | tee -a /opt/seatable-server/seatable/conf/seafile.conf >/dev/null

# create users
printf "%b(10): create all users %b\n" "$RED" "$NC"
cd /opt/seatable-demo-recreate/files/init_docker
docker build --no-cache -t php-init .
docker run --rm \
 -v $(pwd)/createOrgsTemplatesPlugins.php:/app/createOrgsTemplatesPlugins.php \
 -v $(pwd)/../templates:/tmp/templates \
 -v $(pwd)/../plugins:/tmp/plugins \
 -v $(pwd)/../avatars:/tmp/avatars \
 -v $(pwd)/../output:/tmp/output \
 -v $(pwd)/../../.env:/tmp/.env \
php-init

## templates
source /opt/seatable-demo-recreate/files/output/template_token.txt
echo "
# Templates
SHOW_TEMPLATES_LINK = True
TEMPLATE_BASE_API_TOKEN = '${TEMPLATE_TOKEN}'
TEMPLATE_TABLE_NAME = 'templates'
ENABLE_CREATE_BASE_FROM_TEMPLATE = True
" | tee -a /opt/seatable-server/seatable/conf/dtable_web_settings.py >/dev/null


## final restart
docker exec seatable-server /opt/seatable/scripts/seatable.sh

## healthcheck einbauen
## nginx konfiguration optimieren einbauen
## kein memcached