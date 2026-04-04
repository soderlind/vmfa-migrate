/**
 * Preview panel — shows folder tree and stats before migration.
 *
 * @package
 */

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

export default function PreviewPanel({ preview }) {
	const { folders, stats } = preview;
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
		</div>
	);
}
