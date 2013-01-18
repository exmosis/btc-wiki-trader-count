<?php

// Number of edits to check through
define('BACKTRACE', 200);

$baseUrl = 'https://en.bitcoin.it';
$historyUrl = 'https://en.bitcoin.it/w/index.php?title=Trade&offset=&limit=' . BACKTRACE . '&action=history';

$jsonFile = 'stats.json';
$csvFile = 'stats.csv';
$highchartsJsonFile = 'stats.hc.js';

// load previous results
$stats = array();
$total = array();
$monthsCovered = array();
if (file_exists($jsonFile)) {
  $previous = json_decode(file_get_contents($jsonFile), true);
  if (array_key_exists('total', $previous)) {
    $total = $previous['total'];
    $monthsCovered = array_keys($total);
  }
  if (array_key_exists('breakdown', $previous)) {
    $stats = $previous['breakdown'];
  }
}

// get history

$historyPage = file_get_contents($historyUrl);
$latestInMonth = array();

$monthLookup = array (
  'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'
);

if (! $historyPage) {
  echo 'Page not found.' . "\n";
  exit;
}

// echo $historyPage;

// find all links to previous versions in this format:
// 
// <a href="/w/index.php?title=Trade&amp;oldid=28351" title="Trade">09:41, 30 June 2012</a>‎

// preg_match('/((<a href="\/w\/index\.php\?title=[^"]+&amp;oldid=\d+" title="[^"]+">\d+:\d+, \d\d? [a-zA-Z]+ \d{4}<\/a>).*)*/', $historyPage, $historyLinks)) {

// add a token after the links we want, to make exploding easier
$historyPage = preg_replace(
                  '/(<a href="\/w\/index\.php\?title=[^"]+&amp;oldid=\d+" title="[^"]+">\d+:\d+, \d\d? [a-zA-Z]+ \d{4}<\/a>)/', 
                  '$1|||',
                  $historyPage
);

$contents = explode('|||', $historyPage);

$historyLinks = array();

foreach ($contents as $c) {

  if (preg_match('/(<a href="\/w\/index\.php\?title=[^"]+&amp;oldid=\d+" title="[^"]+">\d+:\d+, \d\d? [a-zA-Z]+ \d{4}<\/a>)/', $c, $match)) {
    $historyLinks[] = $match[1];
  }

}

foreach ($historyLinks as $hl) {
  $monthKey = getMonthKey($hl);
  $monthUrl = getUrl($hl);
  if ($monthKey && $monthUrl && ! array_key_exists($monthKey, $latestInMonth)) {
    $latestInMonth[$monthKey] = array ( 'url' => $monthUrl, 'timestamp' => getTimestamp($hl) );
  }
}

// print_r($latestInMonth);

// Start gettig actual content now

foreach ($latestInMonth as $monthKey => $monthInfo) {

  $currentH2 = '';
  $currentH3 = '';
  $currentH4 = '';

  $monthTotal = 0;

  $total = resetAllMonthKeyStats($total, $monthKey);
  $stats = resetAllMonthKeyStats($stats, $monthKey);

  $thisMonth = file_get_contents($baseUrl . str_replace('&amp;', '&', $monthInfo['url']));

  if ($thisMonth) {
    $thisMonthContents = explode("\n", $thisMonth);
    foreach ($thisMonthContents as $tmc) {

      if (preg_match('/<div id="mw-revision-info">(Revision as of \d\d:\d\d, \d\d? [a-zA-Z]+ \d{4})/', $tmc, $matches)) {
      }

      if (preg_match('/<h2> <span class="mw-headline" id="[^"]+">([^<]+)<\/span><\/h2>/', $tmc, $matches)) {
        $currentH2 = $matches[1];
        $currentH3 = '';
        $currentH4 = '';
      }
      if (preg_match('/<h3> <span class="mw-headline" id="[^"]+">([^<]+)<\/span><\/h3>/', $tmc, $matches)) {
        $currentH3 = $matches[1];
        $currentH4 = '';
      }
      if (preg_match('/<h4> <span class="mw-headline" id="[^"]+">([^<]+)<\/span><\/h4>/', $tmc, $matches)) {
        $currentH4 = $matches[1];
      }


      if (preg_match('/<a rel="nofollow" class="external text"/', $tmc) && $currentH2) {
        // could extract details here, but why?
        if (! array_key_exists($currentH2, $stats)) {
          $stats[$currentH2] = array( 'total' => array() );
        }
        if (! array_key_exists($currentH3, $stats[$currentH2])) {
          $stats[$currentH2][$currentH3] = array();
        }
        if (! array_key_exists($currentH4, $stats[$currentH2][$currentH3])) {
          $stats[$currentH2][$currentH3][$currentH4] = array();
        }
        if (! array_key_exists($monthKey, $stats[$currentH2][$currentH3][$currentH4])) {
          $stats[$currentH2][$currentH3][$currentH4][$monthKey] = 0;
        }

        if (! array_key_exists($monthKey, $stats[$currentH2]['total'])) {
          $stats[$currentH2]['total'][$monthKey] = 0;
        }
        if (! array_key_exists($monthKey, $total)) {
          $total[$monthKey] = 0;
        }

        // store current month
        $stats[$currentH2][$currentH3][$currentH4][$monthKey]++;
        $stats[$currentH2]['total'][$monthKey]++;
        $total[$monthKey]++;

        // Finally add monthKey to monthsCovered for reference
        if (! in_array($monthKey, $monthsCovered)) {
          $monthsCovered[] = $monthKey;
        }

      }
    }
  }

}

