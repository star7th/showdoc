<?php

namespace Qcloud\Cos\Tests;

use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\ServiceResponseException;
class COSTest extends \PHPUnit\Framework\TestCase
{
    const SYNC_TIME = 5;
    private $cosClient;
    private $bucket;
    private $region;
    protected function setUp(): void
    {
        $this->bucket = getenv('COS_BUCKET');
        $this->region = getenv('COS_REGION');
        $this->bucket2 = "tmp".$this->bucket;
        $this->cosClient = new Client(array('region' => $this->region,
            'credentials' => array(
                'secretId' => getenv('COS_KEY'),
                'secretKey' => getenv('COS_SECRET'))));
        try {
            $this->cosClient->createBucket(['Bucket' => $this->bucket]);
        } catch(\Exception $e) {
        }
    }

    protected function tearDown(): void {
    }

    function generateRandomString($length = 10) { 
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $randomString = ''; 
        for ($i = 0; $i < $length; $i++) { 
            $randomString .= $characters[rand(0, strlen($characters) - 1)]; 
        } 
        return $randomString; 
    }

    function generateRandomFile($size = 10, $filename = 'random-file') { 
        exec("dd if=/dev/urandom of=". $filename. " bs=1 count=". (string)$size);
    }
    
    /**********************************
     * TestBucket
     **********************************/
    
