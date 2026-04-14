<?php
/**
 * @brief       ConflictResolver Unit Tests — All Section 2.2 Rules
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       14 Apr 2026
 *
 * Tests every conflict resolution rule required by Section 2.2:
 *   - Standard Priority (2.2.1): RSR > Sports South > Davidson's > Lipsey's > Zanders > Bill Hicks
 *   - Longest Text: description
 *   - Highest Resolution: image_url
 *   - Highest Value: msrp
 *   - Flagged for Review: rounds_per_box
 *   - Any True: nfa_item, requires_ffl
 *   - Merge All: additional_images
 *   - Admin Lock Bypass (2.2.3): locked fields skipped, feed conflict still written
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

	/**
	 * Test Longest Text Rule — description (Section 2.2.2).
	 *
	 * Scenarios covered:
	 *   1. Longer incoming replaces shorter current (regardless of priority)
	 *   2. Shorter incoming does NOT replace longer current
	 *   3. Incoming fills empty current
	 *   4. Empty incoming is ignored
	 */
	public function testLongestTextRule(): void
	{
		$log = $this->makeLog();

		/* Scenario 1: Bill Hicks (priority 6) has longer description — wins over RSR. */
		$product  = $this->makeProduct([ 'description' => 'Short desc', 'primary_source' => 'rsr_group' ]);
		$bhFeed   = $this->makeFeed( 'bill_hicks', 6 );
		$resolver = $this->makeResolver( $product, $bhFeed, $log );

		$result = $resolver->resolve( 'description', 'This is a much longer description that should win' );

		$this->assertTrue( $result['changed'], 'Longer description should win regardless of priority' );
		$this->assertSame( 'This is a much longer description that should win', $product->description );

		/* Scenario 2: Shorter incoming does not replace longer current. */
		$product2  = $this->makeProduct([ 'description' => 'A reasonably long existing description here', 'primary_source' => 'sports_south' ]);
		$rsrFeed   = $this->makeFeed( 'rsr_group', 1 );
		$resolver2 = $this->makeResolver( $product2, $rsrFeed, $log );

		$result2 = $resolver2->resolve( 'description', 'Short' );

		$this->assertFalse( $result2['changed'], 'Shorter incoming must NOT replace longer current' );
		$this->assertSame( 'A reasonably long existing description here', $product2->description );

		/* Scenario 3: Any text fills an empty description. */
		$product3  = $this->makeProduct([ 'description' => '', 'primary_source' => '' ]);
		$resolver3 = $this->makeResolver( $product3, $bhFeed, $log );

		$result3 = $resolver3->resolve( 'description', 'New desc' );

		$this->assertTrue( $result3['changed'], 'Any text should fill empty description' );
		$this->assertSame( 'New desc', $product3->description );

		/* Scenario 4: Empty incoming is ignored. */
		$product4  = $this->makeProduct([ 'description' => 'Existing', 'primary_source' => 'rsr_group' ]);
		$resolver4 = $this->makeResolver( $product4, $rsrFeed, $log );

		$result4 = $resolver4->resolve( 'description', '' );

		$this->assertFalse( $result4['changed'], 'Empty incoming should be ignored' );
		$this->assertSame( 'Existing', $product4->description );
	}

	/**
	 * Test Highest Resolution Rule — image_url (Section 2.2.2).
	 *
	 * The resolver extracts resolution from URL dimension hints (e.g. _800x600.jpg).
	 * Scenarios covered:
	 *   1. Higher resolution incoming replaces lower (regardless of priority)
	 *   2. Lower resolution incoming does NOT replace higher
	 *   3. Incoming fills empty current
	 *   4. Empty incoming is ignored
	 */
	public function testHighestResolutionRule(): void
	{
		$log = $this->makeLog();

		/* Scenario 1: Zanders has higher-res image — wins over RSR. */
		$product  = $this->makeProduct([ 'image_url' => 'https://rsr.com/img_400x300.jpg', 'primary_source' => 'rsr_group' ]);
		$zFeed    = $this->makeFeed( 'zanders', 5 );
		$resolver = $this->makeResolver( $product, $zFeed, $log );

		$result = $resolver->resolve( 'image_url', 'https://zanders.com/img_1200x900.jpg' );

		$this->assertTrue( $result['changed'], 'Higher resolution should win regardless of priority' );
		$this->assertSame( 'https://zanders.com/img_1200x900.jpg', $product->image_url );

		/* Scenario 2: Lower resolution incoming does not replace. */
		$product2  = $this->makeProduct([ 'image_url' => 'https://rsr.com/img_1200x900.jpg', 'primary_source' => 'rsr_group' ]);
		$bhFeed    = $this->makeFeed( 'bill_hicks', 6 );
		$resolver2 = $this->makeResolver( $product2, $bhFeed, $log );

		$result2 = $resolver2->resolve( 'image_url', 'https://bh.com/img_400x300.jpg' );

		$this->assertFalse( $result2['changed'], 'Lower resolution must NOT replace higher' );
		$this->assertSame( 'https://rsr.com/img_1200x900.jpg', $product2->image_url );

		/* Scenario 3: Incoming fills empty current. */
		$product3  = $this->makeProduct([ 'image_url' => '', 'primary_source' => '' ]);
		$resolver3 = $this->makeResolver( $product3, $zFeed, $log );

		$result3 = $resolver3->resolve( 'image_url', 'https://zanders.com/img_800x600.jpg' );

		$this->assertTrue( $result3['changed'], 'Any image should fill empty field' );
		$this->assertSame( 'https://zanders.com/img_800x600.jpg', $product3->image_url );

		/* Scenario 4: Empty incoming is ignored. */
		$product4  = $this->makeProduct([ 'image_url' => 'https://rsr.com/img_800x600.jpg', 'primary_source' => 'rsr_group' ]);
		$resolver4 = $this->makeResolver( $product4, $zFeed, $log );

		$result4 = $resolver4->resolve( 'image_url', '' );

		$this->assertFalse( $result4['changed'], 'Empty incoming should be ignored' );
		$this->assertSame( 'https://rsr.com/img_800x600.jpg', $product4->image_url );
	}

	/**
	 * Test Highest Value Rule — msrp (Section 2.2.2).
	 *
	 * Scenarios covered:
	 *   1. Higher MSRP incoming replaces lower (regardless of priority)
	 *   2. Lower MSRP incoming does NOT replace higher
	 *   3. Incoming fills empty/zero current
	 *   4. Empty incoming is ignored
	 *   5. Equal values produce no change
	 */
	public function testHighestValueRule(): void
	{
		$log = $this->makeLog();

		/* Scenario 1: Bill Hicks has higher MSRP — wins over RSR. */
		$product  = $this->makeProduct([ 'msrp' => '499.99', 'primary_source' => 'rsr_group' ]);
		$bhFeed   = $this->makeFeed( 'bill_hicks', 6 );
		$resolver = $this->makeResolver( $product, $bhFeed, $log );

		$result = $resolver->resolve( 'msrp', '599.99' );

		$this->assertTrue( $result['changed'], 'Higher MSRP should win regardless of priority' );
		$this->assertEquals( 599.99, $product->msrp );

		/* Scenario 2: Lower MSRP does not replace higher. */
		$product2  = $this->makeProduct([ 'msrp' => '599.99', 'primary_source' => 'rsr_group' ]);
		$resolver2 = $this->makeResolver( $product2, $bhFeed, $log );

		$result2 = $resolver2->resolve( 'msrp', '399.99' );

		$this->assertFalse( $result2['changed'], 'Lower MSRP must NOT replace higher' );
		$this->assertEquals( 599.99, $product2->msrp );

		/* Scenario 3: Incoming fills zero/empty MSRP. */
		$product3  = $this->makeProduct([ 'msrp' => '0', 'primary_source' => '' ]);
		$resolver3 = $this->makeResolver( $product3, $bhFeed, $log );

		$result3 = $resolver3->resolve( 'msrp', '249.99' );

		$this->assertTrue( $result3['changed'], 'Any MSRP should fill zero value' );
		$this->assertEquals( 249.99, $product3->msrp );

		/* Scenario 4: Empty incoming is ignored. */
		$product4  = $this->makeProduct([ 'msrp' => '499.99', 'primary_source' => 'rsr_group' ]);
		$resolver4 = $this->makeResolver( $product4, $bhFeed, $log );

		$result4 = $resolver4->resolve( 'msrp', '' );

		$this->assertFalse( $result4['changed'], 'Empty incoming should be ignored' );
		$this->assertEquals( 499.99, $product4->msrp );

		/* Scenario 5: Equal values — no change. */
		$product5  = $this->makeProduct([ 'msrp' => '499.99', 'primary_source' => 'rsr_group' ]);
		$resolver5 = $this->makeResolver( $product5, $bhFeed, $log );

		$result5 = $resolver5->resolve( 'msrp', '499.99' );

		$this->assertFalse( $result5['changed'], 'Equal MSRP should produce no change' );
		$this->assertFalse( $result5['conflict'], 'Equal values are not a conflict' );
	}

	/**
	 * Test Flagged for Review Rule — rounds_per_box (Section 2.2.2).
	 *
	 * When sources disagree on rounds_per_box, the record is set to admin_review
	 * status. The existing value is NOT overwritten — admin decides.
	 *
	 * Scenarios covered:
	 *   1. Disagreement flags record for admin review
	 *   2. Agreement produces no change and no flag
	 *   3. Incoming fills empty current (no conflict)
	 *   4. Empty incoming is ignored
	 */
	public function testFlaggedForReviewRule(): void
	{
		$log = $this->makeLog();

		/* Scenario 1: RSR says 20, Sports South says 25 — flag for review. */
		$product  = $this->makeProduct([ 'rounds_per_box' => '20', 'primary_source' => 'rsr_group', 'record_status' => 'active' ]);
		$ssFeed   = $this->makeFeed( 'sports_south', 2 );
		$resolver = $this->makeResolver( $product, $ssFeed, $log );

		$result = $resolver->resolve( 'rounds_per_box', '25' );

		$this->assertTrue( $result['changed'], 'Disagreement should trigger a change (status update)' );
		$this->assertTrue( $result['conflict'], 'Disagreement is a conflict' );
		$this->assertSame( 'admin_review', $product->record_status, 'Record must be flagged for admin review' );

		/* Scenario 2: Both agree on 20 — no change. */
		$product2  = $this->makeProduct([ 'rounds_per_box' => '20', 'primary_source' => 'rsr_group', 'record_status' => 'active' ]);
		$resolver2 = $this->makeResolver( $product2, $ssFeed, $log );

		$result2 = $resolver2->resolve( 'rounds_per_box', '20' );

		$this->assertFalse( $result2['changed'], 'Agreement should produce no change' );
		$this->assertFalse( $result2['conflict'], 'Agreement is not a conflict' );
		$this->assertSame( 'active', $product2->record_status, 'Status should remain active' );

		/* Scenario 3: Incoming fills empty current. */
		$product3  = $this->makeProduct([ 'rounds_per_box' => '', 'primary_source' => '', 'record_status' => 'active' ]);
		$resolver3 = $this->makeResolver( $product3, $ssFeed, $log );

		$result3 = $resolver3->resolve( 'rounds_per_box', '50' );

		$this->assertTrue( $result3['changed'], 'Should fill empty rounds_per_box' );
		$this->assertFalse( $result3['conflict'], 'Filling empty field is not a conflict' );
		$this->assertSame( '50', $product3->rounds_per_box );
		$this->assertSame( 'active', $product3->record_status, 'Status should remain active when filling gap' );

		/* Scenario 4: Empty incoming is ignored. */
		$product4  = $this->makeProduct([ 'rounds_per_box' => '20', 'primary_source' => 'rsr_group', 'record_status' => 'active' ]);
		$resolver4 = $this->makeResolver( $product4, $ssFeed, $log );

		$result4 = $resolver4->resolve( 'rounds_per_box', '' );

		$this->assertFalse( $result4['changed'], 'Empty incoming should be ignored' );
		$this->assertSame( '20', $product4->rounds_per_box );
	}

	/**
	 * Test Any True Rule — nfa_item, requires_ffl (Section 2.2.2).
	 *
	 * Conservative flag: if ANY distributor says true, record stays true.
	 * A lower-priority source cannot set it back to false.
	 *
	 * Scenarios covered:
	 *   1. Incoming true sets currently-false field to true
	 *   2. Incoming false does NOT unset a true field
	 *   3. Both false — no change
	 *   4. Both true — no change
	 */
	public function testAnyTrueRule(): void
	{
		$log = $this->makeLog();

		/* Scenario 1: Bill Hicks flags nfa_item true — sets product true. */
		$product  = $this->makeProduct([ 'nfa_item' => 0, 'primary_source' => 'rsr_group' ]);
		$bhFeed   = $this->makeFeed( 'bill_hicks', 6 );
		$resolver = $this->makeResolver( $product, $bhFeed, $log );

		$result = $resolver->resolve( 'nfa_item', 1 );

		$this->assertTrue( $result['changed'], 'Incoming true should set field to true' );
		$this->assertEquals( 1, $product->nfa_item, 'nfa_item must be true' );

		/* Scenario 2: Incoming false cannot unset true. */
		$product2  = $this->makeProduct([ 'nfa_item' => 1, 'primary_source' => 'sports_south' ]);
		$rsrFeed   = $this->makeFeed( 'rsr_group', 1 );
		$resolver2 = $this->makeResolver( $product2, $rsrFeed, $log );

		$result2 = $resolver2->resolve( 'nfa_item', 0 );

		$this->assertFalse( $result2['changed'], 'Incoming false must NOT unset true field' );
		$this->assertTrue( $result2['conflict'], 'Disagreement should be logged as conflict' );
		$this->assertEquals( 1, $product2->nfa_item, 'nfa_item must remain true' );

		/* Scenario 3: Both false — no change. */
		$product3  = $this->makeProduct([ 'requires_ffl' => 0, 'primary_source' => 'rsr_group' ]);
		$resolver3 = $this->makeResolver( $product3, $bhFeed, $log );

		$result3 = $resolver3->resolve( 'requires_ffl', 0 );

		$this->assertFalse( $result3['changed'], 'Both false should produce no change' );
		$this->assertFalse( $result3['conflict'], 'Both false is not a conflict' );

		/* Scenario 4: Both true — no change. */
		$product4  = $this->makeProduct([ 'requires_ffl' => 1, 'primary_source' => 'rsr_group' ]);
		$resolver4 = $this->makeResolver( $product4, $rsrFeed, $log );

		$result4 = $resolver4->resolve( 'requires_ffl', 1 );

		$this->assertFalse( $result4['changed'], 'Both true should produce no change' );
		$this->assertFalse( $result4['conflict'], 'Both true is not a conflict' );
	}

	/**
	 * Test Merge All Rule — additional_images (Section 2.2.2).
	 *
	 * Merges all unique image URLs from all distributors.
	 *
	 * Scenarios covered:
	 *   1. New URLs are merged into existing set
	 *   2. Duplicate URLs are deduplicated
	 *   3. Empty incoming is ignored
	 *   4. JSON array incoming is parsed correctly
	 */
	public function testMergeAllRule(): void
	{
		$log = $this->makeLog();

		/* Scenario 1: New URLs merged into existing set. */
		$product  = $this->makeProduct([ 'additional_images' => '["https://img1.com/a.jpg"]', 'primary_source' => 'rsr_group' ]);
		$ssFeed   = $this->makeFeed( 'sports_south', 2 );
		$resolver = $this->makeResolver( $product, $ssFeed, $log );

		$result = $resolver->resolve( 'additional_images', 'https://img2.com/b.jpg,https://img3.com/c.jpg' );

		$this->assertTrue( $result['changed'], 'New URLs should be merged' );
		$images = $product->getAdditionalImages();
		$this->assertCount( 3, $images, 'Should have 3 unique images' );
		$this->assertContains( 'https://img1.com/a.jpg', $images );
		$this->assertContains( 'https://img2.com/b.jpg', $images );
		$this->assertContains( 'https://img3.com/c.jpg', $images );

		/* Scenario 2: Duplicate URLs are deduplicated. */
		$product2  = $this->makeProduct([ 'additional_images' => '["https://img1.com/a.jpg","https://img2.com/b.jpg"]', 'primary_source' => 'rsr_group' ]);
		$resolver2 = $this->makeResolver( $product2, $ssFeed, $log );

		$result2 = $resolver2->resolve( 'additional_images', 'https://img1.com/a.jpg,https://img2.com/b.jpg' );

		$this->assertFalse( $result2['changed'], 'Duplicate URLs should not count as a change' );
		$this->assertCount( 2, $product2->getAdditionalImages(), 'Should still have 2 images' );

		/* Scenario 3: Empty incoming is ignored. */
		$product3  = $this->makeProduct([ 'additional_images' => '["https://img1.com/a.jpg"]', 'primary_source' => 'rsr_group' ]);
		$resolver3 = $this->makeResolver( $product3, $ssFeed, $log );

		$result3 = $resolver3->resolve( 'additional_images', '' );

		$this->assertFalse( $result3['changed'], 'Empty incoming should be ignored' );
		$this->assertCount( 1, $product3->getAdditionalImages() );

		/* Scenario 4: JSON array incoming is parsed correctly. */
		$product4  = $this->makeProduct([ 'additional_images' => '["https://img1.com/a.jpg"]', 'primary_source' => 'rsr_group' ]);
		$resolver4 = $this->makeResolver( $product4, $ssFeed, $log );

		$result4 = $resolver4->resolve( 'additional_images', '["https://img4.com/d.jpg","https://img5.com/e.jpg"]' );

		$this->assertTrue( $result4['changed'], 'JSON array incoming should be parsed and merged' );
		$images4 = $product4->getAdditionalImages();
		$this->assertCount( 3, $images4, 'Should have 3 images after JSON merge' );
		$this->assertContains( 'https://img4.com/d.jpg', $images4 );
		$this->assertContains( 'https://img5.com/e.jpg', $images4 );
	}

	/**
	 * Test Admin Lock Bypass (Section 2.2.3).
	 *
	 * Locked fields are immune to all future feed imports.
	 * A feed conflict record is still written when incoming differs from locked value.
	 *
	 * Scenarios covered:
	 *   1. Locked field is NOT overwritten by any distributor
	 *   2. Feed conflict is written when incoming differs from locked value
	 *   3. No feed conflict written when incoming matches locked value
	 */
	public function testAdminLockBypass(): void
	{
		$log = $this->makeLog();

		/* Scenario 1: Locked brand field — RSR cannot overwrite. */
		$product  = $this->makeProduct([
			'brand'         => 'Admin Set Brand',
			'primary_source'=> 'sports_south',
			'locked_fields' => '["brand"]',
		]);
		$rsrFeed  = $this->makeFeed( 'rsr_group', 1 );
		$resolver = $this->makeResolver( $product, $rsrFeed, $log );

		$result = $resolver->resolve( 'brand', 'RSR Brand' );

		$this->assertFalse( $result['changed'], 'Locked field must NOT be overwritten' );
		$this->assertFalse( $result['conflict'], 'Lock bypass does not report as standard conflict' );
		$this->assertSame( 'Admin Set Brand', $product->brand, 'Brand must stay admin-set value' );

		/* Scenario 2: Feed conflict record written when incoming differs. */
		$this->assertCount( 1, $resolver->loggedConflicts, 'Should write one feed conflict for differing locked field' );
		$this->assertSame( 'brand', $resolver->loggedConflicts[0]['field'] );
		$this->assertSame( 'Admin Set Brand', $resolver->loggedConflicts[0]['current'] );
		$this->assertSame( 'RSR Brand', $resolver->loggedConflicts[0]['incoming'] );

		/* Scenario 3: No feed conflict when incoming matches locked value. */
		$product2  = $this->makeProduct([
			'brand'         => 'Same Brand',
			'primary_source'=> 'rsr_group',
			'locked_fields' => '["brand"]',
		]);
		$resolver2 = $this->makeResolver( $product2, $rsrFeed, $log );

		$resolver2->resolve( 'brand', 'Same Brand' );

		$this->assertCount( 0, $resolver2->loggedConflicts, 'No feed conflict when incoming matches locked value' );
	}
}
