<?php

declare(strict_types=1);

namespace WPDesk\Mutex\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPDesk\Mutex\LockKey;

final class LockKeyTest extends TestCase {
	public function test_it_round_trips_without_changing_ownership(): void {
		$key      = LockKey::create( 'order:123' );
		$restored = LockKey::fromString( $key->toString() );

		self::assertSame( $key->getResource(), $restored->getResource() );
		self::assertSame( $key->getToken(), $restored->getToken() );
	}

	public function test_each_key_has_a_distinct_owner(): void {
		$first  = LockKey::create( 'same-resource' );
		$second = LockKey::create( 'same-resource' );

		self::assertNotSame( $first->getToken(), $second->getToken() );
	}

	/** @dataProvider invalidSerializedKeys */
	public function test_it_rejects_invalid_serialized_keys( string $serialized ): void {
		$this->expectException( \InvalidArgumentException::class );

		LockKey::fromString( $serialized );
	}

	/** @return array<string, array{string}> */
	public function invalidSerializedKeys(): array {
		return [
			'not base64'       => [ '***' ],
			'not json'         => [ rtrim( strtr( base64_encode( 'invalid' ), '+/', '-_' ), '=' ) ],
			'missing token'    => [ rtrim( strtr( base64_encode( '{"resource":"x"}' ), '+/', '-_' ), '=' ) ],
			'malformed token'  => [ rtrim( strtr( base64_encode( '{"resource":"x","token":"bad"}' ), '+/', '-_' ), '=' ) ],
		];
	}
}
