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
use PHPUnit\Framework\Assert;

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
     * @Then I activate :tab tab
     */
    public function iActivateTab($tab)
    {

        $this->runActiveActor(function($admin, $actor, MJRBackendPageObject $backend) use($tab) {
            $backend->clickTabItemWithLabel($tab);

            sleep(1);
        });
    }

    /**
     * @Then I activate left menu item :tab
     */
    public function iActivateLeftMenuItem($tab)
    {

        $this->runActiveActor(function(RemoteWebDriver $admin, Actor $actor, MJRBackendPageObject $backend, ExtDeferredQueryHandler $q) use($tab) {
            $button = $q->extComponentDomId("mfc-touchmenu component[tid=$tab]");

            $admin->findElement($button)->click();

            sleep(2);
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
            Assert::assertNull($admin->executeScript("ModeraFoundation.getApplication().getContainer().get('security_context').getUser()"));
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
            Assert::assertNull($admin->executeScript('Ext.ComponentQuery.query("window[tid=authRequiredWindow]")[0]'));
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
     * @Then badge :tid is visible
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
     * @Then confirmation window is visible
     * @Then message box is visible
     * @Then alert window is visible
     */
    public function messageboxIsVisible()
    {
        $this->runActiveActor(function($admin, $actor, $backend, ExtDeferredQueryHandler $q) {
            $q->waitUntilComponentAvailable("messagebox");

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

            Assert::assertNotNull($dataIndex);

            $givenValue = $q->runWhenComponentAvailable(
                "grid[tid=$tid]",
                "var view = firstCmp.getView(); var node = view.getNode($position); return view.getRecord(node).get('$dataIndex')"
            );

            Assert::assertEquals($expectedValue, $givenValue);
        });
    }

    /**
     * @When I replace text :text in field :tid
     * @When I replace text :text in textarea :tid
     *
     * @When I replace :text in field :tid
     * @When I replace :text in textarea :tid
     *
     * @When I replace text :text in :nth field :tid
     * @When I replace text :text in :nth textarea :tid
     *
     * @When I replace :text in :nth field :tid
     * @When I replace :text in :nth textarea :tid
     */
    public function iReplaceTextInField($text, $tid, $nth = 1)
    {
        if ($nth == 'first') {
            $nth = 1;
        } else if ($nth == 'second') {
            $nth = 2;
        } else if ($nth == 'third') {
            $nth = 3;
        } else if ($nth == 'fourth') {
            $nth = 4;
        }

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($text, $tid, $nth) {
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

            $input = By::id($q->runWhenComponentAvailable("component[tid=$tid]:nth-child({$nth}n)", $js));

            $element = $admin->findElement($input);
            $element->clear();
            $element->sendKeys($text);

            sleep(1);
        });
    }

    /**
     * @Then I see text :text in :tid
     * @Then I see value :text in :tid
     * @Then I see text :text in field :tid
     * @Then I see text :text in textarea :tid
     * @Then Value in field :tid must be equal to :text
     * @Then Value in textarea :tid must be equal to :text
     *
     * @Then I see :text in field :tid
     * @Then I see :text in textarea :tid
     * @Then Value in :tid must be equal to :text
     *
     * @Then I see text :text in :nth field :tid
     * @Then I see text :text in :nth textarea :tid
     * @Then Value in :nth field :tid must be equal to :text
     * @Then Value in :nth textarea :tid must be equal to :text
     *
     * @Then I see :text in :nth field :tid
     * @Then I see :text in :nth textarea :tid
     * @Then Value in :nth :tid field must be equal to :text
     * @Then Value in :nth :tid textarea must be equal to :text
     */
    public function iSeeValueInField($text, $tid, $nth = 1)
    {
        if ($nth == 'first') {
            $nth = 1;
        } else if ($nth == 'second') {
            $nth = 2;
        } else if ($nth == 'third') {
            $nth = 3;
        } else if ($nth == 'fourth') {
            $nth = 4;
        }

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($text, $tid, $nth) {

            $js = <<<'JS'
    if (firstCmp.xtype == 'combobox' || firstCmp.xtype == 'combo') {
         return firstCmp.getDisplayValue();
    } else if (firstCmp.xtype == 'button') {
         return firstCmp.text;
    } else if (firstCmp.xtype == 'box') {
         return firstCmp.html;
    } else {
         return firstCmp.getValue();
    }
JS;

            $value = $q->runWhenComponentAvailable("component[tid=$tid]:nth-child({$nth}n)", $js);
var_dump($text, $value, $text == $value);
            //Assert::assertEquals($text, $value);

        });
    }

    /**
     * @When I type text :text in field :tid
     * @When I type text :text in textarea :tid
     *
     * @When I type :text in field :tid
     * @When I type :text in textarea :tid
     *
     * @When I type text :text in :nth field :tid
     * @When I type text :text in :nth textarea :tid
     *
     * @When I type :text in :nth field :tid
     * @When I type :text in :nth textarea :tid
     */
    public function iTypeTextInField($text, $tid, $nth = 1)
    {
        if ($nth == 'first') {
            $nth = 1;
        } else if ($nth == 'second') {
            $nth = 2;
        } else if ($nth == 'third') {
            $nth = 3;
        } else if ($nth == 'fourth') {
            $nth = 4;
        }

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($text, $tid, $nth) {
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

            $input = By::id($q->runWhenComponentAvailable("component[tid=$tid]:nth-child({$nth}n)", $js));

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
     * @When I load url :url
     */
    public function iLoadUrl($url)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin) use ($url) {
            $admin->executeScript('window.location = "' . $url.'";');
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
            Assert::assertContains($text, $admin->getPageSource());
        });
    }

    /**
     * @Then I see a piece of text matched to :pattern
     */
    public function iSeePieceOfTextMatchedTo($pattern)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin) use($pattern) {

            $pattern = preg_replace_callback('/([^*])/', function($m) {
                return preg_quote($m[1],"/");
            }, $pattern);
            $pattern = str_replace('*', '.*', $pattern);
            Assert::assertTrue((bool) preg_match('/' . $pattern . '/i', $admin->getPageSource()));
        });
    }

    /**
     * @Then I do not see a piece of text matched to :pattern
     */
    public function iDoNotSeePieceOfTextMatchedTo($pattern)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin) use($pattern) {

            $pattern = preg_replace_callback('/([^*])/', function($m) {
                return preg_quote($m[1],"/");
            }, $pattern);
            $pattern = str_replace('*', '.*', $pattern);

            Assert::assertFalse((bool) preg_match('/' . $pattern . '/i', $admin->getPageSource()));
        });
    }

    /**
     * @Then I do not see a piece of text :text
     */
    public function iDoNotSeePieceOfText($text)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin) use($text) {
            Assert::assertNotContains($text, $admin->getPageSource());
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
            Assert::assertEquals(false, $isAuthenticated);
        });
    }

    /**
     * @Then I am successfully authenticated
     * @Then I must be successfully authenticated
     */
    public function iAmSuccessfullyAuthenticated()
    {
        $this->isActorAuthenticated(function($isAuthenticated) {
            Assert::assertEquals(true, $isAuthenticated);
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

    /**
     * @When I click :button button in confirmation window
     */
    public function IClickButtonInConfirmationWindow($button)
    {

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($button) {

            $button = $q->extComponentDomId("messagebox button[itemId={$button}]");

            $admin->findElement($button)->click();

            sleep(2);

        });
    }

    /**
     * @When I choose date :date in field :tid
     */
    public function iChooseDateInField($date, $tid, $nth = 1)
    {

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $date, $nth) {
            $js = <<<JS
var dateField = firstCmp;
dateField.setValue(new Date('%expectedValue%'));
return true;
JS;
            $js = str_replace(['%expectedValue%'], [$date], $js);

            $q->runWhenComponentAvailable("component[tid=$tid]", $js);

        });
    }

    /**
     * @When I select option :option in combo :tid
     * @When I select option :option in :nth combo :tid
     */
    public function iSelectOptionsInCombo($option, $tid, $nth = 1)
    {

        if ($nth == 'first') {
            $nth = 1;
        } else if ($nth == 'second') {
            $nth = 2;
        } else if ($nth == 'third') {
            $nth = 3;
        } else if ($nth == 'fourth') {
            $nth = 4;
        }

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $option, $nth) {
            $js = <<<JS
var combo = firstCmp;
var store = combo.getStore();
var record = store.findRecord(combo.displayField, '%expectedValue%');
if (!record) {
    throw "Unable to find a record where option value is equal to '%expectedValue%'." ;
}
combo.select(record);
return true;
JS;
            $js = str_replace(['%expectedValue%'], [$option], $js);

            $q->runWhenComponentAvailable("combo[tid=$tid]:nth-child({$nth}n)", $js);

        });
    }

    /**
     * @When I select radio option :option
     */
    public function iSelectRadioOption($option)
    {

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($option) {

            // We cannot simply query by $tid, because it returns HTML <table> element instead of <input> that we need
            $js = <<<'JS'
    var fieldDomId = firstCmp.el.dom.id;

    var inputs = Ext.query("#"+fieldDomId+" input");
    if (inputs[0]) {
        return inputs[0].id;
    }

    throw "Unable to find 'input' for given TID.";
JS;

            // We cannot simply query by $tid, because it returns HTML <table> element instead of <input> that we need
            $inputEl = By::id($q->runWhenComponentAvailable("radiofield[tid=$option]", $js));

            $admin->findElement($inputEl)->click();

            sleep(1);

        });
    }

    /**
     * @When I expand options for select :tid
     */
    public function iExpandOptionsForSelect($tid)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid) {
            $js = <<<JS
var button = firstCmp;

return [button.getWidth(), button.getHeight(), button.el.dom.id];
JS;

            $result = $q->runWhenComponentAvailable("combobox[tid=$tid] ", $js);

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