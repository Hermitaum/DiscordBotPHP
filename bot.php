<?php
include "DiscordGateway.php";
include "Discord.php";

use Discord\Gateway;
use Discord\DiscordBOT;

$discord = new DiscordBOT([
	"token" => "BOT_TOKEN",
	"bot" => true
]);

//Discord Bot written in PHP
//Created by Hermit

$discord->start(function() use ($discord){
	echo "BOT INICIADO!", PHP_EOL;

	$discord->on('message', function($message){
		echo "{$message->author->username}: {$message->content}", PHP_EOL;

		switch ($message->content) {
			case "!privado":
				$message->channel->replyMessage("Ola, aqui estou eu no privado!");
			break;
			case "!grupo":
				$message->channel->sendMessage("Ola, aqui estou eu no grupo!");
			break;
			case "!embed":
				$message->channel->sendMessage("", ["title" => "Ola!", "description" => "Aqui estou eu na forma embed!"]);
			break;
		}

	});

	$discord->on('messageDelete', function($message){
		echo "UM USUARIO APAGOU A MENSAGEM", PHP_EOL;
	});

});
?>
