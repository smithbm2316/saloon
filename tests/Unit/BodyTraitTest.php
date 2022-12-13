<?php

use Saloon\Contracts\Body\WithBody;
use Saloon\Enums\Method;
use Saloon\Exceptions\BodyException;
use Saloon\Helpers\Helpers;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Tests\Fixtures\Connectors\TestConnector;
use Saloon\Tests\Fixtures\Requests\UserRequest;
use Saloon\Traits\Body\ChecksForWithBody;
use Saloon\Traits\Body\HasBody;
use Saloon\Traits\Body\HasFormBody;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Body\HasMultipartBody;
use Saloon\Traits\Body\HasXmlBody;

test('each of the body traits has the ChecksForWithBody trait added', function (string $trait) {
    $uses = Helpers::classUsesRecursive($trait);

    expect($uses)->toHaveKey(ChecksForWithBody::class, ChecksForWithBody::class);
})->with([
    HasBody::class,
    HasFormBody::class,
    HasJsonBody::class,
    HasMultipartBody::class,
    HasXmlBody::class,
]);

test('when a body trait is added to a request without WithBody it will throw an exception', function () {
    $request = new class extends Request {
        use HasJsonBody;

        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '';
        }
    };

    $this->expectException(BodyException::class);
    $this->expectExceptionMessage('You have added a body trait without adding the `WithBody` interface to your request/connector.');

    TestConnector::make()->send($request);
});

test('when a body trait is added to a connector without WithBody it will throw an exception', function () {
    $connector = new class extends Connector {
        use HasJsonBody;

        public function resolveBaseUrl(): string
        {
            return '';
        }
    };

    $this->expectException(BodyException::class);
    $this->expectExceptionMessage('You have added a body trait without adding the `WithBody` interface to your request/connector.');

    $connector->send(new UserRequest);
});
