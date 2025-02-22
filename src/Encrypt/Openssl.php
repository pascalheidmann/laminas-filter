<?php

declare(strict_types=1);

namespace Laminas\Filter\Encrypt;

use Laminas\Filter\Compress;
use Laminas\Filter\Decompress;
use Laminas\Filter\Exception;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function array_key_exists;
use function count;
use function current;
use function extension_loaded;
use function fclose;
use function fopen;
use function fread;
use function is_array;
use function is_file;
use function is_readable;
use function is_string;
use function md5;
use function openssl_free_key;
use function openssl_open;
use function openssl_pkey_get_details;
use function openssl_pkey_get_private;
use function openssl_pkey_get_public;
use function openssl_seal;
use function pack;
use function strlen;
use function substr;
use function unpack;

use const PHP_VERSION_ID;

/**
 * Encryption adapter for openssl
 */
class Openssl implements EncryptionAlgorithmInterface
{
    /**
     * Definitions for encryption
     *
     * @var array{public: string[], private: string[], envelope: string[]}
     */
    protected array $keys = [
        'public'   => [],
        'private'  => [],
        'envelope' => [],
    ];

    /**
     * Internal passphrase
     */
    protected string $passphrase;

    /**
     * Internal compression
     *
     * @var array
     */
    protected array $compression;

    /**
     * Internal create package
     */
    protected bool $package = false;

    /**
     * Available options
     *   'public'      => public key
     *   'private'     => private key
     *   'envelope'    => envelope key
     *   'passphrase'  => passphrase
     *   'compression' => compress value with this compression adapter
     *   'package'     => pack envelope keys into encrypted string, simplifies decryption
     *
     * @param string|array|Traversable $options Options for this adapter
     * @throws Exception\ExtensionNotLoadedException
     */
    public function __construct($options = [])
    {
        if (! extension_loaded('openssl')) {
            throw new Exception\ExtensionNotLoadedException('This filter needs the openssl extension');
        }

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (! is_array($options)) {
            $options = ['public' => $options];
        }

        if (array_key_exists('passphrase', $options)) {
            $this->setPassphrase($options['passphrase']);
            unset($options['passphrase']);
        }

        if (array_key_exists('compression', $options)) {
            $this->setCompression($options['compression']);
            unset($options['compression']);
        }

        if (array_key_exists('package', $options)) {
            $this->setPackage($options['package']);
            unset($options['package']);
        }

        $this->_setKeys($options);
    }

    /**
     * Sets the encryption keys
     *
     * @param  string|array $keys Key with type association
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    // @codingStandardsIgnoreStart
    protected function _setKeys($keys)
    {
        // @codingStandardsIgnoreEnd
        if (! is_array($keys)) {
            throw new Exception\InvalidArgumentException('Invalid options argument provided to filter');
        }

        foreach ($keys as $type => $key) {
            if (is_string($key) && is_file($key) && is_readable($key)) {
                $file = fopen($key, 'r');
                $cert = fread($file, 8192);
                fclose($file);
            } else {
                $cert = $key;
                $key  = count($this->keys[$type]);
            }

            switch ($type) {
                case 'public':
                    $test = openssl_pkey_get_public($cert);
                    if ($test === false) {
                        throw new Exception\InvalidArgumentException("Public key '{$cert}' not valid");
                    }

                    $this->freeKeyResources([$test]);
                    $this->keys['public'][$key] = $cert;
                    break;
                case 'private':
                    $test = openssl_pkey_get_private($cert, $this->passphrase);
                    if ($test === false) {
                        throw new Exception\InvalidArgumentException("Private key '{$cert}' not valid");
                    }

                    $this->freeKeyResources([$test]);
                    $this->keys['private'][$key] = $cert;
                    break;
                case 'envelope':
                    $this->keys['envelope'][$key] = $cert;
                    break;
                default:
                    break;
            }
        }

        return $this;
    }

    /**
     * Returns all public keys
     *
     * @return array
     */
    public function getPublicKey()
    {
        return $this->keys['public'];
    }

    /**
     * Sets public keys
     *
     * @param  string|array $key Public keys
     * @return self
     */
    public function setPublicKey($key)
    {
        if (is_array($key)) {
            foreach ($key as $type => $option) {
                if ($type !== 'public') {
                    $key['public'] = $option;
                    unset($key[$type]);
                }
            }
        } else {
            $key = ['public' => $key];
        }

        return $this->_setKeys($key);
    }

    /**
     * Returns all private keys
     *
     * @return array
     */
    public function getPrivateKey()
    {
        return $this->keys['private'];
    }

    /**
     * Sets private keys
     *
     * @param  string|array $key Private key
     * @param  string|null $passphrase
     * @return self
     */
    public function setPrivateKey($key, $passphrase = null)
    {
        if (is_array($key)) {
            foreach ($key as $type => $option) {
                if ($type !== 'private') {
                    $key['private'] = $option;
                    unset($key[$type]);
                }
            }
        } else {
            $key = ['private' => $key];
        }

        if ($passphrase !== null) {
            $this->setPassphrase($passphrase);
        }

        return $this->_setKeys($key);
    }

    /**
     * Returns all envelope keys
     *
     * @return array
     */
    public function getEnvelopeKey()
    {
        return $this->keys['envelope'];
    }

