<?php

namespace Nxmad\Heized;

use GuzzleHttp\Client;

class Heized
{
    /**
     * The public key of project.
     *
     * @var int
     */
    protected $public;

    /**
     * The secret or project.
     *
     * @var string
     */
    protected $secret;

    /**
     * The HTTP client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Heized constructor.
     *
     * @param string $public
     * @param string $secret
     * @param string|null $url
     */
    public function __construct(string $public, string $secret, string $url = null)
    {
        $this->public = $public;
        $this->secret = $secret;

        $this->client = new Client([
            'base_uri' => $url ?: 'https://api.heized.com/',
        ]);
    }

    /**
     * Magic call.
     *
     * @param $name
     * @param $arguments
     *
     * @return \stdClass
     */
    public function __call($name, $arguments)
    {
        $path = explode('_', mb_strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name)));

        $method = array_shift($path);

        return $this->call(join('/', $path), $method, $arguments[0]);
    }

    /**
     * Sign data.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function sign($data): string
    {
        $data = is_scalar($data) ? $data : json_encode($data);

        return hash_hmac('sha3-512', $data, $this->secret);
    }

    /**
     * Call API endpoint.
     *
     * @param string $endpoint
     * @param string $method
     * @param array $query
     *
     * @return \stdClass
     */
    public function call(string $endpoint, string $method = 'GET', array $query = [])
    {
        $body = json_encode($query);
        $method = strtolower($method);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',

            'X-Heized-Public' => $this->public,
            'X-Heized-Signature' => $this->sign($body),
        ];

        $request = $this->client->{$method}($endpoint, compact('headers', $method === 'get' ? 'query' : 'body'));

        return json_decode((string) $request->getBody());
    }
}
