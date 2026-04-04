import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

const mockDir = path.resolve( __dirname, 'tests/js/__mocks__/@wordpress' );

export default defineConfig( {
	plugins: [ react() ],
	test: {
		globals: true,
		environment: 'jsdom',
		include: [ 'tests/js/**/*.test.{js,jsx}' ],
		setupFiles: [ 'tests/js/setup.js' ],
		coverage: {
			include: [ 'src/js/**/*.{js,jsx}' ],
		},
	},
	resolve: {
		alias: {
			'@wordpress/element': path.join( mockDir, 'element.js' ),
			'@wordpress/i18n': path.join( mockDir, 'i18n.js' ),
			'@wordpress/api-fetch': path.join( mockDir, 'api-fetch.js' ),
			'@wordpress/components': path.join( mockDir, 'components.jsx' ),
			'@wordpress/icons': path.join( mockDir, 'icons.js' ),
		},
	},
} );
