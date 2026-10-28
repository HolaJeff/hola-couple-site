<?php

namespace HW\Integrations\ClassicVacations\CRM\Utils;

use Exception;
use HW\Utils\Card;
use Jose\Component\Core\JWK;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\CompactSerializer;

class PaymentJWEBuilder{

    protected static $last_error;

    /**
     * builder method for JWE
     *
     * @param string $jwt
     * @param string $number
     * @param string $month
     * @param string $year
     * @param string $ccv
     * @param string $type
     * @return PaymentJWE
     */
    public static function tokenize($jwt, Card $card){

        $logger = hw_cv_get_logger();

        // build Payment Payload
        $jwe = new JWEPayload();
        $jwe->setJWT($jwt);
        $jwe->setCardNumber($card->getNumber());
        $jwe->setCardType($card->getType());
        $jwe->setCCV($card->getCCV());
        $jwe->setExpiration($card->getExpYear(), $card->getExpMonth());

        //
        $payload = $jwe->toJSON();

        // build the JWE
        $time = time();
        list($headersB64, $payloadB64, $sig) = explode('.', $jwt);
        $jwt_payload = json_decode(base64_decode($payloadB64), true); // get JWT payload into associative array
        
        // get the encryptation key from JWT token
        $jwk_data = $jwt_payload['flx']['jwk'];
        $jwk = new JWK($jwk_data);

        //Build JWE
        $keyEncryptionAlgorithmManager = new AlgorithmManager([
            new RSAOAEP256(),
        ]);

        $contentEncryptionAlgorithmManager = new AlgorithmManager([
            new A256GCM()
        ]);

        $compressionMethodManager = new CompressionMethodManager();

        // We instantiate our JWE Builder.
        $jweBuilder = new JWEBuilder(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );

        try{

            $jwe = $jweBuilder
                ->create()              // We want to create a new JWE
                ->withPayload($payload) // We set the payload
                ->withSharedProtectedHeader([
                    'alg' => 'RSA-OAEP-256',        // Key Encryption Algorithm
                    'enc' => 'A256GCM', // Content Encryption Algorithm
                    'kid' => $jwk_data['kid']
                ])
                ->addRecipient($jwk)    // We add a recipient (a shared key or public key).
                ->build();

            $serializer = new CompactSerializer();

            $token = $serializer->serialize($jwe, 0);
            
        }catch(Exception $e){

            self::$last_error = $e->getMessage();
            $logger->error($e->getMessage(), 'PaymentJWEBuilder::tokenize');
            
            return false;
        }

        return $token;
    }

    /**
     * return the last error
     *
     * @return RuntimeException
     */
    public static function get_last_error(){

        return self::$last_error;
    }
}