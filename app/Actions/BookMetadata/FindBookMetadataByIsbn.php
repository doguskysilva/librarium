<?php

namespace App\Actions\BookMetadata;

use App\Contracts\BookMetadataProvider;
use App\Data\BookMetadata;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class FindBookMetadataByIsbn
{
    /**
     * @param  array<int, BookMetadataProvider>  $providers
     */
    public function __construct(
        private readonly array $providers,
    ) {}

    /**
     * @return array<string, array{metadata: ?BookMetadata, error: ?string}>
     */
    public function handle(string $isbn): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            try {
                $results[$provider->key()] = [
                    'metadata' => $provider->findByIsbn($isbn),
                    'error' => null,
                ];
            } catch (ConnectionException) {
                $results[$provider->key()] = [
                    'metadata' => null,
                    'error' => 'Provider could not be reached.',
                ];
            } catch (RequestException $exception) {
                $results[$provider->key()] = [
                    'metadata' => null,
                    'error' => 'Provider returned HTTP '.$exception->response->status().'.',
                ];
            }
        }

        return $results;
    }
}
