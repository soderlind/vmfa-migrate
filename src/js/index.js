/**
 * Migration Dashboard — React entry point.
 *
 * @package
 */

import { createRoot } from '@wordpress/element';
import MigrationDashboard from './components/MigrationDashboard';

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('vmfa-migrate-app');
	if (container) {
		createRoot(container).render(<MigrationDashboard />);
	}
});
