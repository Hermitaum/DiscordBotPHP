<?php
namespace Discord;

class DiscordBOT extends Gateway {
    const DISCORD_API_URL = "https://discordapp.com/api";

    public static $token;
    public static $guildChannel;
    public static $authorChannel;
    
    public function __construct(array $config){
        ini_set("error_reporting", 0);
        self::$token = $config["bot"] ? "Bot ".$config["token"] : $config["token"];
        Gateway::init($config["token"], $this);
    }

    public function sendMessage(string $content, array $embed = []){
        $requestOptions = [
            "path" => "channels/" . self::$guildChannel . "/messages",
            "headers" => [
                "Content-Type: application/json"
            ],
            "method" => "POST",
            "body" => json_encode(array("content" => $content, "tts" => false, "embed" => $embed))
        ];

        self::makeRequest($requestOptions, $response);

        return $response;
    }

    public function replyMessage(string $content, array $embed = []){
        $requestOptions = [
            "path" => "/users/@me/channels",
            "headers" => [
                "Content-Type: application/json"
            ],
            "method" => "POST",
            "body" => json_encode(["recipient_id" => self::$authorChannel])
        ];

        self::makeRequest($requestOptions, $response);

        $requestOptions = [
            "path" => "channels/" . $response->id . "/messages",
            "headers" => [
                "Content-Type: application/json"
            ],
            "method" => "POST",
            "body" => json_encode(array("content" => $content, "tts" => false, "embed" => $embed))
        ];

        self::makeRequest($requestOptions, $response);

        return $response;
    }

    public function makeRequest($options, &$response) {
        $cUrl = curl_init(self::DISCORD_API_URL."/".$options["path"]);
        curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cUrl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($cUrl, CURLOPT_HTTPHEADER, array_merge($options['headers'], array("Authorization: ".self::$token)));
        if (isset($options['method'])){
            curl_setopt($cUrl, CURLOPT_CUSTOMREQUEST, strtoupper($options['method']));

            if (isset($options['body']))
                curl_setopt($cUrl, CURLOPT_POSTFIELDS, $options['body']);
        }

        $response = json_decode(curl_exec($cUrl));
    }
}
?>
