<?php
namespace GuzzleHttp\Command\Guzzle;

use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\ResponseLocation\BodyLocation;
use GuzzleHttp\Command\Guzzle\ResponseLocation\HeaderLocation;
use GuzzleHttp\Command\Guzzle\ResponseLocation\JsonLocation;
use GuzzleHttp\Command\Guzzle\ResponseLocation\ReasonPhraseLocation;
use GuzzleHttp\Command\Guzzle\ResponseLocation\ResponseLocationInterface;
use GuzzleHttp\Command\Guzzle\ResponseLocation\StatusCodeLocation;
use GuzzleHttp\Command\Guzzle\ResponseLocation\XmlLocation;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ResultInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Handler used to create response models based on an HTTP response and
 * a service description.
 *
 * Response location visitors are registered with this Handler to handle
 * locations (e.g., 'xml', 'json', 'header'). All of the locations of a response
 * model that will be visited first have their ``before`` method triggered.
 * After the before method is called on every visitor that will be walked, each
 * visitor is triggered using the ``visit()`` method. After all of the visitors
 * are visited, the ``after()`` method is called on each visitor. This is the
 * place in which you should handle things like additionalProperties with
 * custom locations (i.e., this is how it is handled in the JSON visitor).
 */
class Deserializer
{
    /** @var ResponseLocationInterface[] $responseLocations */
    private $responseLocations;

    /** @var DescriptionInterface $description */
    private $description;

    /** @var boolean $process */
    private $process;

    /**
     * @param DescriptionInterface $description
     * @param bool $process
     * @param ResponseLocationInterface[] $responseLocations Extra response locations
     */
    public function __construct(
        DescriptionInterface $description,
        $process,
        array $responseLocations = []
    ) {
        static $defaultResponseLocations;
        if (!$defaultResponseLocations) {
            $defaultResponseLocations = [
                'body'         => new BodyLocation(),
                'header'       => new HeaderLocation(),
                'reasonPhrase' => new ReasonPhraseLocation(),
                'statusCode'   => new StatusCodeLocation(),
                'xml'          => new XmlLocation(),
                'json'         => new JsonLocation(),
            ];
        }

        $this->responseLocations = $responseLocations + $defaultResponseLocations;
        $this->description = $description;
        $this->process = $process;
    }

    /**
     * Deserialize the response into the specified result representation
     *
     * @param ResponseInterface     $response
     * @param RequestInterface|null $request
     * @param CommandInterface      $command
     * @return Result|ResultInterface|void|ResponseInterface
     */
    public function __invoke(ResponseInterface $response, RequestInterface $request, CommandInterface $command)
    {
        // If the user don't want to process the result, just return the plain response here
        if ($this->process === false) {
            return $response;
        }

        $name = $command->getName();
        $operation = $this->description->getOperation($name);

        $this->handleErrorResponses($response, $request, $command, $operation);

        // Add a default Model as the result if no matching schema was found
        if (!($modelName = $operation->getResponseModel())) {
            // Not sure if this should be empty or contains the response.
            // Decided to do it how it was in the old version for now.
            return new Result();
        }

        $model = $operation->getServiceDescription()->getModel($modelName);
        if (!$model) {
            throw new \RuntimeException("Unknown model: {$modelName}");
        }

        return $this->visit($model, $response);
    }

    /**
     * Handles visit() and after() methods of the Response locations
     *
     * @param Parameter         $model
     * @param ResponseInterface $response
     * @return Result|ResultInterface|void
     */
    protected function visit(Parameter $model, ResponseInterface $response)
    {
        $result = new Result();
        $context = ['visitors' => []];

        if ($model->getType() === 'object') {
            $result = $this->visitOuterObject($model, $result, $response, $context);
        } elseif ($model->getType() === 'array') {
            $result = $this->visitOuterArray($model, $result, $response, $context);
        } else {
            throw new \InvalidArgumentException('Invalid response model: ' . $model->getType());
        }

        // Call the after() method of each found visitor
        /** @var ResponseLocationInterface $visitor */
        foreach ($context['visitors'] as $visitor) {
            $result = $visitor->after($result, $response, $model);
        }

        return $result;
    }

