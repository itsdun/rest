<?php

namespace Cundd\Rest\Tests\Manual\Api;

class GreetingTest extends AbstractApiCase
{
    /**
     * @test
     */
    public function getGreetingTest()
    {
        $response = $this->request('/');

        $this->assertSame(200, $response->status, $this->getErrorDescription($response));
        $this->assertTrue(
            in_array(
                $response->content,
                [
                    '{"message":"What\'s up?"}',
                    '{"message":"Good Morning!"}',
                    '{"message":"Hy! Still awake?"}',
                ]
            ),
            $this->getErrorDescription($response)
        );
    }
}
