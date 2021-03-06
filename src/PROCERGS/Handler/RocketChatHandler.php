<?php

/**
 * This file is part of the RocketChat Monolog Handler
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PROCERGS\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * RocketChatHandler uses cURL to trigger Rocket.Chat WebHooks
 *
 * @package PROCERGS\Handler
 */
class RocketChatHandler extends AbstractProcessingHandler
{
    /** @var ClientInterface */
    private $client;

    /** @var string */
    private $channel;

    /** @var string */
    private $username;

    /** @var string */
    private $webHookUrl;

    /** @var int Maximum string length when adding object dumps as attachment */
    private $maxDumpLength = 4000;

    /**
     * @param string $channel The name of the channel where the logs should be posted
     * @param string $username The username to be displayed
     * @param string $webHookUrl The WebHook URL obtained from Rocket.Chat
     * @param ClientInterface $client The Guzzle HTTP Client that will be used for the request
     * @param int $level The minomum logging level ar which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(
        $webHookUrl,
        $channel = null,
        $username = null,
        ClientInterface $client = null,
        $level = Logger::ERROR,
        $bubble = true
    ) {
        if (!$client instanceof ClientInterface) {
            $client = new Client();
        }

        $this->channel = $channel;
        $this->username = $username;
        $this->webHookUrl = $webHookUrl;
        $this->client = $client;

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $formattedMessage = sprintf(
            "Log channel: *%s*\nLog level: *%s*\n```%s```",
            $record['channel'], $record['level_name'], $record['message']
        );

        $attachments = array_values(
            array_map(
                function ($key, $value) {
                    return [
                        'title' => (string)$key,
                        'text' => substr(json_encode($value, JSON_UNESCAPED_UNICODE), 0, $this->maxDumpLength),
                    ];
                },
                array_keys($record['context']),
                $record['context']
            )
        );

        $postData = array_filter([
            'username' => $this->username,
            'icon_emoji' => '',
            'channel' => $this->channel,
            'text' => $formattedMessage,
            'attachments' => $attachments,
        ]);

        $this->client->post($this->webHookUrl, ['json' => $postData]);
    }

    /**
     * Sets the maximum length of object dump
     * @param int $maxDumpLength
     * @return void
     */
    public function setMaxDumpLength(int $maxDumpLength)
    {
        $this->maxDumpLength = $maxDumpLength;
    }

}
