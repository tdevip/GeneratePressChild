module.exports = function(grunt) {

	grunt.registerTask('default', ['watch']);
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-criticalcss');

	grunt.initConfig ({
		pkg: grunt.file.readJSON('package.json'),

		watch: {
			sass: {
				files: [
					"wp-content/themes/generatepress_child/assets/scss/**/*.scss"
				],
				tasks: [
					"sass",
					"cssmin",
					"compress"
				],
			}
		},

		sass: {
			gpc: {
				options: {
			        sourcemap: 'none'
			    },
				src: [
					"wp-content/themes/generatepress_child/assets/scss/style.scss"
				],
				dest: "wp-content/themes/generatepress_child/style.unmin.css"
			},
			critical: {
				options: {
			        sourcemap: 'none'
			    },
				src: [
					"wp-content/themes/generatepress_child/assets/scss/critical.scss"
				],
				dest:"wp-content/themes/generatepress_child/critical.unmin.css"
			},
		},

		cssmin: {
			gpc: {
				src: [
					"wp-content/themes/generatepress_child/style.unmin.css"
				],
				dest: "wp-content/themes/generatepress_child/style.css"
			},
			critical: {
				src: [
					"wp-content/themes/generatepress_child/critical.unmin.css"
				],
				dest:"wp-content/themes/generatepress_child/critical.css"
			},
		},

		compress: {
			gpc: {
				options: {
			      	mode: 'gzip'
			    },
				src: [
					"wp-content/themes/generatepress_child/style.css"
				],
				dest: "wp-content/themes/generatepress_child/style.gz.css"
			},
			critical: {
				options: {
			      	mode: 'gzip'
			    },
				src: [
					"wp-content/themes/generatepress_child/critical.css"
				],
				dest:"wp-content/themes/generatepress_child/critical.gz.css"
			},
		}
	});
}