    /**
     * Handles the before() method of Response locations
     *
     * @param string            $location
     * @param Parameter         $model
     * @param ResultInterface   $result
     * @param ResponseInterface $response
     * @param array             $context
     * @return ResultInterface
     */
    private function triggerBeforeVisitor(
        $location,
        Parameter $model,
        ResultInterface $result,
        ResponseInterface $response,
        array &$context
    ) {
        if (!isset($this->responseLocations[$location])) {
            throw new \RuntimeException("Unknown location: $location");
        }

        $context['visitors'][$location] = $this->responseLocations[$location];

        $result = $this->responseLocations[$location]->before(
            $result,
            $response,
            $model
        );

        return $result;
    }

    /**
     * Visits the outer object
     *
     * @param Parameter         $model
     * @param ResultInterface   $result
     * @param ResponseInterface $response
     * @param array             $context
     * @return ResultInterface
     */
    private function visitOuterObject(
        Parameter $model,
        ResultInterface $result,
        ResponseInterface $response,
        array &$context
    ) {
        $parentLocation = $model->getLocation();

        // If top-level additionalProperties is a schema, then visit it
        $additional = $model->getAdditionalProperties();
        if ($additional instanceof Parameter) {
            // Use the model location if none set on additionalProperties.
            $location = $additional->getLocation() ?: $parentLocation;
            $result = $this->triggerBeforeVisitor($location, $model, $result, $response, $context);
        }

        // Use 'location' from all individual defined properties, but fall back
        // to the model location if no per-property location is set. Collect
        // the properties that need to be visited into an array.
        $visitProperties = [];
        foreach ($model->getProperties() as $schema) {
            $location = $schema->getLocation() ?: $parentLocation;
            if ($location) {
                $visitProperties[] = [$location, $schema];
                // Trigger the before method on each unique visitor location
                if (!isset($context['visitors'][$location])) {
                    $result = $this->triggerBeforeVisitor($location, $model, $result, $response, $context);
                }
            }
        }

        // Actually visit each response element
        foreach ($visitProperties as $property) {
            $result = $this->responseLocations[$property[0]]->visit($result, $response, $property[1]);
        }

        return $result;
    }

    /**
     * Visits the outer array
     *
     * @param Parameter         $model
     * @param ResultInterface   $result
     * @param ResponseInterface $response
     * @param array             $context
     * @return ResultInterface|void
     */
    private function visitOuterArray(
        Parameter $model,
        ResultInterface $result,
        ResponseInterface $response,
        array &$context
    ) {
        // Use 'location' defined on the top of the model
        if (!($location = $model->getLocation())) {
            return;
        }

        // Trigger the before method on each unique visitor location
        if (!isset($context['visitors'][$location])) {
            $result = $this->triggerBeforeVisitor($location, $model, $result, $response, $context);
        }

        // Visit each item in the response
        $result = $this->responseLocations[$location]->visit($result, $response, $model);

        return $result;
    }

    /**
     * Reads the "errorResponses" from commands, and trigger appropriate exceptions
     *
     * In order for the exception to be properly triggered, all your exceptions must be instance
     * of "GuzzleHttp\Command\Exception\CommandException". If that's not the case, your exceptions will be wrapped
     * around a CommandException
     *
     * @param ResponseInterface $response
     * @param RequestInterface  $request
     * @param CommandInterface  $command
     * @param Operation         $operation
     */
    protected function handleErrorResponses(
        ResponseInterface $response,
        RequestInterface $request,
        CommandInterface $command,
        Operation $operation
    ) {
        $errors = $operation->getErrorResponses();

        // We iterate through each errors in service description. If the descriptor contains both a phrase and
        // status code, there must be an exact match of both. Otherwise, a match of status code is enough
        $bestException = null;

        foreach ($errors as $error) {
            $code = (int) $error['code'];

            if ($response->getStatusCode() !== $code) {
                continue;
            }

            if (isset($error['phrase']) && ! ($error['phrase'] === $response->getReasonPhrase())) {
                continue;
            }

            $bestException = $error['class'];

            // If there is an exact match of phrase + code, then we cannot find a more specialized exception in
            // the array, so we can break early instead of iterating the remaining ones
            if (isset($error['phrase'])) {
                break;
            }
        }

        if (null !== $bestException) {
            throw new $bestException($response->getReasonPhrase(), $command, null, $request, $response);
        }

        // If we reach here, no exception could be match from descriptor, and Guzzle exception will propagate if
        // option "http_errors" is set to true, which is the default setting.
    }
}
