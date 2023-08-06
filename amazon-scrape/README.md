 #To manual run use the following in sequence
<li> php artisan dusk  tests/Browser/ExampleTest.php --filter testBasicExample     (to fetch product url in txt file)</li>
<li> php artisan dusk  tests/Browser/ExampleTest.php --filter testFetchUrlFromTxt (to fetch product data)</li>

<h3>Important points:</h3>
<li> Database should be same as wordpress </li>

<h3> Crons</h3>
<li> 0 1 * * * php /home/dev/project/amazon-daily-deal-scrapper/amazon-scrape/artisan dusk --filter tests/Browser/ExampleTest.php::testBasicExample </li>
<li> 35 11  * * *  php /home/dev/project/amazon-daily-deal-scrapper/amazon-scrape/artisan dusk tests/Browser/ExampleTest.php --filter testFetchUrlFromTxt </li>(run at 11:35 am)