    /**
     * Sets envelope keys
     *
     * @param  string|array $key Envelope keys
     * @return self
     */
    public function setEnvelopeKey($key)
    {
        if (is_array($key)) {
            foreach ($key as $type => $option) {
                if ($type !== 'envelope') {
                    $key['envelope'] = $option;
                    unset($key[$type]);
                }
            }
        } else {
            $key = ['envelope' => $key];
        }

        return $this->_setKeys($key);
    }

    /**
     * Returns the passphrase
     *
     * @return string
     */
    public function getPassphrase()
    {
        return $this->passphrase;
    }

    /**
     * Sets a new passphrase
     *
     * @param string $passphrase
     * @return self
     */
    public function setPassphrase($passphrase)
    {
        $this->passphrase = $passphrase;
        return $this;
    }

    /**
     * Returns the compression
     *
     * @return array
     */
    public function getCompression()
    {
        return $this->compression;
    }

    /**
     * Sets an internal compression for values to encrypt
     *
     * @param string|array $compression
     * @return self
     */
    public function setCompression($compression)
    {
        if (is_string($compression)) {
            $compression = ['adapter' => $compression];
        }

        $this->compression = $compression;
        return $this;
    }

    /**
     * Returns if header should be packaged
     *
     * @return bool
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Sets if the envelope keys should be included in the encrypted value
     *
     * @param  bool $package
     * @return self
     */
    public function setPackage($package)
    {
        $this->package = (bool) $package;
        return $this;
    }

    /**
     * Encrypts $value with the defined settings
     * Note that you also need the "encrypted" keys to be able to decrypt
     *
     * @param  string $value Content to encrypt
     * @return string The encrypted content
     * @throws Exception\RuntimeException
     */
    public function encrypt($value)
    {
        $encrypted     = [];
        $encryptedkeys = [];

        if (! $this->keys['public']) {
            throw new Exception\RuntimeException('Openssl can not encrypt without public keys');
        }

        $keys         = [];
        $fingerprints = [];
        $count        = -1;
        foreach ($this->keys['public'] as $key => $cert) {
            $keys[$key] = openssl_pkey_get_public($cert);
            if ($this->package) {
                $details = openssl_pkey_get_details($keys[$key]);
                if ($details === false) {
                    $details = ['key' => 'Laminas'];
                }

                ++$count;
                $fingerprints[$count] = md5($details['key']);
            }
        }

        // compress prior to encryption
        if (! empty($this->compression)) {
            $compress = new Compress($this->compression);
            $value    = $compress($value);
        }

        $crypt = openssl_seal($value, $encrypted, $encryptedkeys, $keys, 'RC4');

        $this->freeKeyResources($keys);

        if ($crypt === false) {
            throw new Exception\RuntimeException('Openssl was not able to encrypt your content with the given options');
        }

        $this->keys['envelope'] = $encryptedkeys;

        // Pack data and envelope keys into single string
        if ($this->package) {
            $header = pack('n', count($this->keys['envelope']));
            foreach ($this->keys['envelope'] as $key => $envKey) {
                $header .= pack('H32n', $fingerprints[$key], strlen($envKey)) . $envKey;
            }

            $encrypted = $header . $encrypted;
        }

        return $encrypted;
    }

    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Decrypts $value with the defined settings
     *
     * @param  string $value Content to decrypt
     * @return string The decrypted content
     * @throws Exception\RuntimeException
     */
    public function decrypt($value)
    {
        $decrypted = '';
        $envelope  = current($this->getEnvelopeKey());

        if (count($this->keys['private']) !== 1) {
            throw new Exception\RuntimeException('Please give a private key for decryption with Openssl');
        }

        if (! $this->package && empty($envelope)) {
            throw new Exception\RuntimeException('Please give an envelope key for decryption with Openssl');
        }

        foreach ($this->keys['private'] as $cert) {
            $keys = openssl_pkey_get_private($cert, $this->getPassphrase());
        }

        if ($this->package) {
            $details = openssl_pkey_get_details($keys);
            if ($details !== false) {
                $fingerprint = md5($details['key']);
            } else {
                $fingerprint = md5("Laminas");
            }

            $count  = unpack('ncount', $value);
            $count  = $count['count'];
            $length = 2;
            for ($i = $count; $i > 0; --$i) {
                $header  = unpack('H32print/nsize', substr($value, $length, 18));
                $length += 18;
                if ($header['print'] === $fingerprint) {
                    $envelope = substr($value, $length, $header['size']);
                }

                $length += $header['size'];
            }

            // remainder of string is the value to decrypt
            $value = substr($value, $length);
        }

        $crypt = openssl_open($value, $decrypted, $envelope, $keys, 'RC4');

        $this->freeKeyResources([$keys]);

        if ($crypt === false) {
            throw new Exception\RuntimeException('Openssl was not able to decrypt you content with the given options');
        }

        // decompress after decryption
        if (! empty($this->compression)) {
            $decompress = new Decompress($this->compression);
            $decrypted  = $decompress($decrypted);
        }

        return $decrypted;
    }

    /**
     * Returns the adapter name
     *
     * @return string
     */
    public function toString()
    {
        return 'Openssl';
    }

    /**
     * Free key resource if necessary.
     * PHP 8 automatically frees the key instance and deprecates the function
     *
     * @param array<int,resource> $keys
     */
    private function freeKeyResources(array $keys): void
    {
        if (PHP_VERSION_ID < 80000) {
            foreach ($keys as $key) {
                // phpcs:ignore
                openssl_free_key($key);
            }
        }
    }
}
