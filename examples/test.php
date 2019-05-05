<?php declare (strict_types = 1);

include __DIR__ . '/../vendor/autoload.php';

use Concierge\Concierge;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

define('BOT_TOKEN', 'BOT_TOKEN');

define('A_USER_CHAT_ID', 'BOT_OWNER_CHAT_ID');

define('IG_USERNAME', 'FIRST_ACCOUNT_USERNAME');

define('IG_PASSWORD', 'FIRST_ACCOUNT_PASSWORD');


$logger = new Logger('concierge');
$logger->pushHandler(new StreamHandler('php://stdout'));


$ig = new \InstagramAPI\Instagram(false, false);
try {
    $loginResponse = $ig->login(IG_USERNAME, IG_PASSWORD);
    if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
        $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
        echo "Insert PIN: ";
        $verificationCode = trim(fgets(STDIN));
        $ig->finishTwoFactorLogin(IG_USERNAME, IG_PASSWORD, $twoFactorIdentifier, $verificationCode);
    }
} catch (\Exception $e) {
    echo 'Something went wrong: ' . $e->getMessage() . "\n";
    exit(0);
}

$mc = new Concierge(IG_USERNAME, $ig, $logger);

/*
//multi account
$ig2 = new \InstagramAPI\Instagram(false, false);
        try {
            $loginResponse = $ig->login("username", "password");
            if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
                $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
                $verificationCode = trim(fgets(STDIN));
                $ig->finishTwoFactorLogin("username", "password", $twoFactorIdentifier, $verificationCode);
            }
        } catch (\Exception $e) {
            echo 'Something went wrong: ' . $e->getMessage() . "\n";
            exit(0);
        }


$mc->addInstagram("second_account", $ig2);
*/
$mc->run();
