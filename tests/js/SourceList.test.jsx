/**
 * Tests for SourceList component.
 *
 * @package VmfaMigrate
 */

import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SourceList from '../../src/js/components/SourceList';

const sources = [
	{ slug: 'enhanced-media-library', label: 'Enhanced Media Library', folder_count: 5, assignment_count: 20 },
	{ slug: 'filebird', label: 'FileBird', folder_count: 3, assignment_count: 10 },
];

describe( 'SourceList', () => {
	it( 'renders all sources in a table', () => {
		render( <SourceList sources={ sources } selectedSource={ null } onSelect={ vi.fn() } /> );

		expect( screen.getByText( 'Enhanced Media Library' ) ).toBeInTheDocument();
		expect( screen.getByText( 'FileBird' ) ).toBeInTheDocument();
		expect( screen.getByText( '5' ) ).toBeInTheDocument();
		expect( screen.getByText( '20' ) ).toBeInTheDocument();
	} );

	it( 'shows "Selected" for the active source', () => {
		render( <SourceList sources={ sources } selectedSource="filebird" onSelect={ vi.fn() } /> );

		const buttons = screen.getAllByRole( 'button' );
		expect( buttons[ 0 ] ).toHaveTextContent( 'Select' );
		expect( buttons[ 1 ] ).toHaveTextContent( 'Selected' );
	} );

	it( 'calls onSelect when a source is clicked', async () => {
		const user = userEvent.setup();
		const onSelect = vi.fn();
		render( <SourceList sources={ sources } selectedSource={ null } onSelect={ onSelect } /> );

		await user.click( screen.getAllByRole( 'button' )[ 0 ] );

		expect( onSelect ).toHaveBeenCalledWith( 'enhanced-media-library' );
	} );
} );
