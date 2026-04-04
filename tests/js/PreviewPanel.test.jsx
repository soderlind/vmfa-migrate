/**
 * Tests for PreviewPanel component.
 *
 * @package VmfaMigrate
 */

import { render, screen } from '@testing-library/react';
import PreviewPanel from '../../src/js/components/PreviewPanel';

const preview = {
	folders: [
		{ id: 1, name: 'Photos', parent_id: 0 },
		{ id: 2, name: 'Portraits', parent_id: 1 },
		{ id: 3, name: 'Documents', parent_id: 0 },
	],
	stats: { folder_count: 3, assignment_count: 15 },
};

describe( 'PreviewPanel', () => {
	it( 'renders folder stats', () => {
		render( <PreviewPanel preview={ preview } /> );

		expect( screen.getByText( 'Folders:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Assignments:' ) ).toBeInTheDocument();
	} );

	it( 'renders top-level folders', () => {
		render( <PreviewPanel preview={ preview } /> );

		expect( screen.getByText( /Photos/ ) ).toBeInTheDocument();
		expect( screen.getByText( /Documents/ ) ).toBeInTheDocument();
	} );

	it( 'renders nested folder as child', () => {
		render( <PreviewPanel preview={ preview } /> );

		expect( screen.getByText( /Portraits/ ) ).toBeInTheDocument();
	} );

	it( 'shows message when no folders exist', () => {
		const emptyPreview = { folders: [], stats: { folder_count: 0, assignment_count: 0 } };
		render( <PreviewPanel preview={ emptyPreview } /> );

		expect( screen.getByText( 'No folders found.' ) ).toBeInTheDocument();
	} );
} );
