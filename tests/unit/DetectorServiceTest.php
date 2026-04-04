<?php
/**
 * Tests for DetectorService.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function () {
	Monkey\setUp();
} );

afterEach( function () {
	Monkey\tearDown();
} );

it( 'returns empty array when no sources are available', function () {
	// Mock WordPress functions used by drivers.
	Functions\when( 'taxonomy_exists' )->justReturn( false );
	Functions\when( 'apply_filters' )->returnArg( 2 );

	// Mock $wpdb.
	global $wpdb;
	$wpdb = Mockery::mock( 'wpdb' );
	$wpdb->prefix = 'wp_';
	$wpdb->term_taxonomy = 'wp_term_taxonomy';
	$wpdb->terms = 'wp_terms';
	$wpdb->term_relationships = 'wp_term_relationships';

	$wpdb->shouldReceive( 'prepare' )->andReturn( '' );
	$wpdb->shouldReceive( 'get_var' )->andReturn( '0' );

	$detector = new VmfaMigrate\Services\DetectorService();
	$sources  = $detector->get_available_sources();

	expect( $sources )->toBeArray()->toBeEmpty();
} );

it( 'returns a driver by slug', function () {
	Functions\when( 'apply_filters' )->returnArg( 2 );

	$detector = new VmfaMigrate\Services\DetectorService();
	$driver   = $detector->get_driver( 'enhanced-media-library' );

	expect( $driver )->toBeInstanceOf( VmfaMigrate\Drivers\EnhancedMediaLibraryDriver::class );
} );

it( 'returns null for unknown slug', function () {
	Functions\when( 'apply_filters' )->returnArg( 2 );

	$detector = new VmfaMigrate\Services\DetectorService();
	$driver   = $detector->get_driver( 'nonexistent-plugin' );

	expect( $driver )->toBeNull();
} );
