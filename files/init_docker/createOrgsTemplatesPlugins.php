<?php  declare(strict_types=1);

// get variables
$envPath = '/tmp/.env';
if(file_exists($envPath)){
    loadEnvFile('/tmp/.env');
    $host = getenv('SEATABLE_URL');
    $username = getenv('ADMIN_UN');
    $password = getenv('ADMIN_PW');
    $default_pw = getenv('DEFAULT_PW');
} else {
    echo "keine env gefunden";
    die();
}

/* ... */

function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return false; // File does not exist
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        putenv(sprintf('%s=%s', $name, $value));
    }
    return true;
}

function fixBaseName($name){
    switch ($name) {
        case 'crm_sales':
            return "CRM & Sales";
            break;
        case 'software_development_planning':
            return "Software Development & Planning";
            break;
        case 'all_column_types':
            return "all column types";
            break;
        default:
            return ucwords(str_replace("_", " ", $name));
            break;
    }
}

function getRandomBaseIcon(){
    $icons = ["icon-dollar", "icon-company-inventory", "icon-administrative-matters-calendar", "icon-research", "icon-product-knowledge-base"];
    return $icons[array_rand($icons)];
}
function getRandomBaseColor(){
    $colors = ['#4CAF50', '#972CB0', '#1688FC', '#E91E63', '#656463'];
    return $colors[array_rand($colors)];
}

use SeaTable\SeaTableApi\SeaTableApi as SeaTableAPI;
require_once(__DIR__ . '/vendor/autoload.php');
$config = SeaTable\Client\Configuration::getDefaultConfiguration();
$config->setHost($host);

/**
 * AUTH SYS-ADMIN
 */
$apiInstance = new SeaTable\Client\Auth\AccountTokenApi(new GuzzleHttp\Client(), $config);
try {
    $sysAdminToken = $apiInstance->getAccountTokenfromUsername($username, $password)['token'];
} catch (Exception $e) {
    echo 'Exception when calling AccountToken: ', $e->getMessage(), PHP_EOL;
}
$sysAdmin_PluginsApi = new SeaTable\Client\SysAdmin\PluginsApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($sysAdminToken)
);

/**
 * PLUGINS (working)
 */

echo "Install Plugins...\n";
$mainUrl = "https://market.seatable.io/api/plugins";
$response = file_get_contents($mainUrl);
 
if ($response !== false) {
    $data = json_decode($response, true);
 
    if (isset($data['plugin_list']) && is_array($data['plugin_list'])) {
        foreach ($data['plugin_list'] as $plugin) {
 
            if (isset($plugin['name'])) {
                $pluginUrl = $mainUrl ."/" . urlencode($plugin['name']);
                $pluginDetails = file_get_contents($pluginUrl);
 
                if ($pluginDetails !== false) {
                    $plugin = json_decode($pluginDetails, true);
                     
                    // Define the path where the file will be saved
                    $destinationPath = '/tmp/plugins/'. basename($plugin['zip_asset_path']);
 
                    // Download the file from the download URL if the file does not already exists
                    if (!file_exists($destinationPath)) {
                        $fileContent = file_get_contents($plugin['download_url']);
                        if ($fileContent !== false) {
                            file_put_contents($destinationPath, $fileContent);
                        } 
                    }
 
                    // Install plugin
                    try {
                        $sysAdmin_PluginsApi->addPlugin($destinationPath);
                    } catch (Exception $e) {} // plugin might already exist
                }
            }
        }
    }
}

// array to save all users like 'tony' => 'xxx@auth.local'
$users = [];

/**
 * TEAM: Avengers
 */
echo "Install Teams...\n";

// delete old avengers (if exist)
$sysAdmin_TeamsApi = new SeaTable\Client\SysAdmin\TeamsApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($sysAdminToken)
);
$result = $sysAdmin_TeamsApi->listTeams(1, 25);
foreach($result['organizations'] as $org){
    if($org->org_name == "The Avengers"){
        $sysAdmin_TeamsApi->deleteTeam($org->org_id);
    }
}

// create new team: Avengers
$newTeam = new \SeaTable\Client\SysAdmin\AddTeamRequest([
	'org_name' => 'The Avengers',
	'admin_email' => 'hulk@seatable.io',
	'password' => $default_pw,
	'admin_name' => 'Hulk',
	'with_workspace' => true,
]);

try {
    $result = $sysAdmin_TeamsApi->addTeam($newTeam);
    $org_id = $result['org_id'];
    $org_creator = $result['creator_email'];
    $workspace_id = $result['workspace_id'];
    $users['hulk'] = $result['creator_email'];
} catch (Exception $e) {
    echo 'Exception when calling TeamsApi->addTeam: ', $e->getMessage(), PHP_EOL;
    die();
}

