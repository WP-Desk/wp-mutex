<?php

declare(strict_types=1);

namespace WPDesk\Mutex;

final class LockKey {
	private const TOKEN_BYTES = 16;

	private string $resource;

	private string $token;

	private function __construct( string $resource, string $token ) {
		if ( '' === $resource ) {
			throw new \InvalidArgumentException( 'The lock resource cannot be empty.' );
		}

		if ( ! preg_match( '/^[a-f0-9]{32}$/', $token ) ) {
			throw new \InvalidArgumentException( 'The lock token is invalid.' );
		}

		$this->resource = $resource;
		$this->token    = $token;
	}

	public static function create( string $resource ): self {
		return new self( $resource, bin2hex( random_bytes( self::TOKEN_BYTES ) ) );
	}

	public static function fromString( string $serialized ): self {
		$padding    = strlen( $serialized ) % 4;
		$normalized = strtr( $serialized, '-_', '+/' );
		if ( 0 !== $padding ) {
			$normalized .= str_repeat( '=', 4 - $padding );
		}

		$decoded = base64_decode( $normalized, true );
		if ( false === $decoded ) {
			throw new \InvalidArgumentException( 'The serialized lock key is invalid.' );
		}

		try {
			$data = json_decode( $decoded, true, 2, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			throw new \InvalidArgumentException( 'The serialized lock key is invalid.', 0, $e );
		}

		if ( ! is_array( $data ) || ! isset( $data['resource'], $data['token'] ) || ! is_string( $data['resource'] ) || ! is_string( $data['token'] ) ) {
			throw new \InvalidArgumentException( 'The serialized lock key is invalid.' );
		}

		return new self( $data['resource'], $data['token'] );
	}

	public function getResource(): string {
		return $this->resource;
	}

	public function getToken(): string {
		return $this->token;
	}

	public function toString(): string {
		$json = json_encode(
			[
				'resource' => $this->resource,
				'token'    => $this->token,
			],
			JSON_THROW_ON_ERROR
		);

		return rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
	}
}
