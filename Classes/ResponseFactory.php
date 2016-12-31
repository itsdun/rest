<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 24.04.14
 * Time: 21:08
 */

namespace Cundd\Rest;

use Cundd\Rest\Http\Header;
use Cundd\Rest\Http\RestRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Factory class to create Response objects
 */
class ResponseFactory implements SingletonInterface, ResponseFactoryInterface
{
    /**
     * @var \Cundd\Rest\RequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * ResponseFactory constructor.
     *
     * @param RequestFactoryInterface $requestFactory
     */
    public function __construct(RequestFactoryInterface $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    /**
     * Returns a response with the given content and status code
     *
     * @param string|array $data       Data to send
     * @param int          $status     Status code of the response
     * @return ResponseInterface
     */
    public function createResponse($data, $status)
    {
        $responseClass = $this->getResponseImplementationClass();
        /** @var ResponseInterface $response */
        $response = new $responseClass();
        $response = $response->withStatus($status);
        $response->getBody()->write($data);

        return $response;
    }

    /**
     * Returns a response with the given message and status code
     *
     * Some data (e.g. the format) will be read from the request. If no explicit request is given, the Request Factory
     * will be queried
     *
     * @param string|array         $data
     * @param int                  $status
     * @param RestRequestInterface $request
     * @return ResponseInterface
     */
    public function createErrorResponse($data, $status, RestRequestInterface $request = null)
    {
        return $this->createFormattedResponse($data, $status, true, $request);
    }

    /**
     * Returns a response with the given message and status code
     *
     * Some data (e.g. the format) will be read from the request. If no explicit request is given, the Request Factory
     * will be queried
     *
     * @param string|array         $data
     * @param int                  $status
     * @param RestRequestInterface $request
     * @return ResponseInterface
     */
    public function createSuccessResponse($data, $status, RestRequestInterface $request = null)
    {
        return $this->createFormattedResponse($data, $status, false, $request);
    }

    /**
     * Returns a response with the given message and status code
     *
     * @param string|array         $data       Data to send
     * @param int                  $status     Status code of the response
     * @param bool                 $forceError If TRUE the response will be treated as an error, otherwise any status below 400 will be a normal response
     * @param RestRequestInterface $request
     * @return ResponseInterface
     */
    private function createFormattedResponse($data, $status, $forceError, $request)
    {
        $responseClass = $this->getResponseImplementationClass();
        /** @var ResponseInterface $response */
        $response = new $responseClass();
        $response = $response->withStatus($status);

        if (!$request) {
            $request = $this->requestFactory->getRequest();
        }

        $messageKey = 'message';
        if ($forceError || $status >= 400) {
            $messageKey = 'error';
        }

        switch ($request->getFormat()) {
            case 'json':

                switch (gettype($data)) {
                    case 'string':
                        $body = array(
                            $messageKey => $data,
                        );
                        break;

                    case 'array':
                        $body = $data;
                        break;

                    case 'NULL':
                        $body = array(
                            $messageKey => $response->getReasonPhrase(),
                        );
                        break;

                    default:
                        $body = null;
                }

                $response->getBody()->write(json_encode($body));
                $response = $response->withHeader(Header::CONTENT_TYPE, 'application/json');
                break;

            case 'xml':
                // TODO: support more response formats

            default:
                $response->getBody()->write(
                    sprintf(
                        'Unsupported format: %s. Please set the Accept header to application/json',
                        $request->getFormat()
                    )
                );
        }

        return $response;
    }

    /**
     * @return string
     */
    private function getResponseImplementationClass()
    {
        if (class_exists(\TYPO3\CMS\Core\Http\Response::class)) {
            return \TYPO3\CMS\Core\Http\Response::class;
        }
        if (class_exists(\Zend\Diactoros\Response::class)) {
            return \Zend\Diactoros\Response::class;
        }
        throw new \LogicException('No response implementation found');
    }
}
