# MultiSessionSelenium

Allows to run multi-session test-cases using Selenium & optionally recording videos.

## Recording videos

In order to make it possible to record video of selenium tests being run we use are relying on these components:

* Selenium - launches browsers and controls them
* XVFB - is used to start Selenium node, creates a virtual frame buffer for browser windows that Selenium opens
* FFMPEG - connects to a remote XVFB and grabs the virtual screen created by it
* FFMPEG REST server - runs on a host where FFMPEG is installed, the server starts/stops video recording and creates 
properly named video files
* PHPUnit listener - used on a machine where you are running tests, this listeners sends requests to the FFMPEG REST server
to inform it when the server needs to start recording video and then eventually stop and dump it to a file

        All these components must run in one private sub-network or be publicly accessible from web, for this setup
        to work correctly all components must be able to connecto to each other. 

Before we get into details it is worth mentioning that though in a setup described below most of these components run
on different hosts (it gives more flexibility to run tests in parallel in the future) in some situations it might be
fine to run several components on the same host as well - like running FFMPEG REST Server/FFMPEG on the same host where 
you are running tests from.

## Configuring VR (video recorder)
 
First of all you need to fetch a component which contains required utility classes, for this need to run the following
command from your project (make sure that `packages.dev.modera.org` repository is added to your composer.json before
running the command):

    composer require modera/selenium-tools:dev-master

In a project where you have selenium tests and you want to record videos of their execution you need to modify your 
phpunit.xml.dist (preferred way) to add RemoteReportingListener listener and configure endpoint for it, here's how a 
sample file could look like:

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
            <server name="SELENIUM_HOST" value="selenium" />
        </php>
    
        <listeners>
            <listener class="Modera\Component\SeleniumTools\VideoRecording\RemotePHPUnitListener\RemoteReportingListener" />
        </listeners>
    
        <testsuites>
            <testsuite name="Test Suite">
                <directory>./tests/</directory>
            </testsuite>
        </testsuites>
    </phpunit>

This configuration means that FFMPEGServer runs on an endpoint (in our case it just a host, running on the same machine) 
- "vr-nginx", which is configured using "RRL_ENDPOINT" server variable. So now when you are running tests using PHPUnit 
the RemoteReportingListener will attempt to send requests to "vr-nginx" host when tests start/end. Also here we define
"SELENIUM_HOST" variable which point to a host where Selenium node is running.

Now that we have a listener configured we need to start a server on "vr-nginx" host which would listen to those requests and 
then start/stop recording videos accordingly. The easiest way to achieve that is to use a seed configuration which is 
shipped with this library and is located in `Resources/dockerized-video-recorder` directory. In this manual we assume
that your application is using Whaler to configure docker containers, so the next step you need to do is to append contents
of `Resources/dockerized-video-recorder/whaler.yml` to your root whaler.yml, copy `Resources/dockerized-video-recorder/.docker`
and `Resources/dockerized-video-recorder/ffmpeg-server-web` directories next to your root whaler.yml file. Once these 
files are copied you need to update the whaler application's config by running `whaler config --update` and then rebuild 
it using `whaler rebuild`.

One last step might be needed to before we can move on to section describing how to write tests - check 
`ffmpeg-server-web/index.php` that its `require` statement has a proper path for `autoload.php` file.

When writing your tests you may want to extend `Modera\Component\SeleniumTools\SeleniumTestCase` and it adds
a few utility methods simplifying some things like connecting to Selenium server or managing multi-session test
cases. By default SeleniumTestCase assumes that Selenium server is running on a host named "selenium", so if you used
seed configuration from `Resources/dockerized-video-recorder` directory you don't need doing any extra steps to 
have everything working.

Now that we have everything configured let's create a basic test-case to make sure that everything's working. In a directory 
`tests` (you might need to create it, if you have other directory used to store tests and want to use it then you need to 
update phpunit.xml.dist) create a file ExampleComTest.php with the following contents:

    <?php
    
    use Modera\Component\SeleniumTools\SeleniumTestCase;
    use Selenium\Browser;
    
    class ExampleComTest extends SeleniumTestCase
    {
        /**
         * @var Browser
         */
        private $browser;
    
        public function setUp()
        {
            $this->browser = $this->getClient()->getBrowser('http://example.com');
            $this->browser->start();
            $this->browser->windowFocus();
            $this->browser->windowMaximize();
        }
    
        public function tearDown()
        {
            $this->browser->stop();
            $this->browser = null;
        }
    
        public function testHowWellExampleComWorks()
        {
            $b = $this->browser;
    
            $b->open('/');
            $this->assertContains('Example Domain', $b->getHtmlSource());
    
            $b->click(\Selenium\Locator::linkContaining('More information...'));
            sleep(2);
    
            $this->assertEquals('http://www.iana.org/domains/reserved', $b->getLocation());
            $this->assertContains('IANA-managed Reserved Domains', $b->getHtmlSource());
    
            sleep(3);
        }
    }
    
