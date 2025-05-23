<?php declare(strict_types=1);

require_once realpath("vendor/autoload.php");

use PHPUnit\Framework\TestCase;
use QubeSync\QubeSync;

final class QubeSyncTest extends TestCase
{
    public function testValidWebhookSignature()
    {
        $body = json_encode(['foo' => 'bar']);
        $timestamp = time();

        // Simulate a signature using the same logic as signPayload
        $signature = QubeSync::signPayload($body);

        // Simulate signature header
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $result = QubeSync::verifyAndBuildWebhook($body, $signatureHeader);

        $this->assertEquals(['foo' => 'bar'], $result);
    }

    public function testExpiredTimestamp()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Timestamp more than");

        $body = json_encode(['foo' => 'bar']);
        $timestamp = time() - 1000; // simulate expired timestamp
        $signature = QubeSync::signPayload($body);
        $signatureHeader = "t={$timestamp},v1={$signature}";

        QubeSync::verifyAndBuildWebhook($body, $signatureHeader, $maxAge = 500);
    }

    public function testInvalidSignature()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Webhook signature mismatch");

        $body = json_encode(['foo' => 'bar']);
        $timestamp = time();
        $badSignature = 'invalidsignature';
        $signatureHeader = "t={$timestamp},v1={$badSignature}";

        QubeSync::verifyAndBuildWebhook($body, $signatureHeader);
    }
}
