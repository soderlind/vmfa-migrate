/**
 * Source list component — displays detected migration sources.
 *
 * @package
 */

import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function SourceList({ sources, selectedSource, onSelect }) {
	return (
		<table className="widefat striped">
			<thead>
				<tr>
					<th>{__('Plugin', 'vmfa-migrate')}</th>
					<th>{__('Folders', 'vmfa-migrate')}</th>
					<th>{__('Assignments', 'vmfa-migrate')}</th>
					<th>{__('Action', 'vmfa-migrate')}</th>
				</tr>
			</thead>
			<tbody>
				{sources.map((source) => (
					<tr key={source.slug}>
						<td>{source.label}</td>
						<td>{source.folder_count}</td>
						<td>{source.assignment_count}</td>
						<td>
							<Button
								variant={
									selectedSource === source.slug
										? 'primary'
										: 'secondary'
								}
								size="small"
								onClick={() => onSelect(source.slug)}
							>
								{selectedSource === source.slug
									? __('Selected', 'vmfa-migrate')
									: __('Select', 'vmfa-migrate')}
							</Button>
						</td>
					</tr>
				))}
			</tbody>
		</table>
	);
}
