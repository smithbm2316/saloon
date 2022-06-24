<?php

namespace Sammyjo20\Saloon\Http\Responses;

use Exception;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Psr\Http\Message\StreamInterface;
use Sammyjo20\Saloon\Exceptions\SaloonRequestException;
use Sammyjo20\Saloon\Http\PendingSaloonRequest;
use Sammyjo20\Saloon\Http\SaloonRequest;
use SimpleXMLElement;

class SaloonResponse
{
    use Macroable;

    /**
     * The underlying PSR response.
     *
     * @var Response
     */
    protected Response $response;

    /**
     * The decoded JSON response.
     *
     * @var array
     */
    protected $decodedJson;

    /**
     * The decoded XML response.
     *
     * @var string
     */
    protected $decodedXml;

    /**
     * The request options we attached to the request.
     *
     * @var PendingSaloonRequest
     */
    protected PendingSaloonRequest $pendingSaloonRequest;

    /**
     * The original request exception
     *
     * @var Exception|null
     */
    protected ?Exception $requestException = null;

    /**
     * Determines if the response has been cached
     *
     * @var bool
     */
    private bool $isCached = false;

    /**
     * Determines if the response has been mocked.
     *
     * @var bool
     */
    private bool $isMocked = false;

    /**
     * Create a new response instance.
     *
     * @param PendingSaloonRequest $pendingSaloonRequest
     * @param Response $response
     * @param Exception|null $requestException
     */
    public function __construct(PendingSaloonRequest $pendingSaloonRequest, Response $response, Exception $requestException = null)
    {
        $this->pendingSaloonRequest = $pendingSaloonRequest;
        $this->response = $response;
        $this->requestException = $requestException;
    }

    /**
     * @return PendingSaloonRequest
     */
    public function getPendingSaloonRequest(): PendingSaloonRequest
    {
        return $this->pendingSaloonRequest;
    }

    /**
     * Get the original request
     *
     * @return SaloonRequest
     */
    public function getOriginalRequest(): SaloonRequest
    {
        return $this->pendingSaloonRequest->getRequest();
    }

    /**
     * Get the body of the response as string.
     *
     * @return string
     */
    public function body()
    {
        return (string)$this->response->getBody();
    }

    /**
     * Get the body as a stream. Don't forget to close the stream after using ->close().
     *
     * @return StreamInterface
     */
    public function stream(): StreamInterface
    {
        return $this->response->getBody();
    }

    /**
     * Get the JSON decoded body of the response as an array or scalar value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function json($key = null, $default = null)
    {
        if (! $this->decodedJson) {
            $this->decodedJson = json_decode($this->body(), true);
        }

        if (is_null($key)) {
            return $this->decodedJson;
        }

        return Arr::get($this->decodedJson, $key, $default);
    }

    /**
     * Get the JSON decoded body of the response as an object.
     *
     * @return object
     */
    public function object()
    {
        return json_decode($this->body(), false);
    }

    /**
     * Convert the XML response into a SimpleXMLElement.
     *
     * @param ...$arguments
     * @return SimpleXMLElement|bool
     */
    public function xml(...$arguments): SimpleXMLElement|bool
    {
        if (! $this->decodedXml) {
            $this->decodedXml = $this->body();
        }

        return simplexml_load_string($this->decodedXml, ...$arguments);
    }

    /**
     * Get the JSON decoded body of the response as a collection.
     *
     * @param $key
     * @return Collection
     */
    public function collect($key = null): Collection
    {
        return Collection::make($this->json($key));
    }

    /**
     * Cast the response to a DTO.
     *
     * @return object|null
     */
    public function dto(): mixed
    {
        if ($this->failed()) {
            return null;
        }

        return $this->getOriginalRequest()->createDtoFromResponse($this);
    }

    /**
     * Get a header from the response.
     *
     * @param string $header
     * @return string
     */
    public function header(string $header): string
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * Get the headers from the response.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Determine if the response code was "OK".
     *
     * @return bool
     */
    public function ok()
    {
        return $this->status() === 200;
    }

    /**
     * Determine if the response was a redirect.
     *
     * @return bool
     */
    public function redirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Determine if the response indicates a client or server error occurred.
     *
     * @return bool
     */
    public function failed()
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Determine if the response indicates a client error occurred.
     *
     * @return bool
     */
    public function clientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Determine if the response indicates a server error occurred.
     *
     * @return bool
     */
    public function serverError()
    {
        return $this->status() >= 500;
    }

    /**
     * Execute the given callback if there was a server or client error.
     *
     * @param callable $callback
     * @return $this
     */
    public function onError(callable $callback): self
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Close the stream and any underlying resources.
     *
     * @return $this
     */
    public function close(): self
    {
        $this->response->getBody()->close();

        return $this;
    }

    /**
     * Get the underlying PSR response for the response.
     *
     * @return Response
     */
    public function toPsrResponse(): Response
    {
        return $this->response;
    }

    /**
     * Create an exception if a server or client error occurred.
     *
     * @return Exception|void
     */
    public function toException()
    {
        if ($this->successful()) {
            return;
        }

        $body = $this->response?->getBody()?->getContents();

        return $this->createException($body);
    }

    /**
     * Create the request exception
     *
     * @param string $body
     * @return Exception
     */
    protected function createException(string $body): Exception
    {
        return new SaloonRequestException($this, $body, 0, $this->getRequestException());
    }

    /**
     * Throw an exception if a server or client error occurred.
     *
     * @return $this
     * @throws SaloonRequestException
     */
    public function throw()
    {
        if ($this->failed()) {
            throw $this->toException();
        }

        return $this;
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }

    /**
     * Set if the response is cached. Should only be used internally.
     *
     * @param bool $cached
     * @return $this
     */
    public function setCached(bool $cached): self
    {
        $this->isCached = $cached;

        return $this;
    }

    /**
     * Set if the response is mocked. Should only be used internally.
     *
     * @param bool $mocked
     * @return $this
     */
    public function setMocked(bool $mocked): self
    {
        $this->isMocked = $mocked;

        return $this;
    }

    /**
     * Check if the response has been cached
     *
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->isCached;
    }

    /**
     * Check if the response has been mocked
     *
     * @return bool
     */
    public function isMocked(): bool
    {
        return $this->isMocked;
    }

    /**
     * Get the original request exception
     *
     * @return Exception|null
     */
    public function getRequestException(): ?Exception
    {
        return $this->requestException;
    }
}