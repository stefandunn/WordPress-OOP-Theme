var mix = require('laravel-mix');

// Enable source maps in development
if (!mix.inProduction()) {
    mix.webpackConfig({devtool: 'inline-source-map'})
}

mix.options({
	postCss: [
		require('autoprefixer')({
			browsers: [
				'>0.1%',
				'since 2012'
			],
		}),
		require('css-mqpacker')({
			sort: function (a, b) {
				var medias = [
					a.replace(/(\w+)(\s+(?:and\s+)?)(?:\()((?:max-width|min-width|-webkit-min-device-pixel-ratio)?\:\s+)(\d+)(.+)/, '$1'),
					b.replace(/(\w+)(\s+(?:and\s+)?)(?:\()((?:max-width|min-width|-webkit-min-device-pixel-ratio)?\:\s+)(\d+)(.+)/, '$1')
				];
				var queries = [
					a.replace(/(\w+)(\s+(?:and\s+)?)(?:\()((?:max-width|min-width|-webkit-min-device-pixel-ratio)?\:\s+)(\d+)(.+)/, '$3'),
					b.replace(/(\w+)(\s+(?:and\s+)?)(?:\()((?:max-width|min-width|-webkit-min-device-pixel-ratio)?\:\s+)(\d+)(.+)/, '$3')
				];
				
				var values = [
					parseInt(a.replace(/(\w+)(\s+(?:and\s+)?)(?:\()((?:max-width|min-width|-webkit-min-device-pixel-ratio)?\:\s+)(\d+)(.+)/, '$4')),
					parseInt(b.replace(/(\w+)(\s+(?:and\s+)?)(?:\()((?:max-width|min-width|-webkit-min-device-pixel-ratio)?\:\s+)(\d+)(.+)/, '$4'))
				];
				
				// If comparing max-widths
				if (queries[0].match(/max\-width/) && queries[1].match(/max\-width/))					// If a is larger than b, then return true
					return (values[0] < values[1]);
				// If comparing min-widths
				if (queries[0].match(/max\-width/) && queries[1].match(/max\-width/)) {
					return (values[0] > values[1]);
				}
				
      			return a.localeCompare(b);
      		}
		})
	]
});

// Compile BABEL ES2015
mix.babel([
	'./node_modules/jquery/dist/jquery.min.js',
	'./js/start.js',
	'./js/helpers.js',
	'./js/main.js',
	'./js/_pages/*.js',
	'./js/end.js',
], './js/src/main.min.js');

// Compile SASS
mix.sass('./css/main.scss', './css/src/main.min.css').options({
  processCssUrls: false
}).sourceMaps();

mix.copy('./css/src/main.min.css', './editor-style.css');