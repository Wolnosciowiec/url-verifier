<?php

namespace Controllers;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Store multiple URL's in the queue, it's just a wrapper
 * that does responses through AddToQueue controller
 *
 * @package Controllers
 */
class AddBatchToQueueController extends AbstractBaseController
{
    const FAILED_ADDING_TO_QUEUE = 'FAILED_ADDING_MULTIPLE_FILES_TO_QUEUE';

    /**
     * @var App $app
     */
    private $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct($app);
    }

    /**
     * @param array $data
     * @param string $index
     *
     * @throws \Exception
     */
    private function validateQueueData(array $data, string $index)
    {
        if (!is_array($data)) {
            throw new \Exception('Item at index "' . $index . '" is not an array');
        }

        if (!isset($data['url_address'])) {
            throw new \Exception('Item at index "' . $index . '" is missing "url_address" paramter');
        }

        if (!isset($data['type'])) {
            throw new \Exception('Item at index "' . $index . '" is missing "type" paramter');
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function executeAction(Request $request, Response $response)
    {
        $queueData = $request->getParam('queue_data');
        $count     = 0;

        foreach ($queueData as $index => $item) {
            try {
                $this->validateQueueData($item, $index);
            }
            catch (\Exception $e) {
                return $response->withJson([
                    'success' => false,
                    'code'    => self::ERROR_REQUEST_INVALID,
                    'message' => $e->getMessage(),
                ]);
            }

            $controller = new AddToQueueController($this->app);
            $result = $controller->executeAction($request, clone $response);

            if ((int)$response->getStatusCode() > 300) {
                return $response->withJson([
                    'success' => false,
                    'code'    => self::FAILED_ADDING_TO_QUEUE,
                    'message' => 'Failed adding multiple files to queue, stopped at position ' . $index . ', response: ' . (string)$result->getBody(),
                ], 400);
            }

            $count++;
        }

        return $response->withJson([
            'success'         => true,
            'added_count'     => $count,
        ]);
    }
}