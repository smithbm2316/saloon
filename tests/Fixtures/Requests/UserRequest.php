<?php

namespace Sammyjo20\Saloon\Tests\Fixtures\Requests;

use Sammyjo20\Saloon\Http\PendingSaloonRequest;
use Sammyjo20\Saloon\Http\SaloonRequest;
use Sammyjo20\Saloon\Interfaces\Data\SendsJsonBody;
use Sammyjo20\Saloon\Tests\Fixtures\Connectors\TestConnector;
use Sammyjo20\Saloon\Traits\Plugins\AlwaysThrowsOnErrors;

class UserRequest extends SaloonRequest implements SendsJsonBody
{
    /**
     * Define the method that the request will use.
     *
     * @var string
     */
    protected string $method = 'GET';

    /**
     * The connector.
     *
     * @var string
     */
    protected string $connector = TestConnector::class;

    /**
     * @param int|null $userId
     * @param int|null $groupId
     */
    public function __construct(public ?int $userId = null, public ?int $groupId = null)
    {
        $this->middlewarePipeline()
            ->addRequestPipe(function (PendingSaloonRequest $request) {
                $request->headers()->add('X-Name', 'Sam');
            });
    }

    /**
     * @return string
     */
    protected function defineEndpoint(): string
    {
        return '/user';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultConfig(): array
    {
        return [];
    }

    protected function defaultQueryParameters(): array
    {
        return [];
    }

    protected function defaultData(): mixed
    {
        return [
            'foo' => 'bar',
        ];
    }
}
