# SeleniumTools [![Build Status](https://travis-ci.org/modera/SeleniumTools.svg?branch=master)](https://travis-ci.org/modera/SeleniumTools)

This package contains a set of components that you may want to use when:

* You want to write multi-user tests - test cases which involve several users (and browsers) collaborating with each
 other
* You want to be able to run tests in a headless environment (like CI) and being able to record videos of tests
execution
* You want to write multi-user BDD tests using Behat
* You want to test ExtJs/MJR based applications and avoid low-level dom manipulation

# Writing multi-user tests

Sometimes having E2E (end-to-end) tests that operate only with one user just doesn't cut it. For example, in order to 
test if a chat application really works you need to emulate collaboration between two users - a fronted user who might
initiate a conversation and an administrator, who replies to frontend user's questions. In this case we have two roles - 
an administrator and a customer, so our test scenario might look like this:

* A customer initiates a conversation by sending, say, "Hello, I have a question" message
* An administrator is expected to receive a message - we can periodically check if a page source contains 
"Hello, I have a question" piece of text
* Once an administrator has received the message, he writes a response - "Hello, how can I help you ?" and sends it
* Now we are checking that customer's page contains a piece of text "Hello, how can I help you ?"

Although our test scenario if very simple and in this case contains only 4 steps, it is enough to make sure that our 
chat-application baseline functionality works as expected. In context of this library in order to simulate a user
we use a high-level abstraction called an "Actor". Essentially, an actor represents an isolated browser that is being 
managed by Selenium and adds several nice features into the mix:

* You don't need to manually initialize and manage Selenium session, just feed a URL to an actor and you are ready to go.
If you still want to have better control over what happens under the hood, we got your covered as well
* When you have several actors (browsers, that is) and you are switching from performing some manipulations on one actor
to another, then the Actor (through TestHarness, which we will mention in a second) will automatically bring the browser
on top (focus it). Yes, Selenium won't to that for you automatically.
* You can pass data between actors in a clearly defined way. For example, in a sample test-scenario described above
we could have passed from "customer" actor to "administrator" a message the latter should expect to appear in the page
source code - "Hello, I have a question".

In order to effectively orchestrate collaboration between different actors and enable passing data between them, 
they are meant to be attached to a TestHarness, the latter defines which actors you would like to use and makes
it possible to easily switch between them.

It is said that that a picture is worth a thousand words, let's follow this old adage and explore the provided API by 
implementing a simple a sample test case testing if a chat application works correctly, for the sake of this specific
test-case we are going to use appear.in:

    use Facebook\WebDriver\Chrome\ChromeOptions;
    use Facebook\WebDriver\Remote\DesiredCapabilities;
    use Facebook\WebDriver\Remote\RemoteWebDriver;
    use Facebook\WebDriver\Remote\WebDriverCapabilityType;
    use Facebook\WebDriver\WebDriverKeys;
    use Modera\Component\SeleniumTools\Actor;
    use Modera\Component\SeleniumTools\Querying\By;
    use Modera\Component\SeleniumTools\TestHarness;
    
    class AppearInTest extends \PHPUnit_Framework_TestCase
    {
        /**
         * @var TestHarness
         */
        private $harness;
    
        public function setUp()
        {
            $roomUrl = 'https://appear.in/seleniumtools-'.\uniqid();
    
            // 1
            $driverFactory = new CallbackDriverFactory(function() {
                $options = new ChromeOptions();
                $options->addArguments([
                    'use-fake-ui-for-media-stream',
                ]);
     
                $capabilities = DesiredCapabilities::chrome();
                $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
     
                $driver = RemoteWebDriver::create(
                    'http://localhost:4444/wd/hub', $capabilities
                );
     
                return $driver;
            });
     
            $this->harness = new TestHarness('default', null, $driverFactory);
    
            $this->harness->addActor('customer', $roomUrl);
            $this->harness->addActor('admin', $roomUrl);
        }
    
        public function tearDown()
        {
            $this->harness->halt();
        }
    
        public function testBasicMessageExchange()
        {
            $this->harness->runAs('admin', function() {}); // 2
    
            $this->harness->runAs('customer', function(RemoteWebDriver $driver, Actor $actor) { // 3
                $message = 'Hello from customer '.uniqid();
    
                $driver->findElement(By::id('chat-button'))->click();
                sleep(1);
    
                $inputField = $driver->findElement(By::name('message'));
                $inputField->sendKeys([
                    $message,
                    WebDriverKeys::ENTER
                ]);
                sleep(1);
    
                $actor->getHarness()->setContextValue('customer_message', $message); // 4
            });
    
            $this->harness->runAs('admin', function(RemoteWebDriver $driver, Actor $actor) { // 5
                $this->assertContains(
                    $actor->getHarness()->getContextValue('customer_message'), // 6
                    $driver->getPageSource()
                );
            });
        }
    }
    
