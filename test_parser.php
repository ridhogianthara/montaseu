<?php
function parseCoordinate($coord, $isLng = false) {
    $coord = str_replace(',', '.', (string)$coord);
    if (strpos($coord, '.') !== false) {
        return (float)$coord;
    }
    
    $coord = trim($coord);
    if (empty($coord)) return 0.0;
    
    $isNegative = ($coord[0] === '-');
    $numStr = ltrim($coord, '-');
    
    if ($isLng) {
        if (strlen($numStr) >= 4) {
            // Indonesia longitude is 95 to 141. So it's 2 or 3 digits.
            // If it starts with 9, it's 9x (2 digits).
            if ($numStr[0] === '9') {
                $numStr = substr($numStr, 0, 2) . '.' . substr($numStr, 2);
            } else {
                $numStr = substr($numStr, 0, 3) . '.' . substr($numStr, 3);
            }
        }
    } else {
        if (strlen($numStr) >= 2) {
            $firstTwo = (int)substr($numStr, 0, 2);
            if ($firstTwo >= 10 && $firstTwo <= 11) {
                $numStr = substr($numStr, 0, 2) . '.' . substr($numStr, 2);
            } else {
                $numStr = substr($numStr, 0, 1) . '.' . substr($numStr, 1);
            }
        }
    }
    
    $val = (float)$numStr;
    return $isNegative ? -$val : $val;
}

echo parseCoordinate("-6936039") . "\n";
echo parseCoordinate("107627398", true) . "\n";
echo parseCoordinate("-1023456") . "\n";
echo parseCoordinate("98123456", true) . "\n";
echo parseCoordinate("-6.936039") . "\n";
echo parseCoordinate("107.627398", true) . "\n";
