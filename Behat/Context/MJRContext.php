<?php

namespace Modera\Component\SeleniumTools\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Modera\Component\SeleniumTools\TestHarness;
use Modera\Component\SeleniumTools\Actor;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Modera\Component\SeleniumTools\PageObjects\MJRBackendPageObject;
use Modera\Component\SeleniumTools\Querying\ExtDeferredQueryHandler;
use Modera\Component\SeleniumTools\Querying\By;
use Facebook\WebDriver\WebDriverKeys;

require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Defines application features from the specific context.
 */
class MJRContext extends HarnessAwareContext
{
    /**
     * @Given I am on a dashboard section
     */
    public function iAmOnASection()
    {
        $this->runActiveActor(function($admin, $actor, $backend, ExtDeferredQueryHandler $q) {
            $q->runWhenComponentAvailable('modera-backdashboard-dashboardpanel', 'return true;');
        });
    }

    /**
     * @Given it is programatically emulated that my session is expired
     */
    public function itIsProgramaticallyEmulatedThatMySessionIsExpired()
    {
        $this->runActiveActor(function(RemoteWebDriver $admin) {
            $admin->executeScript("ModeraFoundation.getApplication().getContainer().get('security_manager').logout(Ext.emptyFn);");

            sleep(1);
        });
    }

    /**
     * @Then I navigate to :section
     * @Then now when I try to switch a section to :section
     * @Given as a user :actor now when I try to switch a section to :section
     */
    public function iNavigateToSection($section)
    {
        $this->runActiveActor(function($admin, $actor, MJRBackendPageObject $backend) use($section) {
            $backend->clickMenuItemWithLabel($section);

            sleep(1);
        });
    }

    /**
     * @Given I click on :section in Tools view
     */
    public function iClickOnSectionInToolsView($section)
    {
        $this->runActiveActor(function($admin, $actor, MJRBackendPageObject $backend) use($section) {
            $backend->clickToolsSectionWithLabel($section);

            sleep(1);
        });
    }

    /**
     * @Then a session expired notification modal window must be presented to a user
     */
    public function aSessionExpiredNotificationModalWindowMustBePresentedTOAUser()
    {
        $this->runActiveActor(function($admin, $actor, $backend, ExtDeferredQueryHandler $q) {
            $q->runWhenComponentAvailable('window[tid=authRequiredWindow]');
        });
    }

