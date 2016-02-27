<?php
require('config.php');
require('functions.php');
# if is /start will require totp.php

if (!isset($_GET['Token'])) {
	exit;
} elseif (sha1($_GET['Token'] == YSITD_Token)) {   # YSITD Send Message
	$chatID = $_GET['user_id'];
	$options = Array(
		'text' => $_GET['text'],
	);
	$tmp = sendMsg($options);
	var_dump($tmp['result']['chat']);
	exit;
} elseif(sha1($_GET['Token']) != Telegram_Token) {   # Is from Telegram or not.
	exit;
}

$json = file_get_contents('php://input') . PHP_EOL;
$datas = json_decode($json, True);
$data = $datas['message'];
$userID = $data['from']['id'];
$chatID = $data['chat']['id'];

sendAction();

if ($chatID == YSITD_ChatID) {
	if (isset($datas['new_chat_participant'])) {
		sendInGroup($datas['new_chat_participant']['id'], True);
	}
	exit;
}

/* Start of Check User */
if (substr($chatID, 0, 1) == '-' && !in_array($chatID, $allow_chans)) {
	sendMsg(Array(
		'chat_id' => $chatID,
		'text' => '這個 bot 不是給群組用的喔!',
	), True);
}

if (substr($chatID, 0, 1) == '-') {
	exit;
}

if (!isset($data['from']['username'])) {
	$options = Array(
		'text' => '麻煩設定一下 Username 喔!' . "\n" . '設定完後麻煩請用 /start [code] 驗證' ."\n" . 'ex. "/start 426892"' . "\n" . '(code 可於攤位取得)',
	);
	sendMsg($options, True);
	exit;
}

if (in_array($userID, $Admin_list)) {
	if (isset($data['forward_from'])) {
		$name = 'User Info: ' . "\n";
		$name .= 'UID: ' . $data['forward_from']['id'] . "\n";
		if (isset($data['forward_from']['username'])) {
			$name .= 'username: @' . $data['forward_from']['username'] . "\n";
		}
		$name .= 'First name: ' . $data['forward_from']['first_name'] . "\n";
		if (isset($data['forward_from']['last_name'])) {
			$name .= 'Last name: ' . $data['forward_from']['last_name'] . "\n";
		}
		$options = Array(
			'text' => $name,
		);
		sendMsg($options, True);
		exit;
	}
} else {
	if (isset($data['forward_from'])) {
		$options = Array(
			'text' => 'Forward?!',
		);
		sendMsg($options, True);
		exit;
	}
}
/* End of Check User */

sendLog();

/* Start of Command */
if (substr($data['text'], 0, 1) == '/') {
	$cmd = substr($data['text'], 1);
	$cmd = explode(' ', $cmd, 2);
	$arg = $cmd[1];
	$cmd = $cmd[0];
	$cmd = strtolower($cmd);
	switch ($cmd) {
		case 'start':
			if (!preg_match('/^[0-9]{6}$/', $arg)) {
				$options = Array(
					'text' => '您尚未驗證喔！' . "\n" . '請用 /start [code] 驗證' ."\n" . 'ex. "/start 426892"' . "\n" . '(code 可於攤位取得)',
				);
				sendMsg($options, True);
				exit;
			}
			require('totp.php');
			$ga = new PHPGangsta_GoogleAuthenticator();
			$checkResult = $ga->verifyCode(TOTP_Secret, $arg, 10); // 2 = 2*30sec clock tolerance
			if (!$checkResult) {
				$options = Array(
					'text' => '驗證碼錯誤！' . "\n" .'請用 /start [code] 驗證' ."\n" . 'ex. "/start 426892"' . "\n" . '(code 可於攤位取得)',
				);
				sendMsg($options, True);
				exit;
			}

			$field = Array(
				'token' => API_TOKEN,
				'user_id' => $userID,
				'username' => $data['from']['username'],
				'first_name' => $data['from']['first_name'],
				'last_name' => ((isset($data['from']['last_name'])) ? $data['from']['last_name'] : ''),
			);
			$url = API_URL . 'post_answer';
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POST, True);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $field);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
			curl_exec($curl);
			curl_close($curl);
			$options = Array(
				'parse_mode' => 'Markdown',
				'text' => '歡迎加入 YSITD!' . "\n\n" . '[Telegram 群組](https://telegram.me/joinchat/' . group_joinlink . ')' . "\n" . '參與闖關活動請點擊 /question',
			);
			sendMsg($options, True);
			break;

		case 'help':
			$text = '點擊 /question 開始作答' . "\n";
			$text .= '輸入 /me 查看當前已作答題數、答對題數' . "\n";
			$text .= '顯示 YSITD 群組連結請點 /link' . "\n";
			$text .= 'Tips: 答題越多抽中機率越高喔！' . "\n";
			$options = Array(
				'text' => $text,
			);
			sendMsg($options, True);
			break;

		case 'question':
			newQust();
			break;

		case 'me':
			showUserStatus($userID);
			break;

		case 'link':
			$options = Array(
				'parse_mode' => 'Markdown',
				'text' => 'YSITD Telegram Group Link: [Click Me](https://telegram.me/joinchat/' . group_joinlink . ')',
			);
			sendMsg($options, True);
			break;
		case 'id':
			$options = Array(
				'text' => $userID,
			);
			sendMsg($options, True);
			break;

		case 'join':
			sendInGroup($userID, True);
			break;

		case 'cancel':
			$options = Array(
				'hide_keyboard' => True,
			);
			$options = json_encode($options);
			$options = Array(
				'reply_markup' => $options,
				'text' => 'Canceled.',
			);
			sendMsg($options, True);
			break;

		default:
			$options = Array(
				'text' => '無此指令',
			);
			sendMsg($options, True);
	}
	exit;
}
/* End of Command */


if (preg_match('/^[1-4]\. .+/', $data['text'])) {
	$ans = substr($data['text'], 0, 1);
	sendAns($ans);
	newQust();
	exit;
}

if (preg_match('/\'.+(--|\/\*)/', $data['text'])) {
	$options = Array(
		'text' => 'Are you try to hack me?',
	);
	sendMsg($options, True);
}
