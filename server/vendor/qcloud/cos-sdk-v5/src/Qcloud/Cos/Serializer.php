<?php
namespace Qcloud\Cos;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\SchemaValidator;
use GuzzleHttp\Command\Guzzle\DescriptionInterface;
use GuzzleHttp\Command\Guzzle\Serializer as DefaultSerializer;
use Psr\Http\Message\RequestInterface;
/**
 * Override Request serializer to modify authentication mechanism
 */
class Serializer extends DefaultSerializer
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        DescriptionInterface $description,
        array $requestLocations = []
    ) {
        // Override Guzzle's body location as it isn't raw binary data
        $requestLocations['body'] = new Request\BodyLocation;
        parent::__construct($description, $requestLocations);
    }
    /**
     * Authorization header is Loco's preferred authorization method.
     * Add Authorization header to request if API key is set, unless query is explicitly configured as auth method.
     * Unset key from command to avoid sending it as a query param.
     *
     * @override
     *
     * @param CommandInterface $command
     * @param RequestInterface $request
     *
     * @return RequestInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function prepareRequest(
        CommandInterface $command,
        RequestInterface $request
    ) {
		/*
        if ($command->offsetExists('key') === true) {
            $mode = empty($command->offsetGet('auth')) === false
                    ? $command->offsetGet('auth')
                    : 'loco';
            if ($mode !== 'query') {
                // else use Authorization header of various types
                if ($mode === 'loco') {
                    $value = 'Loco '.$command->offsetGet('key');
                    $request = $request->withHeader('Authorization', $value);
                } elseif ($mode === 'basic') {
                    $value = 'Basic '.base64_encode($command->offsetGet('key').':');
                    $request = $request->withHeader('Authorization', $value);
                } else {
                    throw new \InvalidArgumentException("Invalid auth type: {$mode}");
                }
                // avoid request sending key parameter in query string
                $command->offsetUnset('key');
            }
        }
        // Remap legacy parameters to common `data` binding on request body
        static $remap = [
            'import' => ['src'=>'data'],
            'translate' => ['translation'=>'data'],
        ];
        $name = $command->getName();
        if (isset($remap[$name])) {
            foreach ($remap[$name] as $old => $new) {
                if ($command->offsetExists($old)) {
                    $command->offsetSet($new, $command->offsetGet($old));
                    $command->offsetUnset($old);
                }
            }
        }
		*/
        return parent::prepareRequest($command, $request);
    }
}
