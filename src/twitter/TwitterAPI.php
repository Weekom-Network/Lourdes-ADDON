<?php

declare(strict_types=1);

namespace twitter;

require_once('TwitterOAuth.php');

/**
 * Class TwitterAPI
 * @package twitter
 */
class TwitterAPI
{

    /** @var string */
    private static $key;
    /** @var string */
    private static $secret;
    /** @var string */
    private static $token;
    /** @var string */
    private static $tokenSecret;
    private static $twitter;
    private static $host = 'https://api.twitter.com/1.1/statuses/update.json?status=hello%20world';


    private static function getOAuth()
    {
        self::$key = 'xKmcZhBUF231eH2d4fazcOxE4';
        self::$secret = 'Ghv32P9YvmjrnNM6GxAVeJgA0tFKrOh8Du2ljwxzKdOGiXXnta';
        self::$token = '1297272887225851905-WT1yvTjdjfm72AGj7MTcQUle2sOxF7';
        self::$tokenSecret = 'k8cbvID0ng6PBJP2x8Ela2fK6tkSCxwe9Z8vALViB5Xdr';

        self::$twitter = new \TwitterOAuth(self::$key, self::$secret, self::$token, self::$tokenSecret);
        self::$twitter->host = self::$host;
    }

    /**
     * @param $tweet
     */
    public static function postTweet($tweet)
    {
        self::getOAuth();
        self::$twitter->post('statuses/update', ['status' => $tweet]);
    }

    /**
     * @param $player
     * @param $user
     * @param $message
     */
    public static function sendMessageDirect($player, $user, $message)
    {
        self::getOAuth();

        if(strlen($message) > 10000){
            $player->sendMessage('§o§cDM is larger than 10,000 characters, failed to send.');
        }
        else {
            self::$twitter->post('direct_messages/new', ['screen_name' => $user, 'text' => $message]);
            $player->sendMessage('§o§aDM sent!');
        }
    }
}