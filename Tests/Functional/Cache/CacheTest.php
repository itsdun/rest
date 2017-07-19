<?php

/**
 * @author COD
 * Created 10.12.13 09:21
 */

namespace Cundd\Rest\Tests\Functional\Cache;

use Cundd\Rest\Cache\Cache;
use Cundd\Rest\Http\Header;
use Cundd\Rest\Http\RestRequestInterface;
use Cundd\Rest\Tests\Functional\AbstractCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Tests for the Caching interface
 */
class CacheTest extends AbstractCase
{
    /**
     * @var \Cundd\Rest\Cache\Cache
     */
    protected $fixture;

    public function setUp()
    {
        parent::setUp();

        /** @var Cache $fixture */
        $fixture = $this->objectManager->get('Cundd\\Rest\\Cache\\Cache');
        $fixture->setCacheLifeTime(10);
        $fixture->setExpiresHeaderLifeTime(5);
        $this->fixture = $fixture;
    }

    protected function tearDown()
    {
        unset($this->fixture);
        parent::tearDown();
    }


    /**
     * @test
     * @param $uri
     * @param $format
     * @param $expectedKey
     * @dataProvider getCacheKeyDataProvider
     */
    public function getCacheKeyTest($expectedKey, $uri, $format = null)
    {
        $request = $this->buildRequestWithUri($uri, $format);
        $cacheKey = $this->fixture->getCacheKeyForRequest($request);
        $this->assertEquals($expectedKey, $cacheKey, 'Failed for URI ' . $uri);
    }

    public function getCacheKeyDataProvider()
    {
        return [
            ['e53553c0d92fd881af17e02c9bba3e3dd592e1b5', 'MyExt-MyModel/1'],
            ['e53553c0d92fd881af17e02c9bba3e3dd592e1b5', 'MyExt-MyModel/1', 'json'],
            ['31ab99086d0cce9d1ed126c591d7fa575097aa92', 'MyExt-MyModel/1', 'xml'],
            ['35ef58f0e156a72ef1d24eb411d37d6fed5fabc2', 'my_ext-my_model/1'],
            ['35ef58f0e156a72ef1d24eb411d37d6fed5fabc2', 'my_ext-my_model/1', 'json'],
            ['771434eb664ab12cc4a84eb07acbf6721fff1b2c', 'my_ext-my_model/1', 'xml'],
            ['0f83addd3a3712280e964fa86590cac5cc55465b', 'my_ext-my_model'],
            ['0f83addd3a3712280e964fa86590cac5cc55465b', 'my_ext-my_model', 'json'],
            ['a01d218d891309314abd02036e4f33859174db88', 'my_ext-my_model', 'xml'],
            ['071c0adfb11121bc31d50bda32bfee48d1b89b92', 'vendor-my_second_ext-my_model/1'],
            ['bca86e9915f0b66fecdfa466658f0954978b072b', 'Vendor-MySecondExt-MyModel/1'],
            ['e876619ce921ea2515e8e051952dad6c6a76720d', 'Vendor-NotExistingExt-MyModel/1'],
            ['e876619ce921ea2515e8e051952dad6c6a76720d', 'Vendor-NotExistingExt-MyModel/1', 'json'],
            ['fcd5ffa2c3328a2f88a0be9b697afcd13776c202', 'Vendor-NotExistingExt-MyModel/1', 'xml'],
            ['f863f40b16b548ec93c128dcb9baeb24d7978c0a', 'MyAliasedModel'],
        ];
    }

    /**
     * @test
     */
    public function getCacheKeyForGetRequestWithParameterTest()
    {
        $uri = 'MyExt-MyModel/1';
        $request = $this->buildRequestWithUri($uri)->withQueryParams(['q' => 'queryTestParameter']);
        /** @var RestRequestInterface $request */
        $cacheKey = $this->fixture->getCacheKeyForRequest($request);
        $this->assertEquals('8f0f35de918d2e1494849827b2b453792c54d030', $cacheKey, 'Failed for URI ' . $uri);
    }

    /**
     * @test
     */
    public function getCacheKeyForGetRequestWithDifferentParametersShouldNotMatchTest()
    {
        $uri = 'MyExt-MyModel/1';
        /** @var RestRequestInterface $request */
        $request = $this->buildRequestWithUri($uri)->withQueryParams(['q' => 'queryTestParameter']);
        /** @var RestRequestInterface $request2 */
        $request2 = $this->buildRequestWithUri($uri)->withQueryParams(['q' => 'queryTestParameter2']);
        /** @var RestRequestInterface $requestWithoutParameters */
        $requestWithoutParameters = $this->buildRequestWithUri($uri);

        $this->assertNotEquals(
            $this->fixture->getCacheKeyForRequest($request),
            $this->fixture->getCacheKeyForRequest($request2),
            'Failed for URI ' . $uri
        );
        $this->assertNotEquals(
            $this->fixture->getCacheKeyForRequest($request),
            $this->fixture->getCacheKeyForRequest($requestWithoutParameters),
            'Failed for URI ' . $uri
        );
        $this->assertNotEquals(
            $this->fixture->getCacheKeyForRequest($request2),
            $this->fixture->getCacheKeyForRequest($requestWithoutParameters),
            'Failed for URI ' . $uri
        );
    }

    /**
     * @test
     */
    public function getCachedInitialValueForRequestTest()
    {
        $uri = 'MyAliasedModel' . time();
        $request = $this->buildRequestWithUri($uri);

        $this->fixture->setCacheInstance($this->getFrontendCacheMock());
        $cachedValue = $this->fixture->getCachedValueForRequest($request);
        $this->assertNull($cachedValue);
    }

    /**
     * @test
     */
    public function getCachedValueForRequestTest()
    {
        $uri = 'MyAliasedModel' . time();
        $responseArray = [
            'content'             => 'the content',
            'status'              => 200,
            Header::CONTENT_TYPE  => 'application/json',
            Header::LAST_MODIFIED => gmdate('D, d M Y H:i:s \G\M\T'),
        ];

        $request = $this->buildRequestWithUri($uri);

        $cacheInstance = $this->getFrontendCacheMock();
        $cacheInstance->expects($this->atLeastOnce())->method('get')->will($this->returnValue($responseArray));
        $this->fixture->setCacheInstance($cacheInstance);
        $response = $this->fixture->getCachedValueForRequest($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($responseArray['content'], (string)$response->getBody());
        $this->assertSame($responseArray['status'], $response->getStatusCode());
    }

    /**
     * @test
     */
    public function setCachedValueForRequestTest()
    {
        $response = $this->buildTestResponse(200, [], 'Test content');
        $uri = 'MyAliasedModel';
        $request = $this->buildRequestWithUri($uri);

        $cacheInstance = $this->getFrontendCacheMock();
        $cacheInstance->expects($this->atLeastOnce())->method('set')->will($this->returnValue(''));
        $this->fixture->setCacheInstance($cacheInstance);
        $this->fixture->setCachedValueForRequest($request, $response);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    private function getFrontendCacheMock()
    {
        /** @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend|\PHPUnit_Framework_MockObject_MockObject $cacheInstance */
        return $this->getMockObjectGenerator()->getMock(
            'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
            ['getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'],
            [],
            '',
            false
        );
    }
}