// update team role
$result = $sysAdmin_TeamsApi->updateTeam($org_id, ['role' => 'org_enterprise']);
//print_r($result);

// add team members
$newUsers = [
    'tony' => 'Tony Stark',
    'steve' => 'Steve Rogers',
    'thor' => 'Thor'
];
foreach($newUsers as $user => $user_id){
    $result = $sysAdmin_TeamsApi->addTeamUser($org_id, $user .'@seatable.io', $default_pw, $user_id, 'true');
    $newUsers[$user] = $result['email'];
}
$users = array_merge($users, $newUsers);

/**
 * The Avengers - Project shield
 */

// add group 'Project shield'
$teamAdminToken = $apiInstance->getAccountTokenfromUsername('hulk@seatable.io', $default_pw)['token'];
$teamAdmin_GroupsApi = new SeaTable\Client\TeamAdmin\GroupsApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$group = $teamAdmin_GroupsApi->addGroup($org_id, 'Project shield', $org_creator);

// add group members
foreach($users as $user => $user_id){
    $teamAdmin_GroupsApi->addGroupMembers($org_id, $group['id'], $user_id);
}

// get workspace_id
$user_GroupsWorkspacesApi = new SeaTable\Client\User\GroupsWorkspacesApi(new GuzzleHttp\Client(),
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$workspace_list = $user_GroupsWorkspacesApi->listWorkspaces('true')['workspace_list'];
foreach($workspace_list as $ws){
    if($ws->type == "group" && $ws->name == "Project shield"){
        $workspace_id = $ws->id;
    };
}

// import bases to group
$user_ImportExportApi = new SeaTable\Client\User\ImportExportApi(new GuzzleHttp\Client(),
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$user_BasesApi = new SeaTable\Client\User\BasesApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);

$template_list = ['bug_tracker.dtable', 'all_column_types.dtable', 'crm_sales.dtable', 'employee_training.dtable', 'software_development_planning.dtable'];
foreach($template_list as $template){
    try {
        $base = $user_ImportExportApi->importBasefromDTableFile($workspace_id, '/tmp/templates/'.$template, '');
        $user_BasesApi->updateBase($workspace_id, $base['table']['name'], fixBaseName($base['table']['name']), getRandomBaseIcon(), getRandomBaseColor());
    }
    catch (Exception $e) {}
}

/**
 * The Avengers - Templates Group
 */

// add group 'Templates'
$teamAdminToken = $apiInstance->getAccountTokenfromUsername('hulk@seatable.io', $default_pw)['token'];
$teamAdmin_GroupsApi = new SeaTable\Client\TeamAdmin\GroupsApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$group = $teamAdmin_GroupsApi->addGroup($org_id, 'Templates', $org_creator);

// add group members
foreach($users as $user => $user_id){
    $teamAdmin_GroupsApi->addGroupMembers($org_id, $group['id'], $user_id);
}

// get workspace_id
$user_GroupsWorkspacesApi = new SeaTable\Client\User\GroupsWorkspacesApi(new GuzzleHttp\Client(),
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$workspace_list = $user_GroupsWorkspacesApi->listWorkspaces('true')['workspace_list'];
foreach($workspace_list as $ws){
    if($ws->type == "group" && $ws->name == "Templates"){
        $workspace_id = $ws->id;
    };
}

// import bases to group
$user_ImportExportApi = new SeaTable\Client\User\ImportExportApi(new GuzzleHttp\Client(),
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$user_BasesApi = new SeaTable\Client\User\BasesApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);

$template_list = ['bug_tracker.dtable', 'expense_tracking.dtable', 'custom_templates.dtable'];
foreach($template_list as $template){
    try {
        $base = $user_ImportExportApi->importBasefromDTableFile($workspace_id, '/tmp/templates/'.$template, '');
        $user_BasesApi->updateBase($workspace_id, $base['table']['name'], fixBaseName($base['table']['name']), getRandomBaseIcon(), getRandomBaseColor());
    }
    catch (Exception $e) {}
}

$user_APIToken = new SeaTable\Client\Auth\APITokenApi(new GuzzleHttp\Client(), SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken));
$template_token = $user_APIToken->createApiToken($workspace_id, fixBaseName('custom_templates'), 'template', 'rw')['api_token'];
//echo $template_token;

// Write api token of custom_templates base to the file
file_put_contents('/tmp/output/template_token.txt', 'TEMPLATE_TOKEN='.$template_token);


$users2 = [];
/**
 * TEAM: Sesamstraße
 */
echo "Install Team Sesamstraße...\n";

// delete old sesamstraße (if exist)
$sysAdmin_TeamsApi = new SeaTable\Client\SysAdmin\TeamsApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($sysAdminToken)
);
$result = $sysAdmin_TeamsApi->listTeams(1, 25);
foreach($result['organizations'] as $org){
    if($org->org_name == "Sesamstraße"){
        $sysAdmin_TeamsApi->deleteTeam($org->org_id);
    }
}

