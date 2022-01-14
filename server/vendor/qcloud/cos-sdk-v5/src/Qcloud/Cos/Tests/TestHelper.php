<?php

namespace Qcloud\Cos\Tests;

use Qcloud\Cos\Client;

class TestHelper {

    public static function nuke($bucket) {
        try {
            $cosClient = new Client(array('region' => getenv('COS_REGION'),
                        'credentials'=> array(
                        'secretId'    => getenv('COS_KEY'),
                        'secretKey' => getenv('COS_SECRET'))));
            $result = $cosClient->listObjects(array('Bucket' => $bucket));
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $content) {
                    $cosClient->deleteObject(array('Bucket' => $bucket, 'Key' => $content['Key']));
                }
            }

            while(True){
                $result = $cosClient->ListMultipartUploads(
                    array('Bucket' => $bucket));
                if ($result['Uploads'] == []) {
                    break;
                }
                foreach ($result['Uploads'] as $upload) {
                    try {
                        $rt = $cosClient->AbortMultipartUpload(
                            array('Bucket' => $bucket,
                                'Key' => $upload['Key'],
                                'UploadId' => $upload['UploadId']));
                    } catch (\Exception $e) {
                        print_r($e);
                    }
                }
            }        
            $cosClient->deleteBucket(array('Bucket' => $bucket));
        } catch (\Exception $e) {
            // echo "$e\n";
        }
    }
}