    /**
     * @Given I click on Close button in the session expired notification window
     */
    public function iClickOnCloseButtonInTheSessionExpiredNotificationWindow()
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, Actor $actor, MJRBackendPageObject $backend, ExtDeferredQueryHandler $q) {
            $button = $q->extComponentDomId('window[tid=authRequiredWindow] component[tid=closeWindowBtn]');

            $admin->findElement($button)->click();

            sleep(2);
        });
    }

    /**
     * @Then the a page must be reloaded and initial login page must be displayed
     */
    public function theAPageMustBeReloadedAndInitialLoginPageMustBeDisplayed()
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, Actor $actor, MJRBackendPageObject $backend, ExtDeferredQueryHandler $q) {
            assertNull($admin->executeScript("ModeraFoundation.getApplication().getContainer().get('security_context').getUser()"));
        });
    }

    /**
     * @Given I click on Ok button in the window
     */
    public function iClickCloseOkButtonInTheWindow()
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, Actor $actor, MJRBackendPageObject $backend, ExtDeferredQueryHandler $q) {
            $button = $q->extComponentDomId('window[tid=authRequiredWindow] component[tid=closeWindowBtn]');

            $admin->findElement($button)->click();

            sleep(2);
        });
    }

    /**
     * @Then window is no longer displayed
     */
    public function windowIsNoLongerDisplayed()
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, Actor $actor, MJRBackendPageObject $backend, ExtDeferredQueryHandler $q) {
            assertNull($admin->executeScript('Ext.ComponentQuery.query("window[tid=authRequiredWindow]")[0]'));
        });
    }

    /**
     * @Given I click on Login button in the window
     */
    public function iClickOnLoginButtonInTheWindow()
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, Actor $actor, MJRBackendPageObject $backend, ExtDeferredQueryHandler $q) {
            $button = $q->extComponentDomId('window[tid=authRequiredWindow] component[tid=loginBtn]');

            $admin->findElement($button)->click();

            sleep(2);
        });
    }

    /**
     * @Then view :tid is visible
     * @Then panel :tid is visible
     * @Then grid :tid is visible
     * @Then window :tid is visible
     *
     * @Then window :tid should stay visible
     *
     * @Then view :tid must be visible
     * @Then window :tid must be visible
     *
     * @Then view :tid must be shown
     * @Then window :tid must be shown
     *
     * @Then I can see :tid
     */
    public function viewIsVisible($tid)
    {
        $this->runActiveActor(function($admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid) {
            $q->waitUntilComponentAvailable("component[tid=$tid]");

            sleep(1);
        });
    }

    /**
     * @When view :tid is not visible
     * @When panel :tid is not visible
     * @When grid :tid is not visible
     * @When window :tid is not visible
     *
     * @Then window :tid must be closed
     */
    public function viewIsNotVisible($tid)
    {
        // TODO
    }

    /**
     * When I click "button" named "importBtn"
     *
     * @When I click :componentType named :tid
     * @When I click :componentType :tid
     */
    public function iClickElementOfType($componentType, $tid)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($componentType, $tid) {
            $button = $q->extComponentDomId("{$componentType}[tid=$tid]");

            $admin->findElement($button)->click();

            sleep(1);
        });
    }

    /**
     * @When I click :tid
     */
    public function iClickElement($tid)
    {
        $this->iClickElementOfType('component', $tid);
    }

    /**
     * @When I click header :text in a grid :tid
     */
    public function iClickHeaderWithTextInAGrid($text, $tid)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($text, $tid) {
            $column = $q->extComponentDomId("grid[tid=$tid] gridcolumn[text=$text]");

            $admin->findElement($column)->click();

            sleep(1);
        });
    }

    /**
     * @Then in grid :tid row with position :position column :label must be equal to :expectedValue
     */
    public function gridColumnValueMustBe($tid, $position, $columnLabel, $expectedValue)
    {
        $this->runActiveActor(function($admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $position, $columnLabel, $expectedValue) {
            $dataIndex = $q->runWhenComponentAvailable("grid[tid=$tid] gridcolumn[text=$columnLabel]", 'return firstCmp.dataIndex');

            assertNotNull($dataIndex);

            $givenValue = $q->runWhenComponentAvailable(
                "grid[tid=$tid]",
                "var view = firstCmp.getView(); var node = view.getNode($position); return view.getRecord(node).get('$dataIndex')"
            );

            assertEquals($expectedValue, $givenValue);
        });
    }

    /**
     * @When I type text :text in field :tid
     * @When I type text :text in textarea :tid
     *
     * @When I type :text in field :tid
     * @When I type :text in textarea :tid
     */
    public function iTypeTextInField($text, $tid)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($text, $tid) {
            // We cannot simply query by $tid, because it returns HTML <table> element instead of <input> that we need
            $js = <<<'JS'
    var fieldDomId = firstCmp.el.dom.id;
    
    var inputs = Ext.query("#"+fieldDomId+" input");
    if (inputs[0]) {
        return inputs[0].id;
    }
    
    var textareas = Ext.query("#"+fieldDomId+" textarea");
    if (textareas[0]) {
        return textareas[0].id;
    }
            
    throw "Unable to find neither 'input' nor 'textarea' for given TID.";
JS;

            $input = By::id($q->runWhenComponentAvailable("component[tid=$tid]", $js));

            $element = $admin->findElement($input);
            $element->sendKeys($text);

            sleep(1);
        });
    }

    /**
     * @When I wait for :seconds seconds
     * @When I wait :seconds seconds
     */
    public function iWaitForSeconds($seconds)
    {
        sleep($seconds);
    }

    /**
     * TODO Refactor, is is pretty much copy-paste of iTypeTextInField
     *
     * @When I clear text in a field :tid
     * @When I clear text in a textarea :tid
     */
    public function iClearTextInField($tid)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid) {
            // We cannot simply query by $tid, because it returns HTML <table> element instead of <input> that we need
            $js = <<<'JS'
    var fieldDomId = firstCmp.el.dom.id;
    
    var inputs = Ext.query("#"+fieldDomId+" input");
    if (inputs[0]) {
        return inputs[0].id;
    }
    
    var textareas = Ext.query("#"+fieldDomId+" textarea");
    if (textareas[0]) {
        return textareas[0].id;
    }
            
    throw "Unable to find neither 'input' nor 'textarea' for given TID.";
JS;

            // We cannot simply query by $tid, because it returns HTML <table> element instead of <input> that we need
            $inputEl = By::id($q->runWhenComponentAvailable("component[tid=$tid]", $js));

            $input = $admin->findElement($inputEl);
            $input->clear();
            $input->sendKeys(WebDriverKeys::UP);

            sleep(1);
        });
    }

    /**
     * @When I refresh the page
     */
    public function iRefreshThenPage()
    {
        $this->runActiveActor(function(RemoteWebDriver $admin) {
            $admin->executeScript('window.location.reload();');
            sleep(1);
        });
    }

    /**
     * @Then I see a piece of text :text
     */
    public function iSeePieceOfText($text)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin) use($text) {
            assertContains($text, $admin->getPageSource());
        });
    }

    /**
     * @When I authenticate as :username with password :password
     * @When I login as :username with password :password
     */
    public function iAuthenticateAs($username, $password)
    {
        $this->switchActor($username);
        $this->runActiveActor(function($admin, $actor, MJRBackendPageObject $backend) use($username, $password) {
            $backend->login($username, $password);

            sleep(1);
        });
    }

    /**
     * @Then I am not authenticated
     * @Then I must not be authenticated
     */
    public function iAmNotAuthenticated()
    {
        $this->isActorAuthenticated(function($isAuthenticated) {
            assertEquals(false, $isAuthenticated);
        });
    }

    /**
     * @Then I am successfully authenticated
     * @Then I must be successfully authenticated
     */
    public function iAmSuccessfullyAuthenticated()
    {
        $this->isActorAuthenticated(function($isAuthenticated) {
            assertEquals(true, $isAuthenticated);
        });
    }

    private function isActorAuthenticated($isAuthenticatedCallback)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin) use($isAuthenticatedCallback) {
            $js = <<<JS
var done = arguments[arguments.length-1],
    sm = ModeraFoundation.getApplication().getContainer().get('security_manager');

sm.isAuthenticated(function(result) {
    done(result.success);
});
JS;

            $admin->manage()->timeouts()->setScriptTimeout(5);

            $isAuthenticatedCallback($admin->executeAsyncScript($js));
        });
    }

    /**
     * @When we switch back to :username
     * @When session is switched to :username
     */
    public function sessionIsSwitchedTo($username)
    {
        $this->switchActor($username);
    }

    /**
     * @When I expand menu for button :tid
     */
    public function iExpandMenuForButton($tid)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid) {
            $js = <<<JS
var button = firstCmp;

return [button.getWidth(), button.getHeight(), button.el.dom.id];
JS;

            $result = $q->runWhenComponentAvailable("button[tid=$tid] ", $js);

            list($width, $height, $domId) = $result;

            $button = $admin->findElement(By::id($domId));

            $admin->action()
                ->moveToElement($button, $width - 5, 5)
                ->click()
                ->perform()
            ;
        });
    }
}
