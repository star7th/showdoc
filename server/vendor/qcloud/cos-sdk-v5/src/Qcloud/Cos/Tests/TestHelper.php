<?php

namespace Qcloud\Cos\Tests;

use Qcloud\Cos\Client;

class TestHelper {

    public static function nuke($bucket) {
        try {
            $cosClient = new Client(array('region' => getenv('COS_REGION'),
                        'credentials'=> array(
                        'appId' => getenv('COS_APPID'),
                        'secretId'    => getenv('COS_KEY'),
                        'secretKey' => getenv('COS_SECRET'))));
            $result = $cosClient->listObjects(array('Bucket' => $bucket));
            if ($result->get('Contents')) {
                foreach ($result ->get('Contents') as $content) {
                    $cosClient->deleteObject(array('Bucket' => $bucket, 'Key' => $content['Key']));
                }
            }
            $cosClient->deleteBucket(array('Bucket' => $bucket));

            while(True){
                $result = $cosClient->ListMultipartUploads(
                    array('Bucket' => $bucket,
                        'Prefix' => ''));
                if (count($result['Uploads']) == 0){
                    break;
                }
                foreach ($result['Uploads'] as $upload) {
                    try {
                        $rt = $cosClient->AbortMultipartUpload(
                            array('Bucket' => $bucket,
                                'Key' => $upload['Key'],
                                'UploadId' => $upload['UploadId']));
                        print_r($rt);
                    } catch (\Exception $e) {
                        print_r($e);
                    }
                }
            }
        } catch (\Exception $e) {
            //echo "$e\n";
            // Ignore
        }
    }
}
