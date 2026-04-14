<?php
/**
 * @brief       ConflictResolver Unit Tests — Standard Priority Rule
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       14 Apr 2026
 *
 * Section 2.2.1: RSR Group > Sports South > Davidson's > Lipsey's > Zanders > Bill Hicks
 * Tests: higher priority wins, lower priority loses, gap-filling when current is empty,
 * empty incoming is ignored, equal priority overwrites.
 */

namespace IPS\gdcatalog\tests;

use PHPUnit\Framework\TestCase;

class ConflictResolverTest extends TestCase
{
	/**
	 * Build a minimal Product stub with the given field values.
	 */
	protected function makeProduct( array $data = [] ): object
	{
		$defaults = [
			'upc'              => '123456789012',
			'title'            => '',
			'brand'            => '',
			'caliber'          => '',
			'primary_source'   => '',
			'locked_fields'    => '[]',
			'additional_images'=> '[]',
			'record_status'    => 'active',
		];

		$fields = array_merge( $defaults, $data );

		return new class( $fields ) {
			private array $data;

			public function __construct( array $data )
			{
				$this->data = $data;
			}

			public function __get( string $name ): mixed
			{
				return $this->data[$name] ?? null;
			}

			public function __set( string $name, mixed $value ): void
			{
				$this->data[$name] = $value;
			}

			public function isFieldLocked( string $field ): bool
			{
				$locked = json_decode( $this->data['locked_fields'] ?? '[]', true );
				return \in_array( $field, $locked ?: [], true );
			}

			public function getAdditionalImages(): array
			{
				return json_decode( $this->data['additional_images'] ?? '[]', true ) ?: [];
			}

			public function mergeAdditionalImages( array $urls ): void
			{
				$existing = $this->getAdditionalImages();
				$merged = array_values( array_unique( array_merge( $existing, $urls ) ) );
				$this->data['additional_images'] = json_encode( $merged );
			}
		};
	}

	/**
	 * Build a minimal Distributor stub.
	 */
	protected function makeFeed( string $distributor, int $id = 1 ): object
	{
		return new class( $distributor, $id ) {
			public string $distributor;
			public int $id;

			public function __construct( string $distributor, int $id )
			{
				$this->distributor = $distributor;
				$this->id = $id;
			}
		};
	}

	/**
	 * Build a minimal ImportLog stub.
	 */
	protected function makeLog( int $id = 1 ): object
	{
		return new class( $id ) {
			public int $id;

			public function __construct( int $id )
			{
				$this->id = $id;
			}
		};
	}

	/**
	 * Build a testable ConflictResolver that stubs out DB calls.
	 * Overrides isLocked() to use only the product's locked_fields JSON,
	 * and captures conflict log writes instead of hitting the database.
	 */
	protected function makeResolver( object $product, object $feed, object $log ): object
	{
		return new class( $product, $feed, $log ) extends \IPS\gdcatalog\Feed\ConflictResolver {
			/** @var array Captured conflict log entries */
			public array $loggedConflicts = [];

			public function __construct( object $product, object $feed, object $log )
			{
				$this->product = $product;
				$this->feed    = $feed;
				$this->log     = $log;
			}

			protected function isLocked( string $field ): bool
			{
				return $this->product->isFieldLocked( $field );
			}

			protected function writeFeedConflict( string $field, mixed $currentValue, mixed $incomingValue ): void
			{
				$this->loggedConflicts[] = [
					'field'    => $field,
					'current'  => $currentValue,
					'incoming' => $incomingValue,
				];
			}
		};
	}

	/**
	 * Test Standard Priority Rule (Section 2.2.1).
	 *
	 * Scenarios covered:
	 *   1. RSR (priority 1) overwrites Sports South (priority 2)
	 *   2. Sports South (priority 2) does NOT overwrite RSR (priority 1)
	 *   3. Any distributor fills an empty field regardless of priority
	 *   4. Empty incoming value is ignored (no change)
	 *   5. Same-priority distributor overwrites (equal rank)
	 */
	public function testStandardPriorityRule(): void
	{
		/*
		 * Scenario 1: RSR overwrites a value currently owned by Sports South.
		 * "brand" uses RULE_PRIORITY (not in $fieldRules special list).
		 */
		$product  = $this->makeProduct([ 'brand' => 'Old Brand', 'primary_source' => 'sports_south' ]);
		$rsrFeed  = $this->makeFeed( 'rsr_group', 1 );
		$log      = $this->makeLog();
		$resolver = $this->makeResolver( $product, $rsrFeed, $log );

		$result = $resolver->resolve( 'brand', 'RSR Brand' );

		$this->assertTrue( $result['changed'], 'RSR (priority 1) should overwrite Sports South (priority 2)' );
		$this->assertTrue( $result['conflict'], 'Values differ so conflict should be true' );
		$this->assertSame( 'RSR Brand', $product->brand, 'Product brand should now be RSR value' );

		/*
		 * Scenario 2: Sports South cannot overwrite RSR.
		 */
		$product2  = $this->makeProduct([ 'brand' => 'RSR Brand', 'primary_source' => 'rsr_group' ]);
		$ssFeed    = $this->makeFeed( 'sports_south', 2 );
		$resolver2 = $this->makeResolver( $product2, $ssFeed, $log );

		$result2 = $resolver2->resolve( 'brand', 'SS Brand' );

		$this->assertFalse( $result2['changed'], 'Sports South (priority 2) must NOT overwrite RSR (priority 1)' );
		$this->assertTrue( $result2['conflict'], 'Values differ so conflict should be logged' );
		$this->assertSame( 'RSR Brand', $product2->brand, 'Product brand must remain RSR value' );

		/*
		 * Scenario 3: Low-priority distributor fills an empty field.
		 */
		$product3  = $this->makeProduct([ 'brand' => '', 'primary_source' => 'rsr_group' ]);
		$bhFeed    = $this->makeFeed( 'bill_hicks', 6 );
		$resolver3 = $this->makeResolver( $product3, $bhFeed, $log );

		$result3 = $resolver3->resolve( 'brand', 'BH Brand' );

		$this->assertTrue( $result3['changed'], 'Any distributor should fill an empty field' );
		$this->assertFalse( $result3['conflict'], 'Filling empty field is not a conflict' );
		$this->assertSame( 'BH Brand', $product3->brand, 'Brand should be filled by Bill Hicks' );

		/*
		 * Scenario 4: Empty incoming value does nothing.
		 */
		$product4  = $this->makeProduct([ 'brand' => 'Existing', 'primary_source' => 'rsr_group' ]);
		$resolver4 = $this->makeResolver( $product4, $rsrFeed, $log );

		$result4 = $resolver4->resolve( 'brand', '' );

		$this->assertFalse( $result4['changed'], 'Empty incoming should not change anything' );
		$this->assertFalse( $result4['conflict'], 'Empty incoming is not a conflict' );
		$this->assertSame( 'Existing', $product4->brand, 'Brand must stay unchanged' );

		/*
		 * Scenario 5: Same distributor re-importing overwrites (equal rank).
		 */
		$product5  = $this->makeProduct([ 'brand' => 'Old RSR', 'primary_source' => 'rsr_group' ]);
		$resolver5 = $this->makeResolver( $product5, $rsrFeed, $log );

		$result5 = $resolver5->resolve( 'brand', 'New RSR' );

		$this->assertTrue( $result5['changed'], 'Same distributor should overwrite its own value' );
		$this->assertSame( 'New RSR', $product5->brand, 'Brand should update to new RSR value' );
	}
}
