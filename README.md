## General Notes

This is an application that generates schedule for a cleaning company in the form of a CSV file. I've tried to abide to the general rules 
given in the task. So, we have 3 cleaning activities: Vacuuming, Window cleaning and Refrigerator cleaning.
Each of those activities takes certain amount of time: 21 min, 35 min, 50 min in the given order. Vacuuming is done 
on Tuesdays and Thursdays. While Window cleaning and Refrigerator cleaning is done on the last working day and the first cleaning day of the month.
On those dates I'm doing inserts for the respected activities in the Activity column.
I have three columns in the CSV file: Date, Activity and TotalTime. When I don't have a specific value for a certain date
I replace the value with a forward slash ('/'). I'm calculating the total time in the HH::MM format, 
so that value is inserted in the TotalTime column in the last row of the csv file or for the last day of the 3 month period.
The 3 month period starts from the day or the date given to the command and goes 3 months into the future. If we don't provide
the startDate (it's not a required arg) or you provide an invalid startDate, the first day or the first date will default to the current day or to today. 
I've also enabled the user to provide an array of holidays to the command, so for those days we won't do any activities 
even though the days are not Saturday or Sunday. If you provide invalid holiday dates the command will fail.

## Technical Specifications

For this project I've used the Symfony 7 framework, especially the Console Component. I was using my personal
environment - Ubuntu 22.04 on which I have installed PHP 8.2 and which is a requirement for Symfony 7. But I've assumed that
the person that will review my code won't have this version of PHP or maybe won't have PHP installed at all on his/hers machine.
So I've dockerized the project as well, and you will have to have Docker and docker-compose installed on your machine in order
to run the application in an environment with the PHP 8.2 version installed. The Dockerfile and the docker-compose.yml file
are located into the root folder.
So, the important commands that you will need to execute are:

``docker compose build``

``docker compose up -d``

Now the image and the container should be built on your local machine, and the container should be up and running.

In order to get into the container and execute the command from inside, run this command: 

``docker compose exec app bash``

The next step would be to run 

``composer install``

in order to have all the dependencies that the application needs to run properly.

Then you would have to create .env.local file and paste this content in there

``
APP_ENV=dev
APP_SECRET=your key
FILE_PATH=src/files/
``

also don't forget to change the value of APP_ENV from 'dev' to 'prod' in the .env file

To view the command details run

``bin/console list``

you will notice the command signature under the generate group. So if you want to run the command just execute this within the container:

``bin/console generate:schedule``

and after the execution hopefully you will see this line of text

``Your schedule for the next three months has been created.``

If you want to provide some arguments, which by the way are not required, you can execute this command

``bin/console generate:schedule 2024-05-01 2024-05-31``

So the first argument is the startDate, and it should be passed in the format 'Y-m-d'. The second argument is holidays, it
is an argument of type array, so we can pass multiple dates. If we don't pass any dates, we are going to send an empty array
as an argument. The dates within the array should be in the format 'Y-m-d' as well.

and you should have .csv file within the src/files directory.

So for the code I have one command ``GenerateCleaningScheduleCommand.php`` and one service class ``CSVData.php`` which I inject in the
constructor of the command. The service class is responsible for the creation of the rows which I insert into the csv file
from the command. I think I wrote enough comments in the service class that are explaining the logic there.

I also wrote some tests with the help of the PHPUnit library. I have integration tests (tests/integration) with which I'm testing the command,
and I have unit tests (tests/unit) with which I'm testing the CSVData.php class. I think I have 33 test cases in total.
Before you try to run the tests please paste this content into the .env.test file

``
FILE_PATH=tests/files/
APP_ENV=test
``

then you can run the command

`` php bin/phpunit``

and If everything goes well you should have a new .csv file within tests/files.

I have also installed symfony's php-cs-fixer package but in a different directory. First in your terminal run this

``
mkdir -p tools/php-cs-fixer
composer require --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer
``
I don't have a separate config file for the cs fixer so basically it defaults to the @psr-12 standard
You can run code style inspection by executing this command

``tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src``

or if you want to fix the tests

``tools/php-cs-fixer/vendor/bin/php-cs-fixer fix tests``

I've installed PHPStan as well in order to do a static analysis of the code. There is a `phpstan.dist.neon` file where
the configuration is stored. I'm running it on level 9. In order to try it, execute this command within the terminal

``vendor/bin/phpstan analyse``