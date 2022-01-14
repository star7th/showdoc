<?php

namespace Qcloud\Cos;

use Psr\Http\Message\RequestInterface;

class Signature {
    private $accessKey;
    // string: access key.
    private $secretKey;
    // string: secret key.

    public function __construct( $accessKey, $secretKey, $token = null ) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->token = $token;
        $this->signHeader = [
            'host',
            'content-type',
            'content-md5',
            'content-disposition',
            'content-encoding',
            'content-length',
            'transfer-encoding',
            'range',
        ];
        date_default_timezone_set( 'PRC' );
    }

    public function __destruct() {
    }

    public function needCheckHeader( $header ) {
        if ( startWith( $header, 'x-cos-' ) ) {
            return true;
        }
        if ( in_array( $header, $this->signHeader ) ) {
            return true;
        }
        return false;
    }

    public function signRequest( RequestInterface $request ) {
        $authorization = $this->createAuthorization( $request );
        return $request->withHeader( 'Authorization', $authorization );
    }

    public function createAuthorization( RequestInterface $request, $expires = '+30 minutes' ) {
        if ( is_null( $expires ) ) {
            $expires = '+30 minutes';
        }
        $signTime = ( string )( time() - 60 ) . ';' . ( string )( strtotime( $expires ) );
        $urlParamListArray = [];
        foreach ( explode( '&', $request->getUri()->getQuery() ) as $query ) {
            if (!empty($query)) {
                $tmpquery = explode( '=', $query );
                $key = strtolower( $tmpquery[0] );
                if (count($tmpquery) >= 2) {
                    $value = $tmpquery[1];
                } else {
                    $value = "";
                }
                $urlParamListArray[$key] = $key. '='. $value;
            }
        }
        ksort($urlParamListArray);
        $urlParamList = join(';', array_keys($urlParamListArray));
        $httpParameters = join('&', array_values($urlParamListArray));

        $headerListArray = [];
        foreach ( $request->getHeaders() as $key => $value ) {
            $key = strtolower( urlencode( $key ) );
            $value = rawurlencode( $value[0] );
            if ( $this->needCheckHeader( $key ) ) {
                $headerListArray[$key] = $key. '='. $value;
            }
        }
        ksort($headerListArray);
        $headerList = join(';', array_keys($headerListArray));
        $httpHeaders = join('&', array_values($headerListArray));
        $httpString = strtolower( $request->getMethod() ) . "\n" . urldecode( $request->getUri()->getPath() ) . "\n" . $httpParameters.
        "\n". $httpHeaders. "\n";
        $sha1edHttpString = sha1( $httpString );
        $stringToSign = "sha1\n$signTime\n$sha1edHttpString\n";
        $signKey = hash_hmac( 'sha1', $signTime, $this->secretKey );
        $signature = hash_hmac( 'sha1', $stringToSign, $signKey );
        $authorization = 'q-sign-algorithm=sha1&q-ak='. $this->accessKey .
        "&q-sign-time=$signTime&q-key-time=$signTime&q-header-list=$headerList&q-url-param-list=$urlParamList&" .
        "q-signature=$signature";
        return $authorization;
    }

    public function createPresignedUrl( RequestInterface $request, $expires = '+30 minutes' ) {
        $authorization = $this->createAuthorization( $request, $expires );
        $uri = $request->getUri();
        $query = 'sign='.urlencode( $authorization ) . '&' . $uri->getQuery();
        if ( $this->token != null ) {
            $query = $query.'&x-cos-security-token='.$this->token;
        }
        $uri = $uri->withQuery( $query );
        return $uri;
    }
}
