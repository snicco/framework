<?php


    declare(strict_types = 1);


    namespace WPEmerge;

    use Illuminate\Contracts\Encryption\EncryptException as IlluminateEncryptException;
    use Illuminate\Contracts\Encryption\DecryptException as IlluminateDecryptException;
    use Illuminate\Encryption\Encrypter;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\ExceptionHandling\Exceptions\EncryptException;
    use WPEmerge\ExceptionHandling\Exceptions\DecryptException;
    use WPEmerge\Support\Str;

    class Encryptor implements EncryptorInterface
    {

        /**
         * @var Encrypter
         */
        private $encryptor;

        public function __construct(string $key)
        {

            $this->encryptor = new Encrypter($this->parseKey($key), 'AES-256-CBC');

        }

        public function encrypt($value, bool $serialize = true) : string
        {

            try {

                return $this->encryptor->encrypt($value, $serialize);

            }
            catch (IlluminateEncryptException $e) {

                throw new EncryptException($e->getMessage());

            }


        }

        public function decrypt(string $payload, bool $unserialize = true)
        {

            try {

                return $this->encryptor->decrypt($payload, $unserialize);

            }
            catch (IlluminateDecryptException $e) {

                throw new DecryptException($e->getMessage());

            }


        }

        public function encryptString(string $value) : string
        {

            try {

                return $this->encryptor->encrypt($value, false);

            }
            catch (IlluminateEncryptException $e) {

                throw new EncryptException($e->getMessage());

            }

        }

        public function decryptString(string $payload) : string
        {

            try {

                return $this->encryptor->decrypt($payload, false);

            }
            catch (IlluminateDecryptException $e) {

                throw new DecryptException($e->getMessage());

            }

        }

        public static function generateKey() : string
        {

            return 'base64:'.base64_encode(random_bytes(32));

        }

        private function parseKey(string $key) : string
        {

            if (Str::startsWith($key, $prefix = 'base64:')) {

                $key = base64_decode(Str::after($key, $prefix));

            }

            return $key;
        }

        public static function validAppKey(string $key) : bool
        {

            if (Str::startsWith($key, $prefix = 'base64:')) {

                $key = base64_decode(Str::after($key, $prefix));

            }

            if ( ! static::supported($key)) {

                return false;

            }

            return true;

        }

        private static function supported(string $key) : bool
        {

            $length = mb_strlen($key, '8bit');

            return $length === 32;

        }

    }