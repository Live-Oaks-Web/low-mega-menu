/** @type {import('tailwindcss').Config} */
module.exports = {
	prefix: 'low-',
	content: [
		'../includes/modules/**/render.php',
		'../includes/render/**/*.php',
		'./src/styles/**/*.css',
	],
	theme: {
		extend: {
			screens: {
				'low-mm-desktop': '1024px',
			},
		},
	},
	plugins: [],
};
