<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>

<?php
require './vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

// // chrome利用用意
// $options = new ChromeOptions();
// // headlessモードで対応
// $options->addArguments(['--headless']);
// // ブラウザのサイズを指定
// $options->addArguments(["window-size=1024,2048"]);
$host = 'http://localhost:4444/wd/hub';
$capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
// $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = Facebook\WebDriver\Remote\RemoteWebDriver::create($host, $capabilities);
$driver->get('https://sciencelatvia.lv/#/pub/eksperti/list');

//
$driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::tagName('td')));
$tds = $driver->findElements(WebDriverBy::tagName('td')); //Elements
echo "name: " . $tds[28]->getText() . "<br>";
$tds[28]->click();

//
$driver->wait(20, 1000)->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('orcid')));
$orcid = $driver->findElement(WebDriverBy::name("orcid")); //Element
sleep(10);
echo $driver->executeScript("return angular.element(document.getElementsByName('orcid')).val()");

//
// $uibtabs = $driver->findElements(WebDriverBy::clsName('uib-tab')); //Elements
// echo "uib-tab class: " . count($uibtabs) . "\n";
// $uibtabs[1]->click();
// $uibtabs[2]->click();

// $driver->close();