// Sort out the months array
sort($monthsCovered);

$allStats = array(
                  'credits' => array ( 
                                  'source url' => 'https://en.bitcoin.it/wiki/Trade',
                                  'author' => 'Scribe', 
                                  'email' => 'exmosis@gmail.com',
                                  'twitter' => '@exmosis',
                                  'tips' => '1BNrFAAxV44As9wKshs3cdchpus8HqeC4v' 
                  ),
                  'total' => $total,
                  'breakdown' => $stats
            );

// output json
$fh = fopen($jsonFile, 'w');
if ($fh) {
  fwrite($fh, json_encode($allStats));
}
fclose($fh);

// output CSV
// build up array:
$csvOutput = array();

// 0. output credits
foreach ($allStats['credits'] as $cKey => $cVal) {
  $csvOutput[] = array($cKey, $cVal);
}
$csvOutput[] = array( '' );

// 1. headers
$csvHeaders = array( 'Category Level 1', 'Level 2', 'Level 3' );
foreach ($monthsCovered as $mc) {
  $csvHeaders[] = $mc;
}

$csvOutput[] = $csvHeaders;

// 2. Output Grand Total
$csvLine = array( 'GRAND TOTAL', '', '' );
foreach ($monthsCovered as $mc) {
  if (array_key_exists($mc, $total)) {
    $csvLine[] = $total[$mc];
  } else {
    $csvLine[] = '';
  }
}

$csvOutput[] = $csvLine;

// 3. output Level 1 totals - also to highchartsJson
$csvOutput[] = array( '' );
$csvOutput[] = array( 'Category Totals' );

$highChartsJsonData = array();

foreach ($stats as $mainCat => $catInfo) {
  $csvLine = array( $mainCat, '', '' );
  $jsonDataObject = array('name' => $mainCat, 'data' => array());
  foreach ($monthsCovered as $mc) {
    if (array_key_exists($mc, @$catInfo['total'])) {
      $csvLine[] = $catInfo['total'][$mc];
      $jsonDataObject['data'][] = $catInfo['total'][$mc];
    } else {
      $csvLine[] = '';
      $jsonDataObject['data'][] = '0';
    }
  }
  $csvOutput[] = $csvLine;
  $highChartsJsonData[] = $jsonDataObject;
}

// 4. output subcategory counts
$csvOutput[] = array( '' );
$csvOutput[] = array( 'Breakdowns' );

foreach ($stats as $cat1 => $cat1Info) {
  foreach ($cat1Info as $cat2 => $cat2Info) {
    if ($cat2 != 'total') {
      foreach ($cat2Info as $cat3 => $cat3Info) {
        $csvLine = array( $cat1, $cat2, $cat3 );
        foreach ($monthsCovered as $mc) {
          if (array_key_exists($mc, $cat3Info)) {
            $csvLine[] = $cat3Info[$mc];
          }
        }
        $csvOutput[] = $csvLine;
      }
    }
  }
}
      
$fh = fopen($csvFile, 'w');
if ($fh) {
  foreach ($csvOutput as $l) {
    fputcsv($fh, $l, ',');
  }
}
fclose($fh);

// 5. output hicharts json data
$fh = fopen($highchartsJsonFile, 'w');
if ($fh) {
  fwrite($fh, 'var wikiMonthsCovered = ' . json_encode($monthsCovered) . ';' . "\n");
  fwrite($fh, 'var wikiHistoryStats = ' . json_encode($highChartsJsonData) . ';' . "\n");
}
fclose($fh);

exit;


function resetAllMonthKeyStats($stats, $monthKey) {

  if (! $monthKey) {
    return $stats;
  }

  if (! is_array($stats)) {
    return $stats;
  }

  foreach (array_keys($stats) as $s) {
    if ($s == $monthKey) {
      $stats[$s] = 0;
    } else {
      // recurse to next level
      $stats[$s] = resetAllMonthKeyStats($stats[$s], $monthKey);
    }
  }
  return $stats;
}

function getUrl($text) {
  if (preg_match('/<a href="([^"]+)"/', $text, $matches)) {
    return $matches[1];
  }
  return '';
}

function getTimestamp($text) {
  if (preg_match('/>(\d+:\d+), (\d\d?) ([a-zA-Z]+) (\d{4})</', $text, $dateMatches)) {
    return $dateMatches[4] . '-' . $dateMatches[3] . '-' . $dateMatches[2] . ' ' . $dateMatches[1];
  }
  return '';
}

function getMonthKey($text) {

  global $monthLookup;

  if (preg_match('/>\d+:\d+, \d\d? ([a-zA-Z]+) (\d{4})</', $text, $dateMatches)) {
    // convert month name to number
    $i = 1;
    foreach ($monthLookup as $ml) {
      if ($dateMatches[1] == $ml) { $dateMatches[1] = str_pad($i, 2, "0", STR_PAD_LEFT); }
      $i++;
    }
    return $dateMatches[2] . '-' . $dateMatches[1];
  }
  return '';
}

?>
