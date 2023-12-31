<?php

namespace Tests;

use Derekmd\Dusk\Concerns\TogglesHeadlessMode;
use Derekmd\Dusk\Firefox\SupportsFirefox;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication, SupportsFirefox, TogglesHeadlessMode;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        if (static::runningFirefoxInSail()) {
            return;
        }

        if (env('DUSK_CHROME')) {
            static::startChromeDriver();
        } else {
            static::startFirefoxDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return RemoteWebDriver
     */
    protected function driver()
    {
        if (env('DUSK_CHROME')) {
            return $this->chromeDriver();
        }

        return $this->firefoxDriver();
    }

    /**
     * Create the ChromeDriver instance.
     *
     * @return RemoteWebDriver
     */
    protected function chromeDriver()
    {
        $options = (new ChromeOptions)->addArguments($this->filterHeadlessArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
        ]));

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Create the Geckodriver instance.
     *
     * @return RemoteWebDriver
     */
    protected function firefoxDriver()
    {
        return RemoteWebDriver::create(
            "https://bdec30988ed7746b910592c436e684e0:0a4fff0c8446ce754ba95f8bafe6e4be@hub.testingbot.com/wd/hub",
            array("platform"=>"WINDOWS", "browserName"=>"firefox", "version" => "latest", "maxduration" => 18000), 120000);

    }
}
