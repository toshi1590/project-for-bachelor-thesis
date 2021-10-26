<?php
require './vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\UnrecognizedExceptionException;
use Facebook\WebDriver\Exception\UnexpectedJavascriptException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Exception\WebDriverCurlException;


//
$host = 'http://localhost:4444/wd/hub';
$options = new ChromeOptions();
// $options->addArguments(['--headless']);
$capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = Facebook\WebDriver\Remote\RemoteWebDriver::create($host, $capabilities);


//
try {
  $driver->get($_POST['url']);
}
catch (WebDriverCurlException $e) {
  echo 'WebDriverCurlException occured.';
  $driver->close();
  exit();
}
catch (Exception $e) {
  echo 'exception occured.';
  $driver->close();
  exit();
}


// $driver->get($_POST['url']);
$driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::tagName('td')));
sleep(10); //for $titles, but not work all the time


// for json
$scraped_data = [];


//getting titles process ... can combine this process with process from 66th to 69th row????
$column_numbers_to_scrape = $_POST['column_numbers_to_scrape'];
$titles = [];

for ($i=0; $i < count($column_numbers_to_scrape); $i++) {
  $th = $driver->findElement(WebDriverBy::xpath("//tr/th[$column_numbers_to_scrape[$i]]")); //Element
  array_push($titles, $th->getText());
}

if (!empty($_POST['titles_for_elements_in_a_new_page'])) {
  $titles = array_merge($titles, $_POST['titles_for_elements_in_a_new_page']);
}

array_push($scraped_data, $titles);


// getting tds process
if (!empty($_POST['pages'])) {
  $pages = $_POST['pages'];
} else {
  $pages = 1;
}

for ($i=0; $i < $pages; $i++) {
  // Or get rows on each page
  for ($j=1; $j <= $_POST['rows']; $j++) {
    $scraped_row_data = [];

    for ($k=0; $k < count($column_numbers_to_scrape); $k++) {
      $td = $driver->findElement(WebDriverBy::xpath("//tr[$j]/td[$column_numbers_to_scrape[$k]]")); //Element
      array_push($scraped_row_data, $td->getText());
    }

    if (!empty($_POST['xpaths_of_elements_to_click_in_the_table'])) {
      $xpaths_of_elements_to_click_in_the_table = $_POST['xpaths_of_elements_to_click_in_the_table'];

      for ($k=0; $k < count($xpaths_of_elements_to_click_in_the_table); $k++) {
        try {
          // click .../tr[$j]/td[$k]
          $xpath_of_element_to_click_in_the_table = preg_replace('/tr\[[0-9]+\]/', "tr[$j]", $xpaths_of_elements_to_click_in_the_table[$k]); // change tr[x] to tr[$j]
          preg_match('/td\[[0-9]+\]/', $xpath_of_element_to_click_in_the_table, $td); // extract td[x]
          $xpath_of_element_to_click_in_the_table = strstr($xpath_of_element_to_click_in_the_table, 'td', true); // remove td[x]...
          $xpath_of_element_to_click_in_the_table = $xpath_of_element_to_click_in_the_table . $td[0]; // add extracted td[x]
          $xpath_to_click = $driver->findElement(WebDriverBy::xpath($xpath_of_element_to_click_in_the_table));
          $xpath_to_click->click();
        }
        catch (UnrecognizedExceptionException $e) {
          // click .../tr[$j]/td[$k]/...
          $xpath_of_element_to_click_in_the_table = preg_replace('/tr\[[0-9]+\]/', "tr[$j]", $xpaths_of_elements_to_click_in_the_table[$k]); // take digit into consideration
          $xpath_to_click = $driver->findElement(WebDriverBy::xpath($xpath_of_element_to_click_in_the_table));
          $driver->executeScript('arguments[0].click()', [$xpath_to_click]);
        }
        catch (Exception $e) {
          break 1; // To add 'text was not extracted.' into $scraped_row_data, not use break???
        }

        sleep(10);

        //getting data in new pages process
        $xpaths_to_scrape_in_new_pages = $_POST['xpaths_to_scrape_in_new_pages'];
        $keys = array_keys($xpaths_to_scrape_in_new_pages);
        $xpaths_to_scrape_in_a_new_page = $xpaths_to_scrape_in_new_pages[$keys[$k]];

        // angular text comes first, then simple text comes second (In case of vice versa, angular text is extracted as empty)
        for ($l=0; $l < count($xpaths_to_scrape_in_a_new_page); $l++) {
          try {
            // for angular text
            $angular_text = $driver->executeScript("



              var script = document.createElement('script');
              script.src = 'https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js';



              var xpaths_to_scrape_in_a_new_page = '$xpaths_to_scrape_in_a_new_page[$l]';
              var elem = document.evaluate(xpaths_to_scrape_in_a_new_page, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
              return angular.element(elem.singleNodeValue).val();



              // script.remove()



            ");

            array_push($scraped_row_data, $angular_text);
          }
          catch (UnexpectedJavascriptException $e) {
            // for simple text
            $xpath_to_scrape = $driver->findElement(WebDriverBy::xpath($xpaths_to_scrape_in_a_new_page[$l]));
            array_push($scraped_row_data, $xpath_to_scrape->getText());
          }
          catch (Exception $e) {
            array_push($scraped_row_data, '');
          }
        }

        $driver->navigate()->back();
        $driver->wait()->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::tagName('td')));
        sleep(10);
      }
    }

    array_push($scraped_data, $scraped_row_data);
  }

  if ($pages >= 2) {
    // Or loop until text_of_next_btn disappears...then catch NoSuchElementException?????
    try {
      $text_of_next_btn = $driver->findElement(WebDriverBy::linkText($_POST['text_of_next_btn']));
      $text_of_next_btn->click();
      sleep(10);
    } catch (Exception $e) {
      // 1
      // $msg = [];
      // $next_page = $i + 2;
      // array_push($msg, 'could not go to ' . $next_page . ' page.');
      // array_push($scraped_data, $msg);
      // break;

      // 2
      // echo 'could not go to next page.';
      // exit();

      // 3
      break;
    }
  }
}

echo json_encode($scraped_data);


//
$driver->close();
