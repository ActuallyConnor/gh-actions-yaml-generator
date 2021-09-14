<?php

namespace App\Console\Commands;

use App\Objects\GuesserFiles;
use App\Objects\WorkflowGenerator;
use Composer\Semver\Semver;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class GenerateWorkflow extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ghygen:generate
    {--projectdir= : the directory of the project with composer.json}
    {--cache : enable caching packages in the workflow}
    {--envfile=' . GuesserFiles::ENV_DEFAULT_TEMPLATE_FILE . ' : the .env file to use in the workflow}
    ';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate GitHub Actions workflow automatically from
    a project (repository, local filesystem) using composer.json, .env and package.json';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $projectdir = $this->option("projectdir");
        $cache = $this->option("cache");
        $optionEnvWorkflowFile = $this->option("envfile");

        $guesserFiles = new GuesserFiles();
        $guesserFiles->pathFiles($projectdir, $optionEnvWorkflowFile);

        /*
        $composerFile = base_path("composer.json");
        $envFile = base_path(".env");
        $envWorkflowFile = base_path($optionEnvWorkflowFile);
        $nvmrcFile = base_path(".nvmrc");
        $packageFile = base_path("package.json");
        $artisanFile = base_path("artisan");
        $migrationsDir = base_path("database" . DIRECTORY_SEPARATOR . "migrations");

        if ($projectdir !== "") {
            $composerFile = $projectdir . DIRECTORY_SEPARATOR . "composer.json";
            $envFile = $projectdir . DIRECTORY_SEPARATOR . ".env";
            $envWorkflowFile = $projectdir . DIRECTORY_SEPARATOR . $optionEnvWorkflowFile;
            $nvmrcFile = $projectdir . DIRECTORY_SEPARATOR . ".nvmrc";
            $packageFile = $projectdir . DIRECTORY_SEPARATOR . "package.json";
            $artisanFile = $projectdir . DIRECTORY_SEPARATOR . "artisan";
            $migrationsDir = $projectdir . DIRECTORY_SEPARATOR . "database" . DIRECTORY_SEPARATOR . "migrations";
        }
        */
        $this->line("Composer : " . $guesserFiles->getComposerPath());
        if (! $guesserFiles->composerExists()) {
            $this->error("Composer file not found");
            return -1;
        }
        $generator = new WorkflowGenerator();
        $generator->loadDefaults();

        if ($guesserFiles->composerExists()) {
            $composer = json_decode(file_get_contents($guesserFiles->getComposerPath()), true);
            $generator->name = Arr::get($composer, 'name');
            $phpversion = Arr::get($composer, 'require.php', "");
            $generator->detectPhpVersion($phpversion);
        }
        $generator->detectCache($cache);

        $generator->databaseType = WorkflowGenerator::DB_TYPE_NONE;
        $generator->stepRunMigrations = false;

        if ($guesserFiles->envExists()) {
            $envArray = $generator->readDotEnv($guesserFiles->getEnvPath());
            $databaseType = Arr::get($envArray, "DB_CONNECTION", "");
            $this->line("DATABASE:" . $databaseType);
            $generator->databaseType = WorkflowGenerator::DB_TYPE_NONE;
            $generator->stepRunMigrations = false;
            if ($databaseType === "mysql") {
                $generator->databaseType = WorkflowGenerator::DB_TYPE_MYSQL;
            }
            if ($databaseType === "sqlite") {
                $generator->databaseType = WorkflowGenerator::DB_TYPE_SQLITE;
            }
            if ($databaseType === "postgresql") {
                $generator->databaseType = WorkflowGenerator::DB_TYPE_POSTGRESQL;
            }
            if ($generator->databaseType !== WorkflowGenerator::DB_TYPE_NONE) {
                $migrationFiles = scandir($guesserFiles->getMigrationsPath());
                if (count($migrationFiles) > 4) {
                    $generator->stepRunMigrations = true;
                }
            }
        }
        if ($guesserFiles->packageExists()) {
            $generator->stepNodejs = true;
            $generator->stepNodejsVersion = "16.x";
            $versionFromNvmrc = $generator->readNvmrc($guesserFiles
            ->getNvmrcPath());
            if ($versionFromNvmrc !== "") {
                $generator->stepNodejsVersion = $versionFromNvmrc;
            }
        }
        if ($guesserFiles->envDefaultTemplateExists()) {
            $generator->stepCopyEnvTemplateFile = true;
            $generator->stepEnvTemplateFile = $optionEnvWorkflowFile;
        } else {
            $generator->stepCopyEnvTemplateFile = false;
        }

        if ($guesserFiles->artisanExists()) {
            //artisan file so:ENV_TEMPLATE_FILE_DEFAULT.
            // fix storage permissions
            $generator->stepFixStoragePermissions = true;
        }

        $data = $generator->setData();

        $result = $generator->generate($data);
        $this->line($result);





        return 0;
    }
}
