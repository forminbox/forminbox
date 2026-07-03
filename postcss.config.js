// Overrides @wordpress/scripts' default PostCSS setup. Tailwind's plugin
// also handles vendor prefixing, so autoprefixer is not needed here.
module.exports = {
	plugins: {
		'@tailwindcss/postcss': {},
	},
};
