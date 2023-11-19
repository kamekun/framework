<?php

namespace BytePlatform\Livewire;

use BytePlatform\Component;
use BytePlatform\Facades\Assets;
use BytePlatform\Facades\Locale;
use BytePlatform\Facades\Module;
use BytePlatform\Facades\Platform;
use BytePlatform\Facades\Plugin;
use BytePlatform\Facades\Theme;
use BytePlatform\Laravel\JsonData;
use Illuminate\Support\Facades\DB;

class Setup extends Component
{
    public $lang = 'en';
    public $timezone;
    public $db_connection = 'mysql';
    public $db_host = '127.0.0.1';
    public $db_port = '3306';
    public $db_name;
    public $db_username;
    public $db_pass;



    public $acc_name;
    public $acc_email;
    public $acc_pass;
    public $site_name;
    public $site_description;

    public $step_index = 0;
    public $step_max = 4;
    public $languages = [];
    public $active_modules = [];
    public $active_plugins = [];
    public $active_theme = 'none';
    public function updatedLang()
    {
        Locale::SwitchLocale($this->lang);
    }
    public function stepNext()
    {
        if ($this->step_index == 1) {
            if (!$this->updateEnvDB()) {
                return;
            }
        }
        if ($this->step_index == 2) {
            $this->createUser();
        }
        if ($this->step_index >= $this->step_max) return;
        $this->step_index++;
    }
    public function stepBack()
    {
        if ($this->step_index <= 0) return;
        $this->step_index--;
    }
    public function stepFinish()
    {
        $this->showMessage(json_encode($this->active_plugins));
        if ($this->updateEnv())
            return redirect('/');
    }
    /**
     * -------------------------------------------------------------------------------
     *  checkDatabaseConnection
     * -------------------------------------------------------------------------------
     **/
    public function checkDatabaseConnection($database_host, $database_port, $database_name, $database_username, $database_password, $connection = 'mysql')
    {
        $settings = config("database.connections.$connection");

        config([
            'database' => [
                'default' => $connection,
                'connections' => [
                    $connection => array_merge($settings, [
                        'driver'   => $connection,
                        'host'     => $database_host,
                        'port'     => $database_port,
                        'database' => $database_name,
                        'username' => $database_username,
                        'password' => $database_password,
                    ]),
                ],
            ],
        ]);

        DB::purge();

        try {

            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {

            return false;
        }
    }

    public function updateEnvDB()
    {
        // $this->showMessage(json_encode([
        //     'DB_CONNECTION' => $this->db_type,
        //     'DB_HOST' => $this->host,
        //     'DB_DATABASE' => $this->database,
        //     'DB_USERNAME' => $this->username,
        //     'DB_PASSWORD' => $this->password,
        //     'APP_NAME' => $this->site_name,
        //     'APP_URL' => url('')
        // ]));
        // return;
        if (!$this->checkDatabaseConnection($this->db_host, $this->db_port, $this->db_name, $this->db_username, $this->db_pass, $this->db_connection)) {
            $this->showMessage('Connection to database fail!');
            return false;
        }

        Platform::setEnv([
            'DB_CONNECTION' => $this->db_connection,
            'DB_HOST' => $this->db_host,
            'DB_PORT' => $this->db_port,
            'DB_DATABASE' => $this->db_name,
            'DB_USERNAME' => $this->db_username,
            'DB_PASSWORD' => $this->db_pass,
            'APP_NAME' => $this->site_name,
            'APP_URL' => url('')
        ]);

        // //php artisan cache:clear
        // // run_cmd(base_path(''), 'php artisan key:generate');
        // // run_cmd(base_path(''), 'php artisan cache:clear');
        // Artisan::call('migrate');
        // $this->createUser();
        return true;
    }

    public function createUser()
    {
        run_cmd(base_path(''), 'php artisan migrate');
        $roleModel = (config('byte.model.role', \BytePlatform\Models\Role::class));
        $userModel = (config('byte.model.user', \BytePlatform\Models\User::class));
        $roleAdmin = new $roleModel;
        $roleAdmin->name = $roleModel::SupperAdmin();
        $roleAdmin->slug = $roleModel::SupperAdmin();
        $roleAdmin->status = true;
        $roleAdmin->save();
        $userAdmin = new $userModel;
        $userAdmin->name = $this->acc_name;
        $userAdmin->email = $this->acc_email;
        $userAdmin->password = $this->acc_pass;
        $userAdmin->status = 1;
        $userAdmin->save();
        $userAdmin->roles()->sync([$roleAdmin->id]);
    }
    public function mount()
    {
        Theme::setTitle('System Setup');
        Assets::Theme('tabler');
        Assets::AddCss('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css');
        $this->lang = Locale::CurrentLocale();
        $this->languages = JsonData::getJsonFromFile(__DIR__ . '/../../database/contents/languages.json');
        $this->db_connection = env('DB_CONNECTION', 'mysql');
        $this->db_host = env('DB_HOST', '127.0.0.1');
        $this->db_port = env('DB_PORT', '3306');
        $this->db_name = env('DB_DATABASE', 'forge');
        $this->db_username = env('DB_USERNAME', 'forge');
        $this->db_pass = env('DB_PASSWORD', '');
    }
    public function render()
    {
        return view(
            'byte::setup',
            [
                'modules' => Module::getAll(),
                'plugins' => Plugin::getAll()
            ]
        );
    }
}
