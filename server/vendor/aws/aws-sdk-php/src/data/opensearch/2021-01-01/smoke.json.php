<?php
// This file was auto-generated from sdk-root/src/data/opensearch/2021-01-01/smoke.json
return [ 'version' => 1, 'defaultRegion' => 'us-west-2', 'testCases' => [ [ 'operationName' => 'ListDomainNames', 'input' => [], 'errorExpectedFromService' => false, ], [ 'operationName' => 'DescribeDomain', 'input' => [ 'DomainName' => 'not-a-domain', ], 'errorExpectedFromService' => true, ], ],];
