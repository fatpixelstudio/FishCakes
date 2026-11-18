/**
 * ESLint config for the FishCakes front-end and admin scripts.
 * No build step: the shipped files are the source, so lint targets them.
 */

module.exports = [
	{
		files: [ 'assets/js/**/*.js' ],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'script',
			globals: {
				window: 'readonly',
				document: 'readonly',
				location: 'readonly',
				Element: 'readonly',
				CustomEvent: 'readonly',
				DOMContentLoaded: 'readonly',
				setTimeout: 'readonly',
				jQuery: 'readonly',
			},
		},
		rules: {
			// Keep the runtime future-proof but dependency-free.
			'no-var': 'error',
			eqeqeq: 'error',
			'prefer-const': 'error',
			'no-implicit-globals': 'error',
		},
	},
	{
		ignores: [ 'node_modules/**', 'wordpress/**' ],
	},
];
