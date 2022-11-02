<?php declare(strict_types=1);

use GuzzleHttp\Promise\PromiseInterface;
use Sammyjo20\Saloon\Contracts\SaloonResponse;
use Sammyjo20\Saloon\Http\MockResponse;
use Sammyjo20\Saloon\Clients\MockClient;
use Sammyjo20\Saloon\Exceptions\SaloonRequestException;
use Sammyjo20\Saloon\Tests\Fixtures\Responses\UserData;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\UserRequest;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\ErrorRequest;
use Sammyjo20\Saloon\Tests\Fixtures\Responses\UserResponse;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\UserRequestWithCustomResponse;

test('an asynchronous request can be made successfully', function () {
    $request = new UserRequest();
    $promise = $request->sendAsync();

    expect($promise)->toBeInstanceOf(PromiseInterface::class);

    $response = $promise->wait();

    expect($response)->toBeInstanceOf(SaloonResponse::class);

    $data = $response->json();

    expect($response->isMocked())->toBeFalse();
    expect($response->status())->toEqual(200);

    expect($data)->toEqual([
        'name' => 'Sammyjo20',
        'actual_name' => 'Sam',
        'twitter' => '@carre_sam',
    ]);
});

test('an asynchronous request can handle an exception properly', function () {
    $request = new ErrorRequest();
    $promise = $request->sendAsync();

    $this->expectException(SaloonRequestException::class);

    $promise->wait();
});

test('an asynchronous response will still be passed through response middleware', function () {
    $mockClient = new MockClient([
        MockResponse::make(200, ['name' => 'Sam'])
    ]);

    $request = new UserRequest();

    $request->middleware()->onResponse(function (SaloonResponse $response) {
        $response->setCached(true);
    });

    $promise = $request->sendAsync($mockClient);
    $response = $promise->wait();

    expect($response->isCached())->toBeTrue();
});

test('an asynchronous request will return a custom response', function () {
    $mockClient = new MockClient([
        MockResponse::make(200, ['foo' => 'bar'])
    ]);

    $request = new UserRequestWithCustomResponse();

    $promise = $request->sendAsync($mockClient);

    $response = $promise->wait();

    expect($response)->toBeInstanceOf(UserResponse::class);
    expect($response)->customCastMethod()->toBeInstanceOf(UserData::class);
    expect($response)->foo()->toBe('bar');
});