I need to admit that the case above in some ways can be considered to be an advanced one, because in this case
we need enable fake media stream so the chrome browser wouldn't ask a confirmation from a user to allow access
his microphone. If you want to see how to run this test case, scroll a little down. As of now we are going to talk in a 
little more detailed way what each of the specified in comments moments does:

1. As I already mentioned this is pretty advanced example, so in order to disable a microphone we need
 to gain a better control over how a Selenium driver is created (remember, it was mentioned in the beginning of this document 
 that if you need to do some tweaking, you can always do it), to achieve this we are defining a custom driver-factory
 which tells Chrome (this test scenario assumes that you have Chrome installed locally) to emulate a microphone. Most
 of the time you won't need to do this advanced configuration, but still it is always nice to have that option
 when you need it. 
2. Here we are opening a "admin" browser, this is done simply to address a fact that appear.in doesn't show messages
 that were sent before the browser was open, so by opening an "admin" browser we are making sure that when "customer"
 actually sends a message, we will receive it.
3. In this step we finding a button to open a chat area and send a message through it.
4. Here we are caching a message that has been sent, later in "admin" actor we will extract this value and verify
that a HTML source of the page contains this piece of text.
5. Activating back "admin" actor. Browser is open only once for every customer (unless you kill it explicitly), so
in this case the browser that we have opened in step "1" will just receive a focus.
6. Here we are taking a message that has been sent by "customer" actor and verify that the browser page's source
code indeed contains a given piece of text.

In order to run this test-case you need to follow the following steps:

1. Add `modera/selenium-tools` and `phpunit/phpunit` as a package to your composer.json and run `composer update`. This
is how sample composer.json can look like:

    {
        "name": "acme/testing-selenium",
        "type": "project",
        "license": "MIT",
        "prefer-stable": true,
        "minimum-stability": "dev",
        "require": {
            "modera/selenium-tools": "dev-master",
            "phpunit/phpunit": "^5.0"
        }
    }

2. Create a `phpunit.xml.dist`, it could look as simple as this:

        <?xml version="1.0" encoding="UTF-8"?>
        <phpunit backupGlobals="false"
                 backupStaticAttributes="false"
                 colors="true"
                 convertErrorsToExceptions="true"
                 convertNoticesToExceptions="true"
                 convertWarningsToExceptions="true"
                 processIsolation="false"
                 stopOnFailure="false"
                 syntaxCheck="false"
                 bootstrap="vendor/autoload.php"
        >
            <testsuites>
                <testsuite name="Sample testcase">
                    <directory>./tests/</directory>
                </testsuite>
            </testsuites>
        </phpunit>
    
