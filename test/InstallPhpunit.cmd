call pear uninstall phpunit/DbUnit
call pear uninstall phpunit/PHPUnit
call pear uninstall phpunit/PHP_CodeCoverage
call pear uninstall phpunit/File_Iterator
call pear uninstall phpunit/PHPUnit_MockObject
call pear uninstall phpunit/Text_Template
call pear uninstall phpunit/PHP_Timer
call pear uninstall phpunit/PHPUnit_Selenium
call pear uninstall pear.symfony-project.com/YAML
call pear uninstall pear.symfony.com/YAML

call pear clear-cache

call pear install pear.symfony.com/YAML-2.1.0
call pear install phpunit/Text_Template-1.1.1
call pear install phpunit/PHPUnit_Selenium-1.0.1
call pear install phpunit/PHPUnit_MockObject-1.2.0
call pear install phpunit/PHP_Timer-1.0.4
call pear install phpunit/File_Iterator-1.3.1
call pear install phpunit/PHP_CodeCoverage-1.2.1
call pear install --alldeps phpunit/PHPUnit-3.7.35
call pear install phpunit/DbUnit-1.3.1
