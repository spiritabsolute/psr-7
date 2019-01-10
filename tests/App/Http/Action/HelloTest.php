<?php
namespace Tests\App\Http\Action;

use App\Http\Action\Hello;
use Framework\Template\TemplateRenderer;
use PHPUnit\Framework\TestCase;

class HelloTest extends TestCase
{
	private $renderer;

	public function setUp(): void
	{
		parent::setUp();

		$this->renderer = new TemplateRenderer("templates");
	}

	public function testGuest()
	{
		$action = new Hello($this->renderer);
		$response = $action();

		self::assertEquals(200, $response->getStatusCode());
		self::assertContains("Hello, Guest!", $response->getBody()->getContents());
	}
}