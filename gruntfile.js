module.exports = function( grunt ) {

	var pkg = grunt.file.readJSON( 'package.json' );

	console.log( pkg.title + ' - ' + pkg.version );

	// Files to include/exclude in a release.
	var distFiles = [
		'**',
		'!assets/css/sass/**',
		'!assets/images/wp/**',
		'!bower_components/**',
		'!build/**',
		'!node_modules/**',
		'!.editorconfig',
		'!.gitignore',
		'!.jscsrc',
		'!.jshintrc',
		'!bower.json',
		'!composer.json',
		'!composer.lock',
		'!contributing.md',
		'!gruntfile.js',
		'!package.json',
		'!readme.md',
		'!**/*~'
	];

	grunt.initConfig( {

		pkg: pkg,

		banner: '/*! <%= pkg.title %> - <%= pkg.version %>\n' +
		        ' * <%=pkg.homepage %>\n' +
		        ' * Copyright (c) Moonstone Media <%= grunt.template.today("yyyy") %>\n' +
		        ' * Licensed GPLv2+' +
		        ' */\n',

		checktextdomain: {
			options: {
				text_domain: 'google-calendar-events',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: [
					'includes/**/*.php',
					'google-calendar-events.php',
					'uninstall.php'
				],
				expand: true
			}
		},

		clean: {
			build: [ 'build' ]
		},

		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './build/simple-calendar-<%= pkg.version %>.zip'
				},
				expand: true,
				src: distFiles,
				dest: '/google-calendar-events'
			}
		},

		// 'css' & 'js' tasks need to copy vendor-minified assets from bower folder to assets folder (select2, etc).
		copy: {
			css: {
				expand: true,
				cwd: 'bower_components/',
				flatten: true,
				src: [
					'select2/dist/css/select2.css',
					'select2/dist/css/select2.min.css'
				],
				dest: 'assets/css/vendor/'
			},
			js: {
				expand: true,
				cwd: 'bower_components/',
				flatten: true,
				src: [
					'imagesloaded/imagesloaded.pkgd.js', // Using "packaged" version
					'imagesloaded/imagesloaded.pkgd.min.js',
					'jquery-tiptip/jquery.tipTip.js',
					'jquery-tiptip/jquery.tipTip.minified.js',
					'select2/dist/js/select2.js', // Using "non-full" version
					'select2/dist/js/select2.min.js',
				    'moment/moment.js',
				    'moment/min/moment.min.js',
				    'moment-timezone/builds/moment-timezone-with-data.js',
				    'moment-timezone/builds/moment-timezone-with-data.min.js'
				],
				dest: 'assets/js/vendor/'
			},
			main: {
				expand: true,
				src: distFiles,
				dest: 'build/google-calendar-events'
			}
		},

		cssmin: {
			options: {
				processImport: false,
				keepSpecialComments: 0
			},
			minify: {
				expand: true,
				cwd: 'assets/css',
				src: [
					'*.css',
					'!*.min.css'
				],
				dest: 'assets/css',
				ext: '.min.css'
			}
		},

		jshint: {
			options: {
				ignores: [
					'**/*.min.js'
				]
			},
			all: [
				'assets/js/*.js',
				'gruntfile.js'
			]
		},

		postcss: {
			options: {
				processors: [
					require( 'autoprefixer' )( { browsers: 'last 2 versions' } )
				]
			},
			dist: {
				expand: true,
				cwd: 'assets/css',
				src: [
					'*.css',
					'!*.min.css'
				],
				dest: 'assets/css'
			}
		},

		sass: {
			options: {
				precision: 2,
				sourceMap: false
			},
			all: {
				files: [
					{
						expand: true,
						cwd: 'assets/css/sass/',
						src: [ '*.scss' ],
						dest: 'assets/css/',
						ext: '.css'
					}
				]
			}
		},

		uglify: {
			all: {
				files: {
					'assets/js/admin.min.js': [ 'assets/js/admin.js' ],
					'assets/js/admin-add-calendar.min.js': [ 'assets/js/admin-add-calendar.js' ],
					'assets/js/default-calendar.min.js': [ 'assets/js/default-calendar.js' ]
				},
				options: {
					mangle: {
						except: [ 'jQuery' ]
					},
					sourceMap: false,
					preserveComments: false
				}
			}
		},

		usebanner: {
			options: {
				position: 'top',
				banner: '<%= banner %>',
				linebreak: true
			},
			js: {
				files: {
					src: [ 'assets/js/*.min.js' ]
				}
			},
			css: {
				files: {
					src: [ 'assets/css/*.min.css' ]
				}
			}
		},

		// .scss to .css file watcher. Run when project is loaded in PhpStorm or other IDE.
		watch: {
			css: {
				files: '**/*.scss',
				tasks: [ 'sass' ]
			}
		}

	} );

	require( 'load-grunt-tasks' )( grunt );

	grunt.registerTask( 'css', [ 'sass', 'postcss', 'copy:css', 'cssmin', 'usebanner:css' ] );
	grunt.registerTask( 'js', [ 'copy:js', 'uglify', 'usebanner:js' ] );
	grunt.registerTask( 'default', [ 'css', 'jshint', 'js' ] );

	// TODO Add deploy task
	//grunt.registerTask( 'deploy',	['build'] );

	grunt.util.linefeed = '\n';
};
