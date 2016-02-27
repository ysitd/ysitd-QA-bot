<?php
function sendMsg($options, $blackbox = False) {
	global $chatID;
	$default = Array(
		'chat_id' => $chatID,
		'disable_web_page_preview' => True,
	);
	$options = array_merge($default, $options);
	$field = http_build_query($options);
	$url = 'https://api.telegram.org/bot' . botID . ':' . botToken . '/sendMessage?' . $field;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
	$data = curl_exec($curl);
	curl_close($curl);
	if (!$blackbox) {
		$data = json_decode($data, True);
		sendLog($data);
	}
	return $data;
}

function getProPic() {
	global $userID;
	$url = 'https://api.telegram.org/bot' . botID . ':' . botToken . '/getUserProfilePhotos?user_id=' . $userID;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
	$data = curl_exec($curl);
	curl_close($curl);
	$data = json_decode($data, True);
	return $data['result'];
}

function newQust() {
	global $userID;
	$field = Array(
		'token' => API_TOKEN,
		'user_id' => $userID,
	);
	$field = http_build_query($field);
	$url = API_URL . 'random_question?' . $field;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
	$data = curl_exec($curl);
	curl_close($curl);
	$data = json_decode($data, True);
	sendQust($data);
}

function sendQust($data) {
	if (!isset($data['question'])) {
		$text = '您已經回答完所有題目了 Orz' . "\n" . '該起床了喔，肥宅';
		$options = Array(
			'text' => $text,
		);
	} else {
		$text = '[' . $data['class'] . ']' . "\n";
		$text .= '本題目由 ' . $data['author'] . ' 提供' . "\n\n";
		$text .= $data['question'] . "\n\n";
		foreach (range(1, 4) as $i) {
			$text .= $i . '. ' . $data['option'][$i] . "\n";
		}
        	$answer = $data['option'];
		$ans = Array(
			'keyboard' => Array(
				Array(
					'1. ' . $answer[1],
					'2. ' . $answer[2],
				),
				Array(
					'3. ' . $answer[3],
					'4. ' . $answer[4],
				),
			),
			'resize_keyboard' => True,
			'one_time_keyboard' => True,
		);
		$ans = json_encode($ans);
		$options = Array(
			'text' => $text,
			'reply_markup' => $ans,
		);
	}
	sendMsg($options, True);
}

function sendInGroup($user, $status) {
	$field = Array(
		'token' => API_TOKEN,
		'user_id' => $user,
		'ingroup' => $status,
	);
	$url = API_URL . 'user_status';
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, True);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $field);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
	$data = curl_exec($curl);
	curl_close($curl);
	$data = json_decode($data, True);
}

function sendAction() {
	global $userID;
	$options = Array(
		'chat_id' => $userID,
		'action' => 'typing',
	);
	$field = http_build_query($options);
	$url = 'https://api.telegram.org/bot' . botID . ':' . botToken . '/sendChatAction?' . $field;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
	curl_exec($curl);
	curl_close($curl);
}

function getMe($user) {
	$field = Array(
		'token' => API_TOKEN,
		'user_id' => $user,
	);
	$field = http_build_query($field);
	$url = API_URL . 'user_status?' . $field;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
	$data = curl_exec($curl);
	curl_close($curl);
	$data = json_decode($data, True);
	return $data;
}

function showUserStatus($user) {
	$data = getMe($user);
	$text = '';
	$text .= '答對題數: *' . $data['correct'] . '*' . "\n";
	$text .= '錯誤題數: *' . $data['incorrect'] . '*' . "\n";
	if (!$data['ingroup']) {
		$text .= '您尚未加入群組喔！誠摯的邀請您點擊下方連結加入' . "\n";
		$text .= '[Click Me](https://telegram.me/joinchat/' . group_joinlink . ')';
	}
	$options = Array(
		'text' => $text,
		'parse_mode' => 'Markdown',
	);
	sendMsg($options, True);
}

function sendAns($ans) {
	global $userID;
	$field = Array(
		'token' => API_TOKEN,
		'user_id' => $userID,
		'answer' => $ans,
	);
	$url = API_URL . 'post_answer';
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, True);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $field);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
	$data = curl_exec($curl);
	curl_close($curl);
	$data = json_decode($data, True);
	if ($data['result']) {
		$text = '恭喜您答對了！' . "\n";
		if (rand(1, 2) == 1) {
			$text .= '您尚未加入群組喔！誠摯的邀請您點擊下方連結加入' . "\n";
		}
	} else {
		$text = '答錯了喔！';
	}
	$options = Array(
		'text' => $text,
		'chat_id' => $userID,
	);
	sendMsg($options, True);
}

function sendLog($data = '') {
	if ($data == '') {
		global $data;
		$options = Array(
			'chat_id' => Log_ChatID,
			'from_chat_id' => $data['chat']['id'],
			'message_id' => $data['message_id'],
		);
		$field = http_build_query($options);
		$url = 'https://api.telegram.org/bot' . botID . ':' . botToken . '/forwardMessage?' . $field;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
		curl_exec($curl);
		curl_close($curl);
	} elseif (isset($data['result']['chat'])) {
		$data = $data['result'];
		$text = 'to @' . $data['chat']['username'] . ' (' . $data['chat']['id'] . ')' . "\n";
		$text .= $data['text'];
		$options = Array(
			'text' => $text,
			'chat_id' => Log_ChatID,
		);
		sendMsg($options, True);
	}
}
