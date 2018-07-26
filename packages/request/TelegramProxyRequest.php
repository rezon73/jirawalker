<?php

namespace Request;

use Config\Config;

class TelegramProxyRequest extends Request implements \IRequest
{
    public function send()
    {
        if (empty($this->additionalData['pullRequests'])) {
            return;
        }

        $message = '';

        foreach($this->additionalData['pullRequests'] as $pullRequest) {
            $message .= 'Author: ' . $pullRequest['author']['user']['displayName'] . PHP_EOL;
            $message .= 'Link: <a>' . $pullRequest['links']['self'][0]['href'] . '</a>' . PHP_EOL . PHP_EOL;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->additionalData['telegramProxyUrl']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_POSTFIELDS, [
            'chatId' => $this->additionalData['chatId'],
            'title' => $this->additionalData['message'],
            'message' => $message,
        ]);
        var_dump([
            'chatId' => $this->additionalData['chatId'],
            'title' => $this->additionalData['message'],
            'message' => $message,
        ]);

        $response = curl_exec($curl);

        if (!curl_errno($curl)) {
            curl_close($curl);
        } else {
            $curlError = curl_error($curl);
            curl_close($curl);

            echo 'Curl error ' . $this->additionalData['telegramProxyUrl'] . ' ' . $curlError;
        }

        unset($this->additionalData['pullRequests']);
    }
}