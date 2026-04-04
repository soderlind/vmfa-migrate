/**
 * Tests for MigrationProgress component.
 *
 * @package VmfaMigrate
 */

import { render, screen, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import MigrationProgress from '../../src/js/components/MigrationProgress';

describe( 'MigrationProgress', () => {
	beforeEach( () => {
		vi.useFakeTimers();
		apiFetch.mockReset();
	} );

	afterEach( () => {
		vi.useRealTimers();
	} );

	it( 'shows spinner while loading', () => {
		apiFetch.mockReturnValue( new Promise( () => {} ) ); // never resolves
		render( <MigrationProgress jobId="abc123" /> );

		expect( document.querySelector( '.spinner' ) ).toBeInTheDocument();
	} );

	it( 'shows progress after job data loads', async () => {
		vi.useRealTimers();
		apiFetch.mockResolvedValue( {
			status: 'processing',
			total: 100,
			processed: 50,
			assigned: 40,
			skipped: 5,
			errors: 0,
			folders_created: 10,
		} );

		render( <MigrationProgress jobId="abc123" /> );

		await waitFor( () => {
			expect( screen.getByText( /processing/ ) ).toBeInTheDocument();
		} );
	} );

	it( 'shows error notice on API failure', async () => {
		vi.useRealTimers();
		apiFetch.mockRejectedValue( new Error( 'Network error' ) );

		render( <MigrationProgress jobId="abc123" /> );

		await waitFor( () => {
			expect( screen.getByText( 'Network error' ) ).toBeInTheDocument();
		} );
	} );
} );