And now run phpunit, if no extra manual configuration has been done then simply running `/vendor/bin/phpunit` should do it. If
you got a green bar then everything is indeed working, to see a recorded video navigate to `ffmpeg-server-web` directory,
there should will find `example-com-test.mp4` video file.

## Multi-session testing scenario

Here's a sample test case:

    class ChatTest extends \Modera\Component\SeleniumTools\SeleniumTestCase
    {
        /**
         * @var Scenario
         */
        private $scenario;
    
        public function setUp()
        {
            $this->scenario = new Scenario($this->getClient(), function($browser) {
                return [new ExtDeferredQueryHandler($browser)];
            });
    
            $this->scenario->addActor('admin', 'http://chat-app.ci2.dev.modera.org/backend');
            $this->scenario->addActor('user', 'http://chat-app.ci2.dev.modera.org/');
        }
    
        public function tearDown()
        {
            $this->scenario->halt();
        }
    
        public function testMessageExchange()
        {
            $tc = $this;
    
            $this->scenario->runAs('user', function(Browser $user, Actor $actor, ExtDeferredQueryHandler $q)  {
                $user->open('/chat/sandbox');
    
                $sc = $actor->getScenario();
    
                $sc->setContextValue('roomId', 'roomId_'.time());
    
                $user->type($q->named(['field', 'enter the key here...']), $sc->getContextValue('roomId'));
                sleep(1);
                $user->click($q->named(['button', 'Launch']));
                sleep(5);
    
                $sc->setContextValue('userFullName', 'John '.time());
                $sc->setContextValue('userMsg1', 'Hello, I am sending you this message from frontend using Selenium! '.time());
    
                $user->click($q->css('a.bubble'));
                sleep(1);
                $user->type($q->named(['field', 'Your name']), $sc->getContextValue('userFullName'));
                sleep(1);
                $user->type($q->named(['field', 'Enter correct e-mail please']), 'john.doe@example.org');
                sleep(1);
                $user->type($q->named(['field', 'Your message*']), $sc->getContextValue('userMsg1'));
                sleep(1);
                $user->click($q->named(['button', 'Send your message']));
    
                sleep(2);
            });
    
            $this->scenario->runAs('admin', function(Browser $admin, Actor $actor, ExtDeferredQueryHandler $q) use($tc) {
                $admin->open('/');
                sleep(1);
    
                $sc = $actor->getScenario();
    
                $admin->type($q->named(['field', 'User ID']), 'admin');
                $admin->type($q->named(['field', 'Password']), '1234');
                $admin->click($q->named(['button', 'Sign in']));
    
                $admin->click($q->extComponentId('tab[text=Chat]'));
                sleep(2);
    
                $admin->click(
                    $q->extGridColumnWithValue('modera-backend-chat-manager-list[itemId=online]',
                        'name',
                        $sc->getContextValue('userFullName')
                    )
                );
                sleep(1);
    
                $admin->click($q->extComponentId('button[tid=startChatBtn]'));
                sleep(3);
    
                $sc->setContextValue('adminMessage1', 'Hello! That is cool! '.time());
    
                $tc->assertContains($sc->getContextValue('userMsg1'), $admin->getHtmlSource());
    
                $admin->typeKeys($q->named(['field', 'type here ...']), $sc->getContextValue('adminMessage1'));
                $admin->type($q->named(['field', 'type here ...']), $sc->getContextValue('adminMessage1'));
                sleep(1);
                $admin->keyPress($q->named(['field', 'type here ...']), 13);
                sleep(3);
            });
    
            $this->scenario->runAs('user', function(Browser $user, Actor $actor, ExtDeferredQueryHandler $q) use($tc) {
                $sc = $actor->getScenario();
    
                $tc->assertContains($sc->getContextValue('adminMessage1'), $user->getHtmlSource());
            });
        }
    }