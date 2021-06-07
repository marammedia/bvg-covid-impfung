<?php

final class Encryption {
  const AES_256_CBC = 'aes-256-cbc';

  private $_secret_key = '0cGoSBs1vxnMToPSvuVK1a4pJ2Qhk0CtvTFrC7whoHDqbHqhTMb7Uo9npc4Flb+c';
  private $_secret_iv  = '5w1V7uGoPHqdcgAplqV+AAzrZN/dBoPv0yYAEg/tNORF7vzC46cYtA9zNNAslgDZ';
  private $_encryption_key;
  private $_iv;

  public function __construct() {
    $this->_encryption_key = hash('sha256', $this->_secret_key);
    $this->_iv = substr(hash('sha256', $this->_secret_iv), 0, 16);
  }

  public function encryptString($data) {
    return base64_encode(openssl_encrypt($data, self::AES_256_CBC, $this->_encryption_key, 0, $this->_iv));
  }

  public function decryptString($data) {
    return openssl_decrypt(base64_decode($data), self::AES_256_CBC, $this->_encryption_key, 0, $this->_iv);
  }

}