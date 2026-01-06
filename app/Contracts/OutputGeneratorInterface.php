<?php

namespace App\Contracts;

/**
 * Interface for the Strategy Pattern in Output Generation.
 * All generators (XML, CSV, TXT) must implement this method.
 */
interface OutputGeneratorInterface
{
    /**
     * Generate the output content based on the provided RPS list and provider info.
     *
     * @param array $rpsList List of RpsData DTOs
     * @param string $loteId Unique ID for the batch
     * @param array $providerInfo Information about the service provider
     * @param array $options Extra options (e.g., starting_number, state, etc.)
     * @return string The generated content (XML string, CSV string, etc.)
     */
    public function generateBatch(array $rpsList, string $loteId = '1', array $providerInfo = [], array $options = []): string;

    /**
     * Get the file extension for this generator (e.g., 'xml', 'csv', 'txt').
     *
     * @return string
     */
    public function getExtension(): string;
}
