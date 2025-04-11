<?php

namespace QubeSync;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class QubeSync
{
    const BASE_URL = 'https://qubesync.com/api/v1/';

    public static function get($url, $headers = [])
    {
        $client = self::connection();
        try {
            $response = $client->get($url, [
                'headers' => array_merge(self::defaultHeaders(), $headers),
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception("Unexpected QUBE response: " . $e->getCode() . "\n" . $e->getMessage());
        }
    }

    public static function post($url, $body = null, $headers = [])
    {
        $client = self::connection();
        try {
            $options = [
                'headers' => array_merge(self::defaultHeaders(), $headers),
            ];
            if ($body !== null) {
                $options['json'] = $body;
            }
            $response = $client->post($url, $options);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception("Unexpected QUBE response: " . $e->getCode() . "\n" . $e->getMessage());
        }
    }

    public static function delete($url, $headers = [])
    {
        $client = self::connection();
        try {
            $client->delete($url, [
                'headers' => array_merge(self::defaultHeaders(), $headers),
            ]);
            return true;
        } catch (RequestException $e) {
            throw new \Exception("Unexpected QUBE response: " . $e->getCode() . "\n" . $e->getMessage());
        }
    }

    public static function createConnection(callable $callback = null)
    {
        $response = self::post("connections");
        $connectionId = $response['data']['id'] ?? null;

        if (!$connectionId) {
            throw new \Exception("Connection ID not found");
        }

        if ($callback) {
            $callback($connectionId);
        }

        return $connectionId;
    }

    public static function deleteConnection($id)
    {
        return self::delete("connections/$id");
    }

    public static function getConnection($id)
    {
        return self::get("connections/$id")['data'];
    }

    public static function queueRequest($connectionId, array $request)
    {
        if (empty($request['request_xml']) && empty($request['request_json'])) {
            throw new \Exception("Must have either request_xml or request_json");
        }

        if (empty($request['webhook_url'])) {
            trigger_error("No webhook_url provided", E_USER_WARNING);
        }

        $payload = ['queued_request' => $request];

        return self::post("connections/$connectionId/queued_requests", $payload)['data'];
    }

    public static function getRequest($id)
    {
        return self::get("queued_requests/$id")['data'];
    }

    public static function getRequests($connectionId)
    {
        return self::get("connections/$connectionId/queued_requests")['data'];
    }

    public static function deleteRequest($id)
    {
        return self::delete("queued_requests/$id");
    }

    public static function getQwc($connectionId)
    {
        return self::post("connections/$connectionId/qwc")['qwc'];
    }

    public static function generatePassword($connectionId)
    {
        $response = self::post("connections/$connectionId/password");
        return $response['data']['password'] ?? throw new \Exception("Password not found");
    }

    public static function extractSignatureMeta($header)
    {
        $parts = explode(",", $header);
        $ts = explode("=", array_shift($parts));
        return [
            'timestamp' => (int) $ts[1],
            'signatures' => $parts,
        ];
    }

    public static function verifyAndBuildWebhook($body, $signature, $maxAge = 500)
    {
        ['timestamp' => $timestamp, 'signatures' => $signatures] = self::extractSignatureMeta($signature);

        if ($timestamp < time() - $maxAge) {
            throw new \Exception("Timestamp more than {$maxAge}ms old.");
        }

        $signed = self::signPayload($body);
        foreach ($signatures as $sig) {
            if (trim($sig) === $signed) {
                return json_decode($body, true);
            }
        }

        throw new \Exception("Webhook signature mismatch");
    }

    private static function connection()
    {
        return new Client([
            'base_uri' => getenv('QUBE_URL') ?: self::BASE_URL,
            'auth' => [self::apiKey(), ''],
        ]);
    }

    private static function defaultHeaders()
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private static function apiKey()
    {
        $key = getenv('QUBE_API_KEY');
        if (!$key) {
            throw new \Exception("QUBE_API_KEY not set in environment.");
        }
        return $key;
    }

    private static function apiSecret()
    {
        $secret = getenv('QUBE_WEBHOOK_SECRET');
        if (!$secret) {
            throw new \Exception("QUBE_WEBHOOK_SECRET not set in environment.");
        }
        return $secret;
    }

    private static function signPayload($payload)
    {
        return hash_hmac('sha256', $payload, self::apiSecret());
    }
}
