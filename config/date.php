<?php
print "The current time is " . date('h:i:s') . "<br />\n";
print "The formatted hour is " . strftime('%H') . "<br /><hr>\n";
$locale = setlocale(LC_ALL, '0');
$x = setlocale(LC_TIME, $locale);
print "Locale is $locale.<br />\n";
print "The current time is " . date('h:i:s') . "<br />\n";
print "The formatted hour is " . strftime('%H') . "<br />\n";
?>