<?php

namespace Modera\Component\SeleniumTools\Behat\Context;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverKeys;
use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\PageObjects\MJRBackendPageObject;
use Modera\Component\SeleniumTools\Querying\By;
use Modera\Component\SeleniumTools\Querying\ExtDeferredQueryHandler;
use PHPUnit\Framework\Assert;
use Behat\Gherkin\Node\TableNode;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class ExtJsGridContext extends HarnessAwareContext
{
    /**
     * @When in grid :tid I click column :columnLabel at position :position
     */
    public function inGridClickColumnAtPosition($tid, $columnLabel, $position)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $position, $columnLabel) {
            $js = <<<'JS'
var grid = firstCmp;
var column = grid.down("gridcolumn[text=%columnLabel%]");
var cellCssSelector = grid.getView().getCellSelector(column);
var cell = Ext.query(cellCssSelector)[%position%];

return cell.id;
JS;
            $js = str_replace(['%columnLabel%', '%position%'], [$columnLabel, $position], $js);

            $cellDomId = $q->runWhenComponentAvailable("grid[tid=$tid] ", $js);
            $cell = $admin->findElement(By::id($cellDomId));
            $admin->action()->doubleClick($cell)->perform();
        });
    }

    /**
     * @When in grid :tid I click column :columnLabel in row which contains :expectedText piece of text
     */
    public function inGridIClickColumnAtRowWhichContainsPieceOfText($tid, $columnLabel, $expectedText)
    {

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $expectedText, $columnLabel) {
            $js = <<<'JS'
var grid = firstCmp;
var store = grid.getStore();
var columns = grid.query("gridcolumn");

var position = -1;
Ext.each(columns, function(column) {
    if (-1 === position) {
        position = store.find(column.dataIndex, '%expectedValue%')
    }
});

if (-1 === position) {
    return false;
}

var column = grid.down("gridcolumn[text=%columnLabel%]");
var cellCssSelector = grid.getView().getCellSelector(column);
var cell = Ext.query(cellCssSelector)[position];

return cell.id;
JS;
            $js = str_replace(['%columnLabel%', '%expectedValue%'], [$columnLabel, $expectedText], $js);

            $cellDomId = $q->runWhenComponentAvailable("grid[tid=$tid] ", $js);
            $cell = $admin->findElement(By::id($cellDomId));
            $admin->action()->doubleClick($cell)->perform();
        });
    }

    /**
     * @When in grid :tid I double-click column :columnLabel at position :position
     */
    public function inGridDoubleClickColumnAtPosition($tid, $columnLabel, $position)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $position, $columnLabel) {
            $js = <<<'JS'
var grid = firstCmp;
var column = grid.down("gridcolumn[text=%columnLabel%]");
var cellCssSelector = grid.getView().getCellSelector(column);
var cell = Ext.query(cellCssSelector)[%position%];

return cell.id;
JS;
            $js = str_replace(['%columnLabel%', '%position%'], [$columnLabel, $position], $js);

            $cellDomId = $q->runWhenComponentAvailable("grid[tid=$tid] ", $js);
            $cell = $admin->findElement(By::id($cellDomId));
            $admin->action()->doubleClick($cell)->perform();
        });
    }

    /**
     * @When in a grid :tid I click a cell whose column :columnLabel value is :expectedColumnValue
     */
    public function iClickRowInGridWhoseColumnValueIs($tid, $columnLabel, $expectedColumnValue)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $columnLabel, $expectedColumnValue) {
            $js = <<<JS
var grid = firstCmp;
var view = grid.getView();
var column = grid.down('gridcolumn[text=%columnLabel%]');

var rowPosition = grid.getStore().find(column.dataIndex, '%expectedColumnValue%');
if (-1 == rowPosition) {
    throw "Unable to find a record where '%columnLabel%' column's value is equal to '%expectedColumnValue%'." ;
}

var cellCssSelector = grid.getView().getCellSelector(column);
var cell = Ext.query(cellCssSelector)[rowPosition];

return cell.id;
JS;
            $js = str_replace(['%columnLabel%', '%expectedColumnValue%'], [$columnLabel, $expectedColumnValue], $js);

            $rowId = By::id($q->runWhenComponentAvailable("grid[tid=$tid]", $js));
            $row = $admin->findElement($rowId);
            $row->click();
        });
    }

    /**
     * @Then in grid :tid there must be no row whose column :columnTitle value is :value
     */
    public function inGridThereMustBeNoRowWhoseColumnValueIs($tid, $columnLabel, $value)
    {
        $this->runActiveActor(function($admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $columnLabel, $value) {
            $js = <<<JS
var grid = firstCmp;
var column = grid.down('gridcolumn[text=%columnLabel%]');

return grid.getStore().find(column.dataIndex, '%value%');
JS;
            $js = str_replace(['%columnLabel%', '%value%'], [$columnLabel, $value], $js);

            $rowPosition = $q->runWhenComponentAvailable("grid[tid=$tid]", $js);

            Assert::assertTrue(-1 == $rowPosition);
        });
    }

    /**
     * @Then grid :tid must contain a row with value :expectedValue
     */
    public function gridMustContainRowWithValue($tid, $expectedValue)
    {
        Assert::assertTrue($this->isRowWithTextFoundInGrid($tid, $expectedValue));
    }

    /**
     * @Then grid :tid must not contain a row with value :expectedValue
     */
    public function gridMustNotContainRowWithValue($tid, $expectedValue)
    {
        Assert::assertFalse($this->isRowWithTextFoundInGrid($tid, $expectedValue));
    }

    private function isRowWithTextFoundInGrid($tid, $expectedValue)
    {
        $isFound = false;

        $this->runActiveActor(function($admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $expectedValue, &$isFound) {
            $js = <<<'JS'
var grid = firstCmp;
var store = grid.getStore();
var columns = grid.query("gridcolumn");

var isFound = false;
Ext.each(columns, function(column) {
    if (-1 != store.find(column.dataIndex, '%expectedValue%')) {
        isFound = true;

        return false;
    }
});

return isFound;
JS;
            $js = str_replace(['%expectedValue%'], [$expectedValue], $js);

            $isFound = $q->runWhenComponentAvailable("grid[tid=$tid] ", $js);
        });

        return $isFound;
    }

    /**
     * @Then in workflow menu I click on :expectedText stage
     */
    public function inWorkflowMenuIclickStage($expectedText)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($expectedText) {
            $js = <<<'JS'
var grid = firstCmp;
var view = grid.getView();
var store = grid.getStore();
var columns = grid.query("gridcolumn");
var rowPosition =  store.findExact('name', '%expectedText%');
var isRowFound = -1 != rowPosition;
if (isRowFound) {
    return Ext.query('#'+grid.el.dom.id+' '+view.getDataRowSelector())[rowPosition].id;
} else {
    return -1;
}
JS;
            $js = str_replace(['%expectedText%'], [$expectedText], $js);

            $domId = $q->runWhenComponentAvailable("grid[tid=workflowStages] ", $js);
            Assert::assertNotEquals(-1, $domId);

            $button = $admin->findElement(By::id($domId));


            $admin->action()
                ->moveToElement($button, 10, 10)
                ->click()
                ->perform()
            ;

        });
    }

    /**
     * @Then in grid :tid I click a row which contains :expectedText piece of text
     */
    public function inGridIClickARowWhichContainsPieceOfText($tid, $expectedText)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $expectedText) {
            $js = <<<'JS'
var grid = firstCmp;
var view = grid.getView();
var store = grid.getStore();
var columns = grid.query("gridcolumn");

var rowPosition = -1;
Ext.each(columns, function(column) {
    rowPosition = store.find(column.dataIndex, '%expectedText%', 0, true);
    if (-1 != rowPosition) {
        return false;
    }
});

var isRowFound = -1 != rowPosition;
if (isRowFound) {
    return Ext.query('#'+grid.el.dom.id+' '+view.getDataRowSelector())[rowPosition].id;
} else {
    return -1;
}
JS;
            $js = str_replace(['%expectedText%'], [$expectedText], $js);

            $domId = $q->runWhenComponentAvailable("grid[tid=$tid] ", $js);
            Assert::assertNotEquals(-1, $domId);

            $admin->findElement(By::id($domId))->click();
        });
    }

    /**
     * You can use this method when you need to click a row but you don't care what cell will receive the click or the
     * grid simply doesn't have headers that you can use to locate a proper cell.
     *
     * @When in grid :tid I click row at position :position
     */
    public function inGridIClickRowAtPosition($tid, $position)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $position) {
            $js = <<<'JS'
var grid = firstCmp;
var view = grid.getView();

return Ext.query('#'+grid.el.dom.id+' '+view.getDataRowSelector())[%position%].id;
JS;
            $js = str_replace(['%position%'], [$position], $js);

            $rowDomId = $q->runWhenComponentAvailable("grid[tid=$tid] ", $js);

            $admin->findElement(By::id($rowDomId))->click();
        });
    }

    /**
     * @When in grid :tid I click first row
     */
    public function inGridIClickFirstRow($tid)
    {
        $this->inGridIClickRowAtPosition($tid, 0);
    }

    /**
     * @When in grid :tid I click a column :columnLabel where one of the cells contain :expectedText piece of text
     */
    public function inGridIClickCellWhereOneOfTheCellsContainPieceOfText($tid, $columnLabel, $expectedText)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $expectedText, $columnLabel) {
            $js = <<<'JS'
var grid = firstCmp;
var view = grid.getView();
var store = grid.getStore();
var columns = grid.query("gridcolumn");

var rowVerticalPosition = -1;
Ext.each(columns, function(column) {
    rowVerticalPosition = store.find(column.dataIndex, '%expectedText%', 0, true);
    if (-1 != rowVerticalPosition) {
        return false;
    }
});

var column = grid.down("gridcolumn[text=%columnLabel%]");
var cellCssSelector = view.getCellSelector(column);
var cell = Ext.query(cellCssSelector)[rowVerticalPosition];

return cell.id;
JS;
            $js = str_replace(['%expectedText%', '%columnLabel%'], [$expectedText, $columnLabel], $js);

            $domId = $q->runWhenComponentAvailable("grid[tid=$tid] ", $js);

            $admin->findElement(By::id($domId))->click();
        });
    }

    /**
     * @Then grid :tid must contain :rowsCount rows
     */
    public function gridMustContainRows($tid, $rowsCount)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $rowsCount) {
            $query = "grid[tid=$tid]";

            Assert::assertEquals($rowsCount, $q->runWhenComponentAvailable($query, 'return firstCmp.getStore().getCount();'));
        });
    }

    /**
     * @Then grid :tid must contain single row
     */
    public function gridMustContainSingleRows($tid)
    {
        $this->gridMustContainRows($tid, 1);
    }

    /**
     * @Then in grid :tid I change :expectedText to :value
     */
    public function inGridISetPropertyValue($tid, $expectedText, $value)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $expectedText, $value) {
            $js = <<<'JS'
var grid = firstCmp;
var view = grid.getView();
var store = grid.getStore();

var rowPosition = store.find('name', '%expectedText%', 0, true);

var isRowFound = -1 != rowPosition;
if (isRowFound) {
    return Ext.query('#'+grid.el.dom.id+' '+view.getDataRowSelector())[rowPosition].id;
} else {
    return -1;
}
JS;
            $js = str_replace(['%expectedText%'], [$expectedText], $js);

            $domId = $q->runWhenComponentAvailable("propertygrid[tid=$tid] ", $js);
            Assert::assertNotEquals(-1, $domId);

            $el = $admin->findElement(By::id($domId));
            $el->getLocationOnScreenOnceScrolledIntoView();
            $el->click();

            sleep(1);

            $admin->switchTo()->activeElement()->clear();

            $admin->getKeyboard()->sendKeys($value);

            $admin->getKeyboard()
                ->sendKeys(array(
                    WebDriverKeys::ENTER,
                ));

            sleep(1);

        });
    }

    /**
     * @Then in grid :tid I see :expectedText in row :name
     * @Then in grid :tid I see :expectedText as value for :name
     */
    public function inGridISeePropertyValue($tid, $expectedText, $name)
    {


//        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $expectedText, $name) {
//            $js = <<<'JS'
//var grid = firstCmp;
//var view = grid.getView();
//var store = grid.getStore();
//
//var rowPosition = store.find('name', '%name%', 0, true);
//
//var isRowFound = -1 != rowPosition;
//if (isRowFound) {
//    return Ext.query('#'+grid.el.dom.id+' '+view.getDataRowSelector())[rowPosition].id;
//} else {
//    return -1;
//}
//JS;
//            $js = str_replace(['%name%'], [$name], $js);
//
//            $domId = $q->runWhenComponentAvailable("propertygrid[tid=$tid] ", $js);
//            Assert::assertNotEquals(-1, $domId);
//
//            $el = $admin->findElement(By::id($domId));
//            $el->getLocationOnScreenOnceScrolledIntoView();
//            $el->click();
//
//            sleep(1);
//
//            var_dump($admin->switchTo()->activeElement());
//
//            //$admin->getKeyboard()->sendKeys($value);
//
//            $admin->getKeyboard()
//                ->sendKeys(array(
//                    WebDriverKeys::ENTER,
//                ));
//
//            sleep(1);
//
//        });



        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $expectedText, $name) {
            $js = <<<'JS'
var grid = firstCmp;
var store = grid.getStore();
return store.findRecord('name', '%name%').get('value');

JS;
            $js = str_replace(['%name%'], [$name], $js);

            $value = $q->runWhenComponentAvailable("propertygrid[tid=$tid]", $js);

            Assert::assertEquals($expectedText, $value);

        });
    }

    /**
     * @Then in grid :tid I see date :expectedText in row :name
     */
    public function inGridISeeDateValue($tid, $expectedText, $name)
    {

        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $name) {
            $js = <<<'JS'
var grid = firstCmp;
var store = grid.getStore();
return store.findRecord('name', '%name%', 0, true).get('value');

JS;
            $js = str_replace(['%name%'], [$name], $js);

            $value = $q->runWhenComponentAvailable("propertygrid[tid=$tid]", $js);

            Assert::assertTrue($value != 'null' && $value != '' && $value != 'false' && $value != '-');

        });
    }

    /**
     * @Then in grid :tid I see some value in row :name
     * @Then in grid :tid I see some date in row :name
     * @Then in grid :tid I see some text in row :name
     */
    public function inGridISeePropertySmth($tid, $name)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $name) {
            $js = <<<'JS'
var grid = firstCmp;
var store = grid.getStore();
return store.findRecord('name', '%name%', 0, true).get('value');

JS;
            $js = str_replace(['%name%'], [$name], $js);

            $value = $q->runWhenComponentAvailable("propertygrid[tid=$tid]", $js);

            Assert::assertTrue($value != 'null' && $value != '' && $value != 'false' && $value != '-');

        });
    }

    /**
     * @Then in grid :tid I see today date in row :name
     */
    public function inGridISeeTodayDate($tid, $name)
    {
        $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $name) {
            $js = <<<'JS'
var grid = firstCmp;
var store = grid.getStore();
return store.findRecord('name', '%name%').get('value');

JS;
            $js = str_replace(['%name%'], [$name], $js);

            $value = $q->runWhenComponentAvailable("propertygrid[tid=$tid]", $js);

            var_dump([$value, date('Y-m-d'), date('Y-m-d', strtotime($value))]);

            Assert::assertTrue(date('Y-m-d') == date('Y-m-d', strtotime($value)));

        });
    }

    /**
     * @Then /grid :tid contains:/
     */
    public function gridContains($tid, TableNode $table)
    {
        $hash = $table->getHash();
        foreach ($hash as $row) {
            // $row['name'], $row['value'], $row['phone']

            var_dump($row);

//            $this->runActiveActor(function(RemoteWebDriver $admin, $actor, $backend, ExtDeferredQueryHandler $q) use($tid, $expectedText, $value) {
//                $js = <<<'JS'
//var grid = firstCmp;
//var view = grid.getView();
//var store = grid.getStore();
//return store.findRecord('name', '%expectedText%', 0, true).get('value');
//
//JS;
//                $js = str_replace(['%expectedText%'], [$expectedText], $js);
//
//                $value = $q->runWhenComponentAvailable("propertygrid[tid=$tid]", $js);
//
//                Assert::assertTrue('Y-m-d', date(strtotime($value)) == date('Y-m-d', strtotime($value)));
//
//                //sleep(1);
//
//            });

        }
    }
}