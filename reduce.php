<?php
$classes = file("C:\Users\alan\Documents\Span\classes.txt");
foreach ($classes as $classLine) {
  $className = trim($classLine);
  if ($className) {
    $classLookup[$className] = 1;
  }
}
$css = file("C:\Users\alan\Documents\Span\stylesheet.css");

$currentContext = "";
$contextOutput = false;
$inStyle = false;
$outputtingStyle = false;

foreach ($css as $cssLine) {
  switch (substr(trim($cssLine), 0, 1)) {
    case false: // empty
        break;
    case "@":
      if ($currentContext) {
        echo "/* *** NESTED CONTEXT */";
      }
      $currentContext .= $cssLine;
      $contextOutput = false;
      break;
    case "}":
      if ($inStyle) {
        if ($outputtingStyle) {
          echo $cssLine;
          $outputtingStyle = false;
        }
        $inStyle = false;
      } elseif ($currentContext) {
        if ($outputtingContext) {
          echo $cssLine;
          $outputtingContext = false;
        }
        $currentContext = "";
      } else {
        echo "/* *** DANGLING BRACE */";
      }
      break;
    default:
      if ($inStyle) {
          if ($outputtingStyle) {
              echo $cssLine;
          }
      } else {
        if (substr(trim($cssLine),-1,1) == "{") {
            $inStyle = true;
            foreach(explode(",", $cssLine) as $term) {
                $matches = [];
                if(preg_match_all("/[.]([-A-Za-z0-9_]+)/",$term, $matches)) {
                    foreach($matches[1] as $match) {
                        if ($classLookup[$match]) {
                            $outputtingStyle = true;
                            break;
                        }
                    }
                } else {
                    $outputtingStyle = true;
                }
            }
            if ($outputtingStyle) {
                if ($currentContext) {
                    if (!$outputtingContext) {
                        echo $currentContext;
                        $outputtingContext = true;
                    }
                }
                echo $cssLine;
            }
        } else {
            echo "/* *** NO START BRACE */";
        }
      }
  }
}

?>
