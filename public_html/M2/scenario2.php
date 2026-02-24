<?php
// copilot: disable
// @ts-nocheck
require_once "base.php";

$ucid = "mt85"; // <-- set your ucid

// Don't edit the arrays below, they are used to test your code
$array1 = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6];
$array2 = [1.0000001, 1.0000002, 1.0000003, 1.0000004, 1.0000005];
$array3 = [1.0 / 3.0, 2.0 / 3.0, 4.0 / 3.0, 8.0 / 3.0, 8.0 / 3.0];
$array4 = [1e16, 1.0, -1e16, 2.0, -2.0, 1e-16];
$array5 = [M_PI, M_E, sqrt(2), sqrt(3), sqrt(5), log(2), log10(3)];


function sumValues($arr, $arrayNumber)
{
    echo "<div class='problem-item'>";
    // Only make edits between the designated "Start" and "End" comments
    printScenario2ArrayInfo($arr, $arrayNumber);
    // This should be solved without Copilot auto-completion, to toggle it, click the Copilot chat bubble at the top of the editor.
    //  Configure inline suggestions to "Disabled Inline Suggestions" (or similar) when writing code for this problem.
    
    // Challenge 1: Sum all the values of the passed in array and assign to the `total` variable
    // Challenge 2: Have the sum (total) be represented as a number with exactly 2 decimal places (similar to currency), assign to `modifiedTotal` variable
    // Example: 0.1 would be shown as 0.10, 1 would be shown as 1.00, 0.011 as 0.01, etc
    // Step 1: sketch out plan using comments (include ucid and date)
    // Step 2: Add/commit your outline of comments (required for full credit)
    // Step 3: Add code to solve the problem (add/commit as needed)

    $total = 0;
    // Start Solution Edits
    // Solve Challenge 1 here: Sum all values


    // Solve Challenge 2 here: Format to 2 decimal places
    $modifiedTotal = "?";

    // End Solution Edits
    printScenario2Output($total, $modifiedTotal);
    echo "</div>";
}

// Run the problem
printHeader($ucid, 2);
echo "<div class='scenario2-grid'>";
sumValues($array1, 1);
sumValues($array2, 2);
sumValues($array3, 3);
sumValues($array4, 4);
sumValues($array5, 5);
echo "</div>";
printFooter($ucid, 2);