// create new team: Avengers
$newTeam = new \SeaTable\Client\SysAdmin\AddTeamRequest([
	'org_name' => 'Sesamstraße',
	'admin_email' => 'ernie@seatable.io',
	'password' => $default_pw,
	'admin_name' => 'ernie',
	'with_workspace' => true,
]);

try {
    $result = $sysAdmin_TeamsApi->addTeam($newTeam);
    $org_id = $result['org_id'];
    $org_creator = $result['creator_email'];
    $workspace_id = $result['workspace_id'];
    $users['ernie'] = $result['creator_email'];
} catch (Exception $e) {
    echo 'Exception when calling TeamsApi->addTeam: ', $e->getMessage(), PHP_EOL;
    die();
}

// update team role
$result = $sysAdmin_TeamsApi->updateTeam($org_id, ['role' => 'org_free']);
//print_r($result);

// add team members
$newUsers = [
    'bert' => 'Bert',
    'monster' => 'Krümelmonster',
    'elmo' => 'Elmo'
];
foreach($newUsers as $user => $user_id){
    $result = $sysAdmin_TeamsApi->addTeamUser($org_id, $user .'@seatable.io', $default_pw, $user_id, 'true');
    $newUsers[$user] = $result['email'];
}
$users2 = array_merge($users2, $newUsers);

/**
 * Sesamstraße - Sesam-Projekte
 */

// add group
$teamAdminToken = $apiInstance->getAccountTokenfromUsername('ernie@seatable.io', $default_pw)['token'];
$teamAdmin_GroupsApi = new SeaTable\Client\TeamAdmin\GroupsApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$group = $teamAdmin_GroupsApi->addGroup($org_id, 'Sesam-Projekte', $org_creator);

// add group members
foreach($users2 as $user => $user_id){
    $teamAdmin_GroupsApi->addGroupMembers($org_id, $group['id'], $user_id);
}

// get workspace_id
$user_GroupsWorkspacesApi = new SeaTable\Client\User\GroupsWorkspacesApi(new GuzzleHttp\Client(),
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$workspace_list = $user_GroupsWorkspacesApi->listWorkspaces('true')['workspace_list'];
foreach($workspace_list as $ws){
    if($ws->type == "group" && $ws->name == "Sesam-Projekte"){
        $workspace_id = $ws->id;
    };
}

// import bases to group
$user_ImportExportApi = new SeaTable\Client\User\ImportExportApi(new GuzzleHttp\Client(),
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);
$user_BasesApi = new SeaTable\Client\User\BasesApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($teamAdminToken)
);

$template_list = ['bug_tracker.dtable', 'all_column_types.dtable', 'crm_sales.dtable', 'employee_training.dtable', 'software_development_planning.dtable'];
foreach($template_list as $template){
    try {
        $base = $user_ImportExportApi->importBasefromDTableFile($workspace_id, '/tmp/templates/'.$template, '');
        $user_BasesApi->updateBase($workspace_id, $base['table']['name'], fixBaseName($base['table']['name']), getRandomBaseIcon(), getRandomBaseColor());
    }
    catch (Exception $e) {}
}

// final merge...
$users = array_merge($users2, $users);





/**
 * Login IDs
 */
$sysAdmin_UsersApi = new SeaTable\Client\SysAdmin\UsersApi(new GuzzleHttp\Client(), 
    SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($sysAdminToken)
);

foreach ($users as $user => $user_id) {
    $sysAdmin_UsersApi->updateUser($user_id, ['login_id' => $user]);
}

/**
 * Avatars
 */
foreach ($users as $user => $user_id) {

    // usertoken
    $result = $apiInstance->getAccountTokenfromUsername($user .'@seatable.io', $default_pw);
    $config = SeaTable\Client\Configuration::getDefaultConfiguration()->setAccessToken($result['token']);

    // avatars
    $user_UserApi = new SeaTable\Client\User\UserApi(new GuzzleHttp\Client(), $config);
    $result = $user_UserApi->addUserAvatar('/tmp/avatars/'. $user .'.png');
    // print_r($result);

    // notifications
    $user_NotificationApi = new SeaTable\Client\User\NotificationsApi(new GuzzleHttp\Client(), $config);
    $result = $user_NotificationApi->markNotificationAsSeen();
    // print_r($result);
    
}




// TODO: die ganzen print_r($result); noch entfernen...