     /*
     * put bucket,bucket已经存在
     * BucketAlreadyOwnedByYou
     * 409
     */
    public function testCreateExistingBucket()
    {
        try {
            $this->cosClient->createBucket(['Bucket' => $this->bucket]);
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'BucketAlreadyOwnedByYou' && $e->getStatusCode() === 409);
        }
    }

    /*
     * put bucket, 创建所有region的bucket
     * 409
     */
    public function testValidRegionBucket()
    {
        $regionlist = array('cn-east','ap-shanghai',
        'cn-south','ap-guangzhou',
        'cn-north','ap-beijing-1',
        'cn-southwest','ap-chengdu',
        'sg','ap-singapore',
        'tj','ap-beijing-1',
        'bj','ap-beijing',
        'sh','ap-shanghai',
        'gz','ap-guangzhou',
        'cd','ap-chengdu',
        'sgp','ap-singapore');
        foreach ($regionlist as$region) {
            try {
                $this->cosClient = new Client(array('region' => $region,
                    'credentials' => array(
                        'appId' => getenv('COS_APPID'),
                        'secretId' => getenv('COS_KEY'),
                        'secretKey' => getenv('COS_SECRET'))));
                $this->cosClient->createBucket(['Bucket' => $this->bucket]);
                $this->assertTrue(True);
            } catch (ServiceResponseException $e) {
                $this->assertEquals([$e->getStatusCode()], [409]);
            }
        }
    }

    /*
     * put bucket, 不合法的region名
     * 409
     */
    public function testInvalidRegionBucket()
    {
        $regionlist = array('cn-east-2','ap-shanghai-3');
        foreach ($regionlist as$region) {
            try {
                $this->cosClient = new Client(array('region' => $region,
                    'credentials' => array(
                        'appId' => getenv('COS_APPID'),
                        'secretId' => getenv('COS_KEY'),
                        'secretKey' => getenv('COS_SECRET'))));
                $this->cosClient->createBucket(['Bucket' => $this->bucket]);
                $this->assertTrue(True);
            } catch (ServiceResponseException $e) {
                $this->assertFalse(TRUE);
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                $this->assertTrue(TRUE);
            }
        }
    }

    /*
     * get Service
     * 200
     */
    public function testGetService()
    {
        try {
            $this->cosClient->ListBuckets();
            $this->assertTrue(TRUE);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket,bucket名称非法
     * InvalidBucketName
     * 400
     */
    public function testCreateInvalidBucket()
    {
        try {
            $this->cosClient->createBucket(array('Bucket' => 'qwe_123' . $this->bucket));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertTrue($e->getExceptionCode() === 'InvalidBucketName' && $e->getStatusCode() === 400);
        }
    }

    /*
     * put bucket，设置bucket公公权限为private
     * 200
     */
    public function testCreatePrivateBucket()
    {
        try {
            TestHelper::nuke($this->bucket2);
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->createBucket(
                array(
                    'Bucket' => $this->bucket2,
                    'ACL'=>'private'
                ));
            sleep(COSTest::SYNC_TIME);
            TestHelper::nuke($this->bucket2);
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket，设置bucket公公权限为public-read
     * 200
     */
    public function testCreatePublicReadBucket()
    {
        try {
            TestHelper::nuke($this->bucket2);
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->createBucket(
                array(
                    'Bucket' => $this->bucket2,
                    'ACL'=>'public-read'
                )
            );
            sleep(COSTest::SYNC_TIME);
            TestHelper::nuke($this->bucket2);
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket，公共权限非法
     * InvalidArgument
     * 400
     */
    public function testCreateInvalidACLBucket()
    {
        try {
            TestHelper::nuke($this->bucket2);
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->createBucket(
                array(
                    'Bucket' => $this->bucket2,
                    'ACL'=>'public'
                )
            );
            sleep(COSTest::SYNC_TIME);
            TestHelper::nuke($this->bucket2);
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'InvalidArgument' && $e->getStatusCode() === 400);
        }
    }

    /*
     * put bucket acl，设置bucket公共权限为private
     * 200
     */
    public function testPutBucketAclPrivate()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' => $this->bucket,
                    'ACL'=>'private'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，设置bucket公共权限为public-read
     * 200
     */
    public function testPutBucketAclPublicRead()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' => $this->bucket,
                    'ACL'=>'public-read'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，公共权限非法
     * InvalidArgument
     * 400
     */
    public function testPutBucketAclInvalid()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' => $this->bucket,
                    'ACL'=>'public'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'InvalidArgument' && $e->getStatusCode() === 400);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限为grant-read
     * 200
     */
    public function testPutBucketAclReadToUser()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'GrantRead' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限为grant-write
     * 200
     */
    public function testPutBucketAclWriteToUser()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'GrantWrite' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限为grant-full-control
     * 200
     */
    public function testPutBucketAclFullToUser()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'GrantFullControl' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限，同时授权给多个账户
     * 200
     */
    public function testPutBucketAclToUsers()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'GrantFullControl' => 'id="qcs::cam::uin/2779643970:uin/2779643970",id="qcs::cam::uin/2779643970:uin/2779643970",id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限，授权给子账号
     * 200
     */
    public function testPutBucketAclToSubuser()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'GrantFullControl' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限，同时指定read、write和fullcontrol
     * 200
     */
    public function testPutBucketAclReadWriteFull()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'GrantRead' => 'id="qcs::cam::uin/123:uin/123"',
                    'GrantWrite' => 'id="qcs::cam::uin/2779643970:uin/2779643970"',
                    'GrantFullControl' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限，grant值非法
     * InvalidArgument
     * 400
     */
    public function testPutBucketAclInvalidGrant()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'GrantFullControl' => 'id="qcs::camuin/321023:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'InvalidArgument' && $e->getStatusCode() === 400);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限，通过body方式授权
     * 200
     */
    public function testPutBucketAclByBody()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' => $this->bucket,
                    'Grants' => array(
                        array(
                            'Grantee' => array(
                                'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                                'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                                'Type' => 'CanonicalUser',
                            ),
                            'Permission' => 'FULL_CONTROL',
                        ),
                    ),
                    'Owner' => array(
                        'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                        'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                    )
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，设置bucket账号权限，通过body方式授权给anyone
     * 200
     */
    public function testPutBucketAclByBodyToAnyone()
    {
        try {
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' => $this->bucket,
                    'Grants' => array(
                        array(
                            'Grantee' => array(
                                'DisplayName' => 'qcs::cam::anyone:anyone',
                                'ID' => 'qcs::cam::anyone:anyone',
                                'Type' => 'CanonicalUser',
                            ),
                            'Permission' => 'FULL_CONTROL',
                        ),
                    ),
                    'Owner' => array(
                        'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                        'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                    )
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket acl，bucket不存在
     * NoSuchBucket
     * 404
     */
    public function testPutBucketAclBucketNonexisted()
    {
        try {
            TestHelper::nuke($this->bucket2);
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->PutBucketAcl(
                array(
                    'Bucket' =>  $this->bucket2,
                    'GrantFullControl' => 'id="qcs::cam::uin/321023:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'NoSuchBucket' && $e->getStatusCode() === 404);
        }
    }

    /*
     * put bucket acl，覆盖设置
     * x200
     */
    public function testPutBucketAclCover()
    {
        try {
            $this->cosClient->PutBucketAcl(array(
                'Bucket' =>  $this->bucket,
                'GrantFullControl' => 'id="qcs::cam::uin/2779643970:uin/2779643970"',
                'GrantRead' => 'id="qcs::cam::uin/2779643970:uin/2779643970"',
                'GrantWrite' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'));
            $this->cosClient->PutBucketAcl(array(
                'Bucket' =>  $this->bucket,
                'GrantWrite' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 正常head bucket
     * 200
     */
    public function testHeadBucket()
    {
        try {
            $this->cosClient->HeadBucket(array(
                'Bucket' =>  $this->bucket));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * head bucket，bucket不存在
     * NoSuchBucket
     * 404
     */
    public function testHeadBucketNonexisted()
    {
        try {
            TestHelper::nuke($this->bucket2);
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->HeadBucket(array(
                'Bucket' =>  $this->bucket2));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'NoSuchBucket' && $e->getStatusCode() === 404);
        }
    }

    /*
     * get bucket,bucket为空
     * 200
     */
    public function testGetBucketEmpty()
    {
        try {
            $this->cosClient->ListObjects(array(
                'Bucket' =>  $this->bucket));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * get bucket, prefix为中文
     * 200
     */
    public function testGetBucketWithChinese()
    {
        try {
            $this->cosClient->ListObjects(array(
                'Bucket' =>  $this->bucket,
                'Prefix' => '中文',
                'Delimiter' => '/'));
            $this->assertTrue(TRUE);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * get bucket，bucket不存在
     * NoSuchBucket
     * 404
     */
    public function testGetBucketNonexisted()
    {
        try {
            TestHelper::nuke($this->bucket2);
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->ListObjects(
                array(
                    'Bucket' =>  $this->bucket2
                )
            );
            $this->assertTrue(False);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'NoSuchBucket' && $e->getStatusCode() === 404);
        }
    }


    /*
     * put bucket cors，cors规则包含多条
     * 200
     */
    public function testPutBucketCors()
    {
        try {
            $this->cosClient->putBucketCors(
                array(
                    'Bucket' => $this->bucket,
                    'CORSRules' => array(
                        array(
                            'ID' => '1234',
                            'AllowedHeaders' => array('*',),
                            'AllowedMethods' => array('PUT',),
                            'AllowedOrigins' => array('*',),
                            'ExposeHeaders' => array('*',),
                            'MaxAgeSeconds' => 1,
                        ),
                        array(
                            'ID' => '12345',
                            'AllowedHeaders' => array('*',),
                            'AllowedMethods' => array('GET',),
                            'AllowedOrigins' => array('*',),
                            'ExposeHeaders' => array('*',),
                            'MaxAgeSeconds' => 1,
                        ),
                    ),
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }


    /*
     * 正常get bucket cors
     * 200
     */
    public function testGetBucketCors()
    {
        try {
            $this->cosClient->putBucketCors(
                array(
                    'Bucket' => $this->bucket,
                    'CORSRules' => array(
                        array(
                            'ID' => '1234',
                            'AllowedHeaders' => array('*',),
                            'AllowedMethods' => array('PUT',),
                            'AllowedOrigins' => array('*',),
                            'ExposeHeaders' => array('*',),
                            'MaxAgeSeconds' => 1,
                        ),
                        array(
                            'ID' => '12345',
                            'AllowedHeaders' => array('*',),
                            'AllowedMethods' => array('GET',),
                            'AllowedOrigins' => array('*',),
                            'ExposeHeaders' => array('*',),
                            'MaxAgeSeconds' => 1,
                        ),
                    ),
                )
            );
            $this->cosClient->getBucketCors(
                array(
                    'Bucket' => $this->bucket
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * bucket未设置cors规则，发送get bucket cors
     * NoSuchCORSConfiguration
     * 404
     */
    public function testGetBucketCorsNull()
    {
        try {
            $this->cosClient->deleteBucketCors(
                array(
                    'Bucket' => $this->bucket
                )
            );
            $rt = $this->cosClient->getBucketCors(
                array(
                    'Bucket' => $this->bucket
                )
            );
            print_r($rt);
            $this->assertTrue(False);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'NoSuchCORSConfiguration' && $e->getStatusCode() === 404);
        }
    }

    /*
     * 正常get bucket lifecycle
     * 200
     */
    public function testGetBucketLifecycle()
    {
        try {
            $result = $this->cosClient->putBucketLifecycle(
                array(
                    'Bucket' => $this->bucket,
                    'Rules' => array(
                        array(
                            'Status' => 'Enabled',
                            'Filter' => array(
                                'Tag' => array(
                                    'Key' => 'datalevel',
                                    'Value' => 'backup'
                                )
                            ),
                            'Transitions' => array(
                                array(
                                    # 30天后转换为Standard_IA
                                    'Days' => 30,
                                    'StorageClass' => 'Standard_IA'),
                                array(
                                    # 365天后转换为Archive
                                    'Days' => 365,
                                    'StorageClass' => 'Archive')
                            ),
                            'Expiration' => array(
                                # 3650天后过期删除
                                'Days' => 3650,
                            )
                        )
                    )
                )
            );
            $result = $this->cosClient->getBucketLifecycle(array(
                'Bucket' => $this->bucket,
            ));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 正常delete bucket lifecycle
     * 200
     */
    public function testDeleteBucketLifecycle()
    {
        try {
            $result = $this->cosClient->putBucketLifecycle(
                array(
                    'Bucket' => $this->bucket,
                    'Rules' => array(
                        array(
                            'Status' => 'Enabled',
                            'Filter' => array(
                                'Tag' => array(
                                    'Key' => 'datalevel',
                                    'Value' => 'backup'
                                )
                            ),
                            'Transitions' => array(
                                array(
                                    # 30天后转换为Standard_IA
                                    'Days' => 30,
                                    'StorageClass' => 'Standard_IA'),
                                array(
                                    # 365天后转换为Archive
                                    'Days' => 365,
                                    'StorageClass' => 'Archive')
                            ),
                            'Expiration' => array(
                                # 3650天后过期删除
                                'Days' => 3650,
                            )
                        )
                    )
                )
            );
            $result = $this->cosClient->deleteBucketLifecycle(array(
                // Bucket is required
                'Bucket' => $this->bucket,
            ));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket lifecycle，请求body中不指定filter
     * 200
     */
    public function testPutBucketLifecycleNonFilter()
    {
        try {
            $result = $this->cosClient->putBucketLifecycle(
                array(
                    'Bucket' => $this->bucket,
                    'Rules' => array(
                        array(
                            'Expiration' => array(
                                'Days' => 1000,
                            ),
                            'ID' => 'id1',
                            'Status' => 'Enabled',
                            'Transitions' => array(
                                array(
                                    'Days' => 100,
                                    'StorageClass' => 'Standard_IA'),
                            ),
                        ),
                    )
                )
            );
            $result = $this->cosClient->deleteBucketLifecycle(array(
                // Bucket is required
                'Bucket' => $this->bucket,
            ));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(True);
        }
    }

    /*
     * put bucket,bucket名称带有-
     * 200
     */
    public function testPutBucket2()
    {
        try {
            try{
                $this->cosClient->deleteBucket(array('Bucket' => '12345-'.$this->bucket));
            } catch (\Exception $e) {
            }
            $this->cosClient->createBucket(array('Bucket' => '12345-'.$this->bucket));
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->deleteBucket(array('Bucket' => '12345-'.$this->bucket));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put bucket,bucket名称带有两个-
     * 200
     */
    public function testPutBucket3()
    {
        try {
            $this->cosClient->createBucket(array('Bucket' => '12-333-4445' . $this->bucket));
            $this->cosClient->deleteBucket(array('Bucket' => '12-333-4445' . $this->bucket));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 正常get bucket location
     * 200
     */
        public function testGetBucketLocation()
    {
        try {
            $this->cosClient->getBucketLocation(array('Bucket' => $this->bucket));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * bucket不存在，发送get bucket location请求
     * NoSuchBucket
     * 404
     */
    public function testGetBucketLocationNonExisted()
    {
        try {
            TestHelper::nuke($this->bucket2);
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->getBucketLocation(array('Bucket' => $this->bucket2));
            $this->assertTrue(False);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'NoSuchBucket' && $e->getStatusCode() === 404);
        }
    }

    /**********************************
     * TestObject
     **********************************/

    /*
     * put object, 从本地上传文件
     * 200
     */
    public function testPutObjectLocalObject() {
        try {
            $key = '你好.txt';
            $body = $this->generateRandomString(1024+1023);
            $md5 = base64_encode(md5($body, true));
            $local_test_key = "local_test_file";
            $f = fopen($local_test_key, "wb");
            fwrite($f, $body);
            fclose($f);
            $this->cosClient->putObject(['Bucket' => $this->bucket,
                                         'Key' => $key,
                                         'Body' => fopen($local_test_key, "rb")]);
            $rt = $this->cosClient->getObject(['Bucket'=>$this->bucket, 'Key'=>$key]);
            $download_md5 = base64_encode(md5($rt['Body'], true));
            $this->assertEquals($md5, $download_md5);
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * upload, 从本地上传
     * 200
     */
    public function testUploadLocalObject() {
        try {
            $key = '你好.txt';
            $body = $this->generateRandomString(1024+1023);
            $md5 = base64_encode(md5($body, true));
            $local_test_key = "local_test_file";
            $f = fopen($local_test_key, "wb");
            fwrite($f, $body);
            fclose($f);
            $this->cosClient->upload($bucket=$this->bucket,
                                     $key=$key,
                                     $body=fopen($local_test_key, "rb"),
                                     $options=['PartSize'=>1024 * 1024 + 1]);
            $rt = $this->cosClient->getObject(['Bucket'=>$this->bucket, 'Key'=>$key]);
            $download_md5 = base64_encode(md5($rt['Body'], true));
            $this->assertEquals($md5, $download_md5);
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object,请求头部携带服务端加密参数
     * 200
     */
    public function testPutObjectEncryption()
    {
        try {
            $this->cosClient->putObject(
                array(
                    'Bucket' => $this->bucket,
                    'Key' => '11//32//43',
                    'Body' => 'Hello World!',
                    'ServerSideEncryption' => 'AES256'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 上传文件Bucket不存在
     * NoSuchBucket
     * 404
     */
    public function testPutObjectIntoNonexistedBucket() {
        try {
            TestHelper::nuke($this->bucket2);
            sleep(COSTest::SYNC_TIME);
            $this->cosClient->putObject(
                array(
                    'Bucket' => $this->bucket2, 'Key' => 'hello.txt', 'Body' => 'Hello World'
                )
            );
            $this->assertTrue(False);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'NoSuchBucket');
            $this->assertTrue($e->getStatusCode() === 404);
        }
    }


    /*
     * 上传小文件
     * 200
     */
    public function testUploadSmallObject() {
        try {
            $this->cosClient->upload($this->bucket, '你好.txt', 'Hello World');
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 上传空文件
     * 200
     */
    public function testPutObjectEmpty() {
        try {
            $this->cosClient->upload($this->bucket, '你好.txt', '');
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 上传已存在的文件
     * 200
     */
    public function testPutObjectExisted() {
        try {
            $this->cosClient->upload($this->bucket, '你好.txt', '1234124');
            $this->cosClient->upload($this->bucket, '你好.txt', '请二位qwe');
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object，请求头部携带自定义头部x-cos-meta-
     * 200
     */
    public function testPutObjectMeta() {
        try {
            $key = '你好.txt';
            $meta = array(
                'test' => str_repeat('a', 1 * 1024),
                'test-meta' => '中文qwe-23ds-ad-xcz.asd.*qweqw'
            );
            $this->cosClient->putObject(array(
                'Bucket' => $this->bucket,
                'Key' => '你好.txt',
                'Body' => '1234124',
                'Metadata' => $meta
                     
            ));
            $rt = $this->cosClient->headObject(['Bucket'=>$this->bucket, 'Key'=>$key]);
            $this->assertEquals($rt['Metadata'], $meta);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * upload large object，请求头部携带自定义头部x-cos-meta-
     * 200
     */
    public function testUploadLargeObjectMeta() {
        try {
            $key = '你好.txt';
            $meta = array(
                'test' => str_repeat('a', 1 * 1024),
                'test-meta' => 'qwe-23ds-ad-xcz.asd.*qweqw'
            );
            $body = $this->generateRandomString(2*1024*1024+1023);
            $this->cosClient->upload($bucket=$this->bucket,
                                     $key=$key,
                                     $body=$body,
                                     $options=['PartSize'=>1024 * 1024 + 1, 'Metadata'=>$meta]);
            $rt = $this->cosClient->headObject(['Bucket'=>$this->bucket, 'Key'=>$key]);
            $this->assertEquals($rt['Metadata'], $meta);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object，请求头部携带自定义头部x-cos-meta-
     * KeyTooLong
     * 400
     */
    public function testPutObjectMeta2K() {
        try {
            $this->cosClient->putObject(array(
                'Bucket' => $this->bucket,
                'Key' => '你好.txt',
                'Body' => '1234124',
                'Metadata' => array(
                    'lew' => str_repeat('a', 3 * 1024),
                )));
            $this->assertTrue(False);
        } catch (ServiceResponseException $e) {
            $this->assertEquals(
                [$e->getStatusCode(), $e->getExceptionCode()],
                [400, 'KeyTooLong']
            );
            print $e;
        }
    }

    /*
     * 上传复杂文件名的文件
     * 200
     */
    public function testUploadComplexObject() {
        try {
            $key = '→↓←→↖↗↙↘! \"#$%&\'()*+,-./0123456789:;<=>@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
            $this->cosClient->upload($this->bucket, $key, 'Hello World');
            $this->cosClient->headObject(array(
                'Bucket' => $this->bucket,
                'Key' => $key
            ));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 上传大文件
     * 200
     */
    public function testUploadLargeObject() {
        try {
            $key = '你好.txt';
            $body = $this->generateRandomString(2*1024*1024+1023);
            $md5 = base64_encode(md5($body, true));
            $this->cosClient->upload($bucket=$this->bucket,
                                     $key=$key,
                                     $body=$body,
                                     $options=['PartSize'=>1024 * 1024 + 1]);
            $rt = $this->cosClient->getObject(['Bucket'=>$this->bucket, 'Key'=>$key]);
            $download_md5 = base64_encode(md5($rt['Body'], true));
            $this->assertEquals($md5, $download_md5);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 断点重传
     * 200
     */
    public function testResumeUpload() {
        try {
            $key = '你好.txt';
            $body = $this->generateRandomString(3*1024*1024+1023);
            $partSize = 1024 * 1024 + 1;
            $md5 = base64_encode(md5($body, true));
            $rt = $this->cosClient->CreateMultipartUpload(['Bucket' => $this->bucket,
                                                           'Key' => $key]);
            $uploadId = $rt['UploadId'];
            $this->cosClient->uploadPart(['Bucket' => $this->bucket,
                                          'Key' => $key,
                                          'Body' => substr($body, 0, $partSize),
                                          'UploadId' => $uploadId,
                                          'PartNumber' => 1]);
            $rt = $this->cosClient->ListParts(['Bucket' => $this->bucket,
                                          'Key' => $key,
                                          'UploadId' => $uploadId]);
            $this->assertEquals(count($rt['Parts']), 1);
            $this->cosClient->resumeUpload($bucket=$this->bucket,
                                           $key=$key,
                                           $body=$body,
                                           $uploadId=$uploadId,
                                           $options=['PartSize'=>$partSize]);
            $rt = $this->cosClient->getObject(['Bucket'=>$this->bucket, 'Key'=>$key]);
            $download_md5 = base64_encode(md5($rt['Body'], true));
            $this->assertEquals($md5, $download_md5);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 下载文件
     * 200
     */
    public function testGetObject() {
        try {
            $this->cosClient->upload($this->bucket, '你好.txt', 'Hello World');
            $this->cosClient->getObject(array(
                'Bucket' => $this->bucket,
                'Key' => '你好.txt',));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * range下载大文件
     * 200
     */
    public function testDownloadLargeObject() {
        try {
            $key = '你好.txt';
            $local_path = "test_tmp_file";
            $body = $this->generateRandomString(2*1024*1024+1023);
            $md5 = base64_encode(md5($body, true));
            $this->cosClient->upload($bucket=$this->bucket,
                                     $key=$key,
                                     $body=$body,
                                     $options=['PartSize'=>1024 * 1024 + 1]);
            $rt = $this->cosClient->download($bucket=$this->bucket,
                                            $key=$key,
                                            $saveAs=$local_path,
                                            $options=['PartSize'=>1024 * 1024 + 1]);
            $body = file_get_contents($local_path);
            $download_md5 = base64_encode(md5($body, true));
            $this->assertEquals($md5, $download_md5);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }
    /*
     * get object，object名称包含特殊字符
     * 200
     */
    public function testGetObjectSpecialName() {
        try {
            $this->cosClient->upload($this->bucket, '你好<>!@#^%^&*&(&^!@#@!.txt', 'Hello World');
            $this->cosClient->getObject(array(
                'Bucket' => $this->bucket,
                'Key' => '你好<>!@#^%^&*&(&^!@#@!.txt',));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * get object，请求头部带if-match，参数值为true
     * 200
     */
    public function testGetObjectIfMatchTrue() {
        try {
            $this->cosClient->upload($this->bucket, '你好.txt', 'Hello World');
            $this->cosClient->getObject(array(
                'Bucket' => $this->bucket,
                'Key' => '你好.txt',
                'IfMatch' => '"b10a8db164e0754105b7a99be72e3fe5"'));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }


    /*
     * get object，请求头部带if-match，参数值为false
     * PreconditionFailed
     * 412
     */
    public function testGetObjectIfMatchFalse() {
        try {
            $this->cosClient->upload($this->bucket, '你好.txt', 'Hello World');
            $this->cosClient->getObject(array(
                'Bucket' => $this->bucket,
                'Key' => '你好.txt',
                'IfMatch' => '""'));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            $this->assertEquals(
                [$e->getStatusCode(), $e->getExceptionCode()],
                [412, 'PreconditionFailed']
            );
            print $e;
        }
    }

    /*
     * get object，请求头部带if-none-match，参数值为true
     * 200
     */
    public function testGetObjectIfNoneMatchTrue() {
        try {
            $this->cosClient->upload($this->bucket, '你好.txt', 'Hello World');
            $rt = $this->cosClient->getObject(array(
                'Bucket' => $this->bucket,
                'Key' => '你好.txt',
                'IfNoneMatch' => '"b10a8db164e0754105b7a99be72e3fe5"'));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }


    /*
     * get object，请求头部带if-none-match，参数值为false
     * PreconditionFailed
     * 412
     */
    public function testGetObjectIfNoneMatchFalse() {
        try {
            $this->cosClient->upload($this->bucket, '你好.txt', 'Hello World');
            $this->cosClient->getObject(array(
                'Bucket' => $this->bucket,
                'Key' => '你好.txt',
                'IfNoneMatch' => '""'));
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 获取文件url
     * 200
     */
    public function testGetObjectUrl() {
        try{
            $this->cosClient->getObjectUrl($this->bucket, 'hello.txt', '+10 minutes');
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 复制小文件
     * 200
     */
    public function testCopySmallObject() {
        try{
            $this->cosClient->upload($this->bucket, '你好.txt', 'Hello World');
            $this->cosClient->copy($bucket=$this->bucket,
                                   $key='hi.txt', 
                                   $copySource = ['Bucket'=>$this->bucket,
                                                  'Region'=>$this->region,
                                                  'Key'=>'你好.txt']);
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 复制大文件
     * 200
     */
    public function testCopyLargeObject() {
        try{
            $src_key = '你好.txt';
            $dst_key = 'hi.txt';
            $body = $this->generateRandomString(2*1024*1024+333);
            $md5 = base64_encode(md5($body, true));
            $this->cosClient->upload($bucket=$this->bucket,
                                     $key=$src_key,
                                     $body=$body,
                                     $options=['PartSize'=>1024 * 1024 + 1]);
            $this->cosClient->copy($bucket=$this->bucket,
                                   $key=$dst_key, 
                                   $copySource = ['Bucket'=>$this->bucket,
                                                  'Region'=>$this->region,
                                                  'Key'=>$src_key],
                                   $options=['PartSize'=>1024 * 1024 + 1]);
            
            $rt = $this->cosClient->getObject(['Bucket'=>$this->bucket, 'Key'=>$dst_key]);
            $download_md5 = base64_encode(md5($rt['Body'], true));
            $this->assertEquals($md5, $download_md5);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * 设置objectacl
     * 200
     */
    public function testPutObjectACL() {
        try {
            $this->cosClient->upload($this->bucket, '11', 'hello.txt');
            $this->cosClient->PutObjectAcl(
                    array(
                    'Bucket' => $this->bucket,
                    'Key' => '11',
                    'Grants' => array(
                        array(
                            'Grantee' => array(
                                'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                                'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                                'Type' => 'CanonicalUser',
                            ),
                            'Permission' => 'FULL_CONTROL',
                        ),
                        // ... repeated
                    ),
                    'Owner' => array(
                        'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                        'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                    )
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }

    }


    /*
     * 获取objectacl
     * 200
     */
    public function testGetObjectACL()
    {
        try {
            $this->cosClient->upload($this->bucket, '11', 'hello.txt');
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' => $this->bucket,
                    'Key' => '11',
                    'Grants' => array(
                        array(
                            'Grantee' => array(
                                'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                                'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                                'Type' => 'CanonicalUser',
                            ),
                            'Permission' => 'FULL_CONTROL',
                        ),
                    ),
                    'Owner' => array(
                        'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                        'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                    )
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
        * put object acl，设置object公共权限为private
        * 200
        */
    public function testPutObjectAclPrivate()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' => $this->bucket,
                    'Key' => '你好.txt',
                    'ACL'=>'private'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object acl，设置object公共权限为public-read
     * 200
     */
    public function testPutObjectAclPublicRead()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' => $this->bucket,
                    'Key' => '你好.txt',
                    'ACL'=>'public-read'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object acl，公共权限非法
     * InvalidArgument
     * 400
     */
    public function testPutObjectAclInvalid()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' => $this->bucket,
                    'Key' => '你好.txt',
                    'ACL'=>'public'
                )
            );
            $this->assertTrue(False);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'InvalidArgument' && $e->getStatusCode() === 400);
        }
    }

    /*
     * put object acl，设置object账号权限为grant-read
     * 200
     */
    public function testPutObjectAclReadToUser()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'Key' => '你好.txt',
                    'GrantRead' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object acl，设置object账号权限为grant-full-control
     * 200
     */
    public function testPutObjectAclFullToUser()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'Key' => '你好.txt',
                    'GrantFullControl' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object acl，设置object账号权限，同时授权给多个账户
     * 200
     */
    public function testPutObjectAclToUsers()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'Key' => '你好.txt',
                    'GrantFullControl' => 'id="qcs::cam::uin/2779643970:uin/2779643970",id="qcs::cam::uin/2779643970:uin/2779643970",id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object acl，设置object账号权限，授权给子账号
     * 200
     */
    public function testPutObjectAclToSubuser()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'Key' => '你好.txt',
                    'GrantFullControl' => 'id="qcs::cam::uin/2779643970:uin/2779643970"'
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object acl，设置object账号权限，grant值非法
     * InvalidArgument
     * 400
     */
    public function testPutObjectAclInvalidGrant()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' =>  $this->bucket,
                    'Key' => '你好.txt',
                    'GrantFullControl' => 'id="qcs::camuin/321023:uin/2779643970"'
                )
            );
            $this->assertTrue(False);
        } catch (ServiceResponseException $e) {
            $this->assertTrue($e->getExceptionCode() === 'InvalidArgument' && $e->getStatusCode() === 400);
        }
    }

    /*
     * put object acl，设置object账号权限，通过body方式授权
     * 200
     */
    public function testPutObjectAclByBody()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->PutObjectAcl(
                array(
                    'Bucket' => $this->bucket,
                    'Key' => '你好.txt',
                    'Grants' => array(
                        array(
                            'Grantee' => array(
                                'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                                'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                                'Type' => 'CanonicalUser',
                            ),
                            'Permission' => 'FULL_CONTROL',
                        ),
                        // ... repeated
                    ),
                    'Owner' => array(
                        'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                        'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                    )
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
     * put object acl，设置object账号权限，通过body方式授权给anyone
     * 200
     */
    public function testPutObjectAclByBodyToAnyone()
    {
        try {
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => '你好.txt', 'Body' => '123'));
            $this->cosClient->putObjectAcl(
                array(
                    'Bucket' => $this->bucket,
                    'Key' => '你好.txt',
                    'Grants' => array(
                        array(
                            'Grantee' => array(
                                'DisplayName' => 'qcs::cam::anyone:anyone',
                                'ID' => 'qcs::cam::anyone:anyone',
                                'Type' => 'CanonicalUser',
                            ),
                            'Permission' => 'FULL_CONTROL',
                        ),
                        // ... repeated
                    ),
                    'Owner' => array(
                        'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                        'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                    )
                )
            );
            $this->assertTrue(True);
        } catch (ServiceResponseException $e) {
            print $e;
            $this->assertFalse(TRUE);
        }
    }

    /*
    * selectobject，select检索数据
    * 200
    */
    public function testSelectObjectContent()
    {
        $key = '你好.txt';
        try {
            $body = "appid,bucket,region
12500001,22weqwe,sh
12500002,we2qwe,sh
12500003,weq3we,sh
12500004,weqw4e,sh
3278522,azxc,gz
4343,ewqew,tj";
            $this->cosClient->putObject(array('Bucket' => $this->bucket,'Key' => $key, 'Body' => $body));
            $result = $this->cosClient->selectObjectContent(array(
                        'Bucket' => $this->bucket, //格式：BucketName-APPID
                        'Key' => $key,
                        'Expression' => 'Select * from COSObject s',
                        'ExpressionType' => 'SQL',
                        'InputSerialization' => array(
                            'CompressionType' => 'NONE',
                            'CSV' => array(
                                'FileHeaderInfo' => 'USE',
                                'RecordDelimiter' => '\n',
                                'FieldDelimiter' => ',',
                                'QuoteEscapeCharacter' => '"',
                                'Comments' => '#',
                                'AllowQuotedRecordDelimiter' => 'FALSE'
                                )   
                            ),  
                        'OutputSerialization' => array(
                            'CSV' => array(
                                'QuoteField' => 'ASNEEDED',
                                'RecordDelimiter' => '\n',
                                'FieldDelimiter' => ',',
                                'QuoteCharacter' => '"',
                                'QuoteEscapeCharacter' => '"' 
                                )   
                            ),  
                        'RequestProgress' => array(
                                'Enabled' => 'FALSE'
                                )   
                            )); 
            foreach ($result['Data'] as $data) {
            }
            $this->assertTrue(True);
        } catch (\Exception $e) {
            print ($e);
            $this->assertFalse(TRUE);
        }
    }

}
