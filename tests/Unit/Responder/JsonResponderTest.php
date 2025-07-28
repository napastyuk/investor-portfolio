<?php declare(strict_types=1);

namespace App\Test\Unit\Responder;

use App\Interface\Http\Responder\JsonResponder;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class JsonResponderTest extends TestCase
{
    public function testSuccessResponse()
    {
        $responder = new JsonResponder();
        $response = $responder->success(new Response(), ['ok' => true]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertJson((string)$response->getBody());
        $this->assertStringContainsString('"ok":true', (string)$response->getBody());
    }

    public function testErrorResponse()
    {
        $responder = new JsonResponder();
        $response = $responder->error(new Response(), 'Something went wrong', 400);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Something went wrong', (string)$response->getBody());
    }
}