3. Put a test in a file where the PHPUnit can find it. If you you a file from the step 2 in this list, then the file
must be placed in `tests` directory.
4. Download [Selenium](http://docs.seleniumhq.org/download/)
5. Download a [chrome driver](https://sites.google.com/a/chromium.org/chromedriver/downloads) and put it next to
download Selenium jar file.
6. Run `java -jar downloaded-selenium.jar`, where `downloaded-selenium.jar` is name of Selenium that you have downloaded
in step 4.
7. Keep Selenium running and run `./vendor/bin/phpunit`. If you have Docker installed then you can run it like this - 
`docker run --network=host -it --rm -v $(pwd):/mnt/tmp -w /mnt/tmp modera/php5-fpm bash -c "./vendor/bin/phpunit"`

## Configuring TestHarness and Actors

### Connection

In order to create a browser, Actor will try to resolve these environment variables:

 * SELENIUM_HOST
 * SELENIUM_CONNECTION_TIMEOUT
 * SELENIUM_REQUEST_TIMEOUT
 * SELENIUM_BROWSER - for a list of available browser presets see Selenium integrations library's DesiredCapabilities 
 class (methods like 'chrome', 'firefox')
 
So in order to change, say, a host when running a test suite using Docker you would need to use `-e` flag, for example:
    
    docker run --network=host -it --rm -v $(pwd):/mnt/tmp -w /mnt/tmp -e SELENIUM_HOST=http://my-host modera/php5-fpm bash -c "./vendor/bin/phpunit"

### Actors behaviours

Behaviours are used to tell actors, you know, how they should behave in certain situations. For example, you have
an actor "admin" and you want to configure it so that its browser woudln't be maximized automatically, you would
do something like this:

    $harness->getActor('admin')->disableBehaviour(Actor::BHR_AUTO_MAXIMIZE);
    
For a full list of available behaviours see Actor::BHR_* constants.

# Headless environment

In order to make it possible to record video of selenium tests being run we use are relying on these components:

* Selenium - launches browsers and controls them
* XVFB - is used to start Selenium node, creates a virtual frame buffer for browser windows that Selenium opens
* FFMPEG - connects to a remote XVFB and grabs the virtual screen created by it
* FFMPEG REST server - runs on a host where FFMPEG is installed, the server starts/stops video recording and creates 
properly named video files
* PHPUnit/Behat listener - listeners send requests to the FFMPEG REST server to inform it when the server needs to start
 recording video and then eventually stop and dump it to a file

        All these components must run in one private sub-network or be publicly accessible from web, for this setup
        to work correctly all components must be able to connecto to each other. 

Before we get into details it is worth mentioning that though in a setup described below most of these components run
on different hosts (it gives more flexibility to run tests in parallel in the future) in some situations it might be
fine to run several components on the same host as well - like running FFMPEG REST Server/FFMPEG on the same host where 
you are running tests from.

The easiest way to try to run tests in a headless environment is to use a seed configuration which is shipped with this 
library and is located in `Resources/dockerized-video-recorder` directory. In this manual we assume
that your application is using Whaler to configure docker containers, copy `Resources/dockerized-video-recorder/.docker`
and `Resources/dockerized-video-recorder/ffmpeg-server-web` directories next to your root whaler.yml file and then run 
these two commands:
 
 * whaler create --config vendor/modera/selenium-tools/Resources/dockerized-video-recorder/whaler.yml
 * whaler start
 
One last step might be needed to before we can move on to section describing how to write tests - check 
`ffmpeg-server-web/index.php` that its `require` statement has a proper path for `autoload.php` file.

## Testing with plain PHPUnit

If you want to run tests using PHPUnit you will need to slightly tweak your phpunit.xml.dist, we will need to add 
a listener and configure `RRL_ENDPOINT` environment variable, so the file would look similar to this:

    <?xml version="1.0" encoding="UTF-8"?>
    <phpunit backupGlobals="false"
             backupStaticAttributes="false"
             colors="true"
             convertErrorsToExceptions="true"
             convertNoticesToExceptions="true"
             convertWarningsToExceptions="true"
             processIsolation="false"
             stopOnFailure="false"
             syntaxCheck="false"
             bootstrap="vendor/autoload.php"
    >
    
        <php>
            <server name="RRL_ENDPOINT" value="vr-nginx" />
        </php>
    
        <listeners>
            <listener class="Modera\Component\SeleniumTools\VideoRecording\RemotePHPUnitListener\RemoteReportingListener" />
        </listeners>
    
        <testsuites>
            <testsuite name="Sample testcase">
                <directory>./tests/</directory>
            </testsuite>
        </testsuites>
    </phpunit>

`RRL_ENDPOINT` is used by RemoteReportingListener to determine where "FFMPEG REST server" is located.
        
Since previously we were running our AppearInTest test using a Selenium node running on a host machine, but now
running inside an orchestrated dockerized environment we need to update a snippet of code responsible for establishing
a connection to server:

    $driver = RemoteWebDriver::create(
        'http://selenium-hub:4444/wd/hub', $capabilities, 15 * 1000, 30 * 1000
    );
    
Once this is done you can run `whaler run php. "./vendor/bin/phpunit"` to run tests. Once test is run you check 
`ffmpeg-server-web` directory for video recordings.

## Testing with Behat

If you want to run your tests using Behat then you need to install a Behat package to you project 
(`composer require behat/behat:^3.0`) and create a file called `behat.yml` in a root of your project directory. This is 
how a [sample configuration for Modera Foundation](https://github.com/modera/foundation-standard/blob/master/behat.yml) file might 
look like:

    default:
        extensions:
            Modera\Component\SeleniumTools\Behat\ActorsExtension:
                drivers:
                    default:
                        browser: chrome
                        host: "%BEHAT_SELENIUM_HOST%"
                video_recorder:
                    host: "%BEHAT_VR_HOST%"
                harnesses:
                    default:
                        driver: default
                        actors:
                            admin:
                                base_url: "%BEHAT_BACKEND_BASE_URL%"
                            john.doe:
                                base_url: "%BEHAT_BACKEND_BASE_URL%"
        suites:
            default:
                contexts:
                    - Modera\Component\SeleniumTools\Behat\Context\MJRContext
                    - Modera\Component\SeleniumTools\Behat\Context\ExtJsGridContext

The main thing here to pay attention to is that we are telling Behat to use "ActorsExtension", this extension
is responsible for configuring actors as well as video recorder. In behat.yml configuration file you can use
refer to environment variables by wrapping your words with percentage sign. As you can see in this specific
configuration file we are telling behat to attempt to extract three values from the environment:
- `BEHAT_SELENIUM_HOST` - host name where Selenium is running. If you are using `Resources/dockerized-video-recorder/whaler.yml`
seed configuration file then `http://selenium-hub:4444/wd/hub` must be used for this variable.
- `BEHAT_VR_HOST` - a host name where you have FFMPEG REST server running. If seed `whaler.yml` is used then you can use
`http://vr-nginx`.
- `BEHAT_BACKEND_BASE_URL` - in this specific scenario this variable is used to tell what URL actor should start with.

In you `behat.yml` you may not use environment variables to to store configuration, but it is considered a good practise
to avoid storing environment-depending configuration data in configuration files.

When the above configuration file is used (and you have Modera Foundation installed), then in order to run the
 tests (which are located by default in [features](https://github.com/modera/foundation-standard/tree/master/features) 
 directory) you can run this command:

`whaler run php. "./vendor/bin/behat" -e BEHAT_SELENIUM_HOST=http://selenium-hub:4444/wd/hub -e BEHAT_VR_HOST=http://vr-nginx -e BEHAT_BACKEND_BASE_URL=http://nginx/app_dev.php/backend/`

### Writing your own multi-user aware Behat context files

The easiest way to create a Behat context file that supports multi-user E2E is to extend 
`\Modera\Component\SeleniumTools\Behat\Context\HarnessAwareContext`. To learn how to write your own FeatureContext files
take a look at these two links:
* [Behat official guide on writing custom FeatureContext files](http://behat.org/en/latest/user_guide/context.html)
* For multi-user aware sample of FeatureContext files see `Modera\Component\SeleniumTools\Behat\Context\MJRContext`
and `Modera\Component\SeleniumTools\Behat\Context\ExtJsGridContext`.

# Testing ExtJs/MJR application (experimental)

For sample tests please see [Modera Foundation](https://github.com/modera/foundation-standard/tree/master/features).