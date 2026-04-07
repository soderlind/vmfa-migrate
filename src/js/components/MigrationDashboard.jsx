/**
 * Migration Dashboard — main React component.
 *
 * Shows detected sources, preview, migration controls, and progress.
 *
 * @package
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Notice,
	SelectControl,
	Spinner,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import SourceList from './SourceList';
import PreviewPanel from './PreviewPanel';
import MigrationProgress from './MigrationProgress';

export default function MigrationDashboard() {
	const [sources, setSources] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [notice, setNotice] = useState(null);
	const [selectedSource, setSelectedSource] = useState(null);
	const [preview, setPreview] = useState(null);
	const [isLoadingPreview, setIsLoadingPreview] = useState(false);
	const [conflictStrategy, setConflictStrategy] = useState('skip');
	const [includeTaxonomies, setIncludeTaxonomies] = useState(false);
	const [jobId, setJobId] = useState(null);
	const [isMigrating, setIsMigrating] = useState(false);

	const fetchSources = useCallback(async () => {
		setIsLoading(true);
		try {
			const data = await apiFetch({ path: '/vmfa-migrate/v1/sources' });
			setSources(data);
		} catch (error) {
			setNotice({ status: 'error', message: error.message });
		} finally {
			setIsLoading(false);
		}
	}, []);

	// Fetch available sources on mount.
	useEffect(() => {
		fetchSources();
	}, [fetchSources]);

	const fetchPreview = useCallback(async (slug) => {
		setIsLoadingPreview(true);
		setPreview(null);
		try {
			const data = await apiFetch({
				path: `/vmfa-migrate/v1/sources/${slug}/preview`,
			});
			setPreview(data);
		} catch (error) {
			setNotice({ status: 'error', message: error.message });
		} finally {
			setIsLoadingPreview(false);
		}
	}, []);

	const handleSelectSource = useCallback(
		(slug) => {
			setSelectedSource(slug);
			setJobId(null);
			fetchPreview(slug);
		},
		[fetchPreview]
	);

	const startMigration = useCallback(async () => {
		if (!selectedSource) {
			return;
		}
		setIsMigrating(true);
		setNotice(null);
		try {
			const result = await apiFetch({
				path: `/vmfa-migrate/v1/sources/${selectedSource}/migrate`,
				method: 'POST',
				data: {
					conflict_strategy: conflictStrategy,
					include_taxonomies: includeTaxonomies,
					batch_size: 100,
				},
			});
			setJobId(result.job_id);
			setNotice({
				status: 'success',
				message: sprintf(
					/* translators: %d: number of folders created */
					__(
						'Migration started. %d folders created.',
						'vmfa-migrate'
					),
					result.folders_created
				),
			});
		} catch (error) {
			setNotice({ status: 'error', message: error.message });
		} finally {
			setIsMigrating(false);
		}
	}, [selectedSource, conflictStrategy, includeTaxonomies]);

	if (isLoading) {
		return (
			<div className="vmfa-migrate-loading">
				<Spinner />
				<p>{__('Detecting migration sources…', 'vmfa-migrate')}</p>
			</div>
		);
	}

	return (
		<div className="vmfa-migrate-dashboard">
			{notice && (
				<Notice
					status={notice.status}
					isDismissible
					onRemove={() => setNotice(null)}
				>
					{notice.message}
				</Notice>
			)}

			<Card>
				<CardHeader>
					<Text variant="title.small">
						{__('Detected Sources', 'vmfa-migrate')}
					</Text>
				</CardHeader>
				<CardBody>
					{sources.length === 0 ? (
						<p>
							{__(
								'No compatible media folder plugins detected. Install or keep data from a supported plugin to migrate.',
								'vmfa-migrate'
							)}
						</p>
					) : (
						<SourceList
							sources={sources}
							selectedSource={selectedSource}
							onSelect={handleSelectSource}
						/>
					)}
				</CardBody>
			</Card>

			{selectedSource && (
				<>
					<Card style={{ marginTop: '16px' }}>
						<CardHeader>
							<Text variant="title.small">
								{__('Preview', 'vmfa-migrate')}
							</Text>
						</CardHeader>
						<CardBody>
							{isLoadingPreview && <Spinner />}
							{!isLoadingPreview && preview && (
								<PreviewPanel
									preview={preview}
									includeTaxonomies={includeTaxonomies}
									onIncludeTaxonomiesChange={setIncludeTaxonomies}
								/>
							)}
						</CardBody>
					</Card>

					<Card style={{ marginTop: '16px' }}>
						<CardHeader>
							<Text variant="title.small">
								{__('Migration Options', 'vmfa-migrate')}
							</Text>
						</CardHeader>
						<CardBody>
							<SelectControl
								label={__(
									'If a folder with the same name already exists',
									'vmfa-migrate'
								)}
								value={conflictStrategy}
								options={[
									{
										label: __(
											'Skip — reuse the existing folder and assign media to it',
											'vmfa-migrate'
										),
										value: 'skip',
									},
									{
										label: __(
											'Merge — reuse the existing folder, assign media, and log each reuse',
											'vmfa-migrate'
										),
										value: 'merge',
									},
									{
										label: __(
											'Rename — create a new folder with the source plugin name appended',
											'vmfa-migrate'
										),
										value: 'overwrite',
									},
								]}
								onChange={setConflictStrategy}
								help={__(
									'Choose what happens when a source folder has the same name and parent as an existing Virtual Media Folder.',
									'vmfa-migrate'
								)}
							/>
							<div style={{ marginTop: '16px' }}>
								<Button
									variant="primary"
									onClick={startMigration}
									isBusy={isMigrating}
									disabled={isMigrating || !!jobId}
								>
									{isMigrating
										? __('Starting…', 'vmfa-migrate')
										: __('Start Migration', 'vmfa-migrate')}
								</Button>
							</div>
						</CardBody>
					</Card>
				</>
			)}

			{jobId && (
				<Card style={{ marginTop: '16px' }}>
					<CardHeader>
						<Text variant="title.small">
							{__('Migration Progress', 'vmfa-migrate')}
						</Text>
					</CardHeader>
					<CardBody>
						<MigrationProgress jobId={jobId} />
					</CardBody>
				</Card>
			)}
		</div>
	);
}
