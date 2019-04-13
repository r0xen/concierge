<?php
declare(strict_types = 1);

include __DIR__.'/basics.php';
use Concierge\Concierge;

$mc = new Concierge();

$ig = new \InstagramAPI\Instagram(false, false);
        try {
            $loginResponse = $ig->login("SECOND_ACCOUNT", "IG_PASSWORD");
            if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
                $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
                $verificationCode = trim(fgets(STDIN));
                $ig->finishTwoFactorLogin("SECOND_ACCOUNT", "IG_PASSWORD", $twoFactorIdentifier, $verificationCode);
            }
        } catch (\Exception $e) {
            echo 'Something went wrong: ' . $e->getMessage() . "\n";
            exit(0);
        }

$mc->addInstagram("ig2", $ig);

$mc->run();