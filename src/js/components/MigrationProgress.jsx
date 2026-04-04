/**
 * Migration progress component — polls job status and shows progress.
 *
 * @package
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function MigrationProgress({ jobId }) {
	const [job, setJob] = useState(null);
	const [error, setError] = useState(null);
	const intervalRef = useRef(null);

	useEffect(() => {
		const fetchJob = async () => {
			try {
				const data = await apiFetch({
					path: `/vmfa-migrate/v1/jobs/${jobId}`,
				});
				setJob(data);

				if (
					data.status === 'completed' ||
					data.status === 'cancelled' ||
					data.status === 'error'
				) {
					clearInterval(intervalRef.current);
				}
			} catch (err) {
				setError(err.message);
				clearInterval(intervalRef.current);
			}
		};

		fetchJob();
		intervalRef.current = setInterval(fetchJob, 2000);

		return () => clearInterval(intervalRef.current);
	}, [jobId]);

	if (error) {
		return (
			<Notice status="error" isDismissible={false}>
				{error}
			</Notice>
		);
	}

	if (!job) {
		return <Spinner />;
	}

	const progress =
		job.total > 0 ? Math.round((job.processed / job.total) * 100) : 0;

	return (
		<div className="vmfa-migrate-progress">
			<div
				style={{
					background: '#e0e0e0',
					borderRadius: '4px',
					overflow: 'hidden',
					height: '24px',
					marginBottom: '12px',
				}}
			>
				<div
					style={{
						background:
							job.status === 'completed' ? '#00a32a' : '#2271b1',
						height: '100%',
						width: `${progress}%`,
						transition: 'width 0.3s ease',
						borderRadius: '4px',
					}}
				/>
			</div>

			<p>
				<strong>{__('Status:', 'vmfa-migrate')}</strong> {job.status}
				{' — '}
				{job.processed} / {job.total}{' '}
				{__('assignments processed', 'vmfa-migrate')}
			</p>

			<table className="widefat" style={{ maxWidth: '400px' }}>
				<tbody>
					<tr>
						<td>{__('Folders created', 'vmfa-migrate')}</td>
						<td>{job.folders_created}</td>
					</tr>
					<tr>
						<td>{__('Assigned', 'vmfa-migrate')}</td>
						<td>{job.assigned}</td>
					</tr>
					<tr>
						<td>{__('Skipped', 'vmfa-migrate')}</td>
						<td>{job.skipped}</td>
					</tr>
					<tr>
						<td>{__('Errors', 'vmfa-migrate')}</td>
						<td>{job.errors}</td>
					</tr>
				</tbody>
			</table>

			{job.status === 'completed' && (
				<Notice status="success" isDismissible={false}>
					{__('Migration completed successfully!', 'vmfa-migrate')}
				</Notice>
			)}
		</div>
	);
}
