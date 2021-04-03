<?php

namespace Tests\Feature;

use App\Http\Livewire\ConfiguratorForm;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class YamlFormTest extends TestCase
{
    use DatabaseMigrations;

    const DIR_MOCK ="tests/Feature/mock-asserts/";

    /**
     * @description Loading form page with defaults.
     * @return void
     */
    public function test_load_form()
    {
        $this->get('/')
            ->assertStatus(200);
    }


    /**
     * Form Test using pull request option.
     * @return void
     */
    public function test_form_submit_onpullrequest()
    {
        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("onPullrequest", true)
            ->call('submitForm')
            ->assertHasNoErrors('yaml');
    }
    /**
     * Form Test using pull request option.
     * @return void
     */
    public function test_form_submit_emptyname()
    {
        Livewire::test(ConfiguratorForm::class)
            ->set("name","")
            ->set("onPullrequest", true)
            ->call('submitForm')
            ->assertHasErrors('name');
    }

    /**
     * Form Test: using manual triggering option.
     *
     * @return void
     */
    public function test_form_submit_onmanual()
    {
        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", true)
            ->set("onPush", false)
            ->call('submitForm')
            ->assertHasNoErrors('yaml');
    }

    /**
     * Form Test: test on events checkboxes.
     *
     * @return void
     */
    public function test_form_submit_onevents()
    {
        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", false)
            ->set("onPush", false)
            ->set("onPullrequest", false)
            ->call('submitForm')
            ->assertHasErrors('onEvents');

        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", false)
            ->set("onPush", true)
            ->set("onPullrequest", false)
            ->call('submitForm')
            ->assertHasNoErrors('onEvents');
        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", true)
            ->set("onPush", true)
            ->set("onPullrequest", false)
            ->call('submitForm')
            ->assertHasNoErrors('onEvents');
        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", true)
            ->set("onPush", true)
            ->set("onPullrequest", true)
            ->call('submitForm')
            ->assertHasNoErrors('onEvents');

        $hintsTest =[];
        $hint = "You selected all 3 options: 'on Push', 'on Pull Request', and 'Manual Trigger'.";
        $hint = $hint . " I suggest you to select 'Manual Trigger' OR 'on push / on pull request'.";
        $hintsTest[] = $hint;
        $hintsTest[] ="I selected automatically a 'Manual Trigger' for you.";

        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", true)
            ->set("onPush", true)
            ->set("onPullrequest", true)
            ->call('submitForm')
            ->assertSet('hints', $hintsTest);

        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", false)
            ->set("onPush", true)
            ->set("onPullrequest", true)
            ->call('submitForm')
            ->assertSet('hints', []);
    }

    /**
     * Form Test: using manual triggering option.
     *
     * @return void
     */
    public function test_form_submit_tests()
    {
        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", false)
            ->set("onPush", true)
            ->call('submitForm')
            ->assertSee(file_get_contents(base_path(self::DIR_MOCK."on-push-branches.yaml")))
            ->assertSee(file_get_contents(base_path(self::DIR_MOCK."mysql-service.yaml")));


        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", false)
            ->set("onPush", true)
            ->set("databaseType", "mysql")
            ->call('submitForm')
            ->assertSee(file_get_contents(base_path(self::DIR_MOCK."mysql-service.yaml")));

    }
    /**
     * Form Test: using manual triggering option.
     *
     * @return void
     */
    public function test_form_submit_test_matrix()
    {
        Livewire::test(ConfiguratorForm::class)
            ->set("name","Test")
            ->set("manualTrigger", false)
            ->set("onPush", true)
            ->set("matrixLaravel",true)
            ->set("matrixLaravelVersions",["8.*" => "8.*"])
            ->set("stepPhpVersions",[ "8.0", "7.4"])
            ->call('submitForm')
            ->assertSee(file_get_contents(base_path(self::DIR_MOCK."on-push-branches.yaml")))
            ->assertSee(file_get_contents(base_path(self::DIR_MOCK."mysql-service.yaml")))
            ->assertSee(file_get_contents(base_path(self::DIR_MOCK."strategy-php-8-74.yaml")));



    }

}
