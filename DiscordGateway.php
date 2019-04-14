<?php
namespace Discord;

class Gateway {

	const GATEWAY_URL = "gateway.discord.gg";
	const GATEWAY_TRANSPORT = "ssl";
	const GATEWAY_QUERY = array("encoding" => "json", "v" => "6");
	const GATEWAY_PORT = 443;

	public $socket;
	public $discordCodes = array("message" => "MESSAGE_CREATE", "newMember" => "GUILD_MEMBER_ADD", "messageDelete" => "MESSAGE_DELETE");
	public $handles;
	public $session = 0;
	public $timeSession = 0;
	public $timeout = 5;
	public $discordBot;
	public $botClass;

	public function init($token, $bot){
		$this->botClass = $bot;
		$this->timeSession = strtotime("+30 seconds");
		$this->discordBot = $discordBot;
		$this->socket = fsockopen(self::GATEWAY_TRANSPORT."://".self::GATEWAY_URL, self::GATEWAY_PORT, $errNo, $errStr, $this->timeout);

		stream_set_timeout($this->socket, $this->timeout);

		self::handshake(base64_encode(bin2hex(openssl_random_pseudo_bytes(8))));

		self::discordSend('{"op":2,"d":{"token":"'.$token.'","properties":{},"presence":{}}}');
	}

	public function discordSend($payload){
		$frameHead = strlen($payload) > 125 ?
		array_merge(["10000001"], str_split("1".decbin(126).sprintf('%016b', strlen($payload)), 8))
		: 
		array_merge(["10000001"], str_split("1".sprintf('%07b', strlen($payload)), 8));

		$mask = "";

		foreach ($frameHead as $binstr) 
			$frame[] = chr(bindec($binstr));
		
		for ($i = 0; $i < 4; $i++) 
			$mask .= chr(mt_rand(0, 255));

		$frame[] = $mask;

		for ($i = 0; $i < strlen($payload); $i++)
			$frame[] = $payload[$i] ^ $mask[$i % 4];

		$this->socketWrite(implode("", $frame));
	}

	private function handshake($key){
		$host = self::GATEWAY_URL.":".self::GATEWAY_PORT;
		$query = http_build_query(self::GATEWAY_QUERY);
		$key = base64_encode(bin2hex(openssl_random_pseudo_bytes(8)));

		$header = "GET /?{$query} HTTP/1.1\r\nhost: {$host}\r\nuser-agent: DiscordGateway\r\nconnection: Upgrade\r\nupgrade: websocket\r\nsec-websocket-key: {$key}\r\nsec-websocket-version: 13\r\n\r\n";

		self::socketWrite($header);

		do {
			stream_get_line($this->socket, 1024, "\r\n");
			$socketMeta = stream_get_meta_data($this->socket);
		} while (!feof($this->socket) && $socketMeta['unread_bytes'] > 0);

	}

	public function start($continue){
		$continue();
		do {
			$payload = (int) ord($this->read(2)[1]) & 127;
			if ($payload > 125)
				$payload = bindec($this->sprintB($payload === 126 ? $this->read(2) : $this->read(8)));

			$buffer = json_decode($this->read($payload));

			if (is_object($buffer)) {
				$codes = array_flip($this->discordCodes);
				if (@is_callable($this->handles[$codes[$buffer->t]])) {

					$message = $buffer->d;

					DiscordBOT::$guildChannel = $message->channel_id;
					DiscordBOT::$authorChannel = $message->author->id;
					$message->channel = $this->botClass;

					$message->author->mention = "<@{$message->author->id}>";
					$message->author->avatarUrl = "https://cdn.discordapp.com/avatars/{$message->author->id}/{$message->author->avatar}?size=128";
					$message->isDM = boolval(!is_object($message->member));

					call_user_func($this->handles[$codes[$buffer->t]], $message);
				}
			}

			if (strtotime("now") > $this->timeSession) {
				$this->timeSession = strtotime("+30 seconds");
				self::discordSend(json_encode(["op" => 1, "d" => $this->session++]));
			}

		}while(true);
	}

	public function on($type, callable $handle){
		if(in_array($type, array_keys($this->discordCodes)))
			$this->handles[$type] = $handle;
	}

	private function socketWrite($data){
		fwrite($this->socket, $data);
	}

	private function read($length) {
		$data = "";
		while (strlen($data) < $length) {
			$buffer = fread($this->socket, $length - strlen($data));
			$data .= $buffer;
		}
		return $data;
	}

	private function sprintB($string) {

		for ($i = 0; $i < strlen($string); $i++){
			$return .= sprintf("%08b", ord($string[$i]));
		}
		return $return;
	}
}
?>
