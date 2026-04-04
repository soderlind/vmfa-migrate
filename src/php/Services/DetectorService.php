<?php
/**
 * Detector service — discovers available migration sources.
 *
 * @package VmfaMigrate
 */

declare(strict_types=1);

namespace VmfaMigrate\Services;

defined( 'ABSPATH' ) || exit;

use VmfaMigrate\Drivers\CatFoldersDriver;
use VmfaMigrate\Drivers\DriverInterface;
use VmfaMigrate\Drivers\EnhancedMediaLibraryDriver;
use VmfaMigrate\Drivers\FileBirdDriver;
use VmfaMigrate\Drivers\HappyFilesDriver;
use VmfaMigrate\Drivers\MediaLibraryAssistantDriver;
use VmfaMigrate\Drivers\RealMediaLibraryDriver;
use VmfaMigrate\Drivers\WpMediaFolderDriver;

/**
 * Iterates all registered drivers and returns available migration sources.
 */
final class DetectorService {

	/**
	 * All registered driver class names.
	 *
	 * @var array<int, class-string<DriverInterface>>
	 */
	private array $driver_classes;

	/**
	 * Cached driver instances.
	 *
	 * @var array<string, DriverInterface>
	 */
	private array $instances = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->driver_classes = [
			EnhancedMediaLibraryDriver::class,
			HappyFilesDriver::class,
			WpMediaFolderDriver::class,
			MediaLibraryAssistantDriver::class,
			FileBirdDriver::class,
			RealMediaLibraryDriver::class,
			CatFoldersDriver::class,
		];

		/**
		 * Filter the list of migration driver classes.
		 *
		 * Allows third-party plugins to add custom drivers.
		 *
		 * @param array<int, class-string<DriverInterface>> $driver_classes Driver class names.
		 */
		$this->driver_classes = apply_filters( 'vmfa_migrate_drivers', $this->driver_classes );
	}

	/**
	 * Get all available migration sources with stats.
	 *
	 * @return array<int, array{slug: string, label: string, folder_count: int, assignment_count: int}>
	 */
	public function get_available_sources(): array {
		$sources = [];

		foreach ( $this->driver_classes as $class ) {
			$driver = $this->get_driver_instance( $class );

			if ( ! $driver->is_available() ) {
				continue;
			}

			$stats     = $driver->get_stats();
			$sources[] = [
				'slug'             => $driver::slug(),
				'label'            => $driver::label(),
				'folder_count'     => $stats['folder_count'],
				'assignment_count' => $stats['assignment_count'],
			];
		}

		return $sources;
	}

	/**
	 * Get a driver instance by slug.
	 *
	 * @param string $slug Driver slug.
	 * @return DriverInterface|null
	 */
	public function get_driver( string $slug ): ?DriverInterface {
		foreach ( $this->driver_classes as $class ) {
			if ( $class::slug() === $slug ) {
				return $this->get_driver_instance( $class );
			}
		}

		return null;
	}

	/**
	 * Get or create a driver instance.
	 *
	 * @param string $class_name Driver class name.
	 * @return DriverInterface
	 */
	private function get_driver_instance( string $class_name ): DriverInterface {
		$slug = $class_name::slug();

		if ( ! isset( $this->instances[ $slug ] ) ) {
			$this->instances[ $slug ] = new $class_name();
		}

		return $this->instances[ $slug ];
	}
}
