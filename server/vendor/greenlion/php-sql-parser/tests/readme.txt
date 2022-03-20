After installing the Composer dependencies you can execute

$PROJECT_ROOT/vendor/bin/phpunit --bootstrap $PROJECT_ROOT/tests/bootstrap.php <test case file name or dir name>

In Eclipse you have to set the bootstrap class within the PHPUnit preferences and you should
install a newer PHPUnit using PEAR and add this PEAR install directory as PEAR Library for 
PHPUnit (also within the Preferences of PHPUnit).