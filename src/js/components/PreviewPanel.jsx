/**
 * Preview panel — shows folder tree and stats before migration.
 *
 * @package
 */

import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Build a nested tree from flat folder array.
 *
 * @param {Array} folders Flat folder array with id, name, parent_id.
 * @return {Array} Nested tree array.
 */
function buildTree(folders) {
	const map = {};
	const roots = [];

	folders.forEach((f) => {
		map[f.id] = { ...f, children: [] };
	});

	folders.forEach((f) => {
		if (f.parent_id > 0 && map[f.parent_id]) {
			map[f.parent_id].children.push(map[f.id]);
		} else {
			roots.push(map[f.id]);
		}
	});

	return roots;
}

function FolderTree({ nodes, depth = 0 }) {
	return (
		<ul
			style={{ paddingLeft: depth > 0 ? '20px' : '0', listStyle: 'none' }}
		>
			{nodes.map((node) => (
				<li key={node.id} style={{ marginBottom: '4px' }}>
					<span>📁 {node.name}</span>
					{node.children.length > 0 && (
						<FolderTree nodes={node.children} depth={depth + 1} />
					)}
				</li>
			))}
		</ul>
	);
}

export default function PreviewPanel({
	preview,
	includeTaxonomies,
	onIncludeTaxonomiesChange,
}) {
	const { folders, stats, taxonomies = [] } = preview;
	const tree = buildTree(folders);

	return (
		<div className="vmfa-migrate-preview">
			<p>
				<strong>{__('Folders:', 'vmfa-migrate')}</strong>{' '}
				{stats.folder_count}
				{' | '}
				<strong>{__('Assignments:', 'vmfa-migrate')}</strong>{' '}
				{stats.assignment_count}
			</p>

			{tree.length > 0 ? (
				<FolderTree nodes={tree} />
			) : (
				<p>{__('No folders found.', 'vmfa-migrate')}</p>
			)}

			{taxonomies.length > 0 && (
				<div className="vmfa-migrate-preview__taxonomies" style={{ marginTop: '16px' }}>
					<h4 style={{ marginBottom: '8px' }}>
						{__('Additional Taxonomies', 'vmfa-migrate')}
					</h4>
					<table className="widefat striped" style={{ marginTop: '8px' }}>
						<thead>
							<tr>
								<th>{__('Taxonomy', 'vmfa-migrate')}</th>
								<th>{__('Terms', 'vmfa-migrate')}</th>
								<th>{__('Assignments', 'vmfa-migrate')}</th>
							</tr>
						</thead>
						<tbody>
							{taxonomies.map((tax) => (
								<tr key={tax.slug}>
									<td>{tax.label}</td>
									<td>{tax.term_count}</td>
									<td>{tax.assign_count}</td>
								</tr>
							))}
						</tbody>
					</table>
					<div style={{ marginTop: '12px' }}>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={__(
								'Include these taxonomies in migration',
								'vmfa-migrate'
							)}
							help={__(
								'Terms and assignments are stored as WordPress taxonomies on each attachment. You can access them with get_the_terms() and wp_get_object_terms().',
								'vmfa-migrate'
							)}
							checked={includeTaxonomies}
							onChange={onIncludeTaxonomiesChange}
						/>
					</div>
				</div>
			)}
		</div>
	);
}
