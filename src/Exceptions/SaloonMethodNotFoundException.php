<?php declare(strict_types=1);

namespace Sammyjo20\Saloon\Exceptions;

use Sammyjo20\Saloon\Http\SaloonConnector;

class SaloonMethodNotFoundException extends SaloonException
{
    /**
     * Exception
     *
     * @param string $method
     * @param SaloonConnector $connector
     */
    public function __construct(string $method, SaloonConnector $connector)
    {
        parent::__construct(sprintf('Unable to find the "%s" method on the request class or the "%s" connector.', $method, get_class($connector)));
    }
}
