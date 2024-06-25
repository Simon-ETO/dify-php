<?php
declare(strict_types=1);

namespace Simoneto\Dify\Apps;

use Simoneto\Dify\Client;
use Simoneto\Dify\Responses\Response;
use Simoneto\Dify\Responses\StreamResponse;
use Simoneto\Dify\Dify;

abstract class App
{

    /**
     * The api key of dify app.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The http client.
     * @var Client
     */
    protected $client;

    /**
     * Create a new dify app instance.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = Dify::client($apiKey);
    }

    /**
     * Get the api key.
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the dify app information.
     *
     * @return Response
     */
    public function parameters(): Response
    {
        return $this->client->get('/parameters');
    }

    /**
     * End-users can provide feedback messages, facilitating application developers to optimize expected outputs.
     *
     * @param string $userId
     * @param string $messageId
     * @param string|null $rating
     * @return Response
     */
    public function messageFeedback(string $userId, string $messageId, string $rating = null): Response
    {
        return $this->client->postJson("/messages/{$messageId}/feedbacks", [
            'user' => $userId,
            'rating' => $rating
        ]);
    }

    /**
     * Upload a file (currently only images are supported) for use when sending messages, enabling multimodal understanding of images and text.
     * Supports png, jpg, jpeg, webp, gif formats.
     * Uploaded files are for use by the current end-user only.
     *
     * @param string $userId
     * @param string|array<int,array<string,string>> $files
     * @param string|null $filename
     * @return Response
     */
    public function fileUpload(string $userId, $files, string $filename = null): Response
    {
        if (is_string($files)) {
            return $this->fileUpload($userId, [
                [
                    'name' => $filename,
                    'path' => $files
                ]
            ]);
        }
        $multipart = [
            [
                'name' => 'user',
                'contents' => $userId
            ]
        ];
        foreach ($files as $file) {
            $multipart[] = [
                'name' => 'file',
                'contents' => fopen($file['path'], 'r+'),
                'filename' => empty($file['name']) ? pathinfo($file['path'], PATHINFO_FILENAME) : $file['name']
            ];
        }
        return $this->client->request('POST', '/files/upload', [
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
            'multipart' => $multipart
        ]);
    }

    /**
     * Text to speech.
     *
     * @param string $userId
     * @param string $text
     * @return Response
     */
    public function textToAudio(string $userId, string $text): Response
    {
        return $this->client->postJson('/text-to-audio', [
            'text' => $text,
            'user' => $userId,
            'streaming' => false
        ]);
    }

    /**
     * Text to speech with enable streaming output.
     *
     * @param string $userId
     * @param string $text
     * @return StreamResponse
     */
    public function textToAudioStream(string $userId, string $text): StreamResponse
    {
        $response = $this->client->postJson('/text-to-audio', [
            'text' => $text,
            'user' => $userId,
            'streaming' => true
        ]);
        return new StreamResponse($response);
    }
}
