<?php

namespace App\Libraries;

class SensitiveDataService
{
	protected string $hashPepper;

	public function __construct()
	{
		$pepper = env('security.hashPepper');
		$fallback = (string) (env('encryption.key') ?? APP_NAMESPACE . '_default_hash_pepper');
		$this->hashPepper = is_string($pepper) && trim($pepper) !== '' ? $pepper : $fallback;
	}

	public function normalize(?string $value): ?string
	{
		if ($value === null) {
			return null;
		}

		$normalized = trim($value);
		return $normalized === '' ? null : $normalized;
	}

	public function hashForLookup(?string $value): ?string
	{
		$normalized = $this->normalize($value);
		if ($normalized === null) {
			return null;
		}

		return hash_hmac('sha256', mb_strtolower($normalized), $this->hashPepper);
	}

	public function encryptNullable(?string $value): ?string
	{
		$normalized = $this->normalize($value);
		if ($normalized === null) {
			return null;
		}

		try {
			$encrypted = service('encrypter')->encrypt($normalized);
			return 'enc:v1:' . base64_encode($encrypted);
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function decryptNullable(?string $value): ?string
	{
		$normalized = $this->normalize($value);
		if ($normalized === null) {
			return null;
		}

		if (strpos($normalized, 'enc:v1:') !== 0) {
			return $normalized;
		}

		try {
			$payload = substr($normalized, 7);
			if ($payload === false) {
				return null;
			}

			$decoded = base64_decode($payload, true);
			if ($decoded === false) {
				return null;
			}

			return service('encrypter')->decrypt($decoded);
		} catch (\Throwable $e) {
			return null;
		}
	}
}
