<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\ValueObject\CORSRule;

class GetBucketCorsOutput extends Result
{
    /**
     * A set of origins and methods (cross-origin access that you want to allow). You can add up to 100 rules to the
     * configuration.
     */
    private $corsRules;

    /**
     * @return CORSRule[]
     */
    public function getCorsRules(): array
    {
        $this->initialize();

        return $this->corsRules;
    }

    protected function populateResult(Response $response): void
    {
        $data = new \SimpleXMLElement($response->getContent());
        $this->corsRules = !$data->CORSRule ? [] : $this->populateResultCORSRules($data->CORSRule);
    }

    /**
     * @return string[]
     */
    private function populateResultAllowedHeaders(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $a = ($v = $item) ? (string) $v : null;
            if (null !== $a) {
                $items[] = $a;
            }
        }

        return $items;
    }

    /**
     * @return string[]
     */
    private function populateResultAllowedMethods(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $a = ($v = $item) ? (string) $v : null;
            if (null !== $a) {
                $items[] = $a;
            }
        }

        return $items;
    }

    /**
     * @return string[]
     */
    private function populateResultAllowedOrigins(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $a = ($v = $item) ? (string) $v : null;
            if (null !== $a) {
                $items[] = $a;
            }
        }

        return $items;
    }

    /**
     * @return CORSRule[]
     */
    private function populateResultCORSRules(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $items[] = new CORSRule([
                'ID' => ($v = $item->ID) ? (string) $v : null,
                'AllowedHeaders' => !$item->AllowedHeader ? null : $this->populateResultAllowedHeaders($item->AllowedHeader),
                'AllowedMethods' => $this->populateResultAllowedMethods($item->AllowedMethod),
                'AllowedOrigins' => $this->populateResultAllowedOrigins($item->AllowedOrigin),
                'ExposeHeaders' => !$item->ExposeHeader ? null : $this->populateResultExposeHeaders($item->ExposeHeader),
                'MaxAgeSeconds' => ($v = $item->MaxAgeSeconds) ? (int) (string) $v : null,
            ]);
        }

        return $items;
    }

    /**
     * @return string[]
     */
    private function populateResultExposeHeaders(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $a = ($v = $item) ? (string) $v : null;
            if (null !== $a) {
                $items[] = $a;
            }
        }

        return $items;
    }
}